import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

/**
 * La modale de confirmation du profil doit parler le même langage que le
 * serveur : mot de passe, plus code 2FA quand elle est active.
 *
 * Elle poste par axios sur la même route que la page de confirmation, vérifiée
 * par le même Reauthenticator. Le 2026-09-21, ce Reauthenticator s'est mis à
 * exiger le code 2FA ; la modale, elle, n'envoyait que le mot de passe et ne
 * lisait que l'erreur du mot de passe. Pour un utilisateur avec 2FA, cliquer
 * « Désactiver la 2FA » ou « Régénérer les codes » ne produisait plus rien :
 * pas de message, pas d'erreur en console, juste un bouton qui ne fait rien.
 * Aucun test PHP ne pouvait le voir, le défaut vivait dans le composant.
 *
 * Ce test lit le composant. C'est grossier, mais le monter exigerait Inertia,
 * Ziggy, axios et les traductions pour vérifier trois lignes.
 */
const SOURCE = readFileSync(
    resolve(process.cwd(), "resources/js/Components/ConfirmsPassword.vue"),
    "utf8",
);

describe("ConfirmsPassword", () => {
    it("envoie le code 2FA avec le mot de passe", () => {
        expect(SOURCE).toMatch(/two_factor_code:\s*form\.two_factor_code/);
    });

    it("n'affiche le champ du code que s'il y a un second facteur", () => {
        // 'app' ou 'email' : depuis FEAT-124 la modale lit la méthode, pas
        // seulement l'état de l'application.
        expect(SOURCE).toMatch(/second_factor/);
        expect(SOURCE).toMatch(/v-if="requiresCode"/);
    });

    it("envoie le code par e-mail à l'ouverture et permet de le renvoyer", () => {
        expect(SOURCE).toMatch(/if \(emailMode\.value\) emailCode\.send\(\)/);
        expect(SOURCE).toMatch(/<EmailCodeStatus/);
        expect(SOURCE).toMatch(/@resend="emailCode\.send"/);
        // Fermer la modale arrête le compte à rebours et oublie l'état.
        expect(SOURCE).toMatch(/emailCode\.reset\(\)/);
    });

    it("lit l'erreur du code, pas seulement celle du mot de passe", () => {
        expect(SOURCE).toMatch(/errors\.two_factor_code/);
        // La lecture directe `errors.password[0]` plantait quand seul le code
        // manquait : elle ne doit pas revenir.
        expect(SOURCE).not.toMatch(/errors\.password\[0\]/);
    });
});
