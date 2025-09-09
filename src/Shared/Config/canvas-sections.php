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

return array(
    'key_partners' => array(
        'title' => 'Partenaires clés',
        'placeholder' => 'Qui sont vos partenaires clés ?',
        'synthetic' => false,   
        'color' => 'red'
    ),
    'key_activities' => array(
        'title' => 'Activités clés',
        'placeholder' => 'Quelles sont vos activités clés ?',
        'synthetic' => false,
        'color' => 'red'
    ),
    'key_resources' => array(
        'title' => 'Ressources clés',
        'placeholder' => 'Quelles sont vos ressources clés ?',
        'synthetic' => false,
        'color' => 'red'
    ),
    'value_proposition' => array(
        'title' => 'Proposition de valeur',
        'placeholder' => 'Quelle est votre proposition de valeur ?',
        'synthetic' => true,
        'color' => 'green'
    ),
    'customer_relationships' => array(
        'title' => 'Relations clients',
        'placeholder' => 'Quel type de relation établissez-vous avec vos clients ?',
        'synthetic' => false,
        'color' => 'orange'
    ),
    'channels' => array(
        'title' => 'Canaux',
        'placeholder' => 'Quels canaux utilisez-vous pour atteindre vos clients ?',
        'synthetic' => false,
        'color' => 'orange'
    ),
    'customer_segments' => array(
        'title' => 'Segments clients',
        'placeholder' => 'Quels sont vos segments clients ?',
        'synthetic' => true,
        'color' => 'orange'
    ),
    'cost_structure' => array(
        'title' => 'Structure des coûts',
        'placeholder' => 'Quels sont vos coûts principaux ?',
        'synthetic' => false,
        'color' => 'blue'
    ),
    'revenue_streams' => array(
        'title' => 'Sources de revenus',
        'placeholder' => 'Quelles sont vos sources de revenus ?',
        'synthetic' => true,
        'color' => 'blue'
    )
);
