<?php
/**
 * Script de migration pour réorganiser les fichiers du plugin
 * 
 * Ce script aide à déplacer les fichiers de l'ancienne structure vers la nouvelle
 * structure organisée du plugin WP Business Model Canvas v2.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Migration {
    
    /**
     * Liste des fichiers à migrer
     */
    private static $migration_map = array(
        // Templates admin
        'templates/admin/canvas.php' => 'src/Shared/Templates/admin/canvas.php',
        'templates/admin/dashboard.php' => 'src/Shared/Templates/admin/dashboard.php',
        'templates/admin/edit-section.php' => 'src/Shared/Templates/admin/edit-section.php',
        
        // Templates public
        'templates/public/canvas.php' => 'src/Shared/Templates/public/canvas.php',
        'templates/public/dashboard.php' => 'src/Shared/Templates/public/dashboard.php',
        'templates/public/edit-section.php' => 'src/Shared/Templates/public/edit-section.php',
        'templates/public/login-form.php' => 'src/Shared/Templates/public/login-form.php',
        'templates/public/register-form.php' => 'src/Shared/Templates/public/register-form.php',
        
        // Classes Core
        'includes/class-wp-bmc-database.php' => 'src/Core/Database/class-wp-bmc-database.php',
        'includes/class-wp-bmc-auth.php' => 'src/Core/Auth/class-wp-bmc-auth.php',
        'includes/class-wp-bmc-ajax.php' => 'src/Core/Ajax/class-wp-bmc-ajax.php',
        'includes/class-wp-bmc-shortcodes.php' => 'src/Core/Shortcodes/class-wp-bmc-shortcodes.php',
        'includes/class-wp-bmc-template-loader.php' => 'src/Core/class-wp-bmc-template-loader.php',
        
        // Assets admin
        'admin/js/admin.js' => 'src/Admin/Assets/js/admin.js',
        'admin/js/admin-dashboard.js' => 'src/Admin/Assets/js/admin-dashboard.js',
        'admin/js/admin-users.js' => 'src/Admin/Assets/js/admin-users.js',
        
        // Assets public
        'public/css/users.css' => 'src/Public/Assets/css/users.css',
        'public/css/public.css' => 'src/Public/Assets/css/public.css',
        'public/css/admin.css' => 'src/Public/Assets/css/admin.css',
        'public/js/public.js' => 'src/Public/Assets/js/public.js',
        'public/js/auth.js' => 'src/Public/Assets/js/auth.js',
        'public/js/dashboard.js' => 'src/Public/Assets/js/dashboard.js',
        
        // Polices
        'shared/utils/fonts/urbanist.css' => 'src/Shared/utils/fonts/urbanist.css',
        'shared/utils/fonts/class-wp-bmc-fonts.php' => 'src/Shared/utils/fonts/class-wp-bmc-fonts.php',
        'shared/utils/fonts/Urbanist-var.woff2' => 'src/Shared/utils/fonts/Urbanist-var.woff2',
    );
    
    /**
     * Exécuter la migration
     */
    public static function migrate() {
        $plugin_dir = WP_BMC_PLUGIN_DIR;
        $migrated = 0;
        $errors = array();
        
        foreach (self::$migration_map as $old_path => $new_path) {
            $old_file = $plugin_dir . $old_path;
            $new_file = $plugin_dir . $new_path;
            
            // Créer le dossier de destination si nécessaire
            $new_dir = dirname($new_file);
            if (!file_exists($new_dir)) {
                wp_mkdir_p($new_dir);
            }
            
            // Copier le fichier
            if (file_exists($old_file)) {
                if (copy($old_file, $new_file)) {
                    $migrated++;
                } else {
                    $errors[] = "Impossible de copier : $old_path vers $new_path";
                }
            } else {
                $errors[] = "Fichier source non trouvé : $old_path";
            }
        }
        
        return array(
            'migrated' => $migrated,
            'errors' => $errors,
            'total' => count(self::$migration_map)
        );
    }
    
    /**
     * Nettoyer les anciens fichiers (optionnel)
     */
    public static function cleanup_old_files() {
        $plugin_dir = WP_BMC_PLUGIN_DIR;
        $cleaned = 0;
        
        foreach (self::$migration_map as $old_path => $new_path) {
            $old_file = $plugin_dir . $old_path;
            
            if (file_exists($old_file)) {
                if (unlink($old_file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Vérifier l'état de la migration
     */
    public static function check_migration_status() {
        $plugin_dir = WP_BMC_PLUGIN_DIR;
        $status = array(
            'migrated' => 0,
            'missing' => 0,
            'total' => count(self::$migration_map)
        );
        
        foreach (self::$migration_map as $old_path => $new_path) {
            $new_file = $plugin_dir . $new_path;
            
            if (file_exists($new_file)) {
                $status['migrated']++;
            } else {
                $status['missing']++;
            }
        }
        
        return $status;
    }
}

// Fonction utilitaire pour exécuter la migration depuis l'admin
function wp_bmc_run_migration() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    $result = WP_BMC_Migration::migrate();
    
    wp_die(
        sprintf(
            'Migration terminée : %d/%d fichiers migrés. Erreurs : %s',
            $result['migrated'],
            $result['total'],
            implode(', ', $result['errors'])
        ),
        'Migration WP BMC'
    );
}

// Hook pour exécuter la migration via URL
if (isset($_GET['wp_bmc_migrate']) && $_GET['wp_bmc_migrate'] === '1') {
    add_action('admin_init', 'wp_bmc_run_migration');
}
