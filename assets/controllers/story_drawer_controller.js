import { Controller } from '@hotwired/stimulus';

/*
 * Drawer d'ouverture des documents d'une story.
 *
 * Ouvre/ferme le panneau et peuple la liste des documents depuis les paramètres de la
 * carte cliquée (aucun appel réseau au chargement). Le contenu d'un document est chargé
 * à la demande par Turbo : on se contente d'armer le `src` du <turbo-frame>, qui lazy-load
 * le fragment de la route dédiée. Lecture seule stricte — aucune écriture.
 */
export default class extends Controller {
    static targets = ['panel', 'backdrop', 'title', 'storyId', 'docList', 'frame'];

    connect() {
        this.onKeydown = (event) => {
            if (event.key === 'Escape') {
                this.close();
            }
        };
        window.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        window.removeEventListener('keydown', this.onKeydown);
    }

    open(event) {
        const { storyId, title, documents } = event.params;

        this.storyIdTarget.textContent = storyId;
        this.titleTarget.textContent = title;
        this.renderDocuments(documents || []);
        this.show();
    }

    renderDocuments(documents) {
        this.docListTarget.replaceChildren();

        if (documents.length === 0) {
            const note = document.createElement('span');
            note.className = 'text-[11.5px] text-ink-faint';
            note.textContent = 'Aucun document lisible.';
            this.docListTarget.appendChild(note);
            this.frameTarget.removeAttribute('src');
            return;
        }

        documents.forEach((doc, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = doc.name;
            button.dataset.test = 'drawer-doc';
            button.className =
                'rounded-md border border-line-strong bg-raised px-2 py-1 font-mono text-[11.5px] text-ink-dim ' +
                'hover:bg-raised-hover hover:text-ink aria-[current=true]:border-iris aria-[current=true]:bg-iris/12 aria-[current=true]:text-iris-text';
            button.addEventListener('click', () => this.select(button, doc));
            this.docListTarget.appendChild(button);

            // Charge d'emblée le document le plus avancé (liste d'abord, contenu ensuite).
            if (index === 0) {
                this.select(button, doc);
            }
        });
    }

    select(button, doc) {
        this.docListTarget
            .querySelectorAll('button')
            .forEach((other) => other.setAttribute('aria-current', String(other === button)));
        this.frameTarget.setAttribute('src', doc.url);
    }

    show() {
        this.panelTarget.classList.remove('translate-x-full');
        this.panelTarget.setAttribute('aria-hidden', 'false');
        this.backdropTarget.classList.remove('hidden');
    }

    close() {
        this.panelTarget.classList.add('translate-x-full');
        this.panelTarget.setAttribute('aria-hidden', 'true');
        this.backdropTarget.classList.add('hidden');
    }
}
