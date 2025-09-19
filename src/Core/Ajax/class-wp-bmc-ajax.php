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
    
    if (!isset($_POST['canvas_data'])) {
        wp_send_json_error('Données du canvas manquantes.');
    }
    
    $canvas_data = $_POST['canvas_data'];
    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    
    // Si pas de project_id, utiliser le projet de l'utilisateur connecté
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
        $raw_content = isset($canvas_data[$section]) ? $canvas_data[$section] : '';
        
        // Utiliser wp_kses avec des règles permissives pour TinyMCE
        $allowed_html = array(
            'p' => array('style' => array(), 'class' => array()),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
            'em' => array(),
            'i' => array(),
            'u' => array(),
            'h1' => array('style' => array(), 'class' => array()),
            'h2' => array('style' => array(), 'class' => array()),
            'h3' => array('style' => array(), 'class' => array()),
            'h4' => array('style' => array(), 'class' => array()),
            'h5' => array('style' => array(), 'class' => array()),
            'h6' => array('style' => array(), 'class' => array()),
            'ul' => array('style' => array(), 'class' => array()),
            'ol' => array('style' => array(), 'class' => array()),
            'li' => array('style' => array(), 'class' => array()),
            'a' => array('href' => array(), 'title' => array(), 'target' => array(), 'style' => array(), 'class' => array()),
            'img' => array('src' => array(), 'alt' => array(), 'width' => array(), 'height' => array(), 'style' => array(), 'class' => array()),
            'div' => array('style' => array(), 'class' => array()),
            'span' => array('style' => array(), 'class' => array()),
            'blockquote' => array('style' => array(), 'class' => array()),
            'code' => array('style' => array(), 'class' => array()),
            'pre' => array('style' => array(), 'class' => array()),
            'table' => array('style' => array(), 'class' => array(), 'border' => array()),
            'tr' => array('style' => array(), 'class' => array()),
            'td' => array('style' => array(), 'class' => array(), 'colspan' => array(), 'rowspan' => array()),
            'th' => array('style' => array(), 'class' => array(), 'colspan' => array(), 'rowspan' => array()),
            'tbody' => array('style' => array(), 'class' => array()),
            'thead' => array('style' => array(), 'class' => array()),
            'tfoot' => array('style' => array(), 'class' => array())
        );
        
        $content = wp_kses($raw_content, $allowed_html);
        
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
        
        // Créer une révision avec la note et le commentaire
        WP_BMC_Database::create_section_revision(
            $project_id, 
            $section, 
            $current_content, 
            'admin_rating',
            $rating,
            $comment,
            $admin_id
        );
        
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
    if ($user->user_id != $project->user_id && !current_user_can('manage_options')) {
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
    if ($user->user_id != $project->user_id && !current_user_can('manage_options')) {
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
        
        // Vérifier si c'est un mode admin ou si l'utilisateur est le propriétaire
        $is_admin = current_user_can('manage_options');
        if (!$is_admin && $current_user->user_id != $project->user_id) {
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
    fputcsv($file, array('Nom', 'Email', 'Entreprise', 'Projets', 'Dernière activité', 'Demandes de notation'));
    
    // Données
    foreach ($users as $user) {
        fputcsv($file, array(
            $user->display_name,
            $user->user_email,
            $user->company,
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
    if (!$is_admin && $project->user_id != $user->user_id) {
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
            'title' => 'Business Model Canvas - ' . $project->title,
            'author' => $user->first_name . ' ' . $user->last_name,
            'subject' => 'Business Model Canvas',
            'creator' => 'WP Business Model Canvas Plugin',
            'generated_at' => WP_BMC_Database::format_date_for_display(current_time('mysql')),
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
            'company' => $user->company
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
    if (!$is_admin && $project->user_id != $user->user_id) {
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
            'title' => 'Business Model Canvas - ' . $project->title,
            'author' => $user->first_name . ' ' . $user->last_name,
            'subject' => 'Business Model Canvas',
            'creator' => 'WP Business Model Canvas Plugin',
            'generated_at' => WP_BMC_Database::format_date_for_display(current_time('mysql')),
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
            'company' => $user->company
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
    error_log('PDF: Compilation du template avec moteur complet...');
    $compiled_html = compile_handlebars_template($template_content, $pdf_data);
    error_log('PDF: Template compilé, taille: ' . strlen($compiled_html) . ' caractères');
    
    // Sauvegarder le HTML compilé pour debug
    $debug_html_path = WP_CONTENT_DIR . '/uploads/debug-canvas.html';
    file_put_contents($debug_html_path, $compiled_html);
    error_log('PDF: HTML debug sauvegardé: ' . $debug_html_path);
    
    // Faire la requête à Gotenberg v8 - tester plusieurs adresses pour Docker
    $gotenberg_hosts = array(
        'gotenberg-gotenberg-1:3000', // Nom du container Gotenberg
        'host.docker.internal:3000',  // Docker Desktop Windows/Mac
        '172.17.0.1:3000',            // Docker Linux gateway
        'localhost:3000',             // Si pas dans un container
        '127.0.0.1:3000'              // Fallback localhost
    );
    
    $gotenberg_url = null;
    $working_host = null;
    
    foreach ($gotenberg_hosts as $host) {
        error_log('PDF: Test connexion à: ' . $host);
        $test_response = wp_remote_get('http://' . $host . '/health', array('timeout' => 5));
        
        if (!is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) === 200) {
            $working_host = $host;
            $gotenberg_url = 'http://' . $host . '/forms/chromium/convert/html';
            error_log('PDF: Gotenberg trouvé sur: ' . $host);
            break;
        } else {
            error_log('PDF: Échec connexion à: ' . $host . ' - ' . (is_wp_error($test_response) ? $test_response->get_error_message() : 'Code: ' . wp_remote_retrieve_response_code($test_response)));
        }
    }
    
    if (!$gotenberg_url) {
        error_log('PDF: Aucun serveur Gotenberg accessible');
        wp_send_json_error('Gotenberg inaccessible sur toutes les adresses testées. Vérifiez que Gotenberg est démarré.');
    }
    
    error_log('PDF: URL Gotenberg: ' . $gotenberg_url);
    
    // Utiliser wp_remote_post avec les fichiers pour Gotenberg v8
    $boundary = 'wpbmc' . uniqid();
    
    // Construire la requête multipart manuellement
    $post_data = '';
    
    // Ajouter le fichier HTML avec la syntaxe correcte pour Gotenberg v8
    $post_data .= "--{$boundary}\r\n";
    $post_data .= "Content-Disposition: form-data; name=\"files\"; filename=\"index.html\"\r\n";
    $post_data .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $post_data .= $compiled_html . "\r\n";
    
    // Paramètres pour Gotenberg v8
    $params = array(
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
    );
    
    foreach ($params as $name => $value) {
        $post_data .= "--{$boundary}\r\n";
        $post_data .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
        $post_data .= $value . "\r\n";
    }
    
    $post_data .= "--{$boundary}--\r\n";
    
    error_log('PDF: Taille des données POST: ' . strlen($post_data) . ' bytes');
    error_log('PDF: Début des données POST: ' . substr($post_data, 0, 300));
    
    // Faire la requête
    error_log('PDF: Envoi de la requête à Gotenberg...');
    $response = wp_remote_post($gotenberg_url, array(
        'body' => $post_data,
        'headers' => array(
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Content-Length' => strlen($post_data)
        ),
        'timeout' => 60
    ));
    
    if (is_wp_error($response)) {
        error_log('PDF: Erreur wp_remote_post: ' . $response->get_error_message());
        wp_send_json_error('Erreur lors de la connexion à Gotenberg: ' . $response->get_error_message());
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_headers = wp_remote_retrieve_headers($response);
    $response_body = wp_remote_retrieve_body($response);
    
    error_log('PDF: Code de réponse Gotenberg: ' . $response_code);
    error_log('PDF: Headers de réponse: ' . print_r($response_headers, true));
    error_log('PDF: Taille du body: ' . strlen($response_body) . ' bytes');
    
    if ($response_code !== 200) {
        error_log('PDF: Erreur Gotenberg, body: ' . substr($response_body, 0, 1000));
        wp_send_json_error('Erreur Gotenberg (Code: ' . $response_code . '): ' . substr($response_body, 0, 500));
    }
    
    $pdf_content = $response_body;
    
    // Vérifier que c'est bien un PDF
    $pdf_header = substr($pdf_content, 0, 4);
    if ($pdf_header !== '%PDF') {
        error_log('PDF: Le contenu reçu n\'est pas un PDF. Header: ' . bin2hex(substr($pdf_content, 0, 20)));
        error_log('PDF: Début du contenu: ' . substr($pdf_content, 0, 200));
        wp_send_json_error('Le contenu reçu n\'est pas un PDF valide');
    }
    
    // Sauvegarder le PDF dans le dossier uploads
    $upload_dir = wp_upload_dir();
    $pdf_filename = 'canvas-' . $pdf_data['project']['id'] . '-' . date('Y-m-d-H-i-s') . '.pdf';
    $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
    $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;
    
    error_log('PDF: Sauvegarde vers: ' . $pdf_path);
    
    if (file_put_contents($pdf_path, $pdf_content) === false) {
        error_log('PDF: Erreur lors de la sauvegarde');
        wp_send_json_error('Erreur lors de la sauvegarde du PDF.');
    }
    
    error_log('PDF: Sauvegarde réussie, taille fichier: ' . filesize($pdf_path) . ' bytes');
    error_log('PDF: Data: ' . print_r($pdf_data, true));
    error_log('=== FIN GÉNÉRATION PDF GOTENBERG ===');
    
    wp_send_json_success(array(
        'pdf_url' => $pdf_url,
        'pdf_path' => $pdf_path,
        'filename' => $pdf_filename,
        'message' => 'PDF généré avec succès!',
        'debug_html_url' => content_url('/uploads/debug-canvas.html')
    ));
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
        '{{user.company}}' => $data['user']['company'],
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
    }
    
    return null;
}