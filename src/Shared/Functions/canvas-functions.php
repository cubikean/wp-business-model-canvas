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
    $content = isset($canvas_data[$section_key]) ? wp_kses_post($canvas_data[$section_key]) : '';
    $section_class = $section_key;

    // Classes CSS spécifiques pour certaines sections
    if ($section_key === 'value_proposition') {
        $section_class .= ' value-proposition';
    }

    ob_start();
?>
    <div
        class="canvas-section <?php echo $section_class; ?>"
        data-column="<?php echo $section_config['grid-column']; ?>"
        data-row="<?php echo $section_config['grid-row']; ?>"
        data-section="<?php echo $section_key; ?>"
        data-color="<?php echo $section_config['color']; ?>">
        <div class="canvas-section-header">
            <h3>
                <?php echo esc_html($section_config['title']); ?>
                <?php if ($is_admin && in_array($section_key, $pending_grading_requests)): ?>
                    <span class="grading-request-indicator" title="Cette brique nécessite une notation">
                        <i class="fas fa-star-half-alt"></i>
                    </span>
                <?php endif; ?>
            </h3>
        </div>
        <h4>
            <?php echo esc_html($section_config['placeholder']); ?>
        </h4>
        <div
            class="canvas-content wysiwyg-content"
            data-section="<?php echo $section_key; ?>"
            data-color="<?php echo $section_config['color']; ?>">
            <?php
            $allowed_tags = array(
                'span' => array(
                    'style' => true,
                    'class' => true,
                ),
                'p' => array(
                    'style' => true,
                    'class' => true,
                ),
                'ul' => array('class' => true, 'style' => true),
                'ol' => array('class' => true, 'style' => true),
                'li' => array('class' => true, 'style' => true),
                'strong' => array(),
                'em' => array(),
                'u' => array(),
                'br' => array(),
                'div' => array(
                    'class' => true,
                    'style' => true,
                ),
                'i' => array('class' => true, 'style' => true),
                'b' => array('class' => true, 'style' => true),
            );

            echo wp_kses(html_entity_decode($content), $allowed_tags);
            ?>
        </div>
        <div class="canvas-section-footer">

            <?php wp_bmc_display_section_rating($project_ratings, $section_key); ?>


            <div class="edit-brick-btn-container">
                <?php if ($is_admin): ?>
                    <button
                        class="rate-brick-btn"
                        data-project-id="<?php echo $project_id; ?>"
                        data-section-title="<?php echo esc_attr($section_config['title']); ?>"
                        data-section="<?php echo $section_key; ?>"
                        title="Noter cette brique"
                        data-color="<?php echo $section_config['color']; ?>">
                        <i class="fas fa-star"></i>
                    </button>
                <?php endif; ?>

                <button class="edit-brick-btn" data-section="<?php echo $section_key; ?>">
                    <i class="fa fa-pen"></i>
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
function wp_bmc_display_section_rating($project_ratings, $section_name)
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
    </div>
<?php }

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
function wp_bmc_render_canvas_view($view_mode, $project_id, $canvas_data, $project_ratings, $is_admin = false, $pending_grading_requests = array(), $include_progress_chart = false) {
    // Configuration des sections du canvas
    $canvas_sections = WP_BMC_Canvas_Config::get_sections_config($view_mode);
    
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
            echo '<h4>Plan d\'action</h4>';
            echo '<p class="action-plan-info"><i class="fas fa-info-circle"></i> Prochaines 5 tâches à réaliser - Cliquez pour marquer comme terminées</p>';
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
function wp_bmc_get_all_project_todos($project_id) {
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
function wp_bmc_get_section_display_name($section_key) {
    $sections = wp_bmc_get_canvas_sections();
    
    if (isset($sections[$section_key])) {
        return $sections[$section_key]['title'];
    }
    
    // Fallback : convertir la clé en nom lisible
    return ucwords(str_replace('_', ' ', $section_key));
}