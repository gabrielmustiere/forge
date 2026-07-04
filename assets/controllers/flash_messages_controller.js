import { Controller } from '@hotwired/stimulus';

/*
 * This controller manages flash message notifications
 *
 * Usage in Twig:
 * <div data-controller="notification"
 *      data-notification-type-value="success"
 *      data-notification-auto-close-value="true">
 *   <!-- notification content -->
 * </div>
 */
export default class extends Controller {
    static values = {
        type: String,
        autoClose: { type: Boolean, default: true },
        delay: { type: Number, default: 5000 }
    }

    connect() {
        // Start entrance animation
        this.element.classList.add('starting');

        // Remove 'starting' class after a small delay to trigger CSS transition
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                this.element.classList.remove('starting');
            });
        });

        // Auto-close after delay if enabled
        if (this.autoCloseValue) {
            this.timeout = setTimeout(() => {
                this.close();
            }, this.delayValue);
        }
    }

    disconnect() {
        // Clear timeout if component is disconnected
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    close() {
        // Add exit animation classes
        this.element.classList.add('opacity-0', 'translate-y-2', 'sm:translate-x-2');

        // Remove element after animation completes
        setTimeout(() => {
            this.element.remove();
        }, 300);
    }

    // Pause auto-close on hover
    pauseAutoClose() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    // Resume auto-close on mouse leave
    resumeAutoClose() {
        if (this.autoCloseValue) {
            this.timeout = setTimeout(() => {
                this.close();
            }, this.delayValue);
        }
    }
}
