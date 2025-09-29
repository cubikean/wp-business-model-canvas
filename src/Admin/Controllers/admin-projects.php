<?php
/**
 * Gestion des projets pour WP Business Model Canvas v2.0
 * Interface d'administration pour créer et gérer les projets
 */

if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que l'utilisateur est admin
if (!current_user_can('manage_options')) {
    wp_die('Accès non autorisé');
}

// Déclarer la variable globale $wpdb
global $wpdb;

// Obtenir tous les projets
$all_projects = WP_BMC_Database::get_all_projects();

// Obtenir tous les utilisateurs
$all_users = WP_BMC_Database::get_all_users();
?>

<div class="wrap wp-bmc-admin-projects">
    <h1>📋 Gestion des Projets - Business Model Canvas v2.0</h1>
    
    <!-- Statistiques -->
    <div class="wp-bmc-stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Projets</h3>
                <div class="stat-number"><?php echo count($all_projects); ?></div>
                <p>Projets créés</p>
            </div>
            
            <div class="stat-card">
                <h3>Utilisateurs</h3>
                <div class="stat-number"><?php echo count($all_users); ?></div>
                <p>Utilisateurs actifs</p>
            </div>
        </div>
    </div>
    
    <!-- Création d'un nouveau projet -->
    <div class="wp-bmc-section">
        <h2>➕ Créer un nouveau projet</h2>
        <form id="create-project-form" class="wp-bmc-form">
            <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
            <div class="form-group">
                <label for="project_title">Titre du projet *</label>
                <input type="text" id="project_title" name="title" class="regular-text" required>
            </div>
            
            <div class="form-group">
                <label for="project_description">Description</label>
                <textarea id="project_description" name="description" rows="3" class="large-text"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-plus"></i> Créer le projet
                </button>
            </div>
        </form>
    </div>
    
    <!-- Liste des projets -->
    <div class="wp-bmc-section">
        <h2>📋 Projets existants</h2>
        
        <?php if (empty($all_projects)): ?>
            <p>Aucun projet n'a été créé.</p>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($all_projects as $project): ?>
                    <div class="project-card" data-project-id="<?php echo $project->id; ?>">
                        <div class="project-header">
                            <h3><?php echo esc_html($project->title); ?></h3>
                            <div class="project-actions">
                                <button class="button button-small edit-project-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="button button-small manage-users-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-users"></i>
                                </button>
                                <button class="button button-small view-canvas-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-chart-area"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="project-content">
                            <?php if ($project->description): ?>
                                <p class="project-description"><?php echo esc_html($project->description); ?></p>
                            <?php endif; ?>
                            
                            <div class="project-meta">
                                <span class="project-status status-<?php echo $project->status; ?>">
                                    <?php echo ucfirst($project->status); ?>
                                </span>
                                <span class="project-date">
                                    Créé le <?php echo date('d/m/Y', strtotime($project->created_at)); ?>
                                </span>
                            </div>
                            
                            <div class="project-users">
                                <h4>Utilisateurs assignés</h4>
                                <div class="users-list" id="users-list-<?php echo $project->id; ?>">
                                    <?php
                                    $project_users = WP_BMC_Database::get_project_users($project->id);
                                    if (empty($project_users)):
                                    ?>
                                        <p class="no-users">Aucun utilisateur assigné</p>
                                    <?php else: ?>
                                        <?php foreach ($project_users as $user): ?>
                                            <div class="user-item" data-user-id="<?php echo $user->user_id; ?>">
                                                <span class="user-name">
                                                    <?php echo esc_html($user->first_name . ' ' . $user->last_name); ?>
                                                    <?php if ($user->custom_id): ?>
                                                        <small>(<?php echo esc_html($user->custom_id); ?>)</small>
                                                    <?php endif; ?>
                                                </span>
                                                <button class="button button-small remove-user-btn" 
                                                        data-project-id="<?php echo $project->id; ?>" 
                                                        data-user-id="<?php echo $user->user_id; ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de gestion des utilisateurs -->
<div id="manage-users-modal" class="wp-bmc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gérer les utilisateurs du projet</h3>
            <button class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="available-users">
                <h4>Utilisateurs disponibles</h4>
                <div class="users-list" id="available-users-list">
                    <!-- Sera rempli via JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="button button-secondary modal-close">Fermer</button>
        </div>
    </div>
</div>


