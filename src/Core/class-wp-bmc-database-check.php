<?php
/**
 * Script de vérification de la base de données pour WP Business Model Canvas v2.0
 * 
 * Ce script vérifie et répare la base de données si nécessaire
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de vérification de la base de données
 */
class WP_BMC_Database_Check {
    
    /**
     * Vérifier et réparer la base de données
     * 
     * @return array Résultats de la vérification
     */
    public static function check_and_repair_database() {
        global $wpdb;
        
        $results = array(
            'tables_created' => 0,
            'tables_repaired' => 0,
            'errors' => array(),
            'success' => true,
        );
        
        // Vérifier et créer les tables principales
        $tables = self::get_required_tables();
        
        foreach ($tables as $table_name => $table_structure) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Vérifier si la table existe
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
            
            if (!$table_exists) {
                // Créer la table
                $sql = "CREATE TABLE $full_table_name (";
                $sql .= implode(', ', $table_structure);
                $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                
                $result = $wpdb->query($sql);
                
                if ($result !== false) {
                    $results['tables_created']++;
                } else {
                    $results['errors'][] = "Erreur lors de la création de la table $table_name : " . $wpdb->last_error;
                    $results['success'] = false;
                }
            } else {
                // Vérifier la structure de la table
                $columns = $wpdb->get_results("DESCRIBE $full_table_name");
                $existing_columns = array();
                
                foreach ($columns as $column) {
                    $existing_columns[] = $column->Field;
                }
                
                // Vérifier si toutes les colonnes requises existent
                $required_columns = self::get_required_columns($table_structure);
                $missing_columns = array_diff($required_columns, $existing_columns);
                
                if (!empty($missing_columns)) {
                    // Ajouter les colonnes manquantes
                    foreach ($missing_columns as $column) {
                        $column_definition = self::get_column_definition($table_structure, $column);
                        if ($column_definition) {
                            $sql = "ALTER TABLE $full_table_name ADD COLUMN $column_definition";
                            $result = $wpdb->query($sql);
                            
                            if ($result !== false) {
                                $results['tables_repaired']++;
                            } else {
                                $results['errors'][] = "Erreur lors de l'ajout de la colonne $column à la table $table_name : " . $wpdb->last_error;
                                $results['success'] = false;
                            }
                        }
                    }
                }
            }
        }
        
        // Vérifier les options
        self::check_options($results);
        
        return $results;
    }
    
    /**
     * Obtenir les tables requises
     * 
     * @return array Tables requises
     */
    private static function get_required_tables() {
        return array(
            'bmc_users' => array(
                'id INT(11) NOT NULL AUTO_INCREMENT',
                'user_id INT(11) NOT NULL',
                'username VARCHAR(100) NOT NULL',
                'email VARCHAR(255) NOT NULL',
                'password_hash VARCHAR(255) NOT NULL',
                'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'PRIMARY KEY (id)',
                'UNIQUE KEY unique_user_id (user_id)',
                'UNIQUE KEY unique_username (username)',
                'UNIQUE KEY unique_email (email)'
            ),
            'bmc_canvas_data' => array(
                'id INT(11) NOT NULL AUTO_INCREMENT',
                'user_id INT(11) NOT NULL',
                'section VARCHAR(50) NOT NULL',
                'content LONGTEXT',
                'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'PRIMARY KEY (id)',
                'KEY user_section (user_id, section)'
            ),
            'bmc_files' => array(
                'id INT(11) NOT NULL AUTO_INCREMENT',
                'user_id INT(11) NOT NULL',
                'filename VARCHAR(255) NOT NULL',
                'original_name VARCHAR(255) NOT NULL',
                'file_path VARCHAR(500) NOT NULL',
                'file_size INT(11) NOT NULL',
                'file_type VARCHAR(100) NOT NULL',
                'uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'PRIMARY KEY (id)',
                'KEY user_files (user_id)'
            ),
            'bmc_documents' => array(
                'id INT(11) NOT NULL AUTO_INCREMENT',
                'user_id INT(11) NOT NULL',
                'title VARCHAR(255) NOT NULL',
                'content LONGTEXT',
                'file_path VARCHAR(500)',
                'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
                'PRIMARY KEY (id)',
                'KEY user_documents (user_id)'
            )
        );
    }
    
    /**
     * Obtenir les colonnes requises d'une table
     * 
     * @param array $table_structure Structure de la table
     * @return array Colonnes requises
     */
    private static function get_required_columns($table_structure) {
        $columns = array();
        
        foreach ($table_structure as $definition) {
            if (strpos($definition, 'PRIMARY KEY') === false && 
                strpos($definition, 'UNIQUE KEY') === false && 
                strpos($definition, 'KEY') === false) {
                
                $parts = explode(' ', $definition);
                $columns[] = $parts[0];
            }
        }
        
        return $columns;
    }
    
    /**
     * Obtenir la définition d'une colonne
     * 
     * @param array $table_structure Structure de la table
     * @param string $column_name Nom de la colonne
     * @return string|null Définition de la colonne
     */
    private static function get_column_definition($table_structure, $column_name) {
        foreach ($table_structure as $definition) {
            if (strpos($definition, $column_name) === 0) {
                return $definition;
            }
        }
        
        return null;
    }
    
    /**
     * Vérifier les options
     * 
     * @param array $results Résultats (par référence)
     */
    private static function check_options(&$results) {
        $required_options = array(
            'wp_bmc_version' => WP_BMC_VERSION,
            'wp_bmc_config' => array(
                'version' => WP_BMC_VERSION,
                'auto_save_interval' => 30,
                'max_file_size' => 10485760,
                'allowed_file_types' => array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'),
            ),
        );
        
        foreach ($required_options as $option_name => $default_value) {
            $current_value = get_option($option_name);
            
            if ($current_value === false) {
                // Option n'existe pas, la créer
                update_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Obtenir les statistiques de la base de données
     * 
     * @return array Statistiques
     */
    public static function get_database_stats() {
        global $wpdb;
        
        $stats = array();
        
        $tables = array('bmc_users', 'bmc_canvas_data', 'bmc_files', 'bmc_documents');
        
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
            $stats[$table] = intval($count);
        }
        
        return $stats;
    }
    
    /**
     * Afficher les résultats de la vérification
     * 
     * @param array $results Résultats de la vérification
     */
    public static function display_results($results) {
        echo '<div class="wp-bmc-database-check">';
        echo '<h3>🗄️ Vérification de la Base de Données</h3>';
        
        if ($results['success']) {
            echo '<div class="notice notice-success">';
            echo '<p><strong>✅ Vérification réussie !</strong></p>';
            echo '<ul>';
            echo '<li>Tables créées : ' . $results['tables_created'] . '</li>';
            echo '<li>Tables réparées : ' . $results['tables_repaired'] . '</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error">';
            echo '<p><strong>❌ Erreurs détectées :</strong></p>';
            echo '<ul>';
            foreach ($results['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        // Afficher les statistiques
        $stats = self::get_database_stats();
        echo '<h4>📊 Statistiques de la Base de Données</h4>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Table</th><th>Nombre d\'enregistrements</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($stats as $table => $count) {
            echo '<tr>';
            echo '<td>' . esc_html($table) . '</td>';
            echo '<td>' . number_format($count) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

/**
 * Fonction utilitaire pour exécuter la vérification de la BDD
 */
function wp_bmc_check_database() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès refusé');
    }
    
    $results = WP_BMC_Database_Check::check_and_repair_database();
    WP_BMC_Database_Check::display_results($results);
}

// Hook pour exécuter la vérification de la BDD via URL
if (isset($_GET['wp_bmc_check_db']) && $_GET['wp_bmc_check_db'] === '1') {
    add_action('admin_init', 'wp_bmc_check_database');
}
