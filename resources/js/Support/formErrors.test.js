import { beforeEach, describe, expect, it, vi } from 'vitest';
import { FORM_ERRORS_EVENT, traiterErreursDeValidation } from './formErrors';

/**
 * Défilement vers le champ refusé.
 *
 * Le module ne calcule rien, mais il décide de ce que l'utilisateur voit après
 * un enregistrement refusé — et son prédécesseur, dans les paramètres
 * d'entreprise, cherchait une classe CSS qui n'existait pas. Il n'a jamais rien
 * trouvé, sans que personne s'en aperçoive : un défilement qui n'a pas lieu ne
 * lève aucune erreur.
 */

/** happy-dom n'implémente pas le défilement : on observe l'appel. */
const espionnerLeDefilement = () => {
    const appels = [];

    Element.prototype.scrollIntoView = function scrollIntoView() {
        appels.push(this);
    };

    return appels;
};

/** Les deux trames d'attente du module, épuisées d'un coup. */
const attendreLeRendu = () => new Promise((resolve) => {
    requestAnimationFrame(() => requestAnimationFrame(resolve));
});

beforeEach(() => {
    document.body.innerHTML = '';
});

describe('défilement vers le champ refusé', () => {
    it('atteint le champ nommé par la clé d’erreur', async () => {
        document.body.innerHTML = `
            <input id="company_name">
            <input id="matricule">
        `;
        const defilements = espionnerLeDefilement();

        traiterErreursDeValidation({ matricule: 'Le matricule doit contenir 11 chiffres.' });
        await attendreLeRendu();

        expect(defilements).toHaveLength(1);
        expect(defilements[0].id).toBe('matricule');
    });

    /**
     * Le point central. L'ordre des clés renvoyées par le serveur suit celui
     * des règles de validation, qui n'a aucune raison de suivre celui du
     * formulaire. Emmener l'utilisateur au dernier champ alors que le premier
     * est aussi en faute lui ferait manquer la moitié du problème.
     */
    it('choisit le premier champ dans l’ordre de la page, pas celui des erreurs', async () => {
        document.body.innerHTML = `
            <input id="company_name">
            <input id="city">
            <input id="matricule">
        `;
        const defilements = espionnerLeDefilement();

        // Le serveur cite « matricule » en premier : c'est pourtant le dernier
        // dans le formulaire.
        traiterErreursDeValidation({
            matricule: 'Invalide.',
            company_name: 'Obligatoire.',
        });
        await attendreLeRendu();

        expect(defilements[0].id).toBe('company_name');
    });

    it('retrouve un champ par son attribut name', async () => {
        document.body.innerHTML = '<input name="items.0.title">';
        const defilements = espionnerLeDefilement();

        traiterErreursDeValidation({ 'items.0.title': 'Obligatoire.' });
        await attendreLeRendu();

        expect(defilements).toHaveLength(1);
    });

    /**
     * Une clé pointée passée à `querySelector` sans échappement serait lue
     * comme un sélecteur de classe : `#items.0.title` cherche l'élément
     * `items` portant les classes `0` et `title`. Le champ ne serait jamais
     * trouvé — et, selon le navigateur, la requête lèverait une exception.
     */
    it('ne casse pas sur une clé de tableau', async () => {
        document.body.innerHTML = '<input id="autre_champ">';
        espionnerLeDefilement();

        expect(() => traiterErreursDeValidation({ 'items.0.unit_price': 'Invalide.' })).not.toThrow();
        await attendreLeRendu();
    });

    /**
     * Un champ dans une section repliée peut être atteint sans devenir
     * visible : on aurait bougé la page pour ne rien montrer.
     */
    it('déplie la section qui masque le champ', async () => {
        document.body.innerHTML = `
            <details>
                <summary>Options avancées</summary>
                <input id="iban">
            </details>
        `;
        espionnerLeDefilement();

        traiterErreursDeValidation({ iban: 'IBAN invalide.' });
        await attendreLeRendu();

        expect(document.querySelector('details').open).toBe(true);
    });

    it('donne le focus au champ sans relancer le défilement', async () => {
        document.body.innerHTML = '<input id="email">';
        espionnerLeDefilement();

        const champ = document.querySelector('#email');
        const focus = vi.spyOn(champ, 'focus');

        traiterErreursDeValidation({ email: 'Adresse invalide.' });
        await attendreLeRendu();

        expect(focus).toHaveBeenCalledWith({ preventScroll: true });
    });

    /**
     * Une erreur peut ne correspondre à aucun champ affiché — une règle portant
     * sur l'ensemble du formulaire, ou un champ conditionnel masqué. Le message
     * doit tout de même être annoncé.
     */
    it('reste sans effet quand aucun champ ne correspond', async () => {
        document.body.innerHTML = '<input id="autre_chose">';
        const defilements = espionnerLeDefilement();

        traiterErreursDeValidation({ champ_absent: 'Invalide.' });
        await attendreLeRendu();

        expect(defilements).toHaveLength(0);
    });
});

describe('annonce des messages', () => {
    it('émet les messages dans l’ordre reçu', () => {
        const recus = [];
        window.addEventListener(FORM_ERRORS_EVENT, (e) => recus.push(...e.detail.messages));

        traiterErreursDeValidation({ a: 'Premier motif.', b: 'Second motif.' });

        expect(recus).toEqual(['Premier motif.', 'Second motif.']);
    });

    it('n’annonce rien sans erreur', () => {
        const ecouteur = vi.fn();
        window.addEventListener(FORM_ERRORS_EVENT, ecouteur);

        traiterErreursDeValidation({});
        traiterErreursDeValidation(undefined);

        expect(ecouteur).not.toHaveBeenCalled();
    });
});
