<?php

/**
 * Fonctions utilitaires partagées pour les templates du Business Model Canvas
 * 
 * Ce fichier contient les fonctions communes utilisées dans tous les templates
 * pour éviter la duplication de code
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction pour afficher une section de canvas
 * 
 * @param string $section_key Clé de la section
 * @param array $section_config Configuration de la section
 * @param array $canvas_data Données du canvas
 * @param int $project_id ID du projet
 * @param array $project_ratings Notes du projet
 * @param bool $is_admin Si l'utilisateur est admin (optionnel)
 * @param array $pending_grading_requests Demandes de notation en attente (optionnel)
 * @return string HTML de la section
 */
function wp_bmc_render_canvas_section($section_key, $section_config, $canvas_data, $project_id, $project_ratings, $is_admin = false, $pending_grading_requests = array())
{
    // Utiliser le même filtrage strict que pour la sauvegarde
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
    $content = isset($canvas_data[$section_key]) ? wp_kses($canvas_data[$section_key], $allowed_html) : '';
    $section_class = $section_key;

    // Classes CSS spécifiques pour certaines sections
    if ($section_key === 'value_proposition') {
        $section_class .= ' value-proposition';
    }
    if ($is_admin && in_array($section_key, $pending_grading_requests)) {
        $section_class .= ' need-grading';
    }

    ob_start();
?>
    <div
        class="canvas-section <?php echo $section_class; ?>"
        data-title="<?php echo $section_config['title']; ?>"
        data-placeholder="<?php echo $section_config['placeholder']; ?>"
        data-column="<?php echo $section_config['grid-column']; ?>"
        data-row="<?php echo $section_config['grid-row']; ?>"
        data-section="<?php echo $section_key; ?>"
        data-rating="<?php echo get_rating_number($project_ratings, $section_key); ?>"
        data-color="<?php echo $section_config['color']; ?>">
        <div class="canvas-section-header">
            <h3>
                <?php echo esc_html($section_config['title']); ?>
                <span class="help-icon" data-tippy-content="<?php echo esc_attr($section_config['placeholder']); ?>">
                    <i class="fas fa-question-circle"></i>
                </span>
            </h3>
        </div>
        <!-- <h4>
            <?php echo $section_config['placeholder']; ?>
        </h4> -->
        <div
            class="canvas-content wysiwyg-content"
            data-section="<?php echo $section_key; ?>"
            data-color="<?php echo $section_config['color']; ?>">
            <?php
            // Utiliser le même filtrage strict que pour la sauvegarde
            $allowed_tags = array(
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

            echo wp_kses(html_entity_decode($content), $allowed_tags);
            ?>
        </div>
        <div class="canvas-section-footer">

            <?php wp_bmc_display_section_rating($project_ratings, $section_key, false); ?>


            <div class="edit-brick-btn-container">
                <?php if ($is_admin && !in_array($section_key, $pending_grading_requests)): ?>
                    <button
                        disabled
                        class="rate-brick-btn disabled"
                        data-project-id="<?php echo $project_id; ?>"
                        data-section-title="<?php echo esc_attr($section_config['title']); ?>"
                        data-section="<?php echo $section_key; ?>"
                        title="Noter cette brique"
                        data-color="<?php echo $section_config['color']; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 24 24"><path fill="currentColor" d="m14.43 10l-1.47-4.84c-.29-.95-1.63-.95-1.91 0L9.57 10H5.12c-.97 0-1.37 1.25-.58 1.81l3.64 2.6l-1.43 4.61c-.29.93.79 1.68 1.56 1.09l3.69-2.8l3.69 2.81c.77.59 1.85-.16 1.56-1.09l-1.43-4.61l3.64-2.6c.79-.57.39-1.81-.58-1.81h-4.45z"/></svg>
                    </button>
                <?php elseif ($is_admin && in_array($section_key, $pending_grading_requests)): ?>
                    <button
                        class="rate-brick-btn"
                        data-project-id="<?php echo $project_id; ?>"
                        data-section="<?php echo $section_key; ?>"
                        data-section-title="<?php echo esc_attr($section_config['title']); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 24 24"><path fill="currentColor" d="m14.43 10l-1.47-4.84c-.29-.95-1.63-.95-1.91 0L9.57 10H5.12c-.97 0-1.37 1.25-.58 1.81l3.64 2.6l-1.43 4.61c-.29.93.79 1.68 1.56 1.09l3.69-2.8l3.69 2.81c.77.59 1.85-.16 1.56-1.09l-1.43-4.61l3.64-2.6c.79-.57.39-1.81-.58-1.81h-4.45z"/></svg>
                    </button>
                <?php endif; ?>

                <button class="edit-brick-btn" data-section="<?php echo $section_key; ?>">

                    <svg width="27" height="27" viewBox="0 0 27 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M26.9999 5.55066C26.8807 6.08208 26.6788 6.594 26.3163 7.00597L7.35864 26.0493C6.76021 26.5539 6.24205 26.7246 5.4636 26.7928C4.3008 26.8928 2.94338 26.9342 1.77084 26.9488C1.68083 26.9488 1.5373 26.9147 1.48135 27.0025H0.690741C0.0825782 26.7246 -0.0147278 26.393 -0.000131897 25.7568C0.00959871 25.3424 0.0874436 24.8548 0.106905 24.438C0.170154 23.1241 0.0193294 20.8473 0.824537 19.8185L20.0157 0.636253C21.0545 -0.195004 22.4605 -0.229132 23.4944 0.633815C24.2461 1.26031 25.8127 2.81312 26.4185 3.56881C26.5352 3.71263 26.6325 3.89059 26.7298 4.04904L26.9999 4.86079V5.55066ZM21.6091 1.75516C21.4583 1.7771 21.2685 1.88679 21.1469 1.97943C20.4366 2.52304 19.7773 3.48593 19.0646 4.0661L18.9916 4.19286L22.8035 7.97374C23.4068 7.28631 24.2802 6.64519 24.8397 5.94313C25.1632 5.53604 25.3165 5.11188 24.9735 4.64871C24.2923 3.72482 22.9738 2.83019 22.2319 1.90142C22.0592 1.78197 21.8159 1.72591 21.6115 1.75516H21.6091ZM17.8628 5.56529L17.7047 5.50922L17.4298 5.70668L2.27196 20.8984L2.06519 21.325L1.84868 25.1522C2.66362 25.0815 3.48586 25.0937 4.3008 25.045C4.40297 25.0401 4.50271 24.9987 4.61461 24.9913C5.08168 24.9621 5.63632 25.0767 6.04014 24.7817L21.4948 9.2511L17.8604 5.56529H17.8628Z" fill="white" />
                        <path d="M26.2628 27H10.6549C9.80831 26.6636 9.74262 25.5861 10.6111 25.24L26.1776 25.2083C27.1215 25.4374 27.1434 26.6465 26.2603 27H26.2628Z" fill="white" />
                    </svg>

                </button>
            </div>



        </div>
    </div>
<?php
    return ob_get_clean();
}

/**
 * Fonction pour afficher les notes d'une section
 * 
 * @param array $project_ratings Notes du projet
 * @param string $section_name Nom de la section
 * @return void
 */
function wp_bmc_display_section_rating($project_ratings, $section_name, $comment = false)
{
    $section_rating = null;
    foreach ($project_ratings as $rating) {
        if ($rating->section === $section_name) {
            $section_rating = $rating;
            break;
        }
    } ?>
    <div class="admin-rating-display" id="rating-display-<?php echo $section_name; ?>">
        <div class="rating-score">
            <?php if ($section_rating && isset($section_rating->rating)): ?>
                <span class="rating-score-number"><?php echo esc_html($section_rating->rating); ?></span>
            <?php else: ?>
                <span class="rating-score-number">-</span>
            <?php endif; ?>
            <span class="rating-score-total">10</span>
        </div>
        <?php if ($comment && $section_rating && isset($section_rating->comment) && !empty($section_rating->comment)): ?>
            <div class="rating-comment">
                <?php echo esc_html($section_rating->comment); ?>
            </div>
        <?php endif; ?>
    </div>
<?php }

function get_rating_number($project_ratings, $section_name)
{
    $section_rating = null;
    foreach ($project_ratings as $rating) {
        if ($rating->section === $section_name) {
            $section_rating = $rating;
            break;
        }
    }
    return $section_rating ? $section_rating->rating : null;
}

/**
 * Fonction pour obtenir l'ordre des sections du canvas
 * 
 * @return array Ordre des sections
 */
function wp_bmc_get_canvas_order()
{
    return array(
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
}

/**
 * Fonction pour obtenir l'ordre des sections synthétiques
 * 
 * @return array Ordre des sections synthétiques
 */
function wp_bmc_get_synthetic_order()
{
    return array('customer_segments', 'value_proposition', 'revenue_streams');
}

/**
 * Fonction centralisée pour rendre une vue canvas (synthétique ou globale)
 * 
 * @param string $view_mode Mode de vue ('synthetic' ou 'global')
 * @param int $project_id ID du projet
 * @param array $canvas_data Données du canvas
 * @param array $project_ratings Notes du projet
 * @param bool $is_admin Si c'est un mode admin
 * @param array $pending_grading_requests Demandes de notation en attente
 * @param bool $include_progress_chart Si inclure le graphique de progression (pour vue synthétique)
 * @return string HTML généré
 */
function wp_bmc_render_canvas_view($view_mode, $project_id, $canvas_data, $project_ratings, $is_admin = false, $pending_grading_requests = array(), $include_progress_chart = false)
{
    // Inclure la fonction des sections si elle n'est pas disponible
    if (!function_exists('wp_bmc_get_canvas_sections')) {
        include_once WP_BMC_PLUGIN_DIR . 'src/Shared/Config/canvas-sections.php';
    }
    
    // Configuration des sections du canvas (utiliser les configurations personnalisées)
    $canvas_sections = wp_bmc_get_canvas_sections($view_mode, true);

    // Calculer le pourcentage d'avancement si nécessaire
    $progress_percentage = 0;
    if ($include_progress_chart && !empty($project_ratings)) {
        $total_rating = 0;
        foreach ($project_ratings as $rating) {
            $total_rating += intval($rating->rating);
        }
        // Maximum possible : 9 sections × 10 points = 90 points
        $progress_percentage = round(($total_rating / 90) * 100, 0);
    }

    ob_start();

    if ($view_mode === 'synthetic') {
        // Vue synthétique - 3 briques principales
        echo '<div class="canvas-synthetic">';
        echo '<div class="synthetic-grid">';

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

        // Ajouter le graphique de progression si demandé
        if ($include_progress_chart) {
            echo '<div class="canvas-section overview-status" data-column="5/7" data-row="1/3">';
            echo '<h3>Status d\'avancement du projet</h3>';
            echo '<div class="chart">';
            echo '<svg class="progress-svg" viewBox="0 0 200 100" width="200" height="100">';
            echo '<!-- Cercle de fond -->';
            echo '<circle cx="100" cy="100" r="80" fill="none" stroke="#FFC1D3" stroke-width="40" stroke-linecap="round" transform="rotate(-90 100 100)" />';
            echo '<!-- Cercle de progression -->';
            echo '<circle cx="100" cy="100" r="80" fill="none" stroke="#FF4081" stroke-width="40" stroke-dasharray="251.33" stroke-dashoffset="' . (-251 + (1 - $progress_percentage / 100) * 251.33) . '" class="progress-circle" />';
            echo '<!-- Texte du pourcentage -->';
            echo '<text x="100" y="85" text-anchor="middle" class="progress-text">' . $progress_percentage . '%</text>';
            echo '</svg>';
            echo '</div>';
            echo '<div class="action-plan-info">';
            echo '<h4>Plan d\'action</h4>';
            echo '<ul class="action-plan">';

            // Récupérer toutes les tâches todo de toutes les briques
            $all_todos = wp_bmc_get_all_project_todos($project_id);

            if (!empty($all_todos)) {
                foreach ($all_todos as $todo) {
                    $section_name = wp_bmc_get_section_display_name($todo->section);
                    $completed_class = $todo->is_completed ? 'completed' : '';
                    $checked_attr = $todo->is_completed ? 'checked' : '';

                    echo '<li class="todo-item ' . $completed_class . '" data-todo-id="' . $todo->id . '">';
                    echo '<div class="todo-content">';
                    echo '<input type="checkbox" class="todo-checkbox" ' . $checked_attr . ' data-todo-id="' . $todo->id . '">';
                    echo '<span class="todo-text">' . esc_html($todo->task_text) . '</span>';
                    echo '</div>';
                    echo '<div class="todo-section-badge">' . esc_html($section_name) . '</div>';
                    echo '</li>';
                }
            } else {
                echo '<li class="no-todos">';
                echo '<i class="fas fa-check-circle"></i>';
                echo '<p>Aucune tâche en cours</p>';
                echo '<small>Toutes les tâches sont terminées ou aucune tâche n\'a été définie</small>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
            echo '</div>';
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

        echo '</div>';
        echo '</div>';
    }

    return ob_get_clean();
}

/**
 * Récupérer toutes les tâches todo d'un projet dans l'ordre des briques
 */
function wp_bmc_get_all_project_todos($project_id)
{
    global $wpdb;

    $table = $wpdb->prefix . 'bmc_todos';

    // S'assurer que la table existe
    WP_BMC_Database::ensure_todos_table_exists();

    // Ordre des sections du canvas
    $canvas_order = wp_bmc_get_canvas_order();

    // Construire la requête avec ORDER BY personnalisé
    $order_clause = "CASE ";
    foreach ($canvas_order as $index => $section) {
        $order_clause .= "WHEN section = '" . esc_sql($section) . "' THEN " . $index . " ";
    }
    $order_clause .= "ELSE 5 END, created_at ASC";

    $todos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table 
             WHERE project_id = %d AND is_completed = 0
             ORDER BY $order_clause
             LIMIT 5",
            $project_id
        )
    );

    return $todos ? $todos : array();
}

/**
 * Obtenir le nom d'affichage d'une section
 */
function wp_bmc_get_section_display_name($section_key)
{
    // Inclure la fonction des sections si elle n'est pas disponible
    if (!function_exists('wp_bmc_get_canvas_sections')) {
        include_once WP_BMC_PLUGIN_DIR . 'src/Shared/Config/canvas-sections.php';
    }
    
    $sections = wp_bmc_get_canvas_sections('global', true);

    if (isset($sections[$section_key])) {
        return $sections[$section_key]['title'];
    }

    // Fallback : convertir la clé en nom lisible
    return ucwords(str_replace('_', ' ', $section_key));
}
