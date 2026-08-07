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

// --- CLI args ------------------------------------------------------------
const args = process.argv.slice(2);
const limitArg = args.indexOf('--limit');
const LIMIT = limitArg !== -1 ? Number(args[limitArg + 1]) : null;
const onlyArg = args.indexOf('--only');
const ONLY = onlyArg !== -1 ? args[onlyArg + 1].split(',').map((s) => s.trim()) : null;

// --- Collect the list of public URLs to prerender ------------------------
async function collectUrls() {
    if (ONLY) return ONLY.map((p) => `${BASE_URL}${p.startsWith('/') ? p : `/${p}`}`);

    const sitemaps = ['/sitemap-pages.xml', '/sitemap-blog.xml'];
    const urls = new Set();
    for (const sm of sitemaps) {
        const res = await fetch(`${BASE_URL}${sm}`);
        if (!res.ok) {
            console.warn(`⚠️  ${sm} → HTTP ${res.status}, ignoré`);
            continue;
        }
        const xml = await res.text();
        for (const m of xml.matchAll(/<loc>([^<]+)<\/loc>/g)) {
            // Normalize the host to BASE_URL (sitemap may use the prod host).
            const path = new URL(m[1]).pathname;
            urls.add(`${BASE_URL}${path}`);
        }
    }
    const list = [...urls];
    return LIMIT ? list.slice(0, LIMIT) : list;
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
    return html
        // data-page="{…}" — inner quotes are HTML-escaped (&quot;), so the attribute
        // value contains no literal " until its closing delimiter.
        .replace(/\sdata-page="[^"]*"/, ' data-page=""')
        // Vite entry + page chunk module scripts.
        .replace(/<script\b[^>]*\btype="module"[^>]*><\/script>/g, '')
        // modulepreload / preload links for JS chunks.
        .replace(/<link\b[^>]*\brel="modulepreload"[^>]*>/g, '');
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
    const attendus = new Set(urls.map((u) => fileForPath(new URL(u).pathname)));
    const retires = [];

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
            if (e.name === 'index.html' && !attendus.has(chemin)) {
                await rm(dirname(chemin), { recursive: true, force: true });
                retires.push(dirname(chemin).replace(OUT_DIR, ''));
            }
        }
    }

    await parcourir(OUT_DIR);

    if (retires.length) {
        console.log(`\n🧹 ${retires.length} snapshot(s) orphelin(s) supprimé(s) :`);
        retires.forEach((r) => console.log(`   ${r}`));
    }

    return retires.length;
}
// Simple concurrency pool.
async function run() {
    const urls = await collectUrls();
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
