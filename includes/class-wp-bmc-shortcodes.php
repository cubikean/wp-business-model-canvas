<?php
/**
 * Classe des shortcodes pour WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Shortcodes {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_shortcode('wp_bmc_login', array($this, 'login_form'));
        add_shortcode('wp_bmc_register', array($this, 'register_form'));
        add_shortcode('wp_bmc_dashboard', array($this, 'dashboard'));
        add_shortcode('wp_bmc_canvas', array($this, 'canvas'));
    }
    
    /**
     * Formulaire de connexion
     */
    public function login_form() {
        if (WP_BMC_Auth::is_logged_in()) {
            return '<div class="wp-bmc-message success">Vous êtes déjà connecté. <a href="' . home_url('/dashboard/') . '">Aller au tableau de bord</a></div>';
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/login-form');
    }
    
    /**
     * Formulaire d'inscription
     */
    public function register_form() {
        if (WP_BMC_Auth::is_logged_in()) {
            return '<div class="wp-bmc-message success">Vous êtes déjà connecté. <a href="' . home_url('/dashboard/') . '">Aller au tableau de bord</a></div>';
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/register-form');
    }
    
    /**
     * Tableau de bord
     */
    public function dashboard() {
        WP_BMC_Auth::require_login();
        
        return WP_BMC_Template_Loader::get_template_content('public/dashboard');
    }
    
    /**
     * Canvas Business Model
     */
    public function canvas() {
        WP_BMC_Auth::require_login();
        
        // Charger les scripts pour les admins
        if (current_user_can('manage_options')) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('wp-bmc-canvas-admin', WP_BMC_PLUGIN_URL . 'public/js/canvas-admin.js', array('jquery'), WP_BMC_VERSION, true);
            wp_localize_script('wp-bmc-canvas-admin', 'wp_bmc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_bmc_nonce')
            ));
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/canvas');
    }
}

// Initialiser les shortcodes
new WP_BMC_Shortcodes();
