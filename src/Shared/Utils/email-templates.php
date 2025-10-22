<?php
/**
 * Fonctions utilitaires pour les templates d'emails
 * WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Charger et rendre un template d'email
 * 
 * @param string $template_name Nom du template (sans extension)
 * @param array $variables Variables à passer au template
 * @return string HTML du template rendu
 */
function wp_bmc_render_email_template($template_name, $variables = array()) {
    $template_path = WP_BMC_SHARED_DIR . 'Templates/emails/' . $template_name . '.php';
    
    if (!file_exists($template_path)) {
        error_log("wp_bmc_render_email_template - Template non trouvé : $template_path");
        return '';
    }
    
    // Extraire les variables pour les rendre disponibles dans le template
    extract($variables);
    
    // Capturer le rendu du template
    ob_start();
    include $template_path;
    $html = ob_get_clean();
    
    return $html;
}

/**
 * Envoyer un email en utilisant un template
 * 
 * @param string $to Adresse email du destinataire
 * @param string $subject Sujet de l'email
 * @param string $template_name Nom du template
 * @param array $variables Variables à passer au template
 * @return bool True si l'email a été envoyé, false sinon
 */
function wp_bmc_send_email($to, $subject, $template_name, $variables = array()) {
    $html_content = wp_bmc_render_email_template($template_name, $variables);
    
    if (empty($html_content)) {
        error_log("wp_bmc_send_email - Impossible de rendre le template : $template_name");
        return false;
    }
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $result = wp_mail($to, $subject, $html_content, $headers);
    
    if ($result) {
        error_log("wp_bmc_send_email - Email envoyé avec succès à $to (template: $template_name)");
    } else {
        error_log("wp_bmc_send_email - Échec de l'envoi à $to (template: $template_name)");
    }
    
    return $result;
}

/**
 * Envoyer un email de bienvenue à un utilisateur
 * 
 * @param string $email Email de l'utilisateur
 * @param string $first_name Prénom
 * @param string $last_name Nom
 * @param string $password Mot de passe
 * @param string $custom_id ID personnalisé
 * @return bool
 */
function wp_bmc_send_user_welcome_email($email, $first_name, $last_name, $password, $custom_id) {
    return wp_bmc_send_email(
        $email,
        'Bienvenue sur le passeport de l\'entrepreneuriat - Vos identifiants de connexion',
        'user-welcome',
        compact('first_name', 'last_name', 'email', 'password', 'custom_id')
    );
}

/**
 * Envoyer un email de bienvenue à un superviseur
 * 
 * @param string $email Email du superviseur
 * @param string $first_name Prénom
 * @param string $last_name Nom
 * @param string $username Nom d'utilisateur
 * @param string $password Mot de passe
 * @return bool
 */
function wp_bmc_send_supervisor_welcome_email($email, $first_name, $last_name, $username, $password) {
    return wp_bmc_send_email(
        $email,
        'Bienvenue - Accès Superviseur - Passeport de l\'entrepreneuriat',
        'supervisor-welcome',
        compact('first_name', 'last_name', 'email', 'username', 'password')
    );
}

/**
 * Envoyer un email de réinitialisation de mot de passe
 * 
 * @param string $email Email de l'utilisateur
 * @param string $display_name Nom d'affichage
 * @param string $new_password Nouveau mot de passe
 * @return bool
 */
function wp_bmc_send_password_reset_email($email, $display_name, $new_password) {
    return wp_bmc_send_email(
        $email,
        'Réinitialisation de votre mot de passe - Passeport de l\'entrepreneuriat',
        'password-reset',
        compact('display_name', 'new_password')
    );
}



