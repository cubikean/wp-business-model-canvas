<?php
/**
 * Template pour le formulaire d'inscription
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-bmc-register-form">
    <h2>Inscription</h2>
    
    <form id="wp-bmc-register-form" method="post">
        <?php wp_nonce_field('wp_bmc_register_nonce', 'wp_bmc_register_nonce'); ?>
        
        <div class="form-group">
            <label for="first_name">Prénom</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Nom</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="company">Entreprise</label>
            <input type="text" id="company" name="company" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required minlength="6">
            <button type="button" class="show-password">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5M12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5s5 2.24 5 5s-2.24 5-5 5m0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3s3-1.34 3-3s-1.34-3-3-3"/></svg>
            </button>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmer le mot de passe</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
        </div>
        
        <div class="form-group">
            <button type="submit" class="wp-bmc-btn wp-bmc-btn-primary">
                S'inscrire
            </button>
        </div>
        
        <div class="form-links">
            <p>Déjà un compte ? <a href="<?php echo home_url('/login/'); ?>" class="wp-bmc-switch-form">Se connecter</a></p>
        </div>
    </form>
    
    <div id="wp-bmc-register-message" class="wp-bmc-message" style="display: none;"></div>
</div>
