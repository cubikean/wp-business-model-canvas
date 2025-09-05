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

<div class="wrap">
    <h1>WP Business Model Canvas - Administration</h1>
    
    <?php if (isset($message)): ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    
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