# Templates d'Emails - WP Business Model Canvas

## 📁 Structure des templates

Tous les templates d'emails se trouvent dans ce dossier :
```
src/Shared/Templates/emails/
├── user-welcome.php         (Bienvenue utilisateur)
├── supervisor-welcome.php   (Bienvenue superviseur)
├── password-reset.php       (Réinitialisation mot de passe)
└── README.md               (Ce fichier)
```

## ✏️ Comment modifier un template

### 1. Ouvrir le fichier correspondant

**Exemple : Modifier l'email de bienvenue utilisateur**
```
Ouvrir : src/Shared/Templates/emails/user-welcome.php
```

### 2. Modifier le contenu HTML

Vous pouvez modifier :
- ✅ Les textes
- ✅ Les couleurs (dans la balise `<style>`)
- ✅ La mise en page
- ✅ Ajouter des sections
- ✅ Changer le logo/header

### 3. Variables disponibles

Chaque template a accès à des variables PHP :

#### `user-welcome.php`
```php
$first_name   // Prénom de l'utilisateur
$last_name    // Nom de l'utilisateur
$email        // Email de connexion
$password     // Mot de passe généré
$custom_id    // ID personnalisé (Candidature)
```

#### `supervisor-welcome.php`
```php
$first_name   // Prénom du superviseur
$last_name    // Nom du superviseur
$email        // Email de connexion
$username     // Nom d'utilisateur WordPress
$password     // Mot de passe généré
```

#### `password-reset.php`
```php
$display_name  // Nom d'affichage complet
$new_password  // Nouveau mot de passe
```

### 4. Exemples de modifications

#### Changer la couleur du header (utilisateur)

**Avant :**
```css
.header { background-color: #2c3e50; color: white; ... }
```

**Après (bleu) :**
```css
.header { background-color: #0073aa; color: white; ... }
```

#### Ajouter un logo

```php
<div class="header">
    <img src="https://votre-site.com/logo.png" alt="Logo" style="max-width: 200px;">
    <h1>WP Business Model Canvas</h1>
</div>
```

#### Modifier le message de bienvenue

**Avant :**
```php
<h2>Bienvenue <?php echo esc_html($first_name); ?> <?php echo esc_html($last_name); ?> !</h2>
```

**Après :**
```php
<h2>Bonjour <?php echo esc_html($first_name); ?>,</h2>
<p>Nous sommes ravis de vous accueillir sur notre plateforme !</p>
```

#### Ajouter un lien de connexion

```php
<p style="text-align: center;">
    <a href="<?php echo home_url('/login'); ?>" 
       style="background: #3498db; color: white; padding: 12px 30px; text-decoration: none; border-radius: 4px; display: inline-block;">
        Se connecter maintenant
    </a>
</p>
```

## 🔧 Fonctions utilitaires

### `wp_bmc_render_email_template($template_name, $variables)`

Rend un template avec des variables.

**Exemple :**
```php
$html = wp_bmc_render_email_template('user-welcome', array(
    'first_name' => 'Jean',
    'last_name' => 'Dupont',
    'email' => 'jean@example.com',
    'password' => 'CAND001Jean',
    'custom_id' => 'CAND001'
));
```

### `wp_bmc_send_email($to, $subject, $template_name, $variables)`

Envoie un email en utilisant un template.

**Exemple :**
```php
wp_bmc_send_email(
    'jean@example.com',
    'Bienvenue !',
    'user-welcome',
    array(
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'email' => 'jean@example.com',
        'password' => 'CAND001Jean',
        'custom_id' => 'CAND001'
    )
);
```

### Fonctions helper spécifiques

#### `wp_bmc_send_user_welcome_email($email, $first_name, $last_name, $password, $custom_id)`

Envoie l'email de bienvenue utilisateur.

#### `wp_bmc_send_supervisor_welcome_email($email, $first_name, $last_name, $username, $password)`

Envoie l'email de bienvenue superviseur.

#### `wp_bmc_send_password_reset_email($email, $display_name, $new_password)`

Envoie l'email de réinitialisation de mot de passe.

## 📋 Créer un nouveau template

### 1. Créer le fichier

```
Créer : src/Shared/Templates/emails/mon-template.php
```

### 2. Structure de base

```php
<?php
/**
 * Template email : Description
 * Variables disponibles : $var1, $var2
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
        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; background-color: #f8f9fa; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Titre</h1>
        </div>
        <div class="content">
            <h2>Bonjour <?php echo esc_html($var1); ?></h2>
            <p>Votre contenu ici...</p>
        </div>
        <div class="footer">
            <p>Email automatique</p>
        </div>
    </div>
</body>
</html>
```

