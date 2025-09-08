<?php
/**
 * Script de vérification complet pour WP Business Model Canvas v2.0
 * 
 * Ce script vérifie tous les aspects du plugin après la réorganisation :
 * - Hooks et actions
 * - Base de données
 * - Chemins et fichiers
 * - Classes et fonctions
 * - Configuration
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de vérification complète
 */
class WP_BMC_Complete_Check {
    
    /**
     * Exécuter toutes les vérifications
     * 
     * @return array Résultats complets
     */
    public static function run_complete_check() {
        $results = array(
            'hooks' => self::check_hooks(),
            'database' => self::check_database(),
            'files' => self::check_files(),
            'classes' => self::check_classes(),
            'configuration' => self::check_configuration(),
            'paths' => self::check_paths(),
            'templates' => self::check_templates(),
            'assets' => self::check_assets(),
        );
        
        return $results;
    }
    
    /**
     * Vérifier les hooks et actions
     * 
     * @return array Résultats des hooks
     */
    private static function check_hooks() {
        $hooks = array(
            'plugins_loaded' => 'wp_bmc_init',
            'admin_menu' => 'WP_BMC_Loader::add_admin_menu',
            'admin_enqueue_scripts' => 'WP_BMC_Loader::enqueue_admin_scripts',
            'wp_enqueue_scripts' => 'WP_BMC_Loader::enqueue_public_scripts',
            'wp_head' => 'WP_BMC_Loader::add_custom_styles',
            'admin_init' => 'wp_bmc_check_update',
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        foreach ($hooks as $hook => $callback) {
            $test_name = "Hook $hook";
            
            if (has_action($hook)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => 'Hook enregistré correctement',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => 'Hook non enregistré',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Vérifier la base de données
     * 
     * @return array Résultats de la BDD
     */
    private static function check_database() {
        global $wpdb;
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        // Vérifier les tables principales
        $tables = array(
            'wp_bmc_users' => 'Utilisateurs BMC',
            'wp_bmc_canvas_data' => 'Données canvas',
            'wp_bmc_files' => 'Fichiers attachés',
            'wp_bmc_documents' => 'Documents',
        );
        
        foreach ($tables as $table => $description) {
            $test_name = "Table $description";
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            
            if ($table_exists) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Table $table existe",
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => "Table $table manquante",
                );
                $results['failed']++;
            }
        }
        
        // Vérifier les options
        $options = array(
            'wp_bmc_version' => 'Version du plugin',
            'wp_bmc_config' => 'Configuration',
        );
        
        foreach ($options as $option => $description) {
            $test_name = "Option $description";
            
            if (get_option($option) !== false) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Option $option définie",
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'warning',
                    'message' => "Option $option non définie",
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Vérifier les fichiers
     * 
     * @return array Résultats des fichiers
     */
    private static function check_files() {
        $files = array(
            'Fichier principal' => WP_BMC_PLUGIN_DIR . 'wp-business-model-canvas.php',
            'Autoloader' => WP_BMC_CORE_DIR . 'autoloader.php',
            'Configuration' => WP_BMC_CORE_DIR . 'class-wp-bmc-config.php',
            'Chemins' => WP_BMC_CORE_DIR . 'class-wp-bmc-paths.php',
            'Loader' => WP_BMC_CORE_DIR . 'class-wp-bmc-loader.php',
            'Template Loader' => WP_BMC_CORE_DIR . 'class-wp-bmc-template-loader.php',
            'Database' => WP_BMC_CORE_DIR . 'Database/class-wp-bmc-database.php',
            'Auth' => WP_BMC_CORE_DIR . 'Auth/class-wp-bmc-auth.php',
            'Ajax' => WP_BMC_CORE_DIR . 'Ajax/class-wp-bmc-ajax.php',
            'Shortcodes' => WP_BMC_CORE_DIR . 'Shortcodes/class-wp-bmc-shortcodes.php',
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        foreach ($files as $name => $file) {
            $test_name = "Fichier $name";
            
            if (file_exists($file)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Fichier existe : " . basename($file),
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
     * Vérifier les classes
     * 
     * @return array Résultats des classes
     */
    private static function check_classes() {
        $classes = array(
            'WP_BMC_Loader',
            'WP_BMC_Database',
            'WP_BMC_Auth',
            'WP_BMC_Shortcodes',
            'WP_BMC_Ajax',
            'WP_BMC_Template_Loader',
            'WP_BMC_Config',
            'WP_BMC_Paths',
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
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
     * Vérifier la configuration
     * 
     * @return array Résultats de la configuration
     */
    private static function check_configuration() {
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        // Vérifier les constantes
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
                    'message' => 'Constante définie',
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => 'Constante non définie',
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Vérifier les chemins
     * 
     * @return array Résultats des chemins
     */
    private static function check_paths() {
        $paths = array(
            'WP_BMC_SRC_DIR' => WP_BMC_SRC_DIR,
            'WP_BMC_ADMIN_DIR' => WP_BMC_ADMIN_DIR,
            'WP_BMC_PUBLIC_DIR' => WP_BMC_PUBLIC_DIR,
            'WP_BMC_CORE_DIR' => WP_BMC_CORE_DIR,
            'WP_BMC_SHARED_DIR' => WP_BMC_SHARED_DIR,
            'WP_BMC_ASSETS_DIR' => WP_BMC_ASSETS_DIR,
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
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
     * Vérifier les templates
     * 
     * @return array Résultats des templates
     */
    private static function check_templates() {
        $templates = array(
            'admin/canvas.php' => 'Template canvas admin',
            'admin/dashboard.php' => 'Template dashboard admin',
            'admin/edit-section.php' => 'Template édition admin',
            'public/canvas.php' => 'Template canvas public',
            'public/dashboard.php' => 'Template dashboard public',
            'public/edit-section.php' => 'Template édition public',
            'public/login-form.php' => 'Template connexion',
            'public/register-form.php' => 'Template inscription',
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        foreach ($templates as $template => $description) {
            $test_name = "Template $description";
            $file_path = WP_BMC_SHARED_DIR . 'Templates/' . $template;
            
            if (file_exists($file_path)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Template existe : $template",
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => "Template manquant : $template",
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Vérifier les assets
     * 
     * @return array Résultats des assets
     */
    private static function check_assets() {
        $assets = array(
            'Admin CSS' => WP_BMC_ADMIN_DIR . 'Assets/css/admin.css',
            'Admin Dashboard CSS' => WP_BMC_ADMIN_DIR . 'Assets/css/admin-dashboard.css',
            'Admin Users CSS' => WP_BMC_ADMIN_DIR . 'Assets/css/admin-users.css',
            'Admin JS' => WP_BMC_ADMIN_DIR . 'Assets/js/admin.js',
            'Admin Dashboard JS' => WP_BMC_ADMIN_DIR . 'Assets/js/admin-dashboard.js',
            'Admin Users JS' => WP_BMC_ADMIN_DIR . 'Assets/js/admin-users.js',
            'Public CSS' => WP_BMC_PUBLIC_DIR . 'Assets/css/public.css',
            'Public Admin CSS' => WP_BMC_PUBLIC_DIR . 'Assets/css/admin.css',
            'Public JS' => WP_BMC_PUBLIC_DIR . 'Assets/js/public.js',
            'Auth JS' => WP_BMC_PUBLIC_DIR . 'Assets/js/auth.js',
            'Dashboard JS' => WP_BMC_PUBLIC_DIR . 'Assets/js/dashboard.js',
        );
        
        $results = array(
            'passed' => 0,
            'failed' => 0,
            'tests' => array(),
        );
        
        foreach ($assets as $name => $file) {
            $test_name = "Asset $name";
            
            if (file_exists($file)) {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'passed',
                    'message' => "Asset existe : " . basename($file),
                );
                $results['passed']++;
            } else {
                $results['tests'][] = array(
                    'name' => $test_name,
                    'status' => 'failed',
                    'message' => "Asset manquant : $file",
                );
                $results['failed']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Afficher les résultats complets
     * 
     * @param array $results Résultats complets
     */
    public static function display_complete_results($results) {
        echo "<div class='wp-bmc-complete-check'>";
        echo "<h2>🔍 Vérification Complète WP Business Model Canvas v2.0</h2>";
        
        $total_passed = 0;
        $total_failed = 0;
        
        foreach ($results as $category => $result) {
            $total_passed += $result['passed'];
            $total_failed += $result['failed'];
        }
        
        echo "<div class='test-summary'>";
        echo "<h3>📊 Résumé Global</h3>";
        echo "<p><strong>Total :</strong> " . ($total_passed + $total_failed) . " tests</p>";
        echo "<p><strong>✅ Réussis :</strong> $total_passed</p>";
        echo "<p><strong>❌ Échoués :</strong> $total_failed</p>";
        echo "<p><strong>📈 Taux de réussite :</strong> " . round(($total_passed / ($total_passed + $total_failed)) * 100, 1) . "%</p>";
        echo "</div>";
        
        foreach ($results as $category => $result) {
            $category_name = ucfirst(str_replace('_', ' ', $category));
            echo "<div class='test-category'>";
            echo "<h3>🔧 $category_name</h3>";
            echo "<p><strong>Résultat :</strong> {$result['passed']} réussis, {$result['failed']} échoués</p>";
            
            if (!empty($result['tests'])) {
                echo "<table class='wp-list-table widefat fixed striped'>";
                echo "<thead><tr><th>Test</th><th>Statut</th><th>Message</th></tr></thead>";
                echo "<tbody>";
                
                foreach ($result['tests'] as $test) {
                    $status_class = $test['status'] === 'passed' ? 'success' : ($test['status'] === 'warning' ? 'warning' : 'error');
                    $status_icon = $test['status'] === 'passed' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
                    
                    echo "<tr class='$status_class'>";
                    echo "<td>{$test['name']}</td>";
                    echo "<td>$status_icon {$test['status']}</td>";
                    echo "<td>{$test['message']}</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
    }
}

/**
 * Fonction utilitaire pour exécuter la vérification complète
 */
function wp_bmc_run_complete_check() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    $results = WP_BMC_Complete_Check::run_complete_check();
    WP_BMC_Complete_Check::display_complete_results($results);
}

// Hook pour exécuter la vérification via URL
if (isset($_GET['wp_bmc_complete_check']) && $_GET['wp_bmc_complete_check'] === '1') {
    add_action('admin_init', 'wp_bmc_run_complete_check');
}
