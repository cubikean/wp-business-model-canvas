/**
 * JavaScript pour la gestion des utilisateurs dans l'admin
 * Fonctionnalités : recherche, tri, actions sur les utilisateurs, création, gestion des statuts
 */

jQuery(document).ready(function($) {
    
    // ========================================
    // IMPORT CSV
    // ========================================
    
    // Afficher le nom du fichier sélectionné
    $('#csv-file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('.csv-file-name').text('Fichier sélectionné : ' + fileName).show();
            $('.csv-upload-label span').text('Fichier : ' + fileName);
        }
    });

    // Gérer l'import CSV
    $("#import-csv-form").on("submit", function (e) {
        e.preventDefault();
        
        if (typeof wp_bmc_admin_ajax === 'undefined') {
            WP_BMC_Toast.error('Variables AJAX non chargées. Rechargez la page.');
            return;
        }

        var fileInput = $('#csv-file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            WP_BMC_Toast.error('Veuillez sélectionner un fichier CSV.');
            return;
        }

        var file = fileInput.files[0];
        if (!file.name.endsWith('.csv')) {
            WP_BMC_Toast.error('Le fichier doit être au format CSV.');
            return;
        }

        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        $submitBtn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Import en cours...');

        // Masquer les résultats précédents
        $('#csv-import-results').hide();

        var formData = new FormData();
        formData.append('action', 'wp_bmc_import_csv_users');
        formData.append('nonce', wp_bmc_admin_ajax.nonce);
        formData.append('csv_file', file);
        formData.append('send_emails', $('#send-emails-users').is(':checked') ? '1' : '0');

        $.ajax({
            url: wp_bmc_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Réponse AJAX:', response);
                
                if (response.success) {
                    WP_BMC_Toast.success(response.data.message);
                    
                    // Afficher les résultats
                    displayImportResults(response.data);
                    
                    // Réinitialiser le formulaire
                    $('#import-csv-form')[0].reset();
                    $('.csv-file-name').hide();
                    $('.csv-upload-label span').text('Choisir un fichier CSV');
                    
                    // Recharger la page après 3 secondes si des utilisateurs ont été créés
                    if (response.data.created > 0) {
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                } else {
                    WP_BMC_Toast.error(response.data || 'Erreur lors de l\'import.');
                    if (response.data && response.data.errors) {
                        displayImportErrors(response.data.errors);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', xhr, status, error);
                WP_BMC_Toast.error("Erreur lors de l'import du CSV: " + error);
            },
            complete: function() {
                $submitBtn.prop("disabled", false).html(originalText);
            }
        });
    });

    function displayImportResults(data) {
        var statsHtml = '<div class="import-stats-grid">';
        statsHtml += '<div class="stat-success"><i class="fas fa-check-circle"></i> <strong>' + data.created + '</strong> créés</div>';
        statsHtml += '<div class="stat-skipped"><i class="fas fa-exclamation-triangle"></i> <strong>' + data.skipped + '</strong> ignorés</div>';
        statsHtml += '<div class="stat-error"><i class="fas fa-times-circle"></i> <strong>' + data.errors.length + '</strong> erreurs</div>';
        statsHtml += '</div>';

        var detailsHtml = '';
        
        if (data.created_users && data.created_users.length > 0) {
            detailsHtml += '<div class="import-section success-section">';
            detailsHtml += '<h4><i class="fas fa-check-circle"></i> Utilisateurs créés avec succès</h4>';
            detailsHtml += '<ul>';
            data.created_users.forEach(function(user) {
                detailsHtml += '<li>' + user.first_name + ' ' + user.last_name + ' (' + user.email + ') - ID: ' + user.custom_id + '</li>';
            });
            detailsHtml += '</ul></div>';
        }

        if (data.errors && data.errors.length > 0) {
            detailsHtml += '<div class="import-section error-section">';
            detailsHtml += '<h4><i class="fas fa-times-circle"></i> Erreurs rencontrées</h4>';
            detailsHtml += '<ul>';
            data.errors.forEach(function(error) {
                detailsHtml += '<li>' + error + '</li>';
            });
            detailsHtml += '</ul></div>';
        }

        $('#csv-import-results .import-stats').html(statsHtml);
        $('#csv-import-results .import-details').html(detailsHtml);
        $('#csv-import-results').show();
    }

    // ========================================
    // IMPORT CSV SUPERVISEURS
    // ========================================
    
    // Afficher le nom du fichier sélectionné pour les superviseurs
    $('#csv-supervisors-file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('.csv-supervisors-file-name').text('Fichier sélectionné : ' + fileName).show();
            $('#csv-supervisors-file').siblings('.csv-upload-label').find('span').text('Fichier : ' + fileName);
        }
    });

    // Gérer l'import CSV des superviseurs
    $("#import-supervisors-csv-form").on("submit", function (e) {
        e.preventDefault();
        
        if (typeof wp_bmc_admin_ajax === 'undefined') {
            WP_BMC_Toast.error('Variables AJAX non chargées. Rechargez la page.');
            return;
        }

        var fileInput = $('#csv-supervisors-file')[0];
        if (!fileInput.files || !fileInput.files[0]) {
            WP_BMC_Toast.error('Veuillez sélectionner un fichier CSV.');
            return;
        }

        var file = fileInput.files[0];
        if (!file.name.endsWith('.csv')) {
            WP_BMC_Toast.error('Le fichier doit être au format CSV.');
            return;
        }

        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        $submitBtn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i> Import en cours...');

        // Masquer les résultats précédents
        $('#csv-supervisors-import-results').hide();

        var formData = new FormData();
        formData.append('action', 'wp_bmc_import_csv_supervisors');
        formData.append('nonce', wp_bmc_admin_ajax.nonce);
        formData.append('csv_file', file);
        formData.append('send_emails', $('#send-emails-supervisors').is(':checked') ? '1' : '0');

        $.ajax({
            url: wp_bmc_admin_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Réponse AJAX superviseurs:', response);
                
                if (response.success) {
                    WP_BMC_Toast.success(response.data.message);
                    
                    // Afficher les résultats
                    displaySupervisorsImportResults(response.data);
                    
                    // Réinitialiser le formulaire
                    $('#import-supervisors-csv-form')[0].reset();
                    $('.csv-supervisors-file-name').hide();
                    $('#csv-supervisors-file').siblings('.csv-upload-label').find('span').text('Choisir un fichier CSV');
                    
                    // Recharger la page après 3 secondes si des superviseurs ont été créés
                    if (response.data.created > 0) {
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                } else {
                    WP_BMC_Toast.error(response.data || 'Erreur lors de l\'import.');
                    if (response.data && response.data.errors) {
                        displaySupervisorsImportResults(response.data);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', xhr, status, error);
                WP_BMC_Toast.error("Erreur lors de l'import du CSV: " + error);
            },
            complete: function() {
                $submitBtn.prop("disabled", false).html(originalText);
            }
        });
    });

    function displaySupervisorsImportResults(data) {
        var statsHtml = '<div class="import-stats-grid">';
        statsHtml += '<div class="stat-success"><i class="fas fa-check-circle"></i> <strong>' + data.created + '</strong> créés</div>';
        statsHtml += '<div class="stat-skipped"><i class="fas fa-exclamation-triangle"></i> <strong>' + data.skipped + '</strong> ignorés</div>';
        statsHtml += '<div class="stat-error"><i class="fas fa-times-circle"></i> <strong>' + data.errors.length + '</strong> erreurs</div>';
        statsHtml += '</div>';

        var detailsHtml = '';
        
        if (data.created_supervisors && data.created_supervisors.length > 0) {
            detailsHtml += '<div class="import-section success-section">';
            detailsHtml += '<h4><i class="fas fa-check-circle"></i> Superviseurs créés avec succès</h4>';
            detailsHtml += '<ul>';
            data.created_supervisors.forEach(function(supervisor) {
                detailsHtml += '<li>' + supervisor.first_name + ' ' + supervisor.last_name + ' (' + supervisor.email + ') - Username: ' + supervisor.username + '</li>';
            });
            detailsHtml += '</ul></div>';
        }

        if (data.errors && data.errors.length > 0) {
            detailsHtml += '<div class="import-section error-section">';
            detailsHtml += '<h4><i class="fas fa-times-circle"></i> Erreurs rencontrées</h4>';
            detailsHtml += '<ul>';
            data.errors.forEach(function(error) {
                detailsHtml += '<li>' + error + '</li>';
            });
            detailsHtml += '</ul></div>';
        }

        $('#csv-supervisors-import-results .import-stats').html(statsHtml);
        $('#csv-supervisors-import-results .import-details').html(detailsHtml);
        $('#csv-supervisors-import-results').show();
    }

    // ========================================
    // CRÉATION D'UTILISATEUR
    // ========================================
    $("#create-user-form").on("submit", function (e) {
        e.preventDefault();
        
        // Debug: vérifier que les variables AJAX sont disponibles
        if (typeof wp_bmc_admin_ajax === 'undefined') {
            WP_BMC_Toast.error('Variables AJAX non chargées. Rechargez la page.');
            return;
        }

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

        console.log('Envoi AJAX:', formData); // Debug

        $.post(wp_bmc_admin_ajax.ajax_url, formData, function (response) {
            console.log('Réponse AJAX:', response); // Debug
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        })
            .fail(function (xhr, status, error) {
                console.error('Erreur AJAX:', xhr, status, error); // Debug
                WP_BMC_Toast.error("Erreur lors de la création de l'utilisateur: " + error);
            })
            .always(function () {
                $submitBtn.prop("disabled", false).html(originalText);
            });
    });

    // ========================================
    // CRÉATION DE SUPERVISEUR
    // ========================================
    $("#create-supervisor-form").on("submit", function (e) {
        e.preventDefault();
        
        if (typeof wp_bmc_admin_ajax === 'undefined') {
            WP_BMC_Toast.error('Variables AJAX non chargées. Rechargez la page.');
            return;
        }

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();

        $submitBtn
            .prop("disabled", true)
            .html('<i class="fas fa-spinner fa-spin"></i> Création...');

        var formData = {
            action: "wp_bmc_create_supervisor",
            nonce: wp_bmc_admin_ajax.nonce,
            email: $("#supervisor_email").val(),
            password: $("#supervisor_password").val(),
            first_name: $("#supervisor_first_name").val(),
            last_name: $("#supervisor_last_name").val(),
        };

        console.log('Envoi AJAX superviseur:', formData);

        $.post(wp_bmc_admin_ajax.ajax_url, formData, function (response) {
            console.log('Réponse AJAX:', response);
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $form[0].reset();
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        })
            .fail(function (xhr, status, error) {
                console.error('Erreur AJAX:', xhr, status, error);
                WP_BMC_Toast.error("Erreur lors de la création du superviseur: " + error);
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
    // RECHERCHE DE SUPERVISEURS
    // ========================================
    $('#supervisors-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        filterSupervisors(searchTerm);
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
        var $this = $(this);
        
        var newOrder = $this.hasClass('asc') ? 'desc' : 'asc';
        
        sortUsersTable(column, newOrder);
    });
    
    // ========================================
    // ACTIONS SUR LES UTILISATEURS
    // ========================================
    
    // Voir le profil utilisateur
    $(document).on('click', '.view-user-btn', function() {
        var userId = $(this).data('user-id');
        viewUserProfile(userId);
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

    $(document).on('click', '.delete-user-btn', function() {
        var userId = $(this).data('user-id');
        deleteUser(userId);
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
        $('.user-row').hide();
        status 
            ? $('.user-row .user-status span.' + status).closest('.user-row').show()
            : $('.user-row').show();
        updateUsersCount();
    }
    
    // Trier le tableau des utilisateurs
    function sortUsersTable(column, order) {
        console.log('sortUsersTable appelée:', column, order); // Debug
        var $tbody = $('#users-table tbody');
        var $rows = $tbody.find('.user-row').toArray();
        
        $rows.sort(function(a, b) {
            var aVal, bVal;
            
            switch(column) {
                case 'name':
                    // Trier par nom complet (prénom + nom)
                    aVal = $(a).find('.user-name strong').text().trim().toLowerCase();
                    bVal = $(b).find('.user-name strong').text().trim().toLowerCase();
                    break;
                case 'email':
                    // Trier par email
                    aVal = $(a).find('.user-email').text().trim().toLowerCase();
                    bVal = $(b).find('.user-email').text().trim().toLowerCase();
                    break;
                case 'project':
                    // Trier par projet (avec projet vs sans projet)
                    var aHasProject = $(a).find('.no-project').length === 0;
                    var bHasProject = $(b).find('.no-project').length === 0;
                    
                    if (aHasProject && !bHasProject) {
                        aVal = 1; // Avec projet
                        bVal = 0; // Sans projet
                    } else if (!aHasProject && bHasProject) {
                        aVal = 0; // Sans projet
                        bVal = 1; // Avec projet
                    } else {
                        // Même statut, trier par nom de projet
                        aVal = $(a).find('.project-name').text().trim().toLowerCase();
                        bVal = $(b).find('.project-name').text().trim().toLowerCase();
                    }
                    break;
                case 'status':
                    // Trier par statut
                    aVal = $(a).find('.user-status .status-badge').text().trim().toLowerCase();
                    bVal = $(b).find('.user-status .status-badge').text().trim().toLowerCase();
                    break;
                case 'created_at':
                    // Trier par date de création (année)
                    aVal = parseInt($(a).find('.user-created').text()) || 0;
                    bVal = parseInt($(b).find('.user-created').text()) || 0;
                    break;
                default:
                    return 0;
            }
            
            // Comparaison pour le tri
            var result;
            if (typeof aVal === 'string' && typeof bVal === 'string') {
                if (order === 'asc') {
                    result = aVal.localeCompare(bVal);
                } else {
                    result = bVal.localeCompare(aVal);
                }
            } else {
                // Comparaison numérique
                if (order === 'asc') {
                    result = aVal > bVal ? 1 : (aVal < bVal ? -1 : 0);
                } else {
                    result = aVal < bVal ? 1 : (aVal > bVal ? -1 : 0);
                }
            }
            
            // Debug pour les 2 premiers éléments
            if ($rows.indexOf(a) < 2 && $rows.indexOf(b) < 2) {
                console.log('Comparaison:', aVal, 'vs', bVal, 'ordre:', order, 'résultat:', result);
            }
            
            return result;
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

    // Supprimer un utilisateur
    function deleteUser(userId) {
        // Vérifier que l'ID est valide
        if (!userId || userId === 'undefined' || userId === 'null') {
            WP_BMC_Toast.error('ID utilisateur invalide');
            return;
        }

        // Confirmation avec avertissement
        if (!confirm('⚠️ ATTENTION : Cette action va supprimer définitivement cet utilisateur !\n\n\n\nÊtes-vous sûr de vouloir continuer ?')) {
            return;
        }

        // Afficher un loader sur le bouton
        var $deleteBtn = $('.delete-user-btn[data-user-id="' + userId + '"]');
        var originalText = $deleteBtn.html();
        $deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');

        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_delete_user',
            user_id: userId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('Utilisateur supprimé avec succès !');
                
                // Supprimer la ligne du tableau
                $('.user-row[data-user-id="' + userId + '"]').fadeOut(500, function() {
                    $(this).remove();
                    updateUsersCount();
                });
                
            } else {
                WP_BMC_Toast.error('Erreur lors de la suppression : ' + response.data);
                // Restaurer le bouton en cas d'erreur
                $deleteBtn.prop('disabled', false).html(originalText);
            }
        }).fail(function(xhr, status, error) {
            WP_BMC_Toast.error('Erreur de connexion lors de la suppression : ' + error);
            // Restaurer le bouton en cas d'erreur
            $deleteBtn.prop('disabled', false).html(originalText);
        });
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
    
    // ========================================
    // GESTION DES SUPERVISEURS
    // ========================================
    
    // Supprimer un superviseur
    $(document).on('click', '.delete-supervisor-btn', function() {
        var supervisorId = $(this).data('supervisor-id');
        var supervisorName = $(this).data('supervisor-name');
        
        if (!supervisorId || supervisorId === 'undefined' || supervisorId === 'null') {
            WP_BMC_Toast.error('ID superviseur invalide');
            return;
        }

        // Confirmation avec avertissement
        if (!confirm('⚠️ ATTENTION : Vous êtes sur le point de supprimer le superviseur "' + supervisorName + '".\n\nCette action supprimera :\n- Le compte administrateur WordPress\n- Toutes ses associations aux projets\n\nLes projets supervisés seront conservés.\n\nÊtes-vous sûr de vouloir continuer ?')) {
            return;
        }

        var $deleteBtn = $(this);
        var originalText = $deleteBtn.html();
        $deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_delete_supervisor',
            supervisor_id: supervisorId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success('Superviseur supprimé avec succès !');
                
                // Supprimer la ligne du tableau
                $('.supervisor-row[data-user-id="' + supervisorId + '"]').fadeOut(500, function() {
                    $(this).remove();
                    updateSupervisorsCount();
                });
                
            } else {
                WP_BMC_Toast.error('Erreur lors de la suppression : ' + response.data);
                $deleteBtn.prop('disabled', false).html(originalText);
            }
        }).fail(function(xhr, status, error) {
            WP_BMC_Toast.error('Erreur de connexion lors de la suppression : ' + error);
            $deleteBtn.prop('disabled', false).html(originalText);
        });
    });

    // Voir le profil d'un superviseur
    $(document).on('click', '.view-supervisor-profile', function() {
        var supervisorId = $(this).data('supervisor-id');
        // Rediriger vers la page de profil WordPress
        window.location.href = wp_bmc_admin_ajax.admin_url + 'user-edit.php?user_id=' + supervisorId;
    });

    // Réinitialiser le mot de passe d'un superviseur
    $(document).on('click', '.reset-supervisor-password', function() {
        var supervisorId = $(this).data('supervisor-id');
        
        if (!confirm('Voulez-vous vraiment réinitialiser le mot de passe de ce superviseur ?\n\nUn email avec un nouveau mot de passe sera envoyé.')) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(wp_bmc_admin_ajax.ajax_url, {
            action: 'wp_bmc_reset_supervisor_password',
            supervisor_id: supervisorId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        })
        .fail(function() {
            WP_BMC_Toast.error('Erreur de connexion lors de la réinitialisation');
        })
        .always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });

    // Voir les projets d'un superviseur
    $(document).on('click', '.view-supervisor-projects', function() {
        var supervisorId = $(this).data('supervisor-id');
        // Rediriger vers la page des projets avec filtre
        window.location.href = wp_bmc_admin_ajax.admin_url + 'admin.php?page=wp-business-model-canvas-projects&supervisor=' + supervisorId;
    });

    // ========================================
    // FONCTIONS UTILITAIRES - SUPERVISEURS
    // ========================================
    
    function filterSupervisors(searchTerm) {
        var visibleCount = 0;
        
        $('#supervisors-table tbody tr').each(function() {
            var $row = $(this);
            var name = $row.find('.supervisor-name').text().toLowerCase();
            var email = $row.find('.supervisor-email').text().toLowerCase();
            
            var matchesSearch = name.includes(searchTerm) || 
                              email.includes(searchTerm);
            
            if (matchesSearch) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });
        
        // Mettre à jour le compteur
        var totalCount = $('#supervisors-table tbody tr').length;
        if (searchTerm) {
            $('#supervisors-count').html(visibleCount + ' sur ' + totalCount + ' superviseur(s)');
        } else {
            $('#supervisors-count').html(totalCount + ' superviseur(s) au total');
        }
    }
    
    function updateSupervisorsCount() {
        var count = $('#supervisors-table tbody tr').length;
        $('#supervisors-count').html(count + ' superviseur(s) au total');
    }
    
});