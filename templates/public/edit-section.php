<?php
/**
 * Template pour la section d'édition des briques
 * Réutilisable dans le dashboard public et admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Vue d'édition des briques (remplace le contenu principal) -->
<div id="wp-bmc-edit-view" class="wp-bmc-edit-view" style="display: none;">
    <div class="edit-header">
        <button class="back-to-dashboard-btn" id="back-to-dashboard">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </button>
        <h2 id="edit-section-title">Éditer la brique</h2>
    </div>
    
    <div class="edit-content">
        <!-- Section éditeur -->
        <div class="editor-section">
            <label for="wysiwyg-editor">Contenu de la brique</label>
            <div id="wysiwyg-editor">
                <!-- L'éditeur sera initialisé par JavaScript -->
            </div>
        </div>
        
        <!-- Section fichiers -->
        <div class="files-section">
            <div class="files-header">
                <h4>Fichiers attachés</h4>
                <button type="button" class="add-file-btn" id="add-file-btn">
                    <i class="fas fa-plus"></i> Ajouter des fichiers
                </button>
            </div>
            <div class="files-list" id="files-list">
                <div class="no-files">Aucun fichier attaché</div>
            </div>
            <input type="file" id="file-input" multiple style="display: none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
        </div>
        
        <!-- Section documents de référence -->
        <div class="documents-section">
            <div class="documents-header">
                <h4>Documents de référence</h4>
                <button type="button" class="view-documents-btn" id="view-documents-btn">
                    <i class="fas fa-eye"></i> Consulter les documents
                </button>
            </div>
            <div class="documents-list" id="documents-list">
                <div class="no-documents">Aucun document disponible</div>
            </div>
        </div>
        
        <!-- Actions d'édition -->
        <div class="edit-actions">
            <button type="button" class="wp-bmc-btn wp-bmc-btn-secondary" id="edit-cancel">Annuler</button>
            <button type="button" class="wp-bmc-btn wp-bmc-btn-primary" id="edit-save">Sauvegarder</button>
        </div>
    </div>
</div>

<!-- Popup des documents de référence -->
<div id="wp-bmc-documents-popup" class="wp-bmc-popup">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <div class="popup-header">
            <h3>Documents de référence</h3>
            <button class="popup-close" id="documents-popup-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="popup-body">
            <div class="documents-grid" id="documents-grid">
                <!-- Les documents seront chargés dynamiquement -->
            </div>
        </div>
        
        <div class="popup-footer">
            <button type="button" class="popup-btn popup-btn-secondary" id="documents-popup-close">Fermer</button>
        </div>
    </div>
</div>
