/**
 * JavaScript pour l'administration de FilteredGallery
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    var selectedImages = [];
    var mediaFrame;
    
    // Fonction pour ouvrir la médiathèque
    function openMediaLibrary() {
        console.log('Ouverture de la médiathèque...');
        
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        mediaFrame = wp.media({
            title: filtered_gallery_ajax.strings.select_images,
            button: {
                text: filtered_gallery_ajax.strings.select
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });
        
        mediaFrame.on('select', function() {
            var attachments = mediaFrame.state().get('selection').toJSON();
            selectedImages = attachments;
            displaySelectedImages(attachments);
            $('#filtered-gallery-modal').show();
        });
        
        mediaFrame.open();
    }
    
    // Ouvrir la médiathèque depuis les boutons
    $(document).on('click', '#open-media-library, #open-media-library-sidebar, #open-media-library-empty', function(e) {
        e.preventDefault();
        console.log('Clic sur le bouton d\'ouverture de la médiathèque');
        openMediaLibrary();
    });
    
    // Fermer la modale
    $(document).on('click', '.filtered-gallery-modal-close', function() {
        $('#filtered-gallery-modal').hide();
        selectedImages = [];
        $('#selected-images-preview').hide();
    });
    
    // Fermer la modale en cliquant à l'extérieur
    $(window).click(function(event) {
        if (event.target == document.getElementById('filtered-gallery-modal')) {
            $('#filtered-gallery-modal').hide();
            selectedImages = [];
            $('#selected-images-preview').hide();
        }
    });
    
    // Afficher les images sélectionnées
    function displaySelectedImages(attachments) {
        var html = '';
        attachments.forEach(function(attachment) {
            html += '<div class="selected-image-item" data-id="' + attachment.id + '">';
            html += '<img src="' + attachment.sizes.thumbnail.url + '">';
            html += '<span>' + attachment.title + '</span>';
            html += '</div>';
        });
        
        $('#selected-images-list').html(html);
        $('#selected-images-preview').show();
    }
    
    // Ajouter à la galerie
    $(document).on('click', '#add-to-gallery', function() {
        console.log('Clic sur le bouton ajouter à la galerie');
        
        if (selectedImages.length === 0) {
            alert(filtered_gallery_ajax.strings.select_at_least_one);
            return;
        }
        
        var categoryId = $('#gallery-category').val();
        var imageIds = selectedImages.map(function(img) { return img.id; });
        
        console.log('Données à envoyer:', {
            action: 'filtered_gallery_add_images',
            image_ids: imageIds,
            category_id: categoryId,
            nonce: filtered_gallery_ajax.nonce
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filtered_gallery_add_images',
                image_ids: imageIds,
                category_id: categoryId,
                nonce: filtered_gallery_ajax.nonce
            },
            success: function(response) {
                console.log('Réponse AJAX:', response);
                if (response.success) {
                    // Afficher le message de succès avec les détails
                    if (response.data && response.data.message) {
                        alert(response.data.message);
                    }
                    location.reload();
                } else {
                    // Afficher le message d'erreur spécifique
                    var errorMessage = response.data || filtered_gallery_ajax.strings.error_add;
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', {xhr: xhr, status: status, error: error});
                alert(filtered_gallery_ajax.strings.error_add);
            }
        });
    });
    
    // Retirer de la galerie
    $(document).on('click', '.remove-from-gallery', function() {
        var imageId = $(this).data('id');
        
        if (confirm(filtered_gallery_ajax.strings.confirm_remove)) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'filtered_gallery_remove_image',
                    image_id: imageId,
                    nonce: filtered_gallery_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(filtered_gallery_ajax.strings.error_remove);
                    }
                },
                error: function() {
                    alert(filtered_gallery_ajax.strings.error_remove);
                }
            });
        }
    });
    
    // Forcer la mise à jour des tables
    $(document).on('click', '#force-update-tables', function() {
        if (confirm('Êtes-vous sûr de vouloir mettre à jour les tables ? Cela supprimera toutes les données existantes.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'filtered_gallery_force_update_tables',
                    nonce: filtered_gallery_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Tables mises à jour avec succès. La page va se recharger.');
                        location.reload();
                    } else {
                        alert('Erreur lors de la mise à jour des tables.');
                    }
                },
                error: function() {
                    alert('Erreur lors de la mise à jour des tables.');
                }
            });
        }
    });
    
    // Tester la base de données
    $(document).on('click', '#test-database', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filtered_gallery_test_database',
                nonce: filtered_gallery_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var message = 'Test de la base de données :\n';
                    message += 'Table existe : ' + (data.table_exists ? 'Oui' : 'Non') + '\n';
                    message += 'Colonnes : ' + data.columns.join(', ') + '\n';
                    message += 'Champ updated_at : ' + (data.has_updated_at ? 'Oui' : 'Non') + '\n';
                    message += 'Nombre d\'images : ' + data.total_images;
                    alert(message);
                } else {
                    alert('Erreur lors du test : ' + response.data);
                }
            },
            error: function() {
                alert('Erreur lors du test de la base de données.');
            }
        });
    });
    
    // Édition d'image
    $(document).on('click', '.edit-image', function() {
        var imageId = $(this).data('id');
        var title = $(this).data('title');
        var description = $(this).data('description');
        var category = $(this).data('category');
        var sortOrder = $(this).data('sort-order');
        
        // Remplir le formulaire d'édition
        $('#edit-image-id').val(imageId);
        $('#edit-image-title').val(title);
        $('#edit-image-description').val(description);
        $('#edit-image-category').val(category);
        $('#edit-image-sort-order').val(sortOrder);
        
        // Afficher la modale d'édition
        $('#edit-image-modal').show();
    });
    
    // Fermer la modale d'édition
    $(document).on('click', '.cancel-edit, .filtered-gallery-modal-close', function() {
        $('#edit-image-modal').hide();
        $('#edit-image-form')[0].reset();
    });
    
    // Fermer la modale d'édition en cliquant à l'extérieur
    $(window).click(function(event) {
        if (event.target == document.getElementById('edit-image-modal')) {
            $('#edit-image-modal').hide();
            $('#edit-image-form')[0].reset();
        }
    });
    
    // Soumettre le formulaire d'édition
    $(document).on('submit', '#edit-image-form', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'filtered_gallery_update_image',
            image_id: $('#edit-image-id').val(),
            title: $('#edit-image-title').val(),
            description: $('#edit-image-description').val(),
            category_id: $('#edit-image-category').val(),
            sort_order: $('#edit-image-sort-order').val(),
            nonce: filtered_gallery_ajax.nonce
        };
        
        console.log('Données envoyées pour mise à jour:', formData);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Réponse AJAX:', response);
                if (response.success) {
                    // alert(filtered_gallery_ajax.strings.update_success);
                    $('#edit-image-modal').hide();
                    $('#edit-image-form')[0].reset();
                    location.reload();
                } else {
                    var errorMessage = response.data || 'Erreur inconnue';
                    alert(filtered_gallery_ajax.strings.update_error + ' : ' + errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', {xhr: xhr, status: status, error: error});
                var errorMessage = 'Erreur de communication avec le serveur';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                alert(filtered_gallery_ajax.strings.update_error + ' : ' + errorMessage);
            }
        });
    });
    
    // Gestion des actions en lot
    var selectedImages = [];
    
    // Sélectionner/désélectionner toutes les images
    $(document).on('change', '#select-all-images', function() {
        var isChecked = $(this).is(':checked');
        $('.image-checkbox').prop('checked', isChecked);
        updateSelectedImages();
    });
    
    // Gestion des cases à cocher individuelles
    $(document).on('change', '.image-checkbox', function() {
        updateSelectedImages();
        
        // Mettre à jour la case "sélectionner tout"
        var totalCheckboxes = $('.image-checkbox').length;
        var checkedCheckboxes = $('.image-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all-images').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-images').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all-images').prop('indeterminate', true);
        }
    });
    
    // Mettre à jour la liste des images sélectionnées
    function updateSelectedImages() {
        selectedImages = [];
        $('.image-checkbox:checked').each(function() {
            selectedImages.push($(this).val());
        });
        
        // Mettre à jour l'état du bouton d'action
        if (selectedImages.length > 0) {
            $('#do-bulk-action').prop('disabled', false);
        } else {
            $('#do-bulk-action').prop('disabled', true);
            $('#bulk-category-selector').hide();
            $('#bulk-delete-selector').hide();
        }
        
        // Mettre à jour l'apparence des lignes sélectionnées
        $('.wp-list-table tbody tr').removeClass('selected');
        $('.image-checkbox:checked').closest('tr').addClass('selected');
    }
    
    // Gestion du sélecteur d'action en lot
    $(document).on('change', '#bulk-action-selector', function() {
        var action = $(this).val();
        
        // Masquer tous les sélecteurs
        $('#bulk-category-selector').hide();
        $('#bulk-delete-selector').hide();
        
        if (action === 'change-category') {
            $('#bulk-category-selector').show();
        } else if (action === 'delete-images') {
            $('#bulk-delete-selector').show();
        }
    });
    
    // Appliquer l'action en lot
    $(document).on('click', '#do-bulk-action', function() {
        var action = $('#bulk-action-selector').val();
        
        if (action === 'change-category') {
            $('#bulk-category-selector').show();
        } else if (action === 'delete-images') {
            $('#bulk-delete-selector').show();
        }
    });
    
    // Appliquer le changement de catégorie en lot
    $(document).on('click', '#apply-bulk-category', function() {
        if (selectedImages.length === 0) {
            alert(filtered_gallery_ajax.strings.select_images);
            return;
        }
        
        var categoryId = $('#bulk-category').val();
        var categoryName = $('#bulk-category option:selected').text();
        
        var confirmMessage = filtered_gallery_ajax.strings.confirm_bulk_category.replace('{count}', selectedImages.length);
        if (!confirm(confirmMessage + ' vers "' + categoryName + '" ?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filtered_gallery_bulk_update_category',
                image_ids: selectedImages,
                category_id: categoryId,
                nonce: filtered_gallery_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(filtered_gallery_ajax.strings.bulk_update_error + ' : ' + response.data);
                }
            },
            error: function() {
                alert(filtered_gallery_ajax.strings.bulk_update_error);
            }
        });
    });
    
    // Appliquer la suppression en lot
    $(document).on('click', '#apply-bulk-delete', function() {
        if (selectedImages.length === 0) {
            alert(filtered_gallery_ajax.strings.select_images);
            return;
        }
        
        var confirmMessage = filtered_gallery_ajax.strings.confirm_bulk_delete.replace('{count}', selectedImages.length);
        if (!confirm(confirmMessage + '\n\n⚠️ Cette action est irréversible !')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filtered_gallery_bulk_delete_images',
                image_ids: selectedImages,
                nonce: filtered_gallery_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(filtered_gallery_ajax.strings.bulk_delete_error + ' : ' + response.data);
                }
            },
            error: function() {
                alert(filtered_gallery_ajax.strings.bulk_delete_error);
            }
        });
    });
    
    // Debug: Vérifier que le script se charge
    console.log('FilteredGallery Admin JS chargé');
}); 