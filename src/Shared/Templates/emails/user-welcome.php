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
            <h1>Passeport de l’entrepreneuriat - OpenCampusInnov</h1>
        </div>
        <div class="content">
            <h2>Bienvenue <?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?> !</h2>
            
            <p>Votre compte a bien été créé sur le portail étudiant du « Passeport de l’entrepreneuriat ». Vous aurez accès à des ressources, à un canevas de Business Model en ligne ainsi qu’a l’outil Pepitizy.
            <br><br>
            <br>Vous pouvez désormais accéder à votre espace personnel et commencer à travailler sur votre passeport de l’entrepreneuriat.</p>
            
            <div class="credentials">
                <h3>Vos identifiants de connexion :</h3>
                <p><strong>Adresse email :</strong> <?php echo esc_html($email); ?></p>
                <p><strong>Mot de passe :</strong> <?php echo esc_html($password); ?></p>
                <p><strong>URL de connexion :</strong> <a href="<?php echo home_url('/login/'); ?>"><?php echo home_url('/login/'); ?></a></p>
            </div>
            
            <p><strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>
            
            <p>En cas de problème de connexion, n’hésitez pas à contacter votre chargé·e d’accompagnement ou à écrire à : <a href="mailto:opencampusinnov@univ-lr.fr">opencampusinnov@univ-lr.fr</a>
            <br>Bien cordialement,
            <br>L’équipe de OpenCampusInnov
            <br>Université de La Rochelle.</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>

