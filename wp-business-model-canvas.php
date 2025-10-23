<?php
/**
 * Plugin Name: WP Business Model Canvas
 * Plugin URI: https://example.com/wp-business-model-canvas
 * Description: Plugin WordPress pour construire, suivre et enrichir un Business Model Canvas directement depuis le front-end
 * Version: 2.0.0
 * Author: Votre Nom
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: wp-business-model-canvas
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('WP_BMC_VERSION', '2.0.0');
define('WP_BMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_BMC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_BMC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Définir les constantes pour les chemins
define('WP_BMC_SRC_DIR', WP_BMC_PLUGIN_DIR . 'src/');
define('WP_BMC_ADMIN_DIR', WP_BMC_SRC_DIR . 'Admin/');
define('WP_BMC_PUBLIC_DIR', WP_BMC_SRC_DIR . 'Public/');
define('WP_BMC_CORE_DIR', WP_BMC_SRC_DIR . 'Core/');
define('WP_BMC_SHARED_DIR', WP_BMC_SRC_DIR . 'Shared/');
define('WP_BMC_ASSETS_DIR', WP_BMC_PLUGIN_DIR . 'assets/');

// Charger l'autoloader personnalisé
require_once WP_BMC_CORE_DIR . 'autoloader.php';

// Inclure directement les classes essentielles pour éviter les erreurs
require_once WP_BMC_CORE_DIR . 'Database/class-wp-bmc-database.php';
require_once WP_BMC_CORE_DIR . 'Auth/class-wp-bmc-auth.php';
require_once WP_BMC_CORE_DIR . 'Shortcodes/class-wp-bmc-shortcodes.php';
require_once WP_BMC_CORE_DIR . 'Ajax/class-wp-bmc-ajax.php';
require_once WP_BMC_CORE_DIR . 'class-wp-bmc-paths.php';
require_once WP_BMC_CORE_DIR . 'class-wp-bmc-loader.php';
require_once WP_BMC_CORE_DIR . 'class-wp-bmc-template-loader.php';
require_once WP_BMC_SHARED_DIR . 'Config/class-wp-bmc-canvas-config.php';
require_once WP_BMC_SHARED_DIR . 'Functions/canvas-functions.php';
require_once WP_BMC_SHARED_DIR . 'Utils/email-templates.php';

// Initialiser le plugin
function wp_bmc_init() {
    // Initialiser la base de données
    WP_BMC_Database::init();
    
    // Initialiser le chargeur principal
    $plugin = new WP_BMC_Loader();
    $plugin->run();
    
    // Hook pour synchroniser la suppression d'utilisateurs WordPress avec la table BMC
    add_action('delete_user', array('WP_BMC_Database', 'cleanup_on_wp_user_delete'), 10, 1);
}
add_action('plugins_loaded', 'wp_bmc_init');

// Charger les scripts admin
function wp_bmc_admin_scripts($hook) {
    if (strpos($hook, 'wp-business-model-canvas') !== false) {
        wp_enqueue_script('jquery');
        
        // Charger les polices en premier
        wp_enqueue_style('wp-bmc-fonts', WP_BMC_PLUGIN_URL . 'src/Shared/utils/fonts/urbanist.css', array(), WP_BMC_VERSION);
        
        // Charger Font Awesome (fichiers locaux)
        wp_enqueue_style('font-awesome', WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/font-awesome.min.css', array(), '6.0.0');
        
        // Charger le système de Toast
        wp_enqueue_style('wp-bmc-toast', WP_BMC_PLUGIN_URL . 'src/Shared/Assets/css/wp-bmc-toast.css', array(), WP_BMC_VERSION);
        wp_enqueue_script('wp-bmc-toast', WP_BMC_PLUGIN_URL . 'src/Shared/Assets/js/wp-bmc-toast.js', array(), WP_BMC_VERSION, true);
        
        // Charger les styles admin avec dépendance sur les polices
        wp_enqueue_style('wp-bmc-admin', WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/admin.css', array('wp-bmc-fonts', 'font-awesome'), WP_BMC_VERSION);
        
        wp_enqueue_script('wp-bmc-admin-dashboard', WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-dashboard.js', array('jquery', 'wp-bmc-toast'), WP_BMC_VERSION, true);
        
        // Localiser les variables AJAX
        wp_localize_script('wp-bmc-admin-dashboard', 'wp_bmc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
        ));
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

// Mise à jour du plugin
add_action('admin_init', 'wp_bmc_check_update');
function wp_bmc_check_update() {
    $current_version = get_option('wp_bmc_version', '0.0.0');
    
    if (version_compare($current_version, WP_BMC_VERSION, '<')) {
        // Mettre à jour les tables de base de données
        WP_BMC_Database::create_tables();
        
        // Mettre à jour la version
        update_option('wp_bmc_version', WP_BMC_VERSION);
    }
}

// Désactivation du plugin
register_deactivation_hook(__FILE__, 'wp_bmc_deactivate');
function wp_bmc_deactivate() {
    flush_rewrite_rules();
    
    // Désactiver le cron de nettoyage des sessions
    $timestamp = wp_next_scheduled('wp_bmc_cleanup_sessions');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wp_bmc_cleanup_sessions');
    }
}

// ========================================
// CRON JOB - NETTOYAGE DES SESSIONS
// ========================================

// Planifier le nettoyage des sessions toutes les 2 minutes
add_action('wp', 'wp_bmc_schedule_session_cleanup');
function wp_bmc_schedule_session_cleanup() {
    if (!wp_next_scheduled('wp_bmc_cleanup_sessions')) {
        wp_schedule_event(time(), 'every_two_minutes', 'wp_bmc_cleanup_sessions');
    }
}

// Définir un intervalle personnalisé pour le cron
add_filter('cron_schedules', 'wp_bmc_add_cron_intervals');
function wp_bmc_add_cron_intervals($schedules) {
    $schedules['every_two_minutes'] = array(
        'interval' => 120, // 2 minutes
        'display' => __('Toutes les 2 minutes')
    );
    return $schedules;
}

// Action de nettoyage des sessions
add_action('wp_bmc_cleanup_sessions', array('WP_BMC_Database', 'cleanup_inactive_sessions'));

// Fonction utilitaire pour inclure le template d'édition
function wp_bmc_include_edit_section($context = 'public', $section = '') {
    $template_path = WP_BMC_SHARED_DIR . 'Templates/' . $context . '/edit-section.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback vers le template public si le template spécifique n'existe pas
        $fallback_path = WP_BMC_SHARED_DIR . 'Templates/public/edit-section.php';
        if (file_exists($fallback_path)) {
            include $fallback_path;
        }
    }
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

// Charger les scripts publics
function wp_bmc_public_scripts() {
    // Charger jQuery et Font Awesome (toujours nécessaires pour le menu utilisateur)
    wp_enqueue_script('jquery');
    wp_enqueue_style('font-awesome', WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/font-awesome.min.css', array(), '6.0.0');
    
    // Charger le système de Toast (nécessaire pour le menu utilisateur)
    wp_enqueue_style('wp-bmc-toast', WP_BMC_PLUGIN_URL . 'src/Shared/Assets/css/wp-bmc-toast.css', array(), WP_BMC_VERSION);
    wp_enqueue_script('wp-bmc-toast', WP_BMC_PLUGIN_URL . 'src/Shared/Assets/js/wp-bmc-toast.js', array('jquery'), WP_BMC_VERSION, true);
    
    // Charger les styles et scripts du menu utilisateur shortcode (toujours chargés)
    wp_enqueue_style('wp-bmc-user-menu-shortcode', WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/wp-bmc-user-menu-shortcode.css', array('font-awesome'), WP_BMC_VERSION);
    wp_enqueue_script('wp-bmc-user-menu-shortcode', WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/wp-bmc-user-menu-shortcode.js', array('jquery', 'wp-bmc-toast'), WP_BMC_VERSION, true);
    
    // Charger les autres assets seulement si l'utilisateur est connecté
    if (!WP_BMC_Auth::is_logged_in()) {
        return;
    }
    
    // Charger les styles publics
    wp_enqueue_style('wp-bmc-public', WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/public.css', array('font-awesome'), WP_BMC_VERSION);
    
    // Charger les scripts publics
    wp_enqueue_script('wp-bmc-public', WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/public.js', array('jquery', 'wp-bmc-toast'), WP_BMC_VERSION, true);
    wp_enqueue_script('wp-bmc-dashboard', WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/dashboard.js', array('jquery', 'wp-bmc-toast'), WP_BMC_VERSION, true);
    wp_enqueue_script('wp-bmc-auth', WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/auth.js', array('jquery', 'wp-bmc-toast'), WP_BMC_VERSION, true);
    
    // Localiser les variables AJAX
    wp_localize_script('wp-bmc-public', 'wp_bmc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_bmc_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'wp_bmc_public_scripts');

// Ajouter la classe 'bmc-main' au body sur les pages BMC
add_filter('body_class', 'wp_bmc_add_body_class');
function wp_bmc_add_body_class($classes) {
    global $post;
    
    // Vérifier si nous sommes sur une page avec contenu
    if (!is_a($post, 'WP_Post')) {
        return $classes;
    }
    
    // Liste des shortcodes BMC à détecter
    $bmc_shortcodes = array(
        'wp_bmc_login',
        'wp_bmc_dashboard',
        'wp_bmc_canvas',
        'wp_bmc_change_password',
        'wp_bmc_user_menu'
    );
    
    // Vérifier si la page contient l'un des shortcodes BMC
    foreach ($bmc_shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            $classes[] = 'bmc-main '.$shortcode;
            break;
        }
    }
    
    return $classes;
}


add_filter('body_class', 'wp_bmc_add_body_class_logged');
function wp_bmc_add_body_class_logged($classes){
    if(WP_BMC_Auth::is_logged_in()){
        $classes[] = 'bmc-logged-in';
    }
    return $classes;
}

// Flusher les règles de réécriture lors de l'activation du plugin
register_activation_hook(__FILE__, 'wp_bmc_flush_rewrite_rules');
function wp_bmc_flush_rewrite_rules() {
    // Enregistrer les règles de réécriture
    add_rewrite_rule('^logout/?$', 'index.php?wp_bmc_route=logout', 'top');
    add_rewrite_tag('%wp_bmc_route%', '([^&]+)');
    
    // Flusher les règles
    flush_rewrite_rules();
}
