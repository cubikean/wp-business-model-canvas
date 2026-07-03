/**
 * Fonctions de base du canvas WP Business Model Canvas
 * Gestion de la grille, redimensionnement, sauvegarde de base
 */

(function($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('WP_BMC_CanvasCore: jQuery est requis');
        return;
    }

    /**
     * Objet global pour les fonctions de base du canvas
     */
    window.WP_BMC_CanvasCore = window.WP_BMC_CanvasCore || {};

    /**
     * Mettre à jour la grille du canvas selon les attributs data-column et data-row
     */
    WP_BMC_CanvasCore.updateGrid = function() {
        var canvasSections = $('.canvas-section');

        canvasSections.each(function() {
            var $section = $(this);
            var column = $section.attr('data-column');
            var row = $section.attr('data-row');

            if (column) {
                $section.css('grid-column', column);
            }
            if (row) {
                $section.css('grid-row', row);
            }
        });
    };

    /**
     * Redimensionner automatiquement un textarea selon son contenu
     * @param {HTMLElement|jQuery} textarea - Élément textarea à redimensionner
     */
    WP_BMC_CanvasCore.autoResize = function(textarea) {
        var $textarea = $(textarea);
        if ($textarea.length === 0) {
            return;
        }

        var element = $textarea[0];
        element.style.height = 'auto';
        element.style.height = element.scrollHeight + 'px';
    };

    /**
     * Sauvegarder le canvas via AJAX
     * @param {Object} options - Options de sauvegarde
     * @param {string} options.selector - Sélecteur pour les champs ('.canvas-input' ou '.canvas-content')
     * @param {Function} options.getContentCallback - Callback pour obtenir le contenu (optionnel)
     * @param {Function} options.onSuccess - Callback de succès (optionnel)
     * @param {Function} options.onError - Callback d'erreur (optionnel)
     * @param {Function} options.onComplete - Callback de complétion (optionnel)
     */
    WP_BMC_CanvasCore.save = function(options) {
        options = options || {};
        var selector = options.selector || '.canvas-content';
        var projectId = window.WP_BMC_Utils ? window.WP_BMC_Utils.getProjectId() : null;

        if (!projectId) {
            if (options.onError) {
                options.onError('Impossible de trouver le projet à sauvegarder.');
            }
            return;
        }

        // Collecter les données du canvas
        var canvasData = {};
        var getContent = options.getContentCallback || function($element) {
            if ($element.is('textarea, input')) {
                return $element.val();
            } else {
                return $element.html();
            }
        };

        $(selector).each(function() {
            var $element = $(this);
            var section = $element.data('section') || $element.closest('[data-section]').data('section');
            if (section) {
                canvasData[section] = getContent($element);
            }
        });

        // Préparer les données pour l'envoi
        var canvasVersions = window.WP_BMC_CanvasVersions ? window.WP_BMC_CanvasVersions.getPayload() : {};
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) 
            ? wp_bmc_admin_ajax.nonce 
            : (typeof wp_bmc_ajax !== 'undefined' ? wp_bmc_ajax.nonce : null);
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) 
            ? wp_bmc_admin_ajax.ajax_url 
            : (typeof wp_bmc_ajax !== 'undefined' ? wp_bmc_ajax.ajax_url : null);

        if (!nonce || !ajaxUrl) {
            if (options.onError) {
                options.onError('Configuration AJAX invalide.');
            }
            return;
        }

        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: nonce,
            project_id: projectId,
            canvas_data: canvasData,
            canvas_versions: canvasVersions
        };

        // Afficher l'indicateur de sauvegarde si disponible
        if (options.showIndicator !== false && typeof window.WP_BMC_Public !== 'undefined' && window.WP_BMC_Public.showSavingIndicator) {
            window.WP_BMC_Public.showSavingIndicator();
        }

        $.post(ajaxUrl, formData, function(response) {
            if (response.success) {
                // Mettre à jour les versions si disponibles
                if (response.data && response.data.updated_versions && window.WP_BMC_CanvasVersions) {
                    window.WP_BMC_CanvasVersions.merge(response.data.updated_versions);
                }

                // Nettoyer le brouillon
                if (window.WP_BMC_CanvasDraft) {
                    window.WP_BMC_CanvasDraft.clear();
                }

                if (options.onSuccess) {
                    options.onSuccess(response);
                }
            } else {
                var errorMessage = window.WP_BMC_Utils 
                    ? window.WP_BMC_Utils.extractErrorMessage(response, 'Erreur lors de la sauvegarde')
                    : 'Erreur lors de la sauvegarde';
                
                if (options.onError) {
                    options.onError(errorMessage, response);
                }
            }
        }).fail(function(xhr, status, error) {
            if (options.onError) {
                options.onError('Erreur de connexion lors de la sauvegarde', { xhr: xhr, status: status, error: error });
            }
        }).always(function() {
            // Masquer l'indicateur de sauvegarde si disponible
            if (options.showIndicator !== false && typeof window.WP_BMC_Public !== 'undefined' && window.WP_BMC_Public.hideSavingIndicator) {
                window.WP_BMC_Public.hideSavingIndicator();
            }

            if (options.onComplete) {
                options.onComplete();
            }
        });
    };

    /**
     * Charger le canvas depuis le serveur
     * @param {string} projectId - ID du projet
     * @param {Function} onSuccess - Callback de succès
     * @param {Function} onError - Callback d'erreur
     */
    WP_BMC_CanvasCore.load = function(projectId, onSuccess, onError) {
        if (!projectId) {
            projectId = window.WP_BMC_Utils ? window.WP_BMC_Utils.getProjectId() : null;
        }

        if (!projectId) {
            if (onError) {
                onError('ID de projet manquant');
            }
            return;
        }

        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) 
            ? wp_bmc_admin_ajax.nonce 
            : (typeof wp_bmc_ajax !== 'undefined' ? wp_bmc_ajax.nonce : null);
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) 
            ? wp_bmc_admin_ajax.ajax_url 
            : (typeof wp_bmc_ajax !== 'undefined' ? wp_bmc_ajax.ajax_url : null);

        if (!nonce || !ajaxUrl) {
            if (onError) {
                onError('Configuration AJAX invalide');
            }
            return;
        }

        $.post(ajaxUrl, {
            action: 'wp_bmc_get_canvas',
            nonce: nonce,
            project_id: projectId
        }, function(response) {
            if (response && response.success && response.data) {
                // Mettre à jour les versions si disponibles
                if (response.data.canvas_versions && window.WP_BMC_CanvasVersions) {
                    window.WP_BMC_CanvasVersions.merge(response.data.canvas_versions);
                }

                if (onSuccess) {
                    onSuccess(response.data);
                }
            } else {
                var errorMessage = window.WP_BMC_Utils 
                    ? window.WP_BMC_Utils.extractErrorMessage(response, 'Erreur de récupération')
                    : 'Erreur de récupération';
                
                if (onError) {
                    onError(errorMessage);
                }
            }
        }).fail(function() {
            if (onError) {
                onError('Erreur réseau');
            }
        });
    };

})(jQuery);

