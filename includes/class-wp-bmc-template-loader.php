<?php
/**
 * Classe helper pour charger les templates
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Template_Loader {
    
    /**
     * Charge un template
     */
    public static function load_template($template_name, $args = array()) {
        $template_path = WP_BMC_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            // Extraire les variables pour les rendre disponibles dans le template
            if (!empty($args)) {
                extract($args);
            }
            
            include $template_path;
        } else {
            error_log('Template non trouvé : ' . $template_path);
        }
    }
    
    /**
     * Retourne le contenu d'un template
     */
    public static function get_template_content($template_name, $args = array()) {
        ob_start();
        self::load_template($template_name, $args);
        return ob_get_clean();
    }
}
