<?php
/**
 * Configuration de l'autoloader pour WP Business Model Canvas
 * 
 * Ce fichier définit les règles de chargement automatique des classes
 * selon la nouvelle structure organisée du plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader personnalisé pour WP Business Model Canvas
 * 
 * @param string $class_name Nom de la classe à charger
 */
function wp_bmc_autoloader($class_name) {
    // Préfixe des classes du plugin
    $prefix = 'WP_BMC_';
    
    // Vérifier si c'est une classe du plugin
    $len = strlen($prefix);
    if (strncmp($prefix, $class_name, $len) !== 0) {
        return;
    }
    
    // Récupérer le nom de classe relatif
    $relative_class = substr($class_name, $len);
    
    // Convertir les underscores en slashes pour le chemin
    $file_path = str_replace('_', '/', $relative_class);
    
    // Chemins de base pour les différents modules
    $base_paths = array(
        WP_BMC_SRC_DIR . 'Core/',
        WP_BMC_SRC_DIR . 'Admin/',
        WP_BMC_SRC_DIR . 'Public/',
        WP_BMC_SRC_DIR . 'Shared/',
    );
    
    // Essayer de charger depuis chaque chemin de base
    foreach ($base_paths as $base_path) {
        $file = $base_path . $file_path . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Si pas trouvé, essayer avec le préfixe 'class-' pour les fichiers WordPress
    foreach ($base_paths as $base_path) {
        $file = $base_path . 'class-' . strtolower(str_replace('_', '-', $file_path)) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Essayer de charger depuis les sous-dossiers spécialisés
    $specialized_paths = array(
        'Database' => WP_BMC_SRC_DIR . 'Core/Database/',
        'Auth' => WP_BMC_SRC_DIR . 'Core/Auth/',
        'Ajax' => WP_BMC_SRC_DIR . 'Core/Ajax/',
        'Shortcodes' => WP_BMC_SRC_DIR . 'Core/Shortcodes/',
    );
    
    foreach ($specialized_paths as $folder => $path) {
        $file = $path . 'class-' . strtolower(str_replace('_', '-', $file_path)) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

/**
 * Enregistrer l'autoloader
 */
spl_autoload_register('wp_bmc_autoloader');

/**
 * Fonction utilitaire pour charger une classe spécifique
 * 
 * @param string $class_name Nom de la classe
 * @return bool True si la classe a été chargée
 */
function wp_bmc_load_class($class_name) {
    if (class_exists($class_name)) {
        return true;
    }
    
    wp_bmc_autoloader($class_name);
    return class_exists($class_name);
}

/**
 * Fonction utilitaire pour charger tous les fichiers d'un dossier
 * 
 * @param string $directory Chemin du dossier
 * @param string $pattern Pattern de fichiers (optionnel)
 */
function wp_bmc_load_directory($directory, $pattern = '*.php') {
    if (!is_dir($directory)) {
        return;
    }
    
    $files = glob($directory . $pattern);
    
    foreach ($files as $file) {
        if (is_file($file)) {
            require_once $file;
        }
    }
}

/**
 * Charger les classes essentielles au démarrage
 */
function wp_bmc_load_essential_classes() {
    $essential_classes = array(
        'WP_BMC_Database',
        'WP_BMC_Auth',
        'WP_BMC_Shortcodes',
        'WP_BMC_Ajax',
        'WP_BMC_Loader',
        'WP_BMC_Template_Loader',
        'WP_BMC_Config',
        'WP_BMC_Paths',
        'WP_BMC_Complete_Check',
        'WP_BMC_Database_Check',
        'WP_BMC_Hooks_Check',
        'WP_BMC_Canvas_Config',
    );
    
    foreach ($essential_classes as $class) {
        wp_bmc_load_class($class);
    }
}

/**
 * Charger les classes admin
 */
function wp_bmc_load_admin_classes() {
    if (!is_admin()) {
        return;
    }
    
    $admin_classes = array(
        'WP_BMC_Admin_Dashboard',
        'WP_BMC_Admin_Users',
        'WP_BMC_Admin_Settings',
    );
    
    foreach ($admin_classes as $class) {
        wp_bmc_load_class($class);
    }
}

/**
 * Charger les classes public
 */
function wp_bmc_load_public_classes() {
    if (is_admin()) {
        return;
    }
    
    $public_classes = array(
        'WP_BMC_Public_Canvas',
        'WP_BMC_Public_Dashboard',
        'WP_BMC_Public_Auth',
    );
    
    foreach ($public_classes as $class) {
        wp_bmc_load_class($class);
    }
}

// Charger les classes essentielles immédiatement
wp_bmc_load_essential_classes();

// Charger les classes selon le contexte (seulement si WordPress est chargé)
if (function_exists('add_action')) {
    add_action('admin_init', 'wp_bmc_load_admin_classes');
    add_action('wp', 'wp_bmc_load_public_classes');
}
