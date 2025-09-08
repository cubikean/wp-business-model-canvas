/**
 * JavaScript pour la gestion des utilisateurs dans l'admin
 * Fonctionnalités : recherche, tri, actions sur les utilisateurs
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
        $.post(ajaxurl, {
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
        // Rediriger vers une page d'édition ou ouvrir une popup
        window.location.href = 'admin.php?page=wp-business-model-canvas&action=edit_user&user_id=' + userId;
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
        $.post(ajaxurl, {
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
    
});
