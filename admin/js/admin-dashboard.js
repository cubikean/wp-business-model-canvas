/**
 * JavaScript pour le dashboard administrateur WP Business Model Canvas
 */

jQuery(document).ready(function($) {
    
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
    // FILTRAGE PAR DEMANDE DE NOTATION
    // ========================================
    $('#users-filter-grading').on('change', function() {
        var gradingStatus = $(this).val();
        filterUsersByGradingStatus(gradingStatus);
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
    // GESTION DES NOTIFICATIONS
    // ========================================
    
    // Marquer une notification comme lue
    $(document).on('click', '.mark-read-btn', function() {
        var $btn = $(this);
        var notificationId = $btn.data('notification-id');
        var $notification = $btn.closest('.notification-item');
        
        $btn.prop('disabled', true).text('En cours...');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_mark_notification_read',
            notification_id: notificationId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
                showMessage('Notification marquée comme lue.', 'success');
            } else {
                showMessage('Erreur : ' + response.data, 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Marquer comme lu');
        });
    });
    
    // Noter une section depuis une notification
    $(document).on('click', '.grade-btn', function() {
        var projectId = $(this).data('project-id');
        var section = $(this).data('section');
        openGradingModal(projectId, section);
    });
    
    // Noter une section depuis les demandes en attente
    $(document).on('click', '.grade-section-btn', function() {
        var projectId = $(this).data('project-id');
        var section = $(this).data('section');
        var sectionTitle = $(this).data('section-title');
        openGradingModal(projectId, section, sectionTitle);
    });
    
    // ========================================
    // ACTIONS SUR LES UTILISATEURS
    // ========================================
    
    // Voir le canvas de l'utilisateur
    $(document).on('click', '.view-user-canvas-btn', function() {
        var userId = $(this).data('user-id');
        var url = window.location.origin + '/business-model-canvas/?admin_view=true&user_id=' + userId;
        window.open(url, '_blank');
    });
    
    // Éditer l'utilisateur
    $(document).on('click', '.edit-user-btn', function() {
        var userId = $(this).data('user-id');
        editUserInPopup(userId);
    });
    
    // ========================================
    // EXPORT DES DONNÉES
    // ========================================
    $('#export-users-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Export en cours...');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_export_users',
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                // Télécharger le fichier
                var link = document.createElement('a');
                link.href = response.data.file_url;
                link.download = 'utilisateurs-bmc.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('Erreur lors de l\'export : ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    $('#export-data-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Export en cours...');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_export_all_data',
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                var link = document.createElement('a');
                link.href = response.data.file_url;
                link.download = 'bmc-data-export.json';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert('Erreur lors de l\'export : ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    $('#clear-cache-btn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Nettoyage...');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_clear_cache',
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                showMessage('Cache vidé avec succès !', 'success');
            } else {
                showMessage('Erreur lors du nettoyage : ' + response.data, 'error');
            }
        }).always(function() {
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // FONCTIONS UTILITAIRES
    // ========================================
    
    // Filtrer les utilisateurs par terme de recherche
    function filterUsers(searchTerm) {
        $('.user-row').each(function() {
            var $row = $(this);
            var name = $row.find('.user-name').text().toLowerCase();
            var email = $row.find('.user-email').text().toLowerCase();
            var company = $row.find('.user-company').text().toLowerCase();
            
            if (name.includes(searchTerm) || email.includes(searchTerm) || company.includes(searchTerm)) {
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
    
    // Filtrer les utilisateurs par statut de demande de notation
    function filterUsersByGradingStatus(gradingStatus) {
        $('.user-row').each(function() {
            var $row = $(this);
            var $gradingStatus = $row.find('.grading-status');
            
            if (gradingStatus === '') {
                $row.show();
            } else if (gradingStatus === 'no-requests' && $gradingStatus.hasClass('no-requests')) {
                $row.show();
            } else if (gradingStatus === 'pending' && $gradingStatus.hasClass('pending')) {
                $row.show();
            } else if (gradingStatus === 'graded' && $gradingStatus.hasClass('graded')) {
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
                case 'name':
                    aVal = $(a).find('.user-name').text().trim();
                    bVal = $(b).find('.user-name').text().trim();
                    break;
                case 'email':
                    aVal = $(a).find('.user-email').text().trim();
                    bVal = $(b).find('.user-email').text().trim();
                    break;
                case 'company':
                    aVal = $(a).find('.user-company').text().trim();
                    bVal = $(b).find('.user-company').text().trim();
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
                case 'grading_status':
                    var aStatus = $(a).find('.grading-status').attr('class');
                    var bStatus = $(b).find('.grading-status').attr('class');
                    // Priorité : pending > graded > no-requests > other
                    var statusPriority = {
                        'pending': 1,
                        'graded': 2,
                        'no-requests': 3,
                        'other': 4
                    };
                    aVal = 5; // valeur par défaut
                    bVal = 5;
                    for (var status in statusPriority) {
                        if (aStatus && aStatus.includes(status)) aVal = statusPriority[status];
                        if (bStatus && bStatus.includes(status)) bVal = statusPriority[status];
                    }
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
    
    // Fonction pour éditer un utilisateur dans une popup
    function editUserInPopup(userId) {
        var popup = $('<div class="wp-bmc-popup user-edit-popup">' +
            '<div class="popup-overlay"></div>' +
            '<div class="popup-content">' +
                '<div class="popup-header">' +
                    '<h3>Éditer l\'utilisateur</h3>' +
                    '<button class="popup-close">&times;</button>' +
                '</div>' +
                '<div class="popup-body">' +
                    '<div class="user-edit-loading">Chargement...</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(popup);
        popup.fadeIn(300);
        
        // Charger le formulaire d'édition
        $.post(ajaxurl, {
            action: 'wp_bmc_get_user_edit_form',
            user_id: userId,
            nonce: wp_bmc_admin_ajax.nonce
        }, function(response) {
            if (response.success) {
                popup.find('.user-edit-loading').html(response.data.html);
            } else {
                popup.find('.user-edit-loading').html('<div class="error">Erreur lors du chargement du formulaire.</div>');
            }
        });
        
        // Gérer la fermeture
        popup.find('.popup-close, .popup-overlay').on('click', function() {
            popup.fadeOut(300, function() {
                popup.remove();
            });
        });
    }
    
    // Sauvegarder les modifications d'un utilisateur
    $(document).on('submit', '#user-edit-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        
        $submitBtn.prop('disabled', true).text('Sauvegarde...');
        
        var formData = $form.serialize();
        formData += '&action=wp_bmc_update_user&nonce=' + wp_bmc_admin_ajax.nonce;
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                showMessage('Utilisateur mis à jour avec succès.', 'success');
                $('.user-edit-popup').fadeOut(300, function() {
                    $(this).remove();
                });
                // Recharger la page pour voir les changements
                window.location.reload();
            } else {
                showMessage('Erreur lors de la mise à jour : ' + response.data, 'error');
            }
        }).always(function() {
            $submitBtn.prop('disabled', false).text(originalText);
        });
    });
    
    // Fonction pour afficher des messages
    function showMessage(message, type) {
        var $message = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wp-bmc-admin-dashboard').prepend($message);
        
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $message.remove();
            });
        }, 3000);
    }
    
    // Fonction pour ouvrir la modal de notation
    function openGradingModal(projectId, section, sectionTitle) {
        var modal = $('<div class="wp-bmc-popup grading-modal">' +
            '<div class="popup-overlay"></div>' +
            '<div class="popup-content">' +
                '<div class="popup-header">' +
                    '<h3>Noter la section : ' + (sectionTitle || section) + '</h3>' +
                    '<button class="popup-close">&times;</button>' +
                '</div>' +
                '<div class="popup-body">' +
                    '<form id="grading-form">' +
                        '<div class="grading-field">' +
                            '<label for="rating">Note (0-10) :</label>' +
                            '<input type="number" id="rating" name="rating" min="0" max="10" required>' +
                        '</div>' +
                        '<div class="grading-field">' +
                            '<label for="comment">Commentaire :</label>' +
                            '<textarea id="comment" name="comment" rows="4" placeholder="Commentaires sur cette section..."></textarea>' +
                        '</div>' +
                        '<div class="grading-actions">' +
                            '<button type="submit" class="button button-primary">Sauvegarder la note</button>' +
                            '<button type="button" class="button popup-close">Annuler</button>' +
                        '</div>' +
                    '</form>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append(modal);
        modal.fadeIn(300);
        
        // Gérer la soumission du formulaire
        modal.find('#grading-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            $submitBtn.prop('disabled', true).text('Sauvegarde...');
            
            var formData = {
                action: 'wp_bmc_save_section_rating',
                project_id: projectId,
                section: section,
                rating: $('#rating').val(),
                comment: $('#comment').val(),
                nonce: wp_bmc_admin_ajax.nonce
            };
            
            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    showMessage('Note sauvegardée avec succès !', 'success');
                    modal.fadeOut(300, function() {
                        modal.remove();
                    });
                    // Recharger la page pour voir les changements
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage('Erreur lors de la sauvegarde : ' + response.data, 'error');
                }
            }).always(function() {
                $submitBtn.prop('disabled', false).text(originalText);
            });
        });
        
        // Gérer la fermeture
        modal.find('.popup-close, .popup-overlay').on('click', function() {
            modal.fadeOut(300, function() {
                modal.remove();
            });
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
    
});
