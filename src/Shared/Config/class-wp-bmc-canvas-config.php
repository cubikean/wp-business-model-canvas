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
     * @return array Configuration des sections
     */
    public static function get_sections_config() {
        if (self::$sections_config === null) {
            $config_file = WP_BMC_SHARED_DIR . 'Config/canvas-sections.php';
            
            if (file_exists($config_file)) {
                self::$sections_config = include $config_file;
            } else {
                // Configuration par défaut si le fichier n'existe pas
                self::$sections_config = array();
            }
        }
        
        return self::$sections_config;
    }
    
    /**
     * Obtenir la configuration d'une section spécifique
     * 
     * @param string $section_key Clé de la section
     * @return array|null Configuration de la section ou null si non trouvée
     */
    public static function get_section_config($section_key) {
        $config = self::get_sections_config();
        return isset($config[$section_key]) ? $config[$section_key] : null;
    }
    
    /**
     * Obtenir le titre d'une section
     * 
     * @param string $section_key Clé de la section
     * @return string Titre de la section
     */
    public static function get_section_title($section_key) {
        $config = self::get_section_config($section_key);
        return $config ? $config['title'] : ucfirst(str_replace('_', ' ', $section_key));
    }
    
    /**
     * Obtenir le placeholder d'une section
     * 
     * @param string $section_key Clé de la section
     * @return string Placeholder de la section
     */
    public static function get_section_placeholder($section_key) {
        $config = self::get_section_config($section_key);
        return $config ? $config['placeholder'] : 'Saisissez votre contenu...';
    }
    
    /**
     * Vérifier si une section est synthétique
     * 
     * @param string $section_key Clé de la section
     * @return bool True si la section est synthétique
     */
    public static function is_section_synthetic($section_key) {
        $config = self::get_section_config($section_key);
        return $config ? $config['synthetic'] : false;
    }
    
    /**
     * Obtenir toutes les clés des sections
     * 
     * @return array Liste des clés des sections
     */
    public static function get_section_keys() {
        return array_keys(self::get_sections_config());
    }
    
    /**
     * Obtenir les sections synthétiques
     * 
     * @return array Sections marquées comme synthétiques
     */
    public static function get_synthetic_sections() {
        $sections = array();
        $config = self::get_sections_config();
        
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
     * @return array Sections marquées comme non-synthétiques
     */
    public static function get_non_synthetic_sections() {
        $sections = array();
        $config = self::get_sections_config();
        
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
     * @return string Couleur de la section
     */
    public static function get_section_color($section_key) {
        $config = self::get_section_config($section_key);
        return $config && isset($config['color']) ? $config['color'] : 'default';
    }
    
    /**
     * Obtenir les sections par couleur
     * 
     * @param string $color Couleur recherchée
     * @return array Sections ayant cette couleur
     */
    public static function get_sections_by_color($color) {
        $sections = array();
        $config = self::get_sections_config();
        
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
     * @return array Liste des couleurs uniques
     */
    public static function get_available_colors() {
        $colors = array();
        $config = self::get_sections_config();
        
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
     * @return array Configuration complète de la section
     */
    public static function get_section_full_config($section_key) {
        $config = self::get_section_config($section_key);
        
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

}
