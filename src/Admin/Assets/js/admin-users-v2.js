jQuery(document).ready(function ($) {
  // Création d'un utilisateur
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

  // Recherche d'utilisateurs
  $("#users-search").on("input", function () {
    var searchTerm = $(this).val().toLowerCase();
    filterUsers(searchTerm);
  });

  // Filtrage par statut
  $("#users-filter-status").on("change", function () {
    var status = $(this).val();
    filterUsersByStatus(status);
  });

  // Tri des colonnes
  $(".sortable").on("click", function () {
    var column = $(this).data("sort");
    var currentOrder = $(this).hasClass("asc") ? "desc" : "asc";

    $(".sortable").removeClass("asc desc");
    $(this).addClass(currentOrder);

    sortUsersTable(column, currentOrder);
  });

  // Actions sur les utilisateurs
  $(".edit-user-btn").on("click", function () {
    var userId = $(this).data("user-id");
    editUser(userId);
  });

  $(".view-projects-btn").on("click", function () {
    var userId = $(this).data("user-id");
    viewUserProjects(userId);
  });

  $(".reset-password-btn").on("click", function () {
    var userId = $(this).data("user-id");
    resetUserPassword(userId);
  });

  $(".deactivate-user-btn").on("click", function () {
    var userId = $(this).data("user-id");
    deactivateUser(userId);
  });

  function filterUsers(searchTerm) {
    $(".user-row").each(function () {
      var $row = $(this);
      var customId = $row.find(".user-custom-id").text().toLowerCase();
      var name = $row.find(".user-name").text().toLowerCase();
      var email = $row.find(".user-email").text().toLowerCase();

      if (
        customId.includes(searchTerm) ||
        name.includes(searchTerm) ||
        email.includes(searchTerm)
      ) {
        $row.show();
      } else {
        $row.hide();
      }
    });

    updateUsersCount();
  }

  function filterUsersByStatus(status) {
    // Implémentation du filtrage par statut
    updateUsersCount();
  }

  function sortUsersTable(column, order) {
    var $tbody = $("#users-table tbody");
    var $rows = $tbody.find(".user-row").toArray();

    $rows.sort(function (a, b) {
      var aVal, bVal;

      switch (column) {
        case "custom_id":
          aVal = $(a).find(".user-custom-id").text().trim();
          bVal = $(b).find(".user-custom-id").text().trim();
          break;
        case "name":
          aVal = $(a).find(".user-name").text().trim();
          bVal = $(b).find(".user-name").text().trim();
          break;
        case "email":
          aVal = $(a).find(".user-email").text().trim();
          bVal = $(b).find(".user-email").text().trim();
          break;  
        case "created_at":
          aVal = new Date($(a).find(".user-created").text());
          bVal = new Date($(b).find(".user-created").text());
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

    $.each($rows, function (index, row) {
      $tbody.append(row);
    });
  }

  function updateUsersCount() {
    var visibleCount = $(".user-row:visible").length;
    var totalCount = $(".user-row").length;
    $("#users-count").text(visibleCount + " utilisateur(s) sur " + totalCount);
  }

  function editUser(userId) {
    // Implémentation de l'édition d'utilisateur
    WP_BMC_Toast.info("Fonctionnalité d'édition à implémenter");
  }

  function viewUserProjects(userId) {
    // Implémentation de la vue des projets de l'utilisateur
    WP_BMC_Toast.info("Fonctionnalité de vue des projets à implémenter");
  }

  function resetUserPassword(userId) {
    if (
      confirm(
        "Êtes-vous sûr de vouloir réinitialiser le mot de passe de cet utilisateur ?"
      )
    ) {
      // Implémentation de la réinitialisation du mot de passe
      WP_BMC_Toast.info("Fonctionnalité de réinitialisation à implémenter");
    }
  }

  function deactivateUser(userId) {
    if (confirm("Êtes-vous sûr de vouloir désactiver cet utilisateur ?")) {
      // Implémentation de la désactivation
      WP_BMC_Toast.info("Fonctionnalité de désactivation à implémenter");
    }
  }

  // Gestion des statuts utilisateur
  $('.disable-user-btn').on('click', function() {
    var userId = $(this).data('user-id');
    updateUserStatus(userId, 'disabled');
  });

  $('.enable-user-btn').on('click', function() {
    var userId = $(this).data('user-id');
    updateUserStatus(userId, 'active');
  });

  $('.activate-user-btn').on('click', function() {
    var userId = $(this).data('user-id');
    updateUserStatus(userId, 'active');
  });

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
});
