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
    <div class="canvas-section <?php echo $section_class; ?>" data-section="<?php echo $section_key; ?>" data-color="<?php echo $section_config['color']; ?>">
        <h3>
            <?php echo esc_html($section_config['title']); ?>
            <?php if ($is_admin && in_array($section_key, $pending_grading_requests)): ?>
                <span class="grading-request-indicator" title="Cette brique nécessite une notation">
                    <i class="fas fa-star-half-alt"></i>
                </span>
            <?php endif; ?>
        </h3>
        <div class="canvas-content wysiwyg-content" data-placeholder="<?php echo esc_attr($section_config['placeholder']); ?>" data-section="<?php echo $section_key; ?>">
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
        <button class="edit-brick-btn" data-section="<?php echo $section_key; ?>">
            <i class="fas fa-edit"></i>
        </button>

        <?php if ($is_admin): ?>
            <button class="rate-brick-btn" data-project-id="<?php echo $project_id; ?>" data-section-title="<?php echo esc_attr($section_config['title']); ?>" data-section="<?php echo $section_key; ?>" title="Noter cette brique">
                <i class="fas fa-star"></i>
            </button>
        <?php endif; ?>

        <!-- Fichiers attachés -->
        <div class="canvas-files">
            <h4>Fichiers attachés</h4>
            <?php
            $section_files = WP_BMC_Database::get_section_files($project_id, $section_key);
            if (!empty($section_files)): ?>
                <div class="files-list">
                    <?php foreach ($section_files as $file): ?>
                        <div class="file-item">
                            <i class="fas fa-file"></i>
                            <span class="file-name"><?php echo esc_html($file->original_name); ?></span>
                            <a href="<?php echo esc_url($file->url); ?>" target="_blank" class="file-view-btn">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-files">Aucun fichier attaché</p>
            <?php endif; ?>
        </div>

        <!-- Affichage des notes de l'admin -->
        <?php wp_bmc_display_section_rating($project_ratings, $section_key); ?>
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
    }

    if ($section_rating): ?>
        <div class="admin-rating-display" id="rating-display-<?php echo $section_name; ?>">
            <div class="rating-info">
                <div class="rating-score">
                    <i class="fas fa-star"></i>
                    Note admin : <?php echo esc_html($section_rating->rating); ?>/10
                </div>
                <?php if ($section_rating->comment): ?>
                    <div class="rating-comment">
                        <strong>Commentaire :</strong> <?php echo esc_html($section_rating->comment); ?>
                    </div>
                <?php endif; ?>
                <div class="rating-date">
                    Noté le : <?php echo date('d/m/Y', strtotime($section_rating->created_at)); ?>
                </div>
            </div>
        </div>
    <?php endif;
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
