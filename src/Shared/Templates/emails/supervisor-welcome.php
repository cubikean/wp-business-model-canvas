<?php
/**
 * Template email : Bienvenue superviseur
 * Variables disponibles : $first_name, $last_name, $email, $username, $password
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
        .header { background-color: #fe4b6b; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f8f9fa; }
        .credentials { background-color: #ff4b6b21; padding: 20px; border-left: 4px solid #fe4b6b; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .badge { display: inline-block; background-color: #fe4b6b; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Passeport de l’entrepreneuriat - OpenCampusInnov</h1>
            <p><span class="badge">Accès chargé d’accompagnement</span></p>
        </div>
        <div class="content">
            <h2>Bienvenue <?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?> !</h2>
            
            <p>Votre compte de <strong>chargé d’accompagnement</strong> a été créé avec succès sur la plateforme Passeport de l’entrepreneuriat - OpenCampusInnov.</p>
            
            <div class="credentials">
                <h3>Vos identifiants de connexion :</h3>
                <p><strong>Adresse email :</strong> <?php echo esc_html($email); ?></p>
                <p><strong>Mot de passe :</strong> <?php echo esc_html($password); ?></p>
                <p><strong>URL de connexion :</strong> <a href="<?php echo home_url('/login/'); ?>"><?php echo home_url('/login/'); ?></a></p>
            </div>
            
            <h3>Vos privilèges chargé d’accompagnement :</h3>
            <ul>
                <li>Créer et gérer des projets</li>
                <li>Créer et gérer des utilisateurs</li>
                <li>Superviser les passeports de l’entrepreneuriat</li>
                <li>Noter et commenter les sections</li>
                <li>Accéder au tableau de bord administrateur</li>
            </ul>
            
            <p><strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
            
            <p>Vous pouvez maintenant accéder à l’interface d’administration et commencer à superviser les projets.</p>
            
            <p>Si vous avez des questions ou besoin d’assistance, n’hésitez pas à nous contacter.</p>
            
            <p>Cordialement,<br>L’équipe OpenCampusInnov</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>

