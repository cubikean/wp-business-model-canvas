/**
 * JavaScript pour le shortcode menu utilisateur WP Business Model Canvas
 */

jQuery(document).ready(function($) {
    
    // Gestion du menu utilisateur shortcode
    function initShortcodeUserMenu() {
        // Clic sur l'avatar pour ouvrir/fermer le menu
        $(document).on('click', '.wp-bmc-user-menu-shortcode .wp-bmc-user-avatar', function(e) {
            e.stopPropagation();
            const menuContainer = $(this).closest('.wp-bmc-user-menu-shortcode');
            const menuId = menuContainer.attr('id');
            toggleShortcodeUserDropdown(menuId);
        });
        
        // Clic en dehors pour fermer le menu
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.wp-bmc-user-menu-shortcode').length) {
                closeAllShortcodeUserDropdowns();
            }
        });
        
        // Actions du menu
        $(document).on('click', '.wp-bmc-user-menu-shortcode .wp-bmc-user-dropdown-action', function(e) {
            e.preventDefault();
            const action = $(this);
            
            if (action.attr('id').includes('logout-btn')) {
                handleShortcodeLogout();
            } else if (action.attr('id').includes('login-btn')) {
                window.location.href = '/login/';
            } else if (action.attr('id').includes('add-account-btn')) {
                handleShortcodeAddAccount();
            } else if (action.attr('id').includes('change-password')) {
                handleShortcodeChangePassword();
            }
            else if (action.attr('id').includes('emergence-btn')) {
                window.location.href = '/sprints-emergence/';
            }
            else if (action.attr('id').includes('ressources-btn')) {
                window.location.href = '/ressources-pedagogiques/';
            }
            else if (action.attr('id').includes('pepitizy-dashboard-btn')) {
                window.open('https://pepite-eca.pepitizy.fr/fr/account', '_blank');
            }
            else if (action.attr('id').includes('pepitizy-rapports-btn')) {
                window.open('https://pepite-eca.pepitizy.fr/fr/account/reports', '_blank');
            }
            else if (action.attr('id').includes('pepitizy-competences-btn')) {
                window.open('https://pepite-eca.pepitizy.fr/fr/positionnement', '_blank');
            }
            else if (action.attr('id').includes('dashboard-btn')) {
                window.location.href = '/dashboard/';
            }
        });
    }
    
    function toggleShortcodeUserDropdown(menuId) {
        const dropdown = $('#' + menuId + ' .wp-bmc-user-dropdown');
        
        if (dropdown.hasClass('show')) {
            closeShortcodeUserDropdown(menuId);
        } else {
            // Fermer tous les autres menus d'abord
            closeAllShortcodeUserDropdowns();
            openShortcodeUserDropdown(menuId);
        }
    }
    
    function openShortcodeUserDropdown(menuId) {
        const dropdown = $('#' + menuId + ' .wp-bmc-user-dropdown');
        const container = $('#' + menuId);
        
        dropdown.addClass('show');
        
        // Animation d'ouverture selon la position
        if (container.hasClass('wp-bmc-position-center')) {
            dropdown.css({
                'transform': 'translateX(-50%) translateY(0)',
                'opacity': '1',
                'visibility': 'visible'
            });
        } else {
            dropdown.css({
                'transform': 'translateY(0)',
                'opacity': '1',
                'visibility': 'visible'
            });
        }
    }
    
    function closeShortcodeUserDropdown(menuId) {
        const dropdown = $('#' + menuId + ' .wp-bmc-user-dropdown');
        const container = $('#' + menuId);
        
        dropdown.removeClass('show');
        
        // Animation de fermeture selon la position
        if (container.hasClass('wp-bmc-position-center')) {
            dropdown.css({
                'transform': 'translateX(-50%) translateY(-10px)',
                'opacity': '0',
                'visibility': 'hidden'
            });
        } else {
            dropdown.css({
                'transform': 'translateY(-10px)',
                'opacity': '0',
                'visibility': 'hidden'
            });
        }
    }
    
    function closeAllShortcodeUserDropdowns() {
        $('.wp-bmc-user-menu-shortcode .wp-bmc-user-dropdown').each(function() {
            const dropdown = $(this);
            const container = dropdown.closest('.wp-bmc-user-menu-shortcode');
            
            dropdown.removeClass('show');
            
            // Animation de fermeture selon la position
            if (container.hasClass('wp-bmc-position-center')) {
                dropdown.css({
                    'transform': 'translateX(-50%) translateY(-10px)',
                    'opacity': '0',
                    'visibility': 'hidden'
                });
            } else {
                dropdown.css({
                    'transform': 'translateY(-10px)',
                    'opacity': '0',
                    'visibility': 'hidden'
                });
            }
        });
    }
    
    function handleShortcodeLogout() {
        if (typeof WP_BMC_Toast !== 'undefined') {
            WP_BMC_Toast.info('Déconnexion en cours...');
        }
        
        // Vérifier si wp_bmc_ajax est disponible
        if (typeof wp_bmc_ajax === 'undefined') {
            console.error('wp_bmc_ajax non disponible');
            if (typeof WP_BMC_Toast !== 'undefined') {
                WP_BMC_Toast.error('Erreur: variables AJAX non disponibles');
            }
            return;
        }
        
        $.post(wp_bmc_ajax.ajax_url, {
            action: 'wp_bmc_logout',
            nonce: wp_bmc_ajax.nonce
        }, function(response) {
            if (response.success) {
                if (typeof WP_BMC_Toast !== 'undefined') {
                    WP_BMC_Toast.success(response.data.message);
                }
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1000);
            } else {
                if (typeof WP_BMC_Toast !== 'undefined') {
                    WP_BMC_Toast.error(response.data);
                }
            }
        }).fail(function() {
            if (typeof WP_BMC_Toast !== 'undefined') {
                WP_BMC_Toast.error('Erreur de connexion lors de la déconnexion');
            }
        });
    }
    
    function handleShortcodeAddAccount() {
        if (typeof WP_BMC_Toast !== 'undefined') {
            WP_BMC_Toast.info('Redirection vers la page de connexion...');
        }
        setTimeout(function() {
            window.location.href = '/login/';
        }, 500);
    }
    
    function handleShortcodeChangePassword() {
        // Fermer le menu déroulant
        closeAllShortcodeUserDropdowns();
        
        // Appeler la fonction existante showChangePasswordPopup si disponible
        if (typeof window.showChangePasswordPopup === 'function') {
            window.showChangePasswordPopup();
        } else if (typeof showChangePasswordPopup === 'function') {
            showChangePasswordPopup();
        } else {
            // Fallback : rediriger vers le dashboard si la fonction n'existe pas
            if (typeof WP_BMC_Toast !== 'undefined') {
                WP_BMC_Toast.info('Redirection vers le dashboard...');
            }
            setTimeout(function() {
                window.location.href = '/dashboard/';
            }, 500);
        }
    }
    
    // Initialiser le menu
    initShortcodeUserMenu();
    
    // Exposer les fonctions globalement pour debug si nécessaire
    window.WP_BMC_UserMenuShortcode = {
        toggleShortcodeUserDropdown: toggleShortcodeUserDropdown,
        openShortcodeUserDropdown: openShortcodeUserDropdown,
        closeShortcodeUserDropdown: closeShortcodeUserDropdown,
        closeAllShortcodeUserDropdowns: closeAllShortcodeUserDropdowns
    };
});
