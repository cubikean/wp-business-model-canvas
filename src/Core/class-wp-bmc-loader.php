<?php
/**
 * Classe principale du chargeur du plugin WP Business Model Canvas
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_BMC_Loader {
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        // Les fichiers sont déjà inclus dans le fichier principal
    }
    
    /**
     * Définir la locale
     */
    private function set_locale() {
        load_plugin_textdomain(
            'wp-business-model-canvas',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
    
    /**
     * Définir les hooks d'administration
     */
    private function define_admin_hooks() {
        // Hooks pour l'administration
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Définir les hooks publics
     */
    private function define_public_hooks() {
        // Hooks pour le front-end
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('wp_head', array($this, 'add_custom_styles'));
    }
    
    /**
     * Exécuter le plugin
     */
    public function run() {
        // Le plugin est maintenant chargé
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu() {
        add_menu_page(
            'WP Business Model Canvas',
            'BMC',
            'manage_options',
            'wp-business-model-canvas',
            array($this, 'admin_page'),
            'dashicons-chart-area',
            30
        );
        
        // Ajouter un sous-menu pour la vérification
        add_submenu_page(
            'wp-business-model-canvas',
            'Vérification',
            'Vérification',
            'manage_options',
            'wp-business-model-canvas-check',
            array($this, 'check_page')
        );
    }
    
    /**
     * Page d'administration
     */
    public function admin_page() {
        include WP_BMC_ADMIN_DIR . 'Controllers/admin-page.php';
    }
    
    /**
     * Page de vérification
     */
    public function check_page() {
        echo '<div class="wrap">';
        echo '<h1>🔍 Vérification WP Business Model Canvas v2.0</h1>';
        
        if (class_exists('WP_BMC_Complete_Check')) {
            $results = WP_BMC_Complete_Check::run_complete_check();
            WP_BMC_Complete_Check::display_complete_results($results);
        } else {
            echo '<div class="notice notice-error"><p>Classe de vérification non trouvée.</p></div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Charger les scripts d'administration
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_wp-business-model-canvas') {
            return;
        }
        
        wp_enqueue_script(
            'wp-bmc-admin',
            WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin.js',
            array('jquery'),
            WP_BMC_VERSION,
            true
        );
    }
    
    /**
     * Charger les scripts publics
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style(
            'wp-bmc-public',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/public.css',
            array(),
            WP_BMC_VERSION
        );
        
        // Charger Font Awesome pour les icônes
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );
        
        wp_enqueue_script(
            'wp-bmc-public',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/public.js',
            array('jquery'),
            WP_BMC_VERSION,
            true
        );
        
        // Charger le script d'authentification
        wp_enqueue_script(
            'wp-bmc-auth',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/auth.js',
            array('jquery'),
            WP_BMC_VERSION,
            true
        );
        
        // Charger le script du dashboard
        wp_enqueue_script(
            'wp-bmc-dashboard',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/dashboard.js',
            array('jquery'),
            WP_BMC_VERSION,
            true
        );
        
        // Si c'est un admin, charger aussi les styles admin
        if (current_user_can('manage_options')) {
            wp_enqueue_style(
                'wp-bmc-admin',
                WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/admin.css',
                array(),
                WP_BMC_VERSION
            );
            
            // Charger aussi le script admin-dashboard pour les admins
            wp_enqueue_script(
                'wp-bmc-admin-dashboard',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-dashboard.js',
                array('jquery'),
                WP_BMC_VERSION,
                true
            );
            
            // Localiser les variables AJAX pour les admins
            wp_localize_script('wp-bmc-admin-dashboard', 'wp_bmc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
            ));
        }
        
        wp_localize_script('wp-bmc-public', 'wp_bmc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_bmc_nonce')
        ));
    }
    
    /**
     * Ajouter les styles personnalisés
     */
    public function add_custom_styles() {
        echo '<style>
            .wp-bmc-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .wp-bmc-form {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .wp-bmc-form input[type="text"],
            .wp-bmc-form input[type="email"],
            .wp-bmc-form input[type="password"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .wp-bmc-form button {
                background: #0073aa;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .wp-bmc-form button:hover {
                background: #005177;
            }
            .wp-bmc-message {
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 4px;
            }
            .wp-bmc-message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .wp-bmc-message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>';
    }
}