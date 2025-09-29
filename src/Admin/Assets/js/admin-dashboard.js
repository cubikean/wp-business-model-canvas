/**
 * JavaScript pour le dashboard administrateur WP Business Model Canvas
 */

jQuery(document).ready(function ($) {
 
  // ========================================
  // RECHERCHE D'UTILISATEURS
  // ========================================
  $("#users-search").on("input", function () {
    var searchTerm = $(this).val().toLowerCase();
    filterUsers(searchTerm);
  });

  // ========================================
  // FILTRAGE PAR STATUT
  // ========================================
  $("#users-filter-status").on("change", function () {
    var status = $(this).val();
    filterUsersByStatus(status);
  });

  // ========================================
  // FILTRAGE PAR DEMANDE DE NOTATION
  // ========================================
  $("#users-filter-grading").on("change", function () {
    var gradingStatus = $(this).val();
    filterUsersByGradingStatus(gradingStatus);
  });

  // ========================================
  // FILTRAGE PAR GROUPE (MES ÉTUDIANTS)
  // ========================================
  $("#users-filter-group").on("change", function () {
    var groupFilter = $(this).val();
    filterUsersByGroup(groupFilter);
  });

  // ========================================
  // TRI DES COLONNES
  // ========================================
  $(".sortable").on("click", function () {
    var column = $(this).data("sort");
    var currentOrder = $(this).hasClass("asc") ? "desc" : "asc";

    // Réinitialiser tous les indicateurs de tri
    $(".sortable").removeClass("asc desc");

    // Ajouter la classe de tri à la colonne cliquée
    $(this).addClass(currentOrder);

    // Trier le tableau
    sortUsersTable(column, currentOrder);
  });

  // ========================================
  // GESTION DES NOTIFICATIONS
  // ========================================

  // Marquer une notification comme lue
  $(document).on("click", ".mark-read-btn", function () {
    var $btn = $(this);
    var notificationId = $btn.data("notification-id");
    var $notification = $btn.closest(".notification-item");

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_mark_notification_read",
        notification_id: notificationId,
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          $notification.fadeOut(300, function () {
            $(this).remove();
            updateNotificationCount();
          });
          WP_BMC_Toast.success("Notification marquée comme lue.");
        } else {
          WP_BMC_Toast.error("Erreur : " + response.data);
        }
      }
    ).always(function () {
      $btn.prop("disabled", false).html('<i class="fas fa-check"></i>');
    });
  });

  // Marquer toutes les notifications comme lues
  $(document).on("click", ".mark-all-read-btn", function () {
    var $btn = $(this);
    var $notifications = $(".notification-item");

    if ($notifications.length === 0) {
      return;
    }

    $btn
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i> En cours...');

    // Marquer toutes les notifications comme lues
    var promises = [];
    $notifications.each(function () {
      var $notification = $(this);
      var notificationId = $notification.data("notification-id");

      promises.push(
        $.post(wp_bmc_admin_ajax.ajax_url, {
          action: "wp_bmc_mark_notification_read",
          notification_id: notificationId,
          nonce: wp_bmc_admin_ajax.nonce,
        })
      );
    });

    Promise.all(promises)
      .then(function (responses) {
        var successCount = 0;
        responses.forEach(function (response) {
          if (response.success) {
            successCount++;
          }
        });

        if (successCount > 0) {
          $notifications.fadeOut(300, function () {
            $(this).remove();
            updateNotificationCount();
          });
          WP_BMC_Toast.success(
            successCount + " notification(s) marquée(s) comme lue(s)."
          );
        } else {
          WP_BMC_Toast.error("Erreur lors du marquage des notifications.");
        }
      })
      .catch(function () {
        WP_BMC_Toast.error("Erreur lors du marquage des notifications.");
      })
      .finally(function () {
        $btn
          .prop("disabled", false)
          .html('<i class="fas fa-check-double"></i> Tout marquer comme lu');
      });
  });

  // Noter une section depuis une notification
  $(document).on("click", ".grade-btn", function () {
    var projectId = $(this).data("project-id");
    var userId = $(this).data("user-id");
    var section = $(this).data("section");

    // Rediriger vers le canvas de l'utilisateur avec vue admin et auto-ouvrir la popup de notation
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&view=global&user_id=" +
      userId +
      "&project_id=" +
      projectId +
      "&auto_grade_section=" +
      encodeURIComponent(section);
    window.open(url, "_blank");
  });

  // Noter une section depuis les demandes en attente
  $(document).on("click", ".grade-section-btn", function () {
    var projectId = $(this).data("project-id");
    var userId = $(this).data("user-id");
    var section = $(this).data("section");

    // Rediriger vers le canvas de l'utilisateur avec vue admin
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&view=global&user_id=" +
      userId +
      "&project_id=" +
      projectId +
      "&open_section=" +
      encodeURIComponent(section);
    window.open(url, "_blank");
  });

  $(document).on("click", ".rate-brick-btn", function () {
    openGradingModal(
      $(this).data("project-id"),
      $(this).data("section"),
      $(this).data("section-title")
    );
  });

  $(document).on("click", "#inner-rate-brick-btn", function () {
      let projectId = $(".wp-bmc-dashboard").data("project-id");
      let section = $("#wp-bmc-edit-view").data("section");
      let defSection = $("#wp-bmc-edit-view")
      console.log('Section:', section);
      let sectionTitle = $("#wp-bmc-edit-view #edit-section-title").text();
        
      openGradingModal(projectId, section, sectionTitle);
   
  });

  // ========================================
  // ACTIONS SUR LES UTILISATEURS
  // ========================================

  // Voir le canvas de l'utilisateur
  $(document).on("click", ".view-user-canvas-btn", function () {
    var userId = $(this).data("user-id");
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&view=global&user_id=" +
      userId;
    window.open(url, "_blank");
  });

  // Éditer l'utilisateur
  $(document).on("click", ".edit-user-btn", function () {
    var userId = $(this).data("user-id");
    editUserInPopup(userId);
  });

  // ========================================
  // GESTION DES GROUPES D'ÉTUDIANTS
  // ========================================

  // Ajouter un étudiant à mes étudiants
  $(document).on("click", ".add-student-btn", function () {
    var $btn = $(this);
    var userId = $btn.data("user-id");
    var userName = $btn.data("user-name");

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_add_student",
        student_id: userId,
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          WP_BMC_Toast.success(response.data.message);
          // Mettre à jour l'interface
          updateStudentGroupStatus(userId, true);
        } else {
          WP_BMC_Toast.error(response.data);
        }
      }
    )
      .fail(function () {
        WP_BMC_Toast.error("Erreur lors de l'ajout de l'étudiant.");
      })
      .always(function () {
        $btn.prop("disabled", false).html('<i class="fas fa-user-plus"></i>');
      });
  });

  // Retirer un étudiant de mes étudiants
  $(document).on("click", ".remove-student-btn", function () {
    var $btn = $(this);
    var userId = $btn.data("user-id");
    var userName = $btn.data("user-name");

    // Demander confirmation
    if (
      !confirm(
        "Êtes-vous sûr de vouloir retirer " + userName + " de vos étudiants ?"
      )
    ) {
      return;
    }

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_remove_student",
        student_id: userId,
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          WP_BMC_Toast.success(response.data.message);
          // Mettre à jour l'interface
          updateStudentGroupStatus(userId, false);
        } else {
          WP_BMC_Toast.error(response.data);
        }
      }
    )
      .fail(function () {
        WP_BMC_Toast.error("Erreur lors de la suppression de l'étudiant.");
      })
      .always(function () {
        $btn.prop("disabled", false).html('<i class="fas fa-user-minus"></i>');
      });
  });

  // ========================================
  // EXPORT DES DONNÉES
  // ========================================

  // ========================================
  // FONCTIONS UTILITAIRES
  // ========================================

  // Filtrer les utilisateurs par terme de recherche
  function filterUsers(searchTerm) {
    $(".user-row").each(function () {
      var $row = $(this);
      var name = $row.find(".user-name").text().toLowerCase();
      var email = $row.find(".user-email").text().toLowerCase();

      if (
        name.includes(searchTerm) ||
        email.includes(searchTerm)      ) {
        $row.show();
      } else {
        $row.hide();
      }
    });

    updateUsersCount();
  }

  // Filtrer les utilisateurs par statut
  function filterUsersByStatus(status) {
    $(".user-row").each(function () {
      var $row = $(this);
      var projectCount = parseInt($row.find(".project-count").text());

      if (status === "") {
        $row.show();
      } else if (status === "active" && projectCount > 0) {
        $row.show();
      } else if (status === "inactive" && projectCount === 0) {
        $row.show();
      } else {
        $row.hide();
      }
    });

    updateUsersCount();
  }

  // Filtrer les utilisateurs par statut de demande de notation
  function filterUsersByGradingStatus(gradingStatus) {
    $(".user-row").each(function () {
      var $row = $(this);
      var $gradingStatus = $row.find(".grading-status");

      if (gradingStatus === "") {
        $row.show();
      } else if (
        gradingStatus === "no-requests" &&
        $gradingStatus.hasClass("no-requests")
      ) {
        $row.show();
      } else if (
        gradingStatus === "pending" &&
        $gradingStatus.hasClass("pending")
      ) {
        $row.show();
      } else if (
        gradingStatus === "graded" &&
        $gradingStatus.hasClass("graded")
      ) {
        $row.show();
      } else {
        $row.hide();
      }
    });

    updateUsersCount();
  }

  // Filtrer les utilisateurs par groupe (mes étudiants)
  function filterUsersByGroup(groupFilter) {
    $(".user-row").each(function () {
      var $row = $(this);
      var $groupStatus = $row.find(".group-status");
      var groupText = $groupStatus.find("span").text().trim();

      if (groupFilter === "") {
        $row.show();
      } else if (
        groupFilter === "my-students" &&
        groupText === "Mon étudiant"
      ) {
        $row.show();
      } else if (
        groupFilter === "managed-students" &&
        $groupStatus.hasClass("managed-student")
      ) {
        $row.show();
      } else if (
        groupFilter === "unmanaged-students" &&
        $groupStatus.hasClass("not-managed")
      ) {
        $row.show();
      } else {
        $row.hide();
      }
    });

    updateUsersCount();
  }

  // Mettre à jour le statut de groupe d'un utilisateur
  function updateStudentGroupStatus(userId, isMyStudent) {
    var $row = $('.user-row[data-user-id="' + userId + '"]');
    var $groupCell = $row.find(".user-group");
    var $actionCell = $row.find(".user-actions");
    var userName =
      $actionCell.find(".add-student-btn").data("user-name") ||
      $actionCell.find(".remove-student-btn").data("user-name");

    if (isMyStudent) {
      // Mettre à jour le statut de groupe
      $groupCell.html(
        '<span class="group-status managed-student">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4"/></svg>' +
          "<span>Mon étudiant</span>" +
          "</span>"
      );

      // Mettre à jour le bouton d'action
      $actionCell
        .find(".add-student-btn")
        .replaceWith(
          '<button class="button action-button button-small button-secondary remove-student-btn" ' +
            'data-user-id="' +
            userId +
            '" ' +
            'data-user-name="' +
            userName +
            '" ' +
            'title="Retirer de mes étudiants">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m-9-2V8c0-.55-.45-1-1-1s-1 .45-1 1v2H2c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1v-2h2c.55 0 1-.45 1-1s-.45-1-1-1zm9 4c-2.67 0-8 1.34-8 4v1c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-1c0-2.66-5.33-4-8-4"/></svg>' +
            "</button>"
        );
    } else {
      // Mettre à jour le statut de groupe
      $groupCell.html(
        '<span class="group-status not-managed">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2m-2 15l-5-5l1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9"/></svg>' +
          "<span>Non assigné</span>" +
          "</span>"
      );

      // Mettre à jour le bouton d'action
      $actionCell
        .find(".remove-student-btn")
        .replaceWith(
          '<button class="button action-button button-small button-primary add-student-btn" ' +
            'data-user-id="' +
            userId +
            '" ' +
            'data-user-name="' +
            userName +
            '" ' +
            'title="Ajouter à mes étudiants">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="currentColor" d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4m-9-2V8c0-.55-.45-1-1-1s-1 .45-1 1v2H2c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1v-2h2c.55 0 1-.45 1-1s-.45-1-1-1zm9 4c-2.67 0-8 1.34-8 4v1c0 .55.45 1 1 1h14c.55 0 1-.45 1-1v-1c0-2.66-5.33-4-8-4"/></svg>' +
            "</button>"
        );
    }
  }

  // Trier le tableau des utilisateurs
  function sortUsersTable(column, order) {
    var $tbody = $("#users-table tbody");
    var $rows = $tbody.find(".user-row").toArray();

    $rows.sort(function (a, b) {
      var aVal, bVal;

      switch (column) {
        case "name":
          aVal = $(a).find(".user-name").text().trim();
          bVal = $(b).find(".user-name").text().trim();
          break;
        case "email":
          aVal = $(a).find(".user-email").text().trim();
          bVal = $(b).find(".user-email").text().trim();
          break;
        case "project_count":
          aVal = parseInt($(a).find(".project-count").text()) || 0;
          bVal = parseInt($(b).find(".project-count").text()) || 0;
          break;
        case "created_at":
          aVal = new Date($(a).find(".user-registration").text());
          bVal = new Date($(b).find(".user-registration").text());
          break;
        case "last_project_date":
          var aText = $(a).find(".user-last-project").text().trim();
          var bText = $(b).find(".user-last-project").text().trim();
          aVal = aText === "Aucun projet" ? new Date(0) : new Date(aText);
          bVal = bText === "Aucun projet" ? new Date(0) : new Date(bText);
          break;
        case "grading_status":
          var aStatus = $(a).find(".grading-status").attr("class");
          var bStatus = $(b).find(".grading-status").attr("class");
          // Priorité : pending > graded > no-requests > other
          var statusPriority = {
            pending: 1,
            graded: 2,
            "no-requests": 3,
            other: 4,
          };
          aVal = 5; // valeur par défaut
          bVal = 5;
          for (var status in statusPriority) {
            if (aStatus && aStatus.includes(status))
              aVal = statusPriority[status];
            if (bStatus && bStatus.includes(status))
              bVal = statusPriority[status];
          }
          break;
        case "is_my_student":
          var aGroupText = $(a).find(".group-status span").text().trim();
          var bGroupText = $(b).find(".group-status span").text().trim();
          var aVal = aGroupText === "Mon étudiant" ? 1 : 0;
          var bVal = bGroupText === "Mon étudiant" ? 1 : 0;
          break;
        default:
          return 0;
      }

      if (order === "asc") {
        return aVal > bVal ? 1 : -1;
      } else {
        return aVal < bVal ? 1 : -1;
      }
    });

    // Réorganiser les lignes dans le DOM
    $.each($rows, function (index, row) {
      $tbody.append(row);
    });
  }

  // Mettre à jour le compteur d'utilisateurs
  function updateUsersCount() {
    var visibleCount = $(".user-row:visible").length;
    var totalCount = $(".user-row").length;
    $("#users-count").text(visibleCount + " utilisateur(s) sur " + totalCount);
  }

  // Fonction pour mettre à jour le compteur de notifications
  function updateNotificationCount() {
    var remainingNotifications = $(".notification-item").length;
    var $badge = $(".notification-badge");
    var $markAllBtn = $(".mark-all-read-btn");

    if (remainingNotifications === 0) {
      $badge.remove();
      $markAllBtn.remove();

      // Afficher le message "Aucune notification"
      if (!$(".no-notifications").length) {
        $(".notifications-list").html(
          '<div class="no-notifications">' +
            '<i class="fas fa-bell-slash"></i>' +
            "<p>Aucune notification non lue.</p>" +
            "</div>"
        );
      }
    } else {
      $badge.text(remainingNotifications);
    }
  }

  // Fonction pour supprimer les demandes de notation du dashboard
  function removeGradingRequestsFromDashboard(projectId, section) {
    // Supprimer les demandes correspondantes dans la section des demandes en attente
    $(".grading-request-item").each(function () {
      var $item = $(this);
      var itemProjectId = $item.find(".grade-section-btn").data("project-id");
      var itemSection = $item.find(".grade-section-btn").data("section");

      if (itemProjectId == projectId && itemSection == section) {
        $item.fadeOut(300, function () {
          $(this).remove();
        });
      }
    });

    // Supprimer les notifications correspondantes
    $(".notification-item").each(function () {
      var $notification = $(this);
      var $gradeBtn = $notification.find(".grade-btn");

      if ($gradeBtn.length > 0) {
        var notificationProjectId = $gradeBtn.data("project-id");
        var notificationSection = $gradeBtn.data("section");

        if (
          notificationProjectId == projectId &&
          notificationSection == section
        ) {
          $notification.fadeOut(300, function () {
            $(this).remove();
            updateNotificationCount();
          });
        }
      }
    });

    // Mettre à jour le compteur dans le tableau des utilisateurs
    updateUserGradingCount(projectId, section);
  }

  // Fonction pour mettre à jour le compteur de demandes de notation
  function updateGradingRequestsCount() {
    var remainingRequests = $(".grading-request-item").length;
    var $countBadge = $(".grading-count");
    var $gradingSection = $(".grading-requests-section");

    if (remainingRequests === 0) {
      // Masquer toute la section des demandes de notation
      $gradingSection.fadeOut(300, function () {
        $(this).remove();
      });
    } else {
      $countBadge.text(remainingRequests);
    }
  }

  // Fonction pour mettre à jour le compteur de demandes dans le tableau des utilisateurs
  function updateUserGradingCount(projectId, section) {
    // Trouver l'utilisateur correspondant au projet noté
    $(".grading-request-item").each(function () {
      var $item = $(this);
      var itemProjectId = $item.find(".grade-section-btn").data("project-id");
      var itemUserId = $item.find(".grade-section-btn").data("user-id");

      if (itemProjectId == projectId) {
        // Trouver la ligne utilisateur correspondante
        var $userRow = $('.user-row[data-user-id="' + itemUserId + '"]');

        if ($userRow.length > 0) {
          // Faire une requête AJAX pour obtenir les données mises à jour
          $.post(
            wp_bmc_admin_ajax.ajax_url,
            {
              action: "wp_bmc_get_user_grading_count",
              user_id: itemUserId,
              nonce: wp_bmc_admin_ajax.nonce,
            },
            function (response) {
              if (response.success) {
                var $gradingStatus = $userRow.find(".user-grading-status");
                var totalGradingCount =
                  response.data.total_grading_requests_count;
                var pendingGradingCount =
                  response.data.pending_grading_requests_count;
                var gradingStatuses = response.data.grading_statuses;

                // Mettre à jour l'affichage du statut de notation
                if (totalGradingCount == 0) {
                  $gradingStatus.html(
                    '<span class="grading-status no-requests">' +
                      '<i class="fas fa-check-circle"></i> Aucune demande' +
                      "</span>"
                  );
                } else {
                  var statusHtml = "";
                  var statusClass = "";
                  var statusIcon = "";
                  var statusText = "";
                  var displayCount = 0;

                  if (gradingStatuses.includes("pending")) {
                    statusClass = "pending";
                    statusIcon = "fas fa-clock";
                    statusText = "En attente";
                    displayCount = pendingGradingCount;
                  } else if (gradingStatuses.includes("graded")) {
                    statusClass = "graded";
                    statusIcon = "fas fa-check-circle";
                    statusText = "Noté";
                    displayCount = totalGradingCount;
                  } else {
                    statusClass = "other";
                    statusIcon = "fas fa-info-circle";
                    statusText = "Autre";
                    displayCount = totalGradingCount;
                  }

                  statusHtml =
                    '<span class="grading-status ' +
                    statusClass +
                    '">' +
                    '<i class="' +
                    statusIcon +
                    '"></i> ' +
                    statusText +
                    '<span class="request-count">(' +
                    displayCount +
                    ")</span>" +
                    "</span>";

                  $gradingStatus.html(statusHtml);
                }
              }
            }
          );
        }
        return false; // Sortir de la boucle une fois trouvé
      }
    });
  }

  // Fonction pour ouvrir la modal de notation
  function openGradingModal(projectId, section, sectionTitle) {
    
    console.log('=== openGradingModal appelée ===');
    console.log('Project ID reçu:', projectId);
    console.log('Section reçue:', section);
    console.log('Section Title reçu:', sectionTitle);
    
    // Supprimer les modals de notation existants pour éviter les conflits
    $('.grading-modal').remove();
    
    var modal = $(
      '<div class="wp-bmc-popup grading-modal" data-section="' +
        section +
        '">' +
        '<div class="popup-overlay"></div>' +
        '<div class="popup-content">' +
        '<div class="popup-header">' +
        "<h3>Score de maturité</h3>" +
        '<span class="section-title">' +
        sectionTitle +
        "</span>" +
        '<button class="popup-close">&times;</button>' +
        "</div>" +
        '<div class="popup-body">' +
        '<form id="grading-form" data-project-id="' +
        projectId +
        '">' +
        '<div class="grading-field">' +
        '<div class="rating-slider-container">' +
        '<div class="rating-slider-wrapper">' +
        '<input type="range" id="rating" name="rating" min="0" max="10" value="5" step="1" required>' +
        "</div>" +
        '<div class="rating-display">' +
        '<span id="rating-value">5</span>' +
        "</div>" +
        "</div>" +
        "</div>" +
        '<div class="grading-field">' +
        '<textarea id="comment" name="comment" rows="7" placeholder="Commentaires sur cette section..."></textarea>' +
        "</div>" +
        '<div class="grading-actions">' +
        '<button type="submit" class="wp-bmc-btn wp-bmc-btn-primary btn-solid">Enregistrer</button>' +
        "</div>" +
        "</form>" +
        "</div>" +
        "</div>" +
        "</div>"
    );

    $("body").append(modal);
    modal.fadeIn(300);

    // Mettre à jour l'affichage de la note en temps réel
    modal.find("#rating").on("input", function () {
      var value = $(this).val();
      modal.find("#rating-value").text(value);

      // Mettre à jour la couleur de progression du slider
      var progress = (value / 10) * 100;
      modal
        .find(".rating-slider-wrapper")
        .css("--slider-progress", progress + "%");
    });

    // Initialiser la couleur de progression au chargement
    var initialValue = modal.find("#rating").val();
    var initialProgress = (initialValue / 10) * 100;
    modal
      .find(".rating-slider-wrapper")
      .css("--slider-progress", initialProgress + "%");

    // Gérer la soumission du formulaire
    modal.find("#grading-form").on("submit", function (e) {
      e.preventDefault();

      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');
      var originalText = $submitBtn.text();

      $submitBtn.prop("disabled", true).text("Sauvegarde...");

      var ratingValue = modal.find("#rating").val();
      var commentValue = modal.find("#comment").val();

      var formData = {
        action: "wp_bmc_save_section_rating",
        project_id: projectId,
        section: section,
        rating: ratingValue,
        comment: commentValue,
        nonce: wp_bmc_admin_ajax.nonce,
      };

      $.post(wp_bmc_admin_ajax.ajax_url, formData, function (response) {
        if (response.success) {
          console.log(response);
          WP_BMC_Toast.success("Note sauvegardée avec succès !");
          modal.fadeOut(300, function () {
            modal.remove();
          });

          // Supprimer les demandes de notation correspondantes du dashboard
          removeGradingRequestsFromDashboard(projectId, section);

          // Mettre à jour le compteur de demandes
          updateGradingRequestsCount();

          let params = new URLSearchParams(window.location.search);
          let view = params.get("view");
          // Recharger la section rating en AJAX
          loadSectionRating(projectId, section);
          
          if (view === "synthetic") {
            window.WP_BMC_Dashboard.loadCanvasView("synthetic");
          } else {
            window.WP_BMC_Dashboard.loadCanvasView("global");
          }
          
        } else {
          console.log(response);
          WP_BMC_Toast.error("Erreur lors de la sauvegarde : " + response.data);
        }
      }).always(function () {
        $submitBtn.prop("disabled", false).text(originalText);
      });
    });

    // Gérer la fermeture
    modal.find(".popup-close, .popup-overlay").on("click", function () {
      modal.fadeOut(300, function () {
        modal.remove();
      });
    });
  }

  // ========================================
  // CHARGEMENT DES NOTES DE SECTION
  // ========================================

  // Fonction pour charger la note d'une section
  function loadSectionRating(projectId, section) {
    $.post(
      wp_bmc_ajax.ajax_url,
      {
        action: "wp_bmc_get_section_rating",
        project_id: projectId,
        section: section,
        nonce: wp_bmc_ajax.nonce,
      },
      function (response) {
        if (response.success && response.data.rating) {
          displaySectionRating(response.data.rating);
        } else {
          displayNoRating();
        }
      }
    ).fail(function () {
      displayNoRating();
    });
  }

  // Afficher la note d'une section
  function displaySectionRating(rating) {
    console.log("rating:", rating);
    $("#rating-score-number").text(rating.rating);

    if (rating.comment) {
      console.log("rating.comment:", rating.comment);
      $("#rating-comment").html("<p>" + rating.comment + "</p>");
    } else {
      $("#rating-comment").html('<p class="no-comment">Aucun commentaire</p>');
    }

    // Utiliser la date formatée selon les paramètres WordPress
    $("#rating-meta .rating-date").text(
      "Noté le " + (rating.formatted_date || rating.created_at)
    );
    $("#rating-meta .rating-admin").text(
      "Par " + (rating.admin_name || "Admin")
    );

    $("#rating-section").removeClass("no-rating").addClass("has-rating");
  }

  // Afficher l'absence de note
  function displayNoRating() {
    $("#rating-score-number").text("-");
    $("#rating-comment").html('<p class="no-rating">Aucune note attribuée</p>');
    $("#rating-meta .rating-date, #rating-meta .rating-admin").text("");
    $("#rating-section").removeClass("has-rating").addClass("no-rating");
  }

  // ========================================
  // AUTO-OUVERTURE DES SECTIONS
  // ========================================
  
  // Fonction pour ouvrir automatiquement une section
  function autoOpenSection() {
    var urlParams = new URLSearchParams(window.location.search);
    var openSection = urlParams.get("open_section");
    
    if (openSection) {
      console.log('Auto-ouverture de la section:', openSection);
      
      // Attendre que le DOM soit prêt
      setTimeout(function() {
        // Chercher le bouton d'édition de la section
        var $editBtn = $('.edit-brick-btn[data-section="' + openSection + '"]');
        
        if ($editBtn.length > 0) {
          console.log('Bouton d\'édition trouvé pour la section:', openSection);
          // Simuler un clic sur le bouton d'édition
          $editBtn.trigger('click');
          
          // Nettoyer l'URL en supprimant le paramètre open_section
          var newUrl = new URL(window.location);
          newUrl.searchParams.delete('open_section');
          window.history.replaceState({}, document.title, newUrl.toString());
        } else {
          console.log('Bouton d\'édition non trouvé pour la section:', openSection);
        }
      }, 1000); // Attendre 1 seconde pour que le DOM soit chargé
    }
  }
  
  // Exécuter l'auto-ouverture au chargement
  autoOpenSection();

  // ========================================
  // RACCOURCIS CLAVIER
  // ========================================
  $(document).on("keydown", function (e) {
    // Ctrl+F pour focuser sur la recherche
    if (e.ctrlKey && e.key === "f") {
      e.preventDefault();
      $("#users-search").focus();
    }

    // Échap pour fermer les popups
    if (e.key === "Escape") {
      $(".wp-bmc-popup").fadeOut(300, function () {
        $(this).remove();
      });
    }
  });
});
