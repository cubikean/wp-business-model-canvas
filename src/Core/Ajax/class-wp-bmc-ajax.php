<?php
/**
 * Handlers AJAX pour WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// CONFIGURATION DES RÉVISIONS
// ========================================

/**
 * Mode de création des révisions :
 * 
 * MODE 1 (false) : Révision créée uniquement lors de la notation par un admin
 *   - Une révision est créée quand l'admin note une section
 *   - revision_reason = 'admin_rating'
 *   - Le contenu de la section au moment de la notation est sauvegardé
 *   - TOUJOURS ACTIF (indépendant de la configuration ci-dessous)
 * 
 * MODE 2 (true) : Révision créée lors de la modification du contenu par un admin
 *   - Une révision est créée quand l'admin modifie et sauvegarde le contenu du WYSIWYG
 *   - revision_reason = 'admin_edit'
 *   - La note existante (si elle existe) est conservée dans la révision
 *   - Le commentaire est préfixé par "[Modification de contenu]" ou créé automatiquement
 *   - Le nouveau contenu modifié est sauvegardé
 *   - Utile pour suivre l'historique des modifications de l'admin
 * 
 * IMPORTANT :
 * - Les deux modes peuvent coexister (MODE 1 toujours actif + MODE 2 optionnel)
 * - MODE 1 : Révision avec le contenu au moment de la notation
 * - MODE 2 : Révision à chaque modification de contenu par l'admin
 * 
 * Exemple de workflow avec MODE 2 activé :
 * 1. Admin note la section (10/10) avec commentaire "Très bien" 
 *    → Révision créée (admin_rating) : note 10/10, commentaire "Très bien"
 * 2. Admin modifie le contenu 
 *    → Révision créée (admin_edit) : note 10/10, commentaire "[Modification de contenu] Très bien"
 * 3. Admin re-note la section (8/10) avec commentaire "Quelques corrections" 
 *    → Révision créée (admin_rating) : note 8/10, commentaire "Quelques corrections"
 * 4. Admin re-modifie le contenu 
 *    → Révision créée (admin_edit) : note 8/10, commentaire "[Modification de contenu] Quelques corrections"
 */
define('WP_BMC_REVISION_ON_ADMIN_EDIT', true); // Mettre à true pour activer le MODE 2

// Handler pour ajouter un étudiant à un admin
add_action('wp_ajax_wp_bmc_add_student', 'wp_bmc_add_student_handler');
function wp_bmc_add_student_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes.');
    }
    
    $student_id = intval($_POST['student_id']);
    $admin_id = get_current_user_id();
    
    if (!$student_id) {
        wp_send_json_error('ID étudiant invalide.');
    }
    
    $result = WP_BMC_Database::assign_student_to_admin($admin_id, $student_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Étudiant ajouté avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'ajout de l\'étudiant.');
    }
}

// Handler pour retirer un étudiant d'un admin
add_action('wp_ajax_wp_bmc_remove_student', 'wp_bmc_remove_student_handler');
function wp_bmc_remove_student_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes.');
    }
    
    $student_id = intval($_POST['student_id']);
    $admin_id = get_current_user_id();
    
    if (!$student_id) {
        wp_send_json_error('ID étudiant invalide.');
    }
    
    $result = WP_BMC_Database::remove_student_from_admin($admin_id, $student_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Étudiant retiré avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression de l\'étudiant.');
    }
}

// Handler pour créer un nouveau projet
// Handler pour créer un projet (v2.0 - admin seulement)
add_action('wp_ajax_wp_bmc_create_project', 'wp_bmc_create_project_handler');
function wp_bmc_create_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    
    if (empty($title)) {
        wp_send_json_error('Le titre du projet est obligatoire.');
    }
    
    $admin_id = get_current_user_id();
    $project_id = WP_BMC_Database::create_project($admin_id, $title, $description);
    
    if ($project_id) {
        wp_send_json_success(array(
            'message' => 'Projet créé avec succès !',
            'project_id' => $project_id
        ));
    } else {
        wp_send_json_error('Erreur lors de la création du projet.');
    }
}

// Handler pour créer un utilisateur (v2.0 - admin seulement)
add_action('wp_ajax_wp_bmc_create_user', 'wp_bmc_create_user_handler');
function wp_bmc_create_user_handler() {
    error_log('=== wp_bmc_create_user_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $custom_id = sanitize_text_field($_POST['custom_id']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    
    // Vérifier si l'envoi d'email est activé (même comportement que l'import CSV)
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === '1';
    
    if (empty($custom_id) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        wp_send_json_error('Tous les champs obligatoires doivent être remplis.');
    }
    
    // Vérifier si l'email existe déjà
    if (email_exists($email)) {
        wp_send_json_error('Cette adresse email est déjà utilisée.');
    }
    
    // Vérifier si l'ID personnalisé existe déjà
    global $wpdb;
    $table = $wpdb->prefix . 'bmc_users';
    $existing_user = $wpdb->get_row($wpdb->prepare("SELECT email, first_name, last_name FROM $table WHERE custom_id = %s", $custom_id));
    if ($existing_user) {
        wp_send_json_error('Cet ID personnalisé est déjà utilisé par ' . $existing_user->first_name . ' ' . $existing_user->last_name . ' (' . $existing_user->email . ').');
    }
    
    $admin_id = get_current_user_id();
    $user_data = array(
        'custom_id' => $custom_id,
        'email' => $email,
        'password' => $password,
        'first_name' => $first_name,
        'last_name' => $last_name
    );
    
    $result = WP_BMC_Database::create_user($admin_id, $user_data);
    
    if ($result) {
        error_log('email: ' . $email);
        error_log('custom_id: ' . $custom_id);
        error_log('password: ' . $password);
        
        // Envoyer l'email seulement si l'option est activée
        if ($send_email) {
            wp_bmc_send_user_welcome_email($email, $first_name, $last_name, $password, $custom_id);
        }
        
        error_log("Utilisateur créé avec succès : $email (ID: $custom_id)" . ($send_email ? ' - Email envoyé' : ' - Email non envoyé'));
        
        wp_send_json_success(array(
            'message' => 'Utilisateur créé avec succès !' . ($send_email ? ' Email envoyé.' : ' Email non envoyé.'),
            'user_id' => $result,
            'email_sent' => $send_email
        ));
    } else {
        wp_send_json_error('Erreur lors de la création de l\'utilisateur.');
    }
}

// Handler pour importer des utilisateurs via CSV
add_action('wp_ajax_wp_bmc_import_csv_users', 'wp_bmc_import_csv_users_handler');
function wp_bmc_import_csv_users_handler() {
    error_log('=== wp_bmc_import_csv_users_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    // Vérifier qu'un fichier a été uploadé
    if (empty($_FILES['csv_file'])) {
        wp_send_json_error('Aucun fichier n\'a été uploadé.');
    }
    
    $file = $_FILES['csv_file'];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Erreur lors de l\'upload du fichier.');
    }
    
    // Vérifier l'extension du fichier
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'csv') {
        wp_send_json_error('Le fichier doit être au format CSV.');
    }
    
    // Ouvrir et lire le fichier CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        wp_send_json_error('Impossible de lire le fichier CSV.');
    }
    
    // Lire la première ligne (en-têtes)
    $headers = fgetcsv($handle, 1000, ',');
    if ($headers === false) {
        fclose($handle);
        wp_send_json_error('Le fichier CSV est vide ou mal formaté.');
    }
    
    // Nettoyer les en-têtes (supprimer BOM UTF-8 si présent)
    $headers = array_map(function($header) {
        return trim(str_replace("\xEF\xBB\xBF", '', $header));
    }, $headers);
    
    error_log('En-têtes CSV détectés: ' . print_r($headers, true));
    
    // Trouver les indices des colonnes nécessaires
    $prenom_index = array_search('Prénom', $headers);
    $nom_index = array_search('Nom', $headers);
    $email_index = array_search('E-mail', $headers);
    $candidature_index = array_search('Candidature', $headers);
    
    // Vérifier que toutes les colonnes nécessaires sont présentes
    if ($prenom_index === false || $nom_index === false || $email_index === false || $candidature_index === false) {
        fclose($handle);
        error_log('Colonnes manquantes. Prenom: ' . $prenom_index . ', Nom: ' . $nom_index . ', Email: ' . $email_index . ', Candidature: ' . $candidature_index);
        wp_send_json_error('Le fichier CSV doit contenir les colonnes : Prénom, Nom, E-mail, Candidature.');
    }
    
    $admin_id = get_current_user_id();
    $created_users = array();
    $errors = array();
    $skipped = 0;
    $line_number = 1;
    
    // Vérifier si l'envoi d'emails est activé
    $send_emails = isset($_POST['send_emails']) && $_POST['send_emails'] === '1';
    error_log('Envoi d\'emails : ' . ($send_emails ? 'OUI' : 'NON'));
    
    // Lire les données ligne par ligne
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $line_number++;
        
        // Ignorer les lignes vides
        if (empty(array_filter($data))) {
            $skipped++;
            continue;
        }
        
        // Extraire les données
        $first_name = isset($data[$prenom_index]) ? sanitize_text_field(trim($data[$prenom_index])) : '';
        $last_name = isset($data[$nom_index]) ? sanitize_text_field(trim($data[$nom_index])) : '';
        $email = isset($data[$email_index]) ? sanitize_email(trim($data[$email_index])) : '';
        $custom_id = isset($data[$candidature_index]) ? sanitize_text_field(trim($data[$candidature_index])) : '';
        
        // Vérifier que les champs obligatoires sont remplis
        if (empty($first_name) || empty($last_name) || empty($email) || empty($custom_id)) {
            $errors[] = "Ligne $line_number : Champs manquants (Prénom: '$first_name', Nom: '$last_name', Email: '$email', Candidature: '$custom_id')";
            continue;
        }
        
        // Générer le mot de passe : Candidature + Prénom
        $password = $custom_id . $first_name;
        
        // Vérifier si l'email existe déjà
        if (email_exists($email)) {
            $errors[] = "Ligne $line_number : L'email '$email' existe déjà";
            continue;
        }
        
        // Vérifier si l'ID personnalisé existe déjà
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_users';
        $existing_user = $wpdb->get_row($wpdb->prepare("SELECT email, first_name, last_name FROM $table WHERE custom_id = %s", $custom_id));
        if ($existing_user) {
            $errors[] = "Ligne $line_number : L'ID personnalisé '$custom_id' existe déjà (utilisé par {$existing_user->first_name} {$existing_user->last_name} - {$existing_user->email}). Utilisateur ignoré : $email";
            continue;
        }
        
        // Créer l'utilisateur
        $user_data = array(
            'custom_id' => $custom_id,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name
        );
        
        $result = WP_BMC_Database::create_user($admin_id, $user_data);
        
        if ($result) {
            $created_users[] = array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'custom_id' => $custom_id,
                'password' => $password
            );
            
            // Envoyer l'email seulement si l'option est activée
            if ($send_emails) {
                wp_bmc_send_user_welcome_email($email, $first_name, $last_name, $password, $custom_id);
            }
            
            error_log("Utilisateur créé avec succès : $email (ID: $custom_id)" . ($send_emails ? ' - Email envoyé' : ' - Email non envoyé'));
        } else {
            $errors[] = "Ligne $line_number : Erreur lors de la création de l'utilisateur '$email'";
        }
    }
    
    fclose($handle);
    
    $created_count = count($created_users);
    $error_count = count($errors);
    
    error_log("Import CSV terminé. Créés: $created_count, Ignorés: $skipped, Erreurs: $error_count");
    
    wp_send_json_success(array(
        'message' => "$created_count utilisateur(s) créé(s) avec succès !",
        'created' => $created_count,
        'skipped' => $skipped,
        'errors' => $errors,
        'created_users' => $created_users
    ));
}

// Handler pour importer des superviseurs via CSV
add_action('wp_ajax_wp_bmc_import_csv_supervisors', 'wp_bmc_import_csv_supervisors_handler');
function wp_bmc_import_csv_supervisors_handler() {
    error_log('=== wp_bmc_import_csv_supervisors_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    // Vérifier qu'un fichier a été uploadé
    if (empty($_FILES['csv_file'])) {
        wp_send_json_error('Aucun fichier n\'a été uploadé.');
    }
    
    $file = $_FILES['csv_file'];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Erreur lors de l\'upload du fichier.');
    }
    
    // Vérifier l'extension du fichier
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'csv') {
        wp_send_json_error('Le fichier doit être au format CSV.');
    }
    
    // Ouvrir et lire le fichier CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        wp_send_json_error('Impossible de lire le fichier CSV.');
    }
    
    // Lire la première ligne (en-têtes)
    $headers = fgetcsv($handle, 1000, ',');
    if ($headers === false) {
        fclose($handle);
        wp_send_json_error('Le fichier CSV est vide ou mal formaté.');
    }
    
    // Nettoyer les en-têtes (supprimer BOM UTF-8 si présent)
    $headers = array_map(function($header) {
        return trim(str_replace("\xEF\xBB\xBF", '', $header));
    }, $headers);
    
    error_log('En-têtes CSV superviseurs détectés: ' . print_r($headers, true));
    
    // Trouver les indices des colonnes nécessaires
    $tuteur_index = array_search('Tuteur', $headers);
    $email_index = array_search('Coordonnées du tuteur', $headers);
    
    // Vérifier que toutes les colonnes nécessaires sont présentes
    if ($tuteur_index === false || $email_index === false) {
        fclose($handle);
        error_log('Colonnes manquantes. Tuteur: ' . $tuteur_index . ', Email: ' . $email_index);
        wp_send_json_error('Le fichier CSV doit contenir les colonnes : Tuteur, Coordonnées du tuteur.');
    }
    
    $created_supervisors = array();
    $errors = array();
    $skipped = 0;
    $line_number = 1;
    
    // Vérifier si l'envoi d'emails est activé
    $send_emails = isset($_POST['send_emails']) && $_POST['send_emails'] === '1';
    error_log('Envoi d\'emails superviseurs : ' . ($send_emails ? 'OUI' : 'NON'));
    
    // Lire les données ligne par ligne
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $line_number++;
        
        // Ignorer les lignes vides
        if (empty(array_filter($data))) {
            $skipped++;
            continue;
        }
        
        // Extraire les données
        $tuteur = isset($data[$tuteur_index]) ? sanitize_text_field(trim($data[$tuteur_index])) : '';
        $email = isset($data[$email_index]) ? sanitize_email(trim($data[$email_index])) : '';
        
        // Vérifier que les champs obligatoires sont remplis
        if (empty($tuteur) || empty($email)) {
            $errors[] = "Ligne $line_number : Champs manquants (Tuteur: '$tuteur', Email: '$email')";
            continue;
        }
        
        // Séparer le prénom et le nom
        $name_parts = explode(' ', $tuteur, 2);
        $first_name = isset($name_parts[0]) ? sanitize_text_field($name_parts[0]) : '';
        $last_name = isset($name_parts[1]) ? sanitize_text_field($name_parts[1]) : '';
        
        if (empty($first_name)) {
            $errors[] = "Ligne $line_number : Impossible de séparer le prénom du nom dans '$tuteur'";
            continue;
        }
        
        // Si pas de nom de famille, utiliser le prénom comme nom
        if (empty($last_name)) {
            $last_name = $first_name;
        }
        
        // Générer le mot de passe : Prénom + 6 caractères aléatoires (lettres et chiffres)
        $random_chars = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $password = $first_name . $random_chars;
        
        // Vérifier si l'email existe déjà
        if (email_exists($email)) {
            $errors[] = "Ligne $line_number : L'email '$email' existe déjà";
            continue;
        }
        
        // Générer un nom d'utilisateur unique basé sur le prénom et nom
        $username = sanitize_user(strtolower($first_name . '.' . $last_name));
        $original_username = $username;
        $counter = 1;
        
        // Vérifier si le nom d'utilisateur existe déjà et en créer un unique
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Créer l'utilisateur WordPress avec le rôle administrator
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $errors[] = "Ligne $line_number : Erreur lors de la création du superviseur '$email' - " . $user_id->get_error_message();
            continue;
        }
        
        // Mettre à jour les informations utilisateur et définir le rôle administrator
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => 'administrator'
        ));
        
        $created_supervisors[] = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'username' => $username,
            'password' => $password
        );
       
        // Envoyer l'email seulement si l'option est activée
        if ($send_emails) {
            wp_bmc_send_supervisor_welcome_email($email, $first_name, $last_name, $username, $password);
        }
        
        error_log("Superviseur créé avec succès : $email (Username: $username)" . ($send_emails ? ' - Email envoyé' : ' - Email non envoyé'));
    }
    
    fclose($handle);
    
    $created_count = count($created_supervisors);
    $error_count = count($errors);
    
    error_log("Import CSV superviseurs terminé. Créés: $created_count, Ignorés: $skipped, Erreurs: $error_count");
        
        wp_send_json_success(array(
        'message' => "$created_count superviseur(s) créé(s) avec succès !",
        'created' => $created_count,
        'skipped' => $skipped,
        'errors' => $errors,
        'created_supervisors' => $created_supervisors
    ));
}

