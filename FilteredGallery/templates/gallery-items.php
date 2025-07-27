<?php
/**
 * Template pour les éléments de galerie (AJAX)
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php if (!empty($images)) : ?>
    <?php foreach ($images as $image) : ?>
        <li class="filtered-gallery-item" data-category="<?php echo esc_attr($image->category_slug ?? ''); ?>">
            <img src="<?php echo esc_url($image->image_url); ?>" 
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
<?php else : ?>
    <div class="filtered-gallery-no-images">
        <h3><?php _e('Aucune image trouvée', 'filtered-gallery'); ?></h3>
        <p><?php _e('Aucune image n\'est disponible dans cette catégorie.', 'filtered-gallery'); ?></p>
    </div>
<?php endif; ?> 