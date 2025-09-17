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
           p.title as project_name,
           COUNT(DISTINCT p.id) as project_count,
           MAX(p.created_at) as last_project_date,
           COUNT(DISTINCT gr.id) as total_grading_requests_count,
           SUM(CASE WHEN gr.status = 'pending' THEN 1 ELSE 0 END) as pending_grading_requests_count,
           MAX(gr.created_at) as last_grading_request_date,
           GROUP_CONCAT(DISTINCT gr.status) as grading_statuses,
           COALESCE((
               SELECT SUM(r2.rating) 
               FROM {$wpdb->prefix}bmc_ratings r2 
               JOIN {$wpdb->prefix}bmc_projects p2 ON r2.project_id = p2.id 
               WHERE p2.user_id = u.user_id
           ), 0) as sum_rating_bricks
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
    <h1>Dashboard</h1>

    <?php if (isset($message)): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Section des notifications -->
    <div class="wp-bmc-notifications-section">
        <!-- <div class="notifications-header">
            <h2>
                <i class="fas fa-bell"></i>
                Notifications
                <?php if (!empty($unread_notifications)): ?>
                    <span class="notification-badge"><?php echo count($unread_notifications); ?></span>
                <?php endif; ?>
            </h2>
            <?php if (!empty($unread_notifications)): ?>
                <button class="button button-secondary mark-all-read-btn">
                    <i class="fas fa-check-double"></i> Tout marquer comme lu
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($unread_notifications)): ?>
            <div class="notifications-list">
                <?php foreach ($unread_notifications as $notification): ?>
                    <div class="notification-item" data-notification-id="<?php echo $notification->id; ?>">
                        <div class="notification-icon">
                            <?php if ($notification->type === 'grading_request'): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message">
                                <?php echo esc_html($notification->message); ?>
                            </div>
                            <div class="notification-meta">
                                <span class="notification-type">
                                    <?php
                                    $type_labels = array(
                                        'grading_request' => 'Demande de notation',
                                        'info' => 'Information',
                                        'warning' => 'Avertissement',
                                        'success' => 'Succès'
                                    );
                                    echo esc_html($type_labels[$notification->type] ?? $notification->type);
                                    ?>
                                </span>
                                <span class="notification-date">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($notification->created_at)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <button class="button button-small mark-read-btn" 
                                    data-notification-id="<?php echo $notification->id; ?>"
                                    title="Marquer comme lu">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php if ($notification->type === 'grading_request'): ?>
                                <?php
                                $data = json_decode($notification->data, true);
                                if ($data && isset($data['project_id'])):
                                ?>
                                    <button class="button button-small button-primary grade-btn" 
                                            data-project-id="<?php echo $data['project_id']; ?>"
                                            data-user-id="<?php echo $data['user_id']; ?>"
                                            data-section="<?php echo $data['section']; ?>"
                                            data-section-title="<?php echo esc_attr($data['section_title']); ?>"
                                            title="Noter cette section">
                                        <i class="fas fa-star"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>Aucune notification non lue.</p>
            </div>
        <?php endif; ?> -->

        <!-- Demandes de notation en attente -->
        <?php if (!empty($pending_grading_requests)): ?>
            <div class="grading-requests-section">
                <div class="grading-requests-header">
                    <h3>
                        <i class="fas fa-star-half-alt"></i>
                        Demandes de notation en attente
                        <span class="grading-count"><?php echo count($pending_grading_requests); ?></span>
                    </h3>
                </div>
                <div class="grading-requests-list">
                    <?php foreach ($pending_grading_requests as $request): ?>
                        <div class="grading-request-item">
                            <div class="request-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="request-content">
                                <div class="request-info">
                                    <div class="request-user">
                                        <strong><?php echo esc_html($request->user_name); ?></strong>
                                    </div>
                                    <div class="request-details">
                                        demande une notation pour la section
                                        <span class="section-name"><?php echo esc_html($request->section_title); ?></span>
                                        du projet <span class="project-name"><?php echo esc_html($request->project_title); ?></span>
                                    </div>
                                </div>
                                <div class="request-meta">
                                    <span class="request-date">
                                        <i class="fas fa-clock"></i>
                                        Demandé le <?php echo date('d/m/Y à H:i', strtotime($request->created_at)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="request-actions">
                                <button class="button btn-primary grade-section-btn"
                                    data-project-id="<?php echo $request->project_id; ?>"
                                    data-user-id="<?php echo $request->user_id; ?>"
                                    data-section="<?php echo $request->section; ?>"
                                    data-section-title="<?php echo esc_attr($request->section_title); ?>"
                                    title="Noter cette section">
                                    <i class="fas fa-star"></i> Noter
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
        <h2>Utilisateurs</h2>

        <div class="wp-bmc-users-controls">
            <div class="users-search">
                <input type="text" id="users-search" placeholder="Rechercher un utilisateur..." class="regular-text">
            </div>
            <div class="users-filters">
                <!-- <select id="users-filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select> -->
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
                        PRÉNOM - NOM <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="email">
                        EMAIL <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="last_project_date">
                        PROJET <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="grading_status">
                        STATUT <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="project_advancement">
                        AVANCEMENT <span class="sort-indicator"></span>
                    </th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $user): ?>
                    <tr class="user-row" data-user-id="<?php echo $user->user_id; ?>">
                        <td class="user-name-container">
                            <div class="user-name edit-user-btn">
                                <div class="user-avatar">
                                    <?php
                                    $initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                                    echo esc_html($initials);
                                    ?>
                                </div>
                                <strong><?php echo esc_html($user->first_name . ' ' . strtoupper($user->last_name)); ?></strong>
                            </div>

                        </td>
                        <td class="user-email">
                            <a href="mailto:<?php echo esc_attr($user->email); ?>">
                                <?php echo esc_html($user->email); ?>
                            </a>
                        </td>

                        <!-- <td class="user-registration">
                            <?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?>
                        </td> -->
                        <td class="user-project-name">
                            <?php echo $user->project_name; ?>
                        </td>
                        <td class="user-grading-status">
                            <?php
                            $grading_statuses = $user->grading_statuses ? explode(',', $user->grading_statuses) : array();
                            $total_grading_requests_count = intval($user->total_grading_requests_count);
                            $pending_grading_requests_count = intval($user->pending_grading_requests_count);

                            if ($total_grading_requests_count == 0): ?>
                                <span class="grading-status no-requests">
                                    <i class="fas fa-check-circle"></i>
                                    <p>Aucune demande</p>
                                </span>
                            <?php else: ?>
                                <?php if (in_array('pending', $grading_statuses)): ?>
                                    <span class="grading-status pending">
                                        <i class="fas fa-clock"></i>
                                        <p>En attente</p>
                                        <span class="request-count">(<?php echo $pending_grading_requests_count; ?>)</span>
                                    </span>
                                <?php elseif (in_array('graded', $grading_statuses)): ?>
                                    <span class="grading-status graded">
                                        <i class="fas fa-check-circle"></i>
                                        <p>Noté</p>
                                        <span class="request-count">(<?php echo $total_grading_requests_count; ?>)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="grading-status other">
                                        <i class="fas fa-info-circle"></i>
                                        <p>Autre</p>
                                        <span class="request-count">(<?php echo $total_grading_requests_count; ?>)</span>
                                    </span>
                                <?php endif; ?>

                                <?php if ($user->last_grading_request_date): ?>
                                    <div class="grading-date">
                                        Dernière demande : <?php echo date('d/m/Y', strtotime($user->last_grading_request_date)); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="user-project-advancement">
                            <?php
                            $rating_value = intval($user->sum_rating_bricks);
                            $percentage = round(($rating_value / 90) * 100, 0);
                            $rating_class = 'red-rating';

                            if ($rating_value >= 70) {
                                $rating_class = 'green-rating';
                            } elseif ($rating_value >= 50) {
                                $rating_class = 'blue-rating';
                            } elseif ($rating_value >= 30) {
                                $rating_class = 'orange-rating';
                            }
                            ?>
                            <div class="advancement-container">
                                <div class="progress-bar <?php echo $rating_class; ?>">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="advancement-info">
                                    <span class="rating-value"><?php echo $percentage; ?>%</span>
                                </div>

                            </div>
                        </td>

                        <td class="user-actions">
                            <div class="action-buttons">
                                <button class="button action-button button-small button-primary view-user-btn"
                                    data-user-id="<?php echo $user->user_id; ?>"
                                    title="Voir le profil">
                                    <i class="fas fa-arrow-right"></i>
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