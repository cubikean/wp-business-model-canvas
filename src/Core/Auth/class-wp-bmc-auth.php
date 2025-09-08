<?php
/**
 * Classe d'authentification pour WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Auth {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('wp_ajax_wp_bmc_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_wp_bmc_register', array($this, 'handle_register'));
        add_action('wp_ajax_wp_bmc_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_wp_bmc_login', array($this, 'handle_login'));
        add_action('wp_ajax_wp_bmc_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_wp_bmc_logout', array($this, 'handle_logout'));
    }
    
    /**
     * Gérer l'inscription
     */
    public function handle_register() {
        check_ajax_referer('wp_bmc_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $company = sanitize_text_field($_POST['company']);
        
        // Validation
        if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            wp_send_json_error('Tous les champs obligatoires doivent être remplis.');
        }
        
        if (!is_email($email)) {
            wp_send_json_error('Adresse email invalide.');
        }
        
        if (strlen($password) < 6) {
            wp_send_json_error('Le mot de passe doit contenir au moins 6 caractères.');
        }
        
        // Vérifier si l'email existe déjà
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_users';
        $existing_user = $wpdb->get_row(
            $wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email)
        );
        
        if ($existing_user) {
            wp_send_json_error('Cette adresse email est déjà utilisée.');
        }
        
        // Créer l'utilisateur WordPress
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Erreur lors de la création du compte.');
        }
        
        // Mettre à jour les informations utilisateur
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        // Insérer dans la table BMC
        $bmc_user_id = WP_BMC_Database::insert_user(array(
            'user_id' => $user_id,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company
        ));
        
        if ($bmc_user_id) {
            // Connecter automatiquement l'utilisateur
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_send_json_success(array(
                'message' => 'Compte créé avec succès !',
                'redirect_url' => home_url('/dashboard/')
            ));
        } else {
            wp_send_json_error('Erreur lors de la création du compte.');
        }
    }
    
    /**
     * Gérer la connexion
     */
    public function handle_login() {
        check_ajax_referer('wp_bmc_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        
        // Validation
        if (empty($email) || empty($password)) {
            wp_send_json_error('Email et mot de passe requis.');
        }
        
        // Vérifier les identifiants
        $user = WP_BMC_Database::verify_login($email, $password);
        
        if ($user) {
            // Connecter l'utilisateur
            wp_set_current_user($user->user_id);
            wp_set_auth_cookie($user->user_id);
            
            wp_send_json_success(array(
                'message' => 'Connexion réussie !',
                'redirect_url' => home_url('/dashboard/')
            ));
        } else {
            wp_send_json_error('Email ou mot de passe incorrect.');
        }
    }
    
    /**
     * Gérer la déconnexion
     */
    public function handle_logout() {
        check_ajax_referer('wp_bmc_nonce', 'nonce');
        
        wp_logout();
        
        wp_send_json_success(array(
            'message' => 'Déconnexion réussie.',
            'redirect_url' => home_url('/login/')
        ));
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function is_logged_in() {
        return is_user_logged_in();
    }
    
    /**
     * Obtenir l'utilisateur BMC actuel
     */
    public static function get_current_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user_id = get_current_user_id();
        
        // Si c'est un administrateur WordPress, créer un objet utilisateur virtuel
        if (current_user_can('manage_options')) {
            $wp_user = get_userdata($current_user_id);
            if ($wp_user) {
                return (object) array(
                    'user_id' => $current_user_id,
                    'email' => $wp_user->user_email,
                    'first_name' => $wp_user->first_name ?: 'Admin',
                    'last_name' => $wp_user->last_name ?: 'WordPress',
                    'company' => 'Administration WordPress',
                    'is_admin' => true
                );
            }
        }
        
        // Sinon, chercher dans la table BMC
        global $wpdb;
        $table = $wpdb->prefix . 'bmc_users';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d",
                $current_user_id
            )
        );
    }
    
    /**
     * Rediriger si non connecté
     */
    public static function require_login() {
        if (!self::is_logged_in()) {
            wp_redirect(home_url('/login/'));
            exit;
        }
        
        // Vérifier si l'utilisateur a accès
        $current_user = self::get_current_user();
        if (!$current_user) {
            wp_redirect(home_url('/login/'));
            exit;
        }
    }
}

// Initialiser l'authentification
new WP_BMC_Auth();
