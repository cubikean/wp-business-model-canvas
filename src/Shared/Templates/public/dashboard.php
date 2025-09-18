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

// Gestion des paramètres d'URL
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'synthetic';

// Récupération des données du projet
if ($project_id) {
    // Mode canvas spécifique - vérifier les permissions
    $project = WP_BMC_Database::get_project($project_id);
    if (!$project || $current_user->user_id != $project->user_id) {
        wp_redirect(home_url('/login/'));
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
        <!-- Aucun projet créé - Créer le premier canvas -->
        <div class="no-project-section">
            <div class="welcome-message">
                <h2>Bienvenue dans votre espace Business Model Canvas !</h2>
                <p>Vous n'avez pas encore créé votre canvas. Commencez dès maintenant à structurer votre modèle économique.</p>
            </div>

            <div class="create-first-canvas">
                <h3>Créer mon premier Business Model Canvas</h3>
                <form id="wp-bmc-create-first-canvas-form">
                    <?php wp_nonce_field('wp_bmc_project_nonce', 'wp_bmc_project_nonce'); ?>

                    <div class="form-group">
                        <label for="project_title">Nom de votre projet/entreprise</label>
                        <input type="text" id="project_title" name="project_title" required
                            placeholder="Ex: Mon Startup, Mon Entreprise, Mon Projet">
                    </div>

                    <div class="form-group">
                        <label for="project_description">Description (optionnel)</label>
                        <textarea id="project_description" name="project_description" rows="3"
                            placeholder="Décrivez brièvement votre projet..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="wp-bmc-btn wp-bmc-btn-primary">
                            Créer mon canvas
                        </button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Canvas existant - Affichage avec options de vue -->
        <div class="dashboard-header" data-project-name="<?php echo $project ? esc_html($project->title) : ""; ?>">
            <h2 class="dashboard-header-title">Vue synthétique du projet : <?php echo $project ? esc_html($project->title) : ""; ?></h2>


            <!-- // TODO: Ajouter le bouton de déconnexion -->
            <!-- <div class="user-info">
                <a href="#" id="wp-bmc-logout" class="wp-bmc-btn wp-bmc-btn-secondary">Déconnexion</a>
            </div> -->
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

<button id="wp-bmc-export-canvas" class="wp-bmc-btn wp-bmc-btn-secondary btn-outline --icon">
    <i class="fas fa-download"></i>
    Exporter le canvas
</button>

<?php wp_bmc_include_edit_section('public'); ?>