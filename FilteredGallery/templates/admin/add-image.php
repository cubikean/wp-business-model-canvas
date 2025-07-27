<?php
/**
 * Template pour la sélection d'images depuis la médiathèque
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
}

// Récupérer l'instance de la classe admin
$admin = FilteredGallery_Admin::get_instance();

// Récupérer les catégories
$categories = $admin->get_categories();

// Récupérer les images de la galerie
$gallery_images = $admin->get_gallery_images();

// Récupérer les IDs des images déjà dans la galerie
$gallery_image_ids = array();
foreach ($gallery_images as $gallery_image) {
    $gallery_image_ids[] = $gallery_image->attachment_id;
}
?>

<div class="wrap">
    <h1><?php _e('Gérer la galerie d\'images', 'filtered-gallery'); ?></h1>
    
    <div class="filtered-gallery-admin-content">
        <div class="filtered-gallery-admin-sidebar">
            <div class="filtered-gallery-admin-form">
                <h3><?php _e('Ajouter des images à la galerie', 'filtered-gallery'); ?></h3>
                
                <p><?php _e('Sélectionnez des images depuis votre médiathèque WordPress pour les ajouter à la galerie filtrée.', 'filtered-gallery'); ?></p>
                
                <button type="button" id="open-media-library" class="button button-primary">
                    <?php _e('Ouvrir la médiathèque', 'filtered-gallery'); ?>
                </button>
                
                <div id="selected-images" style="margin-top: 20px; display: none;">
                    <h4><?php _e('Images sélectionnées :', 'filtered-gallery'); ?></h4>
                    <div id="selected-images-list"></div>
                    
                    <div style="margin-top: 15px;">
                        <label for="gallery-category"><?php _e('Catégorie :', 'filtered-gallery'); ?></label>
                        <select id="gallery-category" style="width: 100%; margin-top: 5px;">
                            <option value=""><?php _e('Aucune catégorie', 'filtered-gallery'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="button" id="add-to-gallery" class="button button-primary" style="margin-top: 15px;">
                        <?php _e('Ajouter à la galerie', 'filtered-gallery'); ?>
                    </button>
                </div>
            </div>
            
            <div class="filtered-gallery-admin-help">
                <h3><?php _e('Aide', 'filtered-gallery'); ?></h3>
                <p><?php _e('1. Cliquez sur "Ouvrir la médiathèque"', 'filtered-gallery'); ?></p>
                <p><?php _e('2. Sélectionnez les images que vous voulez ajouter', 'filtered-gallery'); ?></p>
                <p><?php _e('3. Choisissez une catégorie (optionnel)', 'filtered-gallery'); ?></p>
                <p><?php _e('4. Cliquez sur "Ajouter à la galerie"', 'filtered-gallery'); ?></p>
            </div>
        </div>
        
        <div class="filtered-gallery-admin-main">
            <h2><?php _e('Images de la galerie', 'filtered-gallery'); ?></h2>
            
            <?php if (!empty($gallery_images)) : ?>
                <div class="filtered-gallery-grid-admin">
                    <?php foreach ($gallery_images as $gallery_image) : ?>
                        <div class="filtered-gallery-item-admin" data-id="<?php echo $gallery_image->id; ?>">
                            <div class="filtered-gallery-item-image">
                                <?php 
                                $attachment = get_post($gallery_image->attachment_id);
                                if ($attachment) {
                                    echo wp_get_attachment_image($gallery_image->attachment_id, 'thumbnail');
                                } else {
                                    echo '<div class="no-image">Image supprimée</div>';
                                }
                                ?>
                            </div>
                            <div class="filtered-gallery-item-info">
                                <h4><?php echo esc_html($gallery_image->title); ?></h4>
                                <p class="category">
                                    <?php echo !empty($gallery_image->category_name) ? esc_html($gallery_image->category_name) : __('Non catégorisé', 'filtered-gallery'); ?>
                                </p>
                                <div class="actions">
                                    <button type="button" class="button button-small remove-from-gallery" 
                                            data-id="<?php echo $gallery_image->id; ?>">
                                        <?php _e('Retirer', 'filtered-gallery'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="filtered-gallery-admin-empty">
                    <p><?php _e('Aucune image n\'a été ajoutée à la galerie.', 'filtered-gallery'); ?></p>
                    <p><?php _e('Utilisez le bouton "Ouvrir la médiathèque" pour ajouter des images.', 'filtered-gallery'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.filtered-gallery-admin-content {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.filtered-gallery-admin-sidebar {
    width: 350px;
    flex-shrink: 0;
}

.filtered-gallery-admin-main {
    flex: 1;
}

.filtered-gallery-admin-form,
.filtered-gallery-admin-help {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.filtered-gallery-grid-admin {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.filtered-gallery-item-admin {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    overflow: hidden;
}

.filtered-gallery-item-image {
    text-align: center;
    padding: 10px;
    background: #f9f9f9;
}

.filtered-gallery-item-image img {
    max-width: 100%;
    height: auto;
}

.filtered-gallery-item-info {
    padding: 10px;
}

.filtered-gallery-item-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.filtered-gallery-item-info .category {
    margin: 0 0 10px 0;
    font-size: 12px;
    color: #666;
}

.filtered-gallery-item-info .actions {
    text-align: center;
}

.no-image {
    padding: 20px;
    color: #999;
    font-style: italic;
}

.filtered-gallery-admin-empty {
    text-align: center;
    padding: 40px;
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
</style>

<script>
jQuery(document).ready(function($) {
    var selectedImages = [];
    var mediaFrame;
    
    // Ouvrir la médiathèque
    $('#open-media-library').click(function() {
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        mediaFrame = wp.media({
            title: '<?php _e('Sélectionner des images pour la galerie', 'filtered-gallery'); ?>',
            button: {
                text: '<?php _e('Ajouter à la galerie', 'filtered-gallery'); ?>'
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
        });
        
        mediaFrame.open();
    });
    
    // Afficher les images sélectionnées
    function displaySelectedImages(attachments) {
        var html = '';
        attachments.forEach(function(attachment) {
            html += '<div class="selected-image" data-id="' + attachment.id + '">';
            html += '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">';
            html += '<span>' + attachment.title + '</span>';
            html += '</div>';
        });
        
        $('#selected-images-list').html(html);
        $('#selected-images').show();
    }
    
    // Ajouter à la galerie
    $('#add-to-gallery').click(function() {
        if (selectedImages.length === 0) {
            alert('<?php _e('Veuillez sélectionner au moins une image.', 'filtered-gallery'); ?>');
            return;
        }
        
        var categoryId = $('#gallery-category').val();
        var imageIds = selectedImages.map(function(img) { return img.id; });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'filtered_gallery_add_images',
                image_ids: imageIds,
                category_id: categoryId,
                nonce: '<?php echo wp_create_nonce('filtered_gallery_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('<?php _e('Erreur lors de l\'ajout des images.', 'filtered-gallery'); ?>');
                }
            }
        });
    });
    
    // Retirer de la galerie
    $('.remove-from-gallery').click(function() {
        var imageId = $(this).data('id');
        
        if (confirm('<?php _e('Êtes-vous sûr de vouloir retirer cette image de la galerie ?', 'filtered-gallery'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'filtered_gallery_remove_image',
                    image_id: imageId,
                    nonce: '<?php echo wp_create_nonce('filtered_gallery_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('<?php _e('Erreur lors de la suppression.', 'filtered-gallery'); ?>');
                    }
                }
            });
        }
    });
});
</script> 