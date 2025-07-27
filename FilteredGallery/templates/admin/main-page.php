<?php
/**
 * Template pour la page d'administration principale
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

// Afficher les messages
if (isset($_GET['message'])) {
    $admin->display_admin_message($_GET['message']);
}

// Récupérer les images
$images = $admin->get_gallery_images();
$categories = $admin->get_categories();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Filtered Gallery', 'filtered-gallery'); ?></h1>
    <button type="button" id="open-media-library" class="page-title-action">
        <?php _e('Ajouter des images', 'filtered-gallery'); ?>
    </button>
    <hr class="wp-header-end">
    
    <div class="filtered-gallery-admin-content">
        <div class="filtered-gallery-admin-sidebar">
            <div class="filtered-gallery-admin-stats">
                <h3><?php _e('Statistiques', 'filtered-gallery'); ?></h3>
                <ul>
                    <li><strong><?php echo count($images); ?></strong> <?php _e('images', 'filtered-gallery'); ?></li>
                    <li><strong><?php echo count($admin->get_categories()); ?></strong> <?php _e('catégories', 'filtered-gallery'); ?></li>
                </ul>
            </div>
            
            <div class="filtered-gallery-admin-actions">
                <h3><?php _e('Actions rapides', 'filtered-gallery'); ?></h3>
                <p>
                    <button type="button" id="open-media-library-sidebar" class="button button-primary">
                        <?php _e('Ajouter des images', 'filtered-gallery'); ?>
                    </button>
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=filtered-gallery-categories'); ?>" class="button">
                        <?php _e('Gérer les catégories', 'filtered-gallery'); ?>
                    </a>
                </p>
            </div>
            
            <div class="filtered-gallery-admin-help">
                <h3><?php _e('Aide', 'filtered-gallery'); ?></h3>
                <p><?php _e('1. Cliquez sur "Ajouter des images"', 'filtered-gallery'); ?></p>
                <p><?php _e('2. Sélectionnez les images dans la médiathèque', 'filtered-gallery'); ?></p>
                <p><?php _e('3. Choisissez une catégorie (optionnel)', 'filtered-gallery'); ?></p>
                <p><?php _e('4. Cliquez sur "Ajouter à la galerie"', 'filtered-gallery'); ?></p>
                <hr>
                <p><?php _e('Utilisez le shortcode <code>[filtered_gallery]</code> dans vos pages.', 'filtered-gallery'); ?></p>
            </div>
            
            <!-- <div class="filtered-gallery-admin-tools">
                <h3><?php _e('Outils', 'filtered-gallery'); ?></h3>
                <button type="button" id="force-update-tables" class="button button-secondary">
                    <?php _e('Mettre à jour les tables', 'filtered-gallery'); ?>
                </button>
                <p class="description"><?php _e('Utilisez ce bouton si vous rencontrez des erreurs de base de données.', 'filtered-gallery'); ?></p>
                
                <button type="button" id="test-database" class="button button-secondary" style="margin-top: 10px;">
                    <?php _e('Tester la base de données', 'filtered-gallery'); ?>
                </button>
                <p class="description"><?php _e('Teste la structure de la base de données.', 'filtered-gallery'); ?></p>
            </div> -->
        </div>
        
        <div class="filtered-gallery-admin-main">
            <h2><?php _e('Images de la galerie', 'filtered-gallery'); ?></h2>
            
            <?php if (!empty($images)) : ?>
                <!-- Actions en lot -->
                <div class="bulk-actions-wrapper">
                    <div class="bulk-actions">
                        <select id="bulk-action-selector">
                            <option value=""><?php _e('Actions en lot', 'filtered-gallery'); ?></option>
                            <option value="change-category"><?php _e('Changer la catégorie', 'filtered-gallery'); ?></option>
                            <option value="delete-images"><?php _e('Supprimer les images', 'filtered-gallery'); ?></option>
                        </select>
                        <button type="button" id="do-bulk-action" class="button action" disabled>
                            <?php _e('Appliquer', 'filtered-gallery'); ?>
                        </button>
                    </div>
                    
                    <div id="bulk-category-selector" style="display: none; margin-top: 10px;">
                        <label for="bulk-category"><?php _e('Nouvelle catégorie :', 'filtered-gallery'); ?></label>
                        <select id="bulk-category" style="margin-left: 10px;">
                            <option value=""><?php _e('Aucune catégorie', 'filtered-gallery'); ?></option>
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="apply-bulk-category" class="button button-primary" style="margin-left: 10px;">
                            <?php _e('Appliquer aux images sélectionnées', 'filtered-gallery'); ?>
                        </button>
                    </div>
                    
                    <div id="bulk-delete-selector" style="display: none; margin-top: 10px;">
                        <p style="margin: 0 0 10px 0; color: #d63638; font-weight: 600;">
                            ⚠️ <?php _e('Attention : Cette action est irréversible !', 'filtered-gallery'); ?>
                        </p>
                        <p style="margin: 0 0 15px 0; font-size: 13px;">
                            <?php _e('Les images sélectionnées seront définitivement supprimées de la galerie.', 'filtered-gallery'); ?>
                        </p>
                        <button type="button" id="apply-bulk-delete" class="button button-link-delete" style="margin-left: 0;">
                            <?php _e('Supprimer définitivement les images sélectionnées', 'filtered-gallery'); ?>
                        </button>
                    </div>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="select-all-images">
                            </th>
                            <th><?php _e('Image', 'filtered-gallery'); ?></th>
                            <th><?php _e('Titre', 'filtered-gallery'); ?></th>
                            <th><?php _e('Catégorie', 'filtered-gallery'); ?></th>
                            <th><?php _e('Date', 'filtered-gallery'); ?></th>
                            <th><?php _e('Actions', 'filtered-gallery'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($images as $image) : ?>
                            <tr>
                                <td class="check-column">
                                    <input type="checkbox" class="image-checkbox" value="<?php echo $image->id; ?>">
                                </td>
                                <td>
                                    <?php 
                                    $attachment = get_post($image->attachment_id);
                                    if ($attachment) {
                                        echo wp_get_attachment_image($image->attachment_id, array(80, 80));
                                    } else {
                                        echo '<div style="width: 80px; height: 80px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <span style="color: #999;">' . __('Supprimée', 'filtered-gallery') . '</span>
                                        </div>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($image->title); ?></strong>
                                    <?php if (!empty($image->description)) : ?>
                                        <br><small><?php echo esc_html(wp_trim_words($image->description, 10)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo !empty($image->category_name) ? esc_html($image->category_name) : __('Non catégorisé', 'filtered-gallery'); ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($image->created_at)); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small edit-image" 
                                            data-id="<?php echo $image->id; ?>"
                                            data-title="<?php echo esc_attr($image->title); ?>"
                                            data-description="<?php echo esc_attr($image->description); ?>"
                                            data-category="<?php echo esc_attr($image->category_id); ?>"
                                            data-sort-order="<?php echo esc_attr($image->sort_order); ?>">
                                        <?php _e('Éditer', 'filtered-gallery'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete remove-from-gallery" 
                                            data-id="<?php echo $image->id; ?>"
                                            onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir retirer cette image de la galerie ?', 'filtered-gallery'); ?>')">
                                        <?php _e('Retirer', 'filtered-gallery'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="filtered-gallery-admin-empty">
                    <p><?php _e('Aucune image n\'a été ajoutée à la galerie.', 'filtered-gallery'); ?></p>
                    <p>
                        <button type="button" id="open-media-library-empty" class="button button-primary">
                            <?php _e('Ajouter votre première image', 'filtered-gallery'); ?>
                        </button>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Les styles sont maintenant gérés par le fichier admin.css -->

<!-- Modal pour la sélection d'images -->
<div id="filtered-gallery-modal" class="filtered-gallery-modal">
    <div class="filtered-gallery-modal-content">
        <div class="filtered-gallery-modal-header">
            <h2><?php _e('Sélectionner des images pour la galerie', 'filtered-gallery'); ?></h2>
            <span class="filtered-gallery-modal-close">&times;</span>
        </div>
        
        <div id="selected-images-preview" class="selected-images-preview">
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
</div>

<!-- Modal pour l'édition d'image -->
<div id="edit-image-modal" class="filtered-gallery-modal">
    <div class="filtered-gallery-modal-content">
        <div class="filtered-gallery-modal-header">
            <h2><?php _e('Éditer l\'image', 'filtered-gallery'); ?></h2>
            <span class="filtered-gallery-modal-close">&times;</span>
        </div>
        
        <div class="edit-image-form">
            <form id="edit-image-form">
                <input type="hidden" id="edit-image-id" name="image_id">
                
                <div class="form-field">
                    <label for="edit-image-title"><?php _e('Titre :', 'filtered-gallery'); ?></label>
                    <input type="text" id="edit-image-title" name="title" class="regular-text" required>
                </div>
                
                <div class="form-field">
                    <label for="edit-image-description"><?php _e('Description :', 'filtered-gallery'); ?></label>
                    <textarea id="edit-image-description" name="description" rows="3" class="large-text"></textarea>
                </div>
                
                <div class="form-field">
                    <label for="edit-image-category"><?php _e('Catégorie :', 'filtered-gallery'); ?></label>
                    <select id="edit-image-category" name="category_id" class="regular-text">
                        <option value=""><?php _e('Aucune catégorie', 'filtered-gallery'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Enregistrer', 'filtered-gallery'); ?></button>
                    <button type="button" class="button cancel-edit"><?php _e('Annuler', 'filtered-gallery'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Le JavaScript est maintenant géré par le fichier admin.js --> 