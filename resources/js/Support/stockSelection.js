/**
 * Sélection d'articles pour valoriser en lot leurs entrées sans coût
 * (FEAT-129, retour de terrain).
 *
 * Chaque article avec des entrées sans coût a sa case, déclinaisons comprises.
 * Cocher une famille coche d'un coup ses déclinaisons qui ont des entrées sans
 * coût ; la décocher les décoche. Une déclinaison se coche ou se décoche seule,
 * pour lui donner un coût différent. Le serveur applique le coût exactement aux
 * articles cochés, rien n'est ajouté en coulisses.
 *
 * Les identifiants viennent de la charge Inertia : MariaDB peut les renvoyer
 * en chaînes, d'où les comparaisons par `String()`.
 */
const sameId = (a, b) => a !== null && a !== undefined && String(a) === String(b);

export const hasUnvalued = (product) => Number(product.unvalued_entries ?? 0) > 0;

export const childrenOf = (products, parentId) => products.filter((p) => sameId(p.parent_id, parentId));

const isFamily = (product) => product.parent_id === null || product.parent_id === undefined;

/** Ce que coche la case de cet article : lui, et pour une famille ses déclinaisons sans coût. */
export const familyOf = (products, product) => {
    if (!isFamily(product)) return [product.id];
    return [product.id, ...childrenOf(products, product.id).filter(hasUnvalued).map((c) => c.id)];
};

/** Une case n'a de sens que s'il y a quelque chose à valoriser derrière. */
export const canSelect = (products, product) => familyOf(products, product).some((id) =>
    hasUnvalued(products.find((p) => sameId(p.id, id)) ?? {}),
);

export const selectableIds = (products) => products.filter((p) => canSelect(products, p)).map((p) => p.id);

export const isSelected = (selected, product) => selected.some((id) => sameId(id, product.id));

export const toggle = (products, selected, product, checked) => {
    const family = familyOf(products, product);
    const rest = selected.filter((id) => !family.some((f) => sameId(f, id)));
    return checked ? [...rest, ...family] : rest;
};

export const allSelected = (products, selected) => {
    const all = selectableIds(products);
    return all.length > 0 && all.every((id) => selected.some((s) => sameId(s, id)));
};

export const toggleAll = (products, selected) => (allSelected(products, selected) ? [] : selectableIds(products));