// Handler pour import CSV complet (unifié) - Utilisateurs + Superviseurs + Projets
add_action('wp_ajax_wp_bmc_import_csv_complete', 'wp_bmc_import_csv_complete_handler');
function wp_bmc_import_csv_complete_handler() {
    error_log('=== wp_bmc_import_csv_complete_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    // Vérifier qu'un fichier a été uploadé
    if (empty($_FILES['csv_file'])) {
        wp_send_json_error('Aucun fichier n\'a été uploadé.');
    }
    
    $file = $_FILES['csv_file'];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Erreur lors de l\'upload du fichier.');
    }
    
    // Vérifier l'extension du fichier
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'csv') {
        wp_send_json_error('Le fichier doit être au format CSV.');
    }
    
    // Ouvrir et lire le fichier CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        wp_send_json_error('Impossible de lire le fichier CSV.');
    }
    
    // Lire la première ligne (en-têtes)
    $headers = fgetcsv($handle, 1000, ',');
    if ($headers === false) {
        fclose($handle);
        wp_send_json_error('Le fichier CSV est vide ou mal formaté.');
    }
    
    // Nettoyer les en-têtes (supprimer BOM UTF-8 si présent)
    $headers = array_map(function($header) {
        return trim(str_replace("\xEF\xBB\xBF", '', $header));
    }, $headers);
    
    error_log('En-têtes CSV complet détectés: ' . print_r($headers, true));
    
    // Trouver les indices des colonnes
    $prenom_index = array_search('Prénom', $headers);
    $nom_index = array_search('Nom', $headers);
    $email_index = array_search('E-mail', $headers);
    $candidature_index = array_search('Candidature', $headers);
    $tuteur_index = array_search('Tuteur', $headers);
    $tuteur_email_index = array_search('Coordonnées du tuteur', $headers);
    $nom_projet_index = array_search('Nom du projet', $headers);
    $resume_projet_index = array_search('Résumé du projet', $headers);
    
    // Vérifier que toutes les colonnes obligatoires sont présentes
    $missing_columns = array();
    if ($prenom_index === false) $missing_columns[] = 'Prénom';
    if ($nom_index === false) $missing_columns[] = 'Nom';
    if ($email_index === false) $missing_columns[] = 'E-mail';
    if ($candidature_index === false) $missing_columns[] = 'Candidature';
    if ($tuteur_index === false) $missing_columns[] = 'Tuteur';
    if ($tuteur_email_index === false) $missing_columns[] = 'Coordonnées du tuteur';
    if ($nom_projet_index === false) $missing_columns[] = 'Nom du projet';
    
    if (!empty($missing_columns)) {
        fclose($handle);
        wp_send_json_error('Colonnes manquantes dans le CSV : ' . implode(', ', $missing_columns));
    }
    
    $admin_id = get_current_user_id();
    global $wpdb;
    
    // Vérifier si l'envoi d'emails est activé
    $send_emails = isset($_POST['send_emails']) && $_POST['send_emails'] === '1';
    error_log('Envoi d\'emails complet : ' . ($send_emails ? 'OUI' : 'NON'));
    
    // Compteurs et tableaux de stockage
    $users_created = 0;
    $supervisors_created = 0;
    $projects_created = 0;
    $assignments_count = 0;
    $errors = array();
    $supervisors_cache = array(); // Cache des superviseurs déjà créés
    $line_number = 1;
    
    // Lire toutes les lignes
    $all_rows = array();
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $line_number++;
        if (empty(array_filter($data))) continue;
        $all_rows[] = array('line' => $line_number, 'data' => $data);
    }
    fclose($handle);
    
    error_log("Import complet : " . count($all_rows) . " lignes à traiter");
    
    // PHASE 1 : Créer tous les utilisateurs
    error_log("=== PHASE 1 : Création des utilisateurs ===");
    foreach ($all_rows as $row) {
        $line_number = $row['line'];
        $data = $row['data'];
        
        $first_name = isset($data[$prenom_index]) ? sanitize_text_field(trim($data[$prenom_index])) : '';
        $last_name = isset($data[$nom_index]) ? sanitize_text_field(trim($data[$nom_index])) : '';
        $email = isset($data[$email_index]) ? sanitize_email(trim($data[$email_index])) : '';
        $custom_id = isset($data[$candidature_index]) ? sanitize_text_field(trim($data[$candidature_index])) : '';
        
        if (empty($first_name) || empty($last_name) || empty($email) || empty($custom_id)) {
            continue; // Silencieux pour les utilisateurs
        }
        
        // Vérifier si existe déjà
        if (email_exists($email)) {
            continue; // Silencieux
        }
        
        $table = $wpdb->prefix . 'bmc_users';
        $existing_custom_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE custom_id = %s", $custom_id));
        if ($existing_custom_id) {
            continue; // Silencieux
        }
        
        // Créer l'utilisateur
        $password = $custom_id . $first_name;
        $user_data = array(
            'custom_id' => $custom_id,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name
        );
        
        $result = WP_BMC_Database::create_user($admin_id, $user_data);
        
        if ($result) {
            $users_created++;
            
            // Envoyer email seulement si l'option est activée
            if ($send_emails) {
                wp_bmc_send_user_welcome_email($email, $first_name, $last_name, $password, $custom_id);
            }
            
            error_log("Ligne $line_number : Utilisateur créé - $email" . ($send_emails ? ' - Email envoyé' : ' - Email non envoyé'));
        }
    }
    
    // PHASE 2 : Créer tous les superviseurs (sans doublons)
    error_log("=== PHASE 2 : Création des superviseurs ===");
    foreach ($all_rows as $row) {
        $line_number = $row['line'];
        $data = $row['data'];
        
        $tuteur = isset($data[$tuteur_index]) ? sanitize_text_field(trim($data[$tuteur_index])) : '';
        $supervisor_email = isset($data[$tuteur_email_index]) ? sanitize_email(trim($data[$tuteur_email_index])) : '';
        
        if (empty($tuteur) || empty($supervisor_email)) {
            continue;
        }
        
        // Vérifier si déjà créé dans cette session
        if (isset($supervisors_cache[$supervisor_email])) {
            continue;
        }
        
        // Vérifier si existe déjà
        if (email_exists($supervisor_email)) {
            $supervisors_cache[$supervisor_email] = true;
            continue;
        }
        
        // Séparer prénom et nom
        $name_parts = explode(' ', $tuteur, 2);
        $first_name = isset($name_parts[0]) ? sanitize_text_field($name_parts[0]) : '';
        $last_name = isset($name_parts[1]) ? sanitize_text_field($name_parts[1]) : $first_name;
        
        if (empty($first_name)) continue;
        
        // Générer mot de passe et username
        $random_chars = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $password = $first_name . $random_chars;
        
        $username = sanitize_user(strtolower($first_name . '.' . $last_name));
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Créer le superviseur
        $user_id = wp_create_user($username, $password, $supervisor_email);
        
        if (!is_wp_error($user_id)) {
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
                'role' => 'administrator'
            ));
            
            $supervisors_created++;
            $supervisors_cache[$supervisor_email] = true;
            
            // Envoyer email seulement si l'option est activée
            if ($send_emails) {
                wp_bmc_send_supervisor_welcome_email($supervisor_email, $first_name, $last_name, $username, $password);
            }
            
            error_log("Ligne $line_number : Superviseur créé - $supervisor_email" . ($send_emails ? ' - Email envoyé' : ' - Email non envoyé'));
        }
    }
    
    // PHASE 3 : Créer tous les projets et assigner
    error_log("=== PHASE 3 : Création des projets et assignations ===");
    $projects_cache = array(); // Cache pour éviter de recréer les mêmes projets
    
    foreach ($all_rows as $row) {
        $line_number = $row['line'];
        $data = $row['data'];
        
        $project_title = isset($data[$nom_projet_index]) ? sanitize_text_field(trim($data[$nom_projet_index])) : '';
        $project_description = ($resume_projet_index !== false && isset($data[$resume_projet_index])) ? sanitize_textarea_field(trim($data[$resume_projet_index])) : '';
        $user_email = isset($data[$email_index]) ? sanitize_email(trim($data[$email_index])) : '';
        $supervisor_email = isset($data[$tuteur_email_index]) ? sanitize_email(trim($data[$tuteur_email_index])) : '';
        
        if (empty($project_title)) {
            $errors[] = "Ligne $line_number : Titre du projet manquant";
            continue;
        }
        
        // Vérifier si le projet existe déjà (dans le cache ou en base)
        if (isset($projects_cache[$project_title])) {
            // Projet déjà créé dans cette session d'import
            $project_id = $projects_cache[$project_title];
            error_log("Ligne $line_number : Projet existant (cache) - '$project_title' (ID: $project_id)");
        } else {
            // Vérifier si le projet existe déjà en base de données
            $existing_project = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}bmc_projects WHERE title = %s",
                $project_title
            ));
            
            if ($existing_project) {
                // Le projet existe déjà, on le réutilise
                $project_id = $existing_project->id;
                $projects_cache[$project_title] = $project_id;
                error_log("Ligne $line_number : Projet existant (BDD) - '$project_title' (ID: $project_id)");
            } else {
                // Créer le nouveau projet
                $project_id = WP_BMC_Database::create_project($admin_id, $project_title, $project_description);
                
                if (!$project_id) {
                    $errors[] = "Ligne $line_number : Erreur lors de la création du projet '$project_title'";
                    continue;
                }
                
                $projects_created++;
                $projects_cache[$project_title] = $project_id;
                error_log("Ligne $line_number : Projet créé - '$project_title' (ID: $project_id)");
            }
        }
        
        // Assigner l'utilisateur au projet
        if (!empty($user_email)) {
            $bmc_user = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}bmc_users WHERE email = %s",
                $user_email
            ));
            
            if ($bmc_user) {
                $assigned = WP_BMC_Database::assign_user_to_project($project_id, $bmc_user->user_id, $admin_id);
                if ($assigned) {
                    $assignments_count++;
                    error_log("Ligne $line_number : Utilisateur '$user_email' assigné au projet");
    } else {
                    $errors[] = "Ligne $line_number : Erreur assignation utilisateur '$user_email' au projet '$project_title'";
                }
            } else {
                $errors[] = "Ligne $line_number : Utilisateur '$user_email' non trouvé pour le projet '$project_title'";
            }
        }
        
        // Assigner le superviseur au projet
        if (!empty($supervisor_email)) {
            $supervisor = get_user_by('email', $supervisor_email);
            
            if ($supervisor && in_array('administrator', $supervisor->roles)) {
                $assigned = WP_BMC_Database::assign_supervisor_to_project($project_id, $supervisor->ID);
                if ($assigned) {
                    $assignments_count++;
                    error_log("Ligne $line_number : Superviseur '$supervisor_email' assigné au projet");
                } else {
                    $errors[] = "Ligne $line_number : Erreur assignation superviseur '$supervisor_email' au projet '$project_title'";
                }
            } else {
                $errors[] = "Ligne $line_number : Superviseur '$supervisor_email' non trouvé pour le projet '$project_title'";
            }
        }
    }
    
    error_log("Import complet terminé. Users: $users_created, Supervisors: $supervisors_created, Projects: $projects_created, Assignations: $assignments_count");
    
    $success_message = "Import complet terminé ! $users_created utilisateur(s), $supervisors_created superviseur(s), $projects_created projet(s) créés.";
    
    wp_send_json_success(array(
        'message' => $success_message,
        'users_created' => $users_created,
        'supervisors_created' => $supervisors_created,
        'projects_created' => $projects_created,
        'assignments_count' => $assignments_count,
        'errors' => $errors
    ));
}

// Handler pour importer des projets via CSV
add_action('wp_ajax_wp_bmc_import_csv_projects', 'wp_bmc_import_csv_projects_handler');
function wp_bmc_import_csv_projects_handler() {
    error_log('=== wp_bmc_import_csv_projects_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    // Vérifier qu'un fichier a été uploadé
    if (empty($_FILES['csv_file'])) {
        wp_send_json_error('Aucun fichier n\'a été uploadé.');
    }
    
    $file = $_FILES['csv_file'];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Erreur lors de l\'upload du fichier.');
    }
    
    // Vérifier l'extension du fichier
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_extension !== 'csv') {
        wp_send_json_error('Le fichier doit être au format CSV.');
    }
    
    // Ouvrir et lire le fichier CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        wp_send_json_error('Impossible de lire le fichier CSV.');
    }
    
    // Lire la première ligne (en-têtes)
    $headers = fgetcsv($handle, 1000, ',');
    if ($headers === false) {
        fclose($handle);
        wp_send_json_error('Le fichier CSV est vide ou mal formaté.');
    }
    
    // Nettoyer les en-têtes (supprimer BOM UTF-8 si présent)
    $headers = array_map(function($header) {
        return trim(str_replace("\xEF\xBB\xBF", '', $header));
    }, $headers);
    
    error_log('En-têtes CSV projets détectés: ' . print_r($headers, true));
    
    // Trouver les indices des colonnes nécessaires
    $nom_projet_index = array_search('Nom du projet', $headers);
    $resume_projet_index = array_search('Résumé du projet', $headers);
    $email_index = array_search('E-mail', $headers);
    $tuteur_email_index = array_search('Coordonnées du tuteur', $headers);
    
    // Vérifier que toutes les colonnes nécessaires sont présentes
    if ($nom_projet_index === false || $resume_projet_index === false) {
        fclose($handle);
        error_log('Colonnes manquantes. Nom du projet: ' . $nom_projet_index . ', Résumé: ' . $resume_projet_index);
        wp_send_json_error('Le fichier CSV doit contenir les colonnes : Nom du projet, Résumé du projet.');
    }
    
    $admin_id = get_current_user_id();
    $created_projects = array();
    $errors = array();
    $skipped = 0;
    $line_number = 1;
    
    // Lire les données ligne par ligne
    while (($data = fgetcsv($handle, 1000, ',')) !== false) {
        $line_number++;
        
        // Ignorer les lignes vides
        if (empty(array_filter($data))) {
            $skipped++;
            continue;
        }
        
        // Extraire les données
        $project_title = isset($data[$nom_projet_index]) ? sanitize_text_field(trim($data[$nom_projet_index])) : '';
        $project_description = isset($data[$resume_projet_index]) ? sanitize_textarea_field(trim($data[$resume_projet_index])) : '';
        $user_email = ($email_index !== false && isset($data[$email_index])) ? sanitize_email(trim($data[$email_index])) : '';
        $supervisor_email = ($tuteur_email_index !== false && isset($data[$tuteur_email_index])) ? sanitize_email(trim($data[$tuteur_email_index])) : '';
        
        // Vérifier que le titre du projet est rempli
        if (empty($project_title)) {
            $errors[] = "Ligne $line_number : Titre du projet manquant";
            continue;
        }
        
        // Créer le projet
        $project_id = WP_BMC_Database::create_project($admin_id, $project_title, $project_description);
        
        if (!$project_id) {
            $errors[] = "Ligne $line_number : Erreur lors de la création du projet '$project_title'";
            continue;
        }
        
        $project_info = array(
            'title' => $project_title,
            'description' => $project_description,
            'user_assigned' => false,
            'supervisor_assigned' => false,
            'user_email' => $user_email,
            'supervisor_email' => $supervisor_email
        );
        
        // Assigner l'utilisateur au projet si l'email est fourni
        if (!empty($user_email)) {
            // Chercher l'utilisateur par email dans la table bmc_users
            global $wpdb;
            $bmc_user = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}bmc_users WHERE email = %s",
                $user_email
            ));
            
            if ($bmc_user) {
                $assigned = WP_BMC_Database::assign_user_to_project($project_id, $bmc_user->user_id, $admin_id);
                if ($assigned) {
                    $project_info['user_assigned'] = true;
                    error_log("Ligne $line_number : Utilisateur '$user_email' assigné au projet '$project_title'");
                } else {
                    $errors[] = "Ligne $line_number : Erreur lors de l'assignation de l'utilisateur '$user_email' au projet '$project_title'";
                }
            } else {
                $errors[] = "Ligne $line_number : Utilisateur avec l'email '$user_email' non trouvé";
            }
        }
        
        // Assigner le superviseur au projet si l'email est fourni
        if (!empty($supervisor_email)) {
            // Chercher le superviseur par email dans les utilisateurs WordPress
            $supervisor = get_user_by('email', $supervisor_email);
            
            if ($supervisor && in_array('administrator', $supervisor->roles)) {
                $assigned = WP_BMC_Database::assign_supervisor_to_project($project_id, $supervisor->ID);
                if ($assigned) {
                    $project_info['supervisor_assigned'] = true;
                    error_log("Ligne $line_number : Superviseur '$supervisor_email' assigné au projet '$project_title'");
                } else {
                    $errors[] = "Ligne $line_number : Erreur lors de l'assignation du superviseur '$supervisor_email' au projet '$project_title'";
                }
            } else {
                if (!$supervisor) {
                    $errors[] = "Ligne $line_number : Superviseur avec l'email '$supervisor_email' non trouvé";
                } else {
                    $errors[] = "Ligne $line_number : L'utilisateur '$supervisor_email' n'est pas un superviseur";
                }
            }
        }
        
        $created_projects[] = $project_info;
        error_log("Ligne $line_number : Projet '$project_title' créé avec succès (ID: $project_id)");
    }
    
    fclose($handle);
    
    $created_count = count($created_projects);
    $error_count = count($errors);
    
    error_log("Import CSV projets terminé. Créés: $created_count, Ignorés: $skipped, Erreurs: $error_count");
    
    wp_send_json_success(array(
        'message' => "$created_count projet(s) créé(s) avec succès !",
        'created' => $created_count,
        'skipped' => $skipped,
        'errors' => $errors,
        'created_projects' => $created_projects
    ));
}

// Handler pour créer un superviseur (admin)
add_action('wp_ajax_wp_bmc_create_supervisor', 'wp_bmc_create_supervisor_handler');
function wp_bmc_create_supervisor_handler() {
    error_log('=== wp_bmc_create_supervisor_handler appelé ===');
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    
    // Vérifier si l'envoi d'email est activé (même comportement que l'import CSV)
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === '1';
    
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        wp_send_json_error('Tous les champs obligatoires doivent être remplis.');
    }
    
    // Vérifier si l'email existe déjà
    if (email_exists($email)) {
        wp_send_json_error('Cette adresse email est déjà utilisée.');
    }
    
    // Générer un nom d'utilisateur unique basé sur le prénom et nom
    $username = sanitize_user(strtolower($first_name . '.' . $last_name));
    $original_username = $username;
    $counter = 1;
    
    // Vérifier si le nom d'utilisateur existe déjà et en créer un unique
    while (username_exists($username)) {
        $username = $original_username . $counter;
        $counter++;
    }
    
    // Créer l'utilisateur WordPress avec le rôle administrator
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error('Erreur lors de la création du superviseur : ' . $user_id->get_error_message());
    }
    
    // Mettre à jour les informations utilisateur et définir le rôle administrator
    wp_update_user(array(
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name,
        'role' => 'administrator'
    ));
    
    error_log('Superviseur créé avec succès - ID: ' . $user_id);
    error_log('email: ' . $email);
    error_log('username: ' . $username);
    
    // Envoyer l'email seulement si l'option est activée
    if ($send_email) {
        wp_bmc_send_supervisor_welcome_email($email, $first_name, $last_name, $username, $password);
    }
    
    error_log("Superviseur créé avec succès : $email (Username: $username)" . ($send_email ? ' - Email envoyé' : ' - Email non envoyé'));
    
    wp_send_json_success(array(
        'message' => 'Superviseur créé avec succès !' . ($send_email ? ' Email envoyé.' : ' Email non envoyé.'),
        'user_id' => $user_id,
        'username' => $username,
        'email_sent' => $send_email
    ));
}

// Handler pour supprimer un superviseur
add_action('wp_ajax_wp_bmc_delete_supervisor', 'wp_bmc_delete_supervisor_handler');
function wp_bmc_delete_supervisor_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $supervisor_id = intval($_POST['supervisor_id']);
    
    if (!$supervisor_id) {
        wp_send_json_error('ID superviseur invalide.');
    }
    
    // Ne pas permettre à un superviseur de se supprimer lui-même
    if ($supervisor_id == get_current_user_id()) {
        wp_send_json_error('Vous ne pouvez pas vous supprimer vous-même.');
    }
    
    // Vérifier que l'utilisateur existe et est bien un administrateur
    $user = get_user_by('ID', $supervisor_id);
    if (!$user) {
        wp_send_json_error('Superviseur non trouvé.');
    }
    
    if (!in_array('administrator', $user->roles)) {
        wp_send_json_error('Cet utilisateur n\'est pas un superviseur.');
    }
    
    // Charger les fonctions utilisateur de WordPress si pas déjà chargées
    if (!function_exists('wp_delete_user')) {
        require_once(ABSPATH . 'wp-admin/includes/user.php');
    }
    
    // Supprimer l'utilisateur WordPress
    $result = wp_delete_user($supervisor_id);
    
    if ($result) {
        error_log('Superviseur supprimé avec succès - ID: ' . $supervisor_id);
        wp_send_json_success(array(
            'message' => 'Superviseur supprimé avec succès !'
        ));
    } else {
        error_log('Erreur lors de la suppression du superviseur - ID: ' . $supervisor_id);
        wp_send_json_error('Erreur lors de la suppression du superviseur.');
    }
}

// Handler pour réinitialiser le mot de passe d'un superviseur
add_action('wp_ajax_wp_bmc_reset_supervisor_password', 'wp_bmc_reset_supervisor_password_handler');
function wp_bmc_reset_supervisor_password_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $supervisor_id = intval($_POST['supervisor_id']);
    
    if (!$supervisor_id) {
        wp_send_json_error('ID superviseur invalide.');
    }
    
    // Vérifier que l'utilisateur existe
    $user = get_user_by('ID', $supervisor_id);
    if (!$user) {
        wp_send_json_error('Superviseur non trouvé.');
    }
    
    // Générer un nouveau mot de passe aléatoire
    $new_password = wp_generate_password(12, true, true);
    
    // Mettre à jour le mot de passe
    wp_set_password($new_password, $supervisor_id);
    
    // Envoyer l'email
    $email_sent = wp_bmc_send_password_reset_email($user->user_email, $user->display_name, $new_password);
    
    if ($email_sent) {
        wp_send_json_success(array(
            'message' => 'Mot de passe réinitialisé avec succès ! Un email a été envoyé au superviseur.'
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'Mot de passe réinitialisé mais l\'email n\'a pas pu être envoyé.'
        ));
    }
}

// Handler pour associer un utilisateur à un projet
add_action('wp_ajax_wp_bmc_assign_user_to_project', 'wp_bmc_assign_user_to_project_handler');
function wp_bmc_assign_user_to_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $project_id = intval($_POST['project_id']);
    $user_id = intval($_POST['user_id']);
    
    if (empty($project_id) || empty($user_id)) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $admin_id = get_current_user_id();
    $result = WP_BMC_Database::assign_user_to_project($project_id, $user_id, $admin_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Utilisateur associé au projet avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'association de l\'utilisateur au projet.');
    }
}

// Handler pour retirer un utilisateur d'un projet
add_action('wp_ajax_wp_bmc_remove_user_from_project', 'wp_bmc_remove_user_from_project_handler');
function wp_bmc_remove_user_from_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $project_id = intval($_POST['project_id']);
    $user_id = intval($_POST['user_id']);
    
    if (empty($project_id) || empty($user_id)) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $result = WP_BMC_Database::remove_user_from_project($project_id, $user_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Utilisateur retiré du projet avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors du retrait de l\'utilisateur du projet.');
    }
}

// Handler pour assigner un superviseur à un projet
add_action('wp_ajax_wp_bmc_assign_supervisor_to_project', 'wp_bmc_assign_supervisor_to_project_handler');
function wp_bmc_assign_supervisor_to_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $project_id = intval($_POST['project_id']);
    $supervisor_id = intval($_POST['supervisor_id']);
    
    if (empty($project_id) || empty($supervisor_id)) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $result = WP_BMC_Database::assign_supervisor_to_project($project_id, $supervisor_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Superviseur assigné au projet avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'assignation du superviseur au projet.');
    }
}

// Handler pour retirer un superviseur d'un projet
add_action('wp_ajax_wp_bmc_remove_supervisor_from_project', 'wp_bmc_remove_supervisor_from_project_handler');
function wp_bmc_remove_supervisor_from_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $project_id = intval($_POST['project_id']);
    $supervisor_id = intval($_POST['supervisor_id']);
    
    if (empty($project_id) || empty($supervisor_id)) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $result = WP_BMC_Database::remove_supervisor_from_project($project_id, $supervisor_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Superviseur retiré du projet avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors du retrait du superviseur du projet.');
    }
}

// Handler pour obtenir les superviseurs disponibles pour un projet
add_action('wp_ajax_wp_bmc_get_available_supervisors', 'wp_bmc_get_available_supervisors_handler');
function wp_bmc_get_available_supervisors_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (empty($project_id)) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    $supervisors = WP_BMC_Database::get_available_supervisors($project_id);
    
    wp_send_json_success(array(
        'supervisors' => $supervisors
    ));
}

// Handler pour vérifier l'accès d'un utilisateur à un projet (v2.0)
add_action('wp_ajax_wp_bmc_check_project_access', 'wp_bmc_check_project_access_handler');
add_action('wp_ajax_nopriv_wp_bmc_check_project_access', 'wp_bmc_check_project_access_handler');
function wp_bmc_check_project_access_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    $project_id = intval($_POST['project_id']);
    
    if (empty($project_id)) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    // Si c'est un admin, il a accès à tous les projets
    if (current_user_can('manage_options')) {
        wp_send_json_success(array('has_access' => true));
    }
    
    // Vérifier si l'utilisateur connecté a accès au projet
    $user = WP_BMC_Auth::get_current_user();
    if (!$user) {
        wp_send_json_error('Utilisateur non connecté.');
    }
    
    $has_access = WP_BMC_Database::user_has_project_access($user->user_id, $project_id);
    
    if ($has_access) {
        wp_send_json_success(array('has_access' => true));
    } else {
        wp_send_json_error('Accès refusé à ce projet.');
    }
}

// Handler pour sauvegarder le canvas
add_action('wp_ajax_wp_bmc_save_canvas', 'wp_bmc_save_canvas_handler');
function wp_bmc_save_canvas_handler() {
    // Vérifier le nonce admin ou public selon le contexte
    $nonce_valid = false;
    
    // Essayer d'abord le nonce admin
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_admin_nonce')) {
        $nonce_valid = true;
        error_log('wp_bmc_save_canvas_handler - Nonce admin validé');
    }
    // Sinon essayer le nonce public
    elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_nonce')) {
        $nonce_valid = true;
        error_log('wp_bmc_save_canvas_handler - Nonce public validé');
    }
    
    if (!$nonce_valid) {
        error_log('wp_bmc_save_canvas_handler - Nonce invalide: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'nonce manquant'));
        wp_send_json_error('Nonce de sécurité invalide.');
    }
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour sauvegarder le canvas.');
    }
    
    if (!isset($_POST['canvas_data'])) {
        wp_send_json_error('Données du canvas manquantes.');
    }
    
    $canvas_data = $_POST['canvas_data'];
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    
    // Log de débogage
    error_log('wp_bmc_save_canvas_handler - project_id reçu: ' . $project_id);
    error_log('wp_bmc_save_canvas_handler - canvas_data: ' . print_r($canvas_data, true));
    
    // Si pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        
        if (empty($projects)) {
            // Si l'utilisateur est admin et n'a pas de projet personnel, utiliser le premier projet disponible
            if (current_user_can('manage_options')) {
                global $wpdb;
                $projects_table = $wpdb->prefix . 'bmc_projects';
                $first_project = $wpdb->get_row("SELECT id FROM $projects_table ORDER BY id ASC LIMIT 1");
                
                if ($first_project) {
                    $project_id = $first_project->id;
                    error_log('wp_bmc_save_canvas_handler - Admin sans projet personnel, utilisation du projet: ' . $project_id);
                } else {
                    wp_send_json_error('Aucun projet trouvé dans le système.');
                }
            } else {
                wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
            }
        } else {
            $project_id = $projects[0]->id;
        }
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    // Les admins peuvent toujours sauvegarder sur tous les projets
    
    // Sauvegarder uniquement les sections qui sont envoyées (ne pas écraser les autres)
    $success_count = 0;
    
    // Utiliser wp_kses avec des règles strictes pour la sécurité
    $allowed_html = array(
        'p' => array(),
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
        'ul' => array(),
        'ol' => array(),
        'li' => array(),
        'h1' => array(),
        'h2' => array(),
        'h3' => array(),
        'h4' => array(),
        'h5' => array(),
        'h6' => array()
    );
    
    // Log de débogage pour vérifier le filtrage
    error_log('wp_bmc_save_canvas_handler - Filtrage HTML strict activé');
    
    // Vérifier si c'est un admin qui sauvegarde
    $is_admin = current_user_can('manage_options');
    $admin_id = $is_admin ? get_current_user_id() : null;
    
    // Log du project_id pour déboguer les révisions multi-canvas
    error_log("wp_bmc_save_canvas_handler - Sauvegarde pour project_id: $project_id (admin: " . ($is_admin ? 'oui' : 'non') . ")");
    
    // Récupérer le contenu actuel du canvas une seule fois pour comparer les changements
    $current_canvas_data = WP_BMC_Database::get_canvas_data($project_id);
    
    // Boucler uniquement sur les sections envoyées
    foreach ($canvas_data as $section => $raw_content) {
        $content = wp_kses($raw_content, $allowed_html);
        
        // Log de débogage pour vérifier le filtrage
        if ($raw_content !== $content) {
            error_log("wp_bmc_save_canvas_handler - Contenu filtré pour section '$section':");
            error_log("  Avant: " . substr($raw_content, 0, 200) . (strlen($raw_content) > 200 ? '...' : ''));
            error_log("  Après: " . substr($content, 0, 200) . (strlen($content) > 200 ? '...' : ''));
        }
        
        // Vérifier si le contenu a réellement changé par rapport à ce qui est en base
        $current_content = isset($current_canvas_data[$section]) ? $current_canvas_data[$section] : '';
        
        // Normaliser les contenus pour une comparaison fiable (supprimer espaces superflus, normaliser les sauts de ligne)
        $current_content_normalized = trim(preg_replace('/\s+/', ' ', $current_content));
        $new_content_normalized = trim(preg_replace('/\s+/', ' ', $content));
        
        $content_has_changed = ($current_content_normalized !== $new_content_normalized);
        
        // Log détaillé pour débogage
        if ($is_admin) {
            error_log("wp_bmc_save_canvas_handler - Section '$section' (project $project_id):");
            error_log("  Contenu changé: " . ($content_has_changed ? 'OUI' : 'NON'));
            if ($content_has_changed) {
                error_log("  Ancien (100 car): " . substr($current_content_normalized, 0, 100));
                error_log("  Nouveau (100 car): " . substr($new_content_normalized, 0, 100));
            }
        }
        
        if (WP_BMC_Database::save_canvas_data($project_id, $section, $content)) {
            $success_count++;
            
            // MODE 2 : Créer une révision si un admin modifie le contenu ET que le contenu a réellement changé
            if (WP_BMC_REVISION_ON_ADMIN_EDIT && $is_admin && $content_has_changed) {
                // Récupérer la dernière note de cette section (si elle existe)
                $latest_rating = WP_BMC_Database::get_latest_section_rating($project_id, $section);
                
                $rating = null;
                $rating_comment = null;
                
                if ($latest_rating) {
                    $rating = $latest_rating->rating;
                    $original_comment = $latest_rating->comment;
                    
                    // Ajouter une indication que c'est une modification de contenu
                    if (!empty($original_comment)) {
                        $rating_comment = "Modification du contenu par l'administrateur";
                    } else {
                        $rating_comment = "Modification du contenu par l'administrateur";
                    }
                } else {
                    // Pas de note existante, mais on indique quand même la modification
                    $rating_comment = "Modification du contenu par l'administrateur";
                }
                
                // Créer une révision avec la note existante (si elle existe)
                WP_BMC_Database::create_section_revision(
                    $project_id, 
                    $section, 
                    $content, 
                    'admin_edit',
                    $rating,
                    $rating_comment,
                    $admin_id
                );
                
                error_log("wp_bmc_save_canvas_handler - Révision créée (admin_edit) pour PROJECT_ID: $project_id, section: '$section'" . ($rating ? " avec note $rating" : " sans note"));
            } elseif (WP_BMC_REVISION_ON_ADMIN_EDIT && $is_admin && !$content_has_changed) {
                error_log("wp_bmc_save_canvas_handler - Pas de révision créée pour section '$section' : contenu inchangé");
            }
        }
    }
    
    if ($success_count > 0) {
        wp_send_json_success(array(
            'message' => 'Canvas sauvegardé avec succès !',
            'saved_sections' => $success_count
        ));
    } else {
        wp_send_json_error('Erreur lors de la sauvegarde du canvas.');
    }
}

// Handler pour obtenir les données du canvas
add_action('wp_ajax_wp_bmc_get_canvas', 'wp_bmc_get_canvas_handler');
function wp_bmc_get_canvas_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder au canvas.');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (!$project_id) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $user = WP_BMC_Auth::get_current_user();
    
    // Vérifier l'accès : admin OU utilisateur assigné au projet
    $has_access = current_user_can('manage_options') || WP_BMC_Database::user_has_project_access($user->user_id, $project_id);
    
    if (!$has_access) {
        wp_send_json_error('Vous n\'avez pas accès à ce projet.');
    }
    
    $canvas_data = WP_BMC_Database::get_canvas_data($project_id);
    
    wp_send_json_success(array(
        'canvas_data' => $canvas_data
    ));
}

// Handler pour supprimer un projet
add_action('wp_ajax_wp_bmc_delete_project', 'wp_bmc_delete_project_handler');
function wp_bmc_delete_project_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour supprimer un projet.');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (!$project_id) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $user = WP_BMC_Auth::get_current_user();
    
    // Vérifier l'accès : admin OU utilisateur assigné au projet
    $has_access = current_user_can('manage_options') || WP_BMC_Database::user_has_project_access($user->user_id, $project_id);
    
    if (!$has_access) {
        wp_send_json_error('Vous n\'avez pas accès à ce projet.');
    }
    
    global $wpdb;
    
    // Supprimer les données du canvas
    $canvas_table = $wpdb->prefix . 'bmc_canvas_data';
    $wpdb->delete($canvas_table, array('project_id' => $project_id), array('%d'));
    
    // Supprimer le projet
    $projects_table = $wpdb->prefix . 'bmc_projects';
    $result = $wpdb->delete($projects_table, array('id' => $project_id), array('%d'));
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Projet supprimé avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression du projet.');
    }
}

// Handler pour supprimer un projet (admin seulement)
add_action('wp_ajax_wp_bmc_admin_delete_project', 'wp_bmc_admin_delete_project_handler');
function wp_bmc_admin_delete_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes.');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (!$project_id) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    global $wpdb;
    
    // Supprimer toutes les données associées au projet
    $tables_to_clean = array(
        $wpdb->prefix . 'bmc_canvas_data',
        $wpdb->prefix . 'bmc_todos',
        $wpdb->prefix . 'bmc_ratings',
        $wpdb->prefix . 'bmc_section_revisions',
        $wpdb->prefix . 'bmc_project_users',
        $wpdb->prefix . 'bmc_project_supervisors'
    );
    
    foreach ($tables_to_clean as $table) {
        $wpdb->delete($table, array('project_id' => $project_id), array('%d'));
    }
    
    // Supprimer le projet
    $projects_table = $wpdb->prefix . 'bmc_projects';
    $result = $wpdb->delete($projects_table, array('id' => $project_id), array('%d'));
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Projet supprimé avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression du projet.');
    }
}

// Handler pour exporter le canvas en PDF (fonctionnalité future)
add_action('wp_ajax_wp_bmc_export_pdf', 'wp_bmc_export_pdf_handler');
function wp_bmc_export_pdf_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour exporter le canvas.');
    }
    
    // Récupérer le projet de l'utilisateur (un seul projet par utilisateur)
    $user = WP_BMC_Auth::get_current_user();
    $projects = WP_BMC_Database::get_user_projects($user->user_id);
    
    if (empty($projects)) {
        wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
    }
    
    $project = $projects[0]; // Prendre le premier (et seul) projet
    $canvas_data = WP_BMC_Database::get_canvas_data($project->id);
    
    // Pour l'instant, on retourne juste les données JSON
    // L'export PDF nécessiterait une bibliothèque comme TCPDF ou mPDF
    wp_send_json_success(array(
        'message' => 'Export PDF en cours de développement',
        'canvas_data' => $canvas_data,
        'project_title' => $project->title,
        'pdf_url' => '#' // URL temporaire
    ));
}

// Handler pour obtenir les fichiers d'une section
add_action('wp_ajax_wp_bmc_get_section_files', 'wp_bmc_get_section_files_handler');
function wp_bmc_get_section_files_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux fichiers.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    
    if (empty($section)) {
        wp_send_json_error('Section invalide.');
    }
    
    // Récupérer le project_id depuis l'URL ou les paramètres
    $project_id = null;
    
    // Essayer de récupérer depuis les paramètres POST
    if (isset($_POST['project_id'])) {
        $project_id = intval($_POST['project_id']);
    }
    
    // Si pas de project_id dans POST, essayer de le récupérer depuis l'URL de référence
    if (!$project_id) {
        $referer = wp_get_referer();
        if ($referer) {
            $url_parts = parse_url($referer);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
                if (isset($query_params['project_id'])) {
                    $project_id = intval($query_params['project_id']);
                }
            }
        }
    }
    
    // Si toujours pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        
        if (empty($projects)) {
            // Si l'utilisateur est admin, permettre l'accès même sans projet personnel
            if (current_user_can('manage_options')) {
                // Pour les admins, utiliser un projet par défaut
                // Récupérer le premier projet disponible pour l'admin
                global $wpdb;
                $projects_table = $wpdb->prefix . 'bmc_projects';
                $first_project = $wpdb->get_row("SELECT id FROM $projects_table ORDER BY id ASC LIMIT 1");
                
                if ($first_project) {
                    $project_id = $first_project->id;
                } else {
                    wp_send_json_error('Aucun projet disponible pour consulter les fichiers.');
                }
            } else {
                wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
            }
        } else {
            $project_id = $projects[0]->id;
        }
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    
    // Récupérer les fichiers de la section
    $files = WP_BMC_Database::get_section_files($project_id, $section);
    
    wp_send_json_success(array(
        'files' => $files
    ));
}

// Handler pour uploader des fichiers
add_action('wp_ajax_wp_bmc_upload_file', 'wp_bmc_upload_file_handler');
function wp_bmc_upload_file_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour uploader des fichiers.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    
    if (empty($section)) {
        wp_send_json_error('Section invalide.');
    }
    
    // Récupérer le project_id depuis l'URL ou les paramètres
    $project_id = null;
    
    // Essayer de récupérer depuis les paramètres POST
    if (isset($_POST['project_id'])) {
        $project_id = intval($_POST['project_id']);
    }
    
    // Si pas de project_id dans POST, essayer de le récupérer depuis l'URL de référence
    if (!$project_id) {
        $referer = wp_get_referer();
        if ($referer) {
            $url_parts = parse_url($referer);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
                if (isset($query_params['project_id'])) {
                    $project_id = intval($query_params['project_id']);
                }
            }
        }
    }
    
    // Si toujours pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        
        if (empty($projects)) {
            // Si l'utilisateur est admin, permettre l'upload même sans projet personnel
            if (current_user_can('manage_options')) {
                // Pour les admins, utiliser un projet par défaut
                // Récupérer le premier projet disponible pour l'admin
                global $wpdb;
                $projects_table = $wpdb->prefix . 'bmc_projects';
                $first_project = $wpdb->get_row("SELECT id FROM $projects_table ORDER BY id ASC LIMIT 1");
                
                if ($first_project) {
                    $project_id = $first_project->id;
                } else {
                    wp_send_json_error('Aucun projet disponible pour l\'upload.');
                }
            } else {
                wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
            }
        } else {
            $project_id = $projects[0]->id;
        }
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    
    // Vérifier les fichiers uploadés
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        wp_send_json_error('Aucun fichier sélectionné.');
    }
    
    $uploaded_files = array();
    $errors = array();
    
    // Traiter chaque fichier
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        $file_name = sanitize_file_name($_FILES['files']['name'][$i]);
        $file_type = $_FILES['files']['type'][$i];
        $file_tmp = $_FILES['files']['tmp_name'][$i];
        $file_size = $_FILES['files']['size'][$i];
        
        // Vérifier la taille (max 10MB)
        if ($file_size > 10 * 1024 * 1024) {
            $errors[] = "Le fichier $file_name est trop volumineux (max 10MB).";
            continue;
        }
        
        // Vérifier le type de fichier
        $allowed_types = array(
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm', 'video/ogg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Le type de fichier $file_type n'est pas autorisé pour $file_name.";
            continue;
        }
        
        // Créer le dossier d'upload si nécessaire
        $upload_dir = wp_upload_dir();
        $bmc_dir = $upload_dir['basedir'] . '/wp-bmc-files/' . $project_id . '/' . $section;
        
        if (!file_exists($bmc_dir)) {
            wp_mkdir_p($bmc_dir);
        }
        
        // Générer un nom de fichier unique
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . $file_name;
        $file_path = $bmc_dir . '/' . $unique_filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Enregistrer dans la base de données
            $file_id = WP_BMC_Database::save_file($project_id, $section, $file_name, $unique_filename, $file_type, $file_size);
            
            if ($file_id) {
                $uploaded_files[] = array(
                    'id' => $file_id,
                    'name' => $file_name,
                    'type' => $file_type,
                    'size' => $file_size,
                    'url' => $upload_dir['baseurl'] . '/wp-bmc-files/' . $project_id . '/' . $section . '/' . $unique_filename
                );
            } else {
                $errors[] = "Erreur lors de l'enregistrement du fichier $file_name.";
            }
        } else {
            $errors[] = "Erreur lors de l'upload du fichier $file_name.";
        }
    }
    
    if (!empty($uploaded_files)) {
        wp_send_json_success(array(
            'message' => count($uploaded_files) . ' fichier(s) uploadé(s) avec succès.',
            'files' => $uploaded_files,
            'errors' => $errors
        ));
    } else {
        wp_send_json_error('Aucun fichier n\'a pu être uploadé. ' . implode(' ', $errors));
    }
}

// Handler pour supprimer un fichier
add_action('wp_ajax_wp_bmc_delete_file', 'wp_bmc_delete_file_handler');
function wp_bmc_delete_file_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour supprimer des fichiers.');
    }
    
    $file_id = intval($_POST['file_id']);
    
    if (!$file_id) {
        wp_send_json_error('ID de fichier invalide.');
    }
    
    // Récupérer le project_id depuis l'URL ou les paramètres
    $project_id = null;
    
    // Essayer de récupérer depuis les paramètres POST
    if (isset($_POST['project_id'])) {
        $project_id = intval($_POST['project_id']);
    }
    
    // Si pas de project_id dans POST, essayer de le récupérer depuis l'URL de référence
    if (!$project_id) {
        $referer = wp_get_referer();
        if ($referer) {
            $url_parts = parse_url($referer);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
                if (isset($query_params['project_id'])) {
                    $project_id = intval($query_params['project_id']);
                }
            }
        }
    }
    
    // Si toujours pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        
        if (empty($projects)) {
            // Si l'utilisateur est admin et n'a pas de projet personnel, utiliser le premier projet disponible
            if (current_user_can('manage_options')) {
                global $wpdb;
                $projects_table = $wpdb->prefix . 'bmc_projects';
                $first_project = $wpdb->get_row("SELECT id FROM $projects_table ORDER BY id ASC LIMIT 1");
                
                if ($first_project) {
                    $project_id = $first_project->id;
                } else {
                    wp_send_json_error('Aucun projet trouvé dans le système.');
                }
            } else {
                wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
            }
        } else {
            $project_id = $projects[0]->id;
        }
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    
    // Supprimer le fichier
    $result = WP_BMC_Database::delete_file($file_id, $project_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Fichier supprimé avec succès.'
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression du fichier.');
    }
}

// Handler pour obtenir les documents de référence
add_action('wp_ajax_wp_bmc_get_documents', 'wp_bmc_get_documents_handler');
function wp_bmc_get_documents_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux documents.');
    }
    
    if (!isset($_POST['section'])) {
        wp_send_json_error('Section manquante pour récupérer les documents.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    
    // Récupérer les documents de référence (gérés par les admins)
    $documents = WP_BMC_Database::get_reference_documents($section);
    
    wp_send_json_success(array(
        'documents' => $documents
    ));
}

// Handler pour obtenir la note d'une section
add_action('wp_ajax_wp_bmc_get_section_rating', 'wp_bmc_get_section_rating_handler');
function wp_bmc_get_section_rating_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux documents.');
    }
    
    
    if (!isset($_POST['section']) || !isset($_POST['project_id'])) {
        wp_send_json_error('Paramètres manquants pour récupérer la note.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = intval($_POST['project_id']);
    $admin_id = get_current_user_id();
    
    if (empty($section) || !$project_id) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $rating = WP_BMC_Database::get_latest_section_rating($project_id, $section);
    
    wp_send_json_success(array(
        'rating' => $rating
    ));
}

// Handler pour sauvegarder une note
add_action('wp_ajax_wp_bmc_save_section_rating', 'wp_bmc_save_section_rating_handler');
function wp_bmc_save_section_rating_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    if (!isset($_POST['section']) || !isset($_POST['project_id']) || !isset($_POST['rating'])) {
        wp_send_json_error('Paramètres manquants pour la notation.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = intval($_POST['project_id']);
    $rating = intval($_POST['rating']);
    $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
    $admin_id = get_current_user_id();
    
    if (empty($section) || !$project_id || $rating < 0 || $rating > 10) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $result = WP_BMC_Database::save_section_rating($project_id, $section, $admin_id, $rating, $comment);
    
    if ($result) {
        // Récupérer le contenu actuel de la section pour la révision
        $canvas_data = WP_BMC_Database::get_canvas_data($project_id);
        $current_content = isset($canvas_data[$section]) ? $canvas_data[$section] : '';
        
        // MODE 1 : Créer une révision lors de la notation par un admin
        // Cette révision est toujours créée, peu importe le mode configuré
        WP_BMC_Database::create_section_revision(
            $project_id, 
            $section, 
            $current_content, 
            'admin_rating',
            $rating,
            $comment,
            $admin_id
        );
        
        error_log("wp_bmc_save_section_rating_handler - Révision créée (admin_rating) pour section '$section' avec note $rating");
        
        wp_send_json_success(array(
            'message' => 'Note sauvegardée avec succès !',
            'project_id' => $project_id,
            'section' => $section
        ));
    } else {
        wp_send_json_error('Erreur lors de la sauvegarde de la note.');
    }
}



// Handler pour obtenir le compteur de demandes de notation d'un utilisateur
add_action('wp_ajax_wp_bmc_get_user_grading_count', 'wp_bmc_get_user_grading_count_handler');
function wp_bmc_get_user_grading_count_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $user_id = intval($_POST['user_id']);
    
    if (!$user_id) {
        wp_send_json_error('ID utilisateur invalide.');
    }
    
    global $wpdb;
    
    // Récupérer les données de notation pour cet utilisateur
    $grading_data = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COUNT(gr.id) as total_grading_requests_count,
                    SUM(CASE WHEN gr.status = 'pending' THEN 1 ELSE 0 END) as pending_grading_requests_count,
                    GROUP_CONCAT(DISTINCT gr.status) as grading_statuses
             FROM {$wpdb->prefix}bmc_users u
             LEFT JOIN {$wpdb->prefix}bmc_projects p ON u.user_id = p.user_id
             LEFT JOIN {$wpdb->prefix}bmc_grading_requests gr ON p.id = gr.project_id
             WHERE u.user_id = %d
             GROUP BY u.user_id",
            $user_id
        )
    );
    
    if ($grading_data) {
        wp_send_json_success(array(
            'total_grading_requests_count' => intval($grading_data->total_grading_requests_count),
            'pending_grading_requests_count' => intval($grading_data->pending_grading_requests_count),
            'grading_statuses' => $grading_data->grading_statuses ? explode(',', $grading_data->grading_statuses) : array()
        ));
    } else {
        wp_send_json_success(array(
            'total_grading_requests_count' => 0,
            'pending_grading_requests_count' => 0,
            'grading_statuses' => array()
        ));
    }
}

// Handler pour vérifier si une demande de notation est en attente
add_action('wp_ajax_wp_bmc_check_grading_request', 'wp_bmc_check_grading_request_handler');
function wp_bmc_check_grading_request_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté.');
    }
    
    if (!isset($_POST['section'])) {
        wp_send_json_error('Section manquante.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    
    // Récupérer le projet de l'utilisateur
    $user = WP_BMC_Auth::get_current_user();
    $projects = WP_BMC_Database::get_user_projects($user->user_id);
    
    if (empty($projects)) {
        wp_send_json_success(array('has_pending_request' => false));
        return;
    }
    
    $project = $projects[0];
    $has_pending_request = WP_BMC_Database::has_pending_grading_request($project->id, $section);
    
    wp_send_json_success(array(
        'has_pending_request' => $has_pending_request
    ));
}

// Handler pour demander une notation
add_action('wp_ajax_wp_bmc_request_grading', 'wp_bmc_request_grading_handler');
function wp_bmc_request_grading_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour demander une notation.');
    }
    
    if (!isset($_POST['section'])) {
        wp_send_json_error('Section manquante pour la demande de notation.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $section_title = isset($_POST['section_title']) ? sanitize_text_field($_POST['section_title']) : $section;
    
    if (empty($section)) {
        wp_send_json_error('Section invalide.');
    }
    
    // Récupérer le projet de l'utilisateur
    $user = WP_BMC_Auth::get_current_user();
    $projects = WP_BMC_Database::get_user_projects($user->user_id);
    
    if (empty($projects)) {
        wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
    }
    
    $project = $projects[0];
    
    // Vérifier si une demande est déjà en attente
    $has_pending_request = WP_BMC_Database::has_pending_grading_request($project->id, $section);
    
    if ($has_pending_request) {
        wp_send_json_error('Une demande de notation est déjà en attente pour cette section.');
        return;
    }
    
    // Les révisions seront créées lors de la notation par l'admin
    
    // Enregistrer la demande de notation
    $result = WP_BMC_Database::save_grading_request($project->id, $section, $section_title, $user->user_id);
    
    if ($result) {
        // Envoyer une notification aux administrateurs
        wp_bmc_notify_admins_grading_request($project, $section, $section_title, $user);
        
        wp_send_json_success(array(
            'message' => 'Demande de notation envoyée avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'envoi de la demande de notation.');
    }
}

// Handler pour obtenir les révisions d'une section
add_action('wp_ajax_wp_bmc_get_section_revisions', 'wp_bmc_get_section_revisions_handler');
function wp_bmc_get_section_revisions_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux révisions.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    
    if (empty($section)) {
        wp_send_json_error('Section manquante.');
    }
    
    // Si pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        if (empty($projects)) {
            wp_send_json_error('Aucun projet trouvé.');
        }
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    $user = WP_BMC_Auth::get_current_user();
    
    // Vérifier l'accès : admin OU utilisateur assigné au projet
    $has_access = current_user_can('manage_options') || WP_BMC_Database::user_has_project_access($user->user_id, $project_id);
    
    if (!$has_access) {
        wp_send_json_error('Accès non autorisé à ce projet.');
    }
    
    $revisions = WP_BMC_Database::get_section_revisions($project_id, $section);
    
    wp_send_json_success(array(
        'revisions' => $revisions,
        'section' => $section,
        'project_id' => $project_id
    ));
}

// Handler pour obtenir une révision spécifique
add_action('wp_ajax_wp_bmc_get_section_revision', 'wp_bmc_get_section_revision_handler');
function wp_bmc_get_section_revision_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux révisions.');
    }
    
    $revision_id = intval($_POST['revision_id']);
    
    if (!$revision_id) {
        wp_send_json_error('ID de révision invalide.');
    }
    
    $revision = WP_BMC_Database::get_section_revision($revision_id);
    
    if (!$revision) {
        wp_send_json_error('Révision non trouvée.');
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($revision->project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    $user = WP_BMC_Auth::get_current_user();
    
    // Vérifier l'accès : admin OU utilisateur assigné au projet
    $has_access = current_user_can('manage_options') || WP_BMC_Database::user_has_project_access($user->user_id, $revision->project_id);
    
    if (!$has_access) {
        wp_send_json_error('Accès non autorisé à cette révision.');
    }
    
    wp_send_json_success(array(
        'revision' => $revision
    ));
}

// Handler pour charger une vue du canvas via AJAX
add_action('wp_ajax_wp_bmc_load_canvas_view', 'wp_bmc_load_canvas_view_handler');
function wp_bmc_load_canvas_view_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    $current_user = WP_BMC_Auth::get_current_user();
    if (!$current_user) {
        wp_send_json_error('Utilisateur non connecté.');
    }
    
    $view = sanitize_text_field($_POST['view']);
    if (!in_array($view, ['synthetic', 'global'])) {
        wp_send_json_error('Vue invalide.');
    }
    
    // Récupérer les données du projet
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    
    if ($project_id) {
        // Mode canvas spécifique - vérifier les permissions
        $project = WP_BMC_Database::get_project($project_id);
        error_log('wp_bmc_load_canvas_view_handler - project found: ' . ($project ? 'yes' : 'no'));
        if (!$project) {
            wp_send_json_error('Projet non trouvé.');
        }
        
        // Vérifier si c'est un mode admin ou si l'utilisateur a accès au projet
        $is_admin = current_user_can('manage_options');
        if (!$is_admin && !WP_BMC_Database::user_has_project_access($current_user->user_id, $project_id)) {
            wp_send_json_error('Accès non autorisé à ce projet.');
        }
    } else {
        // Mode dashboard - utiliser le premier projet de l'utilisateur
        $user_projects = WP_BMC_Database::get_user_projects($current_user->user_id);
        $project = !empty($user_projects) ? $user_projects[0] : null;
        
        if (!$project) {
            wp_send_json_error('Aucun projet trouvé.');
        }
        $project_id = $project->id;
    }
    
    $canvas_data = WP_BMC_Database::get_canvas_data($project_id);
    $project_ratings = WP_BMC_Database::get_project_ratings($project_id);
    
    // Récupérer les demandes de notation en attente pour les admins
    $pending_grading_requests = array();
    if ($is_admin && current_user_can('manage_options')) {
        global $wpdb;
        $grading_table = $wpdb->prefix . 'bmc_grading_requests';
        $pending_requests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT section FROM $grading_table WHERE project_id = %d AND status = 'pending'",
                $project_id
            )
        );
        
        foreach ($pending_requests as $request) {
            $pending_grading_requests[] = $request->section;
        }
    }
    
    // Utiliser la fonction centralisée pour générer le HTML
    $html = wp_bmc_render_canvas_view($view, $project_id, $canvas_data, $project_ratings, $is_admin, $pending_grading_requests, true);
    
    wp_send_json_success(array(
        'html' => $html,
        'view' => $view
    ));
}

// Fonction pour notifier les administrateurs d'une demande de notation
function wp_bmc_notify_admins_grading_request($project, $section, $section_title, $user) {
    // Récupérer tous les administrateurs
    $admins = get_users(array('role' => 'administrator'));
    
    if (empty($admins)) {
        return;
    }
    
    // Préparer le message
    $user_name = isset($user->display_name) ? $user->display_name : ($user->first_name . ' ' . $user->last_name);
    $message = sprintf(
        'Un étudiant (%s) demande une notation pour la section "%s" de son projet "%s".',
        $user_name,
        $section_title,
        $project->title
    );
    
    // Créer une notification dans le dashboard admin
    foreach ($admins as $admin) {
        // Enregistrer la notification dans la base de données
        WP_BMC_Database::save_admin_notification(
            $admin->ID,
            'grading_request',
            $message,
            array(
                'project_id' => $project->id,
                'section' => $section,
                'section_title' => $section_title,
                'user_id' => $user->user_id,
                'user_name' => $user_name
            )
        );
    }
    
    // Optionnel : Envoyer un email aux administrateurs
    $subject = 'Demande de notation - ' . $project->title;
    $email_message = $message . "\n\n";
    $email_message .= "Projet : " . $project->title . "\n";
    $email_message .= "Section : " . $section_title . "\n";
    $email_message .= "Étudiant : " . $user_name . "\n";
    $email_message .= "Date : " . date('d/m/Y H:i') . "\n\n";
    $email_message .= "Connectez-vous au dashboard admin pour noter cette section.";
    
    foreach ($admins as $admin) {
        // wp_mail($admin->user_email, $subject, $email_message);
    }
}

// Handler pour marquer une notification comme lue (admin)
add_action('wp_ajax_wp_bmc_mark_notification_read', 'wp_bmc_mark_notification_read_handler');
function wp_bmc_mark_notification_read_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $notification_id = intval($_POST['notification_id']);
    $admin_id = get_current_user_id();
    
    if (!$notification_id) {
        wp_send_json_error('ID de notification invalide.');
    }
    
    $result = WP_BMC_Database::mark_notification_read($notification_id, $admin_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Notification marquée comme lue.'
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour de la notification.');
    }
}

// Handler pour obtenir les notifications non lues (admin)
add_action('wp_ajax_wp_bmc_get_unread_notifications', 'wp_bmc_get_unread_notifications_handler');
function wp_bmc_get_unread_notifications_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $admin_id = get_current_user_id();
    $notifications = WP_BMC_Database::get_unread_notifications($admin_id);
    
    wp_send_json_success(array(
        'notifications' => $notifications
    ));
}

// Handler pour exporter les utilisateurs (admin)
add_action('wp_ajax_wp_bmc_export_users', 'wp_bmc_export_users_handler');
function wp_bmc_export_users_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    // Récupérer tous les utilisateurs avec leurs projets
    $users = WP_BMC_Database::get_all_users_with_projects();
    
    // Créer le fichier CSV
    $filename = 'utilisateurs-bmc-' . date('Y-m-d-H-i-s') . '.csv';
    $filepath = WP_CONTENT_DIR . '/uploads/' . $filename;
    
    $file = fopen($filepath, 'w');
    
    // En-têtes CSV
    fputcsv($file, array('Nom', 'Email', 'Projets', 'Dernière activité', 'Demandes de notation'));
    
    // Données
    foreach ($users as $user) {
        fputcsv($file, array(
            $user->display_name,
            $user->user_email,
            $user->project_count,
            $user->last_project_date,
            $user->grading_status
        ));
    }
    
    fclose($file);
    
    wp_send_json_success(array(
        'file_url' => content_url('/uploads/' . $filename)
    ));
}

// Handler pour exporter toutes les données (admin)
add_action('wp_ajax_wp_bmc_export_all_data', 'wp_bmc_export_all_data_handler');
function wp_bmc_export_all_data_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour exporter les données.');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (!$project_id) {
        wp_send_json_error('ID de projet invalide.');
    }
    
    // Récupérer les informations du projet et de l'utilisateur
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet introuvable.');
    }
    
    $user = WP_BMC_Auth::get_current_user();
    $is_admin = current_user_can('manage_options');
    
    // Vérifier les permissions
    if (!$is_admin && !WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
        wp_send_json_error('Vous n\'avez pas accès à ce projet.');
    }
    
    // Si c'est un admin qui accède au projet d'un autre utilisateur, récupérer les infos de cet utilisateur
    if ($is_admin && $project->user_id != $user->user_id) {
        $project_owner = WP_BMC_Database::get_user($project->user_id);
        if ($project_owner) {
            $user = $project_owner;
        }
    }
    
    // Récupérer toutes les données nécessaires pour le PDF
    $canvas_data = WP_BMC_Database::get_canvas_data($project_id);
    $project_ratings = WP_BMC_Database::get_project_ratings($project_id);
    $project_todos = WP_BMC_Database::get_project_todos($project_id);
    
    // Configuration des sections du canvas
    include_once WP_BMC_SHARED_DIR . 'Config/canvas-sections.php';
    $sections_config = wp_bmc_get_canvas_sections('global');
    
    // Organiser les données par section avec métadonnées complètes
    $sections_data = array();
    foreach ($sections_config as $section_key => $section_config) {
        // Contenu de la section
        $section_content = isset($canvas_data[$section_key]) ? $canvas_data[$section_key] : '';
        
        // Notes pour cette section
        $section_ratings = array_filter($project_ratings, function($rating) use ($section_key) {
            return $rating->section === $section_key;
        });
        
        // Todos pour cette section
        $section_todos = array_filter($project_todos, function($todo) use ($section_key) {
            return $todo->section === $section_key;
        });
        
        // Statistiques des todos
        $todos_total = count($section_todos);
        $todos_completed = count(array_filter($section_todos, function($todo) {
            return intval($todo->is_completed) === 1;
        }));
        $todos_pending = $todos_total - $todos_completed;
        $completion_rate = $todos_total > 0 ? round(($todos_completed / $todos_total) * 100, 1) : 0;
        
        // Note moyenne pour cette section
        $average_rating = 0;
        $latest_rating = null;
        if (!empty($section_ratings)) {
            $ratings_values = array_column($section_ratings, 'rating');
            $average_rating = round(array_sum($ratings_values) / count($ratings_values), 1);
            
            // Dernière note avec métadonnées
            $latest_rating_obj = reset($section_ratings);
            $latest_rating = array(
                'rating' => intval($latest_rating_obj->rating),
                'comment' => $latest_rating_obj->comment,
                'admin_name' => $latest_rating_obj->admin_name ?? 'Admin',
                'created_at' => WP_BMC_Database::format_date_for_display($latest_rating_obj->created_at),
                'raw_date' => $latest_rating_obj->created_at
            );
        }
        
        $sections_data[$section_key] = array(
            'title' => $section_config['title'],
            'content' => $section_content,
            'content_length' => strlen(strip_tags($section_content)),
            'color' => $section_config['color'],
            'is_synthetic' => $section_config['synthetic'],
            'placeholder' => $section_config['placeholder'],
            'grid_position' => array(
                'column' => $section_config['grid-column'],
                'row' => $section_config['grid-row']
            ),
            'ratings' => array(
                'count' => count($section_ratings),
                'average' => $average_rating,
                'latest' => $latest_rating
            ),
            'todos' => array(
                'total' => $todos_total,
                'completed' => $todos_completed,
                'pending' => $todos_pending,
                'completion_rate' => $completion_rate,
                'items' => array_map(function($todo) {
                    return array(
                        'id' => intval($todo->id),
                        'text' => $todo->task_text,
                        'completed' => intval($todo->is_completed) === 1,
                        'created_at' => WP_BMC_Database::format_date_for_display($todo->created_at),
                        'raw_date' => $todo->created_at
                    );
                }, array_values($section_todos))
            )
        );
    }
    
    // Calculer les statistiques globales du projet
    $total_todos = count($project_todos);
    $total_completed = count(array_filter($project_todos, function($todo) {
        return intval($todo->is_completed) === 1;
    }));
    $global_completion_rate = $total_todos > 0 ? round(($total_completed / $total_todos) * 100, 1) : 0;
    
    // Note moyenne globale
    $global_average_rating = 0;
    if (!empty($project_ratings)) {
        $all_ratings = array_column($project_ratings, 'rating');
        $global_average_rating = round(array_sum($all_ratings) / count($all_ratings), 1);
    }
    
    // Structure JSON complète pour Gotenberg
    $pdf_data = array(
        'document' => array(
            'title' => 'Passeport de l\'entrepreneuriat - ' . $project->title,
            'author' => $user->first_name . ' ' . $user->last_name,
            'subject' => 'Passeport de l\'entrepreneuriat',
            'creator' => 'Passeport de l\'entrepreneuriat - OpenCampusInnov',
            'generated_at' => WP_BMC_Database::format_date_for_display(current_time('mysql'), 'date'),
            'generated_timestamp' => current_time('timestamp'),
            'language' => 'fr'
        ),
        'project' => array(
            'id' => intval($project_id),
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status,
            'created_at' => WP_BMC_Database::format_date_for_display($project->created_at),
            'updated_at' => WP_BMC_Database::format_date_for_display($project->updated_at),
            'raw_created_at' => $project->created_at,
            'raw_updated_at' => $project->updated_at
        ),
        'user' => array(
            'id' => intval($user->user_id),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
        ),
        'canvas' => array(
            'sections' => $sections_data,
            'view_modes' => array('global', 'synthetic'),
            'sections_order' => array_keys($sections_config)
        ),
        'statistics' => array(
            'todos' => array(
                'total' => $total_todos,
                'completed' => $total_completed,
                'pending' => $total_todos - $total_completed,
                'completion_rate' => $global_completion_rate
            ),
            'ratings' => array(
                'total_ratings' => count($project_ratings),
                'average_rating' => $global_average_rating,
                'sections_rated' => count(array_unique(array_column($project_ratings, 'section'))),
                'sections_total' => count($sections_config)
            ),
            'content' => array(
                'total_characters' => array_sum(array_map(function($section) {
                    return $section['content_length'];
                }, $sections_data)),
                'sections_with_content' => count(array_filter($sections_data, function($section) {
                    return $section['content_length'] > 0;
                })),
                'completion_percentage' => round((count(array_filter($sections_data, function($section) {
                    return $section['content_length'] > 0;
                })) / count($sections_config)) * 100, 1)
            )
        ),
        'template' => array(
            'type' => 'business_model_canvas',
            'version' => '2.0',
            'format' => 'a4',
            'orientation' => 'landscape',
            'margins' => array(
                'top' => '1cm',
                'right' => '1cm',
                'bottom' => '1cm',
                'left' => '1cm'
            )
        ),
        'meta' => array(
            'export_type' => 'pdf',
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => '2.0.0',
            'php_version' => PHP_VERSION,
            'timezone' => wp_timezone_string(),
            'locale' => get_locale()
        )
    );
   
    wp_send_json_success(array(
        'pdf_data' => $pdf_data,
        'message' => 'Données PDF générées avec succès'
    ));
}

// Handler pour générer un PDF via Gotenberg
add_action('wp_ajax_wp_bmc_generate_pdf_gotenberg', 'wp_bmc_generate_pdf_gotenberg_handler');
function wp_bmc_generate_pdf_gotenberg_handler() {
    error_log('=== DÉBUT GÉNÉRATION PDF GOTENBERG ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        error_log('PDF: Utilisateur non connecté');
        wp_send_json_error('Vous devez être connecté pour générer le PDF.');
    }
    
    $project_id = intval($_POST['project_id']);
    error_log('PDF: Project ID = ' . $project_id);
    
    if (!$project_id) {
        error_log('PDF: ID de projet invalide');
        wp_send_json_error('ID de projet invalide.');
    }
    
    // Récupérer les données directement au lieu d'utiliser l'autre handler
    error_log('PDF: Récupération directe des données du projet...');
    
    // Récupérer les informations du projet et de l'utilisateur
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        error_log('PDF: Projet introuvable pour ID: ' . $project_id);
        wp_send_json_error('Projet introuvable.');
    }
    
    $user = WP_BMC_Auth::get_current_user();
    $is_admin = current_user_can('manage_options');
    
    // Vérifier les permissions
    if (!$is_admin && !WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
        error_log('PDF: Permissions insuffisantes');
        wp_send_json_error('Vous n\'avez pas accès à ce projet.');
    }
    
    // Si c'est un admin qui accède au projet d'un autre utilisateur, récupérer les infos de cet utilisateur
    if ($is_admin && $project->user_id != $user->user_id) {
        $project_owner = WP_BMC_Database::get_user($project->user_id);
        if ($project_owner) {
            $user = $project_owner;
        }
    }
    
    error_log('PDF: Projet trouvé: ' . $project->title);
    error_log('PDF: Utilisateur: ' . $user->first_name . ' ' . $user->last_name);
    
    // Récupérer toutes les données nécessaires pour le PDF
    $canvas_data = WP_BMC_Database::get_canvas_data($project_id);
    $project_ratings = WP_BMC_Database::get_project_ratings($project_id);
    $project_todos = WP_BMC_Database::get_project_todos($project_id);
    
    error_log('PDF: Canvas data sections: ' . implode(', ', array_keys($canvas_data)));
    error_log('PDF: Ratings count: ' . count($project_ratings));
    error_log('PDF: Todos count: ' . count($project_todos));
    
    // Configuration des sections du canvas
    include_once WP_BMC_SHARED_DIR . 'Config/canvas-sections.php';
    $sections_config = wp_bmc_get_canvas_sections('global');
    
    error_log('PDF: Sections config: ' . implode(', ', array_keys($sections_config)));
    
    // Créer une structure de données simplifiée pour le template
    $pdf_data = array(
        'document' => array(
            'title' => 'Passeport de l\'entrepreneuriat - ' . $project->title,
            'author' => $user->first_name . ' ' . $user->last_name,
            'subject' => 'Passeport de l\'entrepreneuriat',
            'creator' => 'Passeport de l\'entrepreneuriat - OpenCampusInnov',
            'generated_at' => WP_BMC_Database::format_date_for_display(current_time('mysql'), 'date'),
            'language' => 'fr'
        ),
        'project' => array(
            'id' => intval($project_id),
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status,
            'created_at' => WP_BMC_Database::format_date_for_display($project->created_at),
            'updated_at' => WP_BMC_Database::format_date_for_display($project->updated_at)
        ),
        'user' => array(
            'id' => intval($user->user_id),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->first_name . ' ' . $user->last_name,
            'email' => $user->email,
        ),
        'canvas' => array(
            'sections' => array()
        ),
        'statistics' => array(
            'todos' => array(
                'total' => count($project_todos),
                'completed' => count(array_filter($project_todos, function($todo) {
                    return intval($todo->is_completed) === 1;
                })),
                'completion_rate' => count($project_todos) > 0 ? round((count(array_filter($project_todos, function($todo) {
                    return intval($todo->is_completed) === 1;
                })) / count($project_todos)) * 100, 1) : 0
            ),
            'content' => array(
                'sections_with_content' => count(array_filter($canvas_data, function($content) {
                    return !empty(trim(strip_tags($content)));
                })),
                'completion_percentage' => round((count(array_filter($canvas_data, function($content) {
                    return !empty(trim(strip_tags($content)));
                })) / count($sections_config)) * 100, 1),
                'total_characters' => array_sum(array_map(function($content) {
                    return strlen(strip_tags($content));
                }, $canvas_data))
            ),
            'ratings' => array(
                'total_ratings' => count($project_ratings),
                'average_rating' => count($project_ratings) > 0 ? round(array_sum(array_column($project_ratings, 'rating')) / count($project_ratings), 1) : 0,
                'sections_rated' => count(array_unique(array_column($project_ratings, 'section')))
            )
        )
    );
    
    // Organiser les données par section dans l'ordre de la configuration
    error_log('PDF: Création des sections dans l\'ordre de la config...');
    foreach ($sections_config as $section_key => $section_config) {
        $section_content = isset($canvas_data[$section_key]) ? $canvas_data[$section_key] : '';
        
        // Notes pour cette section
        $section_ratings = array_filter($project_ratings, function($rating) use ($section_key) {
            return $rating->section === $section_key;
        });
        
        // Todos pour cette section
        $section_todos = array_filter($project_todos, function($todo) use ($section_key) {
            return $todo->section === $section_key;
        });
        
        $pdf_data['canvas']['sections'][$section_key] = array(
            'title' => $section_config['title'],
            'content' => $section_content,
            'color' => $section_config['color'],
            'placeholder' => $section_config['placeholder'] ?? '',
            'ratings' => array(
                'latest' => !empty($section_ratings) ? array(
                    'rating' => intval(reset($section_ratings)->rating)
                ) : null
            ),
            'todos' => array(
                'total' => count($section_todos),
                'completed' => count(array_filter($section_todos, function($todo) {
                    return intval($todo->is_completed) === 1;
                })),
                'items' => array_map(function($todo) {
                    $is_completed = intval($todo->is_completed) === 1;
                    return array(
                        'text' => $todo->task_text,
                        'completed' => $is_completed
                    );
                }, array_values($section_todos))
            )
        );
        
        error_log('PDF: Section ' . $section_key . ' créée - couleur: ' . $section_config['color'] . ', contenu: ' . (empty($section_content) ? 'vide' : strlen($section_content) . ' chars'));
    }
    
    error_log('PDF: Toutes les sections créées. Ordre final: ' . implode(', ', array_keys($pdf_data['canvas']['sections'])));
    
    error_log('PDF: Structure de données créée, sections: ' . count($pdf_data['canvas']['sections']));
    
    // Debug: afficher les données de quelques sections
    foreach (array_slice($pdf_data['canvas']['sections'], 0, 2, true) as $key => $section) {
        error_log('PDF: Section ' . $key . ' - titre: ' . $section['title'] . ', todos: ' . count($section['todos']['items']) . ', content length: ' . strlen($section['content']));
    }
    
    // Charger le template HTML principal
    $template_path = WP_BMC_PLUGIN_DIR . 'templates/canvas-dashboard-template.html';
    error_log('PDF: Chemin template: ' . $template_path);
    error_log('PDF: Dossier plugin: ' . WP_BMC_PLUGIN_DIR);
    error_log('PDF: Template existe: ' . (file_exists($template_path) ? 'OUI' : 'NON'));
    
    if (!file_exists($template_path)) {
        error_log('PDF: Template introuvable à: ' . $template_path);
        // Lister les fichiers du dossier templates
        $templates_dir = WP_BMC_PLUGIN_DIR . 'templates/';
        if (is_dir($templates_dir)) {
            $files = scandir($templates_dir);
            error_log('PDF: Fichiers dans templates/: ' . implode(', ', $files));
        } else {
            error_log('PDF: Dossier templates/ n\'existe pas');
        }
        wp_send_json_error('Template PDF introuvable: ' . $template_path);
    }
    
    $template_content = file_get_contents($template_path);
    error_log('PDF: Template chargé, taille: ' . strlen($template_content) . ' caractères');
    
    // Compiler le template avec le moteur Handlebars-like
    error_log('PDF: Compilation du template');
    $compiled_html = compile_handlebars_template($template_content, $pdf_data);
    error_log('PDF: Template compilé, taille: ' . strlen($compiled_html) . ' caractères');
    
    // Sauvegarder le HTML temporairement pour Gotenberg
    $temp_html_path = WP_CONTENT_DIR . '/uploads/temp-canvas-' . $project_id . '.html';
    file_put_contents($temp_html_path, $compiled_html);
    error_log('PDF: HTML temp sauvegardé: ' . $temp_html_path);
    
    // Envoi à Gotenberg avec curl (comme wp_bmc_test_gotenberg)
    $gotenberg_url = 'https://gotenberg.beekom.fr/forms/chromium/convert/html';
    error_log('PDF: URL Gotenberg: ' . $gotenberg_url);
    
    $ch = curl_init($gotenberg_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => [
            'files' => curl_file_create($temp_html_path, 'text/html', 'index.html'),
            'paperWidth' => '11.7',
            'paperHeight' => '8.3',
            'marginTop' => '0',
            'marginBottom' => '0',
            'marginLeft' => '0',
            'marginRight' => '0',
            'landscape' => 'true',
            'printBackground' => 'true',
            'scale' => '1.0',
            'waitDelay' => '1s'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: Wh7YgK72Q6aWwDwyoiq2'
        ]
    ]);
    
    error_log('PDF: Envoi requête à Gotenberg');
    $pdf_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Supprimer le fichier temporaire
    if (file_exists($temp_html_path)) {
        unlink($temp_html_path);
    }
    
    error_log('PDF: Code HTTP: ' . $http_code);
    
    if ($curl_error) {
        error_log('PDF: Erreur cURL: ' . $curl_error);
        wp_send_json_error('Erreur de connexion à Gotenberg: ' . $curl_error);
    }
    
    if ($http_code !== 200) {
        error_log('PDF: Erreur Gotenberg (Code ' . $http_code . '): ' . substr($pdf_content, 0, 500));
        wp_send_json_error('Erreur Gotenberg (Code ' . $http_code . '): ' . substr($pdf_content, 0, 500));
    }
    
    // Vérifier que c'est bien un PDF
    if (substr($pdf_content, 0, 4) !== '%PDF') {
        error_log('PDF: Le contenu reçu n\'est pas un PDF');
        wp_send_json_error('Le contenu reçu n\'est pas un PDF valide');
    }
    
    // Sauvegarder le PDF dans le dossier uploads
    $upload_dir = wp_upload_dir();
    
    // Créer un nom de fichier basé sur le titre du projet
    $project_title_clean = sanitize_file_name($project->title);
    $project_title_clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project_title_clean);
    $project_title_clean = preg_replace('/_+/', '_', $project_title_clean);
    $date_iso = date('Ymd');
    
    $pdf_filename = $project_title_clean . '_' . $date_iso . '.pdf';
    $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
    $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;
    
    if (file_put_contents($pdf_path, $pdf_content) === false) {
        error_log('PDF: Erreur lors de la sauvegarde');
        wp_send_json_error('Erreur lors de la sauvegarde du PDF.');
    }
    
    error_log('PDF: PDF généré avec succès: ' . $pdf_filename . ' (' . strlen($pdf_content) . ' bytes)');
    error_log('=== FIN GÉNÉRATION PDF ===');
    
    wp_send_json_success([
        'pdf_url' => $pdf_url,
        'pdf_path' => $pdf_path,
        'filename' => $pdf_filename,
        'message' => 'PDF généré avec succès!'
    ]);
}


/**
 * Moteur de template Handlebars corrigé pour canvas-dashboard-template.html
 */
function compile_handlebars_template($template, $data) {
    error_log('HANDLEBARS: Début compilation...');
    error_log('HANDLEBARS: Taille du template: ' . strlen($template) . ' chars');
    
    // Vérifier si le pattern de boucle est présent
    if (preg_match('/\{\{#each\s+canvas\.sections\}\}/', $template)) {
        error_log('HANDLEBARS: Pattern de boucle canvas.sections trouvé dans le template');
    } else {
        error_log('HANDLEBARS: ERREUR - Pattern de boucle canvas.sections NON trouvé dans le template');
    }
    
    $compiled = $template;
    
    // 1. Traiter la boucle principale {{#each canvas.sections}}
    $compiled = preg_replace_callback('/\{\{#each\s+canvas\.sections\}\}(.*?)\{\{\/each\}\}/s', function($matches) use ($data) {
        $section_template = $matches[1];
        $all_sections_html = '';
        
        error_log('HANDLEBARS: Début traitement boucle sections');
        error_log('HANDLEBARS: Nombre de sections disponibles: ' . count($data['canvas']['sections']));
        error_log('HANDLEBARS: Clés des sections: ' . implode(', ', array_keys($data['canvas']['sections'])));
        
        $section_count = 0;
        foreach ($data['canvas']['sections'] as $section_key => $section_data) {
            $section_count++;
            $section_html = $section_template;
            
            error_log('HANDLEBARS: Traitement section #' . $section_count . ' - ' . $section_key . ' (' . $section_data['title'] . ')');
            
            // Remplacer {{@key}}
            $section_html = str_replace('{{@key}}', $section_key, $section_html);
            
            // Traiter {{#if ratings.latest}}
            $section_html = preg_replace_callback('/\{\{#if\s+ratings\.latest\}\}(.*?)\{\{\/if\}\}/s', function($if_matches) use ($section_data, $section_key) {
                if (!empty($section_data['ratings']['latest'])) {
                    $content = $if_matches[1];
                    $content = str_replace('{{ratings.latest.rating}}', $section_data['ratings']['latest']['rating'], $content);
                    error_log('HANDLEBARS: Section ' . $section_key . ' a une note: ' . $section_data['ratings']['latest']['rating']);
                    return $content;
                } else {
                    error_log('HANDLEBARS: Section ' . $section_key . ' sans note');
                }
                return '';
            }, $section_html);
            
            // Traiter {{#if content}}...{{else}}...{{/if}}
            $section_html = preg_replace_callback('/\{\{#if\s+content\}\}(.*?)(\{\{else\}\}(.*?))?\{\{\/if\}\}/s', function($if_matches) use ($section_data, $section_key) {
                $if_content = $if_matches[1];
                $else_content = isset($if_matches[3]) ? $if_matches[3] : '';
                
                if (!empty(trim(strip_tags($section_data['content'])))) {
                    $if_content = str_replace('{{{content}}}', $section_data['content'], $if_content);
                    error_log('HANDLEBARS: Section ' . $section_key . ' avec contenu (' . strlen($section_data['content']) . ' chars)');
                    return $if_content;
                } else {
                    $else_content = str_replace('{{placeholder}}', htmlspecialchars($section_data['placeholder']), $else_content);
                    error_log('HANDLEBARS: Section ' . $section_key . ' sans contenu, placeholder utilisé');
                    return $else_content;
                }
            }, $section_html);
            
            // Remplacer les variables simples de la section
            $section_html = str_replace('{{title}}', htmlspecialchars($section_data['title']), $section_html);
            $section_html = str_replace('{{color}}', $section_data['color'], $section_html);
            
            error_log('HANDLEBARS: Section ' . $section_key . ' HTML généré (' . strlen($section_html) . ' chars)');
            
            $all_sections_html .= $section_html;
        }
        
        error_log('HANDLEBARS: Toutes les sections traitées, HTML total: ' . strlen($all_sections_html) . ' chars');
        return $all_sections_html;
    }, $compiled);
    
    // 2. Remplacer toutes les variables globales
    $global_vars = array(
        '{{document.title}}' => $data['document']['title'],
        '{{document.generated_at}}' => $data['document']['generated_at'],
        '{{document.creator}}' => $data['document']['creator'],
        '{{project.title}}' => $data['project']['title'],
        '{{project.description}}' => $data['project']['description'],
        '{{user.full_name}}' => $data['user']['full_name'],
        '{{statistics.content.completion_percentage}}' => $data['statistics']['content']['completion_percentage'],
        '{{statistics.content.sections_with_content}}' => $data['statistics']['content']['sections_with_content'],
        '{{statistics.content.total_characters}}' => $data['statistics']['content']['total_characters'],
        '{{statistics.ratings.average_rating}}' => $data['statistics']['ratings']['average_rating'],
        '{{statistics.ratings.sections_rated}}' => $data['statistics']['ratings']['sections_rated'],
        '{{statistics.todos.completion_rate}}' => $data['statistics']['todos']['completion_rate'],
        '{{statistics.todos.total}}' => $data['statistics']['todos']['total']
    );
    
    foreach ($global_vars as $pattern => $value) {
        $compiled = str_replace($pattern, $value, $compiled);
    }
    
    // 3. Au lieu de remplacer par [VAR_NON_TRAITEE], supprimer simplement les variables restantes
    $compiled = preg_replace('/\{\{[^}]*\}\}/', '', $compiled);
    $compiled = preg_replace('/\{\{\{[^}]*\}\}\}/', '', $compiled);
    
    error_log('HANDLEBARS: Compilation terminée');
    return $compiled;
}


