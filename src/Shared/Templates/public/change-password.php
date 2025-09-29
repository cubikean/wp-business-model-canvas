<?php
/**
 * Template pour le changement de mot de passe
 * Utilise les fonctions WordPress natives
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = WP_BMC_Auth::get_current_user();

if (!$current_user) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Vérifier si c'est une réinitialisation via lien email
$action = isset($_GET['action']) ? $_GET['action'] : '';
$key = isset($_GET['key']) ? $_GET['key'] : '';
$login = isset($_GET['login']) ? $_GET['login'] : '';

if ($action === 'rp' && $key && $login) {
    // Mode réinitialisation via lien email
    $user = check_password_reset_key($key, $login);
    
    if (is_wp_error($user)) {
        wp_redirect(home_url('/login/?error=invalidkey'));
        exit;
    }
    
    $is_reset_mode = true;
} else {
    // Mode changement de mot de passe normal
    $is_reset_mode = false;
}
?>

<div class="wp-bmc-change-password">
    <div class="change-password-container">
        <div class="change-password-header">
            <h2><?php echo $is_reset_mode ? 'Réinitialisation de mot de passe' : 'Changement de mot de passe'; ?></h2>
            <p><?php echo $is_reset_mode ? 'Définissez votre nouveau mot de passe.' : 'Modifiez votre mot de passe pour des raisons de sécurité.'; ?></p>
        </div>
        
        <?php if ($is_reset_mode): ?>
            <!-- Formulaire de réinitialisation WordPress -->
            <form name="resetpassform" id="resetpassform" action="<?php echo esc_url(network_site_url('wp-login.php?action=resetpass', 'login_post')); ?>" method="post" autocomplete="off">
                <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr($login); ?>" autocomplete="username" />
                <input type="hidden" name="rp_key" value="<?php echo esc_attr($key); ?>" />
                
                <div class="form-group">
                    <label for="pass1">Nouveau mot de passe *</label>
                    <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="new-password" required>
                </div>
                
                <div class="form-group">
                    <label for="pass2">Confirmer le mot de passe *</label>
                    <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="new-password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="wp-submit" id="wp-submit" class="wp-bmc-btn wp-bmc-btn-primary">
                        Réinitialiser le mot de passe
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- Formulaire de changement de mot de passe custom -->
            <form id="wp-bmc-change-password-form" class="change-password-form">
                <?php wp_nonce_field('wp_bmc_nonce', 'wp_bmc_nonce'); ?>
                
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small class="description">Le mot de passe doit contenir au moins 6 caractères.</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="wp-bmc-btn wp-bmc-btn-primary">
                        Changer le mot de passe
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="form-links">
            <p><a href="<?php echo home_url('/dashboard/'); ?>" class="wp-bmc-link">Retour au tableau de bord</a></p>
        </div>
        
        <div class="change-password-info">
            <div class="info-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Conseils de sécurité</h3>
                <p>Utilisez un mot de passe fort avec au moins 8 caractères, incluant des lettres majuscules, minuscules, des chiffres et des symboles.</p>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_reset_mode): ?>
<script>
jQuery(document).ready(function($) {
    $('#wp-bmc-change-password-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var currentPassword = $('#current_password').val();
        var newPassword = $('#new_password').val();
        var confirmPassword = $('#confirm_password').val();
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            WP_BMC_Toast.error('Veuillez remplir tous les champs.');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            WP_BMC_Toast.error('Les mots de passe ne correspondent pas.');
            return;
        }
        
        if (newPassword.length < 6) {
            WP_BMC_Toast.error('Le mot de passe doit contenir au moins 6 caractères.');
            return;
        }
        
        // Désactiver le bouton et afficher le loader
        $submitBtn.prop('disabled', true).html('<div class="btn-loader"><div class="loader-spinner"></div></div>Changement en cours...');
        
        var formData = {
            action: 'wp_bmc_change_password',
            nonce: wp_bmc_ajax.nonce,
            current_password: currentPassword,
            new_password: newPassword,
            confirm_password: confirmPassword
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
            WP_BMC_Toast.error('Erreur lors du changement de mot de passe.');
        }).always(function() {
            $submitBtn.prop('disabled', false).text('Changer le mot de passe');
        });
    });
});
</script>
<?php endif; ?>