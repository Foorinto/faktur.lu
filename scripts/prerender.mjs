/**
 * Prerender public pages to static HTML for crawlers (Google, ChatGPT, Gemini…).
 *
 * Why: the app is a client-rendered Inertia/Vue SPA — without JS, the H1/body/prices
 * live only inside a data-page JSON blob, invisible to non-JS consumers. This script
 * drives headless Chrome over the PUBLIC pages, lets the SPA render, and saves the
 * resulting HTML so the middleware (App\Http\Middleware\ServePrerendered) can serve
 * it to bots only. Real users keep getting the live SPA — the app is untouched.
 *
 * Run AGAINST a running app instance that has data (local `php artisan serve`, or
 * staging). Snapshots are written to public/prerendered/<path>/index.html and are
 * committed to git, then deployed via git pull (like public/build).
 *
 * Usage:
 *   node scripts/prerender.mjs                       # all sitemap URLs, BASE=http://127.0.0.1:8123
 *   BASE_URL=https://staging.faktur.lu node scripts/prerender.mjs
 *   node scripts/prerender.mjs --limit 5             # first 5 URLs (quick test)
 *   node scripts/prerender.mjs --only /fr,/fr/tarifs # explicit URLs
 */

import { mkdir, writeFile, readdir, rm } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer-core';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const OUT_DIR = join(ROOT, 'public', 'prerendered');

const BASE_URL = (process.env.BASE_URL || 'http://127.0.0.1:8123').replace(/\/$/, '');
const CHROME_PATH =
    process.env.CHROME_PATH ||
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const CONCURRENCY = Number(process.env.PRERENDER_CONCURRENCY || 4);

/**
 * Adresse publique du site, telle qu'elle doit apparaître dans les snapshots.
 *
 * Le rendu se fait contre une instance LOCALE (BASE_URL), et Laravel construit
 * ses URL absolues depuis l'hôte de la REQUÊTE, pas depuis APP_URL — cette
 * dernière ne vaut qu'en console. Les liens et les feuilles de style se
 * retrouvaient donc figés sur http://127.0.0.1:<port> dans le HTML servi aux
 * robots : 424 snapshots sur 430, près de 11 000 références mortes.
 *
 * Constaté le 2026-08-18. Le commentaire de deploy.sh affirmait que « les liens
 * internes sont relatifs » — ils ne le sont pas.
 */
const PUBLIC_URL = (process.env.PUBLIC_URL || 'https://faktur.lu').replace(/\/$/, '');

// --- CLI args ------------------------------------------------------------
const args = process.argv.slice(2);
const limitArg = args.indexOf('--limit');
const LIMIT = limitArg !== -1 ? Number(args[limitArg + 1]) : null;
const onlyArg = args.indexOf('--only');
const ONLY = onlyArg !== -1 ? args[onlyArg + 1].split(',').map((s) => s.trim()) : null;

// Filets de sécurité, après l'incident du 2026-09-17.
//
// Le blog avait disparu de la base LOCALE contre laquelle tourne le rendu.
// `/sitemap-blog.xml` a donc répondu 200 avec zéro URL, le script a généré les
// 210 pages restantes, et la purge a effacé les 255 snapshots d'articles avant
// que le rsync ne les supprime en production. Les robots ont reçu des articles
// vides. Rien dans la sortie ne disait « il en manque la moitié » : le résumé
// annonçait fièrement « 210/210 pages générées ».
//
// Une liste d'URL incomplète ne doit jamais servir de référence pour supprimer.
const ALLOW_EMPTY = args.includes('--allow-empty-sitemap');
const FORCE_PRUNE = args.includes('--force-prune');

// --- Collect the list of public URLs to prerender ------------------------
async function collectUrls() {
    if (ONLY) {
        return { urls: ONLY.map((p) => `${BASE_URL}${p.startsWith('/') ? p : `/${p}`}`), complet: false };
    }

    const sitemaps = ['/sitemap-pages.xml', '/sitemap-blog.xml'];
    const urls = new Set();
    let complet = true;

    for (const sm of sitemaps) {
        let res;
        try {
            res = await fetch(`${BASE_URL}${sm}`);
        } catch (e) {
            console.warn(`⚠️  ${sm} → injoignable (${e.message})`);
            complet = false;
            continue;
        }
        if (!res.ok) {
            console.warn(`⚠️  ${sm} → HTTP ${res.status}, ignoré`);
            complet = false;
            continue;
        }
        const xml = await res.text();
        const avant = urls.size;
        for (const m of xml.matchAll(/<loc>([^<]+)<\/loc>/g)) {
            // Normalize the host to BASE_URL (sitemap may use the prod host).
            const path = new URL(m[1]).pathname;
            urls.add(`${BASE_URL}${path}`);
        }
        // Un sitemap vide n'est jamais une nouvelle rassurante : soit la section
        // a réellement disparu, soit — bien plus souvent — la base interrogée
        // n'est pas celle qu'on croit.
        if (urls.size === avant) {
            console.warn(`⚠️  ${sm} n'a déclaré AUCUNE URL.`);
            complet = false;
        }
    }

    const list = [...urls];

    return { urls: LIMIT ? list.slice(0, LIMIT) : list, complet };
}

