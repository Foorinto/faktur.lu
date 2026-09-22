import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * Les champs de réauthentification doivent parler le même langage que
 * App\Auth\Reauthenticator : le mot de passe, et un code sous le nom
 * `two_factor_code` dès qu'il y a un second facteur, application ou e-mail.
 *
 * En mode e-mail, le code doit partir quand les champs apparaissent (ils ne
 * sont montés qu'à l'ouverture du formulaire), sinon l'utilisateur attend un
 * mail qui ne vient pas. Comme ConfirmsPassword.test.js, on lit la source :
 * monter le composant exigerait Inertia, Ziggy, axios et les traductions.
 */
const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Components/ReauthFields.vue"),
    "utf8",
);

describe("ReauthFields", () => {
    it("lit la méthode du second facteur, pas seulement l'état de l'application", () => {
        expect(SOURCE).toMatch(/second_factor/);
        expect(SOURCE).toMatch(/v-if="requiresCode"/);
        expect(SOURCE).not.toMatch(/two_factor_enabled/);
    });

    it("envoie le code par e-mail au montage, et permet de le renvoyer", () => {
        expect(SOURCE).toMatch(/onMounted\(/);
        expect(SOURCE).toMatch(/if \(emailMode\.value\)\s*\{\s*emailCode\.send\(\);/);
        expect(SOURCE).toMatch(/@resend="emailCode\.send"/);
    });

    it("écrit le code dans le champ que le serveur attend", () => {
        expect(SOURCE).toMatch(/codeField: \{ type: String, default: 'two_factor_code' \}/);
        expect(SOURCE).toMatch(/v-model="form\[codeField\]"/);
    });
});
