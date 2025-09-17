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

    // Rediriger vers le canvas de l'utilisateur avec vue admin
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&user_id=" +
      userId +
      "&project_id=" +
      projectId;
    window.open(url, "_blank");
  });

  // Noter une section depuis les demandes en attente
  $(document).on("click", ".grade-section-btn", function () {
    var projectId = $(this).data("project-id");
    var userId = $(this).data("user-id");

    // Rediriger vers le canvas de l'utilisateur avec vue admin
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&user_id=" +
      userId +
      "&project_id=" +
      projectId;
    window.open(url, "_blank");
  });

  $(document).on("click", ".rate-brick-btn", function () {
    openGradingModal(
      $(this).data("project-id"),
      $(this).data("section"),
      $(this).data("section-title")
    );
  });

  // ========================================
  // ACTIONS SUR LES UTILISATEURS
  // ========================================

  // Voir le canvas de l'utilisateur
  $(document).on("click", ".view-user-canvas-btn", function () {
    var userId = $(this).data("user-id");
    var url =
      window.location.origin +
      "/business-model-canvas/?admin_view=true&user_id=" +
      userId;
    window.open(url, "_blank");
  });

  // Éditer l'utilisateur
  $(document).on("click", ".edit-user-btn", function () {
    var userId = $(this).data("user-id");
    editUserInPopup(userId);
  });

  // ========================================
  // EXPORT DES DONNÉES
  // ========================================
  $("#export-users-btn").on("click", function () {
    var $btn = $(this);
    var originalText = $btn.text();

    $btn.prop("disabled", true).text("Export en cours...");

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_export_users",
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          // Télécharger le fichier
          var link = document.createElement("a");
          link.href = response.data.file_url;
          link.download = "utilisateurs-bmc.csv";
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } else {
          WP_BMC_Toast.error("Erreur lors de l'export : " + response.data);
        }
      }
    ).always(function () {
      $btn.prop("disabled", false).text(originalText);
    });
  });

  $("#export-data-btn").on("click", function () {
    var $btn = $(this);
    var originalText = $btn.text();

    $btn.prop("disabled", true).text("Export en cours...");

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_export_all_data",
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          var link = document.createElement("a");
          link.href = response.data.file_url;
          link.download = "bmc-data-export.json";
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } else {
          WP_BMC_Toast.error("Erreur lors de l'export : " + response.data);
        }
      }
    ).always(function () {
      $btn.prop("disabled", false).text(originalText);
    });
  });

  $("#clear-cache-btn").on("click", function () {
    var $btn = $(this);
    var originalText = $btn.text();

    $btn.prop("disabled", true).text("Nettoyage...");

    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_clear_cache",
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          WP_BMC_Toast.success("Cache vidé avec succès !");
        } else {
          WP_BMC_Toast.error("Erreur lors du nettoyage : " + response.data);
        }
      }
    ).always(function () {
      $btn.prop("disabled", false).text(originalText);
    });
  });

  // ========================================
  // FONCTIONS UTILITAIRES
  // ========================================

  // Filtrer les utilisateurs par terme de recherche
  function filterUsers(searchTerm) {
    $(".user-row").each(function () {
      var $row = $(this);
      var name = $row.find(".user-name").text().toLowerCase();
      var email = $row.find(".user-email").text().toLowerCase();
      var company = $row.find(".user-company").text().toLowerCase();

      if (
        name.includes(searchTerm) ||
        email.includes(searchTerm) ||
        company.includes(searchTerm)
      ) {
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
        case "company":
          aVal = $(a).find(".user-company").text().trim();
          bVal = $(b).find(".user-company").text().trim();
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

  // Fonction pour éditer un utilisateur dans une popup
  function editUserInPopup(userId) {
    var popup = $(
      '<div class="wp-bmc-popup user-edit-popup">' +
        '<div class="popup-overlay"></div>' +
        '<div class="popup-content">' +
        '<div class="popup-header">' +
        "<h3>Éditer l'utilisateur</h3>" +
        '<button class="popup-close">&times;</button>' +
        "</div>" +
        '<div class="popup-body">' +
        '<div class="user-edit-loading">Chargement...</div>' +
        "</div>" +
        "</div>" +
        "</div>"
    );

    $("body").append(popup);
    popup.fadeIn(300);

    // Charger le formulaire d'édition
    $.post(
      wp_bmc_admin_ajax.ajax_url,
      {
        action: "wp_bmc_get_user_edit_form",
        user_id: userId,
        nonce: wp_bmc_admin_ajax.nonce,
      },
      function (response) {
        if (response.success) {
          popup.find(".user-edit-loading").html(response.data.html);
        } else {
          popup
            .find(".user-edit-loading")
            .html(
              '<div class="error">Erreur lors du chargement du formulaire.</div>'
            );
        }
      }
    );

    // Gérer la fermeture
    popup.find(".popup-close, .popup-overlay").on("click", function () {
      popup.fadeOut(300, function () {
        popup.remove();
      });
    });
  }

  // Sauvegarder les modifications d'un utilisateur
  $(document).on("submit", "#user-edit-form", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var originalText = $submitBtn.text();

    $submitBtn.prop("disabled", true).text("Sauvegarde...");

    var formData = $form.serialize();
    formData += "&action=wp_bmc_update_user&nonce=" + wp_bmc_admin_ajax.nonce;

    $.post(wp_bmc_admin_ajax.ajax_url, formData, function (response) {
      if (response.success) {
        WP_BMC_Toast.success("Utilisateur mis à jour avec succès.");
        $(".user-edit-popup").fadeOut(300, function () {
          $(this).remove();
        });
        // Recharger la page pour voir les changements
        window.location.reload();
      } else {
        WP_BMC_Toast.error(
          "Erreur lors de la mise à jour : " + response.data
        );
      }
    }).always(function () {
      $submitBtn.prop("disabled", false).text(originalText);
    });
  });

  // Fonction pour afficher des messages (dépréciée - utiliser WP_BMC_Toast à la place)
  function showMessage(message, type) {
    // Rediriger vers le système de toast
    if (type === 'success') {
      WP_BMC_Toast.success(message);
    } else if (type === 'error') {
      WP_BMC_Toast.error(message);
    } else if (type === 'warning') {
      WP_BMC_Toast.warning(message);
    } else {
      WP_BMC_Toast.info(message);
    }
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
    var modal = $(
      '<div class="wp-bmc-popup grading-modal">' +
        '<div class="popup-overlay"></div>' +
        '<div class="popup-content">' +
        '<div class="popup-header">' +
        "<h3>Noter la section</h3>" +
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
        '<label for="rating">Note (0-10) :</label>' +
        '<div class="rating-slider-container">' +
        '<input type="range" id="rating" name="rating" min="0" max="10" value="5" step="1" required>' +
        '<div class="rating-display">' +
        '<span id="rating-value">5</span>/10' +
        "</div>" +
        "</div>" +
        "</div>" +
        '<div class="grading-field">' +
        '<label for="comment">Commentaire :</label>' +
        '<textarea id="comment" name="comment" rows="4" placeholder="Commentaires sur cette section..."></textarea>' +
        "</div>" +
        '<div class="grading-actions">' +
        '<button type="submit" class="button button-primary">Sauvegarder la note</button>' +
        '<button type="button" class="button popup-close button-secondary">Annuler</button>' +
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
    });

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

          window.WP_BMC_Dashboard.loadCanvasView('synthetic');

        //   // Mettre à jour dynamiquement l'affichage de la note sur la section du canvas sans recharger la page entière
        //   var $ratingDisplay = $("#rating-display-" + section);
        //   if ($ratingDisplay.length) {
        //     // Met à jour la note affichée
        //     $ratingDisplay.find(".rating-score-number").text(ratingValue);
        //   }
        } else {
          console.log(response);
          WP_BMC_Toast.error(
            "Erreur lors de la sauvegarde : " + response.data
          );
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
