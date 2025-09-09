<?php

/**
 * Template pour le tableau de bord utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = WP_BMC_Auth::get_current_user();

if (!$current_user) {
    wp_redirect(home_url('/login/'));
    exit;
}

$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'synthetic';

$project = !empty($user_projects) ? $user_projects[0] : null;
$canvas_data = $project ? WP_BMC_Database::get_canvas_data($project->id) : array();


$user_projects = WP_BMC_Database::get_user_projects($current_user->user_id);
$canvas_sections = WP_BMC_Canvas_Config::get_sections_config();
$project_ratings = WP_BMC_Database::get_project_ratings($project_id);
?>

<div class="wp-bmc-dashboard" <?php if ($project): ?>data-project-id="<?php echo $project->id; ?>" <?php endif; ?>>
    <div class="dashboard-header">
        <h1>Mon Business Model Canvas</h1>
        <div class="user-info">
            <span>Bienvenue, <?php echo esc_html($current_user->first_name . ' ' . $current_user->last_name); ?></span>
            <span class="company-info"><?php echo esc_html($current_user->company); ?></span>
            <a href="#" id="wp-bmc-logout" class="wp-bmc-btn wp-bmc-btn-secondary">Déconnexion</a>
        </div>
    </div>

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
        <div class="canvas-controls">
            <div class="view-toggle">
                <button class="wp-bmc-btn <?php echo $view_mode === 'synthetic' ? 'wp-bmc-btn-primary' : 'wp-bmc-btn-secondary'; ?>"
                    data-view="synthetic">Vue synthétique</button>
                <button class="wp-bmc-btn <?php echo $view_mode === 'global' ? 'wp-bmc-btn-primary' : 'wp-bmc-btn-secondary'; ?>"
                    data-view="global">Vue globale</button>
            </div>

            <!-- <div class="canvas-actions">
                <button id="wp-bmc-save-canvas" class="wp-bmc-btn">
                    Sauvegarder
                </button>
                <button id="wp-bmc-export-pdf" class="wp-bmc-btn">
                    Exporter PDF
                </button>
            </div> -->
        </div>

        <div class="canvas-container">
            <?php if ($view_mode === 'synthetic'): ?>
                <!-- Vue synthétique - 3 briques principales -->
                <div class="canvas-synthetic">
                    <div class="synthetic-grid">
                        <?php
                        // Afficher les sections synthétiques dans l'ordre spécifique
                        $synthetic_order = array('customer_segments', 'value_proposition', 'revenue_streams');
                        foreach ($synthetic_order as $section_key) {
                            if (isset($canvas_sections[$section_key])) {
                                echo wp_bmc_render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project->id, $project_ratings);
                            }
                        }
                        ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- Vue globale - Toutes les briques -->
                <div class="canvas-global">
                    <div class="canvas-grid">
                        <?php
                        // Afficher toutes les sections dans l'ordre du canvas
                        $global_order = array(
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

                        foreach ($global_order as $section_key) {
                            if (isset($canvas_sections[$section_key])) {
                                echo wp_bmc_render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project->id, $project_ratings);
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="canvas-footer">
            <div class="auto-save-status">
                <span id="auto-save-status">Sauvegarde automatique activée</span>
            </div>
            <div class="last-saved">
                <span id="last-saved-time">Dernière sauvegarde : <?php echo date('d/m/Y H:i'); ?></span>
            </div>
        </div>

    <?php endif; ?>
</div>

<div id="wp-bmc-dashboard-message" class="wp-bmc-message" style="display: none;"></div>

<?php wp_bmc_include_edit_section('public'); ?>