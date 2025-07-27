<?php
/**
 * Classe d'administration FilteredGallery
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer l'interface d'administration
 */
class FilteredGallery_Admin {
    
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Constructeur de la classe
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialisation des hooks
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_post_filtered_gallery_save_image', array($this, 'save_image'));
        add_action('admin_post_filtered_gallery_save_category', array($this, 'save_category'));
        add_action('admin_post_filtered_gallery_delete_image', array($this, 'delete_image'));
        add_action('admin_post_filtered_gallery_delete_category', array($this, 'delete_category'));
    }
    
    /**
     * Initialisation de l'administration
     */
    public function admin_init() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Traitement des actions AJAX
        add_action('wp_ajax_filtered_gallery_get_images', array($this, 'ajax_get_images'));
        add_action('wp_ajax_filtered_gallery_get_categories', array($this, 'ajax_get_categories'));
        add_action('wp_ajax_filtered_gallery_add_images', array($this, 'ajax_add_images'));
        add_action('wp_ajax_filtered_gallery_remove_image', array($this, 'ajax_remove_image'));
        add_action('wp_ajax_filtered_gallery_get_image', array($this, 'ajax_get_image'));
        add_action('wp_ajax_filtered_gallery_update_image', array($this, 'ajax_update_image'));
        add_action('wp_ajax_filtered_gallery_force_update_tables', array($this, 'ajax_force_update_tables'));
        add_action('wp_ajax_filtered_gallery_test_database', array($this, 'ajax_test_database'));
        add_action('wp_ajax_filtered_gallery_bulk_update_category', array($this, 'ajax_bulk_update_category'));
        add_action('wp_ajax_filtered_gallery_bulk_delete_images', array($this, 'ajax_bulk_delete_images'));
    }
    
    /**
     * Sauvegarder une image
     */
    public function save_image() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['filtered_gallery_nonce'], 'filtered_gallery_save_image')) {
            wp_die(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'thumbnail_url' => esc_url_raw($_POST['thumbnail_url']),
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'sort_order' => intval($_POST['sort_order'])
        );
        
        if (!empty($_POST['image_id'])) {
            // Mise à jour
            $wpdb->update(
                $table_images,
                $data,
                array('id' => intval($_POST['image_id'])),
                array('%s', '%s', '%s', '%s', '%d', '%d'),
                array('%d')
            );
            $message = 'updated';
        } else {
            // Nouvelle image
            $wpdb->insert(
                $table_images,
                $data,
                array('%s', '%s', '%s', '%s', '%d', '%d')
            );
            $message = 'created';
        }
        
        // Redirection
        wp_redirect(admin_url('admin.php?page=filtered-gallery&message=' . $message));
        exit;
    }
    
    /**
     * Sauvegarder une catégorie
     */
    public function save_category() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['filtered_gallery_nonce'], 'filtered_gallery_save_category')) {
            wp_die(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description'])
        );
        
        if (!empty($_POST['category_id'])) {
            // Mise à jour
            $wpdb->update(
                $table_categories,
                $data,
                array('id' => intval($_POST['category_id'])),
                array('%s', '%s', '%s'),
                array('%d')
            );
            $message = 'category_updated';
        } else {
            // Nouvelle catégorie
            $wpdb->insert(
                $table_categories,
                $data,
                array('%s', '%s', '%s')
            );
            $message = 'category_created';
        }
        
        // Redirection
        wp_redirect(admin_url('admin.php?page=filtered-gallery-categories&message=' . $message));
        exit;
    }
    
    /**
     * Supprimer une image
     */
    public function delete_image() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_GET['nonce'], 'filtered_gallery_delete_image')) {
            wp_die(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $image_id = intval($_GET['image_id']);
        
        $wpdb->delete($table_images, array('id' => $image_id), array('%d'));
        
        // Redirection
        wp_redirect(admin_url('admin.php?page=filtered-gallery&message=deleted'));
        exit;
    }
    
    /**
     * Supprimer une catégorie
     */
    public function delete_category() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_GET['nonce'], 'filtered_gallery_delete_category')) {
            wp_die(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $category_id = intval($_GET['category_id']);
        
        // Supprimer la catégorie
        $wpdb->delete($table_categories, array('id' => $category_id), array('%d'));
        
        // Mettre à jour les images pour retirer la référence à cette catégorie
        $wpdb->update($table_images, array('category_id' => null), array('category_id' => $category_id));
        
        // Redirection
        wp_redirect(admin_url('admin.php?page=filtered-gallery-categories&message=category_deleted'));
        exit;
    }
    
    /**
     * AJAX : Récupérer les images
     */
    public function ajax_get_images() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $sql = "SELECT i.*, c.name as category_name 
                FROM $table_images i 
                LEFT JOIN $table_categories c ON i.category_id = c.id 
                ORDER BY i.sort_order ASC, i.created_at DESC";
        
        $images = $wpdb->get_results($sql);
        
        wp_send_json_success($images);
    }
    
    /**
     * AJAX : Récupérer les catégories
     */
    public function ajax_get_categories() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $categories = $wpdb->get_results("SELECT * FROM $table_categories ORDER BY name ASC");
        
        wp_send_json_success($categories);
    }
    
    /**
     * Récupérer une image par ID
     */
    public function get_image($image_id) {
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $sql = $wpdb->prepare(
            "SELECT i.*, c.name as category_name 
             FROM $table_images i 
             LEFT JOIN $table_categories c ON i.category_id = c.id 
             WHERE i.id = %d",
            $image_id
        );
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Récupérer une catégorie par ID
     */
    public function get_category($category_id) {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_categories WHERE id = %d",
            $category_id
        ));
    }
    
    /**
     * Récupérer toutes les catégories
     */
    public function get_categories() {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        return $wpdb->get_results("SELECT * FROM $table_categories ORDER BY name ASC");
    }
    
    /**
     * Récupérer toutes les images
     */
    public function get_images($args = array()) {
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $where = array();
        $join = "LEFT JOIN $table_categories ON $table_images.category_id = $table_categories.id";
        
        if (!empty($args['category_id'])) {
            $where[] = $wpdb->prepare("$table_images.category_id = %d", $args['category_id']);
        }
        
        if (!empty($args['search'])) {
            $where[] = $wpdb->prepare(
                "($table_images.title LIKE %s OR $table_images.description LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order_clause = "ORDER BY $table_images.sort_order ASC, $table_images.created_at DESC";
        $limit_clause = !empty($args['limit']) ? $wpdb->prepare("LIMIT %d", $args['limit']) : '';
        
        $sql = "SELECT $table_images.*, $table_categories.name as category_name 
                FROM $table_images 
                $join 
                $where_clause 
                $order_clause 
                $limit_clause";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Afficher un message d'administration
     */
    public function display_admin_message($message) {
        $messages = array(
            'created' => __('Image créée avec succès.', 'filtered-gallery'),
            'updated' => __('Image mise à jour avec succès.', 'filtered-gallery'),
            'deleted' => __('Image supprimée avec succès.', 'filtered-gallery'),
            'category_created' => __('Catégorie créée avec succès.', 'filtered-gallery'),
            'category_updated' => __('Catégorie mise à jour avec succès.', 'filtered-gallery'),
            'category_deleted' => __('Catégorie supprimée avec succès.', 'filtered-gallery')
        );
        
        if (isset($messages[$message])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . $messages[$message] . '</p></div>';
        }
    }
    
    /**
     * AJAX : Ajouter des images à la galerie
     */
    public function ajax_add_images() {
        // Debug
        error_log('FilteredGallery: ajax_add_images appelée');
        
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            error_log('FilteredGallery: Erreur de nonce');
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            error_log('FilteredGallery: Erreur de permissions');
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        $image_ids = array_map('intval', $_POST['image_ids']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        error_log('FilteredGallery: image_ids = ' . print_r($image_ids, true));
        error_log('FilteredGallery: category_id = ' . $category_id);
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        $added_count = 0;
        $skipped_count = 0;
        $total_images = count($image_ids);
        
        foreach ($image_ids as $attachment_id) {
            error_log('FilteredGallery: Traitement de l\'image ' . $attachment_id);
            
            // Vérifier si l'attachement existe
            $attachment = get_post($attachment_id);
            if (!$attachment || $attachment->post_type !== 'attachment') {
                error_log('FilteredGallery: Attachement ' . $attachment_id . ' non trouvé ou invalide');
                $skipped_count++;
                continue;
            }
            
            // Vérifier si l'image est déjà dans la galerie
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_images WHERE attachment_id = %d",
                $attachment_id
            ));
            
            if ($exists) {
                error_log('FilteredGallery: Image ' . $attachment_id . ' déjà dans la galerie');
                $skipped_count++;
                continue; // Image déjà dans la galerie
            }
            
            // Ajouter l'image à la galerie
            $insert_data = array(
                'attachment_id' => $attachment_id,
                'title' => $attachment->post_title ?: $attachment->post_name,
                'description' => $attachment->post_content,
                'category_id' => $category_id,
                'sort_order' => 0
            );
            
            error_log('FilteredGallery: Insertion des données: ' . print_r($insert_data, true));
            
            $result = $wpdb->insert(
                $table_images,
                $insert_data,
                array('%d', '%s', '%s', '%d', '%d')
            );
            
            if ($result) {
                $added_count++;
                error_log('FilteredGallery: Image ' . $attachment_id . ' ajoutée avec succès');
            } else {
                error_log('FilteredGallery: Erreur lors de l\'insertion de l\'image ' . $attachment_id . ': ' . $wpdb->last_error);
                $skipped_count++;
            }
        }
        
        error_log('FilteredGallery: Nombre d\'images ajoutées: ' . $added_count . ', ignorées: ' . $skipped_count);
        
        // Préparer le message de réponse
        $message = '';
        
        if ($added_count > 0) {
            $message .= sprintf(__('%d image(s) ajoutée(s) avec succès.', 'filtered-gallery'), $added_count);
        }
        
        if ($skipped_count > 0) {
            if ($message) {
                $message .= ' ';
            }
            if ($skipped_count === $total_images) {
                $message .= __('Toutes les images sélectionnées sont déjà dans la galerie.', 'filtered-gallery');
            } else {
                $message .= sprintf(__('%d image(s) ignorée(s) (déjà présentes dans la galerie).', 'filtered-gallery'), $skipped_count);
            }
        }
        
        if ($added_count > 0) {
            wp_send_json_success(array(
                'message' => $message,
                'added_count' => $added_count,
                'skipped_count' => $skipped_count
            ));
        } else {
            wp_send_json_error($message ?: __('Aucune image n\'a pu être ajoutée.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Retirer une image de la galerie
     */
    public function ajax_remove_image() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        $image_id = intval($_POST['image_id']);
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        $result = $wpdb->delete($table_images, array('id' => $image_id), array('%d'));
        
        if ($result) {
            wp_send_json_success(__('Image retirée de la galerie.', 'filtered-gallery'));
        } else {
            wp_send_json_error(__('Erreur lors de la suppression.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Récupérer les données d'une image pour l'édition
     */
    public function ajax_get_image() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        $image_id = intval($_POST['image_id']);
        $image = $this->get_image($image_id);
        
        if ($image) {
            wp_send_json_success($image);
        } else {
            wp_send_json_error(__('Image non trouvée.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Suppression en lot des images
     */
    public function ajax_bulk_delete_images() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        // Vérifier les données
        if (empty($_POST['image_ids']) || !is_array($_POST['image_ids'])) {
            wp_send_json_error(__('Aucune image sélectionnée.', 'filtered-gallery'));
        }
        
        $image_ids = array_map('intval', $_POST['image_ids']);
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Supprimer toutes les images sélectionnées
        $deleted_count = 0;
        $errors = array();
        
        foreach ($image_ids as $image_id) {
            $result = $wpdb->delete(
                $table_images,
                array('id' => $image_id),
                array('%d')
            );
            
            if ($result !== false) {
                $deleted_count++;
            } else {
                $errors[] = sprintf(__('Erreur lors de la suppression de l\'image %d', 'filtered-gallery'), $image_id);
            }
        }
        
        if ($deleted_count > 0) {
            $message = sprintf(
                __('%d image(s) supprimée(s) avec succès.', 'filtered-gallery'),
                $deleted_count
            );
            
            if (!empty($errors)) {
                $message .= ' ' . implode(', ', $errors);
            }
            
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Aucune image n\'a pu être supprimée.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Mise à jour en lot des catégories
     */
    public function ajax_bulk_update_category() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        // Vérifier les données
        if (empty($_POST['image_ids']) || !is_array($_POST['image_ids'])) {
            wp_send_json_error(__('Aucune image sélectionnée.', 'filtered-gallery'));
        }
        
        $image_ids = array_map('intval', $_POST['image_ids']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Préparer les données à mettre à jour
        $data = array('category_id' => $category_id);
        
        // Vérifier si le champ updated_at existe
        $table_structure = $wpdb->get_results("DESCRIBE $table_images");
        $has_updated_at = false;
        foreach ($table_structure as $column) {
            if ($column->Field === 'updated_at') {
                $has_updated_at = true;
                break;
            }
        }
        
        if ($has_updated_at) {
            $data['updated_at'] = current_time('mysql');
        }
        
        // Mettre à jour toutes les images sélectionnées
        $updated_count = 0;
        $errors = array();
        
        foreach ($image_ids as $image_id) {
            $result = $wpdb->update(
                $table_images,
                $data,
                array('id' => $image_id),
                $has_updated_at ? array('%d', '%s') : array('%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $updated_count++;
            } else {
                $errors[] = sprintf(__('Erreur lors de la mise à jour de l\'image %d', 'filtered-gallery'), $image_id);
            }
        }
        
        if ($updated_count > 0) {
            $message = sprintf(
                __('%d image(s) mise(s) à jour avec succès.', 'filtered-gallery'),
                $updated_count
            );
            
            if (!empty($errors)) {
                $message .= ' ' . implode(', ', $errors);
            }
            
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Aucune image n\'a pu être mise à jour.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Tester la base de données
     */
    public function ajax_test_database() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_images'") == $table_images;
        
        if (!$table_exists) {
            wp_send_json_error(__('La table des images n\'existe pas.', 'filtered-gallery'));
        }
        
        // Vérifier la structure de la table
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_images");
        $column_names = array();
        $has_updated_at = false;
        
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
            if ($column->Field === 'updated_at') {
                $has_updated_at = true;
            }
        }
        
        $result = array(
            'table_exists' => $table_exists,
            'columns' => $column_names,
            'has_updated_at' => $has_updated_at,
            'total_images' => $wpdb->get_var("SELECT COUNT(*) FROM $table_images")
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX : Forcer la mise à jour des tables
     */
    public function ajax_force_update_tables() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Vérifier si la colonne updated_at existe
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_images");
        $has_updated_at = false;
        
        foreach ($columns as $column) {
            if ($column->Field === 'updated_at') {
                $has_updated_at = true;
                break;
            }
        }
        
        // Ajouter le champ updated_at s'il n'existe pas
        if (!$has_updated_at) {
            $result = $wpdb->query("ALTER TABLE $table_images ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            if ($result !== false) {
                wp_send_json_success(__('Structure de la base de données mise à jour avec succès.', 'filtered-gallery'));
            } else {
                wp_send_json_error(__('Erreur lors de la mise à jour de la structure: ') . $wpdb->last_error);
            }
        } else {
            wp_send_json_success(__('La structure de la base de données est déjà à jour.', 'filtered-gallery'));
        }
    }
    
    /**
     * AJAX : Mettre à jour une image
     */
    public function ajax_update_image() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_send_json_error(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Vous n\'avez pas les permissions nécessaires.', 'filtered-gallery'));
        }
        
        $image_id = intval($_POST['image_id']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $sort_order = intval($_POST['sort_order']);
        
        global $wpdb;
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Vérifier si l'image existe
        $existing_image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_images WHERE id = %d",
            $image_id
        ));
        
        if (!$existing_image) {
            wp_send_json_error(__('Image non trouvée.', 'filtered-gallery'));
        }
        
        // Préparer les données à mettre à jour
        $data = array(
            'title' => $title,
            'description' => $description,
            'category_id' => $category_id,
            'sort_order' => $sort_order
        );
        
        // Vérifier si le champ updated_at existe
        $table_structure = $wpdb->get_results("DESCRIBE $table_images");
        $has_updated_at = false;
        foreach ($table_structure as $column) {
            if ($column->Field === 'updated_at') {
                $has_updated_at = true;
                break;
            }
        }
        
        if ($has_updated_at) {
            $data['updated_at'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $table_images,
            $data,
            array('id' => $image_id),
            $has_updated_at ? array('%s', '%s', '%d', '%d', '%s') : array('%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(__('Image mise à jour avec succès.', 'filtered-gallery'));
        } else {
            // Log l'erreur pour le débogage
            error_log('FilteredGallery: Erreur lors de la mise à jour de l\'image ' . $image_id . ': ' . $wpdb->last_error);
            wp_send_json_error(__('Erreur lors de la mise à jour: ') . $wpdb->last_error);
        }
    }
    
    /**
     * Récupérer les images de la galerie avec les données d'attachement
     */
    public function get_gallery_images($args = array()) {
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $where = array();
        $join = "LEFT JOIN $table_categories ON $table_images.category_id = $table_categories.id";
        
        if (!empty($args['category_id'])) {
            $where[] = $wpdb->prepare("$table_images.category_id = %d", $args['category_id']);
        }
        
        if (!empty($args['search'])) {
            $where[] = $wpdb->prepare(
                "($table_images.title LIKE %s OR $table_images.description LIKE %s)",
                '%' . $wpdb->esc_like($args['search']) . '%',
                '%' . $wpdb->esc_like($args['search']) . '%'
            );
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order_clause = "ORDER BY $table_images.sort_order ASC, $table_images.created_at DESC";
        $limit_clause = !empty($args['limit']) ? $wpdb->prepare("LIMIT %d", $args['limit']) : '';
        
        $sql = "SELECT $table_images.*, $table_categories.name as category_name, $table_categories.slug as category_slug 
                FROM $table_images 
                $join 
                $where_clause 
                $order_clause 
                $limit_clause";
        
        $results = $wpdb->get_results($sql);
        
        // Enrichir les résultats avec les données d'attachement WordPress
        foreach ($results as $result) {
            $attachment = get_post($result->attachment_id);
            if ($attachment) {
                $result->image_url = wp_get_attachment_image_url($result->attachment_id, 'large');
                $result->thumbnail_url = wp_get_attachment_image_url($result->attachment_id, 'medium');
                $result->alt_text = get_post_meta($result->attachment_id, '_wp_attachment_image_alt', true);
            } else {
                // Si l'attachement n'existe plus, supprimer de la galerie
                $wpdb->delete($table_images, array('id' => $result->id), array('%d'));
                continue;
            }
        }
        
        return $results;
    }
}

// Initialiser la classe d'administration
FilteredGallery_Admin::get_instance(); 