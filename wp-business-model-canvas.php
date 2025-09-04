<?php
/**
 * Plugin Name: WP Business Model Canvas
 * Plugin URI: https://example.com/wp-business-model-canvas
 * Description: Plugin WordPress pour construire, suivre et enrichir un Business Model Canvas directement depuis le front-end
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: wp-business-model-canvas
 * Domain Path: /languages
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('WP_BMC_VERSION', '1.0.0');
define('WP_BMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_BMC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_BMC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Inclure les fichiers nécessaires
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-loader.php';
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-auth.php';
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-shortcodes.php';
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-database.php';
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-ajax.php';
require_once WP_BMC_PLUGIN_DIR . 'includes/class-wp-bmc-template-loader.php';

// Initialiser le plugin
function wp_bmc_init() {
    // Initialiser la base de données
    WP_BMC_Database::init();
    
    // Initialiser le chargeur principal
    $plugin = new WP_BMC_Loader();
    $plugin->run();
}
add_action('plugins_loaded', 'wp_bmc_init');

// Charger les scripts admin
function wp_bmc_admin_scripts($hook) {
    if (strpos($hook, 'wp-business-model-canvas') !== false) {
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-bmc-admin', WP_BMC_PLUGIN_URL . 'admin/css/admin.css', array(), WP_BMC_VERSION);
    }
}
add_action('admin_enqueue_scripts', 'wp_bmc_admin_scripts');

// Activation du plugin
register_activation_hook(__FILE__, 'wp_bmc_activate');
function wp_bmc_activate() {
    // Créer les tables de base de données
    WP_BMC_Database::create_tables();
    
    // Créer les tables pour les fichiers et documents
    WP_BMC_Database::create_file_tables();
    
    // Créer les pages nécessaires
    wp_bmc_create_pages();
    
    // Flush les règles de réécriture
    flush_rewrite_rules();
}

// Désactivation du plugin
register_deactivation_hook(__FILE__, 'wp_bmc_deactivate');
function wp_bmc_deactivate() {
    flush_rewrite_rules();
}

// Fonction pour créer les pages nécessaires
function wp_bmc_create_pages() {
    $pages = array(
        'business-model-canvas' => array(
            'title' => 'Business Model Canvas',
            'content' => '[wp_bmc_canvas]'
        ),
        'login' => array(
            'title' => 'Connexion',
            'content' => '[wp_bmc_login]'
        ),
        'register' => array(
            'title' => 'Inscription',
            'content' => '[wp_bmc_register]'
        ),
        'dashboard' => array(
            'title' => 'Tableau de bord',
            'content' => '[wp_bmc_dashboard]'
        )
    );
    
    foreach ($pages as $slug => $page_data) {
        $existing_page = get_page_by_path($slug);
        if (!$existing_page) {
            wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug
            ));
        }
    }
}
