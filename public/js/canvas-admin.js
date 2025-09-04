/**
 * JavaScript pour l'édition admin sur la page publique du canvas
 */

jQuery(document).ready(function($) {
    
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
    
    // Gérer le clic sur les boutons de notation
    $(document).on('click', '.rate-brick-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var sectionName = $(this).data('section');
        var sectionTitle = getSectionTitle(sectionName);
        
        openRatingPopup(sectionName, sectionTitle);
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
    
    // Gestionnaires d'événements pour les popups
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
            if (fileUrl) {
                window.open(fileUrl, '_blank');
            }
        } else if (action === 'delete') {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
                deleteFile(fileId);
            }
        }
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
        
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        // Sauvegarder via AJAX
        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: wp_bmc_ajax.nonce,
            canvas_data: {}
        };
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.project_id = projectId;
        }
        
        // Collecter toutes les données du canvas
        $('.canvas-textarea').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            formData.canvas_data[section] = $(this).val();
        });
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Fermer la popup
                closeEditPopup();
                
                // Afficher un message de succès
                showMessage('Contenu sauvegardé avec succès !', 'success');
            } else {
                showMessage('Erreur lors de la sauvegarde : ' + response.data, 'error');
            }
        }).fail(function() {
            showMessage('Erreur lors de la sauvegarde.', 'error');
        });
    }
    
    // Charger les fichiers de la section
    function loadSectionFiles(sectionName) {
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        var formData = {
            action: 'wp_bmc_get_section_files',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.project_id = projectId;
        }
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                displayFiles(response.data.files);
            } else {
                console.error('Erreur lors du chargement des fichiers:', response.data);
            }
        }).fail(function(xhr, status, error) {
            console.error('Erreur AJAX:', error);
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
                            <button class="file-action-btn" data-action="view" data-file-id="${file.id}" title="Voir le fichier">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="file-action-btn" data-action="delete" data-file-id="${file.id}" title="Supprimer le fichier">
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
        
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        var formData = new FormData();
        
        formData.append('action', 'wp_bmc_upload_file');
        formData.append('nonce', wp_bmc_ajax.nonce);
        formData.append('section', sectionName);
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.append('project_id', projectId);
        }
        
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
                    showMessage('Fichiers uploadés avec succès !', 'success');
                } else {
                    showMessage('Erreur : ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Erreur lors de l\'upload des fichiers.', 'error');
            }
        });
    }
    
    // Supprimer un fichier
    function deleteFile(fileId) {
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        var formData = {
            action: 'wp_bmc_delete_file',
            nonce: wp_bmc_ajax.nonce,
            file_id: fileId
        };
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.project_id = projectId;
        }
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Recharger la liste des fichiers
                var sectionName = $('#wp-bmc-edit-popup').attr('data-section');
                loadSectionFiles(sectionName);
                showMessage('Fichier supprimé avec succès !', 'success');
            } else {
                showMessage('Erreur : ' + response.data, 'error');
            }
        });
    }
    
    // Ouvrir le viewer de documents
    function openDocumentsViewer() {
        var sectionName = $('#wp-bmc-edit-popup').attr('data-section');
        
        // Charger les documents
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                displayDocuments(response.data.documents);
                $('#wp-bmc-documents-popup').fadeIn(300);
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
                            <a href="${doc.url}" target="_blank" class="document-action-btn">
                                <i class="fas fa-external-link-alt"></i> Voir
                            </a>
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
    
    function showMessage(message, type) {
        var messageHtml = '<div class="wp-bmc-message ' + type + '">' + message + '</div>';
        $('#wp-bmc-canvas-message').html(messageHtml).fadeIn(300);
        
        setTimeout(function() {
            $('#wp-bmc-canvas-message').fadeOut(300);
        }, 3000);
    }
    
    // ========================================
    // RACCOURCIS CLAVIER
    // ========================================
    $(document).on('keydown', function(e) {
        // Échap pour fermer les popups
        if (e.key === 'Escape') {
            closeEditPopup();
            $('#wp-bmc-documents-popup').fadeOut(300);
            closeRatingPopup();
        }
    });
    
    // ========================================
    // SYSTÈME DE NOTATION POUR LES ADMINS
    // ========================================
    
    // Ouvrir la popup de notation
    function openRatingPopup(sectionName, sectionTitle) {
        // Mettre à jour le titre de la popup
        $('#rating-popup-title').text('Noter : ' + sectionTitle);
        $('#wp-bmc-rating-popup').attr('data-section', sectionName);
        
        // Charger la note actuelle
        loadCurrentRating(sectionName);
        
        // Afficher la popup
        $('#wp-bmc-rating-popup').fadeIn(300);
        $('body').addClass('popup-open');
    }
    
    // Fermer la popup de notation
    function closeRatingPopup() {
        $('#wp-bmc-rating-popup').fadeOut(300);
        $('body').removeClass('popup-open');
    }
    
    // Gestionnaires d'événements pour la popup de notation
    $('#rating-popup-close, #rating-popup-cancel').on('click', function() {
        closeRatingPopup();
    });
    
    // Gérer le slider de notation
    $('#rating-slider').on('input', function() {
        var rating = $(this).val();
        $('#rating-value').text(rating);
    });
    
    // Sauvegarder la notation
    $('#rating-popup-save').on('click', function() {
        saveRating();
    });
    
    // Charger la note actuelle
    function loadCurrentRating(sectionName) {
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        var formData = {
            action: 'wp_bmc_get_section_rating',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.project_id = projectId;
        }
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success && response.data.rating) {
                var rating = response.data.rating;
                
                // Mettre à jour le slider et l'affichage
                $('#rating-slider').val(rating.rating);
                $('#rating-value').text(rating.rating);
                $('#rating-comment').val(rating.comment || '');
                
                // Afficher la note actuelle
                var currentRatingHtml = `
                    <div class="rating-info">
                        <div class="rating-score">Note actuelle : ${rating.rating}/10</div>
                        <div class="rating-date">Noté le : ${rating.created_at}</div>
                        ${rating.comment ? '<div class="rating-comment">Commentaire : ' + rating.comment + '</div>' : ''}
                    </div>
                `;
                $('#current-rating-display').html(currentRatingHtml);
                
                // Marquer le bouton comme noté
                $('[data-section="' + sectionName + '"] .rate-brick-btn').addClass('rated');
            } else {
                // Réinitialiser les valeurs
                $('#rating-slider').val(5);
                $('#rating-value').text('5');
                $('#rating-comment').val('');
                $('#current-rating-display').html('<p>Aucune note enregistrée</p>');
                
                // Retirer la classe rated
                $('[data-section="' + sectionName + '"] .rate-brick-btn').removeClass('rated');
            }
        }).fail(function() {
            console.error('Erreur lors du chargement de la note');
        });
    }
    
    // Sauvegarder la notation
    function saveRating() {
        var sectionName = $('#wp-bmc-rating-popup').attr('data-section');
        var rating = $('#rating-slider').val();
        var comment = $('#rating-comment').val();
        
        // Récupérer le project_id depuis l'URL
        var urlParams = new URLSearchParams(window.location.search);
        var projectId = urlParams.get('project_id');
        
        var formData = {
            action: 'wp_bmc_save_section_rating',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName,
            rating: rating,
            comment: comment
        };
        
        // Ajouter le project_id si disponible
        if (projectId) {
            formData.project_id = projectId;
        }
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Fermer la popup
                closeRatingPopup();
                
                // Afficher un message de succès
                showMessage('Note sauvegardée avec succès !', 'success');
                
                // Marquer le bouton comme noté
                $('[data-section="' + sectionName + '"] .rate-brick-btn').addClass('rated');
            } else {
                showMessage('Erreur lors de la sauvegarde : ' + response.data, 'error');
            }
        }).fail(function() {
            showMessage('Erreur lors de la sauvegarde de la note.', 'error');
        });
    }
    
});
