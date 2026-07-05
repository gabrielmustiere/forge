import { Controller } from '@hotwired/stimulus';

/*
 * Filtre par tag et tri des cartes du board, entièrement côté client sur des cartes déjà
 * rendues (data-attributes). Aucun appel réseau : instantané, cohérent avec l'archi
 * server-rendered (règle 10). Le filtrage n'affecte que l'affichage — l'état déduit des
 * colonnes reste intact (règle 11).
 *
 * Le filtre est un popover recherchable (multi-select OR) : plutôt qu'un mur de chips, un
 * bouton ouvre une liste de tags cherchable. Les tags choisis s'affichent en pills retirables.
 * La liste des tags est construite depuis le DOM (union des `data-filter-tags` des cartes),
 * ce qui évite de dupliquer la liste de tags côté Twig.
 */
export default class extends Controller {
    static targets = [
        'card',
        'column',
        'sortNumber',
        'sortUpdated',
        'menuButton',
        'menu',
        'menuList',
        'menuEmpty',
        'search',
        'pills',
        'activeCount',
    ];

    connect() {
        this.activeTags = new Set();
        this.sort = 'number';
        this.tagCounts = this.computeTagCounts();
        this.renderMenu();
        this.renderPills();
        this.updateActiveCount();
        this.updateSortButtons();

        // Fermeture du popover au clic extérieur / Échap.
        this.onDocumentClick = (event) => {
            if (this.menuOpen && !this.element.contains(event.target)) {
                this.closeMenu();
            }
        };
        document.addEventListener('click', this.onDocumentClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onDocumentClick);
    }

    // Nombre de cartes portant chaque tag, sur l'ensemble du board (indépendant du filtre courant).
    computeTagCounts() {
        const counts = new Map();
        this.cardTargets.forEach((card) => {
            this.tagsOf(card).forEach((tag) => counts.set(tag, (counts.get(tag) || 0) + 1));
        });
        return counts;
    }

    // --- Popover -----------------------------------------------------------

    toggleMenu(event) {
        event.stopPropagation();
        this.menuOpen ? this.closeMenu() : this.openMenu();
    }

    openMenu() {
        this.menuOpen = true;
        this.menuTarget.classList.remove('hidden');
        this.menuButtonTarget.setAttribute('aria-expanded', 'true');
        this.searchTarget.value = '';
        this.filterMenu();
        this.searchTarget.focus();
    }

    closeMenu() {
        this.menuOpen = false;
        this.menuTarget.classList.add('hidden');
        this.menuButtonTarget.setAttribute('aria-expanded', 'false');
    }

    onMenuKeydown(event) {
        if (event.key === 'Escape') {
            this.closeMenu();
            this.menuButtonTarget.focus();
        }
    }

    // Filtre les items du popover selon la saisie (sous-chaîne, insensible à la casse).
    filterMenu() {
        const term = this.searchTarget.value.trim().toLowerCase();
        let visible = 0;
        this.menuListTarget.querySelectorAll('[data-test="filter-tag"]').forEach((item) => {
            const match = item.dataset.tag.includes(term);
            item.classList.toggle('hidden', !match);
            if (match) visible += 1;
        });
        this.menuEmptyTarget.classList.toggle('hidden', visible > 0);
    }

    // (Re)construit la liste du popover depuis `tagCounts`, en marquant les tags actifs.
    renderMenu() {
        this.menuListTarget.replaceChildren();

        [...this.tagCounts.keys()].sort().forEach((tag) => {
            const active = this.activeTags.has(tag);

            const item = document.createElement('button');
            item.type = 'button';
            item.dataset.test = 'filter-tag';
            item.dataset.tag = tag;
            item.className =
                'flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-[12px] transition hover:bg-raised-hover';
            // stopPropagation : `renderMenu` détache ce bouton, ce qui ferait croire au listener de
            // clic-extérieur à un clic hors popover → on garde le menu ouvert pour enchaîner les tags.
            item.addEventListener('click', (event) => {
                event.stopPropagation();
                this.toggleTag(tag);
            });

            const check = document.createElement('span');
            check.className =
                'grid size-4 shrink-0 place-items-center rounded border transition ' +
                (active ? 'border-iris bg-iris/20 text-iris-text' : 'border-line-strong text-transparent');
            check.textContent = '✓';
            check.classList.add('text-[10px]', 'font-bold', 'leading-none');

            const label = document.createElement('span');
            label.className = 'flex-1 font-mono ' + (active ? 'text-iris-text' : 'text-ink-dim');
            label.textContent = tag;

            const count = document.createElement('span');
            count.className = 'font-mono text-[10px] text-ink-faint';
            count.textContent = String(this.tagCounts.get(tag));

            item.append(check, label, count);
            this.menuListTarget.appendChild(item);
        });

        if (this.hasSearchTarget && this.searchTarget.value) {
            this.filterMenu();
        }
    }

