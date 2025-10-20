/**
 * JavaScript pour l'authentification WP Business Model Canvas
 * Gère les formulaires de connexion, inscription et déconnexion
 */

// Système de logs conditionnels pour production
var WP_BMC_DEBUG = false; // Mettre à true pour activer les logs
function log() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && log) {
        log.apply(console, arguments);
    }
}
function logWarn() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && logWarn) {
        logWarn.apply(console, arguments);
    }
}
function logError() {
    if (WP_BMC_DEBUG && typeof console !== 'undefined' && logError) {
        logError.apply(console, arguments);
    }
}

jQuery(document).ready(function($) {
    
    // ========================================
    // FORMULAIRE DE CONNEXION
    // ========================================
    $('#wp-bmc-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Afficher le loader et désactiver le bouton
        $submitBtn.prop('disabled', true).html('<div class="btn-loader"><div class="loader-spinner"></div></div>Connexion en cours...');
        
        var formData = {
            action: 'wp_bmc_login',
            nonce: wp_bmc_ajax.nonce,
            login: $('#login').val(),
            password: $('#password').val()
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de connexion. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton et supprimer le loader
            $submitBtn.prop('disabled', false).text('Se connecter');
        });
    });
    
    // ========================================
    // FORMULAIRE D'INSCRIPTION
    // ========================================
    $('#wp-bmc-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Validation côté client
        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            WP_BMC_Toast.error('Les mots de passe ne correspondent pas.');
            return;
        }
        
        if (password.length < 6) {
            WP_BMC_Toast.error('Le mot de passe doit contenir au moins 6 caractères.');
            return;
        }
        
        // Afficher le loader et désactiver le bouton
        $submitBtn.prop('disabled', true).html('<div class="btn-loader"><div class="loader-spinner"></div></div>Inscription en cours...');
        
        var formData = {
            action: 'wp_bmc_register',
            nonce: wp_bmc_ajax.nonce,
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            password: password
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            } else {
                WP_BMC_Toast.error(response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur d\'inscription. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton et supprimer le loader
            $submitBtn.prop('disabled', false).text('S\'inscrire');
        });
    });
    
    // ========================================
    // DÉCONNEXION
    // ========================================
    $('#wp-bmc-logout').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        // Afficher le loader et désactiver le bouton
        $btn.prop('disabled', true).html('<div class="btn-loader"><div class="loader-spinner"></div></div>Déconnexion...');
        
        var formData = {
            action: 'wp_bmc_logout',
            nonce: wp_bmc_ajax.nonce
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                WP_BMC_Toast.success(response.data.message);
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            } else {
                WP_BMC_Toast.error('Erreur lors de la déconnexion : ' + response.data);
            }
        }).fail(function() {
            WP_BMC_Toast.error('Erreur de déconnexion. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton et supprimer le loader
            $btn.prop('disabled', false).text(originalText);
        });
    });
    
    // ========================================
    // VALIDATION EN TEMPS RÉEL
    // ========================================
    
    // Validation du mot de passe en temps réel
    $('#confirm_password').on('input', function() {
        var password = $('#password').val();
        var confirmPassword = $(this).val();
        
        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                $(this).removeClass('error').addClass('valid');
                $('.password-match-indicator').remove();
                $(this).after('<span class="password-match-indicator" style="color: green; font-size: 12px;">✓ Les mots de passe correspondent</span>');
            } else {
                $(this).removeClass('valid').addClass('error');
                $('.password-match-indicator').remove();
                $(this).after('<span class="password-match-indicator" style="color: red; font-size: 12px;">✗ Les mots de passe ne correspondent pas</span>');
            }
        } else {
            $(this).removeClass('error valid');
            $('.password-match-indicator').remove();
        }
    });
    
    // Validation de la force du mot de passe
    $('#password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        $('.password-strength-indicator').remove();
        
        var strengthText = '';
        var strengthColor = '';
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Très faible';
                strengthColor = '#dc3545';
                break;
            case 2:
                strengthText = 'Faible';
                strengthColor = '#fd7e14';
                break;
            case 3:
                strengthText = 'Moyen';
                strengthColor = '#ffc107';
                break;
            case 4:
                strengthText = 'Fort';
                strengthColor = '#28a745';
                break;
            case 5:
                strengthText = 'Très fort';
                strengthColor = '#20c997';
                break;
        }
        
        if (password.length > 0) {
            $(this).after('<span class="password-strength-indicator" style="font-size: 12px; color: ' + strengthColor + ';">Force : ' + strengthText + '</span>');
        }
    });

    // ========================================
    // PASSWORD TOGGLE  
    // ========================================

    // Gestion souris
    $('.show-password').on('mousedown', function() {
        log('show-password');
        $(this).parent().find('#password').attr('type', 'text');
    });
    $('.show-password').on('mouseup', function() {
        $(this).parent().find('#password').attr('type', 'password');
    });

    // Gestion clavier (Entrée et Espace)
    $('.show-password').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            log('show-password keyboard');
            $(this).parent().find('#password').attr('type', 'text');
        }
    });
    $('.show-password').on('keyup', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).parent().find('#password').attr('type', 'password');
        }
    });
    
    // ========================================
    // ANIMATIONS ET UX
    // ========================================
    
    // Focus sur le premier champ vide
    $('form').on('submit', function() {
        var $firstEmpty = $(this).find('input[required]:invalid').first();
        if ($firstEmpty.length) {
            $firstEmpty.focus();
        }
    });
    
    // ========================================
    // UTILITAIRES
    // ========================================
    
    // Fonction pour nettoyer les formulaires
    function clearForm(formSelector) {
        $(formSelector)[0].reset();
        $(formSelector + ' input').removeClass('error valid');
        $('.password-match-indicator, .password-strength-indicator').remove();
    }
    // Exposer les fonctions globalement si nécessaire
    window.WP_BMC_Auth = {
        clearForm: clearForm
    };
    
});
