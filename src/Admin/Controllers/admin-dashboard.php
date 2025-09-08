<?php
/**
 * Dashboard administrateur pour WP Business Model Canvas
 * Interface complète de gestion des utilisateurs et projets
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

// Obtenir les statistiques
$users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_users");
$projects_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_projects");
$canvas_data_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_canvas_data");

// Obtenir tous les utilisateurs (pour la liste complète)
$all_users = $wpdb->get_results("
    SELECT u.*, 
           COUNT(p.id) as project_count,
           MAX(p.created_at) as last_project_date
    FROM {$wpdb->prefix}bmc_users u
    LEFT JOIN {$wpdb->prefix}bmc_projects p ON u.user_id = p.user_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");

// Obtenir les derniers projets
$recent_projects = $wpdb->get_results("
    SELECT p.*, u.first_name, u.last_name 
    FROM {$wpdb->prefix}bmc_projects p
    JOIN {$wpdb->prefix}bmc_users u ON p.user_id = u.user_id
    ORDER BY p.created_at DESC 
    LIMIT 10
");
?>

<div class="wrap wp-bmc-admin-dashboard">
    <h1>👑 Administration - Business Model Canvas</h1>
    
    <?php if (isset($message)): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="wp-bmc-stats-section">
        <h2>📊 Statistiques du système</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Utilisateurs</h3>
                <div class="stat-number"><?php echo $users_count; ?></div>
                <p>Comptes créés</p>
            </div>
            
            <div class="stat-card">
                <h3>Projets</h3>
                <div class="stat-number"><?php echo $projects_count; ?></div>
                <p>Canvas créés</p>
            </div>
            
            <div class="stat-card">
                <h3>Sections</h3>
                <div class="stat-number"><?php echo $canvas_data_count; ?></div>
                <p>Données sauvegardées</p>
            </div>
        </div>
    </div>
    
    <!-- Gestion des utilisateurs -->
    <div class="wp-bmc-users-section">
        <h2>👥 Gestion des utilisateurs</h2>
        
        <div class="users-controls">
            <div class="users-search">
                <input type="text" id="users-search" placeholder="Rechercher un utilisateur..." class="regular-text">
            </div>
            <div class="users-filters">
                <select id="users-filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped" id="users-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="name">
                        Nom <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="email">
                        Email <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="company">
                        Entreprise <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="project_count">
                        Projets <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="created_at">
                        Inscription <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="last_project_date">
                        Dernier projet <span class="sort-indicator"></span>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $user): ?>
                    <tr class="user-row" data-user-id="<?php echo $user->user_id; ?>">
                        <td class="user-name">
                            <strong><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></strong>
                        </td>
                        <td class="user-email">
                            <a href="mailto:<?php echo esc_attr($user->email); ?>">
                                <?php echo esc_html($user->email); ?>
                            </a>
                        </td>
                        <td class="user-company">
                            <?php echo esc_html($user->company); ?>
                        </td>
                        <td class="user-projects">
                            <span class="project-count"><?php echo $user->project_count; ?></span>
                            <?php if ($user->project_count > 0): ?>
                                <a href="#" class="view-projects-btn" data-user-id="<?php echo $user->user_id; ?>">
                                    Voir les projets
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="user-registration">
                            <?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?>
                        </td>
                        <td class="user-last-project">
                            <?php if ($user->last_project_date): ?>
                                <?php echo date('d/m/Y H:i', strtotime($user->last_project_date)); ?>
                            <?php else: ?>
                                <span class="no-project">Aucun projet</span>
                            <?php endif; ?>
                        </td>
                        <td class="user-actions">
                            <div class="action-buttons">
                                <button class="button button-small button-primary view-user-canvas-btn" 
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        title="Voir le canvas">
                                    <i class="fas fa-chart-area"></i>
                                </button>
                                
                                <button class="button button-small button-secondary edit-user-btn" 
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        title="Éditer l'utilisateur">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <form method="post" action="" style="display: inline;">
                                    <?php wp_nonce_field('wp_bmc_admin_nonce'); ?>
                                    <input type="hidden" name="action" value="wp_bmc_admin_action">
                                    <input type="hidden" name="bmc_action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user->user_id; ?>">
                                    <button type="submit" class="button button-small button-link-delete" 
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur et tous ses projets ?')"
                                            title="Supprimer l'utilisateur">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="users-pagination">
            <div class="pagination-info">
                <span id="users-count"><?php echo count($all_users); ?> utilisateur(s) au total</span>
            </div>
        </div>
    </div>
    
    <!-- Derniers projets -->
    <div class="wp-bmc-projects-section">
        <h2>📋 Derniers projets</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Utilisateur</th>
                    <th>Statut</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_projects as $project): ?>
                    <tr>
                        <td><?php echo esc_html($project->title); ?></td>
                        <td><?php echo esc_html($project->first_name . ' ' . $project->last_name); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $project->status; ?>">
                                <?php echo ucfirst($project->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($project->created_at)); ?></td>
                        <td>
                            <a href="<?php echo home_url('/business-model-canvas/?project_id=' . $project->id . '&admin_view=true'); ?>" 
                               class="button button-small button-primary" target="_blank">
                                Voir le projet
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Actions d'administration -->
    <div class="wp-bmc-actions-section">
        <h2>⚙️ Actions d'administration</h2>
        
        <div class="admin-actions">
            <button type="button" id="export-users-btn" class="button button-secondary">
                <i class="fas fa-download"></i> Exporter les utilisateurs (CSV)
            </button>
            
            <button type="button" id="export-data-btn" class="button button-secondary">
                <i class="fas fa-database"></i> Exporter toutes les données
            </button>
            
            <button type="button" id="clear-cache-btn" class="button button-secondary">
                <i class="fas fa-broom"></i> Vider le cache
            </button>
        </div>
    </div>
    
    <!-- Informations système -->
    <div class="wp-bmc-system-section">
        <h2>ℹ️ Informations système</h2>
        
        <table class="form-table">
            <tr>
                <th>Version du plugin</th>
                <td><?php echo WP_BMC_VERSION; ?></td>
            </tr>
            <tr>
                <th>Version WordPress</th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th>Version PHP</th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th>Base de données</th>
                <td><?php echo $wpdb->db_version(); ?></td>
            </tr>
        </table>
    </div>
</div>

<?php
// Inclure le template d'édition pour l'admin
wp_bmc_include_edit_section('admin');
?>
