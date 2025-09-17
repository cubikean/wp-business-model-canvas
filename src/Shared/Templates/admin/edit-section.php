<?php

/**
 * Template pour la section d'édition des briques - Version Admin
 * Réutilisable dans le dashboard admin
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
        </div>

        <div class="rating-section" id="rating-section">
            <h4>Note de la brique</h4>
            <div class="rating-display" id="rating-display">
                <div class="rating-score">
                    <span class="rating-score-number" id="rating-score-number">-</span>
                    <span class="rating-score-total">/10</span>
                </div>
                <div class="rating-comment" id="rating-comment">
                    <p class="no-rating">Aucune note attribuée</p>
                </div>
                <div class="rating-meta" id="rating-meta">
                    <small class="rating-date"></small>
                    <small class="rating-admin"></small>
                </div>
            </div>
        </div>
    </div>

    <div class="history-section">
        <header>
            <h4 id="revisions-section-title">Historique score de maturité</h4>
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
            <div class="todo-stats" id="todo-stats">
                <span class="todo-count">
                    <span id="todo-completed-count">0</span>/<span id="todo-total-count">0</span> terminées
                </span>
                <span id="todo-save-indicator" class="save-indicator" style="display: none;">
                    <i class="fas fa-circle"></i>
                </span>
            </div>
        </header>
        
        <div class="todo-add-form">
            <div class="todo-input-group">
                <input type="text" id="todo-input" placeholder="Ajouter une nouvelle tâche..." maxlength="255">
                <button type="button" id="add-todo-btn" class="btn-outline --icon --small">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        
        <div class="todo-list-container">
            <ul id="todo-list" class="todo-list">
                <!-- Les tâches seront chargées dynamiquement -->
            </ul>
            <div id="no-todos" class="no-todos" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <p>Aucune tâche pour cette section</p>
                <small>Ajoutez votre première tâche ci-dessus</small>
            </div>
        </div>
    </div>
</div>

<!-- Popup des documents de référence -->
<div id="wp-bmc-documents-popup" class="wp-bmc-popup">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <div class="popup-header">
            <h3>Ressources pédagogiques</h3>
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