/**
 * JavaScript pour le plugin WP Business Model Canvas
 * Version simplifiée utilisant les modules core
 */

// Système de logs conditionnels pour production
var WP_BMC_DEBUG = false;
function log() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && console.log) {
        console.log.apply(console, arguments);
    }
}
function logWarn() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && console.warn) {
        console.warn.apply(console, arguments);
    }
}
function logError() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && console.error) {
        console.error.apply(console, arguments);
    }
}

jQuery(document).ready(function($) {
    
    // Vérifier que les modules core sont disponibles
    if (typeof window.WP_BMC_Utils === 'undefined' || 
        typeof window.WP_BMC_CanvasVersions === 'undefined' ||
        typeof window.WP_BMC_CanvasDraft === 'undefined' ||
        typeof window.WP_BMC_CanvasCore === 'undefined') {
        console.error('WP_BMC_Public: Les modules core sont requis');
        return;
    }

    // Configuration de la popup de comparaison pour public.js
    window.WP_BMC_CanvasDraft.setComparePopupId('wp-bmc-public-draft-compare-popup');
    const draftComparePopupId = window.WP_BMC_CanvasDraft.getComparePopupId();
    
    // Variables locales pour la gestion des brouillons (compatibilité)
    var pendingDraftData = null;
    
    // Fonction pour obtenir les données du brouillon en attente
    function getPendingDraftData() {
        return pendingDraftData || window.WP_BMC_CanvasDraft.getPendingData();
    }
    
    
    function applyDraftData() {
        const draftData = getPendingDraftData();
        if (!draftData || !draftData.sections) return;
        
        Object.keys(draftData.sections).forEach(function(section) {
            const content = draftData.sections[section];
            $('.canvas-input[data-section="' + section + '"]').val(content);
        });
        
        if (draftData.canvas_versions && window.WP_BMC_CanvasVersions) {
            window.WP_BMC_CanvasVersions.merge(draftData.canvas_versions);
        }
        
        window.WP_BMC_CanvasDraft.setHasUnsavedDraft(true);
        showNotification('Brouillon appliqué. Vérifie qu\'il n\'est pas dépassé par une sauvegarde serveur.', 'info');
    }
    
    function openDraftComparePopup() {
        const draftData = getPendingDraftData();
        if (!draftData || !draftData.sections) {
            showNotification('Aucun brouillon à comparer.', 'error');
            return;
        }
        
        const projectId = window.WP_BMC_Utils.getProjectId();
        if (!projectId) {
            showNotification('Projet introuvable pour comparer le brouillon.', 'error');
            return;
        }
        
        let popup = $('#' + draftComparePopupId);
        if (!popup.length) {
            const popupHtml = `
                <div id="${draftComparePopupId}" class="wp-bmc-popup">
                    <div class="popup-overlay"></div>
                    <div class="popup-content">
                        <div class="popup-header">
                            <h3>Comparer le brouillon local</h3>
                            <button class="popup-close">&times;</button>
                        </div>
                        <div class="popup-body draft-compare-body">
                        </div>
                        <div class="popup-footer draft-compare-footer">
                            <button id="draft-compare-restore" class="wp-bmc-btn wp-bmc-btn-primary">Restaurer le brouillon</button>
                            <button id="draft-compare-discard" class="wp-bmc-btn wp-bmc-btn-secondary">Ignorer</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(popupHtml);
            popup = $('#' + draftComparePopupId);
        }
        
        const $body = popup.find('.draft-compare-body');
        $body.html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des données…</span></div>');
        
        fetchServerCanvasData(projectId).then(function(serverData) {
            $body.html(buildDraftDiffHtml(serverData, draftData));
        }).catch(function() {
            $body.html('<p>Impossible de charger les données serveur pour comparer.</p>');
        });
        
        popup.find('.popup-close, .popup-overlay').off('click').on('click', function() {
            closeDraftComparePopup();
        });
        
        popup.find('#draft-compare-restore').off('click').on('click', function() {
            applyDraftData();
            closeDraftComparePopup();
        });
        
        popup.find('#draft-compare-discard').off('click').on('click', function() {
            window.WP_BMC_CanvasDraft.clear();
            pendingDraftData = null;
            closeDraftComparePopup();
            showNotification('Brouillon local ignoré', 'info');
        });
        
        popup.fadeIn(300);
    }
    
    function closeDraftComparePopup() {
        $('#' + draftComparePopupId).fadeOut(200);
    }
    
    function buildDraftDiffHtml(serverData, draftData) {
        const sections = draftData.sections || {};
        const serverSections = serverData || {};
        const keys = Array.from(new Set(Object.keys(sections).concat(Object.keys(serverSections))));
        
        if (!keys.length) {
            return '<p>Aucun contenu dans le brouillon.</p>';
        }
        
        let html = '';
        const escapeHtml = window.WP_BMC_Utils.escapeHtml;
        
        keys.forEach(function(section) {
            const draftContent = sections[section] || '';
            const serverContent = serverSections[section] || '';
            
            const draftText = escapeHtml(String(draftContent || ''));
            const serverText = escapeHtml(String(serverContent || ''));
            
            html += `
                <div class="draft-diff-item">
                    <div class="draft-diff-header">
                        <span class="section-label">Section : ${escapeHtml(section)}</span>
                    </div>
                    <div class="draft-diff-columns">
                        <div class="draft-diff-col">
                            <div class="draft-diff-title">Version enregistrée (serveur)</div>
                            <pre class="draft-diff-content">${serverText || '<em>Vide</em>'}</pre>
                        </div>
                        <div class="draft-diff-col">
                            <div class="draft-diff-title">Brouillon local</div>
                            <pre class="draft-diff-content">${draftText || '<em>Vide</em>'}</pre>
                        </div>
                    </div>
                </div>
            `;
        });
        
        return html;
    }
    
    function fetchServerCanvasData(projectId) {
        return new Promise(function(resolve, reject) {
            $.post(wp_bmc_ajax.ajax_url, {
                action: 'wp_bmc_get_canvas',
                nonce: wp_bmc_ajax.nonce,
                project_id: projectId
            }, function(response) {
                if (response && response.success && response.data && response.data.canvas_data) {
                    resolve(response.data.canvas_data);
                } else {
                    reject(response && response.data ? response.data : 'Erreur de récupération');
                }
            }).fail(function() {
                reject('Erreur réseau');
            });
        });
    }
    
    $(window).on('beforeunload', function(e) {
        if (window.WP_BMC_CanvasDraft.hasUnsavedDraft()) {
            const message = 'Des modifications du canvas ne sont pas sauvegardées.';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    });
    
    // Initialisation générale
    initBMC();
    
    // Détecter un brouillon éventuel (stocké pour utilisation ultérieure si nécessaire)
    window.WP_BMC_CanvasDraft.detect(function(draftData) {
        pendingDraftData = draftData;
    });
    
    function initBMC() {
        // Auto-resize des textareas du canvas
        $('.canvas-input').on('input', function() {
            window.WP_BMC_CanvasCore.autoResize(this);
        });
        
        // Initialiser la taille des textareas
        $('.canvas-input').each(function() {
            window.WP_BMC_CanvasCore.autoResize(this);
        });
        
        // Sauvegarde automatique du canvas
        let saveTimeout;
        $('.canvas-input').on('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                autoSaveCanvas();
            }, 2000);
            
            // Stocker un brouillon local
            window.WP_BMC_CanvasDraft.persist('.canvas-input', function($element) {
                return $element.val();
            });
        });
        
        // Initialiser la grille du canvas
        window.WP_BMC_CanvasCore.updateGrid();
        
        // Réinitialiser la grille après un délai
        setTimeout(function() {
            window.WP_BMC_CanvasCore.updateGrid();
        }, 500);
    }
    
    // Fonction de sauvegarde automatique du canvas
    function autoSaveCanvas() {
        const projectId = window.WP_BMC_Utils.getProjectId();
        if (!projectId) return;
        
        showSavingIndicator();
        
        // Collecter les données du canvas pour la gestion des conflits
        const canvasData = {};
        $('.canvas-input').each(function() {
            const section = $(this).data('section');
            canvasData[section] = $(this).val();
        });
        
        window.WP_BMC_CanvasCore.save({
            selector: '.canvas-input',
            getContentCallback: function($element) {
                return $element.val();
            },
            onSuccess: function(response) {
                hideSavingIndicator();
                showNotification('Sauvegarde automatique effectuée', 'success');
            },
            onError: function(errorMessage, response) {
                hideSavingIndicator();
                // Les conflits sont gérés automatiquement par onConflict
                if (!window.WP_BMC_CanvasConflicts || !window.WP_BMC_CanvasConflicts.hasConflicts(response)) {
                    showNotification(errorMessage, 'error');
                }
            },
            onConflict: function(response) {
                // Les conflits sont gérés automatiquement avec des popups individuelles
                // Pas besoin de notification supplémentaire ici
            }
        });
    }
    
    function updateCanvasGrid() {
        window.WP_BMC_CanvasCore.updateGrid();
    }
    
    // Fonction pour afficher les notifications (utilise maintenant le système de toast)
    function showNotification(message, type = 'info') {
        if (type === 'success') {
            WP_BMC_Toast.success(message);
        } else if (type === 'error') {
            WP_BMC_Toast.error(message);
        } else if (type === 'warning') {
            WP_BMC_Toast.warning(message);
        } else {
            WP_BMC_Toast.info(message);
        }
    }
    
    // Gestion des formulaires avec validation
    $('.wp-bmc-form').on('submit', function(e) {
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).text('Traitement...');
        
        const requiredFields = form.find('[required]');
        let isValid = true;
        
        requiredFields.each(function() {
            const field = $(this);
            if (!field.val().trim()) {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });
        
        if (!isValid) {
            showNotification('Veuillez remplir tous les champs obligatoires', 'error');
            submitBtn.prop('disabled', false).text('Réessayer');
            return false;
        }
    });
    
    // Validation en temps réel
    $('.wp-bmc-form input, .wp-bmc-form textarea').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (field.attr('required') && !value) {
            field.addClass('error');
            showFieldError(field, 'Ce champ est obligatoire');
        } else if (field.attr('type') === 'email' && value && !window.WP_BMC_Utils.isValidEmail(value)) {
            field.addClass('error');
            showFieldError(field, 'Adresse email invalide');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    });
    
    function showFieldError(field, message) {
        hideFieldError(field);
        const errorDiv = $('<div>')
            .addClass('field-error')
            .text(message)
            .insertAfter(field);
    }
    
    function hideFieldError(field) {
        field.siblings('.field-error').remove();
    }
    
    // Gestion des modales
    $('.modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Raccourcis clavier pour le canvas
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S pour sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            $('#wp-bmc-save-canvas').click();
        }
        
        // Échap pour fermer les modales
        if (e.key === 'Escape') {
            $('.modal').hide();
        }
    });
    
    // Indicateur de sauvegarde
    let isSaving = false;
    
    function showSavingIndicator() {
        if (!isSaving) {
            isSaving = true;
            const indicator = $('<div>')
                .addClass('wp-bmc-loader saving-indicator')
                .html('<div class="loader-spinner"></div><span>Sauvegarde...</span>')
                .appendTo('body');
        }
    }
    
    function hideSavingIndicator() {
        isSaving = false;
        $('.saving-indicator').remove();
    }
    
    // Amélioration de l'expérience utilisateur
    $('.canvas-input').on('focus', function() {
        $(this).parent().addClass('focused');
    }).on('blur', function() {
        $(this).parent().removeClass('focused');
    });
    
    $('#wp-bmc-export-canvas').on('click', function() {
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_export_all_data',
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                log('data:', response.data);
            } else {
                logError('Erreur lors de la génération du PDF:', response.data);
            }
        });
    });
    
    // Initialisation de la grille
    updateCanvasGrid();
    
    // Réappeler quand le contenu AJAX est chargé
    $(document).on('wp-bmc-content-loaded', function() {
        updateCanvasGrid();
    });
    
    // Réappeler après un délai pour s'assurer que le DOM est prêt
    setTimeout(function() {
        updateCanvasGrid();
    }, 100);
    
    // Exposer les fonctions globalement
    window.WP_BMC_Public = {
        updateCanvasGrid: updateCanvasGrid,
        autoResize: function(element) {
            window.WP_BMC_CanvasCore.autoResize(element);
        },
        autoSaveCanvas: autoSaveCanvas,
        showNotification: showNotification,
        showSavingIndicator: showSavingIndicator,
        hideSavingIndicator: hideSavingIndicator
    };
    
});
