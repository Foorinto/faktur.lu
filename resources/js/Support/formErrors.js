/**
 * Amène l'utilisateur jusqu'au champ refusé, et le lui dit.
 *
 * Un formulaire long — les paramètres d'entreprise en comptent trente-six
 * champs — refusait l'enregistrement sans que rien ne bouge à l'écran : le
 * message existait bien, mais deux écrans plus bas. L'utilisateur recliquait
 * sur « Enregistrer », concluait à une panne, et n'avait pas tort de le croire.
 *
 * Le traitement est global, branché sur l'événement `error` d'Inertia. Le
 * greffer formulaire par formulaire aurait demandé de toucher quatre-vingts
 * fichiers, et le quatre-vingt-unième aurait été oublié.
 */

/** Événement écouté par la zone de notifications. */
export const FORM_ERRORS_EVENT = 'faktur:form-errors';

/**
 * Élément de saisie correspondant à une clé d'erreur.
 *
 * Laravel nomme ses clés comme les champs : `city`, mais aussi `items.0.title`
 * pour un tableau. On tente l'identifiant puis l'attribut `name`, qui couvrent
 * les deux conventions présentes dans le dépôt.
 */
const champPour = (cle) => {
    const echappee = (window.CSS && CSS.escape) ? CSS.escape(cle) : cle.replace(/([.[\]])/g, '\\$1');

    return document.querySelector(`#${echappee}`)
        ?? document.querySelector(`[name="${cle}"]`)
        ?? document.querySelector(`[data-error-key="${cle}"]`);
};

/**
 * Le premier champ en erreur DANS L'ORDRE DE LA PAGE.
 *
 * L'ordre des clés renvoyées par le serveur suit celui des règles de
 * validation, qui n'a aucune raison de suivre celui du formulaire. Emmener
 * l'utilisateur vers le troisième champ alors que le premier est aussi en
 * erreur lui ferait manquer la moitié du problème.
 */
const premierChampEnErreur = (erreurs) => {
    const champs = Object.keys(erreurs).map(champPour).filter(Boolean);

    return champs.reduce((premier, champ) => {
        if (! premier) return champ;

        return (premier.compareDocumentPosition(champ) & Node.DOCUMENT_POSITION_PRECEDING)
            ? champ
            : premier;
    }, null);
};

/**
 * Déplie les conteneurs repliés qui masqueraient le champ.
 *
 * Un champ dans un `<details>` fermé peut être scrollé jusqu'à sa position sans
 * jamais devenir visible : on aurait bougé la page pour ne rien montrer.
 */
const deplierAncetres = (champ) => {
    let noeud = champ.parentElement;

    while (noeud) {
        if (noeud.tagName === 'DETAILS') noeud.open = true;
        noeud = noeud.parentElement;
    }
};

/**
 * Traite les erreurs de validation d'une requête Inertia.
 *
 * @param {Record<string, string>} erreurs
 */
export const traiterErreursDeValidation = (erreurs) => {
    const cles = Object.keys(erreurs ?? {});

    if (cles.length === 0) {
        return;
    }

    window.dispatchEvent(new CustomEvent(FORM_ERRORS_EVENT, {
        detail: { messages: cles.map((cle) => erreurs[cle]).filter(Boolean) },
    }));

    // Deux trames d'attente : Vue n'a pas encore rendu les messages au moment
    // où Inertia signale l'erreur, et un champ qui n'existe pas encore ne peut
    // être ni trouvé ni atteint.
    requestAnimationFrame(() => requestAnimationFrame(() => {
        const champ = premierChampEnErreur(erreurs);

        if (! champ) {
            return;
        }

        deplierAncetres(champ);
        champ.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Le focus vient après le défilement, et sans le relancer : le
        // navigateur sauterait sinon à sa propre position, annulant l'animation.
        champ.focus?.({ preventScroll: true });
    }));
};
