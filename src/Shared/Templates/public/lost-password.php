<?php
/**
 * Template pour la réinitialisation de mot de passe (mot de passe oublié)
 * Utilise les fonctions WordPress natives
 */

if (!defined('ABSPATH')) {
    exit;
}

// Si l'utilisateur est déjà connecté, rediriger vers le dashboard
if (WP_BMC_Auth::is_logged_in()) {
    wp_redirect(home_url('/dashboard/'));
    exit;
}
?>

<div class="wp-bmc-lost-password">
    <div class="lost-password-container">
        <div class="lost-password-header">
            <h2>Mot de passe oublié</h2>
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>
        </div>
        
        <!-- Utiliser le formulaire WordPress natif -->
        <form name="lostpasswordform" id="lostpasswordform" action="<?php echo esc_url(network_site_url('wp-login.php?action=lostpassword', 'login_post')); ?>" method="post">
            <div class="form-group">
                <label for="user_login">Adresse email *</label>
                <input type="text" name="user_login" id="user_login" class="input" value="" size="20" autocapitalize="off" required>
            </div>
            
            <div class="form-group">
                <button type="submit" name="wp-submit" id="wp-submit" class="wp-bmc-btn wp-bmc-btn-primary">
                    Envoyer le lien de réinitialisation
                </button>
            </div>
        </form>
        
        <div class="form-links">
            <p><a href="<?php echo home_url('/login/'); ?>" class="wp-bmc-link">Retour à la connexion</a></p>
        </div>
        
        <div class="lost-password-info">
            <div class="info-card">
                <i class="fas fa-info-circle"></i>
                <h3>Comment ça fonctionne ?</h3>
                <p>Un email contenant un lien sécurisé vous sera envoyé. Cliquez sur ce lien pour définir un nouveau mot de passe.</p>
            </div>
        </div>
    </div>
</div>
