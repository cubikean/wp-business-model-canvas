/**
 * JavaScript pour la page d'administration des projets
 * WP Business Model Canvas v2.0
 */

jQuery(document).ready(function($) {
    // Création d'un projet
    $('#create-project-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');
        
        var formData = {
            action: 'wp_bmc_create_project',
            nonce: $form.find('[name="wp_bmc_admin_nonce"]').val(),
            title: $('#project_title').val(),
            description: $('#project_description').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de la création du projet.');
        }).always(function() {
            $submitBtn.prop('disabled', false).html(originalText);
        });
    });
    
    // Gestion des utilisateurs
    $('.manage-users-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        openManageUsersModal(projectId);
    });
    
    // Retirer un utilisateur d'un projet
    $('.remove-user-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        var userId = $(this).data('user-id');
        
        if (confirm('Êtes-vous sûr de vouloir retirer cet utilisateur du projet ?')) {
            removeUserFromProject(projectId, userId, $(this));
        }
    });
    
    // Fermer les modales
    $('.modal-close').on('click', function() {
        $(this).closest('.wp-bmc-modal').hide();
    });
    
    function openManageUsersModal(projectId) {
        $('#manage-users-modal').show();
        loadAvailableUsers(projectId);
    }
    
    function loadAvailableUsers(projectId) {
        var $container = $('#available-users-list');
        $container.html('<div class="loading">Chargement des utilisateurs...</div>');
        
        // Charger les utilisateurs disponibles
        $.post(ajaxurl, {
            action: 'wp_bmc_get_available_users',
            project_id: projectId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                var users = response.data.users;
                var html = '';
                
                if (users.length === 0) {
                    html = '<p class="no-users">Aucun utilisateur disponible à assigner.</p>';
                } else {
                    users.forEach(function(user) {
                        html += '<div class="user-item available-user" data-user-id="' + user.user_id + '">';
                        html += '<span class="user-name">';
                        html += user.first_name + ' ' + user.last_name;
                        if (user.custom_id) {
                            html += ' <small>(' + user.custom_id + ')</small>';
                        }
                        html += '</span>';
                        html += '<button class="button button-small add-user-btn" ';
                        html += 'data-project-id="' + projectId + '" ';
                        html += 'data-user-id="' + user.user_id + '">';
                        html += '<i class="fas fa-plus"></i> Ajouter';
                        html += '</button>';
                        html += '</div>';
                    });
                }
                
                $container.html(html);
                
                // Gérer l'ajout d'utilisateurs
                $('.add-user-btn').on('click', function() {
                    var $btn = $(this);
                    var userId = $btn.data('user-id');
                    var projectId = $btn.data('project-id');
                    
                    addUserToProject(projectId, userId, $btn);
                });
            } else {
                $container.html('<p class="error">Erreur lors du chargement des utilisateurs.</p>');
            }
        }).fail(function() {
            $container.html('<p class="error">Erreur de connexion.</p>');
        });
    }
    
    function addUserToProject(projectId, userId, $button) {
        var originalText = $button.html();
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_assign_user_to_project',
            project_id: projectId,
            user_id: userId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.user-item').fadeOut(300, function() {
                    $(this).remove();
                });
                // Recharger la liste des utilisateurs du projet
                location.reload();
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de l\'ajout de l\'utilisateur.');
        }).always(function() {
            $button.prop('disabled', false).html(originalText);
        });
    }
    
    function removeUserFromProject(projectId, userId, $button) {
        var formData = {
            action: 'wp_bmc_remove_user_from_project',
            nonce: $('input[name="wp_bmc_admin_nonce"]').val(),
            project_id: projectId,
            user_id: userId
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.user-item').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors du retrait de l\'utilisateur.');
        });
    }
});
