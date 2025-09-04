<?php
/**
 * Page d'administration du plugin WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'));
}

// Traitement des actions
if (isset($_POST['action']) && check_admin_referer('wp_bmc_admin_action', 'wp_bmc_admin_nonce')) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'add_document':
            $title = sanitize_text_field($_POST['document_title']);
            $description = sanitize_textarea_field($_POST['document_description']);
            $category = sanitize_text_field($_POST['document_category']);
            
            // Traitement du fichier uploadé
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === 0) {
                $file = $_FILES['document_file'];
                $file_name = sanitize_file_name($file['name']);
                $file_type = $file['type'];
                $file_size = $file['size'];
                
                // Vérifier la taille (max 10MB)
                if ($file_size > 10 * 1024 * 1024) {
                    $error_message = "Le fichier est trop volumineux (max 10MB).";
                    break;
                }
                
                // Vérifier le type de fichier
                $allowed_types = array(
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    'video/mp4', 'video/webm', 'video/ogg',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );
                
                if (!in_array($file_type, $allowed_types)) {
                    $error_message = "Type de fichier non autorisé.";
                    break;
                }
                
                // Créer le dossier d'upload si nécessaire
                $upload_dir = wp_upload_dir();
                $bmc_docs_dir = $upload_dir['basedir'] . '/wp-bmc-documents';
                
                if (!file_exists($bmc_docs_dir)) {
                    wp_mkdir_p($bmc_docs_dir);
                }
                
                // Générer un nom de fichier unique
                $unique_filename = uniqid() . '_' . $file_name;
                $file_path = $bmc_docs_dir . '/' . $unique_filename;
                
                // Déplacer le fichier
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Enregistrer dans la base de données
                    $table = $wpdb->prefix . 'bmc_documents';
                    $result = $wpdb->insert(
                        $table,
                        array(
                            'title' => $title,
                            'description' => $description,
                            'filename' => $unique_filename,
                            'file_type' => $file_type,
                            'file_size' => $file_size,
                            'category' => $category,
                            'is_active' => 1
                        ),
                        array('%s', '%s', '%s', '%s', '%d', '%s', '%d')
                    );
                    
                    if ($result) {
                        $success_message = "Document ajouté avec succès !";
                    } else {
                        $error_message = "Erreur lors de l'enregistrement du document.";
                    }
                } else {
                    $error_message = "Erreur lors de l'upload du fichier.";
                }
            } else {
                $error_message = "Aucun fichier sélectionné.";
            }
            break;
            
        case 'delete_document':
            $document_id = intval($_POST['document_id']);
            
            // Récupérer les informations du document
            $table = $wpdb->prefix . 'bmc_documents';
            $document = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $document_id));
            
            if ($document) {
                // Supprimer le fichier physique
                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['basedir'] . '/wp-bmc-documents/' . $document->filename;
                
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Supprimer de la base de données
                $result = $wpdb->delete($table, array('id' => $document_id), array('%d'));
                
                if ($result) {
                    $success_message = "Document supprimé avec succès !";
                } else {
                    $error_message = "Erreur lors de la suppression du document.";
                }
            } else {
                $error_message = "Document non trouvé.";
            }
            break;
            
        case 'toggle_document_status':
            $document_id = intval($_POST['document_id']);
            $new_status = intval($_POST['new_status']);
            
            $table = $wpdb->prefix . 'bmc_documents';
            $result = $wpdb->update(
                $table,
                array('is_active' => $new_status),
                array('id' => $document_id),
                array('%d'),
                array('%d')
            );
            
            if ($result) {
                $success_message = "Statut du document mis à jour !";
            } else {
                $error_message = "Erreur lors de la mise à jour du statut.";
            }
            break;
    }
}

// Récupérer les documents existants
$table = $wpdb->prefix . 'bmc_documents';
$documents = $wpdb->get_results("SELECT * FROM $table ORDER BY category, title");

// Récupérer les projets pour les statistiques
$projects_table = $wpdb->prefix . 'bmc_projects';
$total_projects = $wpdb->get_var("SELECT COUNT(*) FROM $projects_table");

$users_table = $wpdb->prefix . 'bmc_users';
$total_users = $wpdb->get_var("SELECT COUNT(*) FROM $users_table");

// Catégories disponibles
$categories = array(
    'all' => 'Toutes les briques',
    'key_partners' => 'Partenaires clés',
    'key_activities' => 'Activités clés',
    'key_resources' => 'Ressources clés',
    'value_proposition' => 'Proposition de valeur',
    'customer_relationships' => 'Relations clients',
    'channels' => 'Canaux',
    'customer_segments' => 'Segments clients',
    'cost_structure' => 'Structure des coûts',
    'revenue_streams' => 'Sources de revenus'
);
?>

<div class="wrap">
    <h1>WP Business Model Canvas - Administration</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="wp-bmc-stats">
        <div class="stat-card">
            <h3>Utilisateurs</h3>
            <div class="stat-number"><?php echo $total_users; ?></div>
        </div>
        <div class="stat-card">
            <h3>Projets</h3>
            <div class="stat-number"><?php echo $total_projects; ?></div>
        </div>
        <div class="stat-card">
            <h3>Documents</h3>
            <div class="stat-number"><?php echo count($documents); ?></div>
        </div>
    </div>
    
    <!-- Gestion des documents de référence -->
    <div class="wp-bmc-section">
        <h2>Documents de référence</h2>
        <p>Gérez les documents que les utilisateurs peuvent consulter dans leurs popups d'édition.</p>
        
        <!-- Formulaire d'ajout de document -->
        <div class="wp-bmc-add-document">
            <h3>Ajouter un nouveau document</h3>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('wp_bmc_admin_action', 'wp_bmc_admin_nonce'); ?>
                <input type="hidden" name="action" value="add_document">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="document_title">Titre du document</label>
                        </th>
                        <td>
                            <input type="text" id="document_title" name="document_title" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_description">Description</label>
                        </th>
                        <td>
                            <textarea id="document_description" name="document_description" rows="3" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_category">Catégorie</label>
                        </th>
                        <td>
                            <select id="document_category" name="document_category" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <strong>"Toutes les briques"</strong> : Le document sera disponible dans toutes les popups d'édition.<br>
                                <strong>Brique spécifique</strong> : Le document ne sera disponible que dans la popup de cette brique.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="document_file">Fichier</label>
                        </th>
                        <td>
                            <input type="file" id="document_file" name="document_file" required>
                            <p class="description">
                                Types autorisés : Images (JPG, PNG, GIF, WebP), Vidéos (MP4, WebM, OGG), 
                                Documents (PDF, DOC, DOCX, XLS, XLSX). Taille max : 10MB.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Ajouter le document">
                </p>
            </form>
        </div>
        
        <!-- Liste des documents -->
        <div class="wp-bmc-documents-list">
            <h3>Documents existants</h3>
            
            <?php if (empty($documents)): ?>
                <p>Aucun document de référence n'a été ajouté.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Description</th>
                            <th>Catégorie</th>
                            <th>Fichier</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($document->title); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($document->description); ?>
                                </td>
                                <td>
                                    <span class="category-badge category-<?php echo esc_attr($document->category); ?>">
                                        <?php echo esc_html($categories[$document->category] ?? $document->category); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="file-info">
                                        <span class="file-icon">
                                            <i class="fas fa-<?php echo get_file_icon($document->file_type); ?>"></i>
                                        </span>
                                        <span class="file-name"><?php echo esc_html($document->filename); ?></span>
                                        <span class="file-size">(<?php echo format_file_size($document->file_size); ?>)</span>
                                    </div>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('wp_bmc_admin_action', 'wp_bmc_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="toggle_document_status">
                                        <input type="hidden" name="document_id" value="<?php echo $document->id; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $document->is_active ? 0 : 1; ?>">
                                        
                                        <button type="submit" class="button button-small <?php echo $document->is_active ? 'button-secondary' : 'button-primary'; ?>">
                                            <?php echo $document->is_active ? 'Désactiver' : 'Activer'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce document ?');">
                                        <?php wp_nonce_field('wp_bmc_admin_action', 'wp_bmc_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="delete_document">
                                        <input type="hidden" name="document_id" value="<?php echo $document->id; ?>">
                                        
                                        <button type="submit" class="button button-small button-link-delete">
                                            Supprimer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Liste des projets -->
    <div class="wp-bmc-section">
        <h2>Projets des utilisateurs</h2>
        
        <?php
        $projects = $wpdb->get_results("
            SELECT p.*, u.first_name, u.last_name, u.company 
            FROM $projects_table p 
            LEFT JOIN $users_table u ON p.user_id = u.user_id 
            ORDER BY p.created_at DESC
        ");
        ?>
        
        <?php if (empty($projects)): ?>
            <p>Aucun projet n'a été créé.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Entreprise</th>
                        <th>Projet</th>
                        <th>Description</th>
                        <th>Statut</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($project->first_name . ' ' . $project->last_name); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($project->company); ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($project->title); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($project->description); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($project->status); ?>">
                                    <?php echo esc_html(ucfirst($project->status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($project->created_at)); ?>
                            </td>
                            <td>
                                <a href="<?php echo home_url('/business-model-canvas/?project_id=' . $project->id); ?>" 
                                   class="button button-small" target="_blank">
                                    Voir le projet
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.wp-bmc-stats {
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

.wp-bmc-add-document {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.wp-bmc-add-document h3 {
    margin-top: 0;
    color: #333;
}

.category-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.category-all {
    background: #e3f2fd;
    color: #1976d2;
}

.category-key_partners {
    background: #e3f2fd;
    color: #1976d2;
}

.category-key_activities {
    background: #f3e5f5;
    color: #7b1fa2;
}

.category-key_resources {
    background: #e8f5e8;
    color: #388e3c;
}

.category-value_proposition {
    background: #fff3e0;
    color: #f57c00;
}

.category-customer_relationships {
    background: #fce4ec;
    color: #c2185b;
}

.category-channels {
    background: #f1f8e9;
    color: #689f38;
}

.category-customer_segments {
    background: #e0f2f1;
    color: #00796b;
}

.category-cost_structure {
    background: #fafafa;
    color: #424242;
}

.category-revenue_streams {
    background: #fff8e1;
    color: #f57f17;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-published {
    background: #d4edda;
    color: #155724;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.file-icon {
    color: #0073aa;
    font-size: 16px;
}

.file-name {
    font-weight: 600;
}

.file-size {
    color: #666;
    font-size: 12px;
}

.wp-list-table th {
    font-weight: 600;
    color: #333;
}

.wp-list-table td {
    vertical-align: middle;
}
</style>

<?php
// Fonctions utilitaires
function get_file_icon($file_type) {
    if (strpos($file_type, 'image/') === 0) return 'image';
    if (strpos($file_type, 'video/') === 0) return 'video';
    if (strpos($file_type, 'pdf') !== false) return 'file-pdf';
    if (strpos($file_type, 'word') !== false || strpos($file_type, 'document') !== false) return 'file-word';
    if (strpos($file_type, 'excel') !== false || strpos($file_type, 'spreadsheet') !== false) return 'file-excel';
    return 'file';
}

function format_file_size($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
