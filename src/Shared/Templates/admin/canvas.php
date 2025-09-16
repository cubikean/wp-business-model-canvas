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
$canvas_sections = WP_BMC_Canvas_Config::get_sections_config($view_mode);


// Calculer le pourcentage d'avancement du projet
$progress_percentage = 0;
if ($project_id && !empty($project_ratings)) {
    $total_rating = 0;
    foreach ($project_ratings as $rating) {
        $total_rating += intval($rating->rating);
    }
    // Maximum possible : 9 sections × 10 points = 90 points
    $progress_percentage = round(($total_rating / 90) * 100, 0);
}

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

<div class="wp-bmc-canvas-container" data-project-id="<?php echo $project_id; ?>">
    <div class="canvas-header">
        <h1><?php echo esc_html($project->title); ?></h1>

        <?php if ($admin_view && $user_id > 0): ?>
            <?php
            $user_info = WP_BMC_Database::get_user($user_id);
            if ($user_info): ?>
                <div class="admin-user-info">
                    <span class="user-label">BMC de :</span>
                    <strong><?php echo esc_html($user_info->first_name . ' ' . $user_info->last_name); ?></strong>
                    <span class="user-company">(<?php echo esc_html($user_info->company); ?>)</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="canvas-actions">
            <!-- <button id="wp-bmc-save-canvas" class="wp-bmc-btn wp-bmc-btn-primary">
                Sauvegarder
            </button>
            <button id="wp-bmc-export-pdf" class="wp-bmc-btn wp-bmc-btn-secondary">
                Exporter PDF
            </button> -->
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
        <?php if ($view_mode === 'synthetic'): ?>
            <!-- Vue synthétique - 3 briques principales -->
            <div class="canvas-synthetic">
                <div class="synthetic-grid">
                    <?php
                    // Afficher les sections synthétiques dans l'ordre spécifique
                    $synthetic_order = wp_bmc_get_synthetic_order();
                    foreach ($synthetic_order as $section_key) {
                        if (isset($canvas_sections[$section_key])) {
                            echo wp_bmc_render_canvas_section(
                                $section_key,
                                $canvas_sections[$section_key],
                                $canvas_data,
                                $project_id,
                                $project_ratings,
                                $is_admin,
                                $pending_grading_requests
                            );
                        }
                    }
                    ?>

                    <div class="canvas-section overview-status" data-column="5/7" data-row="1/3">
                        <h3>Status d'avancement du projet</h3>
                        <div class="chart">
                            <svg class="progress-svg" viewBox="0 0 200 100" width="200" height="100">
                                <!-- Cercle de fond -->
                                <circle cx="100" cy="100" r="80"
                                    fill="none"
                                    stroke="#FFC1D3"
                                    stroke-width="40"
                                    stroke-linecap="round"
                                    transform="rotate(-90 100 100)" />

                                <!-- Cercle de progression -->
                                <circle cx="100" cy="100" r="80"
                                    fill="none"
                                    stroke="#FF4081"
                                    stroke-width="40"
                                    stroke-dasharray="251.33"
                                    stroke-dashoffset="<?php echo -251 + (1 - $progress_percentage / 100) * 251.33; ?>"
                                    class="progress-circle" />

                                <!-- Texte du pourcentage -->
                                <text x="100" y="85" text-anchor="middle" class="progress-text">
                                    <?php echo $progress_percentage; ?>%
                                </text>
                            </svg>
                        </div>
                        <h4>Plan d'action</h4>
                        <ul class="action-plan">
                            <!-- TO DO LIST -->
                        </ul>
                    </div>

                </div>

            </div>

        <?php else: ?>
            <!-- Vue globale - Toutes les briques -->
            <div class="canvas-global">
                <div class="canvas-grid">
                    <?php
                    // Afficher toutes les sections dans l'ordre du canvas
                    $global_order = wp_bmc_get_canvas_order();
                    foreach ($global_order as $section_key) {
                        if (isset($canvas_sections[$section_key])) {
                            echo wp_bmc_render_canvas_section(
                                $section_key,
                                $canvas_sections[$section_key],
                                $canvas_data,
                                $project_id,
                                $project_ratings,
                                $is_admin,
                                $pending_grading_requests
                            );
                        }
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- <div class="canvas-footer">
        <div class="auto-save-status">
            <span id="auto-save-status">Sauvegarde automatique activée</span>
        </div>
        <div class="last-saved">
            <span id="last-saved-time">Dernière sauvegarde : <?php echo date('d/m/Y H:i'); ?></span>
        </div>
    </div>
</div> -->

    <?php
    // Inclure le template d'édition réutilisable pour l'admin
    wp_bmc_include_edit_section('admin');
    ?>

    <!-- Indicateur admin -->
    <div class="admin-indicator" style="position: fixed; top: 20px; right: 20px; background: #0073aa; color: white; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <i class="fas fa-user-shield"></i> Mode Administrateur
    </div>

    <div id="wp-bmc-canvas-message" class="wp-bmc-message" style="display: none;"></div>