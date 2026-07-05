import { Controller } from '@hotwired/stimulus';

/*
 * Filtre par tag et tri des cartes du board, entièrement côté client sur des cartes déjà
 * rendues (data-attributes). Aucun appel réseau : instantané, cohérent avec l'archi
 * server-rendered (règle 10). Le filtrage n'affecte que l'affichage — l'état déduit des
 * colonnes reste intact (règle 11).
 *
 * Les chips de tags sont construits depuis le DOM (union des `data-filter-tags` des cartes),
 * ce qui évite de dupliquer la liste de tags côté Twig.
 */
export default class extends Controller {
    static targets = ['card', 'column', 'tags', 'sortNumber', 'sortUpdated'];

    connect() {
        this.activeTag = null;
        this.sort = 'number';
        this.renderTagFilters();
        this.updateSortButtons();
    }

    renderTagFilters() {
        const tags = new Set();
        this.cardTargets.forEach((card) => {
            this.tagsOf(card).forEach((tag) => tags.add(tag));
        });

        this.tagsTarget.replaceChildren();

        [...tags].sort().forEach((tag) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.test = 'filter-tag';
            button.dataset.tag = tag;
            button.textContent = tag;
            button.className = this.tagClass(false);
            button.addEventListener('click', () => this.toggleTag(tag));
            this.tagsTarget.appendChild(button);
        });
    }

    toggleTag(tag) {
        this.activeTag = this.activeTag === tag ? null : tag;

        this.tagsTarget.querySelectorAll('button').forEach((button) => {
            button.className = this.tagClass(button.dataset.tag === this.activeTag);
        });

        this.cardTargets.forEach((card) => {
            const visible = this.activeTag === null || this.tagsOf(card).includes(this.activeTag);
            card.classList.toggle('hidden', !visible);
        });

        this.updateCounts();
    }

    // Recale chaque compteur de colonne sur les cartes réellement visibles après filtrage.
    // Sans filtre actif, toutes les cartes sont visibles → le compteur retombe sur le total serveur.
    updateCounts() {
        this.columnTargets.forEach((column) => {
            const counter = column
                .closest('section')
                ?.querySelector('[data-test="column-count"], [data-test="banner-count"]');
            if (!counter) {
                return;
            }

            const visible = column.querySelectorAll('[data-board-filter-target="card"]:not(.hidden)').length;
            counter.textContent = String(visible);
        });
    }

    sortByNumber() {
        this.sort = 'number';
        this.applySort();
        this.updateSortButtons();
    }

    sortByUpdated() {
        this.sort = 'updated';
        this.applySort();
        this.updateSortButtons();
    }

    applySort() {
        this.columnTargets.forEach((column) => {
            const cards = [...column.querySelectorAll('[data-board-filter-target="card"]')];
            cards.sort((a, b) => this.compare(a, b));
            cards.forEach((card) => column.appendChild(card));
        });
    }

    compare(a, b) {
        if (this.sort === 'updated') {
            // Activité récente d'abord ; les cartes sans date (metadata absent) en dernier.
            const da = a.dataset.sortUpdated || '';
            const db = b.dataset.sortUpdated || '';
            if (da === db) return 0;
            if (da === '') return 1;
            if (db === '') return -1;
            return db.localeCompare(da);
        }

        // Défaut serveur : numéro décroissant.
        return Number(b.dataset.sortNumber) - Number(a.dataset.sortNumber);
    }

    updateSortButtons() {
        this.sortNumberTarget.className = this.sortClass(this.sort === 'number');
        this.sortUpdatedTarget.className = this.sortClass(this.sort === 'updated');
    }

    tagsOf(card) {
        return (card.dataset.filterTags || '').split(' ').filter(Boolean);
    }

    tagClass(active) {
        const base =
            'rounded px-1.5 py-0.5 font-mono text-[10px] transition ' +
            (active
                ? 'bg-iris/15 text-iris-text ring-1 ring-iris'
                : 'bg-stat text-ink-dim hover:bg-raised-hover hover:text-ink');
        return base;
    }

    sortClass(active) {
        return (
            'rounded-md border px-2 py-0.5 text-[11px] font-medium transition ' +
            (active
                ? 'border-iris bg-iris/12 text-iris-text'
                : 'border-line-strong bg-raised text-ink-dim hover:bg-raised-hover hover:text-ink')
        );
    }
}