    // --- Sélection ---------------------------------------------------------

    toggleTag(tag) {
        this.activeTags.has(tag) ? this.activeTags.delete(tag) : this.activeTags.add(tag);
        this.renderMenu();
        this.renderPills();
        this.updateActiveCount();
        this.applyFilter();
    }

    clearTags() {
        this.activeTags.clear();
        this.renderMenu();
        this.renderPills();
        this.updateActiveCount();
        this.applyFilter();
    }

    // Une carte est visible si aucun tag n'est actif, ou si elle porte au moins un tag actif (OR).
    applyFilter() {
        this.cardTargets.forEach((card) => {
            const visible =
                this.activeTags.size === 0 || this.tagsOf(card).some((tag) => this.activeTags.has(tag));
            card.classList.toggle('hidden', !visible);
        });
        this.updateCounts();
    }

    renderPills() {
        this.pillsTarget.replaceChildren();

        [...this.activeTags].sort().forEach((tag) => {
            const pill = document.createElement('span');
            pill.dataset.test = 'tag-pill';
            pill.className =
                'inline-flex items-center gap-1 rounded bg-iris/15 py-0.5 pl-1.5 pr-1 font-mono text-[10px] text-iris-text ring-1 ring-iris';

            const label = document.createElement('span');
            label.textContent = tag;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Retirer le tag ${tag}`);
            remove.className = 'grid size-3 place-items-center rounded-sm text-iris-text/70 transition hover:text-iris-text';
            remove.textContent = '✕';
            remove.addEventListener('click', () => this.toggleTag(tag));

            pill.append(label, remove);
            this.pillsTarget.appendChild(pill);
        });

        if (this.activeTags.size >= 2) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.dataset.test = 'clear-tags';
            clear.className = 'text-[10px] font-medium text-ink-muted underline-offset-2 transition hover:text-ink hover:underline';
            clear.textContent = 'Tout effacer';
            clear.addEventListener('click', () => this.clearTags());
            this.pillsTarget.appendChild(clear);
        }
    }

    updateActiveCount() {
        const n = this.activeTags.size;
        this.activeCountTarget.textContent = String(n);
        this.activeCountTarget.classList.toggle('hidden', n === 0);
        this.menuButtonTarget.classList.toggle('text-ink', n > 0);
        this.menuButtonTarget.classList.toggle('border-iris', n > 0);
    }

    // Recale chaque compteur de colonne sur les cartes réellement visibles après filtrage.
    // Sans filtre actif, toutes les cartes sont visibles → le compteur retombe sur le total serveur.
    updateCounts() {
        this.columnTargets.forEach((column) => {
            const section = column.closest('section');
            const counter = section?.querySelector('[data-test="column-count"], [data-test="banner-count"]');
            if (!counter) {
                return;
            }

            const visible = column.querySelectorAll('[data-board-filter-target="card"]:not(.hidden)').length;
            counter.textContent = String(visible);

            // Une colonne vidée par le filtre se rétrécit (largeur alignée sur le rendu Twig).
            section.classList.toggle('w-44', visible === 0);
            section.classList.toggle('w-80', visible > 0);
        });
    }

    // --- Tri ---------------------------------------------------------------

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

    sortClass(active) {
        return (
            'rounded-md border px-2 py-0.5 text-[11px] font-medium transition ' +
            (active
                ? 'border-iris bg-iris/12 text-iris-text'
                : 'border-line-strong bg-raised text-ink-dim hover:bg-raised-hover hover:text-ink')
        );
    }
}