### 3. Créer une fonction helper (optionnel)

Dans `src/Shared/Utils/email-templates.php` :

```php
function wp_bmc_send_mon_email($email, $var1, $var2) {
    return wp_bmc_send_email(
        $email,
        'Sujet de l\'email',
        'mon-template',
        compact('var1', 'var2')
    );
}
```

### 4. Utiliser dans le code

```php
wp_bmc_send_mon_email($user_email, 'valeur1', 'valeur2');
```

## 🎨 Palette de couleurs recommandée

| Type email | Couleur header | Code |
|-----------|----------------|------|
| **Bienvenue user** | Bleu foncé | `#2c3e50` |
| **Bienvenue supervisor** | Vert | `#27ae60` |
| **Réinitialisation** | Rouge | `#e74c3c` |
| **Information** | Bleu | `#3498db` |
| **Avertissement** | Orange | `#f39c12` |

## 🔍 Tester les modifications

### 1. Créer un utilisateur test

```
BMC > Utilisateurs > Créer un utilisateur
→ Email envoyé avec votre nouveau template
```

### 2. Vérifier dans la boîte mail

Ouvrez l'email reçu et vérifiez :
- ✅ Affichage correct du HTML
- ✅ Variables bien remplacées
- ✅ Couleurs correctes
- ✅ Responsive (mobile)

### 3. Tester sur différents clients mail

- 📧 Gmail
- 📧 Outlook
- 📧 Yahoo
- 📧 Thunderbird

## ⚠️ Bonnes pratiques

### CSS inline

Pour une meilleure compatibilité, utilisez des styles inline :

```php
<p style="color: #333; font-size: 16px;">Texte</p>
```

Au lieu de classes CSS externes.

### Variables échappées

Toujours échapper les variables :

```php
<?php echo esc_html($variable); ?>    // ✅ Correct
<?php echo $variable; ?>               // ❌ Risque XSS
```

### Tableaux pour la mise en page

Les clients mail anciens ne supportent pas Flexbox/Grid :

```html
<table width="600" cellpadding="0" cellspacing="0">
    <tr>
        <td>Contenu</td>
    </tr>
</table>
```

### Images

Utilisez des URLs absolues :

```php
<img src="<?php echo WP_BMC_PLUGIN_URL; ?>assets/logo.png" alt="Logo">
```

## 📝 Logs et débogage

Les emails sont loggés dans `wp-content/debug.log` :

```
wp_bmc_send_email - Email envoyé avec succès à jean@ex.com (template: user-welcome)
wp_bmc_send_email - Échec de l'envoi à marie@ex.com (template: supervisor-welcome)
```

## 🔄 Propagation des changements

**Les modifications sont immédiates !**

1. Modifier le fichier template
2. Enregistrer
3. Tester en créant un utilisateur
4. ✅ Le nouvel email est envoyé avec vos modifications

**Pas besoin de :**
- ❌ Vider le cache
- ❌ Recharger WordPress
- ❌ Redémarrer le serveur

## 📚 Ressources utiles

- **Guide email HTML** : https://www.campaignmonitor.com/css/
- **Testeur email** : https://litmus.com/
- **Inline CSS tool** : https://htmlemail.io/inline/

## 🎯 Cas d'usage avancés

### Email multilingue

```php
<?php
$lang = get_user_locale(); // Ex: fr_FR
if ($lang === 'en_US') {
    echo '<h2>Welcome ' . esc_html($first_name) . '!</h2>';
} else {
    echo '<h2>Bienvenue ' . esc_html($first_name) . ' !</h2>';
}
?>
```

### Contenu dynamique

```php
<?php
$is_first_user = WP_BMC_Database::get_all_users_count() === 1;
if ($is_first_user) {
    echo '<p>🎉 Vous êtes le premier utilisateur inscrit !</p>';
}
?>
```

### Pièces jointes

```php
// Dans le code qui appelle wp_mail()
$attachments = array(WP_BMC_PLUGIN_DIR . 'docs/guide.pdf');
wp_mail($to, $subject, $message, $headers, $attachments);
```

## 🚀 Templates supplémentaires suggérés

Vous pourriez créer :

- `project-assigned.php` - Notification d'assignation à un projet
- `grading-completed.php` - Notification de note reçue
- `project-reminder.php` - Rappel de projet en attente
- `weekly-summary.php` - Résumé hebdomadaire

## 📞 Support

Pour toute question sur les templates d'emails :
1. Consultez ce README
2. Vérifiez les logs dans `debug.log`
3. Testez avec un compte test
4. Contactez le support technique

