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
        add_shortcode('wp_bmc_change_password', array($this, 'change_password'));
        add_shortcode('wp_bmc_user_menu', array($this, 'user_menu'));
    }
    
    /**
     * Formulaire de connexion
     */
    public function login_form() {
        if (WP_BMC_Auth::is_logged_in()) {
            return '<script>window.location.href = "' . home_url('/dashboard/') . '";</script>';
        }
        
        return WP_BMC_Template_Loader::get_template_content('public/login-form');
    }
    
    /**
     * Formulaire d'inscription
     */
    public function register_form() {
        if (WP_BMC_Auth::is_logged_in()) {
                return '<script>window.location.href = "' . home_url('/dashboard/') . '";</script>';
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
    
    /**
     * Menu utilisateur pour intégration dans le thème
     */
    public function user_menu($atts) {
        // Paramètres par défaut
        $atts = shortcode_atts(array(
            'style' => 'default', // default, compact, minimal
            'position' => 'right', // left, right, center
            'show_name' => 'true', // true, false
            'show_email' => 'true', // true, false
            'size' => 'medium' // small, medium, large
        ), $atts);
        
        // Si l'utilisateur n'est pas connecté, ne rien afficher
        if (!WP_BMC_Auth::is_logged_in()) {
            return '';
        }
        
        $user_menu_data = WP_BMC_Auth::get_user_menu_data();
        if (!$user_menu_data) {
            return '';
        }
        
        // Générer un ID unique pour éviter les conflits
        $unique_id = 'wp-bmc-user-menu-' . uniqid();
        
        // Classes CSS selon les paramètres
        $classes = array('wp-bmc-user-menu-shortcode');
        $classes[] = 'wp-bmc-style-' . sanitize_html_class($atts['style']);
        $classes[] = 'wp-bmc-position-' . sanitize_html_class($atts['position']);
        $classes[] = 'wp-bmc-size-' . sanitize_html_class($atts['size']);
        
        if ($atts['show_name'] === 'false') {
            $classes[] = 'wp-bmc-hide-name';
        }
        if ($atts['show_email'] === 'false') {
            $classes[] = 'wp-bmc-hide-email';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" id="<?php echo esc_attr($unique_id); ?>">
            <div class="wp-bmc-user-avatar">
                <span class="wp-bmc-user-initials"><?php echo esc_html($user_menu_data['initials']); ?></span>
            </div>
            
            <!-- Menu déroulant -->
            <div class="wp-bmc-user-dropdown">
                <div class="wp-bmc-user-dropdown-header">
                    <div class="wp-bmc-user-dropdown-avatar">
                        <span class="wp-bmc-user-dropdown-initials"><?php echo esc_html($user_menu_data['initials']); ?></span>
                    </div>
                    <div class="wp-bmc-user-dropdown-info">
                        <?php if ($atts['show_name'] !== 'false'): ?>
                        <div class="wp-bmc-user-dropdown-name"><?php echo esc_html($user_menu_data['full_name']); ?></div>
                        <?php endif; ?>
                        <?php if ($atts['show_email'] !== 'false'): ?>
                        <div class="wp-bmc-user-dropdown-email"><?php echo esc_html($user_menu_data['email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="wp-bmc-user-dropdown-separator"></div>
                
                <div class="wp-bmc-user-dropdown-actions">
                    <button class="wp-bmc-user-dropdown-action" id="<?php echo esc_attr($unique_id); ?>-change-password">
                        <i class="fas fa-plus"></i>
                        <span>Changer de mot de passe</span>
                    </button>
                    <button class="wp-bmc-user-dropdown-action" id="<?php echo esc_attr($unique_id); ?>-logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Se déconnecter</span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}

// Initialiser les shortcodes
new WP_BMC_Shortcodes();
