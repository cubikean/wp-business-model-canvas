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

// Vérifier s'il y a un filtre par superviseur
$supervisor_filter = isset($_GET['supervisor']) ? intval($_GET['supervisor']) : 0;
$supervisor_name = '';

// Obtenir les projets (filtrés ou tous)
if ($supervisor_filter > 0) {
    // Obtenir uniquement les projets du superviseur
    $all_projects = WP_BMC_Database::get_supervisor_projects($supervisor_filter);
    
    // Obtenir le nom du superviseur pour l'affichage
    $supervisor = get_user_by('ID', $supervisor_filter);
    if ($supervisor) {
        $supervisor_name = $supervisor->display_name;
    }
} else {
    // Obtenir tous les projets
    $all_projects = WP_BMC_Database::get_all_projects();
}

// Obtenir tous les utilisateurs
$all_users = WP_BMC_Database::get_all_users();
?>

<div class="wrap wp-bmc-admin-projects">
    <h1>Gestion des Projets</h1>
    
    <?php if ($supervisor_filter > 0 && !empty($supervisor_name)): ?>
        <div class="filter-active-notice">
            <div class="filter-info">
                <i class="fas fa-filter"></i>
                <strong>Filtre actif :</strong> Projets supervisés par <strong><?php echo esc_html($supervisor_name); ?></strong>
            </div>
            <a href="<?php echo admin_url('admin.php?page=wp-business-model-canvas-projects'); ?>" class="button button-secondary">
                <i class="fas fa-times"></i> Retirer le filtre
            </a>
        </div>
    <?php endif; ?>
    
    <?php if ($supervisor_filter == 0): ?>
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
        
        <div class="danger-zone">
            <button id="wp-bmc-reset-database-btn" class="button button-link-delete button-large">
                <i class="fas fa-exclamation-triangle"></i> Vider la base de données du plugin
            </button>
            <p class="description danger-text">⚠️ <strong>ATTENTION</strong> : Cette action supprimera TOUTES les données (utilisateurs, projets, canvas, notes, etc.). Action irréversible !</p>
        </div>
    </div>
    
    <!-- Import CSV Complet (Unifié) -->
    <div class="wp-bmc-section csv-unified-section">
        <h2>🚀 Import CSV Complet (Recommandé)</h2>
        <p class="description"><strong>Importez tout en une seule fois !</strong> Ce formulaire va créer automatiquement les utilisateurs, les superviseurs, les projets ET effectuer toutes les assignations.</p>
        
        <div class="csv-import-container">
            <form id="import-complete-csv-form" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
                
                <div class="csv-upload-area">
                    <input type="file" id="csv-complete-file" name="csv_file" accept=".csv" required>
                    <label for="csv-complete-file" class="csv-upload-label csv-upload-complete">
                        <i class="fas fa-magic"></i>
                        <span>Choisir un fichier CSV complet</span>
                        <small>Import automatique : Utilisateurs + Superviseurs + Projets</small>
                    </label>
                    <div class="csv-complete-file-name" style="display: none;"></div>
                </div>

                <div class="csv-info-box csv-info-complete">
                    <h4>✨ Format attendu du CSV complet :</h4>
                    <ul>
                        <li><strong>Prénom</strong> + <strong>Nom</strong> + <strong>E-mail</strong> + <strong>Candidature</strong> → Créera les utilisateurs</li>
                        <li><strong>Tuteur</strong> + <strong>Coordonnées du tuteur</strong> → Créera les superviseurs</li>
                        <li><strong>Nom du projet</strong> + <strong>Résumé du projet</strong> → Créera les projets</li>
                        <li>🎯 <strong>Assignations automatiques</strong> : Utilisateur + Superviseur → Projet</li>
                    </ul>
                    <p><small>
                        <strong>Mots de passe générés :</strong><br>
                        • Utilisateurs : <code>Candidature + Prénom</code><br>
                        • Superviseurs : <code>Prénom + 6 caractères aléatoires</code>
                    </small></p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large button-hero">
                        <i class="fas fa-magic"></i> Import complet (tout en un)
                    </button>
                </div>
            </form>

            <div id="csv-complete-import-results" style="display: none;">
                <h3>📊 Résultats de l'import complet</h3>
                <div class="import-stats-complete"></div>
                <div class="import-details"></div>
            </div>
        </div>
    </div>

    <hr style="margin: 40px 0; border: none; border-top: 2px solid #e1e1e1;">
    <h2 style="text-align: center; color: #666; margin: 40px 0;">📑 Imports séparés (optionnel)</h2>

    <!-- Import CSV Projets -->
    <!-- <div class="wp-bmc-section">
        <h2>📁 Importer uniquement des projets</h2>
        <p class="description">Importez plusieurs projets en une seule fois depuis un fichier CSV. Le fichier doit contenir : <strong>Nom du projet</strong>, <strong>Résumé du projet</strong>, <strong>E-mail</strong> (utilisateur), <strong>Coordonnées du tuteur</strong> (superviseur).</p>
        
        <div class="csv-import-container">
            <form id="import-projects-csv-form" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
                
                <div class="csv-upload-area">
                    <input type="file" id="csv-projects-file" name="csv_file" accept=".csv" required>
                    <label for="csv-projects-file" class="csv-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choisir un fichier CSV</span>
                        <small>ou glisser-déposer ici</small>
                    </label>
                    <div class="csv-projects-file-name" style="display: none;"></div>
                </div>

                <div class="csv-info-box">
                    <h4>📋 Format attendu du CSV :</h4>
                    <ul>
                        <li><strong>Nom du projet</strong> : Titre du projet</li>
                        <li><strong>Résumé du projet</strong> : Description du projet</li>
                        <li><strong>E-mail</strong> : Email de l'utilisateur (étudiant) à assigner</li>
                        <li><strong>Coordonnées du tuteur</strong> : Email du superviseur à assigner</li>
                    </ul>
                    <p><small><strong>Note :</strong> Les utilisateurs et superviseurs doivent déjà exister dans la base de données.</small></p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <i class="fas fa-upload"></i> Importer les projets
                    </button>
                </div>
            </form>

            <div id="csv-projects-import-results" style="display: none;">
                <h3>Résultats de l'import</h3>
                <div class="import-stats"></div>
                <div class="import-details"></div>
            </div>
        </div>
    </div> -->

    <!-- Création d'un nouveau projet -->
    <div class="wp-bmc-section">
        <h2>➕ Créer un projet individuel</h2>
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
    <?php endif; // Fin de la condition supervisor_filter == 0 ?>
    
    <!-- Liste des projets -->
    <div class="wp-bmc-section">
        <h2>📋 Projets existants<?php if ($supervisor_filter > 0) echo ' - ' . esc_html($supervisor_name); ?></h2>
        
        <?php if (empty($all_projects)): ?>
            <?php if ($supervisor_filter > 0): ?>
                <div class="no-projects-filtered">
                    <i class="fas fa-inbox"></i>
                    <p><strong><?php echo esc_html($supervisor_name); ?></strong> ne supervise aucun projet pour le moment.</p>
                    <a href="<?php echo admin_url('admin.php?page=wp-business-model-canvas-projects'); ?>" class="button button-primary">
                        <i class="fas fa-arrow-left"></i> Voir tous les projets
                    </a>
                </div>
            <?php else: ?>
                <p>Aucun projet n'a été créé.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="projects-controls">
                <div class="projects-search">
                    <input type="text" id="projects-search" placeholder="Rechercher un projet par titre ou description..." class="regular-text">
                </div>
                <div class="projects-count">
                    <span id="projects-count"><?php echo count($all_projects); ?> projet(s) au total</span>
                </div>
            </div>
            
            <div class="projects-grid">
                <?php foreach ($all_projects as $project): ?>
                    <div class="project-card <?php echo $supervisor_filter > 0 ? 'filtered-project' : ''; ?>" 
                         data-project-id="<?php echo $project->id; ?>"
                         data-project-title="<?php echo esc_attr(strtolower($project->title)); ?>"
                         data-project-description="<?php echo esc_attr(strtolower($project->description)); ?>">
                        <?php if ($supervisor_filter > 0): ?>
                            <div class="filter-badge">
                                <i class="fas fa-filter"></i> Supervisé par <?php echo esc_html($supervisor_name); ?>
                            </div>
                        <?php endif; ?>
                        <div class="project-header">
                            <h3><?php echo esc_html($project->title); ?></h3>
                            <div class="project-actions">
                                <button class="button button-small edit-project-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="button button-small manage-users-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-users"></i>
                                </button>
                                <button class="button button-small manage-admins-btn" data-project-id="<?php echo $project->id; ?>">
                                    <i class="fas fa-users-cog"></i>
                                </button>
                                <button class="button button-small delete-project-btn" data-project-id="<?php echo $project->id; ?>" data-project-title="<?php echo esc_attr($project->title); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="button view-canvas-btn" data-project-id="<?php echo $project->id; ?>">
                                    Voir le projet
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

                            <div class="project-supervisors">
                                <h4>Superviseurs assignés</h4>
                                <div class="admins-list" id="admins-list-<?php echo $project->id; ?>">
                                    <?php
                                    $project_supervisors = WP_BMC_Database::get_project_supervisors($project->id);
                                    if (empty($project_supervisors)):
                                    ?>
                                        <p class="no-admins">Aucun superviseur assigné</p>
                                    <?php else: ?>
                                        <?php foreach ($project_supervisors as $supervisor): ?>
                                            <div class="admin-item" data-admin-id="<?php echo $supervisor->user_id; ?>">
                                                <span class="admin-name">
                                                    <?php echo esc_html($supervisor->display_name); ?>
                                                    <small>(<?php echo esc_html($supervisor->user_email); ?>)</small>
                                                </span>
                                                <button class="button button-small remove-admin-btn" 
                                                        data-project-id="<?php echo $project->id; ?>" 
                                                        data-admin-id="<?php echo $supervisor->user_id; ?>">
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

