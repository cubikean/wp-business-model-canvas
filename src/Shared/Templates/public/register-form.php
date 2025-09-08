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
