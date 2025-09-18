<?php

/**
 * Template pour le Business Model Canvas - Vue Administrateur
 * Permet aux admins de voir et éditer le canvas de n'importe quel utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$admin_view = isset($_GET['admin_view']) && $_GET['admin_view'] === 'true';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'synthetic';

// Si c'est une vue admin avec un user_id spécifique, récupérer le projet de cet utilisateur
if ($admin_view && $user_id > 0) {

    $user_projects = WP_BMC_Database::get_user_projects($user_id);
    if (!empty($user_projects)) {
        $project = $user_projects[0]; // L'utilisateur ne peut avoir qu'un seul projet
        $project_id = $project->id;
    }
} else {
    $project = WP_BMC_Database::get_project($project_id);
}

$is_admin = current_user_can('manage_options');


$canvas_data = WP_BMC_Database::get_canvas_data($project_id);
$project_ratings = WP_BMC_Database::get_project_ratings($project_id);

// Récupérer les demandes de notation en attente pour ce projet
$pending_grading_requests = array();
if ($is_admin) {
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


if (!$project) {
    echo '<div class="wp-bmc-error">Projet non trouvé ou accès non autorisé.</div>';
    return;
}

// Si c'est une vue admin, permettre l'accès
if ($admin_view && $is_admin) {
    // L'admin peut voir le canvas de n'importe quel utilisateur
    $is_admin = true;
} elseif (!$is_admin) {
    // Si l'utilisateur n'est pas admin, vérifier s'il essaie d'accéder à son propre projet
    $current_user = WP_BMC_Auth::get_current_user();
    if (!$current_user || $current_user->user_id != $project->user_id) {
        wp_redirect(home_url('/login/'));
        exit;
    }
} else {
    wp_redirect(home_url('/login/'));
    exit;
}
?>

<div class="wp-bmc-canvas-container wp-bmc-dashboard" data-project-id="<?php echo $project_id; ?>">
    <div class="dashboard-header" data-project-name="<?php echo $project ? esc_html($project->title) : ""; ?>">
        <h2 class="dashboard-header-title">Vue synthétique du projet : <?php echo $project ? esc_html($project->title) : ""; ?></h2>

        <div class="canvas-actions">
            <a href="/dashboard" class="wp-bmc-btn wp-bmc-btn-secondary">
                Retour au tableau de bord
            </a>
        </div>

        <div class="canvas-controls">
            <div class="view-toggle">
                <button class="wp-bmc-btn <?php echo $view_mode === 'synthetic' ? 'wp-bmc-btn-primary' : 'wp-bmc-btn-secondary'; ?>"
                    data-view="synthetic">Vue synthétique</button>
                <button class="wp-bmc-btn <?php echo $view_mode === 'global' ? 'wp-bmc-btn-primary' : 'wp-bmc-btn-secondary'; ?>"
                    data-view="global">Vue globale</button>
            </div>
        </div>
    </div>

    <div class="canvas-container">
        <?php echo wp_bmc_render_canvas_view($view_mode, $project_id, $canvas_data, $project_ratings, $is_admin, $pending_grading_requests, true); ?>
    </div>

    <?php
    // Inclure le template d'édition réutilisable pour l'admin
    wp_bmc_include_edit_section('admin');
    ?>

    <!-- Indicateur admin -->
    <div class="admin-indicator" style="position: fixed; top: 20px; right: 20px; background: #0073aa; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <i class="fas fa-user-shield"></i> Mode Administrateur
    </div>

    <button id="wp-bmc-export-canvas" class="wp-bmc-btn wp-bmc-btn-secondary btn-outline --icon">
        <i class="fas fa-download"></i>
        Exporter le canvas
    </button>