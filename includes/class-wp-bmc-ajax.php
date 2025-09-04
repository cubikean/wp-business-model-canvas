<?php
/**
 * Handlers AJAX pour WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handler pour créer un nouveau projet
add_action('wp_ajax_wp_bmc_create_project', 'wp_bmc_create_project_handler');
function wp_bmc_create_project_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour créer un projet.');
    }
    
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    
    if (empty($title)) {
        wp_send_json_error('Le titre du projet est obligatoire.');
    }
    
    $user = WP_BMC_Auth::get_current_user();
    $project_id = WP_BMC_Database::create_project($user->user_id, $title, $description);
    
    if ($project_id) {
        wp_send_json_success(array(
            'message' => 'Projet créé avec succès !',
            'project_id' => $project_id,
            'redirect_url' => home_url('/dashboard/')
        ));
    } else {
        wp_send_json_error('Erreur lors de la création du projet.');
    }
}

// Handler pour sauvegarder le canvas
add_action('wp_ajax_wp_bmc_save_canvas', 'wp_bmc_save_canvas_handler');
function wp_bmc_save_canvas_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!WP_BMC_Auth::is_logged_in()) {
        wp_send_json_error('Vous devez être connecté pour sauvegarder le canvas.');
    }
    
    $canvas_data = $_POST['canvas_data'];
    
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
            wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
        }
        
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il est propriétaire du projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if ($project->user_id != $user->user_id) {
            wp_send_json_error('Vous n\'avez pas les droits pour accéder à ce projet.');
        }
    }
    
    // Sauvegarder chaque section du canvas
    $sections = array(
        'key_partners',
        'key_activities',
        'key_resources',
        'value_proposition',
        'customer_relationships',
        'channels',
        'customer_segments',
        'cost_structure',
        'revenue_streams'
    );
    
    $success_count = 0;
    foreach ($sections as $section) {
        $content = isset($canvas_data[$section]) ? sanitize_textarea_field($canvas_data[$section]) : '';
        if (WP_BMC_Database::save_canvas_data($project_id, $section, $content)) {
            $success_count++;
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
    
    // Vérifier que l'utilisateur possède ce projet
    $user = WP_BMC_Auth::get_current_user();
    $projects = WP_BMC_Database::get_user_projects($user->user_id);
    $user_has_project = false;
    
    foreach ($projects as $project) {
        if ($project->id == $project_id) {
            $user_has_project = true;
            break;
        }
    }
    
    if (!$user_has_project) {
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
    
    // Vérifier que l'utilisateur possède ce projet
    $user = WP_BMC_Auth::get_current_user();
    $projects = WP_BMC_Database::get_user_projects($user->user_id);
    $user_has_project = false;
    
    foreach ($projects as $project) {
        if ($project->id == $project_id) {
            $user_has_project = true;
            break;
        }
    }
    
    if (!$user_has_project) {
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
            wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
        }
        
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il est propriétaire du projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if ($project->user_id != $user->user_id) {
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
            wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
        }
        
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il est propriétaire du projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if ($project->user_id != $user->user_id) {
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
            wp_send_json_error('Aucun projet trouvé pour cet utilisateur.');
        }
        
        $project_id = $projects[0]->id;
    }
    
    // Vérifier que l'utilisateur a le droit d'accéder à ce projet
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project) {
        wp_send_json_error('Projet non trouvé.');
    }
    
    // Si l'utilisateur n'est pas admin, vérifier qu'il est propriétaire du projet
    if (!current_user_can('manage_options')) {
        $user = WP_BMC_Auth::get_current_user();
        if ($project->user_id != $user->user_id) {
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
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = intval($_POST['project_id']);
    $admin_id = get_current_user_id();
    
    if (empty($section) || !$project_id) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $rating = WP_BMC_Database::get_section_rating($project_id, $section, $admin_id);
    
    wp_send_json_success(array(
        'rating' => $rating
    ));
}

// Handler pour sauvegarder une note
add_action('wp_ajax_wp_bmc_save_section_rating', 'wp_bmc_save_section_rating_handler');
function wp_bmc_save_section_rating_handler() {
    check_ajax_referer('wp_bmc_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $section = sanitize_text_field($_POST['section']);
    $project_id = intval($_POST['project_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    $admin_id = get_current_user_id();
    
    if (empty($section) || !$project_id || $rating < 0 || $rating > 10) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    $result = WP_BMC_Database::save_section_rating($project_id, $section, $admin_id, $rating, $comment);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Note sauvegardée avec succès !'
        ));
    } else {
        wp_send_json_error('Erreur lors de la sauvegarde de la note.');
    }
}
