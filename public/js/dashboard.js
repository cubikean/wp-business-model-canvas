/**
 * JavaScript pour le dashboard WP Business Model Canvas
 * Gère la création de projet, la sauvegarde, les vues et les popups d'édition
 */

jQuery(document).ready(function($) {
    
    // ========================================
    // CRÉATION DU PREMIER CANVAS
    // ========================================
    $('#wp-bmc-create-first-canvas-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $message = $('#wp-bmc-dashboard-message');
        
        // Désactiver le bouton pendant la soumission
        $submitBtn.prop('disabled', true).text('Création en cours...');
        
        var formData = {
            action: 'wp_bmc_create_project',
            nonce: wp_bmc_ajax.nonce,
            title: $('#project_title').val(),
            description: $('#project_description').val()
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $message.html('<div class="wp-bmc-message success">' + response.data.message + '</div>').show();
                setTimeout(function() {
                    window.location.reload(); // Recharger pour afficher le canvas
                }, 1500);
            } else {
                $message.html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
            }
        }).fail(function() {
            $message.html('<div class="wp-bmc-message error">Erreur lors de la création du projet. Veuillez réessayer.</div>').show();
        }).always(function() {
            // Réactiver le bouton
            $submitBtn.prop('disabled', false).text('Créer mon canvas');
        });
    });
    
    // ========================================
    // CHANGEMENT DE VUE (SYNTHÉTIQUE/GLOBALE)
    // ========================================
    $('.view-toggle button').on('click', function() {
        var view = $(this).data('view');
        var currentUrl = new URL(window.location);
        
        // Mettre à jour le paramètre de vue
        currentUrl.searchParams.set('view', view);
        
        // Rediriger vers la nouvelle vue
        window.location.href = currentUrl.toString();
    });
    
    // ========================================
    // POPUP D'ÉDITION DES BRIQUES
    // ========================================
    
    // Gérer le clic sur les boutons d'édition
    $(document).on('click', '.edit-brick-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var sectionName = $(this).data('section');
        var sectionTitle = getSectionTitle(sectionName);
        var currentContent = $('[data-section="' + sectionName + '"] .canvas-textarea').val();
        
        openEditPopup(sectionName, sectionTitle, currentContent);
    });
    
    // Ouvrir la popup d'édition
    function openEditPopup(sectionName, sectionTitle, content) {
        // Mettre à jour le contenu de la popup
        $('#popup-title').text('Éditer : ' + sectionTitle);
        $('#wp-bmc-edit-popup').attr('data-section', sectionName);
        
        // Initialiser l'éditeur WYSIWYG
        initWysiwygEditor(content);
        
        // Charger les fichiers de la section
        loadSectionFiles(sectionName);
        
        // Charger les documents de référence
        loadReferenceDocuments(sectionName);
        
        // Afficher la popup
        $('#wp-bmc-edit-popup').fadeIn(300);
        $('body').addClass('popup-open');
    }
    
    // Fermer la popup d'édition
    function closeEditPopup() {
        $('#wp-bmc-edit-popup').fadeOut(300);
        $('body').removeClass('popup-open');
        
        // Détruire l'éditeur WYSIWYG
        if (window.wysiwygEditor) {
            window.wysiwygEditor.destroy();
            window.wysiwygEditor = null;
        }
    }
    
    // Gestionnaires d'événements pour les popups existantes
    $(document).ready(function() {
        // Fermer la popup d'édition
        $('#popup-close, .popup-overlay, #popup-cancel').on('click', function() {
            closeEditPopup();
        });
        
        // Sauvegarder le contenu
        $('#popup-save').on('click', function() {
            saveBrickContent();
        });
        
        // Ajouter des fichiers
        $('#add-file-btn').on('click', function() {
            openFileUploader();
        });
        
        // Consulter les documents
        $('#view-documents-btn').on('click', function() {
            openDocumentsViewer();
        });
        
                 // Fermer la popup des documents
         $('#documents-popup-close, #wp-bmc-documents-popup .popup-overlay').on('click', function() {
             $('#wp-bmc-documents-popup').fadeOut(300);
         });
         
         // Gérer les actions sur les fichiers
         $(document).on('click', '.file-action-btn', function() {
             var action = $(this).data('action');
             var fileId = $(this).data('file-id');
             
             if (action === 'view') {
                 // Ouvrir le fichier dans un nouvel onglet
                 var fileUrl = $(this).closest('.file-item').find('.file-name').data('url');
                 console.log(fileUrl);
                 if (fileUrl) {
                     window.open(fileUrl, '_blank');
                 }
             } else if (action === 'delete') {
                 if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
                     deleteFile(fileId);
                 }
             }
         });
     });
    
    // Initialiser l'éditeur WYSIWYG
    function initWysiwygEditor(content) {
        // Utiliser TinyMCE si disponible, sinon un éditeur simple
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#wysiwyg-editor',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
                setup: function(editor) {
                    window.wysiwygEditor = editor;
                    editor.setContent(content || '');
                }
            });
        } else {
            // Éditeur simple en fallback
            var simpleEditor = `
                <div class="simple-editor">
                    <div class="editor-toolbar">
                        <button type="button" class="toolbar-btn" data-command="bold"><i class="fas fa-bold"></i></button>
                        <button type="button" class="toolbar-btn" data-command="italic"><i class="fas fa-italic"></i></button>
                        <button type="button" class="toolbar-btn" data-command="underline"><i class="fas fa-underline"></i></button>
                        <button type="button" class="toolbar-btn" data-command="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="toolbar-btn" data-command="insertOrderedList"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <div class="editor-content" contenteditable="true">${content || ''}</div>
                </div>
            `;
            
            $('#wysiwyg-editor').html(simpleEditor);
            
            // Gérer les boutons de la toolbar
            $('.toolbar-btn').on('click', function() {
                var command = $(this).data('command');
                document.execCommand(command, false, null);
            });
        }
    }
    
         // Supprimer un fichier
     function deleteFile(fileId) {
         var formData = {
             action: 'wp_bmc_delete_file',
             nonce: wp_bmc_ajax.nonce,
             file_id: fileId
         };
         
         $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
             if (response.success) {
                 // Recharger la liste des fichiers
                 var sectionName = $('#wp-bmc-edit-popup').attr('data-section');
                 loadSectionFiles(sectionName);
                 $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Fichier supprimé avec succès !</div>').show();
             } else {
                 $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
             }
         });
     }
     
     // Sauvegarder le contenu de la brique
    function saveBrickContent() {
        var sectionName = $('#wp-bmc-edit-popup').attr('data-section');
        var content = '';
        
        if (window.wysiwygEditor) {
            content = window.wysiwygEditor.getContent();
        } else {
            content = $('.editor-content').html();
        }
        
        // Mettre à jour le textarea dans le canvas
        $('[data-section="' + sectionName + '"] .canvas-textarea').val(content);
        
        // Sauvegarder automatiquement
        autoSaveCanvas();
        
        // Fermer la popup
        closeEditPopup();
        
        // Afficher un message de succès
        $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Contenu sauvegardé avec succès !</div>').show();
    }
    
    // Charger les fichiers de la section
    function loadSectionFiles(sectionName) {
        var formData = {
            action: 'wp_bmc_get_section_files',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                displayFiles(response.data.files);
            }
        });
    }
    
    // Charger les documents de référence
    function loadReferenceDocuments(sectionName) {
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                displayReferenceDocuments(response.data.documents, sectionName);
            }
        });
    }
    
    // Afficher les documents de référence
    function displayReferenceDocuments(documents, sectionName) {
        var documentsHtml = '';
        
        if (documents && documents.length > 0) {
            documents.forEach(function(doc) {
                // Filtrer par catégorie (all ou section spécifique)
                if (doc.category === 'all' || doc.category === sectionName) {
                    documentsHtml += `
                        <div class="document-item" data-document-id="${doc.id}">
                            <div class="document-icon">
                                <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                            </div>
                            <div class="document-info">
                                <div class="document-title">${doc.title}</div>
                                <div class="document-description">${doc.description || ''}</div>
                                <div class="document-size">${formatFileSize(doc.file_size)}</div>
                            </div>
                            <div class="document-actions">
                                <a href="${doc.url}" target="_blank" class="document-action-btn" title="Voir le document">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }
            });
        }
        
        if (documentsHtml === '') {
            documentsHtml = '<div class="no-documents">Aucun document de référence disponible pour cette section</div>';
        }
        
        $('#documents-list').html(documentsHtml);
    }
    
         // Afficher les fichiers
     function displayFiles(files) {
         var filesHtml = '';
         
         if (files && files.length > 0) {
             files.forEach(function(file) {
                 filesHtml += `
                     <div class="file-item" data-file-id="${file.id}">
                         <div class="file-icon">
                             <i class="fas fa-${getFileIcon(file.file_type)}"></i>
                         </div>
                         <div class="file-info">
                             <div class="file-name" data-url="${file.url}">${file.original_name}</div>
                             <div class="file-size">${formatFileSize(file.file_size)}</div>
                         </div>
                         <div class="file-actions">
                             <button class="file-action-btn" data-action="view" data-file-id="${file.id}">
                                 <i class="fas fa-eye"></i>
                             </button>
                             <button class="file-action-btn" data-action="delete" data-file-id="${file.id}">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             });
         } else {
             filesHtml = '<div class="no-files">Aucun fichier attaché</div>';
         }
         
         $('#files-list').html(filesHtml);
     }
    
    // Ouvrir l'uploader de fichiers
    function openFileUploader() {
        // Créer un input file caché
        var $input = $('<input type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;">');
        $('body').append($input);
        
        $input.on('change', function() {
            var files = this.files;
            uploadFiles(files);
            $input.remove();
        });
        
        $input.click();
    }
    
    // Uploader les fichiers
    function uploadFiles(files) {
        var sectionName = $('#wp-bmc-edit-popup').attr('data-section');
        var formData = new FormData();
        
        formData.append('action', 'wp_bmc_upload_file');
        formData.append('nonce', wp_bmc_ajax.nonce);
        formData.append('section', sectionName);
        
        for (var i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        
        $.ajax({
            url: wp_bmc_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    loadSectionFiles(sectionName);
                    $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Fichiers uploadés avec succès !</div>').show();
                } else {
                    $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
                }
            },
            error: function() {
                $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">Erreur lors de l\'upload des fichiers.</div>').show();
            }
        });
    }
    
    // Ouvrir le viewer de documents
    function openDocumentsViewer() {
        var documentsHtml = `
            <div id="wp-bmc-documents-popup" class="wp-bmc-popup">
                <div class="popup-overlay"></div>
                <div class="popup-content">
                    <div class="popup-header">
                        <h3>Documents de référence</h3>
                        <button class="popup-close">&times;</button>
                    </div>
                    
                    <div class="popup-body">
                        <div class="documents-grid" id="documents-grid">
                            <!-- Les documents seront chargés ici -->
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(documentsHtml);
        
        // Charger les documents
        loadDocuments();
        
        // Gérer la fermeture
        $('#wp-bmc-documents-popup .popup-close, #wp-bmc-documents-popup .popup-overlay').on('click', function() {
            $('#wp-bmc-documents-popup').remove();
        });
        
        $('#wp-bmc-documents-popup').fadeIn(300);
    }
    
    // Charger les documents
    function loadDocuments() {
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                displayDocuments(response.data.documents);
            }
        });
    }
    
         // Afficher les documents
     function displayDocuments(documents) {
         var documentsHtml = '';
         
         if (documents && documents.length > 0) {
             documents.forEach(function(doc) {
                 documentsHtml += `
                     <div class="document-item" data-doc-id="${doc.id}">
                         <div class="document-icon">
                             <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                         </div>
                         <div class="document-info">
                             <div class="document-title">${doc.title}</div>
                             <div class="document-description">${doc.description}</div>
                         </div>
                         <div class="document-actions">
                             <button class="document-action-btn" data-action="view" data-doc-id="${doc.id}">
                                 <i class="fas fa-eye"></i> Voir
                             </button>
                         </div>
                     </div>
                 `;
             });
         } else {
             documentsHtml = '<div class="no-documents">Aucun document disponible</div>';
         }
         
         $('#documents-grid').html(documentsHtml);
     }
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    function getSectionTitle(sectionName) {
        var titles = {
            'key_partners': 'Partenaires clés',
            'key_activities': 'Activités clés',
            'key_resources': 'Ressources clés',
            'value_proposition': 'Proposition de valeur',
            'customer_relationships': 'Relations clients',
            'channels': 'Canaux',
            'customer_segments': 'Segments clients',
            'cost_structure': 'Structure des coûts',
            'revenue_streams': 'Sources de revenus'
        };
        
        return titles[sectionName] || sectionName;
    }
    
         function getFileIcon(fileType) {
         if (!fileType) return 'file';
         
         if (fileType.startsWith('image/')) return 'image';
         if (fileType.startsWith('video/')) return 'video';
         if (fileType.includes('pdf')) return 'file-pdf';
         if (fileType.includes('word') || fileType.includes('document')) return 'file-word';
         if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'file-excel';
         return 'file';
     }
    
         function formatFileSize(bytes) {
         if (!bytes || bytes === 0) return '0 Bytes';
         var k = 1024;
         var sizes = ['Bytes', 'KB', 'MB', 'GB'];
         var i = Math.floor(Math.log(bytes) / Math.log(k));
         return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
     }
    
    // ========================================
    // SAUVEGARDE DU CANVAS
    // ========================================
    $('#wp-bmc-save-canvas').on('click', function() {
        var $btn = $(this);
        var $message = $('#wp-bmc-dashboard-message');
        var originalText = $btn.text();
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Sauvegarde...');
        
        // Collecter toutes les données du canvas
        var canvasData = {};
        $('.canvas-textarea').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).val();
        });
        
        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: wp_bmc_ajax.nonce,
            canvas_data: canvasData
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $message.html('<div class="wp-bmc-message success">Canvas sauvegardé avec succès !</div>').show();
                updateLastSavedTime();
            } else {
                $message.html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
            }
        }).fail(function() {
            $message.html('<div class="wp-bmc-message error">Erreur lors de la sauvegarde. Veuillez réessayer.</div>').show();
        }).always(function() {
            // Réactiver le bouton
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // SAUVEGARDE AUTOMATIQUE
    // ========================================
    var autoSaveTimer;
    var autoSaveInterval = 30000; // 30 secondes
    
    $('.canvas-textarea').on('input', function() {
        // Annuler le timer précédent
        clearTimeout(autoSaveTimer);
        
        // Démarrer un nouveau timer
        autoSaveTimer = setTimeout(function() {
            autoSaveCanvas();
        }, autoSaveInterval);
        
        // Mettre à jour le statut
        $('#auto-save-status').text('Modifications détectées - Sauvegarde automatique dans 30s');
    });
    
    function autoSaveCanvas() {
        // Collecter toutes les données du canvas
        var canvasData = {};
        $('.canvas-textarea').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).val();
        });
        
        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: wp_bmc_ajax.nonce,
            canvas_data: canvasData
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $('#auto-save-status').text('Sauvegarde automatique activée');
                updateLastSavedTime();
            }
        });
    }
    
    // ========================================
    // EXPORT PDF
    // ========================================
    $('#wp-bmc-export-pdf').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Génération PDF...');
        
        // Sauvegarder d'abord
        var canvasData = {};
        $('.canvas-textarea').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).val();
        });
        
        var formData = {
            action: 'wp_bmc_export_pdf',
            nonce: wp_bmc_ajax.nonce,
            canvas_data: canvasData,
            view_mode: getCurrentViewMode()
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Télécharger le PDF
                var link = document.createElement('a');
                link.href = response.data.pdf_url;
                link.download = 'business-model-canvas.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
            }
        }).fail(function() {
            $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">Erreur lors de la génération du PDF.</div>').show();
        }).always(function() {
            // Réactiver le bouton
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    function updateLastSavedTime() {
        var now = new Date();
        var timeString = now.getDate().toString().padStart(2, '0') + '/' + 
                       (now.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                       now.getFullYear() + ' ' + 
                       now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
        $('#last-saved-time').text('Dernière sauvegarde : ' + timeString);
    }
    
    function getCurrentViewMode() {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('view') || 'synthetic';
    }
    
    // ========================================
    // AUTO-RESIZE DES TEXTAREAS
    // ========================================
    $('.canvas-textarea').each(function() {
        autoResize($(this));
    });
    
    $('.canvas-textarea').on('input', function() {
        autoResize($(this));
    });
    
    function autoResize($textarea) {
        $textarea.css('height', 'auto');
        $textarea.css('height', $textarea[0].scrollHeight + 'px');
    }
    
    // ========================================
    // ANIMATIONS ET UX
    // ========================================
    
    // Animation des messages
    $('.wp-bmc-message').on('show', function() {
        $(this).hide().fadeIn(300);
    });
    
    // Auto-hide des messages de succès après 3 secondes
    setInterval(function() {
        $('.wp-bmc-message.success').fadeOut(500);
    }, 3000);
    
    // Auto-hide des messages d'erreur après 5 secondes
    setInterval(function() {
        $('.wp-bmc-message.error').fadeOut(500);
    }, 5000);
    
    // Focus sur le premier champ vide lors de la création
    $('#wp-bmc-create-first-canvas-form').on('submit', function() {
        var $firstEmpty = $(this).find('input[required]:invalid').first();
        if ($firstEmpty.length) {
            $firstEmpty.focus();
        }
    });
    
    // ========================================
    // RACCOURCIS CLAVIER
    // ========================================
    $(document).on('keydown', function(e) {
        // Ctrl+S pour sauvegarder
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#wp-bmc-save-canvas').click();
        }
        
        // Ctrl+E pour exporter PDF
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            $('#wp-bmc-export-pdf').click();
        }
        
        // Échap pour fermer les popups
        if (e.key === 'Escape') {
            closeEditPopup();
            $('#wp-bmc-documents-popup').remove();
        }
    });
    
    // ========================================
    // TOOLTIPS ET AIDE
    // ========================================
    $('.canvas-section h3, .synthetic-section h3').each(function() {
        var title = $(this).text();
        var helpText = getHelpText(title);
        
        if (helpText) {
            $(this).append('<span class="help-icon" title="' + helpText + '">?</span>');
        }
    });
    
    function getHelpText(sectionTitle) {
        var helpTexts = {
            'Proposition de valeur': 'Décrivez clairement la valeur que vous apportez à vos clients et ce qui vous différencie de vos concurrents.',
            'Segments clients': 'Identifiez vos clients cibles et leurs caractéristiques principales.',
            'Sources de revenus': 'Listez toutes les façons dont vous générez des revenus.',
            'Partenaires clés': 'Qui sont vos partenaires stratégiques essentiels ?',
            'Activités clés': 'Quelles sont les activités principales nécessaires à votre modèle économique ?',
            'Ressources clés': 'Quelles sont vos ressources les plus importantes (humaines, financières, physiques, intellectuelles) ?',
            'Relations clients': 'Comment établissez-vous et maintenez-vous les relations avec vos clients ?',
            'Canaux': 'Par quels canaux atteignez-vous vos clients ?',
            'Structure des coûts': 'Quels sont vos coûts principaux et comment sont-ils structurés ?'
        };
        
        return helpTexts[sectionTitle] || '';
    }
    
    // ========================================
    // EXPOSER LES FONCTIONS GLOBALEMENT
    // ========================================
    window.WP_BMC_Dashboard = {
        autoSaveCanvas: autoSaveCanvas,
        updateLastSavedTime: updateLastSavedTime,
        getCurrentViewMode: getCurrentViewMode,
        openEditPopup: openEditPopup,
        closeEditPopup: closeEditPopup
    };
    
});
