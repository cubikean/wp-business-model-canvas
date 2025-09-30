<?php
/**
 * Configuration des sections du Business Model Canvas
 * 
 * Ce fichier contient la définition de toutes les sections du canvas
 * et est partagé entre toutes les vues (public, admin, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupère la configuration des sections du canvas selon le mode de vue
 * 
 * @param string $view_mode Mode de vue ('synthetic' ou 'global')
 * @param bool $use_custom_configs Si true, utilise les configurations personnalisées de la DB
 * @return array Configuration des sections
 */
function wp_bmc_get_canvas_sections($view_mode = 'global', $use_custom_configs = true) {
    // Configuration par défaut
    $default_sections = array(
        'key_partners' => array(
            'title' => 'Partenaires clés',
            'placeholder' => 'Quelles sont mes principales dépenses ?',
            'synthetic' => false,   
            'color' => 'red',
            'grid-column' => '1/4',
            'grid-row' => '1'
        ),
        'key_activities' => array(
            'title' => 'Activités clés',
            'placeholder' => 'Quels sont les caractéristiques de ton client idéal ?',
            'synthetic' => false,
            'color' => 'red',
            'grid-column' => '1/3',
            'grid-row' => '2'
        ),
        'key_resources' => array(
            'title' => 'Ressources clés',
            'placeholder' => 'Quels sont les caractéristiques de ton client idéal ?',
            'synthetic' => false,
            'color' => 'red',
            'grid-column' => '1/3',
            'grid-row' => '3'
        ),
        'value_proposition' => array(
            'title' => 'Proposition de valeur',
            'placeholder' => "Pourquoi un client choisirait-il ton offre plutôt qu'une autre ?",
            'synthetic' => true,
            'color' => 'green',
            'grid-column' => $view_mode === 'synthetic' ? '1/3' : '3/5',
            'grid-row' => $view_mode === 'synthetic' ? '1' : '2/4'
        ),
        'customer_segments' => array(
            'title' => 'Segments clients',
            'placeholder' => "Comment mon projet génère-t-il de l'argent ?",
            'synthetic' => true,
            'color' => 'orange',
            'grid-column' => $view_mode === 'synthetic' ? '3/5' : '4/7',
            'grid-row' => $view_mode === 'synthetic' ? '1' : '1'
        ),
        'customer_relationships' => array(
            'title' => 'Relations clients',
            'placeholder' => 'Quels sont les caractéristiques de ton client idéal ?',
            'synthetic' => false,
            'color' => 'orange',
            'grid-column' => '5/7',
            'grid-row' => '2'
        ),
        'channels' => array(
            'title' => 'Canaux',
            'placeholder' => 'Quels sont les caractéristiques de ton client idéal ?',
            'synthetic' => false,
            'color' => 'orange',
            'grid-column' => '5/7',
            'grid-row' => '3'
        ),
        'cost_structure' => array(
            'title' => 'Structure des coûts',
            'placeholder' => 'Quelles sont mes principales dépenses ?',
            'synthetic' => false,
            'color' => 'blue',
            'grid-column' => '1/4',
            'grid-row' => '4'
        ),
        'revenue_streams' => array(
            'title' => 'Sources de revenus',
            'placeholder' => "Comment mon projet génère-t-il de l'argent ?",
            'synthetic' => true,    
            'color' => 'blue',
            'grid-column' => ($view_mode === 'synthetic') ? '1/5' : '4/7',
            'grid-row' => ($view_mode === 'synthetic') ? '2' : '4'
        )
    );
    
    // Récupérer les configurations personnalisées depuis la base de données si demandé
    if ($use_custom_configs) {
        $custom_configs = WP_BMC_Database::get_all_canvas_configs();
        
        // Fusionner les configurations personnalisées avec les valeurs par défaut
        foreach ($default_sections as $section_key => &$section_config) {
            if (isset($custom_configs[$section_key])) {
                if (isset($custom_configs[$section_key]['title'])) {
                    $section_config['title'] = $custom_configs[$section_key]['title'];
                }
                if (isset($custom_configs[$section_key]['placeholder'])) {
                    $section_config['placeholder'] = $custom_configs[$section_key]['placeholder'];
                }
            }
        }
    }
    
    return $default_sections;
}
