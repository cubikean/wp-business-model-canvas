<?php
/**
 * Template pour le popup de changement de mot de passe après première connexion
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Popup de changement de mot de passe -->
<div id="wp-bmc-change-password-popup" class="wp-bmc-popup" style="display: none;">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <div class="popup-header">
            <h3>Changement de mot de passe requis</h3>
            <button class="popup-close" id="change-password-popup-close">&times;</button>
        </div>
        
        <div class="popup-body">
            <div class="change-password-info">
                <div class="info-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <p>Pour des raisons de sécurité, vous devez changer votre mot de passe lors de votre première connexion.</p>
            </div>
            
            <form id="wp-bmc-change-password-form" class="change-password-form">
                <div class="form-group">
                    <label for="current-password">Mot de passe actuel</label>
                    <input type="password" id="current-password" name="current_password" required>
                    <button type="button" class="show-password" data-target="current-password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/>
                        </svg>
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="new-password">Nouveau mot de passe</label>
                    <input type="password" id="new-password" name="new_password" required minlength="6">
                    <button type="button" class="show-password" data-target="new-password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/>
                        </svg>
                    </button>
                    <small class="password-help">Le mot de passe doit contenir au moins 6 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm-password" name="confirm_password" required minlength="6">
                    <button type="button" class="show-password" data-target="confirm-password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/>
                        </svg>
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="wp-bmc-btn wp-bmc-btn-primary" id="change-password-submit">
                        <span class="btn-text">Changer le mot de passe</span>
                        <span class="btn-loader" style="display: none;">
                            <div class="loader-spinner"></div>
                        </span>
                    </button>
                </div>
            </form>
            
            <div id="change-password-message" class="wp-bmc-message" style="display: none;"></div>
        </div>
    </div>
</div>