<!-- Modal d'édition de projet -->
<div id="edit-project-modal" class="wp-bmc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Éditer le projet</h3>
            <button class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="edit-project-form">
                <div class="form-field">
                    <label for="edit_project_title">Titre du projet *</label>
                    <input type="text" id="edit_project_title" name="title" required>
                </div>
                <div class="form-field">
                    <label for="edit_project_description">Description *</label>
                    <textarea id="edit_project_description" name="description" rows="5" required></textarea>
                </div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="submit" form="edit-project-form" class="wp-bmc-btn button-primary bmc-btn-solid">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <button type="button" class="button button-secondary modal-close">Annuler</button>
        </div>
    </div>
</div>

<!-- Modal de gestion des superviseurs -->
<div id="manage-admins-modal" class="wp-bmc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gérer les superviseurs du projet</h3>
            <button class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
            <div class="available-admins">
                <h4>Superviseurs disponibles</h4>
                <div class="admins-list" id="available-admins-list">
                    <!-- Sera rempli via JavaScript -->
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-secondary modal-close">Fermer</button>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div id="delete-project-modal" class="wp-bmc-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmer la suppression</h3>
            <button class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Êtes-vous sûr de vouloir supprimer le projet <strong id="delete-project-title"></strong> ?</p>
                <p class="warning-text">Cette action est irréversible et supprimera :</p>
                <ul>
                    <li>Toutes les données du canvas</li>
                    <li>Tous les todos associés</li>
                    <li>Toutes les évaluations</li>
                    <li>L'historique des révisions</li>
                </ul>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="button button-primary confirm-delete-btn" id="confirm-delete-project">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
            <button type="button" class="button button-secondary modal-close">Annuler</button>
        </div>
    </div>
</div>