/**
 * Trim the snapshot to what crawlers actually need. Snapshots are served to bots
 * only (they never hydrate), so we drop:
 *  - the huge `data-page` JSON blob on the Inertia root (duplicates all translations),
 *  - the Vite module scripts and modulepreload links (bots don't execute them).
 * We KEEP all <head> SEO (title, meta, canonical, hreflang, OG, JSON-LD) and the
 * full rendered body — content stays equivalent to the live page (no cloaking).
 */
function stripForBots(html) {
    return normaliserHote(html)
        // data-page="{…}" — inner quotes are HTML-escaped (&quot;), so the attribute
        // value contains no literal " until its closing delimiter.
        .replace(/\sdata-page="[^"]*"/, ' data-page=""')
        // Vite entry + page chunk module scripts.
        .replace(/<script\b[^>]*\btype="module"[^>]*><\/script>/g, '')
        // modulepreload / preload links for JS chunks.
        .replace(/<link\b[^>]*\brel="modulepreload"[^>]*>/g, '');
}

/**
 * Remplace l'hôte local par l'adresse publique, partout.
 *
 * Y compris dans les scripts restants : la configuration Ziggy y déclare son
 * `url`, et un moteur qui exécute le JavaScript reconstruirait sinon des liens
 * vers 127.0.0.1. La chaîne échappée (\/) est traitée aussi, JSON obligeant.
 */
function normaliserHote(html) {
    if (BASE_URL === PUBLIC_URL) return html;

    const echappe = (v) => v.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    // L'adresse apparaît à trois niveaux d'échappement : telle quelle dans les
    // href, échappée une fois dans la configuration Ziggy (JSON), et deux fois
    // dans le manifeste de préchargement de Vite (JSON dans une chaîne JS).
    // Plutôt que d'énumérer les trois, on capture les barres obliques quelles
    // qu'elles soient et on les restitue à l'identique.
    const schemaLocal = BASE_URL.replace(/:\/\/.*$/, '');           // http
    const hoteLocal = BASE_URL.replace(/^[a-z]+:\/\//, '');          // 127.0.0.1:8129
    const schemaPublic = PUBLIC_URL.replace(/:\/\/.*$/, '');         // https
    const hotePublic = PUBLIC_URL.replace(/^[a-z]+:\/\//, '');       // faktur.lu

    const motif = new RegExp(
        `${echappe(schemaLocal)}:((?:\\\\*/){2})${echappe(hoteLocal)}`,
        'g'
    );

    return html.replace(motif, (_, barres) => `${schemaPublic}:${barres}${hotePublic}`);
}


// Map a URL path to its snapshot file: /fr/tarifs → public/prerendered/fr/tarifs/index.html
function fileForPath(pathname) {
    const clean = pathname.replace(/^\/+|\/+$/g, ''); // trim slashes
    return join(OUT_DIR, clean || 'root', 'index.html');
}

async function renderOne(browser, url) {
    const page = await browser.newPage();
    try {
        await page.setViewport({ width: 1280, height: 900 });
        // domcontentloaded (not networkidle0): the latter is flaky on remote servers
        // with analytics beacons / keep-alive. We don't need network idle — we wait
        // explicitly for the Inertia/Vue render below.
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
        // Wait until Inertia/Vue has mounted real content (an <h1> with text).
        await page.waitForFunction(
            () => {
                const h1 = document.querySelector('h1');
                return h1 && h1.innerText.trim().length > 0;
            },
            { timeout: 30000, polling: 250 },
        );
        const html = stripForBots(await page.content());
        const file = fileForPath(new URL(url).pathname);
        await mkdir(dirname(file), { recursive: true });
        await writeFile(file, html, 'utf8');
        return { url, ok: true, bytes: html.length };
    } catch (err) {
        return { url, ok: false, error: err.message };
    } finally {
        await page.close();
    }
}

/**
 * Supprime les snapshots dont l'URL n'est plus au sitemap.
 *
 * Sans cela ils survivent indéfiniment, et le middleware ServePrerendered
 * continue de les servir AUX ROBOTS alors que la route redirige ou n'existe
 * plus. Un visiteur voit la redirection, Googlebot voit l'ancienne page :
 * l'inverse de ce qu'on veut, et un signal de dissimulation.
 *
 * Ne touche qu'aux dossiers contenant un index.html, jamais au reste.
 */
async function pruneOrphans(urls) {
    // JAMAIS avec --only ni --limit.
    //
    // La purge compare le dossier à la liste des URL rendues. Avec un rendu
    // partiel, cette liste ne compte que les quelques pages demandées : tout le
    // reste passe pour orphelin et disparaît. Constaté deux fois — les 430
    // snapshots effacés pour en régénérer un seul.
    //
    // Le nettoyage n'a de sens qu'après un rendu COMPLET, seul cas où l'absence
    // d'une page du sitemap signifie vraiment qu'elle a été supprimée.
    if (ONLY || LIMIT) {
        console.log('\n(purge ignorée : rendu partiel)');
        return;
    }

    const attendus = new Set(urls.map((u) => fileForPath(new URL(u).pathname)));
    const candidats = [];
    let total = 0;

    // On recense d'abord, on supprime ensuite : sans ce temps d'arrêt, il n'y a
    // aucun endroit où poser une limite.
    async function parcourir(dir) {
        let entrees;
        try {
            entrees = await readdir(dir, { withFileTypes: true });
        } catch {
            return;
        }

        for (const e of entrees) {
            const chemin = join(dir, e.name);
            if (e.isDirectory()) {
                await parcourir(chemin);
                continue;
            }
            if (e.name !== 'index.html') {
                continue;
            }
            total++;
            if (!attendus.has(chemin)) {
                candidats.push(chemin);
            }
        }
    }

    await parcourir(OUT_DIR);

    if (candidats.length === 0) {
        return 0;
    }

    // Une purge légitime retire quelques pages renommées ou fusionnées. Effacer
    // un quart du dossier d'un coup signale une liste d'URL fausse, pas un
    // ménage. Dans le doute, on garde : un snapshot périmé se corrige au
    // déploiement suivant, un snapshot effacé se paie en référencement.
    const seuil = Math.max(10, Math.ceil(total * 0.25));

    if (candidats.length > seuil && !FORCE_PRUNE) {
        console.log(`\n⚠️  Purge REFUSÉE : ${candidats.length} snapshot(s) sur ${total} seraient supprimés (seuil ${seuil}).`);
        console.log('   Une suppression de cette ampleur vient presque toujours d\'une liste d\'URL incomplète.');
        console.log('   Vérifiez les sitemaps, puis relancez avec --force-prune si la suppression est voulue.');
        candidats.slice(0, 10).forEach((c) => console.log(`   · ${dirname(c).replace(OUT_DIR, '')}`));
        if (candidats.length > 10) {
            console.log(`   · … et ${candidats.length - 10} autre(s)`);
        }

        return 0;
    }

    const retires = [];

    for (const chemin of candidats) {
        await rm(dirname(chemin), { recursive: true, force: true });
        retires.push(dirname(chemin).replace(OUT_DIR, ''));
    }

    console.log(`\n🧹 ${retires.length} snapshot(s) orphelin(s) supprimé(s) :`);
    retires.forEach((r) => console.log(`   ${r}`));

    return retires.length;
}
// Simple concurrency pool.
async function run() {
    const { urls, complet } = await collectUrls();

    // On s'arrête AVANT de générer : sans liste fiable, tout ce qui suit est
    // dangereux. Ne rien faire laisse les snapshots en place, et deploy.sh
    // refuse alors le transfert. C'est le comportement qui aurait évité de
    // supprimer 255 articles pour les robots le 2026-09-17.
    if (!complet && !ONLY && !ALLOW_EMPTY) {
        console.error(`
✗ Liste d'URL incomplète : un sitemap a échoué ou n'a déclaré aucune URL.

  Rien n'a été généré, rien n'a été supprimé — c'est volontaire.

  Vérifiez d'abord que ${BASE_URL} sert bien les données attendues :
  une base locale vidée rend un sitemap vide, et la purge effacerait
  alors les snapshots correspondants en production.

  Pour passer outre en connaissance de cause : --allow-empty-sitemap
`);
        process.exit(1);
    }

    console.log(`Prerendering ${urls.length} URL(s) depuis ${BASE_URL} → ${OUT_DIR}\n`);

    // Generate against a LOCAL instance (BASE_URL). Generating against the live
    // o2switch site fails: LiteSpeed rate-limits the headless asset burst (HTTP 429),
    // so the SPA never mounts. Locally there is no rate limit. Run the local server
    // with APP_URL set to the target domain so canonical/hreflang/og are correct.
    const browser = await puppeteer.launch({
        executablePath: CHROME_PATH,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const results = [];
    let i = 0;
    async function worker() {
        while (i < urls.length) {
            const url = urls[i++];
            const r = await renderOne(browser, url);
            results.push(r);
            const tag = r.ok ? `✓ ${(r.bytes / 1024).toFixed(0)}kb` : `✗ ${r.error}`;
            console.log(`  [${results.length}/${urls.length}] ${tag}  ${new URL(url).pathname}`);
        }
    }
    await Promise.all(Array.from({ length: Math.min(CONCURRENCY, urls.length) }, worker));
    await browser.close();

    await pruneOrphans(urls);

    const ok = results.filter((r) => r.ok).length;
    const failed = results.filter((r) => !r.ok);
    console.log(`\n✅ ${ok}/${results.length} pages générées.`);
    if (failed.length) {
        console.log(`❌ ${failed.length} échec(s) :`);
        failed.forEach((f) => console.log(`   ${new URL(f.url).pathname} — ${f.error}`));
        process.exitCode = 1;
    }
}

run().catch((e) => {
    console.error(e);
    process.exit(1);
});
