/**
 * Système de Toast pour WP Business Model Canvas
 * Notifications élégantes et non-intrusives
 */

(function() {
    'use strict';

    // Configuration par défaut
    const defaultConfig = {
        duration: 4000,
        position: 'bottom-right',
        closeButton: true,
        progress: true
    };

    // Conteneur des toasts
    let toastContainer = null;

    // Initialiser le conteneur
    function initToastContainer() {
        if (toastContainer) return;

        toastContainer = document.createElement('div');
        toastContainer.className = 'wp-bmc-toast-container';
        toastContainer.setAttribute('aria-live', 'polite');
        toastContainer.setAttribute('aria-atomic', 'true');
        document.body.appendChild(toastContainer);
    }

    // Créer un toast
    function createToast(message, type = 'info', options = {}) {
        initToastContainer();

        const config = Object.assign({}, defaultConfig, options);
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        // Créer l'élément toast
        const toast = document.createElement('div');
        toast.className = `wp-bmc-toast wp-bmc-toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.id = toastId;

        // Icônes selon le type
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        // Contenu du toast
        let toastHTML = `
            <div class="wp-bmc-toast-content">
                <div class="wp-bmc-toast-icon">
                    <i class="${icons[type] || icons.info}"></i>
                </div>
                <div class="wp-bmc-toast-message">${message}</div>
        `;

        // Bouton de fermeture
        if (config.closeButton) {
            toastHTML += `
                <button class="wp-bmc-toast-close" type="button" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            `;
        }

        toastHTML += '</div>';

        // Barre de progression
        if (config.progress && config.duration > 0) {
            toastHTML += '<div class="wp-bmc-toast-progress"></div>';
        }

        toast.innerHTML = toastHTML;

        // Ajouter au conteneur
        toastContainer.appendChild(toast);

        // Animation d'apparition
        requestAnimationFrame(() => {
            toast.classList.add('wp-bmc-toast-show');
        });

        // Gestion des événements
        const closeButton = toast.querySelector('.wp-bmc-toast-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => removeToast(toast));
        }

        // Auto-suppression
        if (config.duration > 0) {
            const progressBar = toast.querySelector('.wp-bmc-toast-progress');
            if (progressBar) {
                progressBar.style.animationDuration = config.duration + 'ms';
            }

            setTimeout(() => {
                removeToast(toast);
            }, config.duration);
        }

        return toastId;
    }

    // Supprimer un toast
    function removeToast(toast) {
        if (!toast || !toast.parentNode) return;

        toast.classList.add('wp-bmc-toast-hide');
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    // Supprimer tous les toasts
    function clearAllToasts() {
        if (!toastContainer) return;

        const toasts = toastContainer.querySelectorAll('.wp-bmc-toast');
        toasts.forEach(toast => removeToast(toast));
    }

    // API publique
    window.WP_BMC_Toast = {
        // Méthodes principales
        show: createToast,
        success: (message, options) => createToast(message, 'success', options),
        error: (message, options) => createToast(message, 'error', options),
        warning: (message, options) => createToast(message, 'warning', options),
        info: (message, options) => createToast(message, 'info', options),
        
        // Utilitaires
        remove: removeToast,
        clear: clearAllToasts,
        
        // Configuration
        setDefaults: (newDefaults) => Object.assign(defaultConfig, newDefaults)
    };

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToastContainer);
    } else {
        initToastContainer();
    }

})();
