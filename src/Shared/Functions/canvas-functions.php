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
            <span class="rating-score-number"><?php echo esc_html($section_rating->rating); ?></span>
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
