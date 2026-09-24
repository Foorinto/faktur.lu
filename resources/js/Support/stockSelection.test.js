import { describe, expect, it } from 'vitest';
import { allSelected, canSelect, familyOf, isSelected, selectableIds, toggle, toggleAll } from './stockSelection';

// Une famille avec deux déclinaisons (une sans coût, une valorisée), un article
// seul sans coût, un article seul déjà valorisé. Identifiants en chaînes pour
// une partie, comme MariaDB peut les renvoyer.
const products = [
    { id: 1, parent_id: null, unvalued_entries: 1 },
    { id: '2', parent_id: '1', unvalued_entries: 2 },
    { id: 3, parent_id: 1, unvalued_entries: 0 },
    { id: 4, parent_id: null, unvalued_entries: 1 },
    { id: 5, parent_id: null, unvalued_entries: 0 },
];

describe('stockSelection', () => {
    it('cocher une famille coche ses déclinaisons sans coût, pas les autres', () => {
        expect(familyOf(products, products[0])).toEqual([1, '2']);
        expect(toggle(products, [], products[0], true)).toEqual([1, '2']);
    });

    it('décocher la famille décoche ses déclinaisons, sans toucher au reste', () => {
        expect(toggle(products, [1, '2', 4], products[0], false)).toEqual([4]);
    });

    it('une déclinaison se coche et se décoche seule', () => {
        expect(toggle(products, [1, '2'], products[1], false)).toEqual([1]);
        expect(toggle(products, [], products[1], true)).toEqual(['2']);
    });

    it('cocher deux fois la même famille ne la compte pas deux fois', () => {
        expect(toggle(products, [1, '2'], products[0], true)).toEqual([1, '2']);
    });

    it('seuls les articles avec quelque chose à valoriser ont une case', () => {
        expect(canSelect(products, products[0])).toBe(true);
        expect(canSelect(products, products[2])).toBe(false);
        expect(canSelect(products, products[4])).toBe(false);
        expect(selectableIds(products)).toEqual([1, '2', 4]);
    });

    it("une famille sans entrée propre garde sa case si une déclinaison en a", () => {
        const famille = [{ id: 9, parent_id: null, unvalued_entries: 0 }, { id: 10, parent_id: 9, unvalued_entries: 3 }];
        expect(canSelect(famille, famille[0])).toBe(true);
        expect(familyOf(famille, famille[0])).toEqual([9, 10]);
    });

    it('tout sélectionner prend tous les articles à valoriser, puis tout relâche', () => {
        const tous = toggleAll(products, []);
        expect(tous).toEqual([1, '2', 4]);
        expect(allSelected(products, tous)).toBe(true);
        expect(toggleAll(products, tous)).toEqual([]);
    });

    it('compare les identifiants sans se soucier de leur type', () => {
        expect(isSelected(['2'], { id: 2 })).toBe(true);
        expect(allSelected(products, ['1', 2, '4'])).toBe(true);
    });
});
