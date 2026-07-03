/**
 * WP Business Model Canvas - Système de Présence en Temps Réel
 * Utilise WordPress Heartbeat API pour afficher les utilisateurs actifs
 * 
 * STATUS: DÉSACTIVÉ - En attente de corrections
 * Pour réactiver: mettre PRESENCE_ENABLED à true
 */

(function($) {
    'use strict';
    
    // FLAGS DE CONFIGURATION
    var PRESENCE_ENABLED = false; // Activer/désactiver la fonctionnalité
    var DEBUG_MODE = false; // Activer/désactiver les logs console
    
    // Fonction de log conditionnelle
    function log() {
        if (DEBUG_MODE && typeof console !== 'undefined' && console.log) {
            console.log.apply(console, arguments);
        }
    }
    
    function logWarn() {
        if (DEBUG_MODE && typeof console !== 'undefined' && console.warn) {
            console.warn.apply(console, arguments);
        }
    }
    
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
            if (!PRESENCE_ENABLED) {
                log('WP_BMC_Presence : Fonctionnalité désactivée (PRESENCE_ENABLED = false)');
                return;
            }
            
            if (this.initialized) {
                log('WP_BMC_Presence : Déjà initialisé');
                return;
            }
            
            this.currentProjectId = projectId;
            this.initialized = true;
            
            log('WP_BMC_Presence : Initialisation pour le projet', projectId);
            
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
                
                log('====================================');
                log('WP_BMC_Presence : ENVOI HEARTBEAT');
                log('  Project ID:', self.currentProjectId);
                log('  Section:', self.currentSection);
                log('  Is Editing:', self.isEditing, '(valeur envoyée:', self.isEditing ? 1 : 0, ')');
                log('====================================');
            });
            
            // Recevoir les utilisateurs actifs
            $(document).on('heartbeat-tick.wp_bmc_presence', function(e, data) {
                if (data.wp_bmc_active_users) {
                    log('WP_BMC_Presence : Utilisateurs actifs reçus', data.wp_bmc_active_users.length, 'utilisateur(s)');
                    
                    // Mettre à jour la liste des utilisateurs actifs
                    self.activeUsers = data.wp_bmc_active_users;
                    
                    // Mettre à jour uniquement les indicateurs par section (avec comparaison d'état)
                    self.updateSectionIndicators();
                }
            });
            
            // S'assurer que le Heartbeat est actif
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                wp.heartbeat.interval('fast'); // Mode rapide (15s)
                log('WP_BMC_Presence : Heartbeat configuré en mode rapide');
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
                log('WP_BMC_Presence : Clic sur edit-brick-btn - section:', sectionKey);
                
                if (sectionKey) {
                    // Vérifier si on change de section
                    if (self.currentSection !== sectionKey) {
                        log('WP_BMC_Presence : Changement de section - ancienne:', self.currentSection, '- nouvelle:', sectionKey);
                    }
                    
                    self.currentSection = sectionKey;
                    self.isEditing = true;
                    self.sendPresencePing();
                    log('WP_BMC_Presence : Ouverture édition pour', sectionKey);
                }
            });
            
            // Détecter quand l'utilisateur commence à éditer dans l'éditeur WYSIWYG
            $(document).on('focus click keydown', '#wysiwyg-editor, #wysiwyg-editor [contenteditable], .simple-editor-area', function() {
                // Récupérer la section depuis l'attribut data de la vue d'édition
                var $editView = $('#wp-bmc-edit-view');
                if ($editView.is(':visible')) {
                    var sectionKey = $editView.data('section');
                    
                    if (sectionKey) {
                        // Vérifier si la section a changé ou si ce n'est pas encore en mode édition
                        if (self.currentSection !== sectionKey || !self.isEditing) {
                            if (self.currentSection !== sectionKey) {
                                log('WP_BMC_Presence : Changement de section dans éditeur - ancienne:', self.currentSection, '- nouvelle:', sectionKey);
                            }
                            
                            self.currentSection = sectionKey;
                            self.isEditing = true;
                            self.sendPresencePing(); // Ping immédiat pour signaler l'édition
                            log('WP_BMC_Presence : Focus dans éditeur (interaction détectée)', sectionKey);
                        }
                    }
                }
            });
            
            // Vérifier périodiquement si la vue d'édition est ouverte
            setInterval(function() {
                var $editView = $('#wp-bmc-edit-view');
                
                if ($editView.is(':visible')) {
                    var sectionKey = $editView.data('section');
                    
                    if (sectionKey) {
                        // Vérifier si la section a changé
                        if (self.currentSection !== sectionKey) {
                            log('WP_BMC_Presence : Changement de section détecté - ancienne:', self.currentSection, '- nouvelle:', sectionKey);
                            self.currentSection = sectionKey;
                            self.isEditing = true;
                            self.sendPresencePing();
                        } else if (!self.isEditing) {
                            // Première détection
                            self.currentSection = sectionKey;
                            self.isEditing = true;
                            log('WP_BMC_Presence : Vue d\'édition ouverte (détection périodique)', sectionKey);
                        }
                    }
                } else if (self.isEditing) {
                    // La vue d'édition est fermée mais on était en mode édition
                    log('WP_BMC_Presence : Vue d\'édition fermée (détection périodique)');
                    self.isEditing = false;
                    self.currentSection = null;
                    self.sendPresencePing();
                }
            }, 3000); // Vérifier toutes les 3 secondes
            
            // Détecter la fermeture de la vue d'édition (bouton retour)
            $(document).on('click', '#back-to-dashboard, .close-edit-view', function() {
                if (self.currentSection) {
                    log('WP_BMC_Presence : Fermeture édition', self.currentSection);
                    self.isEditing = false;
                    self.currentSection = null;
                    self.sendPresencePing();
                }
            });
            
            // Détecter la sauvegarde (l'utilisateur a terminé)
            $(document).on('click', '#save-canvas-btn, .save-section-btn', function() {
                log('WP_BMC_Presence : Sauvegarde, fin édition');
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
                                    log('WP_BMC_Presence : Focus TinyMCE', sectionKey);
                                }
                            }
                        });
                    });
                }, 2000);
            }
            
            // Détecter le changement de vue (simple/synthetic)
            $(document).on('click', '.view-toggle-button, [data-view]', function() {
                // Nettoyer tous les indicateurs car la vue va être rechargée
                $('.section-editing-indicator').remove();
                log('WP_BMC_Presence : Changement de vue - nettoyage des indicateurs');
                
                setTimeout(function() {
                    // Forcer une mise à jour immédiate après le changement de vue
                    self.previousActiveUsers = []; // Réinitialiser pour forcer la mise à jour
                    self.sendPresencePing();
                }, 500);
            });
        },
        
        /**
         * Créer l'interface de présence (désactivé - on utilise uniquement les indicateurs par section)
         */
        renderPresenceUI: function() {
            // Ne rien créer - on affiche uniquement les indicateurs par section
            log('WP_BMC_Presence : Mode indicateurs par section uniquement (pas de widget global)');
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
                return user.is_editing === true && user.section;
            });
            
            log('WP_BMC_Presence : Utilisateurs en édition', editingUsers.length);
            editingUsers.forEach(function(user) {
                log('  -', user.full_name, 'édite', user.section);
            });
            
            // Créer une clé unique pour chaque état d'édition (basée sur les utilisateurs en édition uniquement)
            var currentState = editingUsers.map(function(user) {
                return user.user_id + ':' + user.section;
            }).sort().join('|');
            
            // Filtrer les utilisateurs en édition de l'état précédent
            var previousEditingUsers = this.previousActiveUsers.filter(function(user) {
                return user.is_editing === true && user.section;
            });
            
            var previousState = previousEditingUsers.map(function(user) {
                return user.user_id + ':' + user.section;
            }).sort().join('|');
            
            // Comparer avec l'état précédent
            if (currentState === previousState) {
                log('WP_BMC_Presence : État d\'édition inchangé, pas de mise à jour des indicateurs');
                return;
            }
            
            log('WP_BMC_Presence : État d\'édition changé, mise à jour des indicateurs');
            log('  Ancien état :', previousState);
            log('  Nouvel état :', currentState);
            
            // D'abord, supprimer TOUS les indicateurs existants
            $('.section-editing-indicator').each(function() {
                var $indicator = $(this);
                var userId = parseInt($indicator.data('user-id'));
                var sectionKey = $indicator.data('section');
                
                // Vérifier si cet utilisateur édite toujours cette section
                var stillEditing = editingUsers.some(function(user) {
                    return parseInt(user.user_id) === userId && user.section === sectionKey;
                });
                
                if (!stillEditing) {
                    log('WP_BMC_Presence : Suppression indicateur pour user', userId, 'sur', sectionKey);
                    $indicator.fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Ensuite, ajouter/mettre à jour les indicateurs pour les utilisateurs en édition
            editingUsers.forEach(function(user) {
                log('WP_BMC_Presence : Recherche section pour user', user.full_name, '- section:', user.section);
                
                // Déboguer : lister toutes les sections disponibles
                var availableSections = [];
                $('.canvas-section[data-section]').each(function() {
                    availableSections.push($(this).data('section'));
                });
                log('WP_BMC_Presence : Sections disponibles dans le DOM:', availableSections);
                
                var $section = $('.canvas-section[data-section="' + user.section + '"]');
                log('WP_BMC_Presence : Section trouvée ? ', $section.length > 0, '(', $section.length, 'élément(s))');
                
                if ($section.length > 0) {
                    // Vérifier si un indicateur existe déjà pour cet utilisateur sur cette section
                    var $existingIndicator = $('.section-editing-indicator[data-user-id="' + user.user_id + '"][data-section="' + user.section + '"]');
                    
                    if ($existingIndicator.length === 0) {
                        // Créer le nouvel indicateur avec les bonnes data attributes
                        var $indicator = $('<div class="section-editing-indicator">')
                            .attr('data-user-id', user.user_id)
                            .attr('data-section', user.section)
                            .html('<span class="edit-icon">✏️</span> <strong>' + user.first_name + ' ' + user.last_name + '</strong> édite cette section')
                            .hide();
                        
                        // Insérer l'indicateur juste après le header de la section
                        var $header = $section.find('.canvas-section-header, .section-header');
                        log('WP_BMC_Presence : Header trouvé ?', $header.length > 0);
                        
                        if ($header.length > 0) {
                            $header.after($indicator);
                            log('WP_BMC_Presence : Indicateur inséré après le header');
                        } else {
                            // Fallback : insérer au début de la section
                            $section.prepend($indicator);
                            log('WP_BMC_Presence : Indicateur inséré au début de la section (fallback)');
                        }
                        
                        $indicator.fadeIn(300);
                        log('WP_BMC_Presence : Indicateur ajouté pour', user.full_name, 'sur', user.section);
                    } else {
                        log('WP_BMC_Presence : Indicateur déjà présent pour', user.full_name, 'sur', user.section);
                    }
                } else {
                    logWarn('WP_BMC_Presence : Section "' + user.section + '" non trouvée dans le DOM pour user', user.full_name);
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
            
            log('WP_BMC_Presence : Nettoyage effectué');
        }
    };
    
    // Exposer globalement
    window.WP_BMC_Presence = WP_BMC_Presence;
    
    // Initialiser automatiquement si la variable globale existe
    $(document).ready(function() {
        if (!PRESENCE_ENABLED) {
            log('WP_BMC_Presence : Auto-initialisation annulée (fonctionnalité désactivée)');
            return;
        }
        
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
                log('WP_BMC_Presence : Auto-initialisation avec projet', projectId);
                WP_BMC_Presence.init(projectId);
            } else {
                log('WP_BMC_Presence : Aucun projet détecté, présence désactivée');
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

