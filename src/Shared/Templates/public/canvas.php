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

// Récupérer les notes de l'admin pour ce projet
$project_ratings = WP_BMC_Database::get_project_ratings($project_id);

// Configuration des sections du canvas
$canvas_sections = array(
    'key_partners' => array(
        'title' => 'Partenaires clés',
        'placeholder' => 'Qui sont vos partenaires clés ?'
    ),
    'key_activities' => array(
        'title' => 'Activités clés',
        'placeholder' => 'Quelles sont vos activités clés ?'
    ),
    'key_resources' => array(
        'title' => 'Ressources clés',
        'placeholder' => 'Quelles sont vos ressources clés ?'
    ),
    'value_proposition' => array(
        'title' => 'Proposition de valeur',
        'placeholder' => 'Quelle est votre proposition de valeur ?'
    ),
    'customer_relationships' => array(
        'title' => 'Relations clients',
        'placeholder' => 'Quel type de relation établissez-vous avec vos clients ?'
    ),
    'channels' => array(
        'title' => 'Canaux',
        'placeholder' => 'Quels canaux utilisez-vous pour atteindre vos clients ?'
    ),
    'customer_segments' => array(
        'title' => 'Segments clients',
        'placeholder' => 'Quels sont vos segments clients ?'
    ),
    'cost_structure' => array(
        'title' => 'Structure des coûts',
        'placeholder' => 'Quels sont vos coûts principaux ?'
    ),
    'revenue_streams' => array(
        'title' => 'Sources de revenus',
        'placeholder' => 'Quelles sont vos sources de revenus ?'
    )
);

// Fonction pour afficher une section de canvas
function render_canvas_section($section_key, $section_config, $canvas_data, $project_id, $project_ratings) {
    $content = isset($canvas_data[$section_key]) ? wp_kses_post($canvas_data[$section_key]) : '';
    $section_class = $section_key;
    
    // Classes CSS spécifiques pour certaines sections
    if ($section_key === 'value_proposition') {
        $section_class .= ' value-proposition';
    }
    
    ob_start();
    ?>
    <div class="canvas-section <?php echo $section_class; ?>" data-section="<?php echo $section_key; ?>">
        <h3><?php echo esc_html($section_config['title']); ?></h3>
        <div class="canvas-content" data-placeholder="<?php echo esc_attr($section_config['placeholder']); ?>"><?php echo $content; ?></div>
        <button class="edit-brick-btn" data-section="<?php echo $section_key; ?>">
            <i class="fas fa-edit"></i>
        </button>
        
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
        <?php display_section_rating($project_ratings, $section_key); ?>
    </div>
    <?php
    return ob_get_clean();
}

// Fonction pour afficher les notes d'une section
function display_section_rating($project_ratings, $section_name) {
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

<div class="wp-bmc-canvas-container" data-project-id="<?php echo $project->id; ?>">
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
            'key_partners', 'key_activities', 'key_resources',
            'value_proposition', 'customer_relationships', 'channels',
            'customer_segments', 'cost_structure', 'revenue_streams'
        );
        
        foreach ($canvas_order as $section_key) {
            if (isset($canvas_sections[$section_key])) {
                echo render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project_id, $project_ratings);
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