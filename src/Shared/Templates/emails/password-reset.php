<?php
/**
 * Template email : Réinitialisation de mot de passe
 * Variables disponibles : $display_name, $new_password
 */
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #e74c3c; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f8f9fa; }
        .credentials { background-color: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Réinitialisation de mot de passe</h1>
        </div>
        <div class="content">
            <h2>Bonjour <?php echo esc_html($display_name); ?>,</h2>
            
            <p>Votre mot de passe a été réinitialisé par un administrateur.</p>
            
            <div class="credentials">
                <h3>Votre nouveau mot de passe :</h3>
                <p style="font-size: 18px;"><strong><?php echo esc_html($new_password); ?></strong></p>
            </div>
            
            <p><strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer ce mot de passe dès votre prochaine connexion.</p>
            
            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez contacter un administrateur immédiatement.</p>
            
            <p>Cordialement,<br>L’équipe OpenCampusInnov</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>

