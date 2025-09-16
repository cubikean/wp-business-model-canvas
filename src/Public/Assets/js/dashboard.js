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
        var $button = $(this);
        
        // Mettre à jour l'URL sans recharger la page
        var currentUrl = new URL(window.location);
        currentUrl.searchParams.set('view', view);
        window.history.pushState({}, '', currentUrl.toString());
        
        // Mettre à jour les boutons actifs
        $('.view-toggle button').removeClass('wp-bmc-btn-primary').addClass('wp-bmc-btn-secondary');
        $button.removeClass('wp-bmc-btn-secondary').addClass('wp-bmc-btn-primary');
        $('.dashboard-header-title').text($button.text() + ' du projet : ' + $('.dashboard-header').data('project-name'));
        
        // Recharger le contenu du canvas via AJAX
        loadCanvasView(view);
    });
    
    // Fonction pour charger une vue du canvas via AJAX
    function loadCanvasView(view) {
        var $canvasContainer = $('.canvas-container');
        var $loadingIndicator = $('<div class="canvas-loading">Chargement...</div>');
        
        // Afficher l'indicateur de chargement
        $canvasContainer.html($loadingIndicator);
        
        // Récupérer le project_id depuis l'attribut data du container
        var projectId = $('.wp-bmc-dashboard').data('project-id') || 
                       $('.wp-bmc-canvas-container').data('project-id');
        
        console.log('loadCanvasView - projectId:', projectId);
        console.log('loadCanvasView - view:', view);
        
        // Charger le contenu via AJAX
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_load_canvas_view',
            view: view,
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            console.log('loadCanvasView - response:', response);
            if (response.success) {
                $canvasContainer.html(response.data.html);
                // Réinitialiser les événements pour les nouveaux éléments
                initCanvasEvents();
                // Réinitialiser le graphique de progression
                initProgressChart();
                // Appeler la fonction updateCanvasGrid depuis public.js
                if (typeof window.WP_BMC_Public !== 'undefined' && window.WP_BMC_Public.updateCanvasGrid) {
                    window.WP_BMC_Public.updateCanvasGrid();
                }
            } else {
                $canvasContainer.html('<div class="wp-bmc-error">Erreur lors du chargement de la vue.</div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('loadCanvasView - AJAX error:', error, xhr.responseText);
            $canvasContainer.html('<div class="wp-bmc-error">Erreur de connexion. Veuillez réessayer.</div>');
        });
    }
    
    // Fonction pour initialiser les événements du canvas
    function initCanvasEvents() {
        // Réattacher les événements aux boutons d'édition
        $('.edit-brick-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var sectionName = $(this).data('section');
            var sectionTitle = getSectionTitle(sectionName);
            var currentContent = $('[data-section="' + sectionName + '"] .canvas-content').html();
            
            openEditView(sectionName, sectionTitle, currentContent);
        });
    }
    
    // ========================================
    // VUE D'ÉDITION DES BRIQUES
    // ========================================
    
    // Gérer le clic sur les boutons d'édition
    $(document).on('click', '.edit-brick-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var sectionName = $(this).data('section');
        var sectionTitle = getSectionTitle(sectionName);
        var currentContent = $('[data-section="' + sectionName + '"] .canvas-content').html();
        
        openEditView(sectionName, sectionTitle, currentContent);
    });
    
    // Ouvrir la vue d'édition
    function openEditView(sectionName, sectionTitle, content) {
        // Définir la section actuellement éditée (priorité à la variable globale)
        currentEditingSection = sectionName;
        
        // Masquer le contenu principal
        $('.wp-bmc-dashboard > *:not(#wp-bmc-edit-view, .dashboard-header)').hide();
        $('.dashboard-header .canvas-controls').hide();
        $('.dashboard-header-title').text('Bloc projet : ' + $('.dashboard-header').data('project-name'));
        
        // Mettre à jour le contenu de la vue d'édition
        $('#edit-section-title').text(sectionTitle);
        $('#wp-bmc-edit-view').attr('data-section', sectionName);
        
        // Mettre à jour le titre des révisions pour cette brique spécifique
        $('#revisions-section-title').text(`Révisions de "${sectionTitle}"`);
        
        // Debug: vérifier que l'attribut est bien défini
        console.log('Vue d\'édition ouverte pour la section:', sectionName);
        console.log('Variable globale définie:', currentEditingSection);
        console.log('Attribut data-section défini:', $('#wp-bmc-edit-view').attr('data-section'));
        
        // Initialiser l'éditeur WYSIWYG
        let decodedContent = cleanContent(content);

        initWysiwygEditor(decodedContent);
        
        // Charger les fichiers de la section
        loadSectionFiles(sectionName);
        
        // Charger les documents de référence
        loadReferenceDocuments(sectionName);
        
        // Réinitialiser la liste des révisions pour cette brique
        $('#revisions-list').html(`
            <div class="no-revisions">
                <i class="fas fa-history"></i>
                <p>Aucune révision disponible pour cette brique</p>
                <small>Les révisions sont créées automatiquement lors des demandes de notation</small>
            </div>
        `);
        
        // Afficher la vue d'édition
        $('#wp-bmc-edit-view').fadeIn(300);
    }
    
    // Fermer la vue d'édition
    function closeEditView() {
        // Réinitialiser la section actuellement éditée
        currentEditingSection = '';
        
        $('#wp-bmc-edit-view').fadeOut(300);
        
        // Réafficher le contenu principal
        $('.wp-bmc-dashboard > *:not(#wp-bmc-edit-view)').show();
        $('.dashboard-header .canvas-controls').show();
        $('.dashboard-header-title').text($('.view-toggle button.wp-bmc-btn-primary').text() + ' du projet : ' + $('.dashboard-header').data('project-name'));
        
        // Détruire l'éditeur WYSIWYG
        if (window.wysiwygEditor) {
            window.wysiwygEditor.destroy();
            window.wysiwygEditor = null;
        }
    }
    
    // Gestionnaires d'événements pour la vue d'édition
    $(document).ready(function() {
        // Bouton de retour au tableau de bord
        $('#back-to-dashboard').on('click', function() {
            closeEditView();
        });
        
        // Bouton d'annulation
        $('#edit-cancel').on('click', function() {
            closeEditView();
        });
        
        // Sauvegarder le contenu
        $('#edit-save').on('click', function() {
            saveBrickContent();
        });
        
        // Demander notation
        $('#request-grading').on('click', function() {
            requestGrading();
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
    

    function cleanContent(str) {
        if (!str) return '';
        // 1. Supprimer les backslashes inutiles
        str = str.replace(/\\"/g, '"');
        str = str.replace(/\\\\/g, '\\');
        
        // 2. Décoder les entités HTML
        var txt = document.createElement("textarea");
        txt.innerHTML = str;
        return txt.value;
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
                 var sectionName = $('#wp-bmc-edit-view').attr('data-section');
                 loadSectionFiles(sectionName);
                 $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Fichier supprimé avec succès !</div>').show();
             } else {
                 $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
             }
         });
     }
     
     // Sauvegarder le contenu de la brique
    function saveBrickContent(callback) {
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        var content = '';
        
        if (window.wysiwygEditor) {
            content = window.wysiwygEditor.getContent();
        } else {
            content = $('.editor-content').html();
        }
        
        // Mettre à jour le contenu dans le canvas
        $('[data-section="' + sectionName + '"] .canvas-content').html(content);
        
        // Si un callback est fourni, sauvegarder via AJAX et appeler le callback
        if (callback) {
            var canvasData = {};
            $('.canvas-content').each(function() {
                var section = $(this).closest('[data-section]').data('section');
                canvasData[section] = $(this).html();
            });
            
            var formData = {
                action: 'wp_bmc_save_canvas',
                nonce: wp_bmc_ajax.nonce,
                canvas_data: canvasData
            };
            
            $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    updateLastSavedTime();
                    callback(true); // Indiquer que la sauvegarde a réussi
                } else {
                    callback(false); // Indiquer que la sauvegarde a échoué
                }
            }).fail(function() {
                callback(false); // Indiquer que la sauvegarde a échoué
            });
        } else {
            // Sauvegarder automatiquement (comportement par défaut)
            autoSaveCanvas();
            
            // Fermer la vue d'édition
            closeEditView();
            
            // Afficher un message de succès
            $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Contenu sauvegardé avec succès !</div>').show();
        }
    }
    
    // Charger les fichiers de la section
    function loadSectionFiles(sectionName) {
        console.log('Chargement des fichiers pour la section:', sectionName);
        
        var formData = {
            action: 'wp_bmc_get_section_files',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Ajouter le project_id si disponible (pour les admins)
        var projectId = getProjectIdFromUrl();
        if (projectId) {
            formData.project_id = projectId;
        }
        
        // Afficher le loader pour la liste de fichiers
        $('#files-list').addClass('loading');
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            console.log('Réponse chargement fichiers:', response);
            
            // Retirer le loader
            $('#files-list').removeClass('loading');
            
            if (response.success) {
                console.log('Fichiers chargés:', response.data.files);
                displayFiles(response.data.files);
            } else {
                console.log('Aucun fichier trouvé ou erreur');
                // Afficher un message d'erreur
                $('#files-list').html('<div class="no-files">Aucun fichier attaché</div>');
            }
        }).fail(function() {
            // Retirer le loader en cas d'erreur
            $('#files-list').removeClass('loading');
            
            // Afficher un message d'erreur
            $('#files-list').html('<div class="no-files">Erreur de connexion</div>');
        });
    }
    
    // Charger les documents de référence
    function loadReferenceDocuments(sectionName) {
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };

        // Afficher le loader pour la liste de documents
        $('#documents-list').addClass('loading');
        
        // Afficher le loader pour la grille de documents dans la popup
        $('#documents-grid').addClass('loading');

        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            // Retirer les loaders
            $('#documents-list').removeClass('loading');
            $('#documents-grid').removeClass('loading');
            
            if (response.success) {
                displayReferenceDocuments(response.data.documents, sectionName);
            } else {
                // Afficher un message d'erreur
                $('#documents-list').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
                $('#documents-grid').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
            }
        }).fail(function() {
            // Retirer les loaders en cas d'erreur
            $('#documents-list').removeClass('loading');
            $('#documents-grid').removeClass('loading');
            
            // Afficher un message d'erreur
            $('#documents-list').html('<div class="no-documents">Erreur de connexion</div>');
            $('#documents-grid').html('<div class="no-documents">Erreur de connexion</div>');
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
    
    // Récupérer l'ID du projet depuis l'URL
    function getProjectIdFromUrl() {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('project_id');
    }
    
    // Ouvrir l'uploader de fichiers
    function openFileUploader() {
        console.log('openFileUploader appelée');
        
        // Vérifier que la vue d'édition est ouverte
        if (!$('#wp-bmc-edit-view').is(':visible')) {
            console.error('Vue d\'édition non ouverte');
            alert('Veuillez d\'abord ouvrir une section pour éditer');
            return;
        }
        
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
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        
        // Debug: vérifier si la section est définie
        if (!sectionName) {
            console.error('Section non définie pour l\'upload de fichiers');
            alert('Erreur: Impossible de déterminer la section pour l\'upload');
            return;
        }
        
        console.log('Upload de fichiers pour la section:', sectionName);
        var formData = new FormData();
        
        formData.append('action', 'wp_bmc_upload_file');
        formData.append('nonce', wp_bmc_ajax.nonce);
        formData.append('section', sectionName);
        
        // Ajouter le project_id si disponible (pour les admins)
        var projectId = getProjectIdFromUrl();
        if (projectId) {
            formData.append('project_id', projectId);
        }
        
        for (var i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        
        // Ajouter un indicateur de chargement
        var $addBtn = $('#add-file-btn');
        var originalText = $addBtn.html();
        $addBtn.html('<i class="fas fa-spinner fa-spin"></i> Upload en cours...').prop('disabled', true);
        
        $.ajax({
            url: wp_bmc_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Réponse upload:', response);
                
                if (response.success) {
                    console.log('Upload réussi, rechargement des fichiers...');
                    loadSectionFiles(sectionName);
                    
                    // Afficher le message de succès
                    var $message = $('#wp-bmc-canvas-message');
                    if ($message.length > 0) {
                        $message.html('<div class="wp-bmc-message success">Fichiers uploadés avec succès !</div>').show();
                        setTimeout(function() { $message.fadeOut(); }, 3000);
                    } else {
                        alert('Fichiers uploadés avec succès !');
                    }
                } else {
                    console.error('Erreur upload:', response.data);
                    alert('Erreur lors de l\'upload : ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX upload:', error);
                alert('Erreur lors de l\'upload des fichiers.');
            },
            complete: function() {
                // Restaurer le bouton
                $addBtn.html(originalText).prop('disabled', false);
            }
        });
    }
    
    // Ouvrir le viewer de documents
    function openDocumentsViewer() {
        console.log('openDocumentsViewer appelée');
        
        var $btn = $('#view-documents-btn');
        
        // Récupérer la section depuis l'attribut data-section de la vue d'édition
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        
        if (!sectionName) {
            console.error('Section non définie pour charger les documents');
            alert('Erreur: Impossible de déterminer la section pour charger les documents');
            return;
        }
        
        console.log('Chargement des documents pour la section:', sectionName);
        
        // Ajouter l'état de chargement au bouton
        $btn.addClass('btn-loading').prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<span class="btn-text">' + originalText + '</span>');
        
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
        
        // Ajouter la popup au DOM si elle n'existe pas
        if (!$('#wp-bmc-documents-popup').length) {
            $('body').append(documentsHtml);
        }
        
        // Ouvrir la popup
        $('#wp-bmc-documents-popup').fadeIn(300);
        
        // Charger les documents avec la section
        loadDocuments(sectionName);
        
        // Retirer l'état de chargement après un délai
        setTimeout(function() {
            $btn.removeClass('btn-loading').prop('disabled', false).html(originalText);
        }, 1000);
    }
    
    // Charger les documents
    function loadDocuments(sectionName) {
        console.log('loadDocuments appelée avec la section:', sectionName);
        
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Afficher le loader pour la grille de documents
        $('#documents-grid').addClass('loading');
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            console.log('Réponse chargement documents:', response);
            
            // Retirer le loader
            $('#documents-grid').removeClass('loading');
            
            if (response.success) {
                console.log('Documents chargés:', response.data.documents);
                displayDocuments(response.data.documents);
            } else {
                // Afficher un message d'erreur
                $('#documents-grid').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
            }
        }).fail(function() {
            // Retirer le loader en cas d'erreur
            $('#documents-grid').removeClass('loading');
            
            // Afficher un message d'erreur
            $('#documents-grid').html('<div class="no-documents">Erreur de connexion</div>');
        });
    }
    
         // Afficher les documents
     function displayDocuments(documents) {
         console.log('displayDocuments appelée avec:', documents);
         
         var documentsHtml = '';
         
         if (documents && documents.length > 0) {
             console.log('Affichage de', documents.length, 'documents');
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
             console.log('Aucun document à afficher');
             documentsHtml = '<div class="no-documents">Aucun document disponible</div>';
         }
         
         $('#documents-grid').html(documentsHtml);
     }
    
    // ========================================
    // DEMANDE DE NOTATION
    // ========================================
    
    function requestGrading() {
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        var sectionTitle = getSectionTitle(sectionName);
        var $btn = $('#request-grading');
        var originalText = $btn.text();
        
        // Confirmation avant envoi
        if (!confirm('Êtes-vous sûr de vouloir demander une notation pour cette section ? L\'administrateur sera notifié.')) {
            return;
        }
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Sauvegarde en cours...');
        
        // Sauvegarder d'abord le contenu actuel
        saveBrickContent(function(saveSuccess) {
            if (!saveSuccess) {
                $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">Erreur lors de la sauvegarde. Impossible de demander la notation.</div>').show();
                $btn.prop('disabled', false).text(originalText);
                return;
            }
            
            // Mettre à jour le texte du bouton
            $btn.text('Envoi de la demande...');
            
            var formData = {
                action: 'wp_bmc_request_grading',
                nonce: wp_bmc_ajax.nonce,
                section: sectionName,
                section_title: sectionTitle
            };
            
            $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message success">Demande de notation envoyée avec succès ! L\'administrateur a été notifié.</div>').show();
                    
                    // Changer le texte du bouton pour indiquer que la demande a été envoyée
                    $btn.text('Demande envoyée').addClass('wp-bmc-btn-success').removeClass('wp-bmc-btn-warning');
                    
                    // Fermer la vue d'édition après un délai
                    setTimeout(function() {
                        closeEditView();
                    }, 2000);
                } else {
                    $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
                }
            }).fail(function() {
                $('#wp-bmc-dashboard-message').html('<div class="wp-bmc-message error">Erreur lors de l\'envoi de la demande. Veuillez réessayer.</div>').show();
            }).always(function() {
                // Réactiver le bouton
                $btn.prop('disabled', false);
            });
        });
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
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
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
    
    $('.canvas-content').on('input', function() {
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
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
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
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
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
    
    $('.canvas-content').on('input', function() {
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
        
        // Échap pour fermer les vues d'édition
        if (e.key === 'Escape') {
            closeEditView();
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
    // ACTIONS ADMINISTRATEUR
    // ========================================
    
    // Voir le canvas de l'utilisateur (pour les admins)
    $(document).on('click', '.view-user-btn', function(e) {
        e.preventDefault();
        
        var userId = $(this).data('user-id');
        if (!userId) {
            console.error('ID utilisateur manquant');
            return;
        }
        
        // Rediriger vers le canvas de l'utilisateur avec les paramètres admin
        var canvasUrl = window.location.origin + '/business-model-canvas/?admin_view=true&user_id=' + userId;
        window.open(canvasUrl, '_blank');
    });
    
    // ========================================
    // GESTION DES RÉVISIONS
    // ========================================
    
    // Charger les révisions d'une section
    function loadSectionRevisions(section) {
        console.log('Chargement des révisions pour la section:', section);
        var projectId = $('.wp-bmc-dashboard').data('project-id');
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_get_section_revisions',
            section: section,
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayRevisions(response.data.revisions, section);
            } else {
                console.error('Erreur lors du chargement des révisions:', response.data);
            }
        }).fail(function() {
            console.error('Erreur de connexion lors du chargement des révisions');
        });
    }
    
    // Afficher les révisions dans la liste
    function displayRevisions(revisions, section) {
        var $revisionsList = $('#revisions-list');
        
        if (revisions.length === 0) {
            $revisionsList.html(`
                <div class="no-revisions">
                    <i class="fas fa-history"></i>
                    <p>Aucune révision disponible pour cette brique</p>
                    <small>Les révisions sont créées automatiquement lors des demandes de notation</small>
                </div>
            `);
            return;
        }
        
        var html = '<div class="revisions-items">';
        
        revisions.forEach(function(revision, index) {
            var date = new Date(revision.created_at);
            var formattedDate = date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            var reasonLabel = getRevisionReasonLabel(revision.revision_reason);
            
            html += `
                <div class="revision-item" data-revision-id="${revision.id}">
                    <div class="revision-header">
                        <div class="revision-info">
                            <span class="revision-number">Révision ${revisions.length - index}</span>
                            <span class="revision-date">${formattedDate}</span>
                        </div>
                        <div class="revision-reason">
                            <span class="reason-badge reason-${revision.revision_reason}">${reasonLabel}</span>
                        </div>
                    </div>
                    <div class="revision-actions">
                        <button class="btn-outline --small view-revision-btn" data-revision-id="${revision.id}">
                            <i class="fas fa-eye"></i> Voir
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $revisionsList.html(html);
        
        // Attacher les événements aux boutons
        $('.view-revision-btn').on('click', function() {
            var revisionId = $(this).data('revision-id');
            viewRevision(revisionId);
        });
    }
    
    // Obtenir le label de la raison de révision
    function getRevisionReasonLabel(reason) {
        var labels = {
            'grading_request': 'Demande de notation',
            'manual': 'Modification manuelle',
            'auto_save': 'Sauvegarde automatique'
        };
        return labels[reason] || reason;
    }
    
    // Visualiser une révision dans un popup
    function viewRevision(revisionId) {
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_get_section_revision',
            revision_id: revisionId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                showRevisionPopup(response.data.revision);
            } else {
                console.error('Erreur lors du chargement de la révision:', response.data);
            }
        }).fail(function() {
            console.error('Erreur de connexion lors du chargement de la révision');
        });
    }
    
    // Afficher le popup de révision
    function showRevisionPopup(revision) {
        var date = new Date(revision.created_at);
        var formattedDate = date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        var reasonLabel = getRevisionReasonLabel(revision.revision_reason);
        
        $('#revision-popup-title').text(`Révision du ${formattedDate}`);
        $('#revision-date').text(formattedDate);
        $('#revision-reason').text(reasonLabel);
        $('#revision-content').html(revision.content || '<p class="empty-content">Aucun contenu dans cette révision</p>');
        
        $('#wp-bmc-revision-popup').fadeIn(300);
    }
    
    // Fermer le popup de révision
    function closeRevisionPopup() {
        $('#wp-bmc-revision-popup').fadeOut(300);
    }
    
    // Variable globale pour stocker la section actuellement éditée
    var currentEditingSection = '';
    
    // Événements pour les révisions
    $(document).on('click', '#load-revisions-btn', function() {
        console.log('Section actuelle (variable):', currentEditingSection);
        console.log('Section actuelle (attribut):', $('#wp-bmc-edit-view').data('section'));
        
        var section = currentEditingSection || $('#wp-bmc-edit-view').data('section');
        
        if (section) {
            loadSectionRevisions(section);
        } else {
            console.error('Aucune section trouvée pour charger les révisions');
            $('#revisions-list').html(`
                <div class="no-revisions">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur : Impossible de déterminer la section actuelle</p>
                </div>
            `);
        }
    });
    
    $(document).on('click', '#revision-popup-close', function() {
        closeRevisionPopup();
    });
    
    $(document).on('click', '.popup-overlay', function() {
        closeRevisionPopup();
    });
    
    // ========================================
    // EXPOSER LES FONCTIONS GLOBALEMENT
    // ========================================
    window.WP_BMC_Dashboard = {
        autoSaveCanvas: autoSaveCanvas,
        updateLastSavedTime: updateLastSavedTime,
        getCurrentViewMode: getCurrentViewMode,
        openEditView: openEditView,
        closeEditView: closeEditView,
        loadCanvasView: loadCanvasView,
        initCanvasEvents: initCanvasEvents,
        loadSectionRevisions: loadSectionRevisions,
        viewRevision: viewRevision
    };
    
    // Initialiser les événements du canvas au chargement
    initCanvasEvents();
    
    // Initialiser le graphique de progression du projet
    initProgressChart();
    
});

// Fonction pour initialiser le graphique de progression du projet
function initProgressChart() {
    const progressCircle = document.querySelector('.progress-circle');
    if (!progressCircle) return;
    
    // Calculer l'offset basé sur le stroke-dashoffset actuel
    const currentOffset = progressCircle.getAttribute('stroke-dashoffset');
    if (currentOffset) {
        // Définir la variable CSS pour l'animation
        progressCircle.style.setProperty('--progress-offset', currentOffset);
    }
}
