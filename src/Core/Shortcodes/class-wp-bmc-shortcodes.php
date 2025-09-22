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
            return '<div class="wp-bmc-message success wp-bmc-login-form">Vous êtes déjà connecté. <a href="' . home_url('/dashboard/') . '">Aller au tableau de bord</a></div>';
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/login-form');
    }
    
    /**
     * Formulaire d'inscription
     */
    public function register_form() {
        if (WP_BMC_Auth::is_logged_in()) {
            return '<div class="wp-bmc-message success wp-bmc-login-form">Vous êtes déjà connecté. <a href="' . home_url('/dashboard/') . '">Aller au tableau de bord</a></div>';
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/register-form');
    }
    
    /**
     * Tableau de bord
     */
    public function dashboard() {
        WP_BMC_Auth::require_login();
        
        // Si c'est un administrateur WordPress, utiliser le template admin
        if (current_user_can('manage_options')) {
            return WP_BMC_Template_Loader::get_template_content('admin/dashboard');
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/dashboard');
    }
    
    /**
     * Canvas Business Model
     */
    public function canvas() {
        WP_BMC_Auth::require_login();
        
        // Vérifier si c'est une vue admin
        $admin_view = isset($_GET['admin_view']) && $_GET['admin_view'] === 'true';
        
        // Si c'est une vue admin et que l'utilisateur est admin, utiliser le template admin
        if ($admin_view && current_user_can('manage_options')) {
            return WP_BMC_Template_Loader::get_template_content('admin/canvas');
        }
        
        // Sinon, rediriger vers le dashboard avec les paramètres appropriés
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'global';
        
        if ($project_id) {
            $redirect_url = add_query_arg(array(
                'project_id' => $project_id,
                'view' => $view
            ), home_url('/dashboard/'));
        } else {
            $redirect_url = add_query_arg('view', $view, home_url('/dashboard/'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Initialiser les shortcodes
new WP_BMC_Shortcodes();
