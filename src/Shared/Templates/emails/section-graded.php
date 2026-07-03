<?php
/**
 * Template email : Notification de notation de section
 * Variables disponibles : $first_name, $last_name, $section_title, $rating, $comment, $admin_name, $project_title
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
        .rating-box { background-color: #e8f4f8; padding: 20px; border-left: 4px solid #3498db; margin: 20px 0; text-align: center; }
        .rating-score { font-size: 48px; font-weight: bold; color: #153ca8; margin: 10px 0; }
        .comment-box { background-color: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd; }
        .cta-button { display: inline-block; padding: 12px 30px; background-color: #153ca8; color: white !important; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Passeport de l'entrepreneuriat - OpenCampusInnov</h1>
        </div>
        <div class="content">
            <h2>Bonjour <?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?>,</h2>
            
            <p>Votre chargé d'accompagnement <strong><?php echo esc_html($admin_name); ?></strong> vient d'évaluer la section <strong>« <?php echo esc_html($section_title); ?> »</strong> de votre projet <strong>« <?php echo esc_html($project_title); ?> »</strong>.</p>
            
            <div class="rating-box">
                <p style="margin: 0; color: #666; font-size: 14px;">Score de maturité</p>
                <div class="rating-score"><?php echo esc_html($rating); ?>/10</div>
            </div>
            
            <?php if (!empty($comment)): ?>
            <div class="comment-box">
                <h3 style="margin-top: 0; color: #153ca8;">Commentaire de votre chargé d'accompagnement :</h3>
                <p style="white-space: pre-wrap;"><?php echo esc_html($comment); ?></p>
            </div>
            <?php endif; ?>
            
            <p style="text-align: center;">
                <a href="<?php echo home_url('/dashboard/'); ?>" class="cta-button">Voir mon tableau de bord</a>
            </p>
            
            <p>Vous pouvez consulter votre score de maturité et les commentaires détaillés en vous connectant à votre espace personnel.</p>
            
            
            <p>Bien cordialement,<br>L'équipe OpenCampusInnov<br>Université de La Rochelle</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>

