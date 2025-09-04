/**
 * JavaScript d'administration pour WP Business Model Canvas
 */

jQuery(document).ready(function($) {
    
    // Initialisation de l'interface d'administration
    initAdminInterface();
    
    function initAdminInterface() {
        // Ajouter des animations aux cartes de statistiques
        animateStatCards();
        
        // Initialiser les tooltips
        initTooltips();
        
        // Gérer les confirmations de suppression
        initDeleteConfirmations();
        
        // Ajouter des fonctionnalités de tri aux tableaux
        initTableSorting();
        
        // Gérer les exports
        initExportFunctionality();
    }
    
    // Animation des cartes de statistiques
    function animateStatCards() {
        $('.wp-bmc-stat-card').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's'
            });
    });
    }
    
    // Initialiser les tooltips
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });
    }
    
    // Gérer les confirmations de suppression
    function initDeleteConfirmations() {
        $('.button-link-delete').on('click', function(e) {
            const action = $(this).data('action');
            const itemName = $(this).data('item-name');
            
            if (!confirm(`Êtes-vous sûr de vouloir supprimer ${action} "${itemName}" ? Cette action est irréversible.`)) {
                e.preventDefault();
                return false;
            }
            
            // Afficher un indicateur de chargement
            $(this).prop('disabled', true).text('Suppression...');
        });
    }
    
    // Tri des tableaux
    function initTableSorting() {
        $('.wp-list-table th[data-sortable]').on('click', function() {
            const table = $(this).closest('table');
            const column = $(this).index();
            const rows = table.find('tbody tr').toArray();
            const isAscending = $(this).hasClass('sort-asc');
            
            // Trier les lignes
            rows.sort(function(a, b) {
                const aValue = $(a).find('td').eq(column).text().trim();
                const bValue = $(b).find('td').eq(column).text().trim();
                
                // Gérer les dates
                if (aValue.match(/^\d{2}\/\d{2}\/\d{4}/)) {
                    return isAscending ? 
                        new Date(aValue.split(' ')[0].split('/').reverse().join('-')) - new Date(bValue.split(' ')[0].split('/').reverse().join('-')) :
                        new Date(bValue.split(' ')[0].split('/').reverse().join('-')) - new Date(aValue.split(' ')[0].split('/').reverse().join('-'));
                }
                
                // Tri alphabétique
                return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            });
            
            // Réorganiser le tableau
            table.find('tbody').empty().append(rows);
            
            // Mettre à jour les indicateurs de tri
            table.find('th').removeClass('sort-asc sort-desc');
            $(this).addClass(isAscending ? 'sort-desc' : 'sort-asc');
        });
    }
    
    // Fonctionnalités d'export
    function initExportFunctionality() {
        $('#wp-bmc-export-data').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const originalText = button.text();
            
            button.prop('disabled', true).text('Export en cours...');
            
            // Simuler l'export (dans un vrai cas, ce serait une requête AJAX)
            setTimeout(function() {
                button.prop('disabled', false).text(originalText);
                showAdminNotification('Export terminé avec succès !', 'success');
            }, 2000);
        });
    }
    
    // Notifications d'administration
    function showAdminNotification(message, type = 'info') {
        const notification = $('<div>')
            .addClass('wp-bmc-admin-notification')
            .addClass(type)
            .text(message)
            .appendTo('.wrap');
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Recherche dans les tableaux
    function initTableSearch() {
        $('.wp-bmc-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const table = $(this).closest('.wp-bmc-section').find('.wp-list-table');
            
            table.find('tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchTerm));
            });
        });
    }
    
    // Filtres de statut
    function initStatusFilters() {
        $('.wp-bmc-status-filter').on('change', function() {
            const status = $(this).val();
            const table = $(this).closest('.wp-bmc-section').find('.wp-list-table');
            
            if (status === 'all') {
                table.find('tbody tr').show();
            } else {
                table.find('tbody tr').each(function() {
                    const rowStatus = $(this).find('.status-badge').text().toLowerCase();
                    $(this).toggle(rowStatus.includes(status));
                });
            }
        });
    }
    
    // Actualisation automatique des statistiques
    function initAutoRefresh() {
        if ($('.wp-bmc-stats-grid').length) {
            setInterval(function() {
                refreshStats();
            }, 300000); // Actualiser toutes les 5 minutes
        }
    }
    
    function refreshStats() {
        // Requête AJAX pour actualiser les statistiques
        $.post(ajaxurl, {
            action: 'wp_bmc_refresh_stats',
            nonce: wp_bmc_admin.nonce
        }, function(response) {
            if (response.success) {
                updateStatCards(response.data);
            }
        });
    }
    
    function updateStatCards(data) {
        $('.stat-number').each(function() {
            const statType = $(this).data('stat');
            if (data[statType]) {
                $(this).text(data[statType]);
            }
        });
    }
    
    // Gestion des onglets (si implémentés)
    function initTabs() {
        $('.wp-bmc-tab-nav a').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href');
            
            // Masquer tous les contenus
            $('.wp-bmc-tab-content').hide();
            
            // Afficher le contenu ciblé
            $(target).show();
            
            // Mettre à jour la navigation
            $('.wp-bmc-tab-nav a').removeClass('active');
            $(this).addClass('active');
        });
    }
    
    // Validation des formulaires d'administration
    function initFormValidation() {
        $('.wp-bmc-admin-form').on('submit', function(e) {
            const form = $(this);
            const requiredFields = form.find('[required]');
            let isValid = true;
            
            requiredFields.each(function() {
                const field = $(this);
                if (!field.val().trim()) {
                    field.addClass('error');
                    isValid = false;
                } else {
                    field.removeClass('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAdminNotification('Veuillez remplir tous les champs obligatoires.', 'error');
            }
        });
    }
    
    // Gestion des modales d'administration
    function initAdminModals() {
        $('.wp-bmc-modal-trigger').on('click', function(e) {
            e.preventDefault();
            
            const modalId = $(this).data('modal');
            $(modalId).show();
        });
        
        $('.wp-bmc-modal-close').on('click', function() {
            $(this).closest('.wp-bmc-modal').hide();
        });
        
        // Fermer la modale en cliquant à l'extérieur
        $('.wp-bmc-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
    }
    
    // Raccourcis clavier
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + E pour exporter
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                $('#wp-bmc-export-data').click();
            }
            
            // Ctrl/Cmd + R pour actualiser
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshStats();
            }
            
            // Échap pour fermer les modales
            if (e.key === 'Escape') {
                $('.wp-bmc-modal').hide();
            }
        });
    }
    
    // Styles CSS supplémentaires pour les fonctionnalités JavaScript
    const additionalStyles = `
        <style>
            .wp-bmc-admin-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 100000;
                animation: slideInRight 0.3s ease;
                max-width: 300px;
            }
            
            .wp-bmc-admin-notification.success {
                background: #28a745;
            }
            
            .wp-bmc-admin-notification.error {
                background: #dc3545;
            }
            
            .wp-bmc-admin-notification.info {
                background: #17a2b8;
            }
            
            .wp-bmc-search {
                width: 100%;
                max-width: 300px;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            
            .wp-bmc-status-filter {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            
            .wp-list-table th[data-sortable] {
                cursor: pointer;
                position: relative;
            }
            
            .wp-list-table th[data-sortable]:hover {
                background: #e9ecef;
            }
            
            .wp-list-table th.sort-asc::after {
                content: " ▲";
                color: #0073aa;
            }
            
            .wp-list-table th.sort-desc::after {
                content: " ▼";
                color: #0073aa;
            }
            
            .wp-bmc-tab-nav {
                border-bottom: 2px solid #e9ecef;
                margin-bottom: 20px;
            }
            
            .wp-bmc-tab-nav a {
                display: inline-block;
                padding: 10px 20px;
                text-decoration: none;
                color: #6c757d;
                border-bottom: 2px solid transparent;
                margin-bottom: -2px;
            }
            
            .wp-bmc-tab-nav a.active {
                color: #0073aa;
                border-bottom-color: #0073aa;
            }
            
            .wp-bmc-tab-content {
                display: none;
            }
            
            .wp-bmc-tab-content:first-child {
                display: block;
            }
            
            .wp-bmc-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 100000;
                display: none;
            }
            
            .wp-bmc-modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 30px;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            
            .wp-bmc-modal-close {
                position: absolute;
                top: 10px;
                right: 15px;
                font-size: 24px;
                cursor: pointer;
                color: #6c757d;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            .wp-bmc-admin-form input.error,
            .wp-bmc-admin-form textarea.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            }
        </style>
    `;
    
    $('head').append(additionalStyles);
    
    // Initialiser toutes les fonctionnalités
    initTableSearch();
    initStatusFilters();
    initAutoRefresh();
    initTabs();
    initFormValidation();
    initAdminModals();
    initKeyboardShortcuts();
});
