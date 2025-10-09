<?php
/**
 * Template email : Bienvenue utilisateur
 * Variables disponibles : $first_name, $last_name, $email, $password, $custom_id
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
        .header { background-color: #153ca8; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f8f9fa; }
        .credentials { background-color: #e8f4f8; padding: 20px; border-left: 4px solid #3498db; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WP Business Model Canvas</h1>
        </div>
        <div class="content">
            <h2>Bienvenue <?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?> !</h2>
            
            <p>Votre compte a été créé avec succès sur la plateforme WP Business Model Canvas.</p>
            
            <div class="credentials">
                <h3>Vos identifiants de connexion :</h3>
                <p><strong>Adresse email :</strong> <?php echo esc_html($email); ?></p>
                <p><strong>Mot de passe :</strong> <?php echo esc_html($password); ?></p>
            </div>
            
            <p><strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
            
            <p>Vous pouvez maintenant accéder à votre espace personnel et commencer à créer vos Business Model Canvas.</p>
            
            <p>Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter.</p>
            
            <p>Cordialement</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>

