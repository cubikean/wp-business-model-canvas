<?php
/**
 * Template principal pour l'affichage de la galerie
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les catégories pour les filtres
global $wpdb;
$table_categories = $wpdb->prefix . 'filtered_gallery_categories';
$categories = $wpdb->get_results("SELECT * FROM $table_categories ORDER BY name ASC");

// Générer un ID unique pour la galerie
$gallery_id = 'filtered-gallery-' . uniqid();
?>

<div class="filtered-gallery" id="<?php echo esc_attr($gallery_id); ?>">
    <?php if (!empty($categories)) : ?>
        <div class="filtered-gallery-filters">
            <a href="#" class="filtered-gallery-filter active" data-category=""><?php _e('Toutes', 'filtered-gallery'); ?></a>
            <?php foreach ($categories as $category) : ?>
                <a href="#" class="filtered-gallery-filter" data-category="<?php echo esc_attr($category->slug); ?>">
                    <?php echo esc_html($category->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="filtered-gallery-container">
        <?php if (!empty($images)) : ?>
            <ul class="filtered-gallery-grid columns-<?php echo intval($atts['columns'] ?? 3); ?>">
                <?php foreach ($images as $image) : ?>
                    <li class="filtered-gallery-item" data-category="<?php echo esc_attr($image->category_slug ?? ''); ?>">
                        <img src="<?php echo esc_url($image->thumbnail_url); ?>" 
                             alt="<?php echo esc_attr($image->alt_text ?: $image->title); ?>"
                             data-full="<?php echo esc_url($image->image_url); ?>"
                             loading="lazy">
                        
                        <?php if (!empty($image->category_name)) : ?>
                            <div class="filtered-gallery-category"><?php echo esc_html($image->category_name); ?></div>
                        <?php endif; ?>
                        
                        <div class="filtered-gallery-overlay">
                            <h3 class="filtered-gallery-title"><?php echo esc_html($image->title); ?></h3>
                            <?php if (!empty($image->description)) : ?>
                                <p class="filtered-gallery-description"><?php echo esc_html($image->description); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="filtered-gallery-no-images">
                <h3><?php _e('Aucune image trouvée', 'filtered-gallery'); ?></h3>
                <p><?php _e('Aucune image n\'est disponible dans cette galerie.', 'filtered-gallery'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div> 