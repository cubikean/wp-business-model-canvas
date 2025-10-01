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
    

    $('.edit-project-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        // Stocker l'ID du projet pour l'utiliser dans le formulaire
        $('#edit-project-form').data('project-id', projectId);
        editProject(projectId);
    });

    function editProject(projectId) {
        var $modal = $('#edit-project-modal');
        
        // Charger les données du projet
        $.post(ajaxurl, {
            action: 'wp_bmc_get_project_data',
            project_id: projectId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                var project = response.data.project;
                $('#edit_project_title').val(project.title);
                $('#edit_project_description').val(project.description);
                
                // Afficher la modale
                $modal.show();
                $modal.css('display', 'flex');
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors du chargement des données du projet.');
        });
    }
    
    // Gérer la soumission du formulaire d'édition
    $('#edit-project-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        var projectId = $('#edit-project-form').data('project-id');
        
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');
        
        var formData = {
            action: 'wp_bmc_edit_project',
            project_id: projectId,
            title: $('#edit_project_title').val(),
            description: $('#edit_project_description').val(),
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $('#edit-project-modal').hide();
                location.reload();
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de l\'édition du projet.');
        }).always(function() {
            $submitBtn.prop('disabled', false).html(originalText);
        });
    });

    // Gestion des utilisateurs
    $('.manage-users-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        openManageUsersModal(projectId);
    });

    $('.manage-admins-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        openManageAdminsModal(projectId);
    });

    $('.view-canvas-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        viewCanvas(projectId);
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
        $('#manage-users-modal').css('display', 'flex');
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

    function viewCanvas(projectId) {
        var url = window.location.origin + '/business-model-canvas/?admin_view=true&project_id=' + projectId + '&view=global';
        window.open(url, '_blank');
    }

    function openManageAdminsModal(projectId) {
        $('#manage-admins-modal').show();
        $('#manage-admins-modal').css('display', 'flex');
        loadAvailableAdmins(projectId);
    }

    function loadAvailableAdmins(projectId) {
        var $container = $('#available-admins-list');
        $container.html('<div class="loading">Chargement des superviseurs...</div>');
        
        // Charger les superviseurs disponibles
        $.post(ajaxurl, {
            action: 'wp_bmc_get_available_supervisors',
            project_id: projectId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                var supervisors = response.data.supervisors;
                var html = '';
                
                if (supervisors.length === 0) {
                    html = '<p class="no-admins">Aucun superviseur disponible à assigner.</p>';
                } else {
                    supervisors.forEach(function(admin) {
                        html += '<div class="admin-item available-admin" data-admin-id="' + admin.user_id + '">';
                        html += '<span class="admin-name">';
                        html += admin.display_name;
                        html += ' <small>(' + admin.user_email + ')</small>';
                        html += '</span>';
                        html += '<button class="button button-small add-admin-btn" ';
                        html += 'data-project-id="' + projectId + '" ';
                        html += 'data-admin-id="' + admin.user_id + '">';
                        html += '<i class="fas fa-plus"></i> Assigner';
                        html += '</button>';
                        html += '</div>';
                    });
                }
                
                $container.html(html);
                
                // Gérer l'ajout de superviseurs
                $('.add-admin-btn').on('click', function() {
                    var $btn = $(this);
                    var adminId = $btn.data('admin-id');
                    var projectId = $btn.data('project-id');
                    
                    addSupervisorToProject(projectId, adminId, $btn);
                });
            } else {
                $container.html('<p class="error">Erreur lors du chargement des superviseurs.</p>');
            }
        }).fail(function() {
            $container.html('<p class="error">Erreur de connexion.</p>');
        });
    }
    
    function addSupervisorToProject(projectId, supervisorId, $button) {
        var originalText = $button.html();
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(ajaxurl, {
            action: 'wp_bmc_assign_supervisor_to_project',
            project_id: projectId,
            supervisor_id: supervisorId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        }, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.admin-item').fadeOut(300, function() {
                    $(this).remove();
                });
                // Recharger la liste des superviseurs du projet
                setTimeout(function() {
                    location.reload();
                }, 500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de l\'assignation du superviseur.');
        }).always(function() {
            $button.prop('disabled', false).html(originalText);
        });
    }
    
    // Retirer un superviseur d'un projet
    $(document).on('click', '.remove-admin-btn', function() {
        var projectId = $(this).data('project-id');
        var supervisorId = $(this).data('admin-id');
        
        if (confirm('Êtes-vous sûr de vouloir retirer ce superviseur du projet ?')) {
            removeSupervisorFromProject(projectId, supervisorId, $(this));
        }
    });
    
    function removeSupervisorFromProject(projectId, supervisorId, $button) {
        var formData = {
            action: 'wp_bmc_remove_supervisor_from_project',
            nonce: $('input[name="wp_bmc_admin_nonce"]').val(),
            project_id: projectId,
            supervisor_id: supervisorId
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $button.closest('.admin-item').fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors du retrait du superviseur.');
        });
    }
});
