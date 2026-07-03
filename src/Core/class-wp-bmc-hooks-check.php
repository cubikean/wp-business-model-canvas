<?php
/**
 * Script de vérification des hooks pour WP Business Model Canvas v2.0
 * 
 * Ce script vérifie tous les hooks et actions du plugin
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de vérification des hooks
 */
class WP_BMC_Hooks_Check {
    
    /**
     * Vérifier tous les hooks du plugin
     * 
     * @return array Résultats de la vérification
     */
    public static function check_all_hooks() {
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        // Hooks principaux
        $main_hooks = array(
            'plugins_loaded' => array(
                'callback' => 'wp_bmc_init',
                'description' => 'Initialisation du plugin',
                'priority' => 10,
            ),
            'admin_menu' => array(
                'callback' => 'WP_BMC_Loader::add_admin_menu',
                'description' => 'Ajout du menu admin',
                'priority' => 10,
            ),
            'admin_enqueue_scripts' => array(
                'callback' => 'WP_BMC_Loader::enqueue_admin_scripts',
                'description' => 'Chargement des scripts admin',
                'priority' => 10,
            ),
            'wp_enqueue_scripts' => array(
                'callback' => 'WP_BMC_Loader::enqueue_public_scripts',
                'description' => 'Chargement des scripts public',
                'priority' => 10,
            ),
            'wp_head' => array(
                'callback' => 'WP_BMC_Loader::add_custom_styles',
                'description' => 'Ajout des styles personnalisés',
                'priority' => 10,
            ),
            'admin_init' => array(
                'callback' => 'wp_bmc_check_update',
                'description' => 'Vérification des mises à jour',
                'priority' => 10,
            ),
        );
        
        foreach ($main_hooks as $hook => $hook_data) {
            $test_name = "Hook $hook";
            
            if (has_action($hook)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => $hook_data['description'] . ' - Hook enregistré',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => $hook_data['description'] . ' - Hook non enregistré',
                );
                $results['failed']++;
            }
        }
        
        // Hooks d'activation/désactivation
        $activation_hooks = array(
            'wp_bmc_activate' => 'Activation du plugin',
            'wp_bmc_deactivate' => 'Désactivation du plugin',
        );
        
        foreach ($activation_hooks as $hook => $description) {
            $test_name = "Hook d'activation $hook";
            
            if (function_exists($hook)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => $description . ' - Fonction définie',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => $description . ' - Fonction non définie',
                );
                $results['failed']++;
            }
        }
        
        // Vérifier les hooks AJAX
        $ajax_hooks = array(
            'wp_ajax_wp_bmc_save_canvas' => 'Sauvegarde canvas (admin)',
            'wp_ajax_nopriv_wp_bmc_save_canvas' => 'Sauvegarde canvas (public)',
            'wp_ajax_wp_bmc_login' => 'Connexion utilisateur',
            'wp_ajax_nopriv_wp_bmc_login' => 'Connexion utilisateur (public)',
            'wp_ajax_wp_bmc_register' => 'Inscription utilisateur',
            'wp_ajax_nopriv_wp_bmc_register' => 'Inscription utilisateur (public)',
            'wp_ajax_wp_bmc_upload_file' => 'Upload de fichier',
            'wp_ajax_nopriv_wp_bmc_upload_file' => 'Upload de fichier (public)',
        );
        
        foreach ($ajax_hooks as $hook => $description) {
            $test_name = "Hook AJAX $hook";
            
            if (has_action($hook)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => $description . ' - Hook AJAX enregistré',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'warning',
                    'message' => $description . ' - Hook AJAX non enregistré',
                );
                $results['failed']++;
            }
        }
        
        // Vérifier les shortcodes
        $shortcodes = array(
            'wp_bmc_canvas' => 'Shortcode canvas',
            'wp_bmc_dashboard' => 'Shortcode dashboard',
            'wp_bmc_login' => 'Shortcode connexion',
            'wp_bmc_register' => 'Shortcode inscription',
        );
        
        foreach ($shortcodes as $shortcode => $description) {
            $test_name = "Shortcode $shortcode";
            
            if (shortcode_exists($shortcode)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => $description . ' - Shortcode enregistré',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => $description . ' - Shortcode non enregistré',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Obtenir la liste de tous les hooks enregistrés
     * 
     * @return array Liste des hooks
     */
    public static function get_registered_hooks() {
        global $wp_filter;
        
        $plugin_hooks = array();
        
        foreach ($wp_filter as $hook_name => $hook_data) {
            if (strpos($hook_name, 'wp_bmc') !== false || 
                strpos($hook_name, 'bmc') !== false) {
                
                $plugin_hooks[$hook_name] = array(
                    'callbacks' => count($hook_data->callbacks),
                    'priorities' => array_keys($hook_data->callbacks),
                );
            }
        }
        
        return $plugin_hooks;
    }
    
    /**
     * Vérifier les fonctions essentielles
     * 
     * @return array Résultats de la vérification
     */
    public static function check_essential_functions() {
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        $functions = array(
            'wp_bmc_init' => 'Initialisation du plugin',
            'wp_bmc_activate' => 'Activation du plugin',
            'wp_bmc_deactivate' => 'Désactivation du plugin',
            'wp_bmc_check_update' => 'Vérification des mises à jour',
            'wp_bmc_create_pages' => 'Création des pages',
            'wp_bmc_include_edit_section' => 'Inclusion du template d\'édition',
        );
        
        foreach ($functions as $function => $description) {
            $test_name = "Fonction $function";
            
            if (function_exists($function)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => $description . ' - Fonction définie',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => $description . ' - Fonction non définie',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Afficher les résultats de la vérification des hooks
     * 
     * @param array $results Résultats de la vérification
     */
    public static function display_results($results) {
        echo '<div class="wp-bmc-hooks-check">';
        echo '<h3>🔗 Vérification des Hooks et Actions</h3>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Test</th><th>Statut</th><th>Message</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($results['tests'] as $test) {
            $status_class = $test['status'] === 'passed' ? 'success' : ($test['status'] === 'warning' ? 'warning' : 'error');
            $status_icon = $test['status'] === 'passed' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
            
            echo '<tr class="' . $status_class . '">';
            echo '<td>' . esc_html($test['name']) . '</td>';
            echo '<td>' . $status_icon . ' ' . esc_html($test['status']) . '</td>';
            echo '<td>' . esc_html($test['message']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<div class="test-summary">';
        echo '<p><strong>Résumé :</strong> ' . $results['passed'] . ' tests réussis, ' . $results['failed'] . ' tests échoués</p>';
        echo '</div>';
        
        // Afficher les hooks enregistrés
        $registered_hooks = self::get_registered_hooks();
        if (!empty($registered_hooks)) {
            echo '<h4>📋 Hooks Enregistrés</h4>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Hook</th><th>Callbacks</th><th>Priorités</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($registered_hooks as $hook => $data) {
                echo '<tr>';
                echo '<td>' . esc_html($hook) . '</td>';
                echo '<td>' . $data['callbacks'] . '</td>';
                echo '<td>' . implode(', ', $data['priorities']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }
}

/**
 * Fonction utilitaire pour exécuter la vérification des hooks
 */
function wp_bmc_check_hooks() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    $results = WP_BMC_Hooks_Check::check_all_hooks();
    WP_BMC_Hooks_Check::display_results($results);
}

// Hook pour exécuter la vérification des hooks via URL
if (isset($_GET['wp_bmc_check_hooks']) && $_GET['wp_bmc_check_hooks'] === '1') {
    add_action('admin_init', 'wp_bmc_check_hooks');
}
