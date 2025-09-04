/**
 * JavaScript pour l'authentification WP Business Model Canvas
 * Gère les formulaires de connexion, inscription et déconnexion
 */

jQuery(document).ready(function($) {
    
    // ========================================
    // FORMULAIRE DE CONNEXION
    // ========================================
    $('#wp-bmc-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#wp-bmc-login-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Désactiver le bouton pendant la soumission
        $submitBtn.prop('disabled', true).text('Connexion en cours...');
        
        var formData = {
            action: 'wp_bmc_login',
            nonce: wp_bmc_ajax.nonce,
            email: $('#email').val(),
            password: $('#password').val()
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $message.html('<div class="wp-bmc-message success">' + response.data.message + '</div>').show();
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            } else {
                $message.html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
            }
        }).fail(function() {
            $message.html('<div class="wp-bmc-message error">Erreur de connexion. Veuillez réessayer.</div>').show();
        }).always(function() {
            // Réactiver le bouton
            $submitBtn.prop('disabled', false).text('Se connecter');
        });
    });
    
    // ========================================
    // FORMULAIRE D'INSCRIPTION
    // ========================================
    $('#wp-bmc-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $message = $('#wp-bmc-register-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        // Validation côté client
        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            $message.html('<div class="wp-bmc-message error">Les mots de passe ne correspondent pas.</div>').show();
            return;
        }
        
        if (password.length < 6) {
            $message.html('<div class="wp-bmc-message error">Le mot de passe doit contenir au moins 6 caractères.</div>').show();
            return;
        }
        
        // Désactiver le bouton pendant la soumission
        $submitBtn.prop('disabled', true).text('Inscription en cours...');
        
        var formData = {
            action: 'wp_bmc_register',
            nonce: wp_bmc_ajax.nonce,
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            company: $('#company').val(),
            password: password
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                $message.html('<div class="wp-bmc-message success">' + response.data.message + '</div>').show();
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1500);
            } else {
                $message.html('<div class="wp-bmc-message error">' + response.data + '</div>').show();
            }
        }).fail(function() {
            $message.html('<div class="wp-bmc-message error">Erreur d\'inscription. Veuillez réessayer.</div>').show();
        }).always(function() {
            // Réactiver le bouton
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
        
        // Désactiver le bouton
        $btn.prop('disabled', true).text('Déconnexion...');
        
        var formData = {
            action: 'wp_bmc_logout',
            nonce: wp_bmc_ajax.nonce
        };
        
        $.post(wp_bmc_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                // Afficher un message de succès avant la redirection
                $('body').append('<div class="wp-bmc-logout-message" style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px; border-radius: 5px; z-index: 9999;">' + response.data.message + '</div>');
                
                setTimeout(function() {
                    window.location.href = response.data.redirect_url;
                }, 1000);
            } else {
                alert('Erreur lors de la déconnexion : ' + response.data);
            }
        }).fail(function() {
            alert('Erreur de déconnexion. Veuillez réessayer.');
        }).always(function() {
            // Réactiver le bouton
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
    // ANIMATIONS ET UX
    // ========================================
    
    // Animation des messages
    $('.wp-bmc-message').on('show', function() {
        $(this).hide().fadeIn(300);
    });
    
    // Auto-hide des messages d'erreur après 5 secondes
    setInterval(function() {
        $('.wp-bmc-message.error').fadeOut(500);
    }, 5000);
    
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
    
    // Fonction pour afficher un message
    function showMessage(selector, message, type) {
        var $message = $(selector);
        $message.html('<div class="wp-bmc-message ' + type + '">' + message + '</div>').show();
    }
    
    // Fonction pour nettoyer les formulaires
    function clearForm(formSelector) {
        $(formSelector)[0].reset();
        $(formSelector + ' .wp-bmc-message').hide();
        $(formSelector + ' input').removeClass('error valid');
        $('.password-match-indicator, .password-strength-indicator').remove();
    }
    
    // Exposer les fonctions globalement si nécessaire
    window.WP_BMC_Auth = {
        showMessage: showMessage,
        clearForm: clearForm
    };
    
});
