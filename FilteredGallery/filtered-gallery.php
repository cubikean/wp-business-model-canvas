<?php
/**
 * Plugin Name: FilteredGallery
 * Plugin URI: https://github.com/cubikean/WPlugins/
 * Description: Un plugin WordPress pour créer des galeries d'images avec filtres par catégories. Permet d'afficher des images organisées par catégories avec des boutons de filtrage interactifs.
 * Version: 1.0.0
 * Author: Cubikean
 * Author URI: https://cubicom.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: filtered-gallery
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 */


// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('FILTERED_GALLERY_VERSION', '1.0.0');
define('FILTERED_GALLERY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FILTERED_GALLERY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FILTERED_GALLERY_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin FilteredGallery
 */
class FilteredGallery {
    
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
     * Initialisation des hooks WordPress
     */
    private function init_hooks() {
        // Activation et désactivation du plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hooks d'initialisation
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Hooks pour les shortcodes
        add_shortcode('filtered_gallery', array($this, 'gallery_shortcode'));
        
        // Hooks pour les widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Hooks pour les menus d'administration
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Hooks pour les AJAX
        add_action('wp_ajax_filtered_gallery_filter', array($this, 'ajax_filter_gallery'));
        add_action('wp_ajax_nopriv_filtered_gallery_filter', array($this, 'ajax_filter_gallery'));
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables personnalisées si nécessaire
        $this->create_tables();
        
        // Définir les options par défaut
        $this->set_default_options();
        
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }
    
    /**
     * Initialisation du plugin
     */
    public function init() {
        // Charger les traductions
        load_plugin_textdomain('filtered-gallery', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Charger les fichiers d'inclusion
        $this->load_includes();
        
        // Vérifier et créer les tables si nécessaire
        $this->check_and_create_tables();
        
        // Initialiser la classe admin
        if (class_exists('FilteredGallery_Admin')) {
            FilteredGallery_Admin::get_instance();
        }
    }
    
    /**
     * Chargement des fichiers d'inclusion
     */
    private function load_includes() {
        $includes = array(
            'class-filtered-gallery-admin',
            'class-filtered-gallery-widget',
            'class-filtered-gallery-shortcode'
        );
        
        foreach ($includes as $include) {
            $file = FILTERED_GALLERY_PLUGIN_PATH . 'includes/' . $include . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }
    
    /**
     * Chargement des scripts et styles frontend
     */
    public function enqueue_scripts() {
        // CSS principal
        wp_enqueue_style(
            'filtered-gallery-style',
            FILTERED_GALLERY_PLUGIN_URL . 'assets/css/filtered-gallery.css',
            array(),
            FILTERED_GALLERY_VERSION
        );
        
        // JavaScript principal
        wp_enqueue_script(
            'filtered-gallery-script',
            FILTERED_GALLERY_PLUGIN_URL . 'assets/js/filtered-gallery.js',
            array('jquery'),
            FILTERED_GALLERY_VERSION,
            true
        );
        
        // Localiser le script pour AJAX
        wp_localize_script('filtered-gallery-script', 'filtered_gallery_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('filtered_gallery_nonce'),
            'strings' => array(
                'loading' => __('Chargement...', 'filtered-gallery'),
                'error' => __('Une erreur est survenue.', 'filtered-gallery')
            )
        ));
    }
    
    /**
     * Chargement des scripts et styles admin
     */
    public function admin_enqueue_scripts($hook) {
        // Charger seulement sur les pages du plugin
        if (strpos($hook, 'filtered-gallery') === false) {
            return;
        }
        
        // Charger les scripts WordPress Media
        wp_enqueue_media();
        
        wp_enqueue_style(
            'filtered-gallery-admin-style',
            FILTERED_GALLERY_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FILTERED_GALLERY_VERSION
        );
        
        wp_enqueue_script(
            'filtered-gallery-admin-script',
            FILTERED_GALLERY_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'media-views'),
            FILTERED_GALLERY_VERSION,
            true
        );
        
        // Localiser le script pour AJAX
        wp_localize_script('filtered-gallery-admin-script', 'filtered_gallery_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('filtered_gallery_nonce'),
            'strings' => array(
                'select_images' => __('Sélectionner des images pour la galerie', 'filtered-gallery'),
                'select' => __('Sélectionner', 'filtered-gallery'),
                'add_images' => __('Ajouter des images', 'filtered-gallery'),
                'remove_image' => __('Retirer cette image', 'filtered-gallery'),
                'confirm_remove' => __('Êtes-vous sûr de vouloir retirer cette image de la galerie ?', 'filtered-gallery'),
                'error_add' => __('Erreur lors de l\'ajout des images.', 'filtered-gallery'),
                'error_remove' => __('Erreur lors de la suppression.', 'filtered-gallery'),
                'select_at_least_one' => __('Veuillez sélectionner au moins une image.', 'filtered-gallery'),
                'edit_image' => __('Éditer l\'image', 'filtered-gallery'),
                'save_changes' => __('Enregistrer les modifications', 'filtered-gallery'),
                'cancel' => __('Annuler', 'filtered-gallery'),
                'update_success' => __('Image mise à jour avec succès !', 'filtered-gallery'),
                'update_error' => __('Erreur lors de la mise à jour.', 'filtered-gallery'),
                'bulk_actions' => __('Actions en lot', 'filtered-gallery'),
                'change_category' => __('Changer la catégorie', 'filtered-gallery'),
                'apply' => __('Appliquer', 'filtered-gallery'),
                'select_images' => __('Veuillez sélectionner au moins une image.', 'filtered-gallery'),
                'confirm_bulk_category' => __('Êtes-vous sûr de vouloir changer la catégorie de {count} image(s) ?', 'filtered-gallery'),
                'bulk_update_success' => __('Images mises à jour avec succès !', 'filtered-gallery'),
                'bulk_update_error' => __('Erreur lors de la mise à jour en lot.', 'filtered-gallery'),
                'delete_images' => __('Supprimer les images', 'filtered-gallery'),
                'confirm_bulk_delete' => __('Êtes-vous sûr de vouloir supprimer définitivement {count} image(s) ?', 'filtered-gallery'),
                'bulk_delete_success' => __('Images supprimées avec succès !', 'filtered-gallery'),
                'bulk_delete_error' => __('Erreur lors de la suppression en lot.', 'filtered-gallery'),
                'images_already_in_gallery' => __('le(s) image(s) sélectionné(s) est(sont) déjà dans la galerie.', 'filtered-gallery'),
                'images_skipped_already_present' => __('image(s) ignorée(s) (déjà présentes dans la galerie).', 'filtered-gallery'),
                'images_added_successfully' => __('image(s) ajoutée(s) avec succès.', 'filtered-gallery')
            )
        ));
    }
    
    /**
     * Création des tables personnalisées
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table pour les catégories de galerie
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        // Table pour les images de galerie
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $sql_images = "CREATE TABLE $table_images (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            category_id mediumint(9),
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY attachment_id (attachment_id),
            KEY category_id (category_id),
            FOREIGN KEY (category_id) REFERENCES $table_categories(id) ON DELETE SET NULL
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categories);
        dbDelta($sql_images);
    }
    
    /**
     * Définition des options par défaut
     */
    private function set_default_options() {
        $default_options = array(
            'gallery_columns' => 3,
            'gallery_spacing' => 20,
            'enable_lightbox' => true,
            'enable_lazy_loading' => true,
            'thumbnail_size' => 'medium',
            'image_size' => 'large'
        );
        
        add_option('filtered_gallery_options', $default_options);
    }
    
    /**
     * Vérifier et créer les tables si elles n'existent pas
     */
    private function check_and_create_tables() {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        
        // Vérifier si les tables existent
        $categories_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_categories'") == $table_categories;
        $images_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_images'") == $table_images;
        
        // Vérifier si la colonne attachment_id existe dans la table images
        $attachment_id_exists = false;
        $updated_at_exists = false;
        if ($images_exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_images LIKE 'attachment_id'");
            $attachment_id_exists = !empty($columns);
            
            // Vérifier si la colonne updated_at existe
            $updated_at_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_images LIKE 'updated_at'");
            $updated_at_exists = !empty($updated_at_columns);
        }
        
        // Si les tables n'existent pas OU si la colonne attachment_id n'existe pas, recréer les tables
        if (!$categories_exists || !$images_exists || !$attachment_id_exists) {
            // Supprimer les anciennes tables si elles existent
            if ($images_exists) {
                $wpdb->query("DROP TABLE IF EXISTS $table_images");
            }
            if ($categories_exists) {
                $wpdb->query("DROP TABLE IF EXISTS $table_categories");
            }
            
            // Recréer les tables
            $this->create_tables();
        } else if (!$updated_at_exists) {
            // Ajouter la colonne updated_at si elle n'existe pas
            $wpdb->query("ALTER TABLE $table_images ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    }
    
    /**
     * Shortcode principal pour afficher la galerie
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts, 'filtered_gallery');
        
        // Récupérer les images
        $images = $this->get_gallery_images($atts);
        
        // Générer le HTML
        ob_start();
        include FILTERED_GALLERY_PLUGIN_PATH . 'templates/gallery-template.php';
        return ob_get_clean();
    }
    
    /**
     * Récupérer les images de la galerie
     */
    private function get_gallery_images($args = array()) {
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $where = array();
        $join = "LEFT JOIN $table_categories ON $table_images.category_id = $table_categories.id";
        
        if (!empty($args['category'])) {
            $where[] = $wpdb->prepare("$table_categories.slug = %s", $args['category']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order_clause = "ORDER BY $table_images.sort_order ASC, $table_images.created_at DESC";
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d", $args['limit']) : '';
        
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
    
    /**
     * Enregistrement des widgets
     */
    public function register_widgets() {
        // Vérifier que la classe existe avant de l'enregistrer
        if (class_exists('FilteredGallery_Widget')) {
            register_widget('FilteredGallery_Widget');
        }
    }
    
    /**
     * Menu d'administration
     */
    public function admin_menu() {
        add_menu_page(
            __('Filtered Gallery', 'filtered-gallery'),
            __('Filtered Gallery', 'filtered-gallery'),
            'manage_options',
            'filtered-gallery',
            array($this, 'admin_page'),
            'dashicons-format-gallery',
            30
        );
        

        
        add_submenu_page(
            'filtered-gallery',
            __('Catégories', 'filtered-gallery'),
            __('Catégories', 'filtered-gallery'),
            'manage_options',
            'filtered-gallery-categories',
            array($this, 'admin_categories_page')
        );
    }
    
    /**
     * Page d'administration principale
     */
    public function admin_page() {
        include FILTERED_GALLERY_PLUGIN_PATH . 'templates/admin/main-page.php';
    }
    

    
    /**
     * Page des catégories
     */
    public function admin_categories_page() {
        include FILTERED_GALLERY_PLUGIN_PATH . 'templates/admin/categories.php';
    }
    
    /**
     * Fonction AJAX pour filtrer la galerie
     */
    public function ajax_filter_gallery() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'filtered_gallery_nonce')) {
            wp_die(__('Erreur de sécurité.', 'filtered-gallery'));
        }
        
        $category = sanitize_text_field($_POST['category']);
        $images = $this->get_gallery_images(array('category' => $category));
        
        wp_send_json_success(array(
            'images' => $images,
            'html' => $this->generate_gallery_html($images)
        ));
    }
    
    /**
     * Générer le HTML de la galerie
     */
    private function generate_gallery_html($images) {
        ob_start();
        include FILTERED_GALLERY_PLUGIN_PATH . 'templates/gallery-items.php';
        return ob_get_clean();
    }
}

// Initialiser le plugin
function filtered_gallery_init() {
    return FilteredGallery::get_instance();
}

// Démarrer le plugin
add_action('plugins_loaded', 'filtered_gallery_init'); 
