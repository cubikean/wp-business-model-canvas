/**
 * Gestion des tooltips Tippy.js
 * WP Business Model Canvas
 */

(function($) {
    'use strict';
    
    /**
     * Initialiser Tippy.js sur tous les éléments avec data-tippy-content
     */
    function initTippy() {
        if (typeof tippy === 'undefined') {
            console.warn('Tippy.js n\'est pas chargé');
            return;
        }
        
        // Initialiser sur tous les éléments avec data-tippy-content
        tippy('[data-tippy-content]', {
            allowHTML: true,
            placement: 'top',
            arrow: true,
            theme: 'light-border',
            animation: 'scale',
            duration: [200, 150],
            delay: [300, 0], // Délai avant d'afficher, pas de délai pour cacher
            maxWidth: 350,
            interactive: true,
            appendTo: document.body,
            // Fonction pour traiter le contenu HTML
            onShow(instance) {
                // Si le contenu est vide, ne pas afficher le tooltip
                if (!instance.props.content || instance.props.content.trim() === '') {
                    return false;
                }
            }
        });
        
       
    }
    
    /**
     * Réinitialiser Tippy après un changement de contenu dynamique
     */
    function reinitTippy() {
        // Détruire les instances existantes
        if (typeof tippy === 'undefined') {
            return;
        }
        
        // Réinitialiser
        initTippy();
    }
    
    /**
     * Créer un tooltip programmatiquement
     * @param {string|HTMLElement} element - Sélecteur ou élément DOM
     * @param {string} content - Contenu du tooltip
     * @param {object} options - Options Tippy.js
     */
    function createTippy(element, content, options = {}) {
        if (typeof tippy === 'undefined') {
            console.warn('Tippy.js n\'est pas chargé');
            return null;
        }
        
        const defaultOptions = {
            content: content,
            allowHTML: true,
            placement: 'top',
            arrow: true,
            theme: 'light-border',
            animation: 'scale'
        };
        
        return tippy(element, { ...defaultOptions, ...options });
    }
    
    /**
     * Détruire un tooltip
     * @param {HTMLElement} element - Élément DOM
     */
    function destroyTippy(element) {
        if (element && element._tippy) {
            element._tippy.destroy();
        }
    }
    
    // Initialiser au chargement du document
    $(document).ready(function() {
        initTippy();
    });
    
    // Observer les changements DOM pour réinitialiser Tippy sur les nouveaux éléments
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Vérifier si le nœud ajouté ou ses enfants ont data-tippy-content
                            if (node.hasAttribute && node.hasAttribute('data-tippy-content')) {
                                shouldReinit = true;
                            } else if (node.querySelector && node.querySelector('[data-tippy-content]')) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                setTimeout(function() {
                    initTippy();
                }, 100);
            }
        });
        
        // Observer le body pour les changements
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Exposer les fonctions globalement
    window.WP_BMC_Tippy = {
        init: initTippy,
        reinit: reinitTippy,
        create: createTippy,
        destroy: destroyTippy
    };
    
})(jQuery);

