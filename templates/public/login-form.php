<?php
/**
 * Template pour le formulaire de connexion
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-bmc-login-form">
    <h2>Connexion</h2>
    
    <form id="wp-bmc-login-form" method="post">
        <?php wp_nonce_field('wp_bmc_login_nonce', 'wp_bmc_login_nonce'); ?>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="wp-bmc-btn wp-bmc-btn-primary">
                Se connecter
            </button>
        </div>
        
        <div class="form-links">
            <p>Pas encore de compte ? <a href="<?php echo home_url('/register/'); ?>" class="wp-bmc-switch-form">S'inscrire</a></p>
        </div>
    </form>
    
    <div id="wp-bmc-login-message" class="wp-bmc-message" style="display: none;"></div>
</div>
