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
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
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
    
    $rating = WP_BMC_Database::get_section_rating($project_id, $section, $admin_id);
    
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
    
    // Récupérer les données de l'utilisateur
    $user_projects = WP_BMC_Database::get_user_projects($current_user->user_id);
    $project = !empty($user_projects) ? $user_projects[0] : null;
    
    if (!$project) {
        wp_send_json_error('Aucun projet trouvé.');
    }
    
    $canvas_data = WP_BMC_Database::get_canvas_data($project->id);
    $project_ratings = WP_BMC_Database::get_project_ratings($project->id);
    
    // Configuration des sections du canvas (externalisée)
    $canvas_sections = WP_BMC_Canvas_Config::get_sections_config();
    
    // Fonction pour afficher une section de canvas (utilise les fonctions externalisées)
    function render_canvas_section_ajax($section_key, $section_config, $canvas_data, $project, $view_mode, $project_ratings) {
        // Utiliser la fonction externalisée
        return wp_bmc_render_canvas_section($section_key, $section_config, $canvas_data, $project->id, $project_ratings, false, array());
    }
    
    // Générer le HTML selon la vue
    ob_start();
    
    if ($view === 'synthetic') {
        // Vue synthétique - 3 briques principales
        echo '<div class="canvas-synthetic">';
        echo '<div class="synthetic-grid">';
        
        $synthetic_order = wp_bmc_get_synthetic_order();
        foreach ($synthetic_order as $section_key) {
            if (isset($canvas_sections[$section_key])) {
                echo render_canvas_section_ajax($section_key, $canvas_sections[$section_key], $canvas_data, $project, $view, $project_ratings);
            }
        }
        
        echo '</div>';
        echo '</div>';
        
    } else {
        // Vue globale - Toutes les briques
        echo '<div class="canvas-global">';
        echo '<div class="canvas-grid">';
        
        $global_order = wp_bmc_get_canvas_order();
        
        foreach ($global_order as $section_key) {
            if (isset($canvas_sections[$section_key])) {
                echo render_canvas_section_ajax($section_key, $canvas_sections[$section_key], $canvas_data, $project, $view, $project_ratings);
            }
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    $html = ob_get_clean();
    
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
        wp_mail($admin->user_email, $subject, $email_message);
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
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    // Récupérer toutes les données
    $data = array(
        'users' => WP_BMC_Database::get_all_users_with_projects(),
        'projects' => WP_BMC_Database::get_all_projects(),
        'grading_requests' => WP_BMC_Database::get_pending_grading_requests(),
        'notifications' => WP_BMC_Database::get_all_notifications(),
        'export_date' => current_time('mysql')
    );
    
    // Créer le fichier JSON
    $filename = 'bmc-data-export-' . date('Y-m-d-H-i-s') . '.json';
    $filepath = WP_CONTENT_DIR . '/uploads/' . $filename;
    
    file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success(array(
        'file_url' => content_url('/uploads/' . $filename)
    ));
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

// Handler pour obtenir le formulaire d'édition utilisateur (admin)
add_action('wp_ajax_wp_bmc_get_user_edit_form', 'wp_bmc_get_user_edit_form_handler');
function wp_bmc_get_user_edit_form_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $user_id = intval($_POST['user_id']);
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        wp_send_json_error('Utilisateur non trouvé.');
    }
    
    $html = '<form id="user-edit-form">';
    $html .= '<input type="hidden" name="user_id" value="' . $user_id . '">';
    $html .= '<div class="form-group">';
    $html .= '<label for="display_name">Nom d\'affichage :</label>';
    $html .= '<input type="text" id="display_name" name="display_name" value="' . esc_attr($user->display_name) . '" required>';
    $html .= '</div>';
    $html .= '<div class="form-group">';
    $html .= '<label for="user_email">Email :</label>';
    $html .= '<input type="email" id="user_email" name="user_email" value="' . esc_attr($user->user_email) . '" required>';
    $html .= '</div>';
    $html .= '<div class="form-group">';
    $html .= '<label for="company">Entreprise :</label>';
    $html .= '<input type="text" id="company" name="company" value="' . esc_attr(get_user_meta($user_id, 'company', true)) . '">';
    $html .= '</div>';
    $html .= '<div class="form-actions">';
    $html .= '<button type="submit" class="button button-primary">Sauvegarder</button>';
    $html .= '<button type="button" class="button popup-close">Annuler</button>';
    $html .= '</div>';
    $html .= '</form>';
    
    wp_send_json_success(array(
        'html' => $html
    ));
}

// Handler pour mettre à jour un utilisateur (admin)
add_action('wp_ajax_wp_bmc_update_user', 'wp_bmc_update_user_handler');
function wp_bmc_update_user_handler() {
    check_ajax_referer('wp_bmc_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès réservé aux administrateurs.');
    }
    
    $user_id = intval($_POST['user_id']);
    $display_name = sanitize_text_field($_POST['display_name']);
    $user_email = sanitize_email($_POST['user_email']);
    $company = sanitize_text_field($_POST['company']);
    
    if (!$user_id || !$display_name || !$user_email) {
        wp_send_json_error('Données manquantes.');
    }
    
    // Mettre à jour l'utilisateur
    $result = wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $display_name,
        'user_email' => $user_email
    ));
    
    if (is_wp_error($result)) {
        wp_send_json_error('Erreur lors de la mise à jour : ' . $result->get_error_message());
    }
    
    // Mettre à jour les métadonnées
    update_user_meta($user_id, 'company', $company);
    
    wp_send_json_success(array(
        'message' => 'Utilisateur mis à jour avec succès.'
    ));
}