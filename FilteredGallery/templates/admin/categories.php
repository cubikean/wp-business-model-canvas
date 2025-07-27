<?php
/**
 * Template pour la gestion des catégories
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

// Récupérer les catégories
$categories = $admin->get_categories();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Catégories de galerie', 'filtered-gallery'); ?></h1>
    <hr class="wp-header-end">
    
    <div class="filtered-gallery-admin-content">
        <div class="filtered-gallery-admin-sidebar">
            <div class="filtered-gallery-admin-form">
                <h3><?php _e('Ajouter une catégorie', 'filtered-gallery'); ?></h3>
                
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="filtered_gallery_save_category">
                    <?php wp_nonce_field('filtered_gallery_save_category', 'filtered_gallery_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name"><?php _e('Nom', 'filtered-gallery'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="description"><?php _e('Description', 'filtered-gallery'); ?></label>
                            </th>
                            <td>
                                <textarea id="description" name="description" rows="3" class="large-text"></textarea>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" 
                               value="<?php _e('Ajouter', 'filtered-gallery'); ?>">
                    </p>
                </form>
            </div>
        </div>
        
        <div class="filtered-gallery-admin-main">
            <h2><?php _e('Catégories existantes', 'filtered-gallery'); ?></h2>
            
            <?php if (!empty($categories)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Nom', 'filtered-gallery'); ?></th>
                            <th><?php _e('Slug', 'filtered-gallery'); ?></th>
                            <th><?php _e('Description', 'filtered-gallery'); ?></th>
                            <th><?php _e('Date de création', 'filtered-gallery'); ?></th>
                            <th><?php _e('Actions', 'filtered-gallery'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($category->name); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo esc_html($category->slug); ?></code>
                                </td>
                                <td>
                                    <?php echo !empty($category->description) ? esc_html($category->description) : __('Aucune description', 'filtered-gallery'); ?>
                                </td>
                                <td>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($category->created_at)); ?>
                                </td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=filtered_gallery_delete_category&category_id=' . $category->id), 'filtered_gallery_delete_category'); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php _e('Êtes-vous sûr de vouloir supprimer cette catégorie ?', 'filtered-gallery'); ?>')">
                                        <?php _e('Supprimer', 'filtered-gallery'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="filtered-gallery-admin-empty">
                    <p><?php _e('Aucune catégorie n\'a été créée.', 'filtered-gallery'); ?></p>
                    <p><?php _e('Créez votre première catégorie en utilisant le formulaire à gauche.', 'filtered-gallery'); ?></p>
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
    width: 400px;
    flex-shrink: 0;
}

.filtered-gallery-admin-main {
    flex: 1;
}

.filtered-gallery-admin-form {
    background: white;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.filtered-gallery-admin-form .form-table th {
    width: 120px;
}

.filtered-gallery-admin-empty {
    text-align: center;
    padding: 40px;
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
</style> 