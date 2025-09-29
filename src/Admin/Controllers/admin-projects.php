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

<style>
.wp-bmc-admin-projects {
    max-width: 1200px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #0073aa;
    font-size: 16px;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #333;
}

.wp-bmc-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.wp-bmc-section h2 {
    margin-top: 0;
    color: #0073aa;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.wp-bmc-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-actions {
    margin-top: 20px;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.project-card {
    background: #f9f9f9;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.3s ease;
}

.project-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.project-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.project-header h3 {
    margin: 0;
    color: #333;
}

.project-actions {
    display: flex;
    gap: 5px;
}

.project-description {
    color: #666;
    margin-bottom: 15px;
}

.project-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.project-status {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-published {
    background: #d4edda;
    color: #155724;
}

.project-date {
    color: #666;
}

.project-users h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #333;
}

.users-list {
    max-height: 150px;
    overflow-y: auto;
}

.user-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e1e1e1;
}

.user-item:last-child {
    border-bottom: none;
}

.user-name {
    font-weight: 500;
}

.user-name small {
    color: #666;
    font-weight: normal;
}

.no-users {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.wp-bmc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e1e1;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e1e1e1;
    text-align: right;
}

.available-users h4 {
    margin: 0 0 15px 0;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
}

.error {
    color: #d63384;
    text-align: center;
    padding: 20px;
}

.available-user {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    margin-bottom: 8px;
    padding: 12px;
}

.available-user .user-name {
    font-weight: 500;
    color: #333;
}

.add-user-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 4px 8px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
}

.add-user-btn:hover {
    background: #218838;
}

.add-user-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .projects-grid {
        grid-template-columns: 1fr;
    }
    
    .project-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .project-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Création d'un projet
    $('#create-project-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');
        
        var formData = {
            action: 'wp_bmc_create_project',
            nonce: $form.find('[name="wp_bmc_admin_nonce"]').val(),
            title: $('#project_title').val(),
            description: $('#project_description').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de la création du projet.');
        }).always(function() {
            $submitBtn.prop('disabled', false).html(originalText);
        });
    });
    
    // Gestion des utilisateurs
    $('.manage-users-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        openManageUsersModal(projectId);
    });
    
    // Retirer un utilisateur d'un projet
    $('.remove-user-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        var userId = $(this).data('user-id');
        
        if (confirm('Êtes-vous sûr de vouloir retirer cet utilisateur du projet ?')) {
            removeUserFromProject(projectId, userId, $(this));
        }
    });
    
    // Fermer les modales
    $('.modal-close').on('click', function() {
        $(this).closest('.wp-bmc-modal').hide();
    });
    
    function openManageUsersModal(projectId) {
        $('#manage-users-modal').show();
        loadAvailableUsers(projectId);
    }
    
    function loadAvailableUsers(projectId) {
        var $container = $('#available-users-list');
        $container.html('<div class="loading">Chargement des utilisateurs...</div>');
        
        // Charger les utilisateurs disponibles
        $.post(ajaxurl, {
            action: 'wp_bmc_get_available_users',
            project_id: projectId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                var users = response.data.users;
                var html = '';
                
                if (users.length === 0) {
                    html = '<p class="no-users">Aucun utilisateur disponible à assigner.</p>';
                } else {
                    users.forEach(function(user) {
                        html += '<div class="user-item available-user" data-user-id="' + user.user_id + '">';
                        html += '<span class="user-name">';
                        html += user.first_name + ' ' + user.last_name;
                        if (user.custom_id) {
                            html += ' <small>(' + user.custom_id + ')</small>';
                        }
                        html += '</span>';
                        html += '<button class="button button-small add-user-btn" ';
                        html += 'data-project-id="' + projectId + '" ';
                        html += 'data-user-id="' + user.user_id + '">';
                        html += '<i class="fas fa-plus"></i> Ajouter';
                        html += '</button>';
                        html += '</div>';
                    });
                }
                
                $container.html(html);
                
                // Gérer l'ajout d'utilisateurs
                $('.add-user-btn').on('click', function() {
                    var $btn = $(this);
                    var userId = $btn.data('user-id');
                    var projectId = $btn.data('project-id');
                    
                    addUserToProject(projectId, userId, $btn);
                });
            } else {
                $container.html('<p class="error">Erreur lors du chargement des utilisateurs.</p>');
            }
        }).fail(function() {
            $container.html('<p class="error">Erreur de connexion.</p>');
        });
    }
    
    function addUserToProject(projectId, userId, $button) {
        var originalText = $button.html();
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_assign_user_to_project',
            project_id: projectId,
            user_id: userId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.user-item').fadeOut(300, function() {
                    $(this).remove();
                });
                // Recharger la liste des utilisateurs du projet
                location.reload();
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de l\'ajout de l\'utilisateur.');
        }).always(function() {
            $button.prop('disabled', false).html(originalText);
        });
    }
    
    function removeUserFromProject(projectId, userId, $button) {
        var formData = {
            action: 'wp_bmc_remove_user_from_project',
            nonce: $('input[name="wp_bmc_admin_nonce"]').val(),
            project_id: projectId,
            user_id: userId
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.user-item').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors du retrait de l\'utilisateur.');
        });
    }
});
</script>
