/**
 * JavaScript pour la gestion des utilisateurs dans l'admin
 * Fonctionnalités : recherche, tri, actions sur les utilisateurs, création, gestion des statuts
 */

jQuery(document).ready(function($) {
    
    // ========================================
    // CRÉATION D'UTILISATEUR
    // ========================================
    $("#create-user-form").on("submit", function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();

        $submitBtn
            .prop("disabled", true)
            .html('<i class="fas fa-spinner fa-spin"></i> Création...');

        var formData = {
            action: "wp_bmc_create_user",
            nonce: wp_bmc_admin_ajax.nonce,
            custom_id: $("#user_custom_id").val(),
            email: $("#user_email").val(),
            password: $("#user_password").val(),
            first_name: $("#user_first_name").val(),
            last_name: $("#user_last_name").val(),
        };

        $.post(wp_bmc_admin_ajax.ajax_url, formData, function (response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        })
            .fail(function () {
                WP_BMC_Toast.error("Erreur lors de la création de l'utilisateur.");
            })
            .always(function () {
                $submitBtn.prop("disabled", false).html(originalText);
            });
    });

    // ========================================
    // RECHERCHE D'UTILISATEURS
    // ========================================
    $('#users-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterUsers(searchTerm);
    });
    
    // ========================================
    // FILTRAGE PAR STATUT
    // ========================================
    $('#users-filter-status').on('change', function() {
        var status = $(this).val();
        filterUsersByStatus(status);
    });
    
    // ========================================
    // TRI DES COLONNES
    // ========================================
    $('.sortable').on('click', function() {
        var column = $(this).data('sort');
        var currentOrder = $(this).hasClass('asc') ? 'desc' : 'asc';
        
        // Réinitialiser tous les indicateurs de tri
        $('.sortable').removeClass('asc desc');
        
        // Ajouter la classe de tri à la colonne cliquée
        $(this).addClass(currentOrder);
        
        // Trier le tableau
        sortUsersTable(column, currentOrder);
    });
    
    // ========================================
    // ACTIONS SUR LES UTILISATEURS
    // ========================================
    
    // Voir le profil utilisateur
    $(document).on('click', '.view-user-btn', function() {
        var userId = $(this).data('user-id');
        viewUserProfile(userId);
    });
    
    // Éditer l'utilisateur
    $(document).on('click', '.edit-user-btn', function() {
        var userId = $(this).data('user-id');
        editUser(userId);
    });
    
    // Voir le canvas de l'utilisateur
    $(document).on('click', '.view-canvas-btn', function() {
        var userId = $(this).data('user-id');
        viewUserCanvas(userId);
    });
    
    // Voir les projets de l'utilisateur
    $(document).on('click', '.view-projects-btn', function() {
        var userId = $(this).data('user-id');
        viewUserProjects(userId);
    });

    // Réinitialiser le mot de passe
    $(document).on('click', '.reset-password-btn', function() {
        var userId = $(this).data('user-id');
        resetUserPassword(userId);
    });

    // Désactiver l'utilisateur
    $(document).on('click', '.deactivate-user-btn', function() {
        var userId = $(this).data('user-id');
        deactivateUser(userId);
    });

    // Gestion des statuts utilisateur
    $(document).on('click', '.disable-user-btn', function() {
        var userId = $(this).data('user-id');
        updateUserStatus(userId, 'disabled');
    });

    $(document).on('click', '.enable-user-btn', function() {
        var userId = $(this).data('user-id');
        updateUserStatus(userId, 'active');
    });

    $(document).on('click', '.activate-user-btn', function() {
        var userId = $(this).data('user-id');
        updateUserStatus(userId, 'active');
    });
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    // Filtrer les utilisateurs par terme de recherche
    function filterUsers(searchTerm) {
        $('.user-row').each(function() {
            var $row = $(this);
            var customId = $row.find('.user-custom-id').text().toLowerCase();
            var name = $row.find('.user-name').text().toLowerCase();
            var email = $row.find('.user-email').text().toLowerCase();
            
            if (customId.includes(searchTerm) || name.includes(searchTerm) || email.includes(searchTerm)) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateUsersCount();
    }
    
    // Filtrer les utilisateurs par statut
    function filterUsersByStatus(status) {
        $('.user-row').each(function() {
            var $row = $(this);
            var projectCount = parseInt($row.find('.project-count').text());
            
            if (status === '') {
                $row.show();
            } else if (status === 'active' && projectCount > 0) {
                $row.show();
            } else if (status === 'inactive' && projectCount === 0) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateUsersCount();
    }
    
    // Trier le tableau des utilisateurs
    function sortUsersTable(column, order) {
        var $tbody = $('#users-table tbody');
        var $rows = $tbody.find('.user-row').toArray();
        
        $rows.sort(function(a, b) {
            var aVal, bVal;
            
            switch(column) {
                case 'custom_id':
                    aVal = $(a).find('.user-custom-id').text().trim();
                    bVal = $(b).find('.user-custom-id').text().trim();
                    break;
                case 'name':
                    aVal = $(a).find('.user-name').text().trim();
                    bVal = $(b).find('.user-name').text().trim();
                    break;
                case 'email':
                    aVal = $(a).find('.user-email').text().trim();
                    bVal = $(b).find('.user-email').text().trim();
                    break;
                case 'project_count':
                    aVal = parseInt($(a).find('.project-count').text()) || 0;
                    bVal = parseInt($(b).find('.project-count').text()) || 0;
                    break;
                case 'created_at':
                    aVal = new Date($(a).find('.user-registration').text());
                    bVal = new Date($(b).find('.user-registration').text());
                    break;
                case 'last_project_date':
                    var aText = $(a).find('.user-last-project').text().trim();
                    var bText = $(b).find('.user-last-project').text().trim();
                    aVal = aText === 'Aucun projet' ? new Date(0) : new Date(aText);
                    bVal = bText === 'Aucun projet' ? new Date(0) : new Date(bText);
                    break;
                default:
                    return 0;
            }
            
            if (order === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });
        
        // Réorganiser les lignes dans le DOM
        $.each($rows, function(index, row) {
            $tbody.append(row);
        });
    }
    
    // Mettre à jour le compteur d'utilisateurs
    function updateUsersCount() {
        var visibleCount = $('.user-row:visible').length;
        var totalCount = $('.user-row').length;
        $('#users-count').text(visibleCount + ' utilisateur(s) sur ' + totalCount);
    }
    
    // ========================================
    // ACTIONS SUR LES UTILISATEURS
    // ========================================
    
    // Voir le profil utilisateur
    function viewUserProfile(userId) {
        // Créer une popup ou rediriger vers une page de profil
        var popup = $('<div class="wp-bmc-popup user-profile-popup">' +
            '<div class="popup-overlay"></div>' +
            '<div class="popup-content">' +
                '<div class="popup-header">' +
                    '<h3>Profil utilisateur</h3>' +
                    '<button class="popup-close">&times;</button>' +
                '</div>' +
                '<div class="popup-body">' +
                    '<div class="user-profile-loading">Chargement...</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(popup);
        popup.fadeIn(300);
        
        // Charger les données utilisateur via AJAX
        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_get_user_profile',
            user_id: userId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                popup.find('.user-profile-loading').html(response.data.html);
            } else {
                popup.find('.user-profile-loading').html('<p>Erreur lors du chargement du profil.</p>');
            }
        });
        
        // Gérer la fermeture
        popup.find('.popup-close, .popup-overlay').on('click', function() {
            popup.fadeOut(300, function() {
                popup.remove();
            });
        });
    }
    
    // Éditer l'utilisateur
    function editUser(userId) {
        // Implémentation de l'édition d'utilisateur
        WP_BMC_Toast.info("Fonctionnalité d'édition à implémenter");
    }
    
    // Voir le canvas de l'utilisateur
    function viewUserCanvas(userId) {
        // Ouvrir le canvas dans un nouvel onglet
        window.open('admin.php?page=wp-business-model-canvas&action=view_canvas&user_id=' + userId, '_blank');
    }
    
    // Voir les projets de l'utilisateur
    function viewUserProjects(userId) {
        // Créer une popup pour afficher les projets
        var popup = $('<div class="wp-bmc-popup user-projects-popup">' +
            '<div class="popup-overlay"></div>' +
            '<div class="popup-content">' +
                '<div class="popup-header">' +
                    '<h3>Projets de l\'utilisateur</h3>' +
                    '<button class="popup-close">&times;</button>' +
                '</div>' +
                '<div class="popup-body">' +
                    '<div class="user-projects-loading">Chargement...</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(popup);
        popup.fadeIn(300);
        
        // Charger les projets via AJAX
        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_get_user_projects',
            user_id: userId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                popup.find('.user-projects-loading').html(response.data.html);
            } else {
                popup.find('.user-projects-loading').html('<p>Erreur lors du chargement des projets.</p>');
            }
        });
        
        // Gérer la fermeture
        popup.find('.popup-close, .popup-overlay').on('click', function() {
            popup.fadeOut(300, function() {
                popup.remove();
            });
        });
    }

    // Réinitialiser le mot de passe
    function resetUserPassword(userId) {
        if (confirm("Êtes-vous sûr de vouloir réinitialiser le mot de passe de cet utilisateur ?")) {
            // Vérifier que l'ID est valide
            if (!userId || userId === 'undefined' || userId === 'null') {
                WP_BMC_Toast.error('ID utilisateur invalide');
                return;
            }
            
            // Récupérer l'ID utilisateur WordPress par défaut
            $.post(wp_bmc_admin_ajax.ajax_url, {
                action: 'wp_bmc_get_wp_user_id',
                nonce: wp_bmc_admin_ajax.nonce,
                bmc_user_id: userId
            }, function(response) {
                if (response.success && response.data.wp_user_id) {
                    // Rediriger vers la page de réinitialisation WordPress
                    var adminUrl = wp_bmc_admin_ajax.admin_url || window.location.origin + '/wp-admin/';
                    var resetUrl = adminUrl + 'user-edit.php?user_id=' + response.data.wp_user_id + '&action=edit';
                    window.open(resetUrl, '_blank');
                    WP_BMC_Toast.success('Redirection vers la page d\'édition de l\'utilisateur');
                } else {
                    WP_BMC_Toast.error('Erreur lors de la récupération de l\'ID utilisateur WordPress: ' + (response.data || 'Erreur inconnue'));
                }
            }).fail(function(xhr, status, error) {
                WP_BMC_Toast.error('Erreur de connexion: ' + error);
            });
        }
    }

    // Désactiver l'utilisateur
    function deactivateUser(userId) {
        if (confirm("Êtes-vous sûr de vouloir désactiver cet utilisateur ?")) {
            // Implémentation de la désactivation
            WP_BMC_Toast.info("Fonctionnalité de désactivation à implémenter");
        }
    }

    // Mettre à jour le statut utilisateur
    function updateUserStatus(userId, status) {
        var actionText = status === 'disabled' ? 'désactiver' : 'activer';
        
        if (confirm('Êtes-vous sûr de vouloir ' + actionText + ' cet utilisateur ?')) {
            $.post(wp_bmc_admin_ajax.ajax_url, {
                action: 'wp_bmc_update_user_status',
                user_id: userId,
                status: status,
                nonce: wp_bmc_admin_ajax.nonce
            }, function(response) {
                if (response.success) {
                    WP_BMC_Toast.success(response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    WP_BMC_Toast.error(response.data);
                }
            }).fail(function() {
                WP_BMC_Toast.error('Erreur lors de la mise à jour du statut.');
            });
        }
    }
    
    // ========================================
    // RACCOURCIS CLAVIER
    // ========================================
    $(document).on('keydown', function(e) {
        // Ctrl+F pour focuser sur la recherche
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            $('#users-search').focus();
        }
        
        // Échap pour fermer les popups
        if (e.key === 'Escape') {
            $('.wp-bmc-popup').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
    
    // ========================================
    // RESET DES DONNÉES
    // ========================================
    
    // Bouton de reset des données
    $(document).on('click', '#wp-bmc-reset-data-btn', function() {
        resetAllData();
    });
    
    // Bouton d'export des utilisateurs
    $(document).on('click', '#wp-bmc-export-users-btn', function() {
        exportUsers();
    });
    
    function resetAllData() {
        // Confirmation critique avec plusieurs étapes
        if (!confirm('⚠️ ATTENTION : Cette action va supprimer TOUTES les données du plugin !\n\nCela inclut :\n- Tous les utilisateurs\n- Tous les projets\n- Tous les canvas\n- Toutes les notes et révisions\n- Toutes les tâches\n\nÊtes-vous sûr de vouloir continuer ?')) {
            return;
        }
        
        if (!confirm('🚨 DERNIÈRE CHANCE !\n\nCette action est IRRÉVERSIBLE !\n\nTapez "OUI" dans la prochaine boîte pour confirmer.')) {
            return;
        }
        
        var finalConfirm = prompt('Pour confirmer la suppression complète, tapez exactement : SUPPRIMER TOUT');
        if (finalConfirm !== 'SUPPRIMER TOUT') {
            WP_BMC_Toast.info('Opération annulée.');
            return;
        }
        
        // Afficher un loader
        $('#wp-bmc-reset-data-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression en cours...');
        
        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_reset_all_data',
            nonce: wp_bmc_admin_ajax.nonce,
            confirm: 'RESET_ALL_DATA'
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('✅ Toutes les données ont été supprimées !');
                WP_BMC_Toast.info('📊 ' + response.data.total_deleted + ' enregistrements supprimés');
                
                // Afficher les détails
                if (response.data.details && response.data.details.length > 0) {
                    setTimeout(function() {
                        var details = response.data.details.join('\n');
                        WP_BMC_Toast.info('Détails :\n' + details);
                    }, 2000);
                }
                
                // Recharger la page après un délai
                setTimeout(function() {
                    location.reload();
                }, 4000);
                
            } else {
                WP_BMC_Toast.error('❌ Erreur : ' + response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors de la suppression');
        }).always(function() {
            // Restaurer le bouton
            $('#wp-bmc-reset-data-btn').prop('disabled', false).html('<i class="fas fa-trash-alt"></i> Réinitialiser toutes les données');
        });
    }
    
    function exportUsers() {
        // Afficher un loader
        $('#wp-bmc-export-users-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Export en cours...');
        
        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_export_users',
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('✅ Export terminé !');
                
                // Télécharger le fichier
                var link = document.createElement('a');
                link.href = response.data.file_url;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                WP_BMC_Toast.info('📥 Téléchargement du fichier CSV...');
                
            } else {
                WP_BMC_Toast.error('❌ Erreur lors de l\'export : ' + response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors de l\'export');
        }).always(function() {
            // Restaurer le bouton
            $('#wp-bmc-export-users-btn').prop('disabled', false).html('<i class="fas fa-download"></i> Exporter les utilisateurs');
        });
    }
    
});