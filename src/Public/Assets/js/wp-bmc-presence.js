/**
 * WP Business Model Canvas - Système de Présence en Temps Réel
 * Utilise WordPress Heartbeat API pour afficher les utilisateurs actifs
 */

(function($) {
    'use strict';
    
    var WP_BMC_Presence = {
        currentProjectId: null,
        currentSection: null,
        isEditing: false,
        activeUsers: [],
        previousActiveUsers: [], // Cache pour comparer l'état précédent
        initialized: false,
        
        /**
         * Initialiser le système de présence
         */
        init: function(projectId) {
            if (this.initialized) {
                console.log('WP_BMC_Presence : Déjà initialisé');
                return;
            }
            
            this.currentProjectId = projectId;
            this.initialized = true;
            
            console.log('WP_BMC_Presence : Initialisation pour le projet', projectId);
            
            this.setupHeartbeat();
            this.setupEditTracking();
            
            // Envoyer un ping immédiat pour annoncer la présence
            this.sendPresencePing();
        },
        
        /**
         * Configurer le Heartbeat WordPress
         */
        setupHeartbeat: function() {
            var self = this;
            
            // Envoyer la présence via Heartbeat
            $(document).on('heartbeat-send.wp_bmc_presence', function(e, data) {
                data.wp_bmc_presence = {
                    project_id: self.currentProjectId,
                    section: self.currentSection,
                    is_editing: self.isEditing ? 1 : 0
                };
                
                console.log('WP_BMC_Presence : Envoi heartbeat', {
                    project_id: self.currentProjectId,
                    section: self.currentSection,
                    is_editing: self.isEditing,
                    is_editing_value: self.isEditing ? 1 : 0
                });
            });
            
            // Recevoir les utilisateurs actifs
            $(document).on('heartbeat-tick.wp_bmc_presence', function(e, data) {
                if (data.wp_bmc_active_users) {
                    console.log('WP_BMC_Presence : Utilisateurs actifs reçus', data.wp_bmc_active_users.length, 'utilisateur(s)');
                    
                    // Mettre à jour la liste des utilisateurs actifs
                    self.activeUsers = data.wp_bmc_active_users;
                    
                    // Mettre à jour uniquement les indicateurs par section (avec comparaison d'état)
                    self.updateSectionIndicators();
                }
            });
            
            // S'assurer que le Heartbeat est actif
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                wp.heartbeat.interval('fast'); // Mode rapide (15s)
                console.log('WP_BMC_Presence : Heartbeat configuré en mode rapide');
            }
        },
        
        /**
         * Envoyer un ping de présence immédiat (sans attendre le Heartbeat)
         */
        sendPresencePing: function() {
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                wp.heartbeat.connectNow();
            }
        },
        
        /**
         * Configurer le tracking d'édition
         */
        setupEditTracking: function() {
            var self = this;
            
            // Détecter l'ouverture de la vue d'édition (bouton "Éditer la brique")
            $(document).on('click', '.edit-brick-btn', function() {
                var sectionKey = $(this).data('section');
                if (sectionKey) {
                    self.currentSection = sectionKey;
                    self.isEditing = true;
                    self.sendPresencePing();
                    console.log('WP_BMC_Presence : Ouverture édition', sectionKey);
                }
            });
            
            // Détecter quand l'utilisateur commence à éditer dans l'éditeur WYSIWYG
            $(document).on('focus click keydown', '#wysiwyg-editor, #wysiwyg-editor [contenteditable], .simple-editor-area', function() {
                // Récupérer la section depuis l'attribut data de la vue d'édition
                var $editView = $('#wp-bmc-edit-view');
                if ($editView.is(':visible')) {
                    var sectionKey = $editView.data('section');
                    if (sectionKey && !self.isEditing) {
                        self.currentSection = sectionKey;
                        self.isEditing = true;
                        self.sendPresencePing(); // Ping immédiat pour signaler l'édition
                        console.log('WP_BMC_Presence : Focus dans éditeur (interaction détectée)', sectionKey);
                    }
                }
            });
            
            // Vérifier périodiquement si la vue d'édition est ouverte
            setInterval(function() {
                var $editView = $('#wp-bmc-edit-view');
                if ($editView.is(':visible') && !self.isEditing) {
                    var sectionKey = $editView.data('section');
                    if (sectionKey) {
                        self.currentSection = sectionKey;
                        self.isEditing = true;
                        console.log('WP_BMC_Presence : Vue d\'édition ouverte (détection périodique)', sectionKey);
                    }
                } else if (!$editView.is(':visible') && self.isEditing) {
                    // La vue d'édition est fermée mais on était en mode édition
                    self.isEditing = false;
                    self.currentSection = null;
                    console.log('WP_BMC_Presence : Vue d\'édition fermée (détection périodique)');
                }
            }, 3000); // Vérifier toutes les 3 secondes
            
            // Détecter la fermeture de la vue d'édition (bouton retour)
            $(document).on('click', '#back-to-dashboard, .close-edit-view', function() {
                if (self.currentSection) {
                    console.log('WP_BMC_Presence : Fermeture édition', self.currentSection);
                    self.isEditing = false;
                    self.currentSection = null;
                    self.sendPresencePing();
                }
            });
            
            // Détecter la sauvegarde (l'utilisateur a terminé)
            $(document).on('click', '#save-canvas-btn, .save-section-btn', function() {
                console.log('WP_BMC_Presence : Sauvegarde, fin édition');
                self.isEditing = false;
                self.currentSection = null;
                self.sendPresencePing();
            });
            
            // Détecter TinyMCE si présent (après initialisation)
            if (typeof tinymce !== 'undefined') {
                setTimeout(function() {
                    tinymce.editors.forEach(function(editor) {
                        editor.on('focus', function() {
                            var $editView = $('#wp-bmc-edit-view');
                            if ($editView.is(':visible')) {
                                var sectionKey = $editView.data('section');
                                if (sectionKey) {
                                    self.currentSection = sectionKey;
                                    self.isEditing = true;
                                    console.log('WP_BMC_Presence : Focus TinyMCE', sectionKey);
                                }
                            }
                        });
                    });
                }, 2000);
            }
            
            // Détecter le changement de vue (simple/synthetic)
            $(document).on('click', '.view-toggle-button', function() {
                setTimeout(function() {
                    self.setupEditTracking();
                }, 500);
            });
        },
        
        /**
         * Créer l'interface de présence (désactivé - on utilise uniquement les indicateurs par section)
         */
        renderPresenceUI: function() {
            // Ne rien créer - on affiche uniquement les indicateurs par section
            console.log('WP_BMC_Presence : Mode indicateurs par section uniquement (pas de widget global)');
        },
        
        /**
         * Mettre à jour l'affichage de la présence (désactivé - on utilise uniquement les indicateurs par section)
         */
        updatePresenceDisplay: function() {
            // Ne rien faire - on n'affiche plus le widget global
            // Uniquement les indicateurs par section
        },
        
        /**
         * Mettre à jour les indicateurs par section (uniquement pour les utilisateurs en édition)
         */
        updateSectionIndicators: function() {
            var self = this;
            
            // Filtrer uniquement les utilisateurs en train d'éditer
            var editingUsers = this.activeUsers.filter(function(user) {
                return user.is_editing && user.section;
            });
            
            // Créer une clé unique pour chaque état d'édition
            var currentState = editingUsers.map(function(user) {
                return user.user_id + ':' + user.section + ':' + user.is_editing;
            }).sort().join('|');
            
            var previousState = this.previousActiveUsers.map(function(user) {
                if (user.is_editing && user.section) {
                    return user.user_id + ':' + user.section + ':' + user.is_editing;
                }
                return null;
            }).filter(function(s) { return s !== null; }).sort().join('|');
            
            // Comparer avec l'état précédent
            if (currentState === previousState) {
                console.log('WP_BMC_Presence : État inchangé, pas de mise à jour des indicateurs');
                return; // Aucun changement, ne rien faire
            }
            
            console.log('WP_BMC_Presence : État changé, mise à jour des indicateurs');
            console.log('  Ancien état :', previousState);
            console.log('  Nouvel état :', currentState);
            
            // Supprimer les indicateurs qui ne sont plus valides
            $('.section-editing-indicator').each(function() {
                var $indicator = $(this);
                var userId = $indicator.data('user-id');
                var section = $indicator.closest('.canvas-section').data('section');
                
                // Vérifier si cet utilisateur édite toujours cette section
                var stillEditing = editingUsers.some(function(user) {
                    return user.user_id === userId && user.section === section;
                });
                
                if (!stillEditing) {
                    // Supprimer avec animation
                    $indicator.fadeOut(200, function() {
                        $(this).remove();
                    });
                    console.log('WP_BMC_Presence : Indicateur supprimé pour user', userId, 'sur', section);
                }
            });
            
            // Ajouter les nouveaux indicateurs
            editingUsers.forEach(function(user) {
                var $section = $('.canvas-section[data-section="' + user.section + '"]');
                
                if ($section.length > 0) {
                    // Vérifier si un indicateur existe déjà pour cet utilisateur sur cette section
                    var indicatorExists = $section.find('.section-editing-indicator[data-user-id="' + user.user_id + '"]').length > 0;
                    
                    if (!indicatorExists) {
                        var $indicator = $('<div class="section-editing-indicator" data-user-id="' + user.user_id + '">')
                            .html('<span class="edit-icon">✏️</span> <strong>' + user.first_name + ' ' + user.last_name + '</strong> édite cette section')
                            .hide()
                            .fadeIn(300);
                        
                        // Insérer l'indicateur juste après le header de la section
                        var $header = $section.find('.canvas-section-header, .section-header');
                        if ($header.length > 0) {
                            $header.after($indicator);
                        } else {
                            // Fallback : insérer au début de la section
                            $section.prepend($indicator);
                        }
                        
                        console.log('WP_BMC_Presence : Indicateur ajouté pour', user.full_name, 'sur', user.section);
                    }
                }
            });
            
            // Sauvegarder l'état actuel pour la prochaine comparaison
            this.previousActiveUsers = JSON.parse(JSON.stringify(this.activeUsers));
        },
        
        /**
         * Nettoyer les événements et session lors de la déconnexion
         */
        destroy: function() {
            $(document).off('.wp_bmc_presence');
            
            if (this.currentProjectId) {
                // Informer le serveur que l'utilisateur quitte
                this.isEditing = false;
                this.currentSection = null;
                this.sendPresencePing();
            }
            
            // Supprimer tous les indicateurs d'édition
            $('.section-editing-indicator').remove();
            this.initialized = false;
            
            console.log('WP_BMC_Presence : Nettoyage effectué');
        }
    };
    
    // Exposer globalement
    window.WP_BMC_Presence = WP_BMC_Presence;
    
    // Initialiser automatiquement si la variable globale existe
    $(document).ready(function() {
        // Attendre un peu pour s'assurer que tout est chargé
        setTimeout(function() {
            var projectId = null;
            
            // Essayer de récupérer depuis la config localisée
            if (typeof wp_bmc_presence_config !== 'undefined' && wp_bmc_presence_config.project_id) {
                projectId = wp_bmc_presence_config.project_id;
            }
            // Fallback : essayer wp_bmc_project_id
            else if (typeof wp_bmc_project_id !== 'undefined' && wp_bmc_project_id) {
                projectId = wp_bmc_project_id;
            }
            // Fallback : essayer depuis le data attribute du container
            else if ($('.wp-bmc-dashboard').data('project-id')) {
                projectId = $('.wp-bmc-dashboard').data('project-id');
            }
            
            if (projectId) {
                console.log('WP_BMC_Presence : Auto-initialisation avec projet', projectId);
                WP_BMC_Presence.init(projectId);
            } else {
                console.log('WP_BMC_Presence : Aucun projet détecté, présence désactivée');
            }
        }, 1000);
    });
    
    // Nettoyer avant de quitter la page
    $(window).on('beforeunload', function() {
        if (WP_BMC_Presence.initialized) {
            WP_BMC_Presence.destroy();
        }
    });
    
})(jQuery);

