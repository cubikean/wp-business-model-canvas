/**
 * JavaScript pour la configuration des sections du canvas
 * WP Business Model Canvas v2.0
 */

jQuery(document).ready(function($) {
    let canvasConfigs = {};
    let defaultConfigs = {};
    let tinyMCEInstances = {};
    
    // Charger les configurations au démarrage
    loadCanvasConfigs();
    
    // Gérer la soumission du formulaire
    $('#canvas-config-form').on('submit', function(e) {
        e.preventDefault();
        saveCanvasConfigs();
    });
    
    // Gérer la restauration des valeurs par défaut
    $('#reset-defaults').on('click', function() {
        if (confirm('Êtes-vous sûr de vouloir restaurer toutes les valeurs par défaut ?')) {
            resetToDefaults();
        }
    });
    
    /**
     * Charger les configurations depuis le serveur
     */
    function loadCanvasConfigs() {
        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_get_canvas_configs',
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                canvasConfigs = response.data.configs;
                defaultConfigs = JSON.parse(JSON.stringify(canvasConfigs)); // Copie profonde
                renderConfigForm();
            } else {
                WP_BMC_Toast.error('Erreur lors du chargement des configurations: ' + response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors du chargement des configurations.');
        });
    }
    
    /**
     * Afficher le formulaire de configuration
     */
    function renderConfigForm() {
        const $container = $('.canvas-config-sections');
        $container.empty();
        
        // Détruire les instances TinyMCE existantes
        destroyAllTinyMCE();
        
        // Mapper les clés des sections vers des noms plus lisibles
        const sectionLabels = {
            'key_partners': 'Partenaires clés',
            'key_activities': 'Activités clés',
            'key_resources': 'Ressources clés',
            'value_proposition': 'Proposition de valeur',
            'customer_segments': 'Segments clients',
            'customer_relationships': 'Relations clients',
            'channels': 'Canaux',
            'cost_structure': 'Structure des coûts',
            'revenue_streams': 'Sources de revenus'
        };
        
        Object.keys(canvasConfigs).forEach(sectionKey => {
            const config = canvasConfigs[sectionKey];
            const sectionLabel = sectionLabels[sectionKey] || sectionKey;
            
            const sectionHtml = `
                <div class="canvas-section-config" data-section="${sectionKey}">
                    <div class="section-config-header">
                        <h3 class="section-config-title">${sectionLabel}</h3>
                        <span class="section-config-key">${sectionKey}</span>
                    </div>
                    
                    <div class="config-field">
                        <label for="title_${sectionKey}">Titre de la section</label>
                        <input type="text" 
                               id="title_${sectionKey}" 
                               name="configs[${sectionKey}][title]" 
                               value="${config.title}"
                               data-default="${config.default_title}">
                        <div class="default-value">Valeur par défaut: ${config.default_title}</div>
                    </div>
                    
                    <div class="config-field">
                        <label for="placeholder_${sectionKey}">Placeholder / Question</label>
                        <textarea id="placeholder_${sectionKey}" 
                                  class="tinymce-placeholder"
                                  name="configs[${sectionKey}][placeholder]" 
                                  data-default="${config.default_placeholder}">${config.placeholder}</textarea>
                        <div class="default-value">Valeur par défaut: ${config.default_placeholder}</div>
                    </div>
                </div>
            `;
            
            $container.append(sectionHtml);
        });
        
        // Initialiser TinyMCE après le rendu
        initializeTinyMCE();
    }
    
    /**
     * Initialiser TinyMCE pour tous les placeholders
     */
    function initializeTinyMCE() {
        if (typeof tinymce === 'undefined') {
            console.warn('TinyMCE non disponible');
            return;
        }
        
        Object.keys(canvasConfigs).forEach(sectionKey => {
            const editorId = 'placeholder_' + sectionKey;

            if (tinymce.get(editorId)) {
                tinymce.get(editorId).remove();
            }

            tinymce.init({
                selector: '#' + editorId,
                height: 200,
                menubar: false,
                plugins: 'lists link paste',
                toolbar: 'undo redo | formatselect | bold italic | bullist numlist | removeformat',
                promotion: false,
                content_style: 'body { font-family: urbanist, sans-serif; font-size: 14px; }',
                paste_as_text: true,
                branding: false,
                statusbar: false,
                setup: function(editor) {
                    tinyMCEInstances[sectionKey] = editor;
                    editor.on('init', function() {
                        editor.setContent(canvasConfigs[sectionKey].placeholder || '');
                    });
                }
            });
        });
    }
    
    /**
     * Détruire toutes les instances TinyMCE
     */
    function destroyAllTinyMCE() {
        Object.keys(tinyMCEInstances).forEach(sectionKey => {
            const editorId = 'placeholder_' + sectionKey;
            if (tinymce.get(editorId)) {
                tinymce.get(editorId).remove();
            }
        });
        tinyMCEInstances = {};
    }
    
    /**
     * Sauvegarder les configurations
     */
    function saveCanvasConfigs() {
        const $form = $('#canvas-config-form');
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Collecter les données du formulaire
        const formData = {
            action: 'wp_bmc_save_canvas_configs',
            nonce: wp_bmc_admin_ajax.nonce,
            configs: {}
        };
        
        // Parcourir tous les champs de configuration
        $('.canvas-section-config').each(function() {
            const $section = $(this);
            const sectionKey = $section.data('section');
            
            // Récupérer le contenu depuis TinyMCE
            let placeholderContent = '';
            if (tinyMCEInstances[sectionKey]) {
                placeholderContent = tinyMCEInstances[sectionKey].getContent();
            } else {
                // Fallback sur le textarea si TinyMCE n'est pas initialisé
                placeholderContent = $section.find(`textarea[name="configs[${sectionKey}][placeholder]"]`).val();
            }
            
            formData.configs[sectionKey] = {
                title: $section.find(`input[name="configs[${sectionKey}][title]"]`).val(),
                placeholder: placeholderContent
            };
        });
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sauvegarde...');
        
        $.post(wp_bmc_admin_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                // Mettre à jour les configurations locales sans recharger les éditeurs
                canvasConfigs = formData.configs;
            } else {
                WP_BMC_Toast.error('Erreur lors de la sauvegarde: ' + response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors de la sauvegarde.');
        }).always(function() {
            $submitBtn.prop('disabled', false).html(originalText);
        });
    }
    
    /**
     * Restaurer les valeurs par défaut
     */
    function resetToDefaults() {
        $('.canvas-section-config').each(function() {
            const $section = $(this);
            const sectionKey = $section.data('section');
            
            // Restaurer le titre
            const $titleInput = $section.find(`input[name="configs[${sectionKey}][title]"]`);
            $titleInput.val($titleInput.data('default'));
            
            // Restaurer le placeholder dans TinyMCE
            const $placeholderTextarea = $section.find(`textarea[name="configs[${sectionKey}][placeholder]"]`);
            const defaultValue = $placeholderTextarea.data('default');
            
            if (tinyMCEInstances[sectionKey]) {
                tinyMCEInstances[sectionKey].setContent(defaultValue);
            } else {
                // Fallback sur le textarea si TinyMCE n'est pas initialisé
                $placeholderTextarea.val(defaultValue);
            }
        });
        
        WP_BMC_Toast.success('Valeurs par défaut restaurées. N\'oubliez pas de sauvegarder.');
    }
    
    
});
