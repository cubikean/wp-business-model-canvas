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
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            company varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Table des projets BMC
        $table_projects = $wpdb->prefix . 'bmc_projects';
        $sql_projects = "CREATE TABLE $table_projects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_users);
        dbDelta($sql_projects);
        dbDelta($sql_canvas_data);
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
                'last_name' => $data['last_name'],
                'company' => isset($data['company']) ? $data['company'] : ''
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Vérifier les identifiants de connexion
     */
    public static function verify_login($email, $password) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_users';
        
        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE email = %s",
                $email
            )
        );
        
        if ($user && wp_check_password($password, $user->password)) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Créer un nouveau projet
     */
    public static function create_project($user_id, $title, $description = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_projects';
        
        $result = $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'status' => 'draft'
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Obtenir les projets d'un utilisateur
     */
    public static function get_user_projects($user_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_projects';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            )
        );
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
     * Sauvegarder les données du canvas
     */
    public static function save_canvas_data($project_id, $section, $content) {
        global $wpdb;
        
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
            return $wpdb->update(
                $table,
                array('content' => $content),
                array('project_id' => $project_id, 'section' => $section),
                array('%s'),
                array('%d', '%s')
            );
        } else {
            // Insérer
            return $wpdb->insert(
                $table,
                array(
                    'project_id' => $project_id,
                    'section' => $section,
                    'content' => $content
                ),
                array('%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Obtenir les données du canvas
     */
    public static function get_canvas_data($project_id) {
        global $wpdb;
        
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
        }
        
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
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Obtenir toutes les notes d'un projet
     */
    public static function get_project_ratings($project_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'bmc_ratings';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE project_id = %d ORDER BY section, created_at DESC",
                $project_id
            )
        );
    }
}
