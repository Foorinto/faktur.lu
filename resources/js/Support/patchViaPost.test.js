import { describe, expect, it } from 'vitest';
import { patchViaPost } from './patchViaPost';

/**
 * Le serveur de production refuse PATCH ; l'intercepteur le déguise en POST.
 *
 * Ces tests portent sur la seule chose qui a cassé en ligne : la méthode
 * réellement émise. Un `router.patch()` qui part en PATCH ne revient jamais.
 */
describe('patchViaPost', () => {
    const requete = (method) => ({ method, url: '/invoices/58/payments/47', headers: {} });

    it('transforme un PATCH en POST', () => {
        expect(patchViaPost(requete('patch')).method).toBe('post');
    });

    it("annonce la méthode d'origine dans l'en-tête que Symfony lit", () => {
        // Sans cet en-tête, Laravel router-ait le POST vers une route qui
        // n'existe pas : 405 au lieu de l'enregistrement attendu.
        expect(patchViaPost(requete('patch')).headers['X-HTTP-METHOD-OVERRIDE']).toBe('PATCH');
    });

    it('accepte la casse majuscule', () => {
        expect(patchViaPost(requete('PATCH')).method).toBe('post');
    });

    it.each(['get', 'post', 'put', 'delete'])('ne touche pas à %s', (methode) => {
        const config = patchViaPost(requete(methode));

        expect(config.method).toBe(methode);
        expect(config.headers['X-HTTP-METHOD-OVERRIDE']).toBeUndefined();
    });

    it('laisse passer une requête sans méthode explicite', () => {
        // axios ne renseigne pas toujours `method` : une valeur absente vaut
        // GET, et surtout pas PATCH.
        const config = patchViaPost({ url: '/fr', headers: {} });

        expect(config.method).toBeUndefined();
        expect(config.headers['X-HTTP-METHOD-OVERRIDE']).toBeUndefined();
    });

    it('conserve les autres en-têtes', () => {
        const config = patchViaPost({ method: 'patch', headers: { 'X-CSRF-TOKEN': 'jeton' } });

        expect(config.headers['X-CSRF-TOKEN']).toBe('jeton');
    });
});
