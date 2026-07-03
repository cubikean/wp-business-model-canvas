<?php
/**
 * Configuration principale du plugin WP Business Model Canvas
 * 
 * Ce fichier centralise toutes les configurations du plugin
 * pour une meilleure gestion et maintenance.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de configuration du plugin
 */
class WP_BMC_Config {
    
    /**
     * Configuration par défaut
     */
    private static $default_config = array(
        'version' => '2.0.0',
        'min_wp_version' => '5.0',
        'min_php_version' => '7.4',
        'text_domain' => 'wp-business-model-canvas',
        'capability' => 'manage_options',
        'menu_slug' => 'wp-business-model-canvas',
        'menu_title' => 'BMC',
        'page_title' => 'WP Business Model Canvas',
        'menu_icon' => 'dashicons-chart-area',
        'menu_position' => 30,
        'auto_save_interval' => 30, // secondes
        'max_file_size' => 10485760, // 10MB
        'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'),
        'canvas_sections' => array(
            'partenaires_cles',
            'activites_cles',
            'proposition_valeur',
            'relations_clients',
            'segments_clients',
            'ressources_cles',
            'canaux',
            'structure_couts',
            'flux_revenus'
        ),
        'features' => array(
            'auto_save' => true,
            'file_upload' => true,
            'pdf_export' => true,
            'user_management' => true,
            'statistics' => true,
            'custom_fields' => false,
        ),
    );
    
    /**
     * Configuration actuelle
     */
    private static $config = null;
    
    /**
     * Initialiser la configuration
     */
    public static function init() {
        if (self::$config === null) {
            self::$config = wp_parse_args(
                get_option('wp_bmc_config', array()),
                self::$default_config
            );
        }
    }
    
    /**
     * Obtenir une valeur de configuration
     * 
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur de configuration
     */
    public static function get($key, $default = null) {
        self::init();
        
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        return $default;
    }
    
    /**
     * Définir une valeur de configuration
     * 
     * @param string $key Clé de configuration
     * @param mixed $value Valeur à définir
     */
    public static function set($key, $value) {
        self::init();
        
        self::$config[$key] = $value;
        update_option('wp_bmc_config', self::$config);
    }
    
    /**
     * Obtenir toute la configuration
     * 
     * @return array Configuration complète
     */
    public static function get_all() {
        self::init();
        return self::$config;
    }
    
    /**
     * Réinitialiser la configuration
     */
    public static function reset() {
        self::$config = self::$default_config;
        update_option('wp_bmc_config', self::$config);
    }
    
    /**
     * Vérifier les prérequis
     * 
     * @return array Résultat de la vérification
     */
    public static function check_requirements() {
        $errors = array();
        $warnings = array();
        
        // Vérifier la version WordPress
        if (version_compare(get_bloginfo('version'), self::get('min_wp_version'), '<')) {
            $errors[] = sprintf(
                'WordPress %s ou supérieur requis. Version actuelle : %s',
                self::get('min_wp_version'),
                get_bloginfo('version')
            );
        }
        
        // Vérifier la version PHP
        if (version_compare(PHP_VERSION, self::get('min_php_version'), '<')) {
            $errors[] = sprintf(
                'PHP %s ou supérieur requis. Version actuelle : %s',
                self::get('min_php_version'),
                PHP_VERSION
            );
        }
        
        // Vérifier les extensions PHP
        $required_extensions = array('mysqli', 'json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $errors[] = sprintf('Extension PHP %s requise', $extension);
            }
        }
        
        // Vérifier les permissions
        $upload_dir = wp_upload_dir();
        if (!is_writable($upload_dir['basedir'])) {
            $warnings[] = 'Le dossier d\'upload n\'est pas accessible en écriture';
        }
        
        return array(
            'errors' => $errors,
            'warnings' => $warnings,
            'valid' => empty($errors),
        );
    }
    
    /**
     * Obtenir les sections du canvas
     * 
     * @return array Sections du canvas
     */
    public static function get_canvas_sections() {
        return self::get('canvas_sections', array());
    }
    
    /**
     * Obtenir les fonctionnalités activées
     * 
     * @return array Fonctionnalités
     */
    public static function get_features() {
        return self::get('features', array());
    }
    
    /**
     * Vérifier si une fonctionnalité est activée
     * 
     * @param string $feature Nom de la fonctionnalité
     * @return bool True si activée
     */
    public static function is_feature_enabled($feature) {
        $features = self::get_features();
        return isset($features[$feature]) && $features[$feature];
    }
    
    /**
     * Obtenir les types de fichiers autorisés
     * 
     * @return array Types de fichiers
     */
    public static function get_allowed_file_types() {
        return self::get('allowed_file_types', array());
    }
    
    /**
     * Obtenir la taille maximale des fichiers
     * 
     * @return int Taille en octets
     */
    public static function get_max_file_size() {
        return self::get('max_file_size', 10485760);
    }
    
    /**
     * Obtenir l'intervalle de sauvegarde automatique
     * 
     * @return int Intervalle en secondes
     */
    public static function get_auto_save_interval() {
        return self::get('auto_save_interval', 30);
    }
    
    /**
     * Obtenir les URLs des pages
     * 
     * @return array URLs des pages
     */
    public static function get_page_urls() {
        return array(
            'canvas' => home_url('/business-model-canvas/'),
            'dashboard' => home_url('/dashboard/'),
            'login' => home_url('/login/'),
            'register' => home_url('/register/'),
        );
    }
    
    /**
     * Obtenir les chemins des assets
     * 
     * @return array Chemins des assets
     */
    public static function get_asset_paths() {
        return array(
            'admin_css' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/admin.css',
            'admin_js' => WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/',
            'public_css' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/',
            'public_js' => WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/',
        );
    }
}
