<?php

/**
 * Template unifié pour le tableau de bord et canvas utilisateur
 * Gère à la fois la création de projet et l'affichage du canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = WP_BMC_Auth::get_current_user();

if (!$current_user) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Obtenir les données utilisateur formatées pour le menu
$user_menu_data = WP_BMC_Auth::get_user_menu_data();

// Gestion des paramètres d'URL
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'synthetic';

// Récupération des données du projet (v2.0)
if ($project_id) {
    // Mode canvas spécifique - vérifier les permissions
    $project = WP_BMC_Database::get_project($project_id);
    
    // Vérifier si l'utilisateur a accès à ce projet (v2.0)
    if (!$project || !WP_BMC_Database::user_has_project_access($current_user->user_id, $project_id)) {
        wp_redirect(home_url('/dashboard/'));
        exit;
    }
} else {
    // Mode dashboard - utiliser le premier projet de l'utilisateur
    $user_projects = WP_BMC_Database::get_user_projects($current_user->user_id);
    $project = !empty($user_projects) ? $user_projects[0] : null;
    $project_id = $project ? $project->id : null;
}

$canvas_data = $project_id ? WP_BMC_Database::get_canvas_data($project_id) : array();
$project_ratings = $project_id ? WP_BMC_Database::get_project_ratings($project_id) : array();
?>

<div class="wp-bmc-dashboard" <?php if ($project): ?>data-project-id="<?php echo $project_id; ?>" <?php endif; ?>>
    <?php if (!$project): ?>
        <!-- Aucun projet assigné (v2.0) -->
        <div class="no-project-section">
            <div class="welcome-message">
                <h2>Bienvenue, <?php echo esc_html($current_user->first_name); ?> !</h2>
            </div>

            <div class="no-projects-info">
                <div class="info-card">
                    <h3>Comment obtenir un projet ?</h3>
                    <p>Les projets sont créés et assignés par les administrateurs. Contactez votre responsable pour qu'il vous assigne un projet Business Model Canvas.</p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Canvas existant - Affichage avec options de vue -->
        <div class="dashboard-header" data-project-name="<?php echo $project ? esc_html($project->title) : ""; ?>">
            <h2 class="dashboard-header-title">Vue synthétique du projet : <?php echo $project ? esc_html($project->title) : ""; ?></h2>

          

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
            <?php echo wp_bmc_render_canvas_view($view_mode, $project_id, $canvas_data, $project_ratings, false, array(), true); ?>
        </div>
    <?php endif; ?>
</div>


<?php wp_bmc_include_edit_section('public'); ?>


<button id="wp-bmc-generate-pdf" class="wp-bmc-btn wp-bmc-btn-secondary btn-outline --icon" data-project-id="<?php echo $project_id; ?>">
    <i class="fas fa-file-pdf"></i> Exporter le canvas
</button>