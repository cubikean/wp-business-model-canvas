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

// Obtenir les derniers utilisateurs
$recent_users = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}bmc_users 
    ORDER BY created_at DESC 
    LIMIT 10
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
    
    <!-- Statistiques -->
    <div class="wp-bmc-stats-grid">
        <div class="wp-bmc-stat-card">
            <h3>Utilisateurs</h3>
            <div class="stat-number"><?php echo $users_count; ?></div>
            <p>Comptes créés</p>
        </div>
        
        <div class="wp-bmc-stat-card">
            <h3>Projets</h3>
            <div class="stat-number"><?php echo $projects_count; ?></div>
            <p>Canvas créés</p>
        </div>
        
        <div class="wp-bmc-stat-card">
            <h3>Sections</h3>
            <div class="stat-number"><?php echo $canvas_data_count; ?></div>
            <p>Données sauvegardées</p>
        </div>
    </div>
    
    <!-- Actions d'administration -->
    <div class="wp-bmc-admin-actions">
        <h2>Actions d'administration</h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('wp_bmc_admin_nonce'); ?>
            <input type="hidden" name="action" value="wp_bmc_admin_action">
            
            <div class="action-buttons">
                <button type="submit" name="bmc_action" value="export_data" class="button button-primary">
                    Exporter les données
                </button>
                
                <button type="submit" name="bmc_action" value="clear_cache" class="button button-secondary">
                    Vider le cache
                </button>
            </div>
        </form>
    </div>
    
    <!-- Derniers utilisateurs -->
    <div class="wp-bmc-recent-users">
        <h2>Derniers utilisateurs</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Entreprise</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                    <tr>
                        <td><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
                        <td><?php echo esc_html($user->email); ?></td>
                        <td><?php echo esc_html($user->company); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?></td>
                        <td>
                            <form method="post" action="" style="display: inline;">
                                <?php wp_nonce_field('wp_bmc_admin_nonce'); ?>
                                <input type="hidden" name="action" value="wp_bmc_admin_action">
                                <input type="hidden" name="bmc_action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user->user_id; ?>">
                                <button type="submit" class="button button-small button-link-delete" 
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                    Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Derniers projets -->
    <div class="wp-bmc-recent-projects">
        <h2>Derniers projets</h2>
        
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
                            <a href="<?php echo home_url('/business-model-canvas/?project_id=' . $project->id); ?>" class="button button-small button-primary">
                                Voir le projet
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Informations système -->
    <div class="wp-bmc-system-info">
        <h2>Informations système</h2>
        
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
