<?php

/**
 * Template pour le Business Model Canvas - Vue Utilisateur
 * Interface complète du canvas pour les utilisateurs BMC
 */

if (!defined('ABSPATH')) {
    exit;
}

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$project = WP_BMC_Database::get_project($project_id);
$canvas_data = WP_BMC_Database::get_canvas_data($project_id);

$project_ratings = WP_BMC_Database::get_project_ratings($project_id);
$canvas_sections = WP_BMC_Canvas_Config::get_sections_config();

// Les fonctions render_canvas_section et display_section_rating sont maintenant externalisées
// dans src/Shared/Functions/canvas-functions.php

// Vérifier si le projet existe
if (!$project) {
    echo '<div class="wp-bmc-error">Projet non trouvé ou accès non autorisé.</div>';
    return;
}

// Vérifier que l'utilisateur peut accéder à ce projet
$current_user = WP_BMC_Auth::get_current_user();
if (!$current_user || $current_user->user_id != $project->user_id) {
    wp_redirect(home_url('/login/'));
    exit;
}
?>

<div class="wp-bmc-canvas-container">
    <div class="canvas-header">
        <h1><?php echo esc_html($project->title); ?></h1>
        <div class="canvas-actions">
            <button id="wp-bmc-save-canvas" class="wp-bmc-btn wp-bmc-btn-primary">
                Sauvegarder
            </button>
            <button id="wp-bmc-export-pdf" class="wp-bmc-btn wp-bmc-btn-secondary">
                Exporter PDF
            </button>
            <a href="/dashboard" class="wp-bmc-btn wp-bmc-btn-secondary">
                Retour au tableau de bord
            </a>
        </div>
    </div>

    <div class="canvas-grid">
        <?php
        // Afficher toutes les sections dans l'ordre du canvas
        $canvas_order = array(
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

        foreach ($canvas_order as $section_key) {
            if (isset($canvas_sections[$section_key])) {
                echo wp_bmc_render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project_id, $project_ratings);
            }
        }
        ?>
    </div>

    <div class="canvas-footer">
        <div class="auto-save-status">
            <span id="auto-save-status">Sauvegarde automatique activée</span>
        </div>
        <div class="last-saved">
            <span id="last-saved-time">Dernière sauvegarde : <?php echo date('d/m/Y H:i'); ?></span>
        </div>
    </div>
</div>

<?php
// Inclure le template d'édition réutilisable pour les utilisateurs
wp_bmc_include_edit_section('public');
?>

<div id="wp-bmc-canvas-message" class="wp-bmc-message" style="display: none;"></div>