<?php
/**
 * Tests de la nouvelle structure WP Business Model Canvas v2.0
 * 
 * Ce fichier contient des tests pour vérifier que la réorganisation
 * fonctionne correctement.
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de tests pour la nouvelle structure
 */
class WP_BMC_Structure_Tests {
    
    /**
     * Exécuter tous les tests
     * 
     * @return array Résultats des tests
     */
    public static function run_all_tests() {
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        // Tests des constantes
        $results = self::test_constants($results);
        
        // Tests des chemins
        $results = self::test_paths($results);
        
        // Tests des classes
        $results = self::test_classes($results);
        
        // Tests des fichiers
        $results = self::test_files($results);
        
        // Tests de l'autoloader
        $results = self::test_autoloader($results);
        
        return $results;
    }
    
    /**
     * Tester les constantes
     * 
     * @param array $results Résultats actuels
     * @return array Résultats mis à jour
     */
    private static function test_constants($results) {
        $constants = array(
            'WP_BMC_VERSION',
            'WP_BMC_PLUGIN_DIR',
            'WP_BMC_PLUGIN_URL',
            'WP_BMC_SRC_DIR',
            'WP_BMC_ADMIN_DIR',
            'WP_BMC_PUBLIC_DIR',
            'WP_BMC_CORE_DIR',
            'WP_BMC_SHARED_DIR',
            'WP_BMC_ASSETS_DIR',
        );
        
        foreach ($constants as $constant) {
            $test_name = "Constante $constant";
            
            if (defined($constant)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => 'Définie correctement',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => 'Non définie',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Tester les chemins
     * 
     * @param array $results Résultats actuels
     * @return array Résultats mis à jour
     */
    private static function test_paths($results) {
        $paths = array(
            'WP_BMC_SRC_DIR' => WP_BMC_SRC_DIR,
            'WP_BMC_ADMIN_DIR' => WP_BMC_ADMIN_DIR,
            'WP_BMC_PUBLIC_DIR' => WP_BMC_PUBLIC_DIR,
            'WP_BMC_CORE_DIR' => WP_BMC_CORE_DIR,
            'WP_BMC_SHARED_DIR' => WP_BMC_SHARED_DIR,
        );
        
        foreach ($paths as $name => $path) {
            $test_name = "Chemin $name";
            
            if (is_dir($path)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Dossier existe : $path",
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => "Dossier manquant : $path",
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Tester les classes
     * 
     * @param array $results Résultats actuels
     * @return array Résultats mis à jour
     */
    private static function test_classes($results) {
        $classes = array(
            'WP_BMC_Loader',
            'WP_BMC_Database',
            'WP_BMC_Auth',
            'WP_BMC_Shortcodes',
            'WP_BMC_Ajax',
            'WP_BMC_Template_Loader',
        );
        
        foreach ($classes as $class) {
            $test_name = "Classe $class";
            
            if (class_exists($class)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => 'Classe chargée correctement',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => 'Classe non trouvée',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Tester les fichiers
     * 
     * @param array $results Résultats actuels
     * @return array Résultats mis à jour
     */
    private static function test_files($results) {
        $files = array(
            'Fichier principal' => WP_BMC_PLUGIN_DIR . 'wp-business-model-canvas.php',
            'Autoloader' => WP_BMC_CORE_DIR . 'autoloader.php',
            'Configuration' => WP_BMC_CORE_DIR . 'class-wp-bmc-config.php',
            'Chemins' => WP_BMC_CORE_DIR . 'class-wp-bmc-paths.php',
            'Migration' => WP_BMC_CORE_DIR . 'class-wp-bmc-migration.php',
        );
        
        foreach ($files as $name => $file) {
            $test_name = "Fichier $name";
            
            if (file_exists($file)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Fichier existe : $file",
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => "Fichier manquant : $file",
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Tester l'autoloader
     * 
     * @param array $results Résultats actuels
     * @return array Résultats mis à jour
     */
    private static function test_autoloader($results) {
        $test_name = "Autoloader";
        
        if (function_exists('wp_bmc_autoloader')) {
            $results['tests'][] = array(
                'name' => $test_name,
                'status' => 'passed',
                'message' => 'Autoloader enregistré',
            );
            $results['passed']++;
        } else {
            $results['tests'][] = array(
                'name' => $test_name,
                'status' => 'failed',
                'message' => 'Autoloader non enregistré',
            );
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Afficher les résultats des tests
     * 
     * @param array $results Résultats des tests
     */
    public static function display_results($results) {
        echo "<div class='wp-bmc-test-results'>";
        echo "<h3>🧪 Tests de la nouvelle structure WP BMC v2.0</h3>";
        echo "<div class='test-summary'>";
        echo "<p><strong>Résumé :</strong> {$results['passed']} tests réussis, {$results['failed']} tests échoués</p>";
        echo "</div>";
        
        echo "<table class='wp-list-table widefat fixed striped'>";
        echo "<thead><tr><th>Test</th><th>Statut</th><th>Message</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($results['tests'] as $test) {
            $status_class = $test['status'] === 'passed' ? 'success' : 'error';
            $status_icon = $test['status'] === 'passed' ? '✅' : '❌';
            
            echo "<tr class='$status_class'>";
            echo "<td>{$test['name']}</td>";
            echo "<td>$status_icon {$test['status']}</td>";
            echo "<td>{$test['message']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
}

/**
 * Fonction utilitaire pour exécuter les tests depuis l'admin
 */
function wp_bmc_run_structure_tests() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    $results = WP_BMC_Structure_Tests::run_all_tests();
    WP_BMC_Structure_Tests::display_results($results);
}

// Hook pour exécuter les tests via URL
if (isset($_GET['wp_bmc_test']) && $_GET['wp_bmc_test'] === '1') {
    add_action('admin_init', 'wp_bmc_run_structure_tests');
}
