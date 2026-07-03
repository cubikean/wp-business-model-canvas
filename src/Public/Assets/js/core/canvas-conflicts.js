/**
 * Gestion des conflits de versions du canvas
 * Système de résolution de conflits avec popup par section
 */

(function($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('WP_BMC_CanvasConflicts: jQuery est requis');
        return;
    }

    /**
     * Objet global pour la gestion des conflits
     */
    window.WP_BMC_CanvasConflicts = window.WP_BMC_CanvasConflicts || {};

    var activeConflicts = [];
    var conflictPopups = {};

    /**
     * Détecter si une réponse AJAX contient des conflits
     * @param {Object} response - Réponse AJAX
     * @returns {boolean} True si des conflits sont détectés
     */
    WP_BMC_CanvasConflicts.hasConflicts = function(response) {
        return response && 
               !response.success && 
               response.data && 
               response.data.error_type === 'conflict' &&
               Array.isArray(response.data.conflicts) &&
               response.data.conflicts.length > 0;
    };

    /**
     * Obtenir les conflits d'une réponse
     * @param {Object} response - Réponse AJAX
     * @returns {Array} Tableau des conflits
     */
    WP_BMC_CanvasConflicts.getConflicts = function(response) {
        if (WP_BMC_CanvasConflicts.hasConflicts(response)) {
            return response.data.conflicts;
        }
        return [];
    };

    /**
     * Ouvrir une popup de conflit pour une section
     * @param {Object} conflict - Objet conflit avec section, client_version, server_version, server_content
     * @param {string} clientContent - Contenu local du client
     * @param {Function} onResolve - Callback appelé quand le conflit est résolu
     */
    WP_BMC_CanvasConflicts.openConflictPopup = function(conflict, clientContent, onResolve) {
        var section = conflict.section;
        var popupId = 'wp-bmc-conflict-popup-' + section;
        
        // Si une popup existe déjà pour cette section, la fermer d'abord
        if (conflictPopups[section]) {
            WP_BMC_CanvasConflicts.closeConflictPopup(section);
        }

        /**
         * Extrait le texte brut du HTML et décode les entités HTML
         * @param {string} html - Contenu HTML à convertir en texte
         * @returns {string} Texte brut sans balises HTML
         */
        function stripHtmlAndDecode(html) {
            if (!html) return '';
            
            // Créer un élément temporaire pour extraire le texte
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = String(html);
            
            // Extraire le texte (supprime automatiquement les balises HTML)
            var text = tempDiv.textContent || tempDiv.innerText || '';
            
            // Décoder les entités HTML restantes (comme &nbsp;)
            var textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            text = textarea.value || textarea.textContent || text;
            
            // Nettoyer les espaces multiples et les retours à la ligne
            text = text.replace(/\s+/g, ' ').trim();
            
            return text;
        }

        var serverContent = conflict.server_content || '';
        var clientText = stripHtmlAndDecode(clientContent || '');
        var serverText = stripHtmlAndDecode(serverContent || '');
        
        // Échapper le texte pour l'affichage dans le HTML
        var escapeHtml = window.WP_BMC_Utils ? window.WP_BMC_Utils.escapeHtml : function(text) {
            return String(text || '').replace(/[&<>"']/g, function(m) {
                var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return map[m];
            });
        };
        
        clientText = escapeHtml(clientText);
        serverText = escapeHtml(serverText);

        // Obtenir le titre de la section
        var sectionTitle = getSectionTitle(section);

        // Calculer la position décalée pour éviter le chevauchement
        var popupIndex = activeConflicts.length;
        var offsetX = (popupIndex % 3) * 30; // Décalage horizontal
        var offsetY = (Math.floor(popupIndex / 3)) * 40; // Décalage vertical

        var popupHtml = `
            <div id="${popupId}" class="wp-bmc-popup wp-bmc-conflict-popup" style="z-index: ${10000 + popupIndex};">
                <div class="popup-overlay"></div>
                <div class="popup-content conflict-popup-content" style="transform: translate(calc(-50% + ${offsetX}px), calc(-50% + ${offsetY}px));">
                    <div class="popup-header">
                        <h3>Conflit de version - ${escapeHtml(sectionTitle)}</h3>
                        <button class="popup-close">&times;</button>
                    </div>
                    <div class="popup-body conflict-popup-body">
                        <div class="conflict-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Cette section a été modifiée par un autre utilisateur pendant que vous l'éditiez.</p>
                        </div>
                        <div class="conflict-diff-columns">
                            <div class="conflict-diff-col">
                                <div class="conflict-diff-title">
                                    <strong>Version actuelle</strong>
                                </div>
                                <div class="conflict-diff-content">
                                    <pre>${serverText || '<em>Vide</em>'}</pre>
                                </div>
                                <button class="wp-bmc-btn wp-bmc-btn-primary conflict-choose-btn" data-choice="server">
                                    <i class="fas fa-check"></i> Utiliser cette version
                                </button>
                            </div>
                            <div class="conflict-diff-col">
                                <div class="conflict-diff-title">
                                    <strong>Votre version (brouillon)</strong>
                                </div>
                                <div class="conflict-diff-content">
                                    <pre>${clientText || '<em>Vide</em>'}</pre>
                                </div>
                                <button class="wp-bmc-btn wp-bmc-btn-warning conflict-choose-btn" data-choice="client">
                                    <i class="fas fa-check"></i> Utiliser cette version
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="popup-footer conflict-popup-footer">
                        <button class="wp-bmc-btn wp-bmc-btn-secondary conflict-cancel-btn">Annuler</button>
                        <small class="conflict-info">Vous devez choisir une version pour continuer</small>
                    </div>
                </div>
            </div>
        `;

        $('body').append(popupHtml);
        var $popup = $('#' + popupId);
        conflictPopups[section] = $popup;
        activeConflicts.push(section);

        // Gérer les événements - empêcher la fermeture accidentelle
        $popup.find('.popup-close').on('click', function(e) {
            e.stopPropagation();
            if (confirm('Annuler la résolution de ce conflit ? La section ne sera pas sauvegardée.')) {
                WP_BMC_CanvasConflicts.closeConflictPopup(section);
                if (typeof onResolve === 'function') {
                    onResolve({
                        section: section,
                        choice: 'cancel',
                        content: null
                    });
                }
            }
        });
        
        // L'overlay ne ferme pas la popup (pour éviter les fermetures accidentelles)
        $popup.find('.popup-overlay').on('click', function(e) {
            e.stopPropagation();
        });

        $popup.find('.conflict-choose-btn').on('click', function() {
            var choice = $(this).data('choice');
            var selectedContent = choice === 'server' ? serverContent : clientContent;
            
            if (typeof onResolve === 'function') {
                onResolve({
                    section: section,
                    choice: choice,
                    content: selectedContent,
                    serverVersion: conflict.server_version
                });
            }
            
            WP_BMC_CanvasConflicts.closeConflictPopup(section);
        });

        $popup.find('.conflict-cancel-btn').on('click', function() {
            WP_BMC_CanvasConflicts.closeConflictPopup(section);
            if (typeof onResolve === 'function') {
                onResolve({
                    section: section,
                    choice: 'cancel',
                    content: null
                });
            }
        });

        // Afficher la popup
        $popup.fadeIn(300);
    };

    /**
     * Fermer une popup de conflit
     * @param {string} section - Section concernée
     */
    WP_BMC_CanvasConflicts.closeConflictPopup = function(section) {
        if (conflictPopups[section]) {
            conflictPopups[section].fadeOut(200, function() {
                $(this).remove();
            });
            delete conflictPopups[section];
            activeConflicts = activeConflicts.filter(function(s) { return s !== section; });
        }
    };

    /**
     * Fermer toutes les popups de conflit
     */
    WP_BMC_CanvasConflicts.closeAllPopups = function() {
        Object.keys(conflictPopups).forEach(function(section) {
            WP_BMC_CanvasConflicts.closeConflictPopup(section);
        });
    };

    /**
     * Obtenir le titre d'une section
     * @param {string} sectionKey - Clé de la section
     * @returns {string} Titre de la section
     */
    function getSectionTitle(sectionKey) {
        if (typeof wp_bmc_sections_config !== 'undefined' && wp_bmc_sections_config[sectionKey]) {
            return wp_bmc_sections_config[sectionKey].title;
        }
        
        var titles = {
            'key_partners': 'Partenaires clés',
            'key_activities': 'Activités clés',
            'key_resources': 'Ressources clés',
            'value_proposition': 'Proposition de valeur',
            'customer_relationships': 'Relations clients',
            'channels': 'Canaux',
            'customer_segments': 'Segments clients',
            'cost_structure': 'Structure des coûts',
            'revenue_streams': 'Sources de revenus'
        };
        
        return titles[sectionKey] || sectionKey;
    }

    /**
     * Résoudre tous les conflits d'une réponse
     * Ouvre une popup par conflit simultanément
     * @param {Object} response - Réponse AJAX avec conflits
     * @param {Object} clientCanvasData - Données du canvas côté client
     * @param {Function} onAllResolved - Callback appelé quand tous les conflits sont résolus
     */
    WP_BMC_CanvasConflicts.resolveAllConflicts = function(response, clientCanvasData, onAllResolved) {
        var conflicts = WP_BMC_CanvasConflicts.getConflicts(response);
        if (conflicts.length === 0) {
            if (typeof onAllResolved === 'function') {
                onAllResolved({});
            }
            return;
        }

        var resolutions = {};
        var resolvedCount = 0;
        var totalConflicts = conflicts.length;

        // Ouvrir toutes les popups simultanément
        conflicts.forEach(function(conflict, index) {
            var section = conflict.section;
            var clientContent = clientCanvasData[section] || '';

            // Positionner les popups de manière décalée pour éviter le chevauchement
            setTimeout(function() {
                WP_BMC_CanvasConflicts.openConflictPopup(conflict, clientContent, function(resolution) {
                    if (resolution.choice !== 'cancel') {
                        resolutions[section] = {
                            content: resolution.content,
                            serverVersion: resolution.serverVersion
                        };
                        
                        // Mettre à jour la version si on a choisi le serveur
                        if (resolution.choice === 'server' && window.WP_BMC_CanvasVersions) {
                            window.WP_BMC_CanvasVersions.setSectionVersion(section, resolution.serverVersion);
                        }
                    }
                    
                    resolvedCount++;
                    
                    // Vérifier si toutes les popups sont fermées
                    if (resolvedCount >= totalConflicts) {
                        // Attendre un court délai pour que toutes les animations de fermeture se terminent
                        setTimeout(function() {
                            if (typeof onAllResolved === 'function') {
                                onAllResolved(resolutions);
                            }
                        }, 300);
                    }
                });
            }, index * 100); // Délai progressif pour l'animation
        });
    };

    /**
     * Vérifier s'il y a des conflits actifs
     * @returns {boolean} True si des conflits sont actifs
     */
    WP_BMC_CanvasConflicts.hasActiveConflicts = function() {
        return activeConflicts.length > 0;
    };

})(jQuery);

