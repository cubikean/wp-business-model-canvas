/**
 * JavaScript pour le plugin WP Business Model Canvas
 */

// Système de logs conditionnels pour production
var WP_BMC_DEBUG = false; // Mettre à true pour activer les logs
function log() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && log) {
        log.apply(console, arguments);
    }
}
function logWarn() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && logWarn) {
        logWarn.apply(console, arguments);
    }
}
function logError() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && logError) {
        logError.apply(console, arguments);
    }
}

jQuery(document).ready(function($) {
    
    // Initialisation générale
    initBMC();
    
    function initBMC() {
        // Auto-resize des textareas du canvas
        $('.canvas-input').on('input', function() {
            autoResize(this);
        });
        
        // Initialiser la taille des textareas
        $('.canvas-input').each(function() {
            autoResize(this);
        });
        
        // Sauvegarde automatique du canvas
        let saveTimeout;
        $('.canvas-input').on('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                autoSaveCanvas();
            }, 2000); // Sauvegarde automatique après 2 secondes d'inactivité
        });


        
        // Initialiser la grille du canvas
        updateCanvasGrid();
        
        // Réinitialiser la grille après un délai pour s'assurer que le DOM est prêt
        setTimeout(function() {
            updateCanvasGrid();
        }, 500);
    }
    
    // Fonction pour redimensionner automatiquement les textareas
    function autoResize(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    
    // Fonction de sauvegarde automatique du canvas
    function autoSaveCanvas() {
        const projectId = getProjectId();
        if (!projectId) return;
        
        // Afficher l'indicateur de sauvegarde
        showSavingIndicator();
        
        const canvasData = {};
        $('.canvas-input').each(function() {
            const section = $(this).data('section');
            const content = $(this).val();
            canvasData[section] = content;
        });
        
        const formData = {
            action: 'wp_bmc_save_canvas',
            nonce: wp_bmc_ajax.nonce,
            project_id: projectId,
            canvas_data: canvasData
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            hideSavingIndicator();
            if (response.success) {
                showNotification('Sauvegarde automatique effectuée', 'success');
            } else {
                showNotification('Erreur lors de la sauvegarde', 'error');
            }
        }).fail(function() {
            hideSavingIndicator();
            showNotification('Erreur de connexion lors de la sauvegarde', 'error');
        });
    }
    
    function updateCanvasGrid() {
        const canvasSections = $('.canvas-section');

        canvasSections.each(function() {
            const $section = $(this);
            const column = $section.attr('data-column');
            const row = $section.attr('data-row');

            $section.css({
                'grid-column': column,
                'grid-row': row
            });
            
        });
    }

    // Fonction pour obtenir l'ID du projet depuis l'URL
    function getProjectId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('project_id');
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
        
        // Désactiver le bouton pendant la soumission
        submitBtn.prop('disabled', true).text('Traitement...');
        
        // Validation côté client
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
        } else if (field.attr('type') === 'email' && value && !isValidEmail(value)) {
            field.addClass('error');
            showFieldError(field, 'Adresse email invalide');
        } else {
            field.removeClass('error');
            hideFieldError(field);
        }
    });
    
    // Fonction pour valider l'email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Fonction pour afficher les erreurs de champ
    function showFieldError(field, message) {
        hideFieldError(field);
        const errorDiv = $('<div>')
            .addClass('field-error')
            .text(message)
            .insertAfter(field);
    }
    
    // Fonction pour masquer les erreurs de champ
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
    
    
    // ========================================
    // INITIALISATION DE LA GRILLE
    // ========================================
    
    // Appeler updateCanvasGrid au chargement de la page
    updateCanvasGrid();
    
    // Réappeler quand le contenu AJAX est chargé
    $(document).on('wp-bmc-content-loaded', function() {
        updateCanvasGrid();
    });
    
    // Réappeler après un délai pour s'assurer que le DOM est prêt
    setTimeout(function() {
        updateCanvasGrid();
    }, 100);
    
    // ========================================
    // EXPOSER LES FONCTIONS GLOBALEMENT
    // ========================================
    window.WP_BMC_Public = {
        updateCanvasGrid: updateCanvasGrid,
        autoResize: autoResize,
        autoSaveCanvas: autoSaveCanvas,
        showNotification: showNotification
    };
    
});
