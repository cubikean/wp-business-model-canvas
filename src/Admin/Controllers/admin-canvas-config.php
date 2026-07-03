<?php
/**
 * Contrôleur pour la configuration des sections du canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    wp_die('Accès non autorisé.');
}

// Traitement du formulaire
if (isset($_POST['save_canvas_configs']) && wp_verify_nonce($_POST['wp_bmc_admin_nonce'], 'wp_bmc_admin_nonce')) {
    // Le traitement sera fait via AJAX
}

// Charger TinyMCE et les scripts nécessaires
if (!wp_script_is('wp-bmc-tinymce', 'registered')) {
    wp_register_script(
        'wp-bmc-tinymce',
        'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js',
        array(),
        '6.8.3',
        true
    );
}

wp_enqueue_script('wp-bmc-tinymce');

// Inclure les assets nécessaires
wp_enqueue_script('wp-bmc-admin-canvas-config', plugin_dir_url(__FILE__) . '../Assets/js/admin-canvas-config.js', array('jquery', 'wp-bmc-tinymce'), '1.0.0', true);
wp_localize_script('wp-bmc-admin-canvas-config', 'wp_bmc_admin_ajax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
));

?>

<div class="wrap">
    <h1>Configuration du Canvas</h1>
    
    <div class="wp-bmc-admin-container">
        <div class="canvas-config-header">
            <p>Personnalisez les titres et placeholders des sections du passeport de l'entrepreneuriat.</p>
        </div>
        
        <form id="canvas-config-form">
            <div class="canvas-config-sections">
                <!-- Les sections seront chargées via JavaScript -->
                <div class="loading-message">
                    <i class="fas fa-spinner fa-spin"></i> Chargement des configurations...
                </div>
            </div>
            
            <div class="canvas-config-actions">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-save"></i> Sauvegarder les configurations
                </button>
                <button type="button" id="reset-defaults" class="button button-secondary">
                    <i class="fas fa-undo"></i> Restaurer les valeurs par défaut
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.wp-bmc-admin-container {
    max-width: 1200px;
    margin: 20px 0;
}

.canvas-config-header {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 4px solid #0073aa;
}

.canvas-config-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.canvas-section-config {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-config-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.section-config-key {
    font-size: 12px;
    color: #666;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
}

.config-field {
    margin-bottom: 15px;
}

.config-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.config-field input,
.config-field textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.config-field input:focus,
.config-field textarea:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
}

.config-field textarea {
    resize: vertical;
    min-height: 60px;
}

/* Styles pour TinyMCE */
.config-field .mce-tinymce {
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
}

.config-field .mce-edit-area {
    border: none !important;
}

.default-value {
    font-size: 12px;
    color: #666;
    font-style: italic;
    margin-top: 5px;
}

.canvas-config-actions {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-top: 1px solid #ddd;
}

.canvas-config-actions .button {
    margin: 0 10px;
    padding: 10px 20px;
    font-size: 14px;
}

.loading-message {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .canvas-config-sections {
        grid-template-columns: 1fr;
    }
    
    .canvas-config-actions .button {
        display: block;
        width: 100%;
        margin: 10px 0;
    }
}
</style>
