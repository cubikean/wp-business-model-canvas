/**
 * JavaScript pour le plugin WP Business Model Canvas
 */

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
            if (response.success) {
                showNotification('Sauvegarde automatique effectuée', 'success');
            }
        });
    }
    
    // Fonction pour obtenir l'ID du projet depuis l'URL
    function getProjectId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('project_id');
    }
    
    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        const notification = $('<div>')
            .addClass('wp-bmc-notification')
            .addClass(type)
            .text(message)
            .appendTo('body');
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
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
                .addClass('saving-indicator')
                .html('<span>Sauvegarde...</span>')
                .appendTo('.canvas-header');
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
    
    // Tooltips pour les sections du canvas
    const tooltips = {
        'key_partners': 'Qui sont vos partenaires et fournisseurs clés ?',
        'key_activities': 'Quelles sont les activités clés de votre modèle économique ?',
        'key_resources': 'Quelles sont les ressources clés nécessaires ?',
        'value_proposition': 'Quelle valeur apportez-vous à vos clients ?',
        'customer_relationships': 'Quel type de relation entretenez-vous avec vos clients ?',
        'channels': 'Par quels canaux vos clients veulent-ils être contactés ?',
        'customer_segments': 'Pour quels segments de clients créez-vous de la valeur ?',
        'cost_structure': 'Quels sont les coûts les plus importants de votre modèle économique ?',
        'revenue_streams': 'Pour quelle valeur vos clients sont-ils prêts à payer ?'
    };
    
    $('.canvas-section').each(function() {
        const section = $(this);
        const sectionType = section.attr('class').split(' ')[1];
        const tooltip = tooltips[sectionType.replace('-', '_')];
        
        if (tooltip) {
            section.find('h3').attr('title', tooltip);
        }
    });
    
    // Export du canvas (fonctionnalité future)
    $('#wp-bmc-export-canvas').on('click', function() {
        const canvasData = {};
        $('.canvas-input').each(function() {
            const section = $(this).data('section');
            const content = $(this).val();
            canvasData[section] = content;
        });
        
        const dataStr = JSON.stringify(canvasData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = 'business-model-canvas.json';
        link.click();
        
        URL.revokeObjectURL(url);
    });
    
    // Import du canvas (fonctionnalité future)
    $('#wp-bmc-import-canvas').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    Object.keys(data).forEach(function(section) {
                        $(`[data-section="${section}"]`).val(data[section]);
                    });
                    showNotification('Canvas importé avec succès', 'success');
                } catch (error) {
                    showNotification('Erreur lors de l\'import du fichier', 'error');
                }
            };
            reader.readAsText(file);
        }
    });
    
    // Styles CSS supplémentaires pour les nouvelles fonctionnalités
    const additionalStyles = `
        <style>
            .wp-bmc-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            }
            
            .wp-bmc-notification.success {
                background: #28a745;
            }
            
            .wp-bmc-notification.error {
                background: #dc3545;
            }
            
            .wp-bmc-notification.info {
                background: #17a2b8;
            }
            
            .field-error {
                color: #dc3545;
                font-size: 12px;
                margin-top: 5px;
            }
            
            .wp-bmc-form input.error,
            .wp-bmc-form textarea.error {
                border-color: #dc3545;
            }
            
            .saving-indicator {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: #6c757d;
                font-size: 14px;
            }
            
            .saving-indicator::before {
                content: '';
                width: 12px;
                height: 12px;
                border: 2px solid #6c757d;
                border-top-color: transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            .canvas-section.focused {
                box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.2);
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }
        </style>
    `;
    
    $('head').append(additionalStyles);
});
