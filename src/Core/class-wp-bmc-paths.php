<?php
/**
 * Configuration des chemins et URLs pour WP Business Model Canvas
 * 
 * Ce fichier centralise la gestion des chemins et URLs
 * pour une meilleure maintenabilité.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de configuration des chemins
 */
class WP_BMC_Paths {
    
    /**
     * Chemins des assets admin
     */
    public static function get_admin_assets() {
        return array(
           
            'js' => array(
                'admin' => WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin.js',
                'admin_dashboard' => WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-dashboard.js',
                'admin_users' => WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-users.js',
            ),
        );
    }
    
    /**
     * Chemins des assets public
     */
    public static function get_public_assets() {
        return array(
            'css' => array(
                'users' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/users.css',
                'public' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/public.css',
                'admin' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/admin.css',
            ),
            'js' => array(
                'public' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/public.js',
                'auth' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/auth.js',
                'dashboard' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/dashboard.js',
            ),
        );
    }
    
    /**
     * Chemins des polices
     */
    public static function get_fonts() {
        return array(
            'css' => WP_BMC_PLUGIN_URL . 'src/Shared/utils/fonts/urbanist.css',
            'path' => WP_BMC_SHARED_DIR . 'utils/fonts/',
            'url' => WP_BMC_PLUGIN_URL . 'src/Shared/utils/fonts/',
            'files' => array(
                'variable' => WP_BMC_PLUGIN_URL . 'src/Shared/utils/fonts/Urbanist-var.woff2',
            ),
        );
    }
    
    /**
     * Chemins des templates
     */
    public static function get_templates() {
        return array(
            'admin' => array(
                'canvas' => WP_BMC_SHARED_DIR . 'Templates/admin/canvas.php',
                'dashboard' => WP_BMC_SHARED_DIR . 'Templates/admin/dashboard.php',
                'edit_section' => WP_BMC_SHARED_DIR . 'Templates/admin/edit-section.php',
            ),
            'public' => array(
                'canvas' => WP_BMC_SHARED_DIR . 'Templates/public/canvas.php',
                'dashboard' => WP_BMC_SHARED_DIR . 'Templates/public/dashboard.php',
                'edit_section' => WP_BMC_SHARED_DIR . 'Templates/public/edit-section.php',
                'login_form' => WP_BMC_SHARED_DIR . 'Templates/public/login-form.php',
                'register_form' => WP_BMC_SHARED_DIR . 'Templates/public/register-form.php',
            ),
        );
    }
    
    /**
     * Chemins des classes
     */
    public static function get_classes() {
        return array(
            'core' => array(
                'database' => WP_BMC_CORE_DIR . 'Database/class-wp-bmc-database.php',
                'auth' => WP_BMC_CORE_DIR . 'Auth/class-wp-bmc-auth.php',
                'ajax' => WP_BMC_CORE_DIR . 'Ajax/class-wp-bmc-ajax.php',
                'shortcodes' => WP_BMC_CORE_DIR . 'Shortcodes/class-wp-bmc-shortcodes.php',
                'loader' => WP_BMC_CORE_DIR . 'class-wp-bmc-loader.php',
                'template_loader' => WP_BMC_CORE_DIR . 'class-wp-bmc-template-loader.php',
            ),
            'public' => array(
                'canvas' => WP_BMC_PUBLIC_DIR . 'Controllers/canvas.php',
                'dashboard' => WP_BMC_PUBLIC_DIR . 'Controllers/dashboard.php',
            ),
        );
    }
    
    /**
     * URLs des pages
     */
    public static function get_page_urls() {
        return array(
            'canvas' => home_url('/business-model-canvas/'),
            'dashboard' => home_url('/dashboard/'),
            'login' => home_url('/login/'),
            'logout' => home_url('/logout/'),
        );
    }
    
    /**
     * Vérifier si un fichier existe
     * 
     * @param string $file_path Chemin du fichier
     * @return bool True si le fichier existe
     */
    public static function file_exists($file_path) {
        return file_exists($file_path);
    }
    
    /**
     * Obtenir le chemin relatif depuis la racine du plugin
     * 
     * @param string $file_path Chemin complet du fichier
     * @return string Chemin relatif
     */
    public static function get_relative_path($file_path) {
        return str_replace(WP_BMC_PLUGIN_DIR, '', $file_path);
    }
    
    /**
     * Obtenir l'URL d'un fichier
     * 
     * @param string $file_path Chemin du fichier
     * @return string URL du fichier
     */
    public static function get_file_url($file_path) {
        $relative_path = self::get_relative_path($file_path);
        return WP_BMC_PLUGIN_URL . $relative_path;
    }
}
