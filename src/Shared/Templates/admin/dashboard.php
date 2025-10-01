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

// Obtenir tous les projets avec leurs utilisateurs associés
$current_admin_id = get_current_user_id();
$all_projects = $wpdb->get_results("
    SELECT p.id as project_id,
           p.title as project_name,
           p.description as project_description,
           p.created_at as project_created_at,
           COUNT(DISTINCT gr.id) as total_grading_requests_count,
           SUM(CASE WHEN gr.status = 'pending' THEN 1 ELSE 0 END) as pending_grading_requests_count,
           MAX(gr.created_at) as last_grading_request_date,
           GROUP_CONCAT(DISTINCT gr.status) as grading_statuses,
           COALESCE((
               SELECT SUM(r2.rating)
               FROM {$wpdb->prefix}bmc_ratings r2
               WHERE r2.project_id = p.id
           ), 0) as sum_rating_bricks,
           GROUP_CONCAT(DISTINCT CONCAT(u.user_id, ':', u.first_name, ' ', u.last_name, ':', 
               CASE WHEN as_rel.admin_id IS NOT NULL THEN 1 ELSE 0 END) SEPARATOR '||') as users_info
    FROM {$wpdb->prefix}bmc_projects p
    LEFT JOIN {$wpdb->prefix}bmc_project_users pu ON p.id = pu.project_id AND pu.is_active = 1
    LEFT JOIN {$wpdb->prefix}bmc_users u ON pu.user_id = u.user_id
    LEFT JOIN {$wpdb->prefix}bmc_grading_requests gr ON p.id = gr.project_id
    LEFT JOIN {$wpdb->prefix}bmc_admin_students as_rel ON u.user_id = as_rel.student_id AND as_rel.admin_id = $current_admin_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

// Traiter les informations des utilisateurs pour chaque projet
foreach ($all_projects as $project) {
    $users_list = array();
    $is_my_student = 0;
    
    if (!empty($project->users_info)) {
        $users_data = explode('||', $project->users_info);
        foreach ($users_data as $user_data) {
            if (!empty($user_data)) {
                list($user_id, $user_name, $is_student) = explode(':', $user_data);
                $users_list[] = array(
                    'user_id' => $user_id,
                    'name' => $user_name,
                    'is_my_student' => (int)$is_student
                );
                if ((int)$is_student) {
                    $is_my_student = 1;
                }
            }
        }
    }
    
    $project->users = $users_list;
    $project->is_my_student = $is_my_student;
    
    // Obtenir l'admin du premier utilisateur du projet (si géré)
    if (!empty($users_list)) {
        $first_user_id = $users_list[0]['user_id'];
        $admin_info = $wpdb->get_row($wpdb->prepare(
            "SELECT wp_user.display_name, wp_user.user_login 
             FROM {$wpdb->prefix}bmc_admin_students as_rel
             JOIN {$wpdb->prefix}users wp_user ON as_rel.admin_id = wp_user.ID
             WHERE as_rel.student_id = %d
             LIMIT 1",
            $first_user_id
        ));
        
        $project->admin_name = $admin_info ? $admin_info->display_name : null;
    } else {
        $project->admin_name = null;
    }
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

    <!-- Liste complète des projets -->
    <div class="wp-bmc-all-users">
        <h2>Projets</h2>

        <div class="wp-bmc-users-controls">
            <div class="users-search">
                <input type="text" id="users-search" placeholder="Rechercher un projet..." class="regular-text">
            </div>
            <div class="users-filters">
                <select id="users-filter-group">
                    <option value="">Tous les projets</option>
                    <option value="my-students">Mes étudiants</option>
                    <option value="managed-students">Projets gérés</option>
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
                        PROJET <span class="sort-indicator"></span>
                    </th>
                    <th class="sortable" data-sort="users">
                        UTILISATEURS <span class="sort-indicator"></span>
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
                <?php foreach ($all_projects as $project): ?>
                    <tr class="user-row" data-project-id="<?php echo $project->project_id; ?>">
                        <td class="user-name-container">
                            <div class="project-info">
                                <div class="project-title">
                                    <strong><?php echo esc_html($project->project_name); ?></strong>
                                </div>
                                <?php if (!empty($project->project_description)): ?>
                                    <div class="project-description">
                                        <?php echo esc_html(wp_trim_words($project->project_description, 10)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="project-users">
                            <div class="users-list">
                                <?php if (!empty($project->users)): ?>
                                    <?php foreach ($project->users as $user): ?>
                                        <div class="user-badge">
                                            <!-- <div class="user-avatar-small">
                                                <?php
                                                $name_parts = explode(' ', $user['name']);
                                                $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
                                                echo esc_html($initials);
                                                ?>
                                            </div> -->
                                            <span><?php echo esc_html($user['name']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="no-users">Aucun utilisateur</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="user-grading-status">
                            <?php
                            $grading_statuses = $project->grading_statuses ? explode(',', $project->grading_statuses) : array();
                            $total_grading_requests_count = intval($project->total_grading_requests_count);
                            $pending_grading_requests_count = intval($project->pending_grading_requests_count);

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

                                <?php if ($project->last_grading_request_date): ?>
                                    <div class="grading-date">
                                        Dernière demande : <?php echo date('d/m/Y', strtotime($project->last_grading_request_date)); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        
                        <td class="user-project-advancement">
                            <?php
                            $rating_value = intval($project->sum_rating_bricks);
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
                            <?php if ($project->admin_name && !$project->is_my_student): ?>
                                <span class="group-status managed-student">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4" />
                                    </svg>
                                    <span>Géré par <?php echo esc_html($project->admin_name); ?></span>
                                </span>
                            <?php elseif ($project->is_my_student): ?>
                                <span class="group-status managed-student">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4" />
                                    </svg>
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
                                <button class="button action-button button-small button-primary view-project-btn"
                                    data-project-id="<?php echo $project->project_id; ?>"
                                    title="Voir le projet">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3" />
                                    </svg>
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
                <span id="users-count"><?php echo count($all_projects); ?> projet(s) au total</span>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le template d'édition pour l'admin
wp_bmc_include_edit_section('admin');
?>