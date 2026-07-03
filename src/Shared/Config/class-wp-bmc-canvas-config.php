<?php
/**
 * Classe de gestion de la configuration du canvas
 * 
 * Cette classe fournit des méthodes pour accéder à la configuration
 * des sections du Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Canvas_Config {
    
    /**
     * Cache pour la configuration des sections
     */
    private static $sections_config = null;
    
    /**
     * Charger la configuration des sections du canvas
     * 
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Configuration des sections
     */
    public static function get_sections_config($view_mode = 'global') {
        $cache_key = $view_mode;
        
        if (self::$sections_config === null) {
            self::$sections_config = array();
        }
        
        if (!isset(self::$sections_config[$cache_key])) {
            $config_file = WP_BMC_SHARED_DIR . 'Config/canvas-sections.php';
            
            if (file_exists($config_file)) {
                // Inclure le fichier qui définit la fonction wp_bmc_get_canvas_sections
                include_once $config_file;
                
                // Appeler la fonction avec le mode de vue approprié
                if (function_exists('wp_bmc_get_canvas_sections')) {
                    self::$sections_config[$cache_key] = wp_bmc_get_canvas_sections($view_mode);
                } else {
                    self::$sections_config[$cache_key] = array();
                }
            } else {
                // Configuration par défaut si le fichier n'existe pas
                self::$sections_config[$cache_key] = array();
            }
        }
        
        return self::$sections_config[$cache_key];
    }
    
    /**
     * Obtenir la configuration d'une section spécifique
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array|null Configuration de la section ou null si non trouvée
     */
    public static function get_section_config($section_key, $view_mode = 'global') {
        $config = self::get_sections_config($view_mode);
        return isset($config[$section_key]) ? $config[$section_key] : null;
    }
    
    /**
     * Obtenir le titre d'une section
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return string Titre de la section
     */
    public static function get_section_title($section_key, $view_mode = 'global') {
        $config = self::get_section_config($section_key, $view_mode);
        return $config ? $config['title'] : ucfirst(str_replace('_', ' ', $section_key));
    }
    
    /**
     * Obtenir le placeholder d'une section
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return string Placeholder de la section
     */
    public static function get_section_placeholder($section_key, $view_mode = 'global') {
        $config = self::get_section_config($section_key, $view_mode);
        return $config ? $config['placeholder'] : 'Saisissez votre contenu...';
    }
    
    /**
     * Vérifier si une section est synthétique
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return bool True si la section est synthétique
     */
    public static function is_section_synthetic($section_key, $view_mode = 'global') {
        $config = self::get_section_config($section_key, $view_mode);
        return $config ? $config['synthetic'] : false;
    }
    
    /**
     * Obtenir toutes les clés des sections
     * 
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Liste des clés des sections
     */
    public static function get_section_keys($view_mode = 'global') {
        return array_keys(self::get_sections_config($view_mode));
    }
    
    /**
     * Obtenir les sections synthétiques
     * 
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Sections marquées comme synthétiques
     */
    public static function get_synthetic_sections($view_mode = 'global') {
        $sections = array();
        $config = self::get_sections_config($view_mode);
        
        foreach ($config as $key => $section_config) {
            if ($section_config['synthetic']) {
                $sections[] = $key;
            }
        }
        
        return $sections;
    }
    
    /**
     * Obtenir les sections non-synthétiques
     * 
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Sections marquées comme non-synthétiques
     */
    public static function get_non_synthetic_sections($view_mode = 'global') {
        $sections = array();
        $config = self::get_sections_config($view_mode);
        
        foreach ($config as $key => $section_config) {
            if (!$section_config['synthetic']) {
                $sections[] = $key;
            }
        }
        
        return $sections;
    }
    
    /**
     * Obtenir la couleur d'une section
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return string Couleur de la section
     */
    public static function get_section_color($section_key, $view_mode = 'global') {
        $config = self::get_section_config($section_key, $view_mode);
        return $config && isset($config['color']) ? $config['color'] : 'default';
    }
    
    /**
     * Obtenir les sections par couleur
     * 
     * @param string $color Couleur recherchée
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Sections ayant cette couleur
     */
    public static function get_sections_by_color($color, $view_mode = 'global') {
        $sections = array();
        $config = self::get_sections_config($view_mode);
        
        foreach ($config as $key => $section_config) {
            if (isset($section_config['color']) && $section_config['color'] === $color) {
                $sections[] = $key;
            }
        }
        
        return $sections;
    }
    
    /**
     * Obtenir toutes les couleurs disponibles
     * 
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Liste des couleurs uniques
     */
    public static function get_available_colors($view_mode = 'global') {
        $colors = array();
        $config = self::get_sections_config($view_mode);
        
        foreach ($config as $section_config) {
            if (isset($section_config['color'])) {
                $colors[] = $section_config['color'];
            }
        }
        
        return array_unique($colors);
    }
    
    /**
     * Obtenir la configuration complète d'une section avec toutes ses propriétés
     * 
     * @param string $section_key Clé de la section
     * @param string $view_mode Mode de vue ('synthetic' ou 'global')
     * @return array Configuration complète de la section
     */
    public static function get_section_full_config($section_key, $view_mode = 'global') {
        $config = self::get_section_config($section_key, $view_mode);
        
        if (!$config) {
            return array(
                'title' => ucfirst(str_replace('_', ' ', $section_key)),
                'placeholder' => 'Saisissez votre contenu...',
                'synthetic' => false,
                'color' => 'default'
            );
        }
        
        // S'assurer que toutes les propriétés sont présentes
        return array_merge(array(
            'title' => '',
            'placeholder' => '',
            'synthetic' => false,
            'color' => 'default'
        ), $config);
    }
    
    /**
     * Vider le cache des configurations
     */
    public static function clear_cache() {
        self::$sections_config = null;
    }

}