/**
 * Construire les données multipart pour Gotenberg v8
 */
function build_data_files($delimiter, $files, $fields) {
    $data = '';
    
    // Ajouter les fichiers d'abord (requis par Gotenberg)
    foreach ($files as $filename => $content) {
        $data .= "--{$delimiter}\r\n";
        $data .= "Content-Disposition: form-data; name=\"files\"; filename=\"{$filename}\"\r\n";
        $data .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $data .= $content . "\r\n";
    }
    
    // Ajouter les champs de formulaire
    foreach ($fields as $name => $value) {
        $data .= "--{$delimiter}\r\n";
        $data .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
        $data .= $value . "\r\n";
    }
    
    return $data;
}


// Handler pour vider le cache (admin)
add_action('wp_ajax_wp_bmc_clear_cache', 'wp_bmc_clear_cache_handler');
function wp_bmc_clear_cache_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    // Vider le cache WordPress
    wp_cache_flush();
    
    
   
 wp_send_json_success(array(
        'message' => 'Cache vidé avec succès.'
    ));
}



// ========================================
// HANDLERS POUR LA GESTION DES TODOS
// ========================================

// Handler pour ajouter une nouvelle tâche
add_action('wp_ajax_wp_bmc_add_todo', 'wp_bmc_add_todo_handler');
function wp_bmc_add_todo_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour ajouter une tâche.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $task_text = sanitize_text_field($_POST['task_text']);
    
    if (empty($section) || empty($task_text)) {
        wp_send_json_error('Section et texte de la tâche sont obligatoires.');
    }
    
    // Récupérer le project_id
    $project_id = wp_bmc_get_current_project_id();
    if (!$project_id) {
        wp_send_json_error('Aucun projet trouvé.');
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $user = WP_BMC_Auth::get_current_user();
    if (!current_user_can('manage_options') && !WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
        wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
    }
    
    // S'assurer que la table des todos existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    $todo_id = WP_BMC_Database::add_todo($project_id, $section, $task_text);
    
    if ($todo_id) {
    wp_send_json_success(array(
            'message' => 'Tâche ajoutée avec succès !',
            'todo_id' => $todo_id
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'ajout de la tâche.');
    }
}

// Handler pour obtenir les tâches d'une section
add_action('wp_ajax_wp_bmc_get_section_todos', 'wp_bmc_get_section_todos_handler');
function wp_bmc_get_section_todos_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour accéder aux tâches.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    
    if (empty($section)) {
        wp_send_json_error('Section manquante.');
    }
    
    // Si pas de project_id, utiliser le projet de l'utilisateur connecté
    if (!$project_id) {
        $user = WP_BMC_Auth::get_current_user();
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        if (empty($projects)) {
            wp_send_json_error('Aucun projet trouvé.');
        }
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    // Les admins peuvent accéder à tous les projets
    
    // S'assurer que la table des todos existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    $todos = WP_BMC_Database::get_section_todos($project_id, $section);
    $stats = WP_BMC_Database::get_section_todo_stats($project_id, $section);
    
    wp_send_json_success(array(
        'todos' => $todos,
        'stats' => $stats
    ));
}

// Handler pour basculer l'état d'une tâche
add_action('wp_ajax_wp_bmc_toggle_todo', 'wp_bmc_toggle_todo_handler');
function wp_bmc_toggle_todo_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour modifier une tâche.');
    }
    
    $todo_id = intval($_POST['todo_id']);
    
    if (!$todo_id) {
        wp_send_json_error('ID de tâche invalide.');
    }
    
    // Récupérer le project_id depuis la tâche elle-même
    global $wpdb;
    $table = $wpdb->prefix . 'bmc_todos';
    
    // S'assurer que la table existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $todo_id));
    
    if (!$todo) {
        wp_send_json_error('Tâche non trouvée.');
    }
    
    $project_id = $todo->project_id;
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour modifier cette tâche.');
        }
    }
    // Les admins peuvent modifier les tâches de tous les projets
    
    $result = WP_BMC_Database::toggle_todo($todo_id, $project_id);
    
    if ($result) {
    wp_send_json_success(array(
            'message' => 'État de la tâche mis à jour.'
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour de la tâche.');
    }
}

// Handler pour supprimer une tâche
add_action('wp_ajax_wp_bmc_delete_todo', 'wp_bmc_delete_todo_handler');
function wp_bmc_delete_todo_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour supprimer une tâche.');
    }
    
    $todo_id = intval($_POST['todo_id']);
    
    if (!$todo_id) {
        wp_send_json_error('ID de tâche invalide.');
    }
    
    // Récupérer le project_id depuis la tâche elle-même
    global $wpdb;
    $table = $wpdb->prefix . 'bmc_todos';
    
    // S'assurer que la table existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $todo_id));
    
    if (!$todo) {
        wp_send_json_error('Tâche non trouvée.');
    }
    
    $project_id = $todo->project_id;
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour supprimer cette tâche.');
        }
    }
    // Les admins peuvent supprimer les tâches de tous les projets
    
    $result = WP_BMC_Database::delete_todo($todo_id, $project_id);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Tâche supprimée avec succès.'
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression de la tâche.');
    }
}

// Handler pour modifier le texte d'une tâche
add_action('wp_ajax_wp_bmc_update_todo_text', 'wp_bmc_update_todo_text_handler');
function wp_bmc_update_todo_text_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour modifier une tâche.');
    }
    
    $todo_id = intval($_POST['todo_id']);
    $new_text = sanitize_text_field($_POST['new_text']);
    
    if (!$todo_id || empty($new_text)) {
        wp_send_json_error('ID de tâche et nouveau texte sont obligatoires.');
    }
    
    // Récupérer le project_id depuis la tâche elle-même
    global $wpdb;
    $table = $wpdb->prefix . 'bmc_todos';
    
    // S'assurer que la table existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $todo_id));
    
    if (!$todo) {
        wp_send_json_error('Tâche non trouvée.');
    }
    
    $project_id = $todo->project_id;
    
    // Vérifier que l'utilisateur a accès à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il a accès au projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if (!WP_BMC_Database::user_has_project_access($user->user_id, $project_id)) {
            wp_send_json_error('Vous n\'avez pas les droits pour modifier cette tâche.');
        }
    }
    // Les admins peuvent modifier les tâches de tous les projets
    
    $result = WP_BMC_Database::update_todo_text($todo_id, $project_id, $new_text);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Texte de la tâche mis à jour.'
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour du texte.');
    }
}

// Handler pour les opérations batch des todos
add_action('wp_ajax_wp_bmc_batch_todo_operations', 'wp_bmc_batch_todo_operations_handler');
function wp_bmc_batch_todo_operations_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté.');
    }
    
    $operations = isset($_POST['operations']) ? $_POST['operations'] : array();
    
    if (empty($operations)) {
        wp_send_json_success(array('message' => 'Aucune opération à traiter'));
        return;
    }
    
    // Récupérer le project_id depuis une tâche existante ou les paramètres
    $project_id = null;
    
    // Essayer de récupérer depuis les paramètres POST
    if (isset($_POST['project_id'])) {
        $project_id = intval($_POST['project_id']);
    }
    
    // Si pas de project_id, essayer de le récupérer depuis une tâche existante
    if (!$project_id && !empty($operations['update'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_todos';
        $first_todo_id = $operations['update'][0]['todo_id'];
        $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $first_todo_id));
        if ($todo) {
            $project_id = $todo->project_id;
        }
    }
    
    if (!$project_id && !empty($operations['delete'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_todos';
        $first_todo_id = $operations['delete'][0]['todo_id'];
        $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $first_todo_id));
        if ($todo) {
            $project_id = $todo->project_id;
        }
    }
    
    if (!$project_id && !empty($operations['toggle'])) {
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_todos';
        $first_todo_id = $operations['toggle'][0]['todo_id'];
        $todo = $wpdb->get_row($wpdb->prepare("SELECT project_id FROM $table WHERE id = %d", $first_todo_id));
        if ($todo) {
            $project_id = $todo->project_id;
        }
    }
    
    if (!$project_id) {
        wp_send_json_error('Impossible de déterminer le projet.');
    }
    
    $results = array();
    
    // S'assurer que la table existe
    WP_BMC_Database::ensure_todos_table_exists();
    
    // Traiter les ajouts
    if (!empty($operations['add'])) {
        foreach ($operations['add'] as $add_op) {
            $todo_id = WP_BMC_Database::add_todo(
                $project_id,
                $add_op['section'],
                $add_op['task_text']
            );
            if ($todo_id) {
                $results['add'][] = array(
                    'temp_id' => $add_op['temp_id'] ?? null,
                    'real_id' => $todo_id
                );
            }
        }
    }
    
    // Traiter les mises à jour
    if (!empty($operations['update'])) {
        foreach ($operations['update'] as $update_op) {
            $success = WP_BMC_Database::update_todo_text(
                $update_op['todo_id'],
                $project_id,
                $update_op['new_text']
            );
            $results['update'][] = array(
                'todo_id' => $update_op['todo_id'],
                'success' => $success
            );
        }
    }
    
    // Traiter les suppressions
    if (!empty($operations['delete'])) {
        foreach ($operations['delete'] as $delete_op) {
            $success = WP_BMC_Database::delete_todo(
                $delete_op['todo_id'],
                $project_id
            );
            $results['delete'][] = array(
                'todo_id' => $delete_op['todo_id'],
                'success' => $success
            );
        }
    }
    
    // Traiter les changements d'état
    if (!empty($operations['toggle'])) {
        foreach ($operations['toggle'] as $toggle_op) {
            $success = WP_BMC_Database::toggle_todo(
                $toggle_op['todo_id'],
                $project_id
            );
            $results['toggle'][] = array(
                'todo_id' => $toggle_op['todo_id'],
                'success' => $success
            );
        }
    }
    
    wp_send_json_success(array(
        'message' => 'Opérations traitées avec succès',
        'results' => $results
    ));
}

// Handler temporaire pour debug - forcer la création de la table des todos
add_action('wp_ajax_wp_bmc_debug_create_todos_table', 'wp_bmc_debug_create_todos_table_handler');
function wp_bmc_debug_create_todos_table_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté.');
    }
    
    // Forcer la création de la table
    WP_BMC_Database::force_create_todos_table();
    
    wp_send_json_success(array(
        'message' => 'Table des todos recréée avec succès !'
    ));
}

// Handler pour réinitialiser toutes les données du plugin
add_action('wp_ajax_wp_bmc_reset_all_data', 'wp_bmc_reset_all_data_handler');
function wp_bmc_reset_all_data_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    // Vérifier que l'utilisateur est administrateur
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Vous devez être administrateur pour effectuer cette action.');
    }
    
    // Confirmation requise
    $confirm = isset($_POST['confirm']) ? sanitize_text_field($_POST['confirm']) : '';
    if ($confirm !== 'RESET_ALL_DATA') {
        wp_send_json_error('Confirmation requise. Envoyez "confirm: RESET_ALL_DATA" pour confirmer la suppression.');
    }
    
    try {
        // Réinitialiser toutes les données
        $result = WP_BMC_Database::reset_all_data();
        
        wp_send_json_success(array(
            'message' => 'Toutes les données ont été réinitialisées avec succès !',
            'total_deleted' => $result['total_deleted'],
            'details' => $result['details']
        ));
        
    } catch (Exception $e) {
        error_log('wp_bmc_reset_all_data - Erreur : ' . $e->getMessage());
        wp_send_json_error('Erreur lors de la réinitialisation : ' . $e->getMessage());
    }
}

// Fonction utilitaire pour récupérer l'ID du projet actuel
function wp_bmc_get_current_project_id() {
    // Essayer de récupérer depuis les paramètres POST
    if (isset($_POST['project_id'])) {
        return intval($_POST['project_id']);
    }
    
    // Essayer de récupérer depuis l'URL de référence
    $referer = wp_get_referer();
    if ($referer) {
        $url_parts = parse_url($referer);
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $query_params);
            if (isset($query_params['project_id'])) {
                return intval($query_params['project_id']);
            }
        }
    }
    
    // Utiliser le projet de l'utilisateur connecté
    $user = WP_BMC_Auth::get_current_user();
    if ($user) {
        $projects = WP_BMC_Database::get_user_projects($user->user_id);
        if (!empty($projects)) {
            return $projects[0]->id;
        }
        
        // Si l'utilisateur est admin et n'a pas de projet personnel, utiliser le premier projet disponible
        if (current_user_can('manage_options')) {
            global $wpdb;
            $projects_table = $wpdb->prefix . 'bmc_projects';
            $first_project = $wpdb->get_row("SELECT id FROM $projects_table ORDER BY id ASC LIMIT 1");
            if ($first_project) {
                return $first_project->id;
            }
        }
    }
    
    return null;
}

// ========================================
// GESTION DES UTILISATEURS DISPONIBLES (v2.0)
// ========================================

add_action('wp_ajax_wp_bmc_get_available_users', 'wp_bmc_get_available_users_handler');
function wp_bmc_get_available_users_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }

    $project_id = intval($_POST['project_id']);

    if (!$project_id) {
        wp_send_json_error('ID de projet invalide.');
    }

    // Obtenir tous les utilisateurs actifs
    $all_users = WP_BMC_Database::get_all_users();
    
    // Obtenir TOUS les utilisateurs déjà liés à un projet (peu importe le projet)
    global $wpdb;
    $table_project_users = $wpdb->prefix . 'bmc_project_users';
    $all_assigned_user_ids = $wpdb->get_col("
        SELECT DISTINCT user_id 
        FROM $table_project_users 
        WHERE is_active = 1
    ");
    
    // Filtrer les utilisateurs non liés à aucun projet
    $available_users = array_filter($all_users, function($user) use ($all_assigned_user_ids) {
        return !in_array($user->user_id, $all_assigned_user_ids);
    });

    wp_send_json_success(array(
        'users' => array_values($available_users)
    ));
}

// ========================================
// GESTION DES ADMINISTRATEURS DISPONIBLES (v2.0)
// ========================================



// ========================================
// GESTION DES STATUTS UTILISATEUR (v2.0)
// ========================================

add_action('wp_ajax_wp_bmc_update_user_status', 'wp_bmc_update_user_status_handler');
function wp_bmc_update_user_status_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }

    $user_id = intval($_POST['user_id']);
    $status = sanitize_text_field($_POST['status']);

    if (!$user_id || !in_array($status, array('active', 'pending', 'disabled'))) {
        wp_send_json_error('Paramètres invalides.');
    }

    $result = WP_BMC_Database::update_user_status($user_id, $status);

    if ($result) {
        $status_labels = array(
            'active' => 'Actif',
            'pending' => 'En attente',
            'disabled' => 'Désactivé'
        );
        
        wp_send_json_success(array(
            'message' => 'Statut mis à jour : ' . $status_labels[$status]
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour du statut.');
    }
}

// Handler pour supprimer un utilisateur
add_action('wp_ajax_wp_bmc_delete_user', 'wp_bmc_delete_user_handler');
function wp_bmc_delete_user_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé.');
    }

    $user_id = intval($_POST['user_id']);

    if (!$user_id) {
        wp_send_json_error('ID utilisateur invalide.');
    }

    error_log("wp_bmc_delete_user_handler - Suppression demandée pour l'utilisateur ID: $user_id");

    $result = WP_BMC_Database::delete_user($user_id);

    if ($result && $result['success']) {
        error_log("wp_bmc_delete_user_handler - Suppression réussie pour l'utilisateur ID: $user_id");
        wp_send_json_success(array(
            'message' => 'Utilisateur supprimé avec succès !',
            'details' => $result['details'],
            'total_deleted' => $result['total_deleted'],
            'user_info' => $result['user_info']
        ));
    } else {
        error_log("wp_bmc_delete_user_handler - Échec de la suppression pour l'utilisateur ID: $user_id");
        wp_send_json_error('Erreur lors de la suppression de l\'utilisateur.');
    }
}

// ========================================
// GESTION DU CHANGEMENT DE MOT DE PASSE
// ========================================

// Handler pour vérifier si un changement de mot de passe est requis
add_action('wp_ajax_wp_bmc_check_password_change_required', 'wp_bmc_check_password_change_required_handler');
function wp_bmc_check_password_change_required_handler() {
    error_log('=== DEBUG CHANGEMENT MOT DE PASSE PHP ===');
    error_log('wp_bmc_check_password_change_required_handler - Début de la vérification');
    
    // Accepter les deux types de nonces (admin et public)
    $nonce_valid = false;
    
    // Essayer d'abord le nonce admin
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_admin_nonce')) {
        $nonce_valid = true;
        error_log('wp_bmc_check_password_change_required_handler - Nonce admin valide');
    }
    // Sinon essayer le nonce public
    elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_nonce')) {
        $nonce_valid = true;
        error_log('wp_bmc_check_password_change_required_handler - Nonce public valide');
    }
    
    if (!$nonce_valid) {
        error_log('wp_bmc_check_password_change_required_handler - Nonce invalide');
        wp_send_json_error('Nonce de sécurité invalide.');
    }
    
    if (!WP_BMC_Auth::is_logged_in()) {
        error_log('wp_bmc_check_password_change_required_handler - Utilisateur non connecté');
        wp_send_json_error('Connexion requise');
    }
    
    $current_user = WP_BMC_Auth::get_current_user();
    
    if (!$current_user) {
        error_log('wp_bmc_check_password_change_required_handler - Utilisateur non trouvé');
        wp_send_json_error('Utilisateur non trouvé');
    }
    
    error_log('wp_bmc_check_password_change_required_handler - Utilisateur trouvé: ' . $current_user->email);
    error_log('wp_bmc_check_password_change_required_handler - Statut utilisateur: ' . (isset($current_user->status) ? $current_user->status : 'non défini'));
    
    // Vérifier si l'utilisateur a le statut 'pending' (première connexion)
    // ou s'il a un flag de changement de mot de passe requis
    $required = false;
    
    if (isset($current_user->status) && $current_user->status === 'pending') {
        $required = true;
        error_log('wp_bmc_check_password_change_required_handler - Changement requis: statut pending');
    }
    
    // Vérifier aussi dans les meta utilisateur WordPress
    $password_change_required = get_user_meta($current_user->user_id, 'wp_bmc_password_change_required', true);
    if ($password_change_required) {
        $required = true;
        error_log('wp_bmc_check_password_change_required_handler - Changement requis: meta wp_bmc_password_change_required');
    }
    
    error_log('wp_bmc_check_password_change_required_handler - Résultat final: ' . ($required ? 'requis' : 'non requis'));
    
    wp_send_json_success(array(
        'required' => $required
    ));
}

// Handler pour obtenir le template du popup de changement de mot de passe
add_action('wp_ajax_wp_bmc_get_change_password_popup', 'wp_bmc_get_change_password_popup_handler');
function wp_bmc_get_change_password_popup_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');

    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Connexion requise');
    }

    // Charger le template du popup
    ob_start();
    include WP_BMC_PLUGIN_DIR . 'src/Shared/Templates/public/change-password-popup.php';
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html
    ));
}

// Handler pour changer le mot de passe
add_action('wp_ajax_wp_bmc_change_password', 'wp_bmc_change_password_handler');
function wp_bmc_change_password_handler() {
    // Accepter les deux types de nonces (admin et public)
    $nonce_valid = false;
    
    // Essayer d'abord le nonce admin
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_admin_nonce')) {
        $nonce_valid = true;
    }
    // Sinon essayer le nonce public
    elseif (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wp_bmc_nonce')) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error('Nonce de sécurité invalide.');
    }
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Connexion requise');
    }
    
    $current_user = WP_BMC_Auth::get_current_user();
    
    if (!$current_user) {
        wp_send_json_error('Utilisateur non trouvé');
    }
    
    $current_password = sanitize_text_field($_POST['current_password']);
    $new_password = sanitize_text_field($_POST['new_password']);
    $confirm_password = sanitize_text_field($_POST['confirm_password']);
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        wp_send_json_error('Tous les champs sont obligatoires');
    }
    
    if (strlen($new_password) < 6) {
        wp_send_json_error('Le nouveau mot de passe doit contenir au moins 6 caractères');
    }
    
    if ($new_password !== $confirm_password) {
        wp_send_json_error('Les mots de passe ne correspondent pas');
    }
    
    // Vérifier le mot de passe actuel
    $user = WP_BMC_Database::verify_login($current_user->email, $current_password);
    
    if (!$user) {
        wp_send_json_error('Mot de passe actuel incorrect');
    }
    
    // Changer le mot de passe dans WordPress
    wp_set_password($new_password, $current_user->user_id);
    
    // Mettre à jour le mot de passe dans la table BMC
    global $wpdb;
    $table = $wpdb->prefix . 'bmc_users';
    $result = $wpdb->update(
        $table,
        array('password' => $new_password),
        array('user_id' => $current_user->user_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        // Supprimer le flag de changement de mot de passe requis
        delete_user_meta($current_user->user_id, 'wp_bmc_password_change_required');
        
        // Mettre à jour le statut de l'utilisateur s'il était en 'pending'
        if (isset($current_user->status) && $current_user->status === 'pending') {
            WP_BMC_Database::update_user_status($current_user->user_id, 'active');
        }
        
        wp_send_json_success(array(
            'message' => 'Mot de passe changé avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour du mot de passe');
    }
}

// ========================================
// GESTION ADMINISTRATEUR - UTILISATEURS
// ========================================

// Handler pour récupérer l'ID utilisateur WordPress depuis l'ID BMC
add_action('wp_ajax_wp_bmc_get_wp_user_id', 'wp_bmc_get_wp_user_id_handler');
function wp_bmc_get_wp_user_id_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $bmc_user_id = intval($_POST['bmc_user_id']);
    
    if (!$bmc_user_id) {
        wp_send_json_error('ID utilisateur BMC invalide');
    }
    
    // Récupérer l'utilisateur BMC
    $bmc_user = WP_BMC_Database::get_user_by_id($bmc_user_id);
    
    if (!$bmc_user) {
        wp_send_json_error('Utilisateur BMC non trouvé');
    }
    
    $wp_user_id = $bmc_user->user_id;
    
    // Vérifier que l'utilisateur WordPress existe
    $wp_user = get_user_by('id', $wp_user_id);
    
    if (!$wp_user) {
        wp_send_json_error('Utilisateur WordPress non trouvé');
    }
    
    // Générer le nonce pour la réinitialisation de mot de passe
    $reset_nonce = wp_create_nonce('reset-password_' . $wp_user_id);
    
    wp_send_json_success(array(
        'wp_user_id' => $wp_user_id,
        'nonce' => $reset_nonce,
        'user_email' => $wp_user->user_email,
        'display_name' => $wp_user->display_name
    ));
}

// Handler pour récupérer les données d'un projet pour édition
add_action('wp_ajax_wp_bmc_get_project_data', 'wp_bmc_get_project_data_handler');
function wp_bmc_get_project_data_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $project_id = intval($_POST['project_id']);
    
    if (empty($project_id)) {
        wp_send_json_error('ID de projet invalide');
    }
    
    $project = WP_BMC_Database::get_project($project_id);
    
    if (!$project) {
        wp_send_json_error('Projet non trouvé');
    }
    
    wp_send_json_success(array(
        'project' => $project
    ));
}

// Handler pour éditer un projet
add_action('wp_ajax_wp_bmc_edit_project', 'wp_bmc_edit_project_handler');
function wp_bmc_edit_project_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    $project_id = intval($_POST['project_id']); 
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);

    if (empty($project_id) || empty($title) || empty($description)) {
        wp_send_json_error('Paramètres invalides');
    }
    
    $result = WP_BMC_Database::edit_project($project_id, $title, $description);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Projet édité avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'édition du projet.');
    }
}

// ========================================
// GESTION DES CONFIGURATIONS DU CANVAS
// ========================================

// Handler pour récupérer les configurations des sections du canvas
add_action('wp_ajax_wp_bmc_get_canvas_configs', 'wp_bmc_get_canvas_configs_handler');
function wp_bmc_get_canvas_configs_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    // S'assurer que la table des configurations existe
    WP_BMC_Database::create_tables();
    
    // Vider le cache des configurations pour forcer le rechargement
    WP_BMC_Canvas_Config::clear_cache();
    
    $configs = WP_BMC_Database::get_all_canvas_configs();
    
    // Inclure la fonction des sections si elle n'est pas disponible
    if (!function_exists('wp_bmc_get_canvas_sections')) {
        include_once WP_BMC_PLUGIN_DIR . 'src/Shared/Config/canvas-sections.php';
    }
    
    $sections = wp_bmc_get_canvas_sections('global', false); // Utiliser les valeurs par défaut du fichier
    
    // Préparer les données pour l'interface admin
    $formatted_configs = array();
    foreach ($sections as $section_key => $section_config) {
        $formatted_configs[$section_key] = array(
            'title' => isset($configs[$section_key]['title']) ? stripslashes($configs[$section_key]['title']) : $section_config['title'],
            'placeholder' => isset($configs[$section_key]['placeholder']) ? stripslashes($configs[$section_key]['placeholder']) : $section_config['placeholder'],
            'default_title' => $section_config['title'],
            'default_placeholder' => $section_config['placeholder']
        );
    }
    
    wp_send_json_success(array(
        'configs' => $formatted_configs
    ));
}

// Handler pour sauvegarder les configurations des sections du canvas
add_action('wp_ajax_wp_bmc_save_canvas_configs', 'wp_bmc_save_canvas_configs_handler');
function wp_bmc_save_canvas_configs_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    // S'assurer que la table des configurations existe
    WP_BMC_Database::create_tables();
    
    $configs = $_POST['configs'];
    
    if (!is_array($configs)) {
        wp_send_json_error('Format de données invalide');
    }
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($configs as $section_key => $section_configs) {
        // Sauvegarder le titre
        if (isset($section_configs['title'])) {
            $result = WP_BMC_Database::save_canvas_section_config(
                $section_key,
                'title',
                sanitize_text_field($section_configs['title'])
            );
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        // Sauvegarder le placeholder (avec HTML autorisé pour la mise en forme)
        if (isset($section_configs['placeholder'])) {
            // Utiliser wp_kses_post pour autoriser les balises HTML de base tout en sécurisant
            $result = WP_BMC_Database::save_canvas_section_config(
                $section_key,
                'placeholder',
                wp_kses_post($section_configs['placeholder'])
            );
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($error_count === 0) {
        wp_send_json_success(array(
            'message' => 'Configurations sauvegardées avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la sauvegarde de certaines configurations.');
    }
}

// ========================================
// GESTION DE LA PRÉSENCE EN TEMPS RÉEL (HEARTBEAT API)
// ========================================

/**
 * Hook Heartbeat pour gérer la présence des utilisateurs
 */
add_filter('heartbeat_received', 'wp_bmc_heartbeat_received', 10, 2);
function wp_bmc_heartbeat_received($response, $data) {
    // Vérifier si l'utilisateur est connecté au plugin
    if (!WP_BMC_Auth::is_logged_in()) {
        error_log('WP_BMC_Heartbeat : Utilisateur non connecté au plugin');
        return $response;
    }
    
    // Vérifier si des données de présence BMC sont envoyées
    if (!isset($data['wp_bmc_presence'])) {
        return $response;
    }
    
    $user = WP_BMC_Auth::get_current_user();
    if (!$user) {
        error_log('WP_BMC_Heartbeat : Utilisateur non trouvé');
        return $response;
    }
    
    $activity = $data['wp_bmc_presence'];
    
    $project_id = intval($activity['project_id']);
    $section = isset($activity['section']) ? sanitize_text_field($activity['section']) : null;
    $is_editing = isset($activity['is_editing']) ? intval($activity['is_editing']) : 0;
    
    error_log('WP_BMC_Heartbeat : Ping reçu - User: ' . $user->user_id . ' (' . $user->first_name . ' ' . $user->last_name . '), Project: ' . $project_id . ', Section: ' . ($section ?? 'NULL') . ', Editing: ' . $is_editing);
    
    if (!$project_id) {
        error_log('WP_BMC_Heartbeat : Project ID manquant');
        return $response;
    }
    
    // Vérifier que l'utilisateur a accès au projet
    $is_admin = current_user_can('manage_options');
    
    // Les admins ont accès à tous les projets, pas besoin de vérifier
    if (!$is_admin) {
        $has_project_access = WP_BMC_Database::user_has_project_access($user->user_id, $project_id);
        
        if (!$has_project_access) {
            error_log('WP_BMC_Heartbeat : Accès refusé au projet ' . $project_id . ' pour l\'utilisateur ' . $user->user_id . ' (non assigné)');
            return $response;
        }
        
        error_log('WP_BMC_Heartbeat : Accès autorisé - Utilisateur assigné au projet');
    } else {
        error_log('WP_BMC_Heartbeat : Accès autorisé - Admin (bypass)');
    }
    
    // Mettre à jour la session de l'utilisateur
    WP_BMC_Database::update_user_session(
        $user->user_id,
        $project_id,
        $section,
        $is_editing
    );
    
    // Récupérer les autres utilisateurs actifs sur ce projet
    $active_users = WP_BMC_Database::get_active_project_users($project_id, $user->user_id);
    
    // Formater les données pour le frontend
    $formatted_users = array();
    foreach ($active_users as $u) {
        $section_title = $u->section ? WP_BMC_Database::get_section_display_name($u->section) : null;
        
        $formatted_users[] = array(
            'user_id' => intval($u->user_id),
            'full_name' => $u->first_name . ' ' . $u->last_name,
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'initials' => strtoupper(substr($u->first_name, 0, 1) . substr($u->last_name, 0, 1)),
            'section' => $u->section,
            'section_title' => $section_title,
            'is_editing' => intval($u->is_editing) === 1,
            'last_ping' => $u->last_ping,
            'seconds_ago' => time() - strtotime($u->last_ping)
        );
    }
    
    $response['wp_bmc_active_users'] = $formatted_users;
    
    return $response;
}

/**
 * Configurer l'intervalle Heartbeat pour les pages du canvas
 */
add_filter('heartbeat_settings', 'wp_bmc_heartbeat_settings');
function wp_bmc_heartbeat_settings($settings) {
    // Vérifier si on est sur une page du canvas ou du dashboard
    if (is_page('business-model-canvas') || is_page('dashboard')) {
        $settings['interval'] = 15; // 15 secondes pour la réactivité
    }
    
    return $settings;
}