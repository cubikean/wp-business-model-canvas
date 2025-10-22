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

// Obtenir tous les superviseurs (administrateurs WordPress)
$supervisors = get_users(array(
    'role' => 'administrator',
    'orderby' => 'registered',
    'order' => 'DESC'
));
?>

<div class="wrap wp-bmc-admin-users">
    <h1>Gestion des Utilisateurs</h1>

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

    <!-- Import CSV -->
    <!-- <div class="wp-bmc-section">
        <h2>📁 Importer des utilisateurs via CSV</h2>
        <p class="description">Importez plusieurs utilisateurs en une seule fois depuis un fichier CSV. Le fichier doit contenir les colonnes : <strong>Prénom</strong>, <strong>Nom</strong>, <strong>E-mail</strong>, <strong>Candidature</strong>.</p>
        
        <div class="csv-import-container">
            <form id="import-csv-form" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
                
                <div class="csv-upload-area">
                    <input type="file" id="csv-file" name="csv_file" accept=".csv" required>
                    <label for="csv-file" class="csv-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choisir un fichier CSV</span>
                        <small>ou glisser-déposer ici</small>
                    </label>
                    <div class="csv-file-name" style="display: none;"></div>
                </div>

                <div class="csv-info-box">
                    <h4>📋 Format attendu du CSV :</h4>
                    <ul>
                        <li><strong>Prénom</strong> : Prénom de l'utilisateur</li>
                        <li><strong>Nom</strong> : Nom de l'utilisateur</li>
                        <li><strong>E-mail</strong> : Adresse email (doit être unique)</li>
                        <li><strong>Candidature</strong> : ID personnalisé (doit être unique)</li>
                    </ul>
                    <p><small><strong>Note :</strong> Le mot de passe sera automatiquement généré : <code>Candidature + Prénom</code></small></p>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="send-emails-users" checked>
                        <strong>Envoyer les emails d'identifiants aux utilisateurs</strong>
                    </label>
                    <p class="description">⚠️ Pour les imports volumineux (&gt;50), décochez pour éviter les timeouts et limites serveur.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <i class="fas fa-upload"></i> Importer les utilisateurs
                    </button>
                </div>
            </form>

            <div id="csv-import-results" style="display: none;">
                <h3>Résultats de l'import</h3>
                <div class="import-stats"></div>
                <div class="import-details"></div>
            </div>
        </div>
    </div> -->

    <!-- Création d'un nouvel utilisateur -->
    <div class="wp-bmc-section">
        <h2>➕ Créer un utilisateur individuel</h2>
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
                <label for="user_password">Mot de passe *</label>
                <input type="password" id="user_password" name="password" class="regular-text" required>
                <small class="description">Le mot de passe sera envoyé à l'utilisateur par email</small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="send-email-user" name="send_email" value="1" checked>
                    <strong>Envoyer l'email d'identifiants à l'utilisateur</strong>
                </label>
                <p class="description">Si décoché, l'utilisateur devra être informé manuellement de ses identifiants.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-user-plus"></i> Créer l'utilisateur
                </button>
            </div>
        </form>
    </div>

    <!-- Import CSV Superviseurs -->
    <!-- <div class="wp-bmc-section">
        <h2>📁 Importer des superviseurs via CSV</h2>
        <p class="description">Importez plusieurs superviseurs en une seule fois depuis un fichier CSV. Le fichier doit contenir les colonnes : <strong>Tuteur</strong>, <strong>Coordonnées du tuteur</strong>.</p>
        
        <div class="csv-import-container">
            <form id="import-supervisors-csv-form" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>
                
                <div class="csv-upload-area">
                    <input type="file" id="csv-supervisors-file" name="csv_file" accept=".csv" required>
                    <label for="csv-supervisors-file" class="csv-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Choisir un fichier CSV</span>
                        <small>ou glisser-déposer ici</small>
                    </label>
                    <div class="csv-supervisors-file-name" style="display: none;"></div>
                </div>

                <div class="csv-info-box">
                    <h4>📋 Format attendu du CSV :</h4>
                    <ul>
                        <li><strong>Tuteur</strong> : Nom complet du superviseur (Ex: "Jean Dupont")</li>
                        <li><strong>Coordonnées du tuteur</strong> : Adresse email (doit être unique)</li>
                    </ul>
                    <p><small><strong>Note :</strong> Le mot de passe sera automatiquement généré : <code>Prénom + 6 caractères aléatoires</code></small></p>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="send-emails-supervisors" checked>
                        <strong>Envoyer les emails d'identifiants aux superviseurs</strong>
                    </label>
                    <p class="description">⚠️ Pour les imports volumineux (&gt;50), décochez pour éviter les timeouts et limites serveur.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <i class="fas fa-upload"></i> Importer les superviseurs
                    </button>
                </div>
            </form>

            <div id="csv-supervisors-import-results" style="display: none;">
                <h3>Résultats de l'import</h3>
                <div class="import-stats"></div>
                <div class="import-details"></div>
            </div>
        </div>
    </div> -->

    <!-- Création d'un superviseur (admin) -->
    <div class="wp-bmc-section">
        <h2>👨‍💼 Créer un superviseur individuel</h2>
        <p class="description">Les superviseurs sont des administrateurs qui peuvent gérer les projets et les utilisateurs.</p>
        <form id="create-supervisor-form" class="wp-bmc-form">
            <?php wp_nonce_field('wp_bmc_admin_nonce', 'wp_bmc_admin_nonce'); ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="supervisor_first_name">Prénom *</label>
                    <input type="text" id="supervisor_first_name" name="first_name" class="regular-text" required>
                </div>

                <div class="form-group">
                    <label for="supervisor_last_name">Nom *</label>
                    <input type="text" id="supervisor_last_name" name="last_name" class="regular-text" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="supervisor_email">Email *</label>
                    <input type="email" id="supervisor_email" name="email" class="regular-text" required>
                </div>

                <div class="form-group">
                    <label for="supervisor_password">Mot de passe *</label>
                    <input type="password" id="supervisor_password" name="password" class="regular-text" required>
                    <small class="description">Minimum 8 caractères recommandés</small>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="send-email-supervisor" name="send_email" value="1" checked>
                    <strong>Envoyer l'email d'identifiants au superviseur</strong>
                </label>
                <p class="description">Si décoché, le superviseur devra être informé manuellement de ses identifiants.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-user-shield"></i> Créer le superviseur
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
                    <option value="status-active">Actifs</option>
                    <option value="status-pending">En attente</option>
                    <option value="status-disabled">Désactivé</option>
                </select>
            </div>
        </div>

        <?php if (empty($all_users)): ?>
            <p>Aucun utilisateur n'a été créé.</p>
        <?php else: ?>
            <table class="wp-list-table widefat striped" id="users-table">
                <thead>
                    <tr>
                       
                        <th class="sortable" data-sort="name">
                            Nom <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="email">
                            Email <span class="sort-indicator"></span>
                        </th>

                        <th class="sortable" data-sort="project">
                            Projet <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="status">
                            Statut <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="created_at">
                            Créé en <span class="sort-indicator"></span>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr class="user-row" data-user-id="<?php echo $user->user_id; ?>">
                            <td class="user-name">
                                <strong><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></strong>
                                <small><?php echo esc_html($user->custom_id); ?></small>
                            </td>
                            <td class="user-email">
                                <a href="mailto:<?php echo esc_attr($user->email); ?>">
                                    <?php echo esc_html($user->email); ?>
                                </a>
                            </td>

                            <td class="user-project">
                            <?php $user_projects = WP_BMC_Database::get_user_projects($user->user_id); ?>
                            <?php if (empty($user_projects)): ?>
                                <span class="no-project">Aucun projet assigné</span>
                            <?php else: ?>
                                <?php foreach ($user_projects as $project): ?>
                                    <?php 
                                    // Vérifier que l'objet project existe et a une propriété title
                                    $project_title = isset($project->title) ? $project->title : (isset($project->project_name) ? $project->project_name : 'Projet sans nom');
                                    ?>
                                    <a class="project-name" href="<?php echo home_url('/business-model-canvas/?admin_view=true&project_id=' . $project->id) . '&view=global'; ?>"><strong><?php echo esc_html($project_title); ?></strong></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </td>
                            <td class="user-status">
                                <span class="status-badge status-<?php echo esc_attr($user->status); ?>">
                                    <?php 
                                    $status_labels = array(
                                        'active' => 'Actif',
                                        'pending' => 'En attente',
                                        'disabled' => 'Désactivé'
                                    );
                                    echo $status_labels[$user->status] ?? ucfirst($user->status);
                                    ?>
                                </span>
                            </td>
                            <td class="user-created">
                                <?php echo date('Y', strtotime($user->created_at)); ?>
                            </td>
                            <td class="user-actions">
                                <div class="action-buttons">
                                    <button class="button button-small button-secondary reset-password-btn"
                                        data-user-id="<?php echo $user->id; ?>"
                                        title="Réinitialiser le mot de passe">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    <div class="status-actions">
                                        <?php if ($user->status === 'active'): ?>
                                            <button class="button button-small button-secondary disable-user-btn"
                                                data-user-id="<?php echo $user->user_id; ?>"
                                                title="Désactiver le compte">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php elseif ($user->status === 'disabled'): ?>
                                            <button class="button button-small button-primary enable-user-btn"
                                                data-user-id="<?php echo $user->user_id; ?>"
                                                title="Activer le compte">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php elseif ($user->status === 'pending'): ?>
                                            <button class="button button-small button-primary activate-user-btn"
                                                data-user-id="<?php echo $user->user_id; ?>"
                                                title="Activer manuellement">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <button class="button button-small button-link-delete deactivate-user-btn"
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        title="Désactiver l'utilisateur">
                                        <i class="fas fa-ban"></i>
                                    </button>

                                    <button class="button button-small button-link-delete delete-user-btn"
                                        data-user-id="<?php echo $user->user_id; ?>"
                                        title="Supprimer l'utilisateur">
                                        <i class="fas fa-trash"></i>
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

    <!-- Liste des superviseurs -->
    <div class="wp-bmc-section">
        <h2>👨‍💼 Superviseurs (Administrateurs)</h2>
        <p class="description">Liste de tous les superviseurs ayant accès à l'administration de la plateforme.</p>

        <div class="users-controls">
            <div class="users-search">
                <input type="text" id="supervisors-search" placeholder="Rechercher un superviseur..." class="regular-text">
            </div>
        </div>

        <?php if (empty($supervisors)): ?>
            <p>Aucun superviseur n'a été trouvé.</p>
        <?php else: ?>
            <table class="wp-list-table widefat striped" id="supervisors-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="name">
                            Nom <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="email">
                            Email <span class="sort-indicator"></span>
                        </th>
                       
                        <th class="sortable" data-sort="projects">
                            Projets supervisés <span class="sort-indicator"></span>
                        </th>
                        <th class="sortable" data-sort="registered">
                            Créé le <span class="sort-indicator"></span>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supervisors as $supervisor): ?>
                        <?php 
                        $supervisor_projects = WP_BMC_Database::get_supervisor_projects($supervisor->ID);
                        $projects_count = count($supervisor_projects);
                        ?>
                        <tr class="supervisor-row" data-user-id="<?php echo $supervisor->ID; ?>">
                            <td class="supervisor-name">
                                <strong>
                                    <?php 
                                    $display_name = $supervisor->display_name ?: $supervisor->first_name . ' ' . $supervisor->last_name;
                                    echo esc_html($display_name); 
                                    ?>
                                </strong>

                            </td>
                            <td class="supervisor-email">
                                <a href="mailto:<?php echo esc_attr($supervisor->user_email); ?>">
                                    <?php echo esc_html($supervisor->user_email); ?>
                                </a>
                            </td>
                           
                            <td class="supervisor-projects">
                                <?php if ($projects_count > 0): ?>
                                    <strong><?php echo $projects_count; ?></strong> projet<?php echo $projects_count > 1 ? 's' : ''; ?>
                                    <button class="button button-small view-supervisor-projects" 
                                            data-supervisor-id="<?php echo $supervisor->ID; ?>"
                                            title="Voir les projets">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="no-projects">Aucun projet</span>
                                <?php endif; ?>
                            </td>
                            <td class="supervisor-registered">
                                <?php echo date('d/m/Y', strtotime($supervisor->user_registered)); ?>
                            </td>
                            <td class="supervisor-actions">
                                <div class="action-buttons">
                                    <button class="button button-small button-secondary view-supervisor-profile"
                                        data-supervisor-id="<?php echo $supervisor->ID; ?>"
                                        title="Voir le profil">
                                        <i class="fas fa-user"></i>
                                    </button>

                                    <button class="button button-small button-secondary reset-supervisor-password"
                                        data-supervisor-id="<?php echo $supervisor->ID; ?>"
                                        title="Réinitialiser le mot de passe">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    <?php 
                                    // Ne pas permettre la suppression du superviseur connecté
                                    $current_user_id = get_current_user_id();
                                    if ($supervisor->ID != $current_user_id): 
                                    ?>
                                        <button class="button button-small button-link-delete delete-supervisor-btn"
                                            data-supervisor-id="<?php echo $supervisor->ID; ?>"
                                            data-supervisor-name="<?php echo esc_attr($display_name); ?>"
                                            title="Supprimer le superviseur">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="current-user-badge" title="Vous ne pouvez pas vous supprimer vous-même">
                                            <i class="fas fa-user-shield"></i> Vous
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="users-pagination">
                <div class="pagination-info">
                    <span id="supervisors-count"><?php echo count($supervisors); ?> superviseur(s) au total</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>