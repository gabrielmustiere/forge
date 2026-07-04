import { Controller } from '@hotwired/stimulus';

/*
 * Pré-remplit le champ « nom » (owner/repo) à partir de l'URL saisie,
 * sans jamais écraser une saisie manuelle de l'utilisateur.
 */
export default class extends Controller {
    static targets = ['url', 'name'];

    suggest() {
        if (this.nameTarget.dataset.touched === 'true') {
            return;
        }

        const match = this.urlTarget.value
            .trim()
            .match(/([^/:]+)\/([^/]+?)(?:\.git)?\/?$/);

        this.nameTarget.value = match ? `${match[1]}/${match[2]}` : '';
    }

    markTouched() {
        this.nameTarget.dataset.touched = 'true';
    }
}
