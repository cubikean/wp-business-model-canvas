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
        add_action('wp_ajax_nopriv_wp_bmc_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_wp_bmc_get_user_menu_data', array($this, 'handle_get_user_menu_data'));
        
        // Hook pour vérifier l'authentification avant l'affichage du contenu
        add_action('wp', array($this, 'check_page_access'));
        add_action('template_redirect', array($this, 'check_page_access_early'));
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
        
        // Générer un pseudonyme unique basé sur le prénom et nom
        $username = sanitize_user(strtolower($first_name . '.' . $last_name));
        $original_username = $username;
        $counter = 1;
        
        // Vérifier si le pseudonyme existe déjà et en créer un unique
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        // Créer l'utilisateur WordPress avec le pseudonyme généré
        $user_id = wp_create_user($username, $password, $email);
        
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
        
        $login = sanitize_text_field($_POST['login']);
        $password = sanitize_text_field($_POST['password']);
        
        error_log("handle_login - Tentative de connexion avec login: " . $login);
        
        // Validation
        if (empty($login) || empty($password)) {
            error_log("handle_login - Champs manquants");
            wp_send_json_error('Email/nom d\'utilisateur et mot de passe requis.');
        }
        
        // Vérifier les identifiants (accepte email ou pseudonyme)
        $user = WP_BMC_Database::verify_login($login, $password);
        
        error_log("handle_login - Résultat verify_login: " . print_r($user, true));
        
        if ($user === 'account_disabled') {
            error_log("handle_login - Compte désactivé");
            wp_send_json_error('Votre compte a été désactivé. Veuillez contacter un administrateur pour plus d\'informations.');
        } elseif ($user) {
            error_log("handle_login - Connexion réussie pour user_id: " . $user->user_id);
            
            // Si l'utilisateur est en pending, marquer qu'il doit changer son mot de passe
            if ($user->status === 'pending') {
                error_log("handle_login - Utilisateur en statut pending, marquage changement mot de passe requis");
                // Marquer que l'utilisateur doit changer son mot de passe
                update_user_meta($user->user_id, 'wp_bmc_password_change_required', true);
            }

            // Connecter l'utilisateur
            wp_set_current_user($user->user_id);
            wp_set_auth_cookie($user->user_id);
            
            error_log("handle_login - Utilisateur connecté avec succès");
            
            wp_send_json_success(array(
                'message' => 'Connexion réussie !',
                'redirect_url' => home_url('/dashboard/'),
                'password_change_required' => ($user->status === 'pending')
            ));
        } else {
            error_log("handle_login - Identifiants incorrects");
            wp_send_json_error('Email/nom d\'utilisateur ou mot de passe incorrect.');
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
     * Générer les initiales d'un utilisateur
     */
    public static function get_user_initials($user) {
        if (!$user) {
            return 'U';
        }
        
        $first_name = $user->first_name ?: '';
        $last_name = $user->last_name ?: '';
        
        // Si pas de prénom/nom, utiliser l'email
        if (empty($first_name) && empty($last_name)) {
            $email = $user->email ?: '';
            if (!empty($email)) {
                return strtoupper(substr($email, 0, 1));
            }
            return 'U';
        }
        
        $initials = '';
        if (!empty($first_name)) {
            $initials .= strtoupper(substr($first_name, 0, 1));
        }
        if (!empty($last_name)) {
            $initials .= strtoupper(substr($last_name, 0, 1));
        }
        
        return $initials ?: 'U';
    }
    
    /**
     * Obtenir le nom complet formaté d'un utilisateur
     */
    public static function get_user_full_name($user) {
        if (!$user) {
            return 'Utilisateur';
        }
        
        $first_name = $user->first_name ?: '';
        $last_name = $user->last_name ?: '';
        
        $full_name = trim($first_name . ' ' . $last_name);
        
        // Si pas de nom complet, utiliser l'email
        if (empty($full_name)) {
            $email = $user->email ?: '';
            if (!empty($email)) {
                return $email;
            }
            return 'Utilisateur';
        }
        
        return $full_name;
    }
    
    /**
     * Obtenir les données utilisateur formatées pour le menu
     */
    public static function get_user_menu_data() {
        $user = self::get_current_user();
        if (!$user) {
            return false;
        }
        
        return array(
            'user_id' => $user->user_id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => self::get_user_full_name($user),
            'initials' => self::get_user_initials($user),
            'is_admin' => isset($user->is_admin) ? $user->is_admin : false
        );
    }
    
    /**
     * Gérer la récupération des données utilisateur pour le menu
     */
    public function handle_get_user_menu_data() {
        check_ajax_referer('wp_bmc_nonce', 'nonce');
        
        if (!self::is_logged_in()) {
            wp_send_json_error('Vous devez être connecté.');
        }
        
        $user_data = self::get_user_menu_data();
        
        if ($user_data) {
            wp_send_json_success($user_data);
        } else {
            wp_send_json_error('Impossible de récupérer les données utilisateur.');
        }
    }
    
    /**
     * Rediriger si non connecté
     */
    public static function require_login() {
        if (!self::is_logged_in()) {
            error_log('WP_BMC_Auth::require_login() - Utilisateur non connecté, redirection vers login');
            
            // Forcer la redirection même si les headers sont déjà envoyés
            if (headers_sent()) {
                error_log('WP_BMC_Auth::require_login() - Headers déjà envoyés, utiliser JavaScript redirection');
                echo '<script type="text/javascript">window.location.href = "' . home_url('/login/') . '";</script>';
                echo '<noscript><meta http-equiv="refresh" content="0; url=' . home_url('/login/') . '"></noscript>';
                exit;
            } else {
                wp_redirect(home_url('/login/'));
                exit;
            }
        }
        
        // Vérifier si l'utilisateur a accès
        $current_user = self::get_current_user();
        if (!$current_user) {
            error_log('WP_BMC_Auth::require_login() - Utilisateur BMC non trouvé, redirection vers login');
            
            // Forcer la redirection même si les headers sont déjà envoyés
            if (headers_sent()) {
                error_log('WP_BMC_Auth::require_login() - Headers déjà envoyés, utiliser JavaScript redirection');
                echo '<script type="text/javascript">window.location.href = "' . home_url('/login/') . '";</script>';
                echo '<noscript><meta http-equiv="refresh" content="0; url=' . home_url('/login/') . '"></noscript>';
                exit;
            } else {
                wp_redirect(home_url('/login/'));
                exit;
            }
        }
        
        error_log('WP_BMC_Auth::require_login() - Utilisateur connecté et autorisé');
    }
    
    /**
     * Vérifier l'accès aux pages avant l'affichage
     */
    public function check_page_access() {
        global $post;
        
        // Vérifier si nous sommes sur une page qui contient le shortcode dashboard
        if (is_admin() || !$post) {
            return;
        }
        
        // Vérifier si la page contient le shortcode wp_bmc_dashboard
        if (has_shortcode($post->post_content, 'wp_bmc_dashboard')) {
            error_log('WP_BMC_Auth::check_page_access() - Page dashboard détectée');
            
            if (!self::is_logged_in()) {
                error_log('WP_BMC_Auth::check_page_access() - Utilisateur non connecté, redirection vers login');
                
                // Redirection précoce avant le shortcode
                wp_redirect(home_url('/login/'));
                exit;
            }
            
            $current_user = self::get_current_user();
            if (!$current_user) {
                error_log('WP_BMC_Auth::check_page_access() - Utilisateur BMC non trouvé, redirection vers login');
                
                wp_redirect(home_url('/login/'));
                exit;
            }
        }
    }
    
    /**
     * Vérification précoce de l'accès aux pages dashboard
     * Utilise template_redirect qui est exécuté avant l'affichage
     */
    public function check_page_access_early() {
        // Vérifier si nous sommes sur la page dashboard par URL
        if (is_page() && get_queried_object()) {
            $page = get_queried_object();
            
            // Vérifier si c'est la page dashboard (slug ou titre)
            if (strpos($page->post_name, 'dashboard') !== false || 
                strpos(strtolower($page->post_title), 'dashboard') !== false) {
                
                error_log('WP_BMC_Auth::check_page_access_early() - Page dashboard détectée:' . $page->post_name);
                
                if (!self::is_logged_in()) {
                    error_log('WP_BMC_Auth::check_page_access_early() - Utilisateur non connecté, redirection vers login');
                    
                    wp_redirect(home_url('/login/'));
                    exit;
                }
                
                $current_user = self::get_current_user();
                if (!$current_user) {
                    error_log('WP_BMC_Auth::check_page_access_early() - Utilisateur BMC non trouvé, redirection vers login');
                    
                    wp_redirect(home_url('/login/'));
                    exit;
                }
            }
        }
        
        // Vérification alternative pour les URLs contenant /dashboard
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
        if (strpos($current_url, '/dashboard') !== false) {
            error_log('WP_BMC_Auth::check_page_access_early() - URL dashboard détectée: ' . $current_url);
            
            if (!self::is_logged_in()) {
                error_log('WP_BMC_Auth::check_page_access_early() - Utilisateur non connecté, redirection vers login');
                
                wp_redirect(home_url('/login/'));
                exit;
            }
            
            $current_user = self::get_current_user();
            if (!$current_user) {
                error_log('WP_BMC_Auth::check_page_access_early() - Utilisateur BMC non trouvé, redirection vers login');
                
                wp_redirect(home_url('/login/'));
                exit;
            }
        }
    }
}

// Initialiser l'authentification
new WP_BMC_Auth();
