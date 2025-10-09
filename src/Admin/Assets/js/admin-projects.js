/**
 * JavaScript pour la page d'administration des projets
 * WP Business Model Canvas v2.0
 */

jQuery(document).ready(function($) {
    // ========================================
    // IMPORT CSV PROJETS
    // ========================================
    
    // Afficher le nom du fichier sélectionné pour les projets
    $('#csv-projects-file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $('.csv-projects-file-name').text('Fichier sélectionné : ' + fileName).show();
            $('#csv-projects-file').siblings('.csv-upload-label').find('span').text('Fichier : ' + fileName);
        }
    });

    // Gérer l'import CSV des projets
    $("#import-projects-csv-form").on("submit", function (e) {
        e.preventDefault();
        
        var fileInput = $('#csv-projects-file')[0];
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
        $('#csv-projects-import-results').hide();

        var formData = new FormData();
        formData.append('action', 'wp_bmc_import_csv_projects');
        formData.append('nonce', $('input[name="wp_bmc_admin_nonce"]').val());
        formData.append('csv_file', file);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Réponse AJAX projets:', response);
                
                if (response.success) {
                    WP_BMC_Toast.success(response.data.message);
                    
                    // Afficher les résultats
                    displayProjectsImportResults(response.data);
                    
                    // Réinitialiser le formulaire
                    $('#import-projects-csv-form')[0].reset();
                    $('.csv-projects-file-name').hide();
                    $('#csv-projects-file').siblings('.csv-upload-label').find('span').text('Choisir un fichier CSV');
                    
                    // Recharger la page après 3 secondes si des projets ont été créés
                    if (response.data.created > 0) {
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    }
                } else {
                    WP_BMC_Toast.error(response.data || 'Erreur lors de l\'import.');
                    if (response.data && response.data.errors) {
                        displayProjectsImportResults(response.data);
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

    function displayProjectsImportResults(data) {
        var statsHtml = '<div class="import-stats-grid">';
        statsHtml += '<div class="stat-success"><i class="fas fa-check-circle"></i> <strong>' + data.created + '</strong> créés</div>';
        statsHtml += '<div class="stat-skipped"><i class="fas fa-exclamation-triangle"></i> <strong>' + data.skipped + '</strong> ignorés</div>';
        statsHtml += '<div class="stat-error"><i class="fas fa-times-circle"></i> <strong>' + data.errors.length + '</strong> erreurs</div>';
        statsHtml += '</div>';

        var detailsHtml = '';
        
        if (data.created_projects && data.created_projects.length > 0) {
            detailsHtml += '<div class="import-section success-section">';
            detailsHtml += '<h4><i class="fas fa-check-circle"></i> Projets créés avec succès</h4>';
            detailsHtml += '<ul>';
            data.created_projects.forEach(function(project) {
                detailsHtml += '<li><strong>' + project.title + '</strong>';
                if (project.user_assigned) {
                    detailsHtml += ' - Utilisateur: ' + project.user_email;
                }
                if (project.supervisor_assigned) {
                    detailsHtml += ' - Superviseur: ' + project.supervisor_email;
                }
                detailsHtml += '</li>';
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

        $('#csv-projects-import-results .import-stats').html(statsHtml);
        $('#csv-projects-import-results .import-details').html(detailsHtml);
        $('#csv-projects-import-results').show();
    }
    
    // ========================================
    // Création d'un projet
    // ========================================
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

    // Gestion de la suppression de projet
    $('.delete-project-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        var projectTitle = $(this).data('project-title');
        openDeleteProjectModal(projectId, projectTitle);
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

    // Confirmation de suppression de projet
    $('#confirm-delete-project').on('click', function() {
        var projectId = $(this).data('project-id');
        deleteProject(projectId);
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

    // Fonction pour ouvrir le modal de suppression de projet
    function openDeleteProjectModal(projectId, projectTitle) {
        var $modal = $('#delete-project-modal');
        
        // Mettre à jour le titre du projet dans le modal
        $('#delete-project-title').text(projectTitle);
        
        // Stocker l'ID du projet pour l'utiliser lors de la confirmation
        $('#confirm-delete-project').data('project-id', projectId);
        
        // Afficher le modal
        $modal.show();
        $modal.css('display', 'flex');
    }

    // Fonction pour supprimer un projet
    function deleteProject(projectId) {
        var $confirmBtn = $('#confirm-delete-project');
        var originalText = $confirmBtn.html();
        
        $confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');
        
        var formData = {
            action: 'wp_bmc_admin_delete_project',
            project_id: projectId,
            nonce: $('input[name="wp_bmc_admin_nonce"]').val()
        };
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                $('#delete-project-modal').hide();
                
                // Supprimer la carte du projet de l'interface
                $('.project-card[data-project-id="' + projectId + '"]').fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Recharger la page après un délai pour mettre à jour les statistiques
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur lors de la suppression du projet.');
        }).always(function() {
            $confirmBtn.prop('disabled', false).html(originalText);
        });
    }
});
