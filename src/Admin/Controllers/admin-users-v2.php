<?php
/**
 * Gestion des utilisateurs pour WP Business Model Canvas v2.0
 * Interface d'administration pour créer et gérer les utilisateurs
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

// Obtenir tous les utilisateurs
$all_users = WP_BMC_Database::get_all_users();
?>

<div class="wrap wp-bmc-admin-users">
    <h1>👥 Gestion des Utilisateurs - Business Model Canvas v2.0</h1>
    
    <!-- Statistiques -->
    <div class="wp-bmc-stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Utilisateurs</h3>
                <div class="stat-number"><?php echo count($all_users); ?></div>
                <p>Utilisateurs actifs</p>
            </div>
            
            <div class="stat-card">
                <h3>Projets</h3>
                <div class="stat-number"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bmc_projects"); ?></div>
                <p>Projets créés</p>
            </div>
        </div>
    </div>
    
    <!-- Création d'un nouvel utilisateur -->
    <div class="wp-bmc-section">
        <h2>➕ Créer un nouvel utilisateur</h2>
        <form id="create-user-form" class="wp-bmc-form">
            <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="user_custom_id">ID personnalisé *</label>
                    <input type="text" id="user_custom_id" name="custom_id" class="regular-text" required>
                    <small class="description">Identifiant unique pour cet utilisateur (ex: ETU001, EMP123)</small>
                </div>
                
                <div class="form-group">
                    <label for="user_email">Email *</label>
                    <input type="email" id="user_email" name="email" class="regular-text" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="user_first_name">Prénom *</label>
                    <input type="text" id="user_first_name" name="first_name" class="regular-text" required>
                </div>
                
                <div class="form-group">
                    <label for="user_last_name">Nom *</label>
                    <input type="text" id="user_last_name" name="last_name" class="regular-text" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="user_company">Entreprise</label>
                <input type="text" id="user_company" name="company" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="user_password">Mot de passe *</label>
                <input type="password" id="user_password" name="password" class="regular-text" required>
                <small class="description">Le mot de passe sera envoyé à l'utilisateur par email</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-user-plus"></i> Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>
    
    <!-- Liste des utilisateurs -->
    <div class="wp-bmc-section">
        <h2>👥 Utilisateurs existants</h2>
        
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
        
        <?php if (empty($all_users)): ?>
            <p>Aucun utilisateur n'a été créé.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped" id="users-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="custom_id">
                            ID personnalisé <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="name">
                            Nom <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="email">
                            Email <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="company">
                            Entreprise <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="created_at">
                            Créé le <span class="sort-indicator"></span>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr class="user-row" data-user-id="<?php echo $user->user_id; ?>">
                            <td class="user-custom-id">
                                <strong><?php echo esc_html($user->custom_id); ?></strong>
                            </td>
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
                            <td class="user-created">
                                <?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?>
                            </td>
                            <td class="user-actions">
                                <div class="action-buttons">
                                    <button class="button button-small button-primary edit-user-btn" 
                                            data-user-id="<?php echo $user->user_id; ?>"
                                            title="Éditer l'utilisateur">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="button button-small button-secondary view-projects-btn" 
                                            data-user-id="<?php echo $user->user_id; ?>"
                                            title="Voir les projets">
                                        <i class="fas fa-folder"></i>
                                    </button>
                                    
                                    <button class="button button-small button-secondary reset-password-btn" 
                                            data-user-id="<?php echo $user->user_id; ?>"
                                            title="Réinitialiser le mot de passe">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    
                                    <button class="button button-small button-link-delete deactivate-user-btn" 
                                            data-user-id="<?php echo $user->user_id; ?>"
                                            title="Désactiver l'utilisateur">
                                        <i class="fas fa-ban"></i>
                                    </button>
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
        <?php endif; ?>
    </div>
</div>

<style>
.wp-bmc-admin-users {
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
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-group small.description {
    display: block;
    margin-top: 5px;
    color: #666;
    font-style: italic;
}

.form-actions {
    margin-top: 20px;
}

.users-controls {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    align-items: center;
}

.users-search {
    flex: 1;
}

.users-filters {
    flex: 0 0 200px;
}

#users-table {
    margin-top: 20px;
}

#users-table thead th {
    background: #f1f1f1;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #0073aa;
    padding: 12px 8px;
}

#users-table thead th.sortable {
    cursor: pointer;
    position: relative;
}

#users-table thead th.sortable:hover {
    background: #e9ecef;
}

#users-table thead th.sortable.asc::after {
    content: " ▲";
    color: #0073aa;
    position: absolute;
    right: 8px;
}

#users-table thead th.sortable.desc::after {
    content: " ▼";
    color: #0073aa;
    position: absolute;
    right: 8px;
}

#users-table tbody td {
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #e1e1e1;
}

#users-table tbody tr:hover td {
    background: #f9f9f9;
}

.user-custom-id {
    font-family: monospace;
    background: #f0f0f0;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.user-name strong {
    color: #333;
}

.user-email a {
    color: #0073aa;
    text-decoration: none;
}

.user-email a:hover {
    text-decoration: underline;
}

.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-buttons .button {
    padding: 4px 8px;
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-buttons .button i {
    font-size: 12px;
}

.users-pagination {
    margin-top: 20px;
    text-align: center;
}

.pagination-info {
    color: #666;
    font-size: 14px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .users-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .users-filters {
        flex: none;
    }
    
    #users-table {
        font-size: 14px;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Création d'un utilisateur
    $('#create-user-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');
        
        var formData = {
            action: 'wp_bmc_create_user',
            nonce: $form.find('[name="wp_bmc_admin_nonce"]').val(),
            custom_id: $('#user_custom_id').val(),
            email: $('#user_email').val(),
            password: $('#user_password').val(),
            first_name: $('#user_first_name').val(),
            last_name: $('#user_last_name').val(),
            company: $('#user_company').val()
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
            WP_BMC_Toast.error('Erreur lors de la création de l\'utilisateur.');
        }).always(function() {
            $submitBtn.prop('disabled', false).html(originalText);
        });
    });
    
    // Recherche d'utilisateurs
    $('#users-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterUsers(searchTerm);
    });
    
    // Filtrage par statut
    $('#users-filter-status').on('change', function() {
        var status = $(this).val();
        filterUsersByStatus(status);
    });
    
    // Tri des colonnes
    $('.sortable').on('click', function() {
        var column = $(this).data('sort');
        var currentOrder = $(this).hasClass('asc') ? 'desc' : 'asc';
        
        $('.sortable').removeClass('asc desc');
        $(this).addClass(currentOrder);
        
        sortUsersTable(column, currentOrder);
    });
    
    // Actions sur les utilisateurs
    $('.edit-user-btn').on('click', function() {
        var userId = $(this).data('user-id');
        editUser(userId);
    });
    
    $('.view-projects-btn').on('click', function() {
        var userId = $(this).data('user-id');
        viewUserProjects(userId);
    });
    
    $('.reset-password-btn').on('click', function() {
        var userId = $(this).data('user-id');
        resetUserPassword(userId);
    });
    
    $('.deactivate-user-btn').on('click', function() {
        var userId = $(this).data('user-id');
        deactivateUser(userId);
    });
    
    function filterUsers(searchTerm) {
        $('.user-row').each(function() {
            var $row = $(this);
            var customId = $row.find('.user-custom-id').text().toLowerCase();
            var name = $row.find('.user-name').text().toLowerCase();
            var email = $row.find('.user-email').text().toLowerCase();
            var company = $row.find('.user-company').text().toLowerCase();
            
            if (customId.includes(searchTerm) || name.includes(searchTerm) || 
                email.includes(searchTerm) || company.includes(searchTerm)) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateUsersCount();
    }
    
    function filterUsersByStatus(status) {
        // Implémentation du filtrage par statut
        updateUsersCount();
    }
    
    function sortUsersTable(column, order) {
        var $tbody = $('#users-table tbody');
        var $rows = $tbody.find('.user-row').toArray();
        
        $rows.sort(function(a, b) {
            var aVal, bVal;
            
            switch(column) {
                case 'custom_id':
                    aVal = $(a).find('.user-custom-id').text().trim();
                    bVal = $(b).find('.user-custom-id').text().trim();
                    break;
                case 'name':
                    aVal = $(a).find('.user-name').text().trim();
                    bVal = $(b).find('.user-name').text().trim();
                    break;
                case 'email':
                    aVal = $(a).find('.user-email').text().trim();
                    bVal = $(b).find('.user-email').text().trim();
                    break;
                case 'company':
                    aVal = $(a).find('.user-company').text().trim();
                    bVal = $(b).find('.user-company').text().trim();
                    break;
                case 'created_at':
                    aVal = new Date($(a).find('.user-created').text());
                    bVal = new Date($(b).find('.user-created').text());
                    break;
                default:
                    return 0;
            }
            
            if (order === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        $.each($rows, function(index, row) {
            $tbody.append(row);
        });
    }
    
    function updateUsersCount() {
        var visibleCount = $('.user-row:visible').length;
        var totalCount = $('.user-row').length;
        $('#users-count').text(visibleCount + ' utilisateur(s) sur ' + totalCount);
    }
    
    function editUser(userId) {
        // Implémentation de l'édition d'utilisateur
        WP_BMC_Toast.info('Fonctionnalité d\'édition à implémenter');
    }
    
    function viewUserProjects(userId) {
        // Implémentation de la vue des projets de l'utilisateur
        WP_BMC_Toast.info('Fonctionnalité de vue des projets à implémenter');
    }
    
    function resetUserPassword(userId) {
        if (confirm('Êtes-vous sûr de vouloir réinitialiser le mot de passe de cet utilisateur ?')) {
            // Implémentation de la réinitialisation du mot de passe
            WP_BMC_Toast.info('Fonctionnalité de réinitialisation à implémenter');
        }
    }
    
    function deactivateUser(userId) {
        if (confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur ?')) {
            // Implémentation de la désactivation
            WP_BMC_Toast.info('Fonctionnalité de désactivation à implémenter');
        }
    }
});
</script>
