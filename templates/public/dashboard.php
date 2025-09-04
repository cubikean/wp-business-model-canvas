<?php
/**
 * Template pour le tableau de bord utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = WP_BMC_Auth::get_current_user();

// Vérifier que l'utilisateur est connecté et trouvé
if (!$current_user) {
    wp_redirect(home_url('/login/'));
    exit;
}

$user_projects = WP_BMC_Database::get_user_projects($current_user->user_id);

// L'utilisateur ne peut avoir qu'un seul projet
$project = !empty($user_projects) ? $user_projects[0] : null;
$canvas_data = $project ? WP_BMC_Database::get_canvas_data($project->id) : array();

// Vue par défaut (synthétique ou globale)
$view_mode = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'synthetic';

// Configuration des sections du canvas
$canvas_sections = array(
    'key_partners' => array(
        'title' => 'Partenaires clés',
        'placeholder' => 'Qui sont vos partenaires clés ?',
        'synthetic' => false
    ),
    'key_activities' => array(
        'title' => 'Activités clés',
        'placeholder' => 'Quelles sont vos activités clés ?',
        'synthetic' => false
    ),
    'key_resources' => array(
        'title' => 'Ressources clés',
        'placeholder' => 'Quelles sont vos ressources clés ?',
        'synthetic' => false
    ),
    'value_proposition' => array(
        'title' => 'Proposition de valeur',
        'placeholder' => 'Quelle est votre proposition de valeur ?',
        'synthetic' => true
    ),
    'customer_relationships' => array(
        'title' => 'Relations clients',
        'placeholder' => 'Quel type de relation établissez-vous avec vos clients ?',
        'synthetic' => false
    ),
    'channels' => array(
        'title' => 'Canaux',
        'placeholder' => 'Quels canaux utilisez-vous pour atteindre vos clients ?',
        'synthetic' => false
    ),
    'customer_segments' => array(
        'title' => 'Segments clients',
        'placeholder' => 'Quels sont vos segments clients ?',
        'synthetic' => true
    ),
    'cost_structure' => array(
        'title' => 'Structure des coûts',
        'placeholder' => 'Quels sont vos coûts principaux ?',
        'synthetic' => false
    ),
    'revenue_streams' => array(
        'title' => 'Sources de revenus',
        'placeholder' => 'Quelles sont vos sources de revenus ?',
        'synthetic' => true
    )
);

// Fonction pour afficher une section de canvas
function render_canvas_section($section_key, $section_config, $canvas_data, $project, $view_mode) {
    $content = isset($canvas_data[$section_key]) ? esc_textarea($canvas_data[$section_key]) : '';
    $section_class = $section_key;
    
    // Classes CSS spécifiques pour certaines sections
    if ($section_key === 'value_proposition') {
        $section_class .= ' value-proposition';
    }
    
    // Déterminer si la section doit être affichée dans la vue synthétique
    $show_in_synthetic = $section_config['synthetic'];
    $show_in_global = true;
    
    // Filtrer les sections selon la vue
    if ($view_mode === 'synthetic' && !$show_in_synthetic) {
        return '';
    }
    
    // Récupérer les notes de l'admin
    $ratings = WP_BMC_Database::get_project_ratings($project->id);
    $section_rating = null;
    foreach ($ratings as $rating) {
        if ($rating->section === $section_key) {
            $section_rating = $rating;
            break;
        }
    }
    
    ob_start();
    ?>
    <div class="<?php echo $view_mode === 'synthetic' ? 'synthetic-section' : 'canvas-section'; ?> <?php echo $section_class; ?>" data-section="<?php echo $section_key; ?>">
        <button class="edit-brick-btn" data-section="<?php echo $section_key; ?>" title="Éditer cette brique">
            <i class="fas fa-edit"></i>
        </button>
        <h3><?php echo esc_html($section_config['title']); ?></h3>
        <textarea class="canvas-textarea" disabled placeholder="<?php echo esc_attr($section_config['placeholder']); ?>"><?php echo $content; ?></textarea>
        
        <?php if ($section_rating): ?>
            <!-- Affichage des notes de l'admin -->
            <div class="admin-rating-display" id="rating-display-<?php echo $section_key; ?>">
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
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<div class="wp-bmc-dashboard">
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
            
            <div class="canvas-actions">
                <button id="wp-bmc-save-canvas" class="wp-bmc-btn wp-bmc-btn-primary">
                    Sauvegarder
                </button>
                <button id="wp-bmc-export-pdf" class="wp-bmc-btn wp-bmc-btn-secondary">
                    Exporter PDF
                </button>
            </div>
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
                                echo render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project, $view_mode);
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
                            'key_partners', 'key_activities', 'key_resources',
                            'value_proposition', 'customer_relationships', 'channels',
                            'customer_segments', 'cost_structure', 'revenue_streams'
                        );
                        
                        foreach ($global_order as $section_key) {
                            if (isset($canvas_sections[$section_key])) {
                                echo render_canvas_section($section_key, $canvas_sections[$section_key], $canvas_data, $project, $view_mode);
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

<!-- Popup d'édition des briques -->
<div id="wp-bmc-edit-popup" class="wp-bmc-popup">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <div class="popup-header">
            <h3 id="popup-title">Éditer la brique</h3>
            <button class="popup-close" id="popup-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="popup-body">
            <!-- Section éditeur -->
            <div class="editor-section">
                <label for="wysiwyg-editor">Contenu de la brique</label>
                <div id="wysiwyg-editor">
                    <!-- L'éditeur sera initialisé par JavaScript -->
                </div>
            </div>
            
            <!-- Section fichiers -->
            <div class="files-section">
                <div class="files-header">
                    <h4>Fichiers attachés</h4>
                    <button type="button" class="add-file-btn" id="add-file-btn">
                        <i class="fas fa-plus"></i> Ajouter des fichiers
                    </button>
                </div>
                <div class="files-list" id="files-list">
                    <div class="no-files">Aucun fichier attaché</div>
                </div>
                <input type="file" id="file-input" multiple style="display: none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
            
            <!-- Section documents de référence -->
            <div class="documents-section">
                <div class="documents-header">
                    <h4>Documents de référence</h4>
                    <button type="button" class="view-documents-btn" id="view-documents-btn">
                        <i class="fas fa-eye"></i> Consulter les documents
                    </button>
                </div>
                <div class="documents-list" id="documents-list">
                    <div class="no-documents">Aucun document disponible</div>
                </div>
            </div>
        </div>
        
        <div class="popup-footer">
            <button type="button" class="popup-btn popup-btn-secondary" id="popup-cancel">Annuler</button>
            <button type="button" class="popup-btn popup-btn-primary" id="popup-save">Sauvegarder</button>
        </div>
    </div>
</div>

<!-- Popup des documents de référence -->
<div id="wp-bmc-documents-popup" class="wp-bmc-popup">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <div class="popup-header">
            <h3>Documents de référence</h3>
            <button class="popup-close" id="documents-popup-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="popup-body">
            <div class="documents-grid" id="documents-grid">
                <!-- Les documents seront chargés dynamiquement -->
            </div>
        </div>
        
        <div class="popup-footer">
            <button type="button" class="popup-btn popup-btn-secondary" id="documents-popup-close">Fermer</button>
        </div>
    </div>
</div>
