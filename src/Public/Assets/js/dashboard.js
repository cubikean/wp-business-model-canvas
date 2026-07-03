/**
 * JavaScript pour le dashboard WP Business Model Canvas
 * Gère la création de projet, la sauvegarde, les vues et les popups d'édition
 * Utilise les modules core pour éviter la duplication de code
 */

jQuery(document).ready(function($) {
    
    // Vérifier que les modules core sont disponibles
    if (typeof window.WP_BMC_Utils === 'undefined' || 
        typeof window.WP_BMC_CanvasVersions === 'undefined' ||
        typeof window.WP_BMC_CanvasDraft === 'undefined' ||
        typeof window.WP_BMC_CanvasCore === 'undefined') {
        console.error('WP_BMC_Dashboard: Les modules core sont requis');
        return;
    }
    
    // ========================================
    // GESTION DU MENU UTILISATEUR
    // ========================================
    
    // Initialiser le menu utilisateur
    initUserMenu();
    
    function initUserMenu() {
        // Clic sur l'avatar pour ouvrir/fermer le menu
        $(document).on('click', '#wp-bmc-user-avatar', function(e) {
            e.stopPropagation();
            toggleUserDropdown();
        });
        
        // Clic en dehors pour fermer le menu
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.wp-bmc-user-menu').length) {
                closeUserDropdown();
            }
        });
        
        // Actions du menu
        $(document).on('click', '#wp-bmc-logout-btn', function(e) {
            e.preventDefault();
            handleLogout();
        });
        
        $(document).on('click', '#wp-bmc-add-account-btn', function(e) {
            e.preventDefault();
            handleAddAccount();
        });
    }
    
    function toggleUserDropdown() {
        const dropdown = $('#wp-bmc-user-dropdown');
        if (dropdown.hasClass('show')) {
            closeUserDropdown();
        } else {
            openUserDropdown();
        }
    }
    
    function openUserDropdown() {
        const dropdown = $('#wp-bmc-user-dropdown');
        dropdown.addClass('show');
        
        // Animation d'ouverture
        dropdown.css({
            'transform': 'translateY(0)',
            'opacity': '1',
            'visibility': 'visible'
        });
    }
    
    function closeUserDropdown() {
        const dropdown = $('#wp-bmc-user-dropdown');
        dropdown.removeClass('show');
        
        // Animation de fermeture
        dropdown.css({
            'transform': 'translateY(-10px)',
            'opacity': '0',
            'visibility': 'hidden'
        });
    }
    
    function handleLogout() {
        WP_BMC_Toast.info('Déconnexion en cours...');
        
        const ajaxConfig = getAjaxConfig();
        
        $.post(ajaxConfig.url, {
            action: 'wp_bmc_logout',
            nonce: ajaxConfig.nonce
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1000);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors de la déconnexion');
        });
    }
    
    function handleAddAccount() {
        WP_BMC_Toast.info('Redirection vers la page de connexion...');
        setTimeout(function() {
            window.location.href = '/login/';
        }, 500);
    }
    
    // ========================================
    // SYSTÈME D'OPTIMISATION DES TODOS
    // ========================================
    
    // Variables globales pour les todos
    var currentSectionTodos = [];
    var currentSectionStats = { total: 0, completed: 0, pending: 0 };
    
    // Système de cache et opérations différées
    var todoCache = {};
    var pendingOperations = {
        add: [],
        update: [],
        delete: [],
        toggle: []
    };
    var isDirty = false; // Indique si des changements non sauvegardés existent
    var saveTimeout = null; // Timeout pour la sauvegarde automatique
    var pendingDraftData = null; // Variable locale pour compatibilité
    var draftComparePopupId = window.WP_BMC_CanvasDraft.getComparePopupId();
    
    // ========================================
    // UTILITAIRES AJAX
    // ========================================
    
    // Fonction utilitaire pour obtenir le bon nonce et URL AJAX
    function getAjaxConfig() {
        // Vérifier d'abord si wp_bmc_admin_ajax est défini et disponible
        if (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce && wp_bmc_admin_ajax.ajax_url) {
            return {
                nonce: wp_bmc_admin_ajax.nonce,
                url: wp_bmc_admin_ajax.ajax_url
            };
        }
        
        // Sinon utiliser wp_bmc_ajax (qui devrait toujours être disponible)
        if (typeof wp_bmc_ajax !== 'undefined' && wp_bmc_ajax.nonce && wp_bmc_ajax.ajax_url) {
            return {
                nonce: wp_bmc_ajax.nonce,
                url: wp_bmc_ajax.ajax_url
            };
        }
        
        // Fallback si aucune variable AJAX n'est disponible
        logError('getAjaxConfig - Aucune variable AJAX disponible');
        return {
            nonce: null,
            url: null
        };
    }
    
    function isAdminContext() {
        return typeof wp_bmc_admin_ajax !== 'undefined' &&
            typeof wp_bmc_admin_ajax.nonce !== 'undefined' &&
            typeof wp_bmc_admin_ajax.ajax_url !== 'undefined';
    }

    // Utiliser les modules core pour les versions et les erreurs
    function mergeCanvasVersions(newVersions) {
        if (window.WP_BMC_CanvasVersions) {
            window.WP_BMC_CanvasVersions.merge(newVersions);
        }
    }
    
    function getCanvasVersionsPayload() {
        return window.WP_BMC_CanvasVersions ? window.WP_BMC_CanvasVersions.getPayload() : {};
    }
    
    function extractErrorMessage(response, fallbackMessage) {
        return window.WP_BMC_Utils ? window.WP_BMC_Utils.extractErrorMessage(response, fallbackMessage) : (fallbackMessage || 'Erreur inconnue');
    }
    
    // ========================================
    // GESTION DES BROUILLONS (anti-perte au refresh)
    // Utilise le module core WP_BMC_CanvasDraft
    // ========================================
    function persistCanvasDraft() {
        window.WP_BMC_CanvasDraft.persist('.canvas-content', function($element) {
            return $element.html();
        });
        // Synchroniser avec la variable locale pour compatibilité
        pendingDraftData = window.WP_BMC_CanvasDraft.getPendingData();
    }
    
    function clearCanvasDraft() {
        window.WP_BMC_CanvasDraft.clear();
        pendingDraftData = null;
    }
    
    function detectCanvasDraft() {
        window.WP_BMC_CanvasDraft.detect(function(draftData) {
            pendingDraftData = draftData;
            showDraftRestoreNotice();
        });
    }
    
    function getDraftTargetSection() {
        var section = null;
        if ($('#wp-bmc-edit-view').is(':visible')) {
            section = $('#wp-bmc-edit-view').attr('data-section');
        }
        if (!section && pendingDraftData && pendingDraftData.sections) {
            var keys = Object.keys(pendingDraftData.sections);
            section = keys.length ? keys[0] : null;
        }
        return section;
    }
    
    function showDraftRestoreNotice() {
        var $container = $('.wp-bmc-dashboard');
        if (!$container.length || $('#wp-bmc-draft-notice').length) {
            return;
        }
        
        var $target = $container.find('.dashboard-header').first();
        if (!$target.length) {
            $target = $container;
        }
        
        var $notice = $('<div id="wp-bmc-draft-notice" class="wp-bmc-draft-notice"></div>').css({
            padding: '10px 12px',
            background: '#fff8e5',
            border: '1px solid #f0c36d',
            color: '#6b4c1f',
            borderRadius: '6px',
            margin: '10px 0'
        });
        
        var $text = $('<span></span>').text('Brouillon local détecté. Il peut être dépassé par une sauvegarde récente. Comparer avant d\'appliquer ? ');
        var $btnCompare = $('<button type="button" class="wp-bmc-btn wp-bmc-btn-warning"></button>').text('Comparer').css({ marginRight: '8px' });
        var $btnRestore = $('<button type="button" class="wp-bmc-btn wp-bmc-btn-primary"></button>').text('Restaurer le brouillon').css({ marginRight: '8px' });
        var $btnDiscard = $('<button type="button" class="wp-bmc-btn wp-bmc-btn-secondary"></button>').text('Ignorer').css({ marginRight: '0' });
        
        var targetSection = getDraftTargetSection();
        
        $btnCompare.on('click', function() {
            openDraftComparePopup(targetSection);
        });
        
        $btnRestore.on('click', function() {
            applyDraftData(targetSection);
            $notice.remove();
        });
        
        $btnDiscard.on('click', function() {
            clearCanvasDraft();
            pendingDraftData = null;
            $notice.remove();
            WP_BMC_Toast.info('Brouillon local ignoré.');
        });
        
        $notice.append($text).append($btnCompare).append($btnRestore).append($btnDiscard);
        $notice.insertAfter($target);
    }
    
    function applyDraftData(targetSection) {
        if (!pendingDraftData || !pendingDraftData.sections) {
            return;
        }
        
        var sectionToApply = targetSection || getDraftTargetSection();
        if (!sectionToApply) {
            WP_BMC_Toast.error('Impossible de déterminer la section du brouillon.');
            return;
        }
        
        var content = pendingDraftData.sections[sectionToApply] || '';
        $('[data-section="' + sectionToApply + '"] .canvas-content').html(content);
        
        if ($('.wp-bmc-edit-view').is(':visible')) {
            var currentEditSection = $('#wp-bmc-edit-view').attr('data-section');
            if (currentEditSection === sectionToApply) {
                if (window.wysiwygEditor && typeof window.wysiwygEditor.setContent === 'function') {
                    window.wysiwygEditor.setContent(content || '');
                    $('#wp-bmc-edit-view').data('original-content', content || '');
                } else {
                    $('.editor-content').html(content || '');
                    $('#wp-bmc-edit-view').data('original-content', content || '');
                }
            }
        }
        
        // Recharger les versions actuelles depuis le serveur pour permettre la sauvegarde
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
        
        if (projectId && nonce && ajaxUrl) {
            $.post(ajaxUrl, {
                action: 'wp_bmc_get_canvas',
                nonce: nonce,
                project_id: projectId
            }, function(response) {
                if (response.success && response.data && response.data.canvas_versions) {
                    mergeCanvasVersions(response.data.canvas_versions);
                    hasUnsavedDraft = true;
                    WP_BMC_Toast.info('Brouillon appliqué sur la section concernée. Tu peux maintenant sauvegarder.');
                } else {
                    hasUnsavedDraft = true;
                    WP_BMC_Toast.info('Brouillon appliqué sur la section concernée. Vérifie qu\'il n\'est pas dépassé par une sauvegarde serveur.');
                }
            }).fail(function() {
                // En cas d'erreur, utiliser les versions du brouillon comme fallback
                if (pendingDraftData.canvas_versions) {
                    mergeCanvasVersions(pendingDraftData.canvas_versions);
                }
                hasUnsavedDraft = true;
                WP_BMC_Toast.warning('Brouillon appliqué mais impossible de recharger les versions serveur. La sauvegarde peut échouer.');
            });
        } else {
            // Fallback si pas de config AJAX
            if (pendingDraftData.canvas_versions) {
                mergeCanvasVersions(pendingDraftData.canvas_versions);
            }
            hasUnsavedDraft = true;
            WP_BMC_Toast.info('Brouillon appliqué sur la section concernée. Vérifie qu\'il n\'est pas dépassé par une sauvegarde serveur.');
        }
    }
    
    function openDraftComparePopup(targetSection) {
        if (!pendingDraftData || !pendingDraftData.sections) {
            WP_BMC_Toast.error('Aucun brouillon à comparer.');
            return;
        }
        
        var sectionToCompare = targetSection || getDraftTargetSection();
        if (!sectionToCompare) {
            WP_BMC_Toast.error('Impossible de déterminer la section à comparer.');
            return;
        }
        
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        if (!projectId) {
            WP_BMC_Toast.error('Projet introuvable pour comparer le brouillon.');
            return;
        }
        
        var popup = $('#' + draftComparePopupId);
        var popupExists = popup.length > 0;
        
        if (!popupExists) {
            var popupHtml = `
                <div id="${draftComparePopupId}" class="wp-bmc-popup">
                    <div class="popup-overlay"></div>
                    <div class="popup-content">
                        <div class="popup-header">
                            <h3>Comparer le brouillon local</h3>
                            <button class="popup-close">&times;</button>
                        </div>
                        <div class="popup-body draft-compare-body">
                            <!-- contenu généré dynamiquement -->
                        </div>
                        <div class="popup-footer draft-compare-footer">
                            <button id="draft-compare-restore" class="wp-bmc-btn wp-bmc-btn-primary">Restaurer le brouillon</button>
                            <button id="draft-compare-discard" class="wp-bmc-btn wp-bmc-btn-secondary">Ignorer</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(popupHtml);
            popup = $('#' + draftComparePopupId);
        }
        
        var $body = popup.find('.draft-compare-body');
        $body.html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des données...</span></div>');
        
        fetchServerCanvasData(projectId).then(function(serverData) {
            $body.html(buildDraftDiffHtml(serverData, sectionToCompare));
        }).catch(function() {
            $body.html('<p>Impossible de charger les données serveur pour comparer.</p>');
        });
        
        popup.find('.popup-close, .popup-overlay').off('click').on('click', function() {
            closeDraftComparePopup();
        });
        
        popup.find('#draft-compare-restore').off('click').on('click', function() {
            applyDraftData(sectionToCompare);
            closeDraftComparePopup();
            $('#wp-bmc-draft-notice').remove();
        });
        
        popup.find('#draft-compare-discard').off('click').on('click', function() {
            clearCanvasDraft();
            pendingDraftData = null;
            closeDraftComparePopup();
            $('#wp-bmc-draft-notice').remove();
            WP_BMC_Toast.info('Brouillon local ignoré.');
        });
        
        popup.fadeIn(300);
    }
    
    function closeDraftComparePopup() {
        $('#' + draftComparePopupId).fadeOut(200);
    }
    
    function fetchServerCanvasData(projectId) {
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
        
        return new Promise(function(resolve, reject) {
            $.post(ajaxUrl, {
                action: 'wp_bmc_get_canvas',
                nonce: nonce,
                project_id: projectId
            }, function(response) {
                if (response && response.success && response.data && response.data.canvas_data) {
                    resolve(response.data.canvas_data);
                } else {
                    reject(response && response.data ? response.data : 'Erreur de récupération');
                }
            }).fail(function() {
                reject('Erreur réseau');
            });
        });
    }
    
    function buildDraftDiffHtml(serverData, sectionToCompare) {
        var draftData = pendingDraftData || window.WP_BMC_CanvasDraft.getPendingData();
        var sections = draftData ? draftData.sections : {};
        var serverSections = serverData || {};
        
        var draftContent = sections[sectionToCompare] || '';
        var serverContent = serverSections[sectionToCompare] || '';
        
        var escapeHtml = window.WP_BMC_Utils.escapeHtml;
        var draftText = escapeHtml(String(draftContent || ''));
        var serverText = escapeHtml(String(serverContent || ''));
        
        var html = `
            <div class="draft-diff-item">
                <div class="draft-diff-header">
                    <span class="section-label">Section : ${escapeHtml(sectionToCompare)}</span>
                </div>
                <div class="draft-diff-columns">
                    <div class="draft-diff-col">
                        <div class="draft-diff-title">Version enregistrée (serveur)</div>
                        <pre class="draft-diff-content">${serverText || '<em>Vide</em>'}</pre>
                    </div>
                    <div class="draft-diff-col">
                        <div class="draft-diff-title">Brouillon local</div>
                        <pre class="draft-diff-content">${draftText || '<em>Vide</em>'}</pre>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    }
    
    $(window).on('beforeunload', function(e) {
        if (window.WP_BMC_CanvasDraft.hasUnsavedDraft()) {
            var message = 'Des modifications du canvas ne sont pas sauvegardées.';
            e.preventDefault();
            e.returnValue = message;
            return message;
        }
    });

    // Détecter un brouillon éventuel dès le chargement de la page (sans appliquer automatiquement)
    detectCanvasDraft();
    
    // Fonction pour marquer les données comme modifiées
    function markAsDirty() {
        isDirty = true;
        updateSaveIndicator();
        
        // Sauvegarde automatique après 2 secondes d'inactivité
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        saveTimeout = setTimeout(function() {
            savePendingOperations();
        }, 2000);
    }
    
    // Fonction pour mettre à jour l'indicateur de sauvegarde
    function updateSaveIndicator() {
        var $indicator = $('#todo-save-indicator');
        if (isDirty) {
            $indicator.show().text('Modifications non sauvegardées');
        } else {
            $indicator.hide();
        }
    }
    
    // Fonction pour sauvegarder toutes les opérations en attente
    function savePendingOperations() {
        if (!isDirty) return Promise.resolve();
        
        var hasOperations = pendingOperations.add.length > 0 || 
                          pendingOperations.update.length > 0 || 
                          pendingOperations.delete.length > 0 || 
                          pendingOperations.toggle.length > 0;
        
        if (!hasOperations) {
            isDirty = false;
            updateSaveIndicator();
            return Promise.resolve();
        }
        
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        var formData = {
            action: 'wp_bmc_batch_todo_operations',
            nonce: wp_bmc_ajax.nonce,
            project_id: projectId,
            operations: pendingOperations
        };
        
        return $.post(wp_bmc_ajax.ajax_url, formData).done(function(response) {
            if (response.success) {
                // Vider les opérations en attente
                pendingOperations = { add: [], update: [], delete: [], toggle: [] };
                isDirty = false;
                updateSaveIndicator();
                
                // Mettre à jour les IDs temporaires avec les vrais IDs
                if (response.data.results && response.data.results.add) {
                    response.data.results.add.forEach(function(result) {
                        if (result.temp_id && result.real_id) {
                            $('.todo-item[data-todo-id="' + result.temp_id + '"]').attr('data-todo-id', result.real_id);
                        }
                    });
                }
                
                // Vider le cache pour forcer le rechargement depuis le serveur
                todoCache = {};
                
            } 
        }).promise();
    }
    
    // Fonction pour forcer la sauvegarde immédiate
    function forceSave() {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        return savePendingOperations();
    }
    
    // ========================================
    // CRÉATION DU PREMIER CANVAS (v2.0 - désactivé)
    // ========================================
    // La création de projets est maintenant gérée par les admins
    // Les utilisateurs ne peuvent plus créer de projets directement
    
    // ========================================
    // CHANGEMENT DE VUE (SYNTHÉTIQUE/GLOBALE)
    // ========================================
    $('.view-toggle button').on('click', function() {
        var view = $(this).data('view');
        var $button = $(this);
        
        // Forcer la sauvegarde avant de changer de vue
        forceSave().then(function() {
            // Mettre à jour l'URL sans recharger la page
            var currentUrl = new URL(window.location);
            currentUrl.searchParams.set('view', view);
            window.history.pushState({}, '', currentUrl.toString());
            
            // Mettre à jour les boutons actifs
            $('.view-toggle button').removeClass('wp-bmc-btn-primary').addClass('wp-bmc-btn-secondary');
            $button.removeClass('wp-bmc-btn-secondary').addClass('wp-bmc-btn-primary');
            $('.dashboard-header-title').text($button.text() + ' du projet : ' + $('.dashboard-header').data('project-name'));
            
            // Recharger le contenu du canvas via AJAX
            loadCanvasView(view);
        });
    });
    
    // Fonction pour charger une vue du canvas via AJAX
    function loadCanvasView(view) {
        var $canvasContainer = $('.canvas-container');
        var $loadingIndicator = $('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement de la vue...</span></div>');
        
        // Afficher l'indicateur de chargement
        $canvasContainer.html($loadingIndicator);
        
        // Récupérer le project_id depuis l'attribut data du container
        var projectId = $('.wp-bmc-dashboard').data('project-id') || 
                       $('.wp-bmc-canvas-container').data('project-id');
        
        
        // Charger le contenu via AJAX
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_load_canvas_view',
            view: view,
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                $canvasContainer.html(response.data.html);
                // Réinitialiser les événements pour les nouveaux éléments
                initCanvasEvents();
                // Réinitialiser le graphique de progression
                initProgressChart();
                // Appeler la fonction updateCanvasGrid depuis public.js
                if (typeof window.WP_BMC_Public !== 'undefined' && window.WP_BMC_Public.updateCanvasGrid) {
                    window.WP_BMC_Public.updateCanvasGrid();
                }
                // Forcer la mise à jour des indicateurs de présence après le rechargement de la vue
                if (typeof window.WP_BMC_Presence !== 'undefined' && window.WP_BMC_Presence.initialized) {
                    setTimeout(function() {
                        window.WP_BMC_Presence.previousActiveUsers = [];
                        window.WP_BMC_Presence.updateSectionIndicators();
                    }, 100);
                }
            } else {
                $canvasContainer.html('<div class="wp-bmc-error">Erreur lors du chargement de la vue.</div>');
            }
        }).fail(function(xhr, status, error) {
            $canvasContainer.html('<div class="wp-bmc-error">Erreur de connexion. Veuillez réessayer.</div>');
        });
    }
    // Fonction pour initialiser les événements du canvas
    function initCanvasEvents() {
        // Réattacher les événements aux boutons d'édition
        $('.edit-brick-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var sectionName = $(this).data('section');
            var sectionTitle = getSectionTitle(sectionName);
            var sectionPlaceholder = getSectionPlaceholder(sectionName);
            var currentContent = $('[data-section="' + sectionName + '"] .canvas-content').html();
            
            openEditView(sectionName, sectionTitle, sectionPlaceholder, currentContent);
        });
    }
    
    // ========================================
    // VUE D'ÉDITION DES BRIQUES
    // ========================================
    
    // Gérer le clic sur les boutons d'édition
    $(document).on('click', '.edit-brick-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var sectionName = $(this).data('section');
        var sectionTitle = getSectionTitle(sectionName);
        var sectionPlaceholder = getSectionPlaceholder(sectionName);
        
        // Charger le contenu depuis la base de données pour être sûr d'avoir la dernière version
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
        
        // Afficher un loader pendant le chargement
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.post(ajaxUrl, {
            action: 'wp_bmc_get_canvas',
            nonce: nonce,
            project_id: projectId
        }, function(response) {
            $btn.html(originalHtml).prop('disabled', false);
            
            var currentContent = '';
            if (response.success && response.data && response.data.canvas_data) {
                currentContent = response.data.canvas_data[sectionName] || '';
                
                if (response.data.canvas_versions) {
                    mergeCanvasVersions(response.data.canvas_versions);
                }
            } else {
                // Fallback sur le contenu du DOM si l'AJAX échoue
                currentContent = $('[data-section="' + sectionName + '"] .canvas-content').html();
            }
        
        openEditView(sectionName, sectionTitle, sectionPlaceholder, currentContent);
        }).fail(function() {
            $btn.html(originalHtml).prop('disabled', false);
            // En cas d'erreur, utiliser le contenu du DOM
            var currentContent = $('[data-section="' + sectionName + '"] .canvas-content').html();
            openEditView(sectionName, sectionTitle, sectionPlaceholder, currentContent);
        });
    });
    
    // Ouvrir la vue d'édition
    function openEditView(sectionName, sectionTitle, sectionPlaceholder, content) {
        // Sauvegarder les todos de la section actuelle avant de changer
        forceSave().then(function() {
            const mainElement = document.querySelector('.wp-bmc-dashboard');

            mainElement?.scrollIntoView({ behavior: "smooth" })

            // Définir la section actuellement éditée (priorité à la variable globale)
            currentEditingSection = sectionName;
        
            // Masquer le contenu principal
            $('.wp-bmc-dashboard .canvas-controls').hide();
            $('.wp-bmc-dashboard .canvas-container').hide();
            $('.wp-bmc-dashboard .wp-bmc-edit-view').show();
            $('.dashboard-header-title').text('Bloc projet : ' + $('.dashboard-header').data('project-name'));
            
            // Mettre à jour le contenu de la vue d'édition
            $('#edit-section-title').text(sectionTitle);
            $('#edit-section-placeholder').html(sectionPlaceholder);
            $('#wp-bmc-edit-view').attr('data-section', sectionName);
            
            // Mettre à jour le titre des révisions pour cette brique spécifique
            // $('#revisions-section-title').text(`Révisions de "${sectionTitle}"`);
            
            // Debug: vérifier que l'attribut est bien défini
            log('Vue d\'édition ouverte pour la section:', sectionName);
            log('Variable globale définie:', currentEditingSection);
            log('Attribut data-section défini:', $('#wp-bmc-edit-view').attr('data-section'));
            
            // Initialiser l'éditeur WYSIWYG
            let decodedContent = cleanContent(content);
            $('#wp-bmc-edit-view').data('original-content', decodedContent || '');

            initWysiwygEditor(decodedContent);
            
            // Charger les fichiers de la section
            loadSectionFiles(sectionName);
            
            // Charger les documents de référence
            loadReferenceDocuments(sectionName);
            
            // Charger les todos de la section
            loadSectionTodos(sectionName);

            // Charger les révisions de la section
            loadSectionRevisions(sectionName);

            loadSectionRating(sectionName);
            
            // Vérifier si la section a une demande de notation en attente
            checkGradingRequestStatus(sectionName);
            
            // Vérifier si la section a une demande de notation en attente (mode admin uniquement)
                var $rateBtn = $('#inner-rate-brick-btn');
            if ($rateBtn.length > 0) {
                // Vérifier d'abord avec la variable globale si disponible
                var hasPendingRequest = false;
                if (typeof wp_bmc_pending_grading_requests !== 'undefined') {
                    hasPendingRequest = wp_bmc_pending_grading_requests.indexOf(sectionName) !== -1;
                }
                
                // Vérifier aussi via AJAX pour être sûr (mode admin)
                var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
                var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
                var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
                var action = (typeof wp_bmc_admin_ajax !== 'undefined') ? 'wp_bmc_check_grading_request_admin' : 'wp_bmc_check_grading_request';
                
                $.post(ajaxUrl, {
                    action: action,
                    nonce: nonce,
                    section: sectionName,
                    project_id: projectId
                }, function(response) {
                    if (response.success && response.data.has_pending_request) {
                        hasPendingRequest = true;
                    }
                    
                    // Mettre à jour le bouton selon le résultat
                if (hasPendingRequest) {
                    $rateBtn.prop('disabled', false).removeClass('disabled');
                    $rateBtn.attr('title', 'Cliquer pour noter cette section');
                } else {
                    $rateBtn.prop('disabled', true).addClass('disabled');
                    $rateBtn.attr('title', 'Aucune demande d\'avis en attente pour cette section');
                }
                }).fail(function() {
                    // En cas d'erreur, utiliser la variable globale si disponible
                    if (typeof wp_bmc_pending_grading_requests !== 'undefined') {
                        hasPendingRequest = wp_bmc_pending_grading_requests.indexOf(sectionName) !== -1;
                        if (hasPendingRequest) {
                            $rateBtn.prop('disabled', false).removeClass('disabled');
                            $rateBtn.attr('title', 'Cliquer pour noter cette section');
                        } else {
                            $rateBtn.prop('disabled', true).addClass('disabled');
                            $rateBtn.attr('title', 'Aucune demande d\'avis en attente pour cette section');
                        }
                    }
                });
            }
            
            // Réinitialiser la liste des révisions pour cette brique
            $('#revisions-list').html(`
                <div class="no-revisions">
                    <i class="fas fa-history"></i>
                    <p>Aucune révision disponible pour cette brique</p>
                    <small>Les révisions sont créées automatiquement lors des demandes de notation</small>
                </div>
            `);
            
            // Afficher la vue d'édition
            $('#wp-bmc-edit-view').fadeIn(300);
        });
    }
    
    // Fermer la vue d'édition
    function closeEditView() {
        // Sauvegarder les todos avant de fermer
        forceSave().then(function() {
            // Réinitialiser la section actuellement éditée
            currentEditingSection = '';
            $('#wp-bmc-edit-view').removeData('original-content');
            
            $('#wp-bmc-edit-view').fadeOut(300);
            
            // Réafficher le contenu principal
            $('.wp-bmc-dashboard .canvas-controls').show();
            $('.wp-bmc-dashboard .canvas-container').show();

            $('.wp-bmc-dashboard .wp-bmc-edit-view').hide();

            $('.dashboard-header-title').text($('.view-toggle button.wp-bmc-btn-primary').text() + ' du projet : ' + $('.dashboard-header').data('project-name'));
            
            // Détruire l'éditeur WYSIWYG
            if (window.wysiwygEditor) {
                window.wysiwygEditor.remove();
                window.wysiwygEditor = null;
            }
        });
    }
    
    // Gestionnaires d'événements pour la vue d'édition
    $(document).ready(function() {
        // Bouton de retour au tableau de bord
        $('#back-to-dashboard').on('click', function() {
            closeEditView();
        });
        
        // Bouton d'annulation
        $('#edit-cancel').on('click', function() {
            closeEditView();
        });
        
        // Sauvegarder le contenu
        $('#edit-save').on('click', function() {
            var $btn = $(this);
            var originalText = $btn.text();
            var originalHtml = $btn.html();
            
            // Désactiver le bouton et changer le texte
            $btn.prop('disabled', true)
                .html('<span class="wp-bmc-loader-inline"></span> Sauvegarde...');
            
            // Sauvegarder avec callback pour restaurer le bouton
            saveBrickContent(function(success, details) {
                // Restaurer le bouton après la sauvegarde
                $btn.prop('disabled', false).html(originalHtml);
                
                if (success) {
                    // Optionnel : afficher un toast de succès
                    WP_BMC_Toast.success('Contenu sauvegardé avec succès !');
                    
                    if (
                        details &&
                        details.contentChanged &&
                        isAdminContext() &&
                        details.projectId &&
                        details.section
                    ) {
                        $(document).trigger('wp_bmc:admin_section_saved', [details]);
                    }
                } else {
                    // Optionnel : afficher un toast d'erreur
                    WP_BMC_Toast.error('Erreur lors de la sauvegarde');
                }
            });
        });
        
        // Demander notation
        $('#request-grading').on('click', function() {
            requestGrading();
        });
        
        // Ajouter des fichiers
        $('#add-file-btn').on('click', function() {
            openFileUploader();
        });
        
        // Consulter les documents
        $('#view-documents-btn').on('click', function() {
            const sectionName = $('#wp-bmc-edit-view').attr('data-section');
            window.open('/ressources-pedagogiques?section=' + sectionName.toLowerCase(), '_blank');
        });
        
        //  Fermer la popup des documents
         $('#documents-popup-close, #wp-bmc-documents-popup .popup-overlay').on('click', function() {
             $('#wp-bmc-documents-popup').fadeOut(300);
         });
         
         // Gérer les actions sur les fichiers
         $(document).on('click', '.file-action-btn', function() {
             var action = $(this).data('action');
             var fileId = $(this).data('file-id');
             
             if (action === 'view') {
                 // Ouvrir le fichier dans un nouvel onglet
                 var fileUrl = $(this).closest('.file-item').find('.file-name').data('url');
                 if (fileUrl) {
                     window.open(fileUrl, '_blank');
                 }
             } else if (action === 'delete') {
                 if (confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')) {
                     deleteFile(fileId);
                 }
             }
         });
     });
    
    // Initialiser l'éditeur WYSIWYG
    function initWysiwygEditor(content) {
        // Utiliser TinyMCE si disponible, sinon un éditeur simple
        if (typeof tinymce !== 'undefined') {
            if (tinymce.get('wysiwyg-editor')) {
                tinymce.get('wysiwyg-editor').remove();
            }

            tinymce.init({
                selector: '#wysiwyg-editor',
                height: 300,
                menubar: true,
                plugins: ["image", "code", "table", "link", "media", "codesample", "lists"],
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | customremoveformat customimageupload | help',
            
                content_style: 'body { font-family: urbanist, sans-serif; font-size: 14px; }',
                paste_as_text: true,
                promotion: false,
                branding: false,
                statusbar: false,
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                setup: function(editor) {
                    window.wysiwygEditor = editor;
                    editor.on('init', function() {
                        editor.setContent(content || '');
                    });
                    
                    editor.on('change keyup input', function() {
                        persistCanvasDraft();
                        hasUnsavedDraft = true;
                    });
                    
                    editor.ui.registry.addButton('customremoveformat', {
                        icon: 'remove-formatting',
                        tooltip: 'Supprimer tout le formatage et les listes',
                        onAction: function() {
                            editor.execCommand('RemoveFormat');
                            editor.execCommand('RemoveList');
                        }
                    });
                    
                    editor.ui.registry.addButton('customimageupload', {
                        icon: 'image',
                        tooltip: 'Insérer une image',
                        onAction: function() {
                            openTinyMCEImageUpload(editor);
                        }
                    });
                }
            });
        } else {
            // Éditeur simple en fallback
            var simpleEditor = `
                <div class="simple-editor">
                    <div class="editor-toolbar">
                        <button type="button" class="toolbar-btn" data-command="bold"><i class="fas fa-bold"></i></button>
                        <button type="button" class="toolbar-btn" data-command="italic"><i class="fas fa-italic"></i></button>
                        <button type="button" class="toolbar-btn" data-command="underline"><i class="fas fa-underline"></i></button>
                        <button type="button" class="toolbar-btn" data-command="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                        <button type="button" class="toolbar-btn" data-command="insertOrderedList"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <div class="editor-content" contenteditable="true">${content || ''}</div>
                </div>
            `;
            
            $('#wysiwyg-editor').html(simpleEditor);
            
            // Gérer les boutons de la toolbar
            // $('.toolbar-btn').on('click', function() {
            //     var command = $(this).data('command');
            //     document.execCommand(command, false, null);
            // });
            
            $('.editor-content').on('input keyup', function() {
                persistCanvasDraft();
                hasUnsavedDraft = true;
            });
            
            // Gérer le collage pour supprimer la mise en forme (Ctrl+V = Ctrl+Shift+V)
            // $('.editor-content').on('paste', function(e) {
            //     e.preventDefault();
                
            //     // Récupérer le texte brut depuis le presse-papiers
            //     var text = '';
            //     if (e.originalEvent.clipboardData || e.originalEvent.clipboardData) {
            //         text = (e.originalEvent || e).clipboardData.getData('text/plain');
            //     } else if (window.clipboardData) {
            //         text = window.clipboardData.getData('Text');
            //     }
                
            //     // Insérer le texte brut
            //     if (document.queryCommandSupported('insertText')) {
            //         document.execCommand('insertText', false, text);
            //     } else {
            //         // Fallback pour les navigateurs plus anciens
            //         document.execCommand('paste', false, text);
            //     }
                
            // });
        }
    }
    

    function cleanContent(str) {
        if (!str) return '';
        // 1. Supprimer les backslashes inutiles
        str = str.replace(/\\"/g, '"');
        str = str.replace(/\\\\/g, '\\');
        
        // 2. Décoder les entités HTML
        var txt = document.createElement("textarea");
        txt.innerHTML = str;
        return txt.value;
    }
    
    function normalizeContent(str) {
        return window.WP_BMC_Utils ? window.WP_BMC_Utils.normalizeContent(str) : '';
    }

    function hasContentChanged(originalContent, newContent) {
        return window.WP_BMC_Utils ? window.WP_BMC_Utils.hasContentChanged(originalContent, newContent) : false;
    }
    
    // ========================================
    // UPLOAD D'IMAGES POUR TINYMCE V2.0
    // ========================================
    
    function openTinyMCEImageUpload(editor) {
        var uploadedImageUrl = null;
        var uploadedImageFile = null;
        
        editor.windowManager.open({
            title: 'Insérer une image',
            body: {
                type: 'panel',
                items: [
                    {
                        type: 'htmlpanel',
                        html: `
                            <div id="tinymce-image-upload-container" style="text-align: center; padding: 20px;">
                                <div id="tinymce-drop-zone" style="
                                    border: 2px dashed #ccc;
                                    border-radius: 8px;
                                    padding: 40px;
                                    margin-bottom: 15px;
                                    cursor: pointer;
                                    transition: all 0.3s;
                                    background: #fafafa;
                                ">
                                    <svg width="48" height="48" style="margin-bottom: 10px; opacity: 0.5;" viewBox="0 0 48 48">
                                        <path d="M24 16v16m-8-8h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <rect x="8" y="8" width="32" height="32" rx="4" stroke="currentColor" stroke-width="2" fill="none"/>
                                    </svg>
                                    <p style="margin: 10px 0; color: #666; font-weight: 500;">Glissez-déposez une image ici</p>
                                    <p style="margin: 5px 0; color: #999; font-size: 12px;">ou</p>
                                    <button type="button" id="tinymce-select-file-btn" style="
                                        padding: 8px 16px;
                                        background: #2196F3;
                                        color: white;
                                        border: none;
                                        border-radius: 4px;
                                        cursor: pointer;
                                        margin-top: 10px;
                                        font-size: 14px;
                                    ">Sélectionner un fichier</button>
                                    <input type="file" id="tinymce-file-input" accept="image/*" style="display: none;">
                                </div>
                                <div id="tinymce-preview-container" style="display: none; margin-top: 15px;">
                                    <img id="tinymce-preview-image" style="max-width: 100%; max-height: 300px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <p id="tinymce-file-name" style="margin-top: 10px; color: #666; font-size: 14px;"></p>
                                    <div id="tinymce-upload-progress" style="display: none; margin-top: 10px;">
                                        <div style="background: #e0e0e0; border-radius: 10px; height: 6px; overflow: hidden;">
                                            <div id="tinymce-progress-bar" style="background: #2196F3; height: 100%; width: 0%; transition: width 0.3s;"></div>
                                        </div>
                                        <p style="margin-top: 5px; color: #666; font-size: 12px;">Upload en cours...</p>
                                    </div>
                                </div>
                            </div>
                        `
                    }
                ]
            },
            buttons: [
                {
                    type: 'cancel',
                    text: 'Annuler'
                },
                {
                    type: 'submit',
                    text: 'Insérer',
                    primary: true,
                    enabled: false,
                    name: 'insert-btn'
                }
            ],
            onSubmit: function(api) {
                if (uploadedImageUrl) {
                    var filename = uploadedImageFile ? uploadedImageFile.name : 'Image';
                    editor.insertContent('<img src="' + uploadedImageUrl + '" alt="' + filename + '" style="max-width: 100%; height: auto;" />');
                    WP_BMC_Toast.success('Image insérée avec succès');
                }
                api.close();
            }
        });
        
        setTimeout(function() {
            setupTinyMCEImageUploadHandlers(editor, function(url, file) {
                uploadedImageUrl = url;
                uploadedImageFile = file;
            });
        }, 100);
    }
    
    function setupTinyMCEImageUploadHandlers(editor, onImageUploaded) {
        var dropZone = document.getElementById('tinymce-drop-zone');
        var fileInput = document.getElementById('tinymce-file-input');
        var selectBtn = document.getElementById('tinymce-select-file-btn');
        var previewContainer = document.getElementById('tinymce-preview-container');
        var previewImage = document.getElementById('tinymce-preview-image');
        var fileName = document.getElementById('tinymce-file-name');
        var uploadProgress = document.getElementById('tinymce-upload-progress');
        var progressBar = document.getElementById('tinymce-progress-bar');
        
        if (!dropZone || !fileInput) return;
        
        selectBtn.addEventListener('click', function(e) {
            e.preventDefault();
            fileInput.click();
        });
        
        dropZone.addEventListener('click', function(e) {
            if (e.target.id !== 'tinymce-select-file-btn') {
                fileInput.click();
            }
        });
        
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                handleTinyMCEImageFile(file);
            }
        });
        
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#2196F3';
            dropZone.style.backgroundColor = '#e3f2fd';
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#fafafa';
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropZone.style.borderColor = '#ccc';
            dropZone.style.backgroundColor = '#fafafa';
            
            var file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                handleTinyMCEImageFile(file);
            }
        });
        
        function handleTinyMCEImageFile(file) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                fileName.textContent = file.name;
                previewContainer.style.display = 'block';
                uploadProgress.style.display = 'block';
                progressBar.style.width = '0%';
                
                uploadImageToServer(file, function(url) {
                    progressBar.style.width = '100%';
                    setTimeout(function() {
                        uploadProgress.style.display = 'none';
                    }, 500);
                    
                    onImageUploaded(url, file);
                    
                    var dialog = document.querySelector('.tox-dialog');
                    if (dialog) {
                        var insertBtn = dialog.querySelector('button[title="Insérer"]');
                        if (insertBtn) {
                            insertBtn.disabled = false;
                        }
                    }
                });
            };
            
            reader.readAsDataURL(file);
        }
        
        function uploadImageToServer(file, callback) {
            var sectionName = $('#wp-bmc-edit-view').attr('data-section') || 'editor_images';
            var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
            
            var formData = new FormData();
            formData.append('action', 'wp_bmc_upload_file');
            formData.append('nonce', wp_bmc_ajax.nonce);
            formData.append('section', sectionName);
            formData.append('files[]', file);
            
            if (projectId) {
                formData.append('project_id', projectId);
            }
            
            $.ajax({
                url: wp_bmc_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var percentComplete = (e.loaded / e.total) * 100;
                            progressBar.style.width = percentComplete + '%';
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success && response.data && response.data.files && response.data.files.length > 0) {
                        var uploadedFile = response.data.files[0];
                        callback(uploadedFile.url);
                    } else {
                        WP_BMC_Toast.error('Erreur lors de l\'upload de l\'image');
                    }
                },
                error: function() {
                    WP_BMC_Toast.error('Erreur lors de l\'upload de l\'image');
                }
            });
        }
    }

    
         // Supprimer un fichier
     function deleteFile(fileId) {
         var formData = {
             action: 'wp_bmc_delete_file',
             nonce: wp_bmc_ajax.nonce,
             file_id: fileId
         };
         
         $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
             if (response.success) {
                 // Recharger la liste des fichiers
                 var sectionName = $('#wp-bmc-edit-view').attr('data-section');
                 loadSectionFiles(sectionName);
                 WP_BMC_Toast.success('Fichier supprimé avec succès !');
             } else {
                 WP_BMC_Toast.error(response.data);
             }
         });
     }
     
     // Sauvegarder le contenu de la brique
    function saveBrickContent(callback) {
        
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        
        var content = '';
        
        if (window.wysiwygEditor) {
            content = window.wysiwygEditor.getContent();
        } else {
            content = $('.editor-content').html();
        }

        var originalContent = $('#wp-bmc-edit-view').data('original-content') || '';
        var contentChanged = hasContentChanged(originalContent, content);
        
        // Mettre à jour le contenu dans le canvas
        $('[data-section="' + sectionName + '"] .canvas-content').html(content);
        
        // Si un callback est fourni, sauvegarder via AJAX et appeler le callback
        if (callback) {
            var sectionTitle = getSectionTitle(sectionName);
            
            var canvasData = {};
            $('.canvas-content').each(function() {
                var section = $(this).closest('[data-section]').data('section');
                canvasData[section] = $(this).html();
            });
            
            var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
            
            // Utiliser le nonce admin si disponible, sinon le nonce public
            var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
            var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
            
            var formData = {
                action: 'wp_bmc_save_canvas',
                nonce: nonce,
                project_id: projectId,
                canvas_data: canvasData,
                canvas_versions: getCanvasVersionsPayload()
            };
            
            var callbackDetails = {
                contentChanged: contentChanged,
                projectId: projectId,
                section: sectionName,
                sectionTitle: sectionTitle
            };
            
            $.post(ajaxUrl, formData, function(response) {
                if (response.success) {
                    updateLastSavedTime();
                    if (response.data && response.data.updated_versions) {
                        mergeCanvasVersions(response.data.updated_versions);
                    }
                    clearCanvasDraft();
                    
                    // Recharger le contenu filtré depuis le serveur
                    var reloadFormData = {
                        action: 'wp_bmc_get_canvas',
                        nonce: nonce,
                        project_id: projectId
                    };
                    
                    $.post(ajaxUrl, reloadFormData, function(reloadResponse) {
                        var filteredContent = '';
                        if (reloadResponse.success && reloadResponse.data.canvas_data) {
                            filteredContent = reloadResponse.data.canvas_data[sectionName] || '';
                            
                            if (reloadResponse.data.canvas_versions) {
                                mergeCanvasVersions(reloadResponse.data.canvas_versions);
                            }
                            
                            clearCanvasDraft();
                            
                            // Mettre à jour le WYSIWYG avec le contenu filtré
                            if (window.wysiwygEditor) {
                                window.wysiwygEditor.setContent(filteredContent);
                            }
                            
                            // Mettre à jour aussi le contenu dans le canvas visible
                            $('[data-section="' + sectionName + '"] .canvas-content').html(filteredContent);
                        }
                        
                        var updatedContent = filteredContent || content;
                        $('#wp-bmc-edit-view').data('original-content', updatedContent);
                        
                        callback(true, callbackDetails); // Indiquer que la sauvegarde a réussi
                    }).fail(function() {
                        // Même si le rechargement échoue, la sauvegarde a réussi
                        $('#wp-bmc-edit-view').data('original-content', content);
                        callback(true, callbackDetails);
                    });
                } else {
                    const errorMessage = extractErrorMessage(response, 'Erreur lors de la sauvegarde.');
                    WP_BMC_Toast.error(errorMessage);
                    callback(false, callbackDetails); // Indiquer que la sauvegarde a échoué
                }
            }).fail(function(xhr, status, error) {
                WP_BMC_Toast.error('Erreur lors de la sauvegarde. Veuillez réessayer.');
                callback(false, callbackDetails); // Indiquer que la sauvegarde a échoué
            });
        } else {
            autoSaveCanvas();
        }
        
    }
    
    // Charger les fichiers de la section
    function loadSectionFiles(sectionName) {
        
        var formData = {
            action: 'wp_bmc_get_section_files',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Ajouter le project_id si disponible (pour les admins)
        var projectId = getProjectIdFromUrl();
        if (projectId) {
            formData.project_id = projectId;
        }
        
        // Afficher le loader pour la liste de fichiers
        $('#files-list').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des fichiers...</span></div>');
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            
            if (response.success) {
                displayFiles(response.data.files);
            } else {
                // Afficher un message d'erreur
                $('#files-list').html('<div class="no-files">Aucun fichier attaché</div>');
            }
        }).fail(function() {
            // Afficher un message d'erreur
            $('#files-list').html('<div class="no-files">Erreur de connexion</div>');
        });
    }
    
    // Charger les documents de référence
    function loadReferenceDocuments(sectionName) {
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };

        // Afficher le loader pour la liste de documents
        $('#documents-list').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des documents...</span></div>');
        
        // Afficher le loader pour la grille de documents dans la popup
        $('#documents-grid').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des documents...</span></div>');

        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            
            if (response.success) {
                displayReferenceDocuments(response.data.documents, sectionName);
            } else {
                // Afficher un message d'erreur
                $('#documents-list').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
                $('#documents-grid').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
            }
        }).fail(function() {
            // Afficher un message d'erreur
            $('#documents-list').html('<div class="no-documents">Erreur de connexion</div>');
            $('#documents-grid').html('<div class="no-documents">Erreur de connexion</div>');
        });
    }
    
    // Afficher les documents de référence
    function displayReferenceDocuments(documents, sectionName) {
        var documentsHtml = '';
        
        if (documents && documents.length > 0) {
            documents.forEach(function(doc) {
                // Filtrer par catégorie (all ou section spécifique)
                if (doc.category === 'all' || doc.category === sectionName) {
                    documentsHtml += `
                        <div class="document-item" data-document-id="${doc.id}">
                            <div class="document-icon">
                                <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                            </div>
                            <div class="document-info">
                                <div class="document-title">${doc.title}</div>
                                <div class="document-description">${doc.description || ''}</div>
                                <div class="document-size">${formatFileSize(doc.file_size)}</div>
                            </div>
                            <div class="document-actions">
                                <a href="${doc.url}" target="_blank" class="document-action-btn" title="Voir le document">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }
            });
        }
        
        if (documentsHtml === '') {
            documentsHtml = '<div class="no-documents">Aucun document de référence disponible pour cette section</div>';
        }
        
        $('#documents-list').html(documentsHtml);
    }
    
         // Afficher les fichiers
     function displayFiles(files) {
         var filesHtml = '';
         
         if (files && files.length > 0) {
             files.forEach(function(file) {
                 filesHtml += `
                     <div class="file-item" data-file-id="${file.id}">
                         <div class="file-icon">
                             <i class="fas fa-${getFileIcon(file.file_type)}"></i>
                         </div>
                         <div class="file-info">
                             <div class="file-name" data-url="${file.url}">${file.original_name}</div>
                             <div class="file-size">${formatFileSize(file.file_size)}</div>
                         </div>
                         <div class="file-actions">
                             <button class="file-action-btn" data-action="view" data-file-id="${file.id}">
                                 <i class="fas fa-eye"></i>
                             </button>
                             <button class="file-action-btn" data-action="delete" data-file-id="${file.id}">
                                 <i class="fas fa-trash"></i>
                             </button>
                         </div>
                     </div>
                 `;
             });
         } else {
             filesHtml = '<div class="no-files">Aucun fichier attaché</div>';
         }
         
         $('#files-list').html(filesHtml);
     }
    
    // Récupérer l'ID du projet depuis l'URL
    function getProjectIdFromUrl() {
        return window.WP_BMC_Utils ? window.WP_BMC_Utils.getProjectId() : null;
    }
    
    // ========================================
    // VÉRIFICATION D'ACCÈS AU PROJET (v2.0)
    // ========================================
    function checkProjectAccess(projectId) {
        if (!projectId) {
            return false;
        }
        
        // Vérifier si l'utilisateur a accès à ce projet
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_check_project_access',
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (!response.success) {
                WP_BMC_Toast.error('Vous n\'avez pas accès à ce projet.');
                // Rediriger vers le dashboard
                setTimeout(function() {
                    window.location.href = home_url('/dashboard/');
                }, 2000);
                return false;
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de vérification d\'accès.');
        });
        
        return true;
    }
    
    // Ouvrir l'uploader de fichiers
    function openFileUploader() {
        
        // Vérifier que la vue d'édition est ouverte
        if (!$('#wp-bmc-edit-view').is(':visible')) {
            WP_BMC_Toast.warning('Veuillez d\'abord ouvrir une section pour éditer');
            return;
        }
        
        // Créer un input file caché
        var $input = $('<input type="file" multiple accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;">');
        $('body').append($input);
        
        $input.on('change', function() {
            var files = this.files;
            uploadFiles(files);
            $input.remove();
        });
        
        $input.click();
    }
    
    // Uploader les fichiers
    function uploadFiles(files) {
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        
        // Debug: vérifier si la section est définie
        if (!sectionName) {
            WP_BMC_Toast.error('Erreur: Impossible de déterminer la section pour l\'upload');
            return;
        }
        
        var formData = new FormData();
        
        formData.append('action', 'wp_bmc_upload_file');
        formData.append('nonce', wp_bmc_ajax.nonce);
        formData.append('section', sectionName);
        
        // Ajouter le project_id si disponible (pour les admins)
        var projectId = getProjectIdFromUrl();
        if (projectId) {
            formData.append('project_id', projectId);
        }
        
        for (var i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        
        // Ajouter un indicateur de chargement
        var $addBtn = $('#add-file-btn');
        var originalText = $addBtn.html();
        $addBtn.html('<i class="fas fa-spinner fa-spin"></i> Upload en cours...').prop('disabled', true);
        
        $.ajax({
            url: wp_bmc_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                
                if (response.success) {
                    loadSectionFiles(sectionName);
                    
                    // Afficher le message de succès
                    WP_BMC_Toast.success('Fichiers uploadés avec succès !');
                } else {
                    WP_BMC_Toast.error('Erreur lors de l\'upload : ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                WP_BMC_Toast.error('Erreur lors de l\'upload des fichiers.');
            },
            complete: function() {
                // Restaurer le bouton
                $addBtn.html(originalText).prop('disabled', false);
            }
        });
    }
    
    // Ouvrir le viewer de documents
    function openDocumentsViewer() {
        
        var $btn = $('#view-documents-btn');
        
        // Récupérer la section depuis l'attribut data-section de la vue d'édition
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        
        if (!sectionName) {
            WP_BMC_Toast.error('Erreur: Impossible de déterminer la section pour charger les documents');
            return;
        }
        
        
        // Ajouter l'état de chargement au bouton
        $btn.addClass('btn-loading').prop('disabled', true);
        var originalText = $btn.html();
        $btn.html('<span class="btn-text">' + originalText + '</span>');
        
        var documentsHtml = `
            <div id="wp-bmc-documents-popup" class="wp-bmc-popup">
                <div class="popup-overlay"></div>
                <div class="popup-content">
                    <div class="popup-header">
                        <h3>Documents de référence</h3>
                        <button class="popup-close">&times;</button>
                    </div>
                    
                    <div class="popup-body">
                        <div class="documents-grid" id="documents-grid">
                            <!-- Les documents seront chargés ici -->
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter la popup au DOM si elle n'existe pas
        if (!$('#wp-bmc-documents-popup').length) {
            $('body').append(documentsHtml);
        }
        
        // Ouvrir la popup
        $('#wp-bmc-documents-popup').fadeIn(300);
        
        // Charger les documents avec la section
        loadDocuments(sectionName);
        
        // Retirer l'état de chargement après un délai
        setTimeout(function() {
            $btn.removeClass('btn-loading').prop('disabled', false).html(originalText);
        }, 1000);
    }
    
    // Charger les documents
    function loadDocuments(sectionName) {
        
        var formData = {
            action: 'wp_bmc_get_documents',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        };
        
        // Afficher le loader pour la grille de documents
        $('#documents-grid').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des documents...</span></div>');
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            
            if (response.success) {
                displayDocuments(response.data.documents);
            } else {
                // Afficher un message d'erreur
                $('#documents-grid').html('<div class="no-documents">Erreur lors du chargement des documents</div>');
            }
        }).fail(function() {
            // Afficher un message d'erreur
            $('#documents-grid').html('<div class="no-documents">Erreur de connexion</div>');
        });
    }
    
         // Afficher les documents
     function displayDocuments(documents) {
         
         var documentsHtml = '';
         
         if (documents && documents.length > 0) {
             documents.forEach(function(doc) {
                 documentsHtml += `
                     <div class="document-item" data-doc-id="${doc.id}">
                         <div class="document-icon">
                             <i class="fas fa-${getFileIcon(doc.file_type)}"></i>
                         </div>
                         <div class="document-info">
                             <div class="document-title">${doc.title}</div>
                             <div class="document-description">${doc.description}</div>
                         </div>
                         <div class="document-actions">
                             <button class="document-action-btn" data-action="view" data-doc-id="${doc.id}">
                                 <i class="fas fa-eye"></i> Voir
                             </button>
                         </div>
                     </div>
                 `;
             });
         } else {
             documentsHtml = '<div class="no-documents">Aucun document disponible</div>';
         }
         
         $('#documents-grid').html(documentsHtml);
     }
    
    // ========================================
    // DEMANDE DE NOTATION
    // ========================================
    
    // Vérifier si une demande de notation est en attente pour cette section
    function checkGradingRequestStatus(sectionName) {
        var $btn = $('#request-grading');
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_check_grading_request',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName
        }, function(response) {
            if (response.success && response.data.has_pending_request) {
                // Une demande est en attente, changer le style du bouton
                $btn.text('Demande envoyée')
                    .addClass('wp-bmc-btn-success')
                    .removeClass('wp-bmc-btn-warning')
                    .prop('disabled', true);
            } else {
                // Pas de demande en attente, réinitialiser le bouton
                $btn.text('Demander un avis')
                    .removeClass('wp-bmc-btn-success')
                    .addClass('wp-bmc-btn-warning')
                    .prop('disabled', false);
            }
        });
    }
    
    function requestGrading() {
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        var sectionTitle = getSectionTitle(sectionName);
        var $btn = $('#request-grading');
        var originalText = $btn.text();
        
        // Vérifier d'abord si une demande n'est pas déjà en attente
        if ($btn.hasClass('wp-bmc-btn-success')) {
            WP_BMC_Toast.info('Une demande d\'avis est déjà en attente pour cette section.');
            return;
        }
        
        // Confirmation avant envoi
        if (!confirm('Êtes-vous sûr de vouloir demander un avis pour cette section ? L\'administrateur sera notifié.')) {
            return;
        }
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Sauvegarde en cours...');
        
        // Sauvegarder d'abord le contenu actuel
        saveBrickContent(function(saveSuccess) {
            
            if (!saveSuccess) {
                WP_BMC_Toast.error('Erreur lors de la sauvegarde. Impossible de demander un avis.');
                $btn.prop('disabled', false).text(originalText);
                return;
            }
            
            // Mettre à jour le texte du bouton
            $btn.text('Envoi de la demande...');
            
            var formData = {
                action: 'wp_bmc_request_grading',
                nonce: wp_bmc_ajax.nonce,
                section: sectionName,
                section_title: sectionTitle
            };
            
            $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    WP_BMC_Toast.success('Demande d\'avis envoyée avec succès ! L\'administrateur a été notifié.');
                    
                    // Changer le texte du bouton pour indiquer que la demande a été envoyée
                    $btn.text('Demande envoyée').addClass('wp-bmc-btn-success').removeClass('wp-bmc-btn-warning');
                    
                    // Fermer la vue d'édition après un délai
                    setTimeout(function() {
                        closeEditView();
                    }, 2000);
                } else {
                    WP_BMC_Toast.error(response.data);
                    $btn.prop('disabled', false).text(originalText);
                }
            }).fail(function() {
                WP_BMC_Toast.error('Erreur lors de l\'envoi de la demande d\'avis. Veuillez réessayer.');
                $btn.prop('disabled', false).text(originalText);
            });
        });
    }
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    function getSectionTitle(sectionName) {
        // Utiliser les configurations de sections depuis PHP si disponibles
        if (typeof wp_bmc_sections_config !== 'undefined' && wp_bmc_sections_config[sectionName]) {
            return wp_bmc_sections_config[sectionName].title;
        }
        
        // Fallback sur les titres par défaut
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
        
        return titles[sectionName] || sectionName;
    }

    function getSectionPlaceholder(sectionName) {
        // Utiliser les configurations de sections depuis PHP si disponibles
        if (typeof wp_bmc_sections_config !== 'undefined' && wp_bmc_sections_config[sectionName]) {
            return wp_bmc_sections_config[sectionName].placeholder;
        }
        
        // Fallback sur les placeholders par défaut
        var placeholders = {
            'key_partners': 'Quelles sont mes principales dépenses ?',
            'key_activities': 'Quels sont les caractéristiques de ton client idéal ?',
            'key_resources': 'Quels sont les caractéristiques de ton client idéal ?',
            'value_proposition': 'Pourquoi un client choisirait-il ton offre plutôt qu\'une autre ?',
            'customer_relationships': 'Quels sont les caractéristiques de ton client idéal ?',
            'channels': 'Quels sont les caractéristiques de ton client idéal ?',
            'customer_segments': 'Comment mon projet génère-t-il de l\'argent ?',
            'cost_structure': 'Quelles sont mes principales dépenses ?',
            'revenue_streams': 'Comment mon projet génère-t-il de l\'argent ?'
        };
        
        return placeholders[sectionName] || sectionName;
    }
         function getFileIcon(fileType) {
         if (!fileType) return 'file';
         
         if (fileType.startsWith('image/')) return 'image';
         if (fileType.startsWith('video/')) return 'video';
         if (fileType.includes('pdf')) return 'file-pdf';
         if (fileType.includes('word') || fileType.includes('document')) return 'file-word';
         if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'file-excel';
         return 'file';
     }
    
         function formatFileSize(bytes) {
         if (!bytes || bytes === 0) return '0 Bytes';
         var k = 1024;
         var sizes = ['Bytes', 'KB', 'MB', 'GB'];
         var i = Math.floor(Math.log(bytes) / Math.log(k));
         return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
     }
    
    // ========================================
    // SAUVEGARDE DU CANVAS
    // ========================================
    $('#wp-bmc-save-canvas').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Sauvegarde...');
        
        // Vérifier l'ID de projet pour éviter une sauvegarde sur un mauvais canvas
        if (!projectId) {
            WP_BMC_Toast.error('Impossible de trouver le projet à sauvegarder.');
            $btn.prop('disabled', false).text(originalText);
            return;
        }
        
        // Collecter toutes les données du canvas
        var canvasData = {};
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
        });
        
        // Utiliser le nonce admin si disponible, sinon le nonce public
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
        
        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: nonce,
            project_id: projectId,
            canvas_data: canvasData,
            canvas_versions: getCanvasVersionsPayload()
        };
        
        $.post(ajaxUrl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('Canvas sauvegardé avec succès !');
                updateLastSavedTime();
                if (response.data && response.data.updated_versions) {
                    mergeCanvasVersions(response.data.updated_versions);
                }
                clearCanvasDraft();
            } else {
                const errorMessage = extractErrorMessage(response, 'Erreur lors de la sauvegarde.');
                WP_BMC_Toast.error(errorMessage);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de la sauvegarde. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // SAUVEGARDE AUTOMATIQUE
    // ========================================
    var autoSaveTimer;
    var autoSaveInterval = 30000; // 30 secondes
    
    $('.canvas-content').on('input', function() {
        // Annuler le timer précédent
        clearTimeout(autoSaveTimer);
        
        // Démarrer un nouveau timer
        autoSaveTimer = setTimeout(function() {
            autoSaveCanvas();
        }, autoSaveInterval);
        
        // Mettre à jour le statut
        $('#auto-save-status').text('Modifications détectées - Sauvegarde automatique dans 30s');
        
        // Sauvegarder un brouillon local pour éviter la perte en cas de refresh
        persistCanvasDraft();
    });
    
    function autoSaveCanvas() {
        
        // Collecter toutes les données du canvas
        var canvasData = {};
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
        });
        
        // Récupérer le project_id depuis le conteneur
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        // Utiliser le nonce admin si disponible, sinon le nonce public
        var nonce = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.nonce) ? wp_bmc_admin_ajax.nonce : wp_bmc_ajax.nonce;
        var ajaxUrl = (typeof wp_bmc_admin_ajax !== 'undefined' && wp_bmc_admin_ajax.ajax_url) ? wp_bmc_admin_ajax.ajax_url : wp_bmc_ajax.ajax_url;
        
        
        var formData = {
            action: 'wp_bmc_save_canvas',
            nonce: nonce,
            project_id: projectId,
            canvas_data: canvasData,
            canvas_versions: getCanvasVersionsPayload()
        };
        
        
        $.post(ajaxUrl, formData, function(response) {
            
            if (response.success) {
                $('#auto-save-status').text('Sauvegarde automatique activée');
                updateLastSavedTime();
                if (response.data && response.data.updated_versions) {
                    mergeCanvasVersions(response.data.updated_versions);
                }
                clearCanvasDraft();
                
                // Si on est dans la vue d'édition, recharger le contenu filtré
                var $editView = $('#wp-bmc-edit-view');
                if ($editView.is(':visible')) {
                    var sectionName = $editView.attr('data-section');
                    
                    // Recharger le contenu filtré depuis le serveur
                    var reloadFormData = {
                        action: 'wp_bmc_get_canvas',
                        nonce: nonce,
                        project_id: projectId
                    };
                    
                    $.post(ajaxUrl, reloadFormData, function(reloadResponse) {
                        if (reloadResponse.success && reloadResponse.data.canvas_data && sectionName) {
                            var filteredContent = reloadResponse.data.canvas_data[sectionName] || '';
                            
                            if (reloadResponse.data.canvas_versions) {
                                mergeCanvasVersions(reloadResponse.data.canvas_versions);
                            }
                            
                            clearCanvasDraft();
                            
                            // Mettre à jour le WYSIWYG avec le contenu filtré
                            if (window.wysiwygEditor) {
                                window.wysiwygEditor.setContent(filteredContent);
                            }
                            
                            // Mettre à jour aussi le contenu dans le canvas visible
                            $('[data-section="' + sectionName + '"] .canvas-content').html(filteredContent);
                        }
                    });
                }
                
                // Afficher le toast de succès seulement après validation
                WP_BMC_Toast.success('Contenu sauvegardé avec succès !');
            } else {
                // Afficher le toast d'erreur
                const errorMessage = extractErrorMessage(response, 'Erreur lors de la sauvegarde');
                WP_BMC_Toast.error('Erreur lors de la sauvegarde : ' + errorMessage);
            }
        }).fail(function(xhr, status, error) {
            // Afficher le toast d'erreur de connexion
            WP_BMC_Toast.error('Erreur de connexion lors de la sauvegarde');
        });
        
    }
    
    // ========================================
    // EXPORT PDF
    // ========================================
    $('#wp-bmc-export-pdf').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Génération PDF...');
        
        // Sauvegarder d'abord
        var canvasData = {};
        $('.canvas-content').each(function() {
            var section = $(this).closest('[data-section]').data('section');
            canvasData[section] = $(this).html();
        });
        
        var formData = {
            action: 'wp_bmc_export_pdf',
            nonce: wp_bmc_ajax.nonce,
            canvas_data: canvasData,
            view_mode: getCurrentViewMode()
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Télécharger le PDF
                var link = document.createElement('a');
                link.href = response.data.pdf_url;
                link.download = 'business-model-canvas.pdf';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de la génération du PDF.');
        }).always(function() {
            // Réactiver le bouton
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // GÉNÉRATION PDF AVEC GOTENBERG
    // ========================================
    $('#wp-bmc-generate-pdf').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        var projectId = $btn.data('project-id');
        
        if (!projectId) {
            WP_BMC_Toast.error('ID de projet manquant');
            return;
        }
        
        // Désactiver le bouton et afficher le loader
        $btn.prop('disabled', true).html('<div class="btn-loader"><div class="loader-spinner"></div></div>Génération PDF...');
        
        var formData = {
            action: 'wp_bmc_generate_pdf_gotenberg',
            nonce: wp_bmc_ajax.nonce,
            project_id: projectId
        };
        
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            
            if (response.success) {
                
                WP_BMC_Toast.success(response.data.message);
               
                
                // Télécharger automatiquement le PDF
                var link = document.createElement('a');
                link.href = response.data.pdf_url;
                link.download = response.data.filename;
                link.target = '_blank'; // Ouvrir dans un nouvel onglet pour debug
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
            } else {
                WP_BMC_Toast.error('Erreur: ' + response.data);
            }
        }).fail(function(xhr, status, error) {
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                WP_BMC_Toast.error('Erreur serveur: ' + (errorResponse.data || error));
            } catch (e) {
                WP_BMC_Toast.error('Erreur lors de la génération du PDF: ' + error);
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
        
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    function updateLastSavedTime() {
        var now = new Date();
        var timeString = now.getDate().toString().padStart(2, '0') + '/' + 
                       (now.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                       now.getFullYear() + ' ' + 
                       now.getHours().toString().padStart(2, '0') + ':' + 
                       now.getMinutes().toString().padStart(2, '0');
        $('#last-saved-time').text('Dernière sauvegarde : ' + timeString);
    }
    
    function getCurrentViewMode() {
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('view') || 'synthetic';
    }
    
    // ========================================
    // AUTO-RESIZE DES TEXTAREAS
    // ========================================
    $('.canvas-textarea').each(function() {
        autoResize($(this));
    });
    
    $('.canvas-content').on('input', function() {
        autoResize($(this));
    });
    
    function autoResize($textarea) {
        $textarea.css('height', 'auto');
        $textarea.css('height', $textarea[0].scrollHeight + 'px');
    }
    
    // ========================================
    // ANIMATIONS ET UX
    // ========================================
    
    
    // Focus sur le premier champ vide lors de la création
    $('#wp-bmc-create-first-canvas-form').on('submit', function() {
        var $firstEmpty = $(this).find('input[required]:invalid').first();
        if ($firstEmpty.length) {
            $firstEmpty.focus();
        }
    });
    
    // ========================================
    // RACCOURCIS CLAVIER
    // ========================================
    $(document).on('keydown', function(e) {
        // Ctrl+S pour sauvegarder
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#wp-bmc-save-canvas').click();
        }
        
        // Ctrl+E pour exporter PDF
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            $('#wp-bmc-export-pdf').click();
        }
        
        // Échap pour fermer les vues d'édition
        if (e.key === 'Escape') {
            closeEditView();
            $('#wp-bmc-documents-popup').remove();
        }
    });
    
    // ========================================
    // TOOLTIPS ET AIDE
    // ========================================
    $('.canvas-section h3, .synthetic-section h3').each(function() {
        var title = $(this).text();
        var helpText = getHelpText(title);
        
        if (helpText) {
            $(this).append('<span class="help-icon" title="' + helpText + '">?</span>');
        }
    });
    
    function getHelpText(sectionTitle) {
        var helpTexts = {
            'Proposition de valeur': 'Décrivez clairement la valeur que vous apportez à vos clients et ce qui vous différencie de vos concurrents.',
            'Segments clients': 'Identifiez vos clients cibles et leurs caractéristiques principales.',
            'Sources de revenus': 'Listez toutes les façons dont vous générez des revenus.',
            'Partenaires clés': 'Qui sont vos partenaires stratégiques essentiels ?',
            'Activités clés': 'Quelles sont les activités principales nécessaires à votre modèle économique ?',
            'Ressources clés': 'Quelles sont vos ressources les plus importantes (humaines, financières, physiques, intellectuelles) ?',
            'Relations clients': 'Comment établissez-vous et maintenez-vous les relations avec vos clients ?',
            'Canaux': 'Par quels canaux atteignez-vous vos clients ?',
            'Structure des coûts': 'Quels sont vos coûts principaux et comment sont-ils structurés ?'
        };
        
        return helpTexts[sectionTitle] || '';
    }
    
    // ========================================
    // ACTIONS ADMINISTRATEUR
    // ========================================
    
    // Voir le canvas de l'utilisateur (pour les admins)
    $(document).on('click', '.view-user-btn', function(e) {
        e.preventDefault();
        
        var userId = $(this).data('user-id');
        if (!userId) {
            return;
        }
        
        // Rediriger vers le canvas de l'utilisateur avec les paramètres admin
        var canvasUrl = window.location.origin + '/business-model-canvas/?admin_view=true&user_id=' + userId +'&view=global';
        window.open(canvasUrl, '_blank');
    });

    // Voir le canvas de l'utilisateur (pour les admins)
    $(document).on('click', '.view-project-btn', function(e) {
        e.preventDefault();
        
        var projectId = $(this).data('project-id');
        if (!projectId) {
            return;
        }
        
        // Rediriger vers le canvas de l'utilisateur avec les paramètres admin
        var canvasUrl = window.location.origin + '/business-model-canvas/?admin_view=true&project_id=' + projectId +'&view=global';
        window.open(canvasUrl, '_blank');
    });
    
    // ========================================
    // GESTION DES RÉVISIONS
    // ========================================
    
    // Charger les révisions d'une section
    function loadSectionRevisions(section) {
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        // Afficher le loader pour les révisions
        $('#revisions-list').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des révisions...</span></div>');
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_get_section_revisions',
            section: section,
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                displayRevisions(response.data.revisions, section);
            }
        }).fail(function() {
        });
    }

    function loadSectionRating(section) {
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        // Afficher le loader pour la note
        $('#rating-section').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement de la note...</span></div>');
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_get_section_rating',
            section: section,
            project_id: projectId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success && response.data && response.data.rating) {
                // Restaurer la structure HTML de la section rating avant d'afficher
                $('#rating-section').html(`
                    <div class="rating-display" id="rating-display">
                        <div class="rating-score">
                            <span class="rating-score-number" id="rating-score-number">-</span>
                            <span class="rating-score-total">10</span>
                        </div>
                        <div class="rating-comment" id="rating-comment">
                            <p class="no-rating">Aucune note attribuée</p>
                        </div>
                        <div class="rating-meta" id="rating-meta">
                            <small class="rating-date"></small>
                            <small class="rating-admin"></small>
                        </div>
                    </div>
                `);
                displaySectionRating(response.data.rating);
            } else {    
                displayNoRating();
            }
        }).fail(function() {
            $('#rating-section').html('<div class="no-rating">Erreur de connexion</div>');
        });
    }

    function displaySectionRating(rating) {
        // Vérifier que rating existe et a les propriétés nécessaires
        if (!rating || rating.rating === null || rating.rating === undefined) {
            displayNoRating();
            return;
        }
        
        $('#rating-score-number').text(rating.rating);
        if(rating.comment !== null && rating.comment !== undefined && rating.comment !== '') {
            $('#rating-comment').text(rating.comment);
        } else {
            $('#rating-comment').hide();
        }
        // Utiliser la date formatée selon les paramètres WordPress
        $('#rating-meta .rating-date').text('Noté le ' + (rating.formatted_date || rating.created_at));
        $('#rating-meta .rating-admin').text('Par ' + (rating.admin_name || 'Admin'));
    }

    function displayNoRating() {
        $('#rating-section').html(`
            <div class="rating-display" id="rating-display">
                <div class="rating-score">
                    <span class="rating-score-number" id="rating-score-number">-</span>
                    <span class="rating-score-total">10</span>
                </div>
                <div class="rating-comment" id="rating-comment">
                    <p class="no-rating">Aucune note attribuée</p>
                </div>
                <div class="rating-meta" id="rating-meta">
                    <small class="rating-date"></small>
                    <small class="rating-admin"></small>
                </div>
            </div>
        `);
    }

    // Afficher les révisions dans la liste
    function displayRevisions(revisions, section) {
        var $revisionsList = $('#revisions-list');
        
        if (revisions.length === 0) {
            $revisionsList.html(`
                <div class="no-revisions">
                    <i class="fas fa-history"></i>
                    <p>Aucune révision disponible pour cette brique</p>
                    <small>Les révisions sont créées automatiquement lors des demandes de notation</small>
                </div>
            `);
            return;
        }
        
        var html = '<div class="revisions-items">';
        
        revisions.forEach(function(revision, index) {
            // Utiliser la date formatée selon les paramètres WordPress
            var formattedDate = revision.formatted_date || revision.created_at;
            
            
            // Ajouter les informations de notation si disponibles
            var ratingInfo = '';
            if (revision.rating !== null && revision.rating !== undefined) {
                ratingInfo = `
                    
                    <div class="rating-score">
                            <span class="rating-score-number" id="rating-score-number">${revision.rating}</span>
                            <span class="rating-score-total">10</span>
                        </div>
                `;
            }

            
            html += `
                <div class="revision-item" data-revision-id="${revision.id}">

                    <div class="revision-actions">
                        <button class="btn-outline --small view-revision-btn" data-revision-id="${revision.id}">
                            Lire cette version
                        </button>
                    </div>

                    <div class="revision-header">
                        ${ratingInfo}
                        <div class="revision-reason">
                            <span class="reason-badge reason-${revision.revision_reason}">${revision.rating_comment || 'Aucun commentaire'}</span>
                        </div>
                    </div>

                   
                    <div class="revision-meta">
                        <span class="revision-admin">Par ${revision.admin_name || 'Admin'} </span>
                        <span class="revision-date">Le ${formattedDate}</span>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $revisionsList.html(html);
        
        // Attacher les événements aux boutons
        $('.view-revision-btn').on('click', function() {
            var revisionId = $(this).data('revision-id');
            viewRevision(revisionId);
        });
    }
    
    // Obtenir le label de la raison de révision
    function getRevisionReasonLabel(reason) {
        var labels = {
            'grading_request': 'Demande d\'avis',
            'admin_rating': 'Notation par admin',
            'manual': 'Modification manuelle',
            'auto_save': 'Sauvegarde automatique'
        };
        return labels[reason] || reason;
    }
    
    // Visualiser une révision dans un popup
    function viewRevision(revisionId) {
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_get_section_revision',
            revision_id: revisionId,
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                showRevisionPopup(response.data.revision);
            } 
        }).fail(function() {
        });
    }
    
    // Afficher le popup de révision
    function showRevisionPopup(revision) {
        // Utiliser la date formatée par PHP selon les conventions
        var formattedDate = revision.formatted_date || revision.created_at;
        
        var reasonLabel = getRevisionReasonLabel(revision.revision_reason);
        
        $('#revision-popup-title').text(`Révision du ${formattedDate}`);
        $('#revision-date').text(formattedDate);
        $('#revision-reason').text(reasonLabel);
        $('#revision-content').html(revision.content || '<p class="empty-content">Aucun contenu dans cette révision</p>');
        
        // Nettoyer et ajouter les informations de notation si disponibles
        var $revisionInfo = $('.popup-body .revision-info');
        // Supprimer toutes les infos de notation précédentes
        $revisionInfo.find('.revision-rating-info').remove();
        
        if (revision.rating !== null && revision.rating !== undefined) {
            var ratingHtml = `
                <div class="revision-rating-info">
                    <div class="revision-score-display">
                        <span class="score">${revision.rating}/10</span>
                        <span class="admin-name">Noté par ${revision.admin_name || 'Admin'}</span>
                    </div>
                    ${revision.rating_comment ? `<div class="revision-comment-display">
                        <p>${revision.rating_comment}</p>
                    </div>` : ''}
                </div>
            `;
            $revisionInfo.append(ratingHtml);
        }
        
        $('#wp-bmc-revision-popup').fadeIn(300);
    }
    
    // Fermer le popup de révision
    function closeRevisionPopup() {
        $('#wp-bmc-revision-popup').fadeOut(300);
    }
    
    // Variable globale pour stocker la section actuellement éditée
    var currentEditingSection = '';
    
    // Événements pour les révisions
    $(document).on('click', '#load-revisions-btn', function() {
        
        var section = currentEditingSection || $('#wp-bmc-edit-view').data('section');
        
        if (section) {
            loadSectionRevisions(section);
        } else {
            $('#revisions-list').html(`
                <div class="no-revisions">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur : Impossible de déterminer la section actuelle</p>
                </div>
            `);
        }
    });
    
    $(document).on('click', '#revision-popup-close', function() {
        closeRevisionPopup();
    });
    
    $(document).on('click', '.popup-overlay', function() {
        closeRevisionPopup();
    });
    
    // ========================================
    // GESTION DES TODOS
    // ========================================
    
    // Charger les todos d'une section (avec cache)
    function loadSectionTodos(sectionName) {
        // Vérifier le cache d'abord
        if (todoCache[sectionName]) {
            displayTodos(todoCache[sectionName].todos, todoCache[sectionName].stats);
            return;
        }
        
        // Afficher le loader pour les todos
        $('#todo-list').html('<div class="wp-bmc-loader"><div class="loader-spinner"></div><span>Chargement des tâches...</span></div>');
        
        var projectId = $('.wp-bmc-dashboard').data('project-id') || $('.wp-bmc-canvas-container').data('project-id');
        
        var formData = {
            action: 'wp_bmc_get_section_todos',
            nonce: wp_bmc_ajax.nonce,
            section: sectionName,
            project_id: projectId
        };

        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Mettre en cache
                todoCache[sectionName] = {
                    todos: response.data.todos,
                    stats: response.data.stats
                };
                currentSectionTodos = response.data.todos;
                currentSectionStats = response.data.stats;
                displayTodos(response.data.todos, response.data.stats);
            } else {
                displayTodos([], { total: 0, completed: 0, pending: 0 });
            }
        }).fail(function() {
            displayTodos([], { total: 0, completed: 0, pending: 0 });
        });
    }
    
    // Afficher les todos
    function displayTodos(todos, stats) {
        var $todoList = $('#todo-list');
        var $noTodos = $('#no-todos');
        var $completedCount = $('#todo-completed-count');
        var $totalCount = $('#todo-total-count');
        
        // Mettre à jour les statistiques
        $completedCount.text(stats.completed || 0);
        $totalCount.text(stats.total || 0);
        
        // Vider la liste
        $todoList.empty();
        
        if (todos && todos.length > 0) {
            $noTodos.hide();
            todos.forEach(function(todo) {
                var todoHtml = createTodoHtml(todo);
                $todoList.append(todoHtml);
            });
        } else {
            $noTodos.show();
        }
        
        // Réattacher les événements
        attachTodoEvents();
    }
    
    // Créer le HTML pour une tâche
    function createTodoHtml(todo) {
        // Convertir en entier pour éviter les problèmes de type
        var isCompleted = parseInt(todo.is_completed) === 1;
        var completedClass = isCompleted ? 'completed' : '';
        var checkedAttr = isCompleted ? 'checked' : '';
        
        return `
            <li class="todo-item ${completedClass}" data-todo-id="${todo.id}">
                <div class="todo-content">
                    <input type="checkbox" class="todo-checkbox" ${checkedAttr}>
                    <span class="todo-text">${escapeHtml(todo.task_text)}</span>
                </div>
                <div class="todo-actions">
                    <button type="button" class="todo-edit-btn" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="todo-delete-btn" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </li>
        `;
    }
    
    // Attacher les événements aux todos avec délégation d'événements
    function attachTodoEvents() {
        // Ajouter une nouvelle tâche
        $(document).off('click', '#add-todo-btn').on('click', '#add-todo-btn', function() {
            addNewTodo();
        });
        
        // Entrée dans le champ de saisie
        $(document).off('keypress', '#todo-input').on('keypress', '#todo-input', function(e) {
            if (e.which === 13) { // Entrée
                addNewTodo();
            }
        });
        
        // Cocher/décocher une tâche (délégation d'événements)
        $(document).off('change', '.todo-checkbox').on('change', '.todo-checkbox', function() {
            var $todoItem = $(this).closest('.todo-item');
            var todoId = $todoItem.data('todo-id');
            toggleTodo(todoId);
        });
        
        // Modifier une tâche (délégation d'événements)
        $(document).off('click', '.todo-edit-btn').on('click', '.todo-edit-btn', function() {
            var $todoItem = $(this).closest('.todo-item');
            var todoId = $todoItem.data('todo-id');
            var currentText = $todoItem.find('.todo-text').text();
            editTodo(todoId, currentText, $todoItem);
        });
        
        // Supprimer une tâche (délégation d'événements)
        $(document).off('click', '.todo-delete-btn').on('click', '.todo-delete-btn', function() {
            var $todoItem = $(this).closest('.todo-item');
            var todoId = $todoItem.data('todo-id');
            deleteTodo(todoId);
        });
    }
    
    // Ajouter une nouvelle tâche (avec opération différée)
    function addNewTodo() {
        var taskText = $('#todo-input').val().trim();
        
        if (!taskText) {
            return;
        }
        
        var sectionName = $('#wp-bmc-edit-view').attr('data-section');
        if (!sectionName) {
            return;
        }
        
        // Générer un ID temporaire pour l'interface
        var tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Créer la tâche localement
        var newTodo = {
            id: tempId,
            task_text: taskText,
            is_completed: 0,
            project_id: null,
            section: sectionName,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        
        // Ajouter à l'interface immédiatement
        $('#todo-input').val('');
        addTodoToInterface(newTodo);
        updateTodoStats();
        
        // Mettre à jour le cache local
        if (sectionName && todoCache[sectionName]) {
            todoCache[sectionName].todos.push(newTodo);
        }
        
        // Ajouter à la liste des opérations en attente
        pendingOperations.add.push({
            section: sectionName,
            task_text: taskText,
            temp_id: tempId
        });
        
        // Marquer comme modifié
        markAsDirty();
    }
    
    // Ajouter une tâche à l'interface sans recharger
    function addTodoToInterface(todo) {
        var $todoList = $('#todo-list');
        var $noTodos = $('#no-todos');
        
        // Masquer le message "aucune tâche"
        $noTodos.hide();
        
        // Créer et ajouter le HTML de la tâche
        var todoHtml = createTodoHtml(todo);
        $todoList.append(todoHtml);
        
        // Les événements sont automatiquement disponibles grâce à la délégation
    }
    
    // Mettre à jour les statistiques sans recharger
    function updateTodoStats() {
        var $todoList = $('#todo-list');
        var totalCount = $todoList.find('.todo-item').length;
        var completedCount = $todoList.find('.todo-item.completed').length;
        
        $('#todo-completed-count').text(completedCount);
        $('#todo-total-count').text(totalCount);
    }
    
    // Basculer l'état d'une tâche (avec opération différée)
    function toggleTodo(todoId) {
        // Chercher uniquement dans le conteneur visible
        var $visibleContainer = $('#wp-bmc-edit-view:visible, .canvas-container:visible');
        var $todoItems = $visibleContainer.find('.todo-item[data-todo-id="' + todoId + '"]');
        
        if ($todoItems.length === 0) {
            return;
        }
        
        var $firstItem = $todoItems.first();
        var $checkbox = $firstItem.find('.todo-checkbox');
        var isChecked = $checkbox.is(':checked');
        
        // Mettre à jour l'interface immédiatement pour TOUS les items avec cet ID dans le conteneur visible
        $todoItems.each(function() {
            var $item = $(this);
            if (isChecked) {
                $item.addClass('completed');
                $item.find('.todo-checkbox').prop('checked', true);
            } else {
                $item.removeClass('completed');
                $item.find('.todo-checkbox').prop('checked', false);
            }
        });
        
        // Mettre à jour les statistiques immédiatement
        updateTodoStats();
        
        // Mettre à jour le cache local
        if (currentEditingSection && todoCache[currentEditingSection]) {
            var todoInCache = todoCache[currentEditingSection].todos.find(function(todo) {
                return parseInt(todo.id) === parseInt(todoId);
            });
            if (todoInCache) {
                todoInCache.is_completed = isChecked ? '1' : '0';
            }
        }
        
        // Ajouter à la liste des opérations en attente
        pendingOperations.toggle.push({
            todo_id: todoId,
            is_completed: isChecked ? 1 : 0
        });
        
        // Marquer comme modifié
        markAsDirty();
    }
    
    // Modifier le texte d'une tâche (avec opération différée)
    function editTodo(todoId, currentText, $contextItem) {
        // Utiliser le contexte fourni ou chercher dans la vue visible
        var $todoItem;
        if ($contextItem) {
            $todoItem = $contextItem;
        } else {
            // Chercher uniquement dans le conteneur visible (edit-view ou canvas actuel)
            var $visibleContainer = $('#wp-bmc-edit-view:visible, .canvas-container:visible');
            $todoItem = $visibleContainer.find('.todo-item[data-todo-id="' + todoId + '"]').first();
        }
        
        if (!$todoItem || $todoItem.length === 0) {
            return;
        }
        
        var $todoText = $todoItem.find('.todo-text');
        
        // Vérifier si déjà en mode édition
        if ($todoItem.find('.todo-edit-input').length > 0) {
            return;
        }
        
        // Créer un input d'édition
        var $editInput = $('<input type="text" class="todo-edit-input">')
            .val(currentText)
        
        // Créer les boutons de validation/annulation
        var $editActions = $('<div class="todo-edit-actions">')
            .html(`
                <button type="button" class="todo-save-edit">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="todo-cancel-edit">
                    <i class="fas fa-times"></i>
                </button>
            `);
        
        // Remplacer le texte par l'input
        $todoText.hide();
        $todoItem.find('.todo-actions').hide();
        $todoText.after($editActions).after($editInput);
        
        // Focus sur l'input
        $editInput.focus().select();
        
        // Fonction pour sauvegarder
        function saveEdit() {
            var newText = $editInput.val().trim();
            
            if (!newText) {
                WP_BMC_Toast.warning('Le texte de la tâche ne peut pas être vide');
                $editInput.focus();
                return;
            }
            
            if (newText === currentText) {
                cancelEdit();
                return;
            }
            
            // Mettre à jour l'interface
            $todoText.text(newText).show();
            $editInput.remove();
            $editActions.remove();
            $todoItem.find('.todo-actions').show();
            
            // Mettre à jour le cache local
            if (currentEditingSection && todoCache[currentEditingSection]) {
                var todoInCache = todoCache[currentEditingSection].todos.find(function(todo) {
                    return parseInt(todo.id) === parseInt(todoId);
                });
                if (todoInCache) {
                    todoInCache.task_text = newText;
                }
            }
            
            // Ajouter à la liste des opérations en attente
            pendingOperations.update.push({
                todo_id: todoId,
                new_text: newText
            });
            
            // Marquer comme modifié
            markAsDirty();
            
            WP_BMC_Toast.success('Tâche modifiée avec succès !');
        }
        
        // Fonction pour annuler
        function cancelEdit() {
            $todoText.show();
            $editInput.remove();
            $editActions.remove();
            $todoItem.find('.todo-actions').show();
        }
        
        // Événements
        $editActions.find('.todo-save-edit').on('click', saveEdit);
        $editActions.find('.todo-cancel-edit').on('click', cancelEdit);
        
        // Sauvegarder avec Entrée, annuler avec Échap
        $editInput.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancelEdit();
            }
        });
    }
    
    // Supprimer une tâche
    function deleteTodo(todoId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
            return;
        }
        
        // Chercher uniquement dans le conteneur visible
        var $visibleContainer = $('#wp-bmc-edit-view:visible, .canvas-container:visible');
        var $todoItems = $visibleContainer.find('.todo-item[data-todo-id="' + todoId + '"]');
        
        if ($todoItems.length === 0) {
            return;
        }
        
        // Supprimer l'élément de l'interface immédiatement
        $todoItems.fadeOut(300, function() {
            $(this).remove();
            
            // Vérifier s'il reste des tâches dans le conteneur visible
            if ($visibleContainer.find('#todo-list .todo-item').length === 0) {
                $visibleContainer.find('#no-todos').show();
            }
            
            // Mettre à jour les statistiques
            updateTodoStats();
        });
        
        // Mettre à jour le cache local pour supprimer le todo
        if (currentEditingSection && todoCache[currentEditingSection]) {
            todoCache[currentEditingSection].todos = todoCache[currentEditingSection].todos.filter(function(todo) {
                return parseInt(todo.id) !== parseInt(todoId);
            });
        }
        
        // Ajouter à la liste des opérations en attente
        pendingOperations.delete.push({
            todo_id: todoId
        });
        
        // Marquer comme modifié et forcer une sauvegarde rapide
        isDirty = true;
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        saveTimeout = setTimeout(function() {
            savePendingOperations();
        }, 500);
    }
    
    // Fonction utilitaire pour échapper le HTML (utilise le module core)
    function escapeHtml(text) {
        return window.WP_BMC_Utils ? window.WP_BMC_Utils.escapeHtml(text) : String(text || '');
    }
    
    // ========================================
    // EXPOSER LES FONCTIONS GLOBALEMENT
    // ========================================
    window.WP_BMC_Dashboard = {
        autoSaveCanvas: autoSaveCanvas,
        updateLastSavedTime: updateLastSavedTime,
        getCurrentViewMode: getCurrentViewMode,
        openEditView: openEditView,
        closeEditView: closeEditView,
        loadCanvasView: loadCanvasView,
        initCanvasEvents: initCanvasEvents,
        loadSectionRevisions: loadSectionRevisions,
        viewRevision: viewRevision,
        loadSectionTodos: loadSectionTodos,
        addNewTodo: addNewTodo,
        toggleTodo: toggleTodo,
        editTodo: editTodo,
        deleteTodo: deleteTodo,
        displaySectionRating: displaySectionRating,
        loadSectionRating: loadSectionRating,

    };
    
    // Initialiser les événements du canvas au chargement
    initCanvasEvents();
    
    // Initialiser le graphique de progression du projet
    initProgressChart();
    
    // Initialiser les événements pour la vue globale des todos
    initGlobalTodoEvents();
    
    // ========================================
    // GESTION DES TODOS DANS LA VUE GLOBALE
    // ========================================
    
    // Initialiser les événements pour les todos de la vue globale
    function initGlobalTodoEvents() {
        // Cocher/décocher une tâche dans la vue globale
        $(document).on('change', '.action-plan .todo-checkbox', function() {
            var todoId = $(this).data('todo-id');
            var $todoItem = $(this).closest('.todo-item');
            var isChecked = $(this).is(':checked');
            
            // Mettre à jour l'interface immédiatement
            if (isChecked) {
                $todoItem.addClass('completed');
            } else {
                $todoItem.removeClass('completed');
            }
            
            // Envoyer la requête au serveur
            var formData = {
                action: 'wp_bmc_toggle_todo',
                nonce: wp_bmc_ajax.nonce,
                todo_id: todoId
            };
            
            $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
                if (!response.success) {
                    // Restaurer l'état précédent en cas d'erreur
                    $(this).prop('checked', !isChecked);
                    if (isChecked) {
                        $todoItem.removeClass('completed');
                    } else {
                        $todoItem.addClass('completed');
                    }
                }
            }).fail(function() {
                // Restaurer l'état précédent en cas d'erreur
                $(this).prop('checked', !isChecked);
                if (isChecked) {
                    $todoItem.removeClass('completed');
                } else {
                    $todoItem.addClass('completed');
                }
            });
        });
    }
    
    // ========================================
    // TRIGGERS DE SAUVEGARDE AUTOMATIQUE
    // ========================================
    
    // Sauvegarder avant de recharger la page
    $(window).on('beforeunload', function() {
        if (isDirty) {
            forceSave();
        }
    });
    
    // Sauvegarder quand l'utilisateur quitte la page (mobile)
    $(document).on('visibilitychange', function() {
        if (document.hidden && isDirty) {
            forceSave();
        }
    });
    
    // Sauvegarder périodiquement (toutes les 30 secondes si des modifications)
    setInterval(function() {
        if (isDirty) {
            forceSave();
        }
    }, 30000);
    
    // ========================================
    // AUTO-OUVERTURE DES SECTIONS
    // ========================================
    
    // Fonction pour ouvrir automatiquement une section
    function autoOpenSection() {
        var urlParams = new URLSearchParams(window.location.search);
        var openSection = urlParams.get("open_section");
        
        if (openSection) {
            
            // Attendre que le DOM soit prêt
            setTimeout(function() {
                // Chercher le bouton d'édition de la section
                var $editBtn = $('.edit-brick-btn[data-section="' + openSection + '"]');
                
                if ($editBtn.length > 0) {
                    // Simuler un clic sur le bouton d'édition
                    $editBtn.trigger('click');
                    
                    // Nettoyer l'URL en supprimant le paramètre open_section
                    var newUrl = new URL(window.location);
                    newUrl.searchParams.delete('open_section');
                    window.history.replaceState({}, document.title, newUrl.toString());
                } 
            }, 1000); // Attendre 1 seconde pour que le DOM soit chargé
        }
    }
    
    // Exécuter l'auto-ouverture au chargement
    autoOpenSection();
    
    // ========================================
    // GESTION DU CHANGEMENT DE MOT DE PASSE
    // ========================================
    
    // Vérifier si l'utilisateur doit changer son mot de passe
    setTimeout(checkPasswordChangeRequired, 1000);

    // Fonction pour vérifier si un changement de mot de passe est requis
    function checkPasswordChangeRequired() {
        if (!window.location.pathname.includes('/dashboard') && !window.location.pathname.includes('/business-model-canvas')) {
            return;
        }
        
        // Vérifier si c'est une première connexion (utilisateur avec statut 'pending' qui vient de se connecter)
        var ajaxConfig = getAjaxConfig();
        
        
        // Vérification supplémentaire
        if (!ajaxConfig.nonce || !ajaxConfig.url) {
            console.error('Configuration AJAX invalide:', ajaxConfig);
            return;
        }
        
        
        $.post(ajaxConfig.url, {
            action: 'wp_bmc_check_password_change_required',
            nonce: ajaxConfig.nonce
        }, function(response) {
            if (response.success && response.data.required) {
                showChangePasswordPopup();
            } 
        }).fail(function(xhr, status, error) {
            console.error('Erreur lors de la vérification du changement de mot de passe:', xhr.responseText, 'Status:', status, 'Error:', error);
        });
    }

    
    // Afficher le popup de changement de mot de passe
    function showChangePasswordPopup() {
        // Ajouter le popup au DOM s'il n'existe pas
        if (!$('#wp-bmc-change-password-popup').length) {
            // Charger le template du popup
            $.get(wp_bmc_ajax.ajax_url, {
                action: 'wp_bmc_get_change_password_popup',
                nonce: wp_bmc_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('body').append(response.data.html);
                    initChangePasswordEvents();
                    $('#wp-bmc-change-password-popup').fadeIn(300);
                } 
            }).fail(function(xhr, status, error) {
                logError('Erreur AJAX lors du chargement du template:', xhr.responseText, 'Status:', status, 'Error:', error);
            });
        } else {
            $('#wp-bmc-change-password-popup').fadeIn(300);
        }
    }
    
    // Exposer la fonction globalement pour qu'elle soit disponible partout
    window.showChangePasswordPopup = showChangePasswordPopup;
    
    // Initialiser les événements du popup de changement de mot de passe
    function initChangePasswordEvents() {
        // Vérifier le statut utilisateur pour déterminer si la fermeture est autorisée
        var ajaxConfig = getAjaxConfig();
        
        $.get(ajaxConfig.url, {
            action: 'wp_bmc_check_password_change_required',
            nonce: ajaxConfig.nonce
        }, function(response) {
            var canClosePopup = true;
            
            if (response.success && response.data) {
                // Si le changement de mot de passe est requis, empêcher la fermeture
                if (response.data.password_change_required) {
                    canClosePopup = false;
                }
            }
            
            // Configurer les événements de fermeture selon le statut
            $('#change-password-popup-close, #wp-bmc-change-password-popup .popup-overlay').on('click', function() {
                if (!canClosePopup) {
                    WP_BMC_Toast.warning('Vous devez changer votre mot de passe pour continuer');
                } else {
                    $('#wp-bmc-change-password-popup').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        }).fail(function() {
            // En cas d'erreur, empêcher la fermeture par sécurité
            $('#change-password-popup-close, #wp-bmc-change-password-popup .popup-overlay').on('click', function() {
                WP_BMC_Toast.warning('Vous devez changer votre mot de passe pour continuer');
            });
        });
        
        // Gestion de l'affichage/masquage des mots de passe
        $('.show-password').on('click', function() {
            var target = $(this).data('target');
            var $input = $('#' + target);
            var $icon = $(this).find('svg');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.html('<path fill="currentColor" d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7M2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27M7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2m4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01"/>');
            } else {
                $input.attr('type', 'password');
                $icon.html('<path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/>');
            }
        });
        
        // Soumission du formulaire
        $('#wp-bmc-change-password-form').on('submit', function(e) {
            e.preventDefault();
            changePassword();
        });
        
        // Validation en temps réel
        $('#new-password, #confirm-password').on('input', function() {
            validatePasswordMatch();
        });
    }
    
    // Valider que les mots de passe correspondent
    function validatePasswordMatch() {
        var newPassword = $('#new-password').val();
        var confirmPassword = $('#confirm-password').val();
        var $submitBtn = $('#change-password-submit');
        
        if (confirmPassword && newPassword !== confirmPassword) {
            $('#confirm-password').addClass('error');
            $submitBtn.prop('disabled', true);
        } else {
            $('#confirm-password').removeClass('error');
            $submitBtn.prop('disabled', false);
        }
    }
    
    // Changer le mot de passe
    function changePassword() {
        var $form = $('#wp-bmc-change-password-form');
        var $submitBtn = $('#change-password-submit');
        var $btnText = $submitBtn.find('.btn-text');
        var $btnLoader = $submitBtn.find('.btn-loader');
        
        // Validation côté client
        var currentPassword = $('#current-password').val();
        var newPassword = $('#new-password').val();
        var confirmPassword = $('#confirm-password').val();
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            WP_BMC_Toast.error('Tous les champs sont obligatoires');
            return;
        }
        
        if (newPassword.length < 6) {
            WP_BMC_Toast.error('Le nouveau mot de passe doit contenir au moins 6 caractères');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            WP_BMC_Toast.error('Les mots de passe ne correspondent pas');
            return;
        }
        
        // Désactiver le bouton et afficher le loader
        $submitBtn.prop('disabled', true);
        $btnText.hide();
        $btnLoader.show();
        
        // Envoyer la requête
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_change_password',
            nonce: wp_bmc_ajax.nonce,
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('Mot de passe changé avec succès !');
                setTimeout(() => {
                    WP_BMC_Toast.info('Vous allez être redirigé pour vous reconnecter avec votre nouveau mot de passe')
                }, 400);
                $('#wp-bmc-change-password-popup').fadeOut(300, function() {
                    $(this).remove();
                });
            // Si le mot de passe est changé avec succès, déconnecter l'utilisateur et rediriger vers la page de login
                setTimeout(function() {
                    window.location.href = wp_bmc_ajax.login_url || '/login';
                }, 3000); // Laisser le toast s'afficher avant de rediriger
            } else {
                WP_BMC_Toast.error(response.data || 'Erreur lors du changement de mot de passe');
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton
            $submitBtn.prop('disabled', false);
            $btnText.show();
            $btnLoader.hide();
        });
    }

    // Gestionnaire pour le bouton de changement de mot de passe dans le menu
    $(document).on('click', '#wp-bmc-change-password-btn', function(e) {
        e.preventDefault();
        showChangePasswordPopup();
    });
    
});

// Fonction pour initialiser le graphique de progression du projet
function initProgressChart() {
    const progressCircle = document.querySelector('.progress-circle');
    if (!progressCircle) return;
    
    // Calculer l'offset basé sur le stroke-dashoffset actuel
    const currentOffset = progressCircle.getAttribute('stroke-dashoffset');
    if (currentOffset) {
        // Définir la variable CSS pour l'animation
        progressCircle.style.setProperty('--progress-offset', currentOffset);
    }
}

