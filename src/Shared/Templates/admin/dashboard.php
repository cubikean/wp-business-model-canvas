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

// Obtenir tous les utilisateurs (pour la liste complète) avec les demandes de notation et les groupes
$current_admin_id = get_current_user_id();
$all_users = $wpdb->get_results("
    SELECT u.*, 
           p.title as project_name,
           p.description as project_description,
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
           ), 0) as sum_rating_bricks,
           CASE WHEN as_rel.admin_id IS NOT NULL THEN 1 ELSE 0 END as is_my_student
    FROM {$wpdb->prefix}bmc_users u
    LEFT JOIN {$wpdb->prefix}bmc_projects p ON u.user_id = p.user_id
    LEFT JOIN {$wpdb->prefix}bmc_grading_requests gr ON p.id = gr.project_id
    LEFT JOIN {$wpdb->prefix}bmc_admin_students as_rel ON u.user_id = as_rel.student_id AND as_rel.admin_id = $current_admin_id
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
");

// Obtenir les informations des admins pour chaque utilisateur
foreach ($all_users as $user) {
    $admin_info = $wpdb->get_row($wpdb->prepare(
        "SELECT wp_user.display_name, wp_user.user_login 
         FROM {$wpdb->prefix}bmc_admin_students as_rel
         JOIN {$wpdb->prefix}users wp_user ON as_rel.admin_id = wp_user.ID
         WHERE as_rel.student_id = %d
         LIMIT 1",
        $user->user_id
    ));
    
    $user->admin_name = $admin_info ? $admin_info->display_name : null;
    $user->admin_login = $admin_info ? $admin_info->user_login : null;
}

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
                                    <i class="fas fa-star"></i> Évaluer
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
                <select id="users-filter-group">
                    <option value="">Tous les utilisateurs</option>
                    <option value="my-students">Mes étudiants</option>
                    <option value="managed-students">Étudiants gérés</option>
                    <option value="unmanaged-students">Non assignés</option>
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
                    <th class="sortable" data-sort="is_my_student">
                        GROUPE <span class="sort-indicator"></span>
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
                            <div class="project-info">
                                <div class="project-title">
                                    <?php echo esc_html($user->project_name); ?>
                                </div>
                                <?php if (!empty($user->project_description)): ?>
                                    <div class="project-description">
                                        <?php echo esc_html($user->project_description); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
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
                        
                        <td class="user-group">
                            <?php if ($user->admin_name && !$user->is_my_student): ?>
                                <span class="group-status managed-student">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4"/></svg>
                                    <span>Géré par <?php echo esc_html($user->admin_name); ?></span>
                                </span>
                               <?php elseif ($user->is_my_student): ?>
                                <span class="group-status managed-student">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4"/></svg>
                                    <span>Mon étudiant</span>
                                </span>
                            <?php else: ?>
                                <span class="group-status not-managed">
                                    <span>Non assigné</span>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="user-actions">
                            <div class="action-buttons">
                                <button class="button action-button button-small button-primary view-user-btn"
                                    data-user-id="<?php echo $user->user_id; ?>"
                                    title="Voir le profil">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/></svg>
                                </button>
                                
                                <?php if ($user->is_my_student): ?>
                                    <button class="button action-button button-small button-secondary remove-student-btn"
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        data-user-name="<?php echo esc_attr($user->first_name . ' ' . $user->last_name); ?>"
                                        title="Retirer de mes étudiants">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M14 8c0-2.21-1.79-4-4-4S6 5.79 6 8s1.79 4 4 4s4-1.79 4-4M2 18v1c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-1c0-2.66-5.33-4-8-4s-8 1.34-8 4m16-8h4c.55 0 1 .45 1 1s-.45 1-1 1h-4c-.55 0-1-.45-1-1s.45-1 1-1"/></svg>
                                    </button>
                                <?php elseif (!$user->admin_name): ?>
                                    <button class="button action-button button-small button-primary add-student-btn"
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        data-user-name="<?php echo esc_attr($user->first_name . ' ' . $user->last_name); ?>"
                                        title="Ajouter à mes étudiants">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m-9-2V8c0-.55-.45-1-1-1s-1 .45-1 1v2H2c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1v-2h2c.55 0 1-.45 1-1s-.45-1-1-1zm9 4c-2.67 0-8 1.34-8 4v1c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-1c0-2.66-5.33-4-8-4"/></svg>
                                    </button>
                                <?php else: ?>
                                    <span class="action-disabled" title="Cet utilisateur est déjà géré par <?php echo esc_attr($user->admin_name); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M6 20V10h12v1c.7 0 1.37.1 2 .29V10c0-1.1-.9-2-2-2h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h6.26c-.42-.6-.75-1.28-.97-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/><path fill="currentColor" d="M18 13c-2.76 0-5 2.24-5 5s2.24 5 5 5s5-2.24 5-5s-2.24-5-5-5m0 2c.83 0 1.5.67 1.5 1.5S18.83 18 18 18s-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5m0 6c-1.03 0-1.94-.52-2.48-1.32c.73-.42 1.57-.68 2.48-.68s1.75.26 2.48.68c-.54.8-1.45 1.32-2.48 1.32"/></svg>
                                    </span>
                                <?php endif; ?>

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