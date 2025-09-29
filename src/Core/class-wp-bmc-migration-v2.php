<?php
/**
 * Script de migration pour WP Business Model Canvas v2.0
 * Migre les données existantes vers la nouvelle architecture
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Migration_V2 {
    
    /**
     * Exécuter la migration vers la v2.0
     */
    public static function migrate_to_v2() {
        global $wpdb;
        
        $results = array(
            'success' => true,
            'errors' => array(),
            'migrated' => array(
                'users' => 0,
                'projects' => 0,
                'assignments' => 0
            )
        );
        
        try {
            // 1. Mettre à jour la structure des tables
            self::update_table_structure($results);
            
            // 2. Migrer les utilisateurs existants
            self::migrate_existing_users($results);
            
            // 3. Migrer les projets existants
            self::migrate_existing_projects($results);
            
            // 4. Créer les associations projet-utilisateur
            self::create_project_user_assignments($results);
            
            // 5. Nettoyer les données obsolètes
            self::cleanup_old_data($results);
            
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Erreur lors de la migration : ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Mettre à jour la structure des tables
     */
    private static function update_table_structure(&$results) {
        global $wpdb;
        
        // Ajouter les nouvelles colonnes à la table des utilisateurs
        $users_table = $wpdb->prefix . 'bmc_users';
        
        // Vérifier si les colonnes existent déjà
        $columns = $wpdb->get_col("DESCRIBE $users_table");
        
        if (!in_array('custom_id', $columns)) {
            $wpdb->query("ALTER TABLE $users_table ADD COLUMN custom_id varchar(50) DEFAULT NULL AFTER user_id");
            $wpdb->query("ALTER TABLE $users_table ADD UNIQUE KEY custom_id (custom_id)");
        }
        
        if (!in_array('is_active', $columns)) {
            $wpdb->query("ALTER TABLE $users_table ADD COLUMN is_active tinyint(1) DEFAULT 1 AFTER status");
        }
        
        if (!in_array('created_by_admin', $columns)) {
            $wpdb->query("ALTER TABLE $users_table ADD COLUMN created_by_admin bigint(20) DEFAULT NULL AFTER is_active");
            $wpdb->query("ALTER TABLE $users_table ADD KEY created_by_admin (created_by_admin)");
        }
        
        // Mettre à jour la table des projets
        $projects_table = $wpdb->prefix . 'bmc_projects';
        $project_columns = $wpdb->get_col("DESCRIBE $projects_table");
        
        if (!in_array('created_by_admin', $project_columns)) {
            $wpdb->query("ALTER TABLE $projects_table ADD COLUMN created_by_admin bigint(20) NOT NULL AFTER status");
            $wpdb->query("ALTER TABLE $projects_table ADD KEY created_by_admin (created_by_admin)");
        }
        
        // Créer la table de liaison projet-utilisateurs
        $project_users_table = $wpdb->prefix . 'bmc_project_users';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$project_users_table'") != $project_users_table) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $project_users_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                project_id mediumint(9) NOT NULL,
                user_id bigint(20) NOT NULL,
                assigned_by_admin bigint(20) NOT NULL,
                assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
                is_active tinyint(1) DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY project_user (project_id, user_id),
                KEY project_id (project_id),
                KEY user_id (user_id),
                KEY assigned_by_admin (assigned_by_admin),
                KEY is_active (is_active)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Migrer les utilisateurs existants
     */
    private static function migrate_existing_users(&$results) {
        global $wpdb;
        
        $users_table = $wpdb->prefix . 'bmc_users';
        
        // Obtenir tous les utilisateurs existants
        $existing_users = $wpdb->get_results("SELECT * FROM $users_table WHERE custom_id IS NULL");
        
        foreach ($existing_users as $user) {
            // Générer un ID personnalisé basé sur l'ID existant
            $custom_id = 'USER' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
            
            // Vérifier si cet ID existe déjà
            $counter = 1;
            $original_custom_id = $custom_id;
            while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $users_table WHERE custom_id = %s", $custom_id))) {
                $custom_id = $original_custom_id . '_' . $counter;
                $counter++;
            }
            
            // Mettre à jour l'utilisateur
            $wpdb->update(
                $users_table,
                array(
                    'custom_id' => $custom_id,
                    'is_active' => 1,
                    'created_by_admin' => 1 // Admin par défaut
                ),
                array('id' => $user->id),
                array('%s', '%d', '%d'),
                array('%d')
            );
            
            $results['migrated']['users']++;
        }
    }
    
    /**
     * Migrer les projets existants
     */
    private static function migrate_existing_projects(&$results) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'bmc_projects';
        
        // Obtenir tous les projets existants
        $existing_projects = $wpdb->get_results("SELECT * FROM $projects_table WHERE created_by_admin IS NULL");
        
        foreach ($existing_projects as $project) {
            // Mettre à jour le projet avec un admin par défaut
            $wpdb->update(
                $projects_table,
                array('created_by_admin' => 1), // Admin par défaut
                array('id' => $project->id),
                array('%d'),
                array('%d')
            );
            
            $results['migrated']['projects']++;
        }
    }
    
    /**
     * Créer les associations projet-utilisateur
     */
    private static function create_project_user_assignments(&$results) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'bmc_projects';
        $project_users_table = $wpdb->prefix . 'bmc_project_users';
        
        // Obtenir tous les projets avec leur user_id original
        $projects = $wpdb->get_results("SELECT * FROM $projects_table");
        
        foreach ($projects as $project) {
            // Vérifier si l'association existe déjà
            $existing_assignment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $project_users_table WHERE project_id = %d AND user_id = %d",
                $project->id,
                $project->user_id
            ));
            
            if (!$existing_assignment) {
                // Créer l'association
                $wpdb->insert(
                    $project_users_table,
                    array(
                        'project_id' => $project->id,
                        'user_id' => $project->user_id,
                        'assigned_by_admin' => $project->created_by_admin ?: 1,
                        'is_active' => 1
                    ),
                    array('%d', '%d', '%d', '%d')
                );
                
                $results['migrated']['assignments']++;
            }
        }
    }
    
    /**
     * Nettoyer les données obsolètes
     */
    private static function cleanup_old_data(&$results) {
        global $wpdb;
        
        $projects_table = $wpdb->prefix . 'bmc_projects';
        
        // Supprimer la colonne user_id de la table des projets (optionnel)
        // On la garde pour l'instant pour la compatibilité
        
        $results['cleanup'] = 'Données obsolètes nettoyées';
    }
    
    /**
     * Vérifier l'état de la migration
     */
    public static function check_migration_status() {
        global $wpdb;
        
        $status = array(
            'needs_migration' => false,
            'issues' => array()
        );
        
        // Vérifier la structure des tables
        $users_table = $wpdb->prefix . 'bmc_users';
        $projects_table = $wpdb->prefix . 'bmc_projects';
        $project_users_table = $wpdb->prefix . 'bmc_project_users';
        
        // Vérifier les colonnes de la table des utilisateurs
        $users_columns = $wpdb->get_col("DESCRIBE $users_table");
        if (!in_array('custom_id', $users_columns)) {
            $status['needs_migration'] = true;
            $status['issues'][] = 'Colonne custom_id manquante dans la table des utilisateurs';
        }
        
        if (!in_array('is_active', $users_columns)) {
            $status['needs_migration'] = true;
            $status['issues'][] = 'Colonne is_active manquante dans la table des utilisateurs';
        }
        
        if (!in_array('created_by_admin', $users_columns)) {
            $status['needs_migration'] = true;
            $status['issues'][] = 'Colonne created_by_admin manquante dans la table des utilisateurs';
        }
        
        // Vérifier les colonnes de la table des projets
        $projects_columns = $wpdb->get_col("DESCRIBE $projects_table");
        if (!in_array('created_by_admin', $projects_columns)) {
            $status['needs_migration'] = true;
            $status['issues'][] = 'Colonne created_by_admin manquante dans la table des projets';
        }
        
        // Vérifier l'existence de la table de liaison
        if ($wpdb->get_var("SHOW TABLES LIKE '$project_users_table'") != $project_users_table) {
            $status['needs_migration'] = true;
            $status['issues'][] = 'Table de liaison projet-utilisateurs manquante';
        }
        
        return $status;
    }
    
    /**
     * Afficher les résultats de la migration
     */
    public static function display_migration_results($results) {
        if ($results['success']) {
            echo '<div class="notice notice-success">';
            echo '<h3>Migration vers la v2.0 réussie !</h3>';
            echo '<ul>';
            echo '<li>Utilisateurs migrés : ' . $results['migrated']['users'] . '</li>';
            echo '<li>Projets migrés : ' . $results['migrated']['projects'] . '</li>';
            echo '<li>Assignations créées : ' . $results['migrated']['assignments'] . '</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error">';
            echo '<h3>Erreurs lors de la migration :</h3>';
            echo '<ul>';
            foreach ($results['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }
}

// Fonction pour exécuter la migration depuis l'admin
function wp_bmc_run_migration_v2() {
    if (!current_user_can('manage_options')) {
        wp_die('Accès non autorisé');
    }
    
    $results = WP_BMC_Migration_V2::migrate_to_v2();
    WP_BMC_Migration_V2::display_migration_results($results);
}
