<?php
/**
 * Classe de gestion de la base de données pour WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Database {
    
    /**
     * Initialiser la base de données
     */
    public static function init() {
        // Les tables sont créées lors de l'activation
    }
    
    /**
     * Formater une date selon les paramètres WordPress (fuseau horaire et format)
     */
    public static function format_date_for_display($date_string, $format = 'datetime') {
        if (empty($date_string)) {
            return '';
        }
        
        // Convertir la date UTC en fuseau horaire local WordPress
        $timestamp = strtotime($date_string . ' UTC');
        
        switch ($format) {
            case 'date':
                return wp_date(get_option('date_format'), $timestamp);
            case 'time':
                return wp_date(get_option('time_format'), $timestamp);
            case 'datetime':
                return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
            default:
                return wp_date($format, $timestamp);
        }
    }
    
    /**
     * Créer les tables de base de données
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des utilisateurs BMC
        $table_users = $wpdb->prefix . 'bmc_users';
        $sql_users = "CREATE TABLE $table_users (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            custom_id varchar(50) DEFAULT NULL,
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            is_active tinyint(1) DEFAULT 1,
            created_by_admin bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY custom_id (custom_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY is_active (is_active),
            KEY created_by_admin (created_by_admin)
        ) $charset_collate;";
        
        // Table des projets BMC
        $table_projects = $wpdb->prefix . 'bmc_projects';
        $sql_projects = "CREATE TABLE $table_projects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'draft',
            created_by_admin bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY created_by_admin (created_by_admin),
            KEY status (status)
        ) $charset_collate;";
        
        // Table des données BMC
        $table_canvas_data = $wpdb->prefix . 'bmc_canvas_data';
        $sql_canvas_data = "CREATE TABLE $table_canvas_data (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            content text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY section (section)
        ) $charset_collate;";
        
        // Table des demandes de notation
        $table_grading_requests = $wpdb->prefix . 'bmc_grading_requests';
        $sql_grading_requests = "CREATE TABLE $table_grading_requests (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            section_title varchar(100) NOT NULL,
            user_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY section (section),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Table des révisions de sections
        $table_section_revisions = $wpdb->prefix . 'bmc_section_revisions';
        $sql_section_revisions = "CREATE TABLE $table_section_revisions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            content text,
            revision_reason varchar(100) DEFAULT 'manual',
            rating tinyint(2) DEFAULT NULL,
            rating_comment text DEFAULT NULL,
            admin_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY section (section),
            KEY created_at (created_at),
            KEY admin_id (admin_id)
        ) $charset_collate;";
        
        // Table des notifications admin
        $table_admin_notifications = $wpdb->prefix . 'bmc_admin_notifications';
        $sql_admin_notifications = "CREATE TABLE $table_admin_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            admin_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            data text,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY admin_id (admin_id),
            KEY type (type),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        // Table des todos par section
        $table_todos = $wpdb->prefix . 'bmc_todos';
        $sql_todos = "CREATE TABLE $table_todos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            task_text text NOT NULL,
            is_completed tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY section (section),
            KEY is_completed (is_completed)
        ) $charset_collate;";
        
        // Table des relations admin-étudiant
        $table_admin_students = $wpdb->prefix . 'bmc_admin_students';
        $sql_admin_students = "CREATE TABLE $table_admin_students (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            admin_id bigint(20) NOT NULL,
            student_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY admin_student (admin_id, student_id),
            KEY admin_id (admin_id),
            KEY student_id (student_id)
        ) $charset_collate;";
        
        // Table de liaison projet-utilisateurs (nouvelle pour v2.0)
        $table_project_users = $wpdb->prefix . 'bmc_project_users';
        $sql_project_users = "CREATE TABLE $table_project_users (
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
        dbDelta($sql_users);
        dbDelta($sql_projects);
        dbDelta($sql_canvas_data);
        dbDelta($sql_grading_requests);
        dbDelta($sql_section_revisions);
        dbDelta($sql_admin_notifications);
        dbDelta($sql_todos);
        dbDelta($sql_admin_students);
        dbDelta($sql_project_users);
    }
    
        /**
     * Insérer un nouvel utilisateur BMC
     */
    public static function insert_user($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $data['user_id'],
                'email' => $data['email'],
                'password' => wp_hash_password($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Vérifier les identifiants de connexion
     * Accepte soit l'email soit le pseudonyme WordPress
     */
    public static function verify_login($login, $password) {
        global $wpdb;
        
        // D'abord, essayer de trouver l'utilisateur par email dans notre table BMC
        $table = $wpdb->prefix . 'bmc_users';
        
        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE email = %s",
                $login
            )
        );
        
        if ($user && wp_check_password($password, $user->password)) {
            // Vérifier le statut de l'utilisateur
            if ($user->status === 'disabled') {
                return false; // Compte désactivé
            }
            return $user;
        }
        
        // Si pas trouvé par email, essayer par pseudonyme WordPress
        $wp_user = get_user_by('login', $login);
        if ($wp_user) {
            // Vérifier le mot de passe WordPress
            if (wp_check_password($password, $wp_user->user_pass)) {
                // Chercher dans notre table BMC
                $bmc_user = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE user_id = %d",
                        $wp_user->ID
                    )
                );
                
                if ($bmc_user) {
                    // Vérifier le statut de l'utilisateur
                    if ($bmc_user->status === 'disabled') {
                        return false; // Compte désactivé
                    }
                    return $bmc_user;
                }
                
                // Si pas dans BMC mais admin WordPress, créer un objet virtuel
                if (user_can($wp_user->ID, 'manage_options')) {
                    return (object) array(
                        'user_id' => $wp_user->ID,
                        'email' => $wp_user->user_email,
                        'first_name' => $wp_user->first_name ?: 'Admin',
                        'last_name' => $wp_user->last_name ?: 'WordPress',
                        'is_admin' => true
                    );
                }
            }
        }
        
        return false;
    }
    
    /**
     * Créer un nouveau projet (v2.0 - créé par admin)
     */
    public static function create_project($admin_id, $title, $description = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_projects';
        
        $result = $wpdb->insert(
            $table,
            array(
                'title' => $title,
                'description' => $description,
                'status' => 'draft',
                'created_by_admin' => $admin_id
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Mettre à jour un projet
     */
    public static function edit_project($project_id, $title, $description) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_projects';
        $result = $wpdb->update(
            $table,
            array('title' => $title, 'description' => $description),
            array('id' => $project_id),
            array('%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Créer un nouvel utilisateur (v2.0 - créé par admin)
     */
    public static function create_user($admin_id, $data) {
        global $wpdb;
        
        // Créer l'utilisateur WordPress
        $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
        
        if (is_wp_error($user_id)) {
            return false;
        }
        
        // Mettre à jour les informations utilisateur WordPress
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['first_name'] . ' ' . $data['last_name']
        ));
        
        // Insérer dans la table BMC
        $table = $wpdb->prefix . 'bmc_users';
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'custom_id' => $data['custom_id'],
                'email' => $data['email'],
                'password' => $data['password'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'status' => 'pending',
                'created_by_admin' => $admin_id
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Associer un utilisateur à un projet
     */
    public static function assign_user_to_project($project_id, $user_id, $admin_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_project_users';
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => $project_id,
                'user_id' => $user_id,
                'assigned_by_admin' => $admin_id
            ),
            array('%d', '%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Retirer un utilisateur d'un projet
     */
    public static function remove_user_from_project($project_id, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_project_users';
        $result = $wpdb->update(
            $table,
            array('is_active' => 0),
            array(
                'project_id' => $project_id,
                'user_id' => $user_id
            ),
            array('%d'),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Vérifier si un utilisateur a accès à un projet
     */
    public static function user_has_project_access($user_id, $project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_project_users';
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM $table 
            WHERE project_id = %d AND user_id = %d AND is_active = 1
        ", $project_id, $user_id));
        
        return $result > 0;
    }
    
    /**
     * Obtenir les projets d'un utilisateur (v2.0 - via table de liaison)
     */
    public static function get_user_projects($user_id) {
        global $wpdb;
        
        $table_projects = $wpdb->prefix . 'bmc_projects';
        $table_project_users = $wpdb->prefix . 'bmc_project_users';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, pu.assigned_at, pu.is_active as assignment_active
            FROM $table_projects p
            JOIN $table_project_users pu ON p.id = pu.project_id
            WHERE pu.user_id = %d AND pu.is_active = 1
            ORDER BY pu.assigned_at DESC
        ", $user_id));
        
        return $results;
    }
    
    /**
     * Obtenir les utilisateurs d'un projet
     */
    public static function get_project_users($project_id) {
        global $wpdb;
        
        $table_users = $wpdb->prefix . 'bmc_users';
        $table_project_users = $wpdb->prefix . 'bmc_project_users';
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT u.*, pu.assigned_at, pu.assigned_by_admin, pu.is_active as assignment_active
            FROM $table_users u
            JOIN $table_project_users pu ON u.user_id = pu.user_id
            WHERE pu.project_id = %d AND pu.is_active = 1
            ORDER BY pu.assigned_at DESC
        ", $project_id));
        
        return $results;
    }
    
    /**
     * Obtenir tous les projets (pour l'admin)
     */
    public static function get_all_projects() {
        global $wpdb;
        
        $table_projects = $wpdb->prefix . 'bmc_projects';
        $table_users = $wpdb->prefix . 'bmc_users';
        
        $results = $wpdb->get_results("
            SELECT p.*, u.first_name, u.last_name
            FROM $table_projects p
            LEFT JOIN $table_users u ON p.created_by_admin = u.user_id
            ORDER BY p.created_at DESC
        ");
        
        return $results;
    }
    
    /**
     * Obtenir tous les utilisateurs (pour l'admin)
     */
    public static function get_all_users() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        $results = $wpdb->get_results("
            SELECT * FROM $table 
            WHERE is_active = 1
            ORDER BY created_at DESC
        ");
        
        return $results;
    }
    
    /**
     * Obtenir un projet par son ID
     */
    public static function get_project($project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_projects';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $project_id
            )
        );
    }
    
    /**
     * Obtenir un utilisateur par son ID
     */
    public static function get_user($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d",
                $user_id
            )
        );
    }
    
    /**
     * Obtenir un utilisateur BMC par son ID BMC
     */
    public static function get_user_by_id($bmc_user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $bmc_user_id
            )
        );
    }
    
    /**
     * Sauvegarder les données du canvas
     */
    public static function save_canvas_data($project_id, $section, $content) {
        global $wpdb;
        
        // Log de débogage
        error_log('WP_BMC_Database::save_canvas_data - project_id: ' . $project_id . ', section: ' . $section . ', content length: ' . strlen($content));
        
        $table = $wpdb->prefix . 'bmc_canvas_data';
        
        // Vérifier si les données existent déjà
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE project_id = %d AND section = %s",
                $project_id,
                $section
            )
        );
        
        if ($existing) {
            // Mettre à jour
            $result = $wpdb->update(
                $table,
                array('content' => $content),
                array('project_id' => $project_id, 'section' => $section),
                array('%s'),
                array('%d', '%s')
            );
            // Retourner true si pas d'erreur (même si 0 ligne affectée)
            return $result !== false;
        } else {
            // Insérer
            $result = $wpdb->insert(
                $table,
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'content' => $content
                ),
                array('%d', '%s', '%s')
            );
            return $result !== false;
        }
    }
    
    /**
     * Obtenir les données du canvas
     */
    public static function get_canvas_data($project_id) {
        global $wpdb;
        
        // Log de débogage
        error_log('WP_BMC_Database::get_canvas_data - project_id: ' . $project_id);
        
        $table = $wpdb->prefix . 'bmc_canvas_data';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT section, content FROM $table WHERE project_id = %d",
                $project_id
            )
        );
        
        $data = array();
        foreach ($results as $row) {
            $data[$row->section] = $row->content;
            error_log('WP_BMC_Database::get_canvas_data - section: ' . $row->section . ', content length: ' . strlen($row->content));
        }
        
        error_log('WP_BMC_Database::get_canvas_data - total sections found: ' . count($data));
        return $data;
    }
    
    /**
     * Créer les tables pour les fichiers et documents
     */
    public static function create_file_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des fichiers attachés aux sections
        $table_files = $wpdb->prefix . 'bmc_files';
        $sql_files = "CREATE TABLE $table_files (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            original_name varchar(255) NOT NULL,
            filename varchar(255) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_size bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY section (section)
        ) $charset_collate;";
        
        // Table des documents de référence (gérés par les admins)
        $table_documents = $wpdb->prefix . 'bmc_documents';
        $sql_documents = "CREATE TABLE $table_documents (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            filename varchar(255) NOT NULL,
            file_type varchar(100) NOT NULL,
            file_size bigint(20) NOT NULL,
            category varchar(50) DEFAULT 'general',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Table pour les notes des admins
        $table_ratings = $wpdb->prefix . 'bmc_ratings';
        $sql_ratings = "CREATE TABLE $table_ratings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            project_id mediumint(9) NOT NULL,
            section varchar(50) NOT NULL,
            admin_id mediumint(9) NOT NULL,
            rating tinyint(2) NOT NULL CHECK (rating >= 0 AND rating <= 10),
            comment text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY project_section_admin (project_id, section, admin_id),
            KEY project_section (project_id, section),
            KEY admin_id (admin_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_files);
        dbDelta($sql_documents);
        dbDelta($sql_ratings);
    }
    
    /**
     * Obtenir les fichiers d'une section
     */
    public static function get_section_files($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_files';
        
        $files = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE project_id = %d AND section = %s ORDER BY created_at DESC",
                $project_id,
                $section
            )
        );
        
        // Ajouter l'URL pour chaque fichier
        $upload_dir = wp_upload_dir();
        foreach ($files as $file) {
            $file->url = $upload_dir['baseurl'] . '/wp-bmc-files/' . $project_id . '/' . $section . '/' . $file->filename;
        }
        
        return $files;
    }
    
    /**
     * Sauvegarder un fichier
     */
    public static function save_file($project_id, $section, $original_name, $filename, $file_type, $file_size) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_files';
        
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => $project_id,
                'section' => $section,
                'original_name' => $original_name,
                'filename' => $filename,
                'file_type' => $file_type,
                'file_size' => $file_size
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Supprimer un fichier
     */
    public static function delete_file($file_id, $project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_files';
        
        // Vérifier que le fichier appartient au projet
        $file = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND project_id = %d",
                $file_id,
                $project_id
            )
        );
        
        if (!$file) {
            return false;
        }
        
        // Supprimer le fichier physique
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/wp-bmc-files/' . $project_id . '/' . $file->section . '/' . $file->filename;
        
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Supprimer de la base de données
        return $wpdb->delete(
            $table,
            array('id' => $file_id),
            array('%d')
        );
    }
    
    /**
     * Obtenir les documents de référence
     */
    public static function get_reference_documents($section = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_documents';
        
        $where_clause = "WHERE is_active = 1";
        if ($section && $section !== 'all') {
            $where_clause .= $wpdb->prepare(" AND (category = %s OR category = 'all')", $section);
        }
        
        $documents = $wpdb->get_results(
            "SELECT * FROM $table $where_clause ORDER BY category, title"
        );
        
        // Ajouter l'URL pour chaque document
        $upload_dir = wp_upload_dir();
        foreach ($documents as $document) {
            $document->url = $upload_dir['baseurl'] . '/wp-bmc-documents/' . $document->filename;
        }
        
        return $documents;
    }
    
    /**
     * Obtenir la note d'un admin pour une section
     */
    public static function get_section_rating($project_id, $section, $admin_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_ratings';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE project_id = %d AND section = %s AND admin_id = %d",
                $project_id,
                $section,
                $admin_id
            )
        );
    }
    
    /**
     * Obtenir la dernière note pour une section (peu importe l'admin)
     */
    public static function get_latest_section_rating($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_ratings';
        
        $rating = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as admin_name 
                 FROM $table r
                 LEFT JOIN {$wpdb->users} u ON r.admin_id = u.ID
                 WHERE r.project_id = %d AND r.section = %s 
                 ORDER BY r.created_at DESC 
                 LIMIT 1",
                $project_id,
                $section
            )
        );
        
        // Ajouter la date formatée selon les paramètres WordPress
        if ($rating) {
            $rating->formatted_date = self::format_date_for_display($rating->created_at);
        }
        
        return $rating;
    }
    
    /**
     * Sauvegarder ou mettre à jour une note
     */
    public static function save_section_rating($project_id, $section, $admin_id, $rating, $comment = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_ratings';
        
        // Vérifier si une note existe déjà
        $existing_rating = self::get_section_rating($project_id, $section, $admin_id);
        
        if ($existing_rating) {
            // Mettre à jour la note existante
            $result = $wpdb->update(
                $table,
                array(
                    'rating' => $rating,
                    'comment' => $comment,
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'admin_id' => $admin_id
                ),
                array('%d', '%s', '%s'),
                array('%d', '%s', '%d')
            );
            
            // Marquer les demandes de notation comme notées
            if ($result !== false) {
                self::mark_grading_request_as_graded($project_id, $section);
            }
            
            return $result !== false;
        } else {
            // Créer une nouvelle note
            $result = $wpdb->insert(
                $table,
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'admin_id' => $admin_id,
                    'rating' => $rating,
                    'comment' => $comment
                ),
                array('%d', '%s', '%d', '%d', '%s')
            );
            
            // Marquer les demandes de notation comme notées
            if ($result) {
                self::mark_grading_request_as_graded($project_id, $section);
            }
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Obtenir toutes les notes d'un projet
     */
    public static function get_project_ratings($project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_ratings';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as admin_name 
                 FROM $table r
                 LEFT JOIN {$wpdb->users} u ON r.admin_id = u.ID
                 WHERE r.project_id = %d 
                 ORDER BY r.section, r.created_at DESC",
                $project_id
            )
        );
        
        // Ajouter les dates formatées
        if ($results) {
            foreach ($results as $rating) {
                $rating->formatted_date = self::format_date_for_display($rating->created_at);
            }
        }
        
        return $results ?: array();
    }
    
    /**
     * Sauvegarder une demande de notation
     */
    public static function save_grading_request($project_id, $section, $section_title, $user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_grading_requests';
        
        // Vérifier si une demande existe déjà pour cette section
        $existing_request = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE project_id = %d AND section = %s AND status = 'pending'",
                $project_id,
                $section
            )
        );
        
        if ($existing_request) {
            // Mettre à jour la demande existante
            $result = $wpdb->update(
                $table,
                array(
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'status' => 'pending'
                ),
                array('%s'),
                array('%d', '%s', '%s')
            );
            
            return $result !== false;
        } else {
            // Créer une nouvelle demande
            $result = $wpdb->insert(
                $table,
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'section_title' => $section_title,
                    'user_id' => $user_id,
                    'status' => 'pending'
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Marquer une demande de notation comme notée
     */
    public static function mark_grading_request_as_graded($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_grading_requests';
        
        // Mettre à jour le statut de toutes les demandes en attente pour cette section
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'graded',
                'updated_at' => current_time('mysql')
            ),
            array(
                'project_id' => $project_id,
                'section' => $section,
                'status' => 'pending'
            ),
            array('%s', '%s'),
            array('%d', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Sauvegarder une notification admin
     */
    public static function save_admin_notification($admin_id, $type, $message, $data = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_notifications';
        
        $result = $wpdb->insert(
            $table,
            array(
                'admin_id' => $admin_id,
                'type' => $type,
                'message' => $message,
                'data' => json_encode($data),
                'is_read' => 0
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtenir les notifications non lues d'un admin
     */
    public static function get_unread_notifications($admin_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_notifications';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE admin_id = %d AND is_read = 0 ORDER BY created_at DESC",
                $admin_id
            )
        );
    }
    
    /**
     * Marquer une notification comme lue
     */
    public static function mark_notification_read($notification_id, $admin_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_notifications';
        
        return $wpdb->update(
            $table,
            array('is_read' => 1),
            array(
                'id' => $notification_id,
                'admin_id' => $admin_id
            ),
            array('%d'),
            array('%d', '%d')
        );
    }
    
    /**
     * Obtenir les demandes de notation en attente
     */
    public static function get_pending_grading_requests() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_grading_requests';
        
        return $wpdb->get_results(
            "SELECT gr.*, p.title as project_title, 
                    COALESCE(u.display_name, u.user_login) as user_name 
             FROM $table gr 
             JOIN {$wpdb->prefix}bmc_projects p ON gr.project_id = p.id 
             JOIN {$wpdb->users} u ON gr.user_id = u.ID 
             WHERE gr.status = 'pending' 
             ORDER BY gr.created_at DESC"
        );
    }
    
    /**
     * Obtenir tous les utilisateurs avec leurs projets
     */
    public static function get_all_users_with_projects() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT u.ID, u.display_name, u.user_email, u.user_registered,
                    COUNT(p.id) as project_count,
                    MAX(p.updated_at) as last_project_date,
                    CASE 
                        WHEN EXISTS(SELECT 1 FROM {$wpdb->prefix}bmc_grading_requests gr WHERE gr.user_id = u.ID AND gr.status = 'pending') THEN 'pending'
                        WHEN EXISTS(SELECT 1 FROM {$wpdb->prefix}bmc_grading_requests gr WHERE gr.user_id = u.ID AND gr.status = 'graded') THEN 'graded'
                        ELSE 'no-requests'
                    END as grading_status
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->prefix}bmc_projects p ON u.ID = p.user_id
             GROUP BY u.ID
             ORDER BY u.display_name"
        );
    }
    
    /**
     * Obtenir tous les projets (méthode supprimée - doublon)
     */
    
    /**
     * Obtenir toutes les notifications
     */
    public static function get_all_notifications() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_notifications';
        
        return $wpdb->get_results(
            "SELECT n.*, u.display_name as admin_name
             FROM $table n
             JOIN {$wpdb->users} u ON n.admin_id = u.ID
             ORDER BY n.created_at DESC"
        );
    }
    
    // ========================================
    // FONCTIONS DE GESTION DES RÉVISIONS
    // ========================================
    
    /**
     * Créer une révision d'une section avec note et commentaire
     */
    public static function create_section_revision($project_id, $section, $content, $revision_reason = 'manual', $rating = null, $rating_comment = null, $admin_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_section_revisions';
        
        $data = array(
            'project_id' => $project_id,
            'section' => $section,
            'content' => $content,
            'revision_reason' => $revision_reason
        );
        
        $format = array('%d', '%s', '%s', '%s');
        
        // Ajouter les données de notation si fournies
        if ($rating !== null) {
            $data['rating'] = $rating;
            $format[] = '%d';
        }
        
        if ($rating_comment !== null) {
            $data['rating_comment'] = $rating_comment;
            $format[] = '%s';
        }
        
        if ($admin_id !== null) {
            $data['admin_id'] = $admin_id;
            $format[] = '%d';
        }
        
        $result = $wpdb->insert($table, $data, $format);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtenir les révisions d'une section avec les informations admin
     */
    public static function get_section_revisions($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_section_revisions';
        
        $revisions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as admin_name 
                 FROM $table r
                 LEFT JOIN {$wpdb->users} u ON r.admin_id = u.ID
                 WHERE r.project_id = %d AND r.section = %s 
                 ORDER BY r.created_at DESC",
                $project_id,
                $section
            )
        );
        
        // Ajouter les dates formatées selon les paramètres WordPress
        if ($revisions) {
            foreach ($revisions as $revision) {
                $revision->formatted_date = self::format_date_for_display($revision->created_at);
            }
        }
        
        return $revisions;
    }
    
    /**
     * Obtenir une révision spécifique
     */
    public static function get_section_revision($revision_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_section_revisions';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $revision_id
            )
        );
    }
    
    /**
     * Compter le nombre de révisions d'une section
     */
    public static function count_section_revisions($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_section_revisions';
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table 
                 WHERE project_id = %d AND section = %s",
                $project_id,
                $section
            )
        );
    }
    
    // ========================================
    // FONCTIONS DE GESTION DES TODOS
    // ========================================
    
    /**
     * Ajouter une nouvelle tâche à une section
     */
    public static function add_todo($project_id, $section, $task_text) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            self::ensure_todos_table_exists();
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'project_id' => $project_id,
                'section' => $section,
                'task_text' => $task_text,
                'is_completed' => 0
            ),
            array('%d', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtenir toutes les tâches d'une section
     */
    public static function get_section_todos($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE project_id = %d AND section = %s 
                 ORDER BY created_at ASC",
                $project_id,
                $section
            )
        );
    }
    
    /**
     * Obtenir toutes les tâches d'un projet
     */
    public static function get_project_todos($project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            self::ensure_todos_table_exists();
            return array(); // Retourner un tableau vide si la table vient d'être créée
        }
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE project_id = %d 
                 ORDER BY section ASC, created_at ASC",
                $project_id
            )
        );
        
        // Ajouter les dates formatées
        if ($results) {
            foreach ($results as $todo) {
                $todo->formatted_date = self::format_date_for_display($todo->created_at);
            }
        }
        
        return $results ?: array();
    }
    
    /**
     * Marquer une tâche comme terminée ou non terminée
     */
    public static function toggle_todo($todo_id, $project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        // Vérifier que la tâche appartient au projet
        $todo = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND project_id = %d",
                $todo_id,
                $project_id
            )
        );
        
        if (!$todo) {
            return false;
        }
        
        // Basculer l'état de completion
        $new_status = $todo->is_completed ? 0 : 1;
        
        $result = $wpdb->update(
            $table,
            array('is_completed' => $new_status),
            array('id' => $todo_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Supprimer une tâche
     */
    public static function delete_todo($todo_id, $project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        $result = $wpdb->delete(
            $table,
            array(
                'id' => $todo_id,
                'project_id' => $project_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Modifier le texte d'une tâche
     */
    public static function update_todo_text($todo_id, $project_id, $new_text) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        $result = $wpdb->update(
            $table,
            array('task_text' => $new_text),
            array(
                'id' => $todo_id,
                'project_id' => $project_id
            ),
            array('%s'),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Obtenir le nombre de tâches terminées et non terminées pour une section
     */
    public static function get_section_todo_stats($project_id, $section) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN is_completed = 0 THEN 1 ELSE 0 END) as pending
                 FROM $table 
                 WHERE project_id = %d AND section = %s",
                $project_id,
                $section
            )
        );
        
        return $stats;
    }
    
    /**
     * Vérifier et créer la table des todos si elle n'existe pas
     */
    public static function ensure_todos_table_exists() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if (!$table_exists) {
            // Créer la table
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                project_id mediumint(9) NOT NULL,
                section varchar(50) NOT NULL,
                task_text text NOT NULL,
                is_completed tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY project_id (project_id),
                KEY section (section),
                KEY is_completed (is_completed)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Forcer la création de la table des todos (pour debug)
     */
    public static function force_create_todos_table() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_todos';
        
        // Supprimer la table si elle existe
        $wpdb->query("DROP TABLE IF EXISTS $table");
        error_log("Table $table supprimée");
        
        // Recréer la table
        self::ensure_todos_table_exists();
    }
    
    /**
     * Réinitialiser toutes les données du plugin
     * Vide toutes les tables mais conserve la structure
     */
    public static function reset_all_data() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'bmc_section_revisions',
            $wpdb->prefix . 'bmc_todos', 
            $wpdb->prefix . 'bmc_ratings',
            $wpdb->prefix . 'bmc_canvas_data',
            $wpdb->prefix . 'bmc_projects',
            $wpdb->prefix . 'bmc_users'
        );
        
        $total_deleted = 0;
        $results = array();
        
        foreach ($tables as $table) {
            // Vérifier si la table existe
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            
            if ($table_exists) {
                // Compter les enregistrements avant suppression
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                
                // Vider la table
                $result = $wpdb->query("TRUNCATE TABLE $table");
                
                if ($result !== false) {
                    $total_deleted += $count;
                    $results[] = "Table " . str_replace($wpdb->prefix, '', $table) . " : $count enregistrements supprimés";
                    error_log("wp_bmc_reset_data - Table $table : $count enregistrements supprimés");
                } else {
                    $results[] = "Erreur lors de la suppression de la table " . str_replace($wpdb->prefix, '', $table);
                    error_log("wp_bmc_reset_data - Erreur lors de la suppression de la table $table");
                }
            } else {
                $results[] = "Table " . str_replace($wpdb->prefix, '', $table) . " : n'existe pas";
            }
        }
        
        error_log("wp_bmc_reset_data - Total : $total_deleted enregistrements supprimés");
        
        return array(
            'total_deleted' => $total_deleted,
            'details' => $results
        );
    }
    
    // ========================================
    // MÉTHODES POUR LA GESTION DES GROUPES ADMIN-ÉTUDIANT
    // ========================================
    
    /**
     * Assigner un étudiant à un admin
     */
    public static function assign_student_to_admin($admin_id, $student_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_students';
        
        // Vérifier si la relation existe déjà
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE admin_id = %d AND student_id = %d",
                $admin_id, $student_id
            )
        );
        
        if ($existing) {
            return false; // Relation déjà existante
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'admin_id' => $admin_id,
                'student_id' => $student_id
            ),
            array('%d', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Retirer un étudiant d'un admin
     */
    public static function remove_student_from_admin($admin_id, $student_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_students';
        
        $result = $wpdb->delete(
            $table,
            array(
                'admin_id' => $admin_id,
                'student_id' => $student_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Obtenir tous les étudiants d'un admin
     */
    public static function get_admin_students($admin_id) {
        global $wpdb;
        
        $table_admin_students = $wpdb->prefix . 'bmc_admin_students';
        $table_users = $wpdb->prefix . 'bmc_users';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT u.*, as_rel.created_at as assigned_at
                 FROM $table_users u
                 JOIN $table_admin_students as_rel ON u.user_id = as_rel.student_id
                 WHERE as_rel.admin_id = %d
                 ORDER BY as_rel.created_at DESC",
                $admin_id
            )
        );
    }
    
    /**
     * Obtenir tous les admins d'un étudiant
     */
    public static function get_student_admins($student_id) {
        global $wpdb;
        
        $table_admin_students = $wpdb->prefix . 'bmc_admin_students';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT as_rel.admin_id, as_rel.created_at as assigned_at
                 FROM $table_admin_students as_rel
                 WHERE as_rel.student_id = %d
                 ORDER BY as_rel.created_at DESC",
                $student_id
            )
        );
    }
    
    /**
     * Vérifier si un étudiant est assigné à un admin
     */
    public static function is_student_assigned_to_admin($admin_id, $student_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_students';
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE admin_id = %d AND student_id = %d",
                $admin_id, $student_id
            )
        );
        
        return $result > 0;
    }
    
    /**
     * Obtenir les IDs des étudiants d'un admin
     */
    public static function get_admin_student_ids($admin_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_admin_students';
        
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT student_id FROM $table WHERE admin_id = %d",
                $admin_id
            )
        );
        
        return $results ?: array();
    }
    
    /**
     * Mettre à jour le statut d'un utilisateur
     */
    public static function update_user_status($user_id, $status) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        $result = $wpdb->update(
            $table,
            array('status' => $status),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }

}
