<?php
/**
 * Template pour la page d'administration principale
 */

if (!defined('ABSPATH')) {
    exit;
}

// Déclarer la variable globale $wpdb
global $wpdb;

// Obtenir les statistiques
$users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_users");
$projects_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_projects");
$canvas_data_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_canvas_data");

// Obtenir les derniers utilisateurs (pour la section récente)
$recent_users = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}bmc_users 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Obtenir tous les utilisateurs (pour la liste complète) avec les demandes de notation
$all_users = $wpdb->get_results("
    SELECT u.*, 
           COUNT(p.id) as project_count,
           MAX(p.created_at) as last_project_date,
           COUNT(gr.id) as grading_requests_count,
           MAX(gr.created_at) as last_grading_request_date,
           GROUP_CONCAT(DISTINCT gr.status) as grading_statuses
    FROM {$wpdb->prefix}bmc_users u
    LEFT JOIN {$wpdb->prefix}bmc_projects p ON u.user_id = p.user_id
    LEFT JOIN {$wpdb->prefix}bmc_grading_requests gr ON p.id = gr.project_id
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

// Obtenir les notifications non lues pour l'admin actuel
$current_admin_id = get_current_user_id();
$unread_notifications = WP_BMC_Database::get_unread_notifications($current_admin_id);

// Obtenir les demandes de notation en attente
$pending_grading_requests = WP_BMC_Database::get_pending_grading_requests();
?>

<div class="wrap">
    <h1>WP Business Model Canvas - Administration</h1>
    
    <?php if (isset($message)): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Section des notifications -->
    <div class="wp-bmc-notifications-section">
        <h2>Notifications</h2>
        
        <?php if (!empty($unread_notifications)): ?>
            <div class="notifications-list">
                <?php foreach ($unread_notifications as $notification): ?>
                    <div class="notification-item" data-notification-id="<?php echo $notification->id; ?>">
                        <div class="notification-content">
                            <div class="notification-message">
                                <?php echo esc_html($notification->message); ?>
                            </div>
                            <div class="notification-meta">
                                <span class="notification-type"><?php echo esc_html($notification->type); ?></span>
                                <span class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notification->created_at)); ?></span>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <button class="button button-small mark-read-btn" 
                                    data-notification-id="<?php echo $notification->id; ?>">
                                Marquer comme lu
                            </button>
                            <?php if ($notification->type === 'grading_request'): ?>
                                <?php 
                                $data = json_decode($notification->data, true);
                                if ($data && isset($data['project_id'])): 
                                ?>
                                    <button class="button button-small button-primary grade-btn" 
                                            data-project-id="<?php echo $data['project_id']; ?>"
                                            data-section="<?php echo $data['section']; ?>">
                                        Noter
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucune notification non lue.</p>
        <?php endif; ?>
        
        <!-- Demandes de notation en attente -->
        <?php if (!empty($pending_grading_requests)): ?>
            <div class="grading-requests-section">
                <h3>Demandes de notation en attente</h3>
                <div class="grading-requests-list">
                    <?php foreach ($pending_grading_requests as $request): ?>
                        <div class="grading-request-item">
                            <div class="request-info">
                                <strong><?php echo esc_html($request->user_name); ?></strong> 
                                demande une notation pour la section 
                                <strong><?php echo esc_html($request->section_title); ?></strong>
                                du projet <strong><?php echo esc_html($request->project_title); ?></strong>
                            </div>
                            <div class="request-meta">
                                Demandé le : <?php echo date('d/m/Y H:i', strtotime($request->created_at)); ?>
                            </div>
                            <div class="request-actions">
                                <button class="button button-primary grade-section-btn" 
                                        data-project-id="<?php echo $request->project_id; ?>"
                                        data-section="<?php echo $request->section; ?>"
                                        data-section-title="<?php echo esc_attr($request->section_title); ?>">
                                    Noter cette section
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Liste complète des utilisateurs -->
    <div class="wp-bmc-all-users">
        <h2>Tous les utilisateurs</h2>
        
        <div class="wp-bmc-users-controls">
            <div class="users-search">
                <input type="text" id="users-search" placeholder="Rechercher un utilisateur..." class="regular-text">
            </div>
            <div class="users-filters">
                <select id="users-filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select>
                <select id="users-filter-grading">
                    <option value="">Toutes les demandes</option>
                    <option value="no-requests">Aucune demande</option>
                    <option value="pending">En attente</option>
                    <option value="graded">Noté</option>
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
                    
                    <th class="sortable" data-sort="created_at">
                        Inscription <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="last_project_date">
                        Dernier projet <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="grading_status">
                        Demande de notation <span class="sort-indicator"></span>
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
                        <td class="user-grading-status">
                            <?php 
                            $grading_statuses = $user->grading_statuses ? explode(',', $user->grading_statuses) : array();
                            $grading_requests_count = intval($user->grading_requests_count);
                            
                            if ($grading_requests_count == 0): ?>
                                <span class="grading-status no-requests">
                                    <i class="fas fa-check-circle"></i> Aucune demande
                                </span>
                            <?php else: ?>
                                <?php if (in_array('pending', $grading_statuses)): ?>
                                    <span class="grading-status pending">
                                        <i class="fas fa-clock"></i> En attente
                                        <span class="request-count">(<?php echo $grading_requests_count; ?>)</span>
                                    </span>
                                <?php elseif (in_array('graded', $grading_statuses)): ?>
                                    <span class="grading-status graded">
                                        <i class="fas fa-check-circle"></i> Noté
                                        <span class="request-count">(<?php echo $grading_requests_count; ?>)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="grading-status other">
                                        <i class="fas fa-info-circle"></i> Autre
                                        <span class="request-count">(<?php echo $grading_requests_count; ?>)</span>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($user->last_grading_request_date): ?>
                                    <div class="grading-date">
                                        Dernière demande : <?php echo date('d/m/Y', strtotime($user->last_grading_request_date)); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="user-actions">
                            <div class="action-buttons">
                                <button class="button button-small button-primary view-user-btn" 
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        title="Voir le profil">
                                    <i class="fas fa-eye"></i>
                                </button>
                                
                                <form method="post" action="" style="display: inline;">
                                    <?php wp_nonce_field('wp_bmc_admin_nonce'); ?>
                                    <input type="hidden" name="action" value="wp_bmc_admin_action">
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="wp-bmc-users-pagination">
            <div class="pagination-info">
                <span id="users-count"><?php echo count($all_users); ?> utilisateur(s) au total</span>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le template d'édition pour l'admin
wp_bmc_include_edit_section('admin');
?>