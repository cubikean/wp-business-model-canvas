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
<div id="wp-bmc-edit-view" class="wp-bmc-edit-view" style="display: none;" data-section="">
    <div class="edit-view-container">
        <div class="edit-header">
            <button class="back-to-dashboard-btn" id="back-to-dashboard">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h2 id="edit-section-title">Éditer la brique</h2>
        </div>

        <div class="edit-content">
            <!-- Section éditeur -->
            <div class="sub-section">
                <p>Placeholder pour le contenu de la brique</p>
                <button type="button" class="view-documents-btn btn-outline --icon" id="view-documents-btn">
                <i class="fa fa-file-lines"></i>Ressources pédagogiques
                </button>
            </div>
            <div class="editor-section">
                <div id="wysiwyg-editor">
                    <!-- L'éditeur sera initialisé par JavaScript -->
                </div>
            </div>

            <!-- Section fichiers -->
            <div class="files-section">
                <div class="files-header">
                    <button type="button" class="add-file-btn btn-outline --icon --icon-bg" id="add-file-btn">
                        <i class="fas fa-plus"></i> Ajouter des documents
                    </button>
                </div>
                <div class="files-list" id="files-list">
                    <div class="no-files">Aucun fichier attaché</div>
                </div>
                <input type="file" id="file-input" multiple style="display: none;" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx">
            </div>
        </div>


        <!-- Actions d'édition -->
        <div class="edit-actions">
            <button type="button" class="wp-bmc-btn wp-bmc-btn-secondary btn-outline --icon" id="edit-cancel">Annuler</button>
            <button type="button" class="wp-bmc-btn wp-bmc-btn-primary btn-solid" id="edit-save">Sauvegarder</button>
            <button type="button" class="wp-bmc-btn wp-bmc-btn-primary btn-solid" id="request-grading">Demander une notation</button>
        </div>
    </div>

    <div class="history-section">
        <header>
            <h4 id="revisions-section-title">Révisions de cette brique</h4>
            <button type="button" class="btn-outline --icon --small" id="load-revisions-btn">
                <i class="fas fa-history"></i> Charger les révisions
            </button>
        </header>
        <div class="revisions-list" id="revisions-list">
            <div class="no-revisions">
                <i class="fas fa-history"></i>
                <p>Aucune révision disponible pour cette brique</p>
                <small>Les révisions sont créées automatiquement lors des demandes de notation</small>
            </div>
        </div>
    </div>

    <div class="todo-section">
        <header>
            <h4>Plan d'action</h4>
        </header>
        <ul id="todo-list">
            <li>Plan d'action 1</li>
            <li>Plan d'action 2</li>
            <li>Plan d'action 3</li>
        </ul>
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

<!-- Popup de visualisation des révisions -->
<div id="wp-bmc-revision-popup" class="wp-bmc-popup">
    <div class="popup-overlay"></div>
    <div class="popup-content revision-popup">
        <div class="popup-header">
            <h3 id="revision-popup-title">Révision de la section</h3>
            <button class="popup-close" id="revision-popup-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="popup-body">
            <div class="revision-info">
                <div class="revision-meta">
                    <span class="revision-date" id="revision-date"></span>
                    <span class="revision-reason" id="revision-reason"></span>
                </div>
            </div>
            <div class="revision-content" id="revision-content">
                <!-- Le contenu de la révision sera chargé ici -->
            </div>
        </div>

        <div class="popup-footer">
            <button type="button" class="popup-btn popup-btn-secondary" id="revision-popup-close">Fermer</button>
        </div>
    </div>
</div>