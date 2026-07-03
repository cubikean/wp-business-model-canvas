<?php
/**
 * Gestionnaire des polices pour WP Business Model Canvas
 * 
 * Ce fichier gère le chargement et l'enregistrement des polices personnalisées
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de gestion des polices
 */
class WP_BMC_Fonts {
    
    /**
     * Chemin vers le dossier des polices
     */
    private static $fonts_path;
    
    /**
     * URL vers le dossier des polices
     */
    private static $fonts_url;
    
    /**
     * Initialiser les chemins des polices
     */
    public static function init() {
        self::$fonts_path = WP_BMC_SHARED_DIR . 'utils/fonts/';
        self::$fonts_url = WP_BMC_PLUGIN_URL . 'src/Shared/utils/fonts/';
    }
    
    /**
     * Enregistrer les styles de polices
     */
    public static function enqueue_fonts() {
        // Enregistrer le CSS des polices
        wp_enqueue_style(
            'wp-bmc-fonts',
            self::$fonts_url . 'urbanist.css',
            array(),
            WP_BMC_VERSION
        );
    }
    
    /**
     * Vérifier si les fichiers de polices existent
     */
    public static function check_font_files() {
        $required_fonts = array(
            'Urbanist-var.woff2',
        );
        
        $missing_fonts = array();
        
        foreach ($required_fonts as $font_file) {
            if (!file_exists(self::$fonts_path . $font_file)) {
                $missing_fonts[] = $font_file;
            }
        }
        
        return $missing_fonts;
    }
    
    /**
     * Obtenir la liste des polices disponibles
     */
    public static function get_available_fonts() {
        $fonts = array();
        
        if (is_dir(self::$fonts_path)) {
            $files = scandir(self::$fonts_path);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'woff2') {
                    $fonts[] = $file;
                }
            }
        }
        
        return $fonts;
    }
    
    /**
     * Générer les URLs des polices pour le CSS
     */
    public static function get_font_urls() {
        return array(
            'base_url' => self::$fonts_url,
            'fonts' => array(
                'regular' => self::$fonts_url . 'Urbanist-var.woff2',
            )
        );
    }
    
    /**
     * Créer les fichiers de polices par défaut (fallback)
     */
    public static function create_fallback_fonts() {
        // Cette méthode peut être utilisée pour créer des polices de fallback
        // ou pour télécharger automatiquement les polices depuis Google Fonts
        
        $fallback_css = '
        /* Fallback vers Google Fonts si les fichiers locaux ne sont pas disponibles */
        @import url("https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap");
        ';
        
        file_put_contents(self::$fonts_path . 'fallback.css', $fallback_css);
    }
    
    /**
     * Obtenir les informations de configuration des polices
     */
    public static function get_font_config() {
        return array(
            'primary_font' => 'Urbanist',
            'fallback_fonts' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif',
            'weights' => array(
                'normal' => 400,
                'medium' => 500,
                'semibold' => 600,
                'bold' => 700,
                'extrabold' => 800
            ),
            'display' => 'swap',
            'variable_font' => true,
            'weight_range' => '100 900'
        );
    }
    
    /**
     * Tester le chargement des polices
     */
    public static function test_font_loading() {
        $results = array(
            'font_file_exists' => file_exists(self::$fonts_path . 'Urbanist-var.woff2'),
            'css_file_exists' => file_exists(self::$fonts_path . 'urbanist.css'),
            'missing_fonts' => self::check_font_files(),
            'available_fonts' => self::get_available_fonts(),
            'config' => self::get_font_config()
        );
        
        return $results;
    }
}

// Initialiser la classe
WP_BMC_Fonts::init();
