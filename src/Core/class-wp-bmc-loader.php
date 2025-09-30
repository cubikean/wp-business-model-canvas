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
        // Menu principal - Projets
        add_menu_page(
            'BMC',
            'BMC',
            'manage_options',
            'wp-business-model-canvas-projects',
            array($this, 'admin_projects_page'),
            'dashicons-chart-area',
            30
        );

        // Menu principal - Utilisateurs
        add_submenu_page(
            'wp-business-model-canvas-projects',
            'Projets',
            'Projets',
            'manage_options',
            'wp-business-model-canvas-projects',
            array($this, 'admin_projects_page')
        );
        
        // Sous-menu - Utilisateurs
        add_submenu_page(
            'wp-business-model-canvas-projects',
            'Utilisateurs',
            'Utilisateurs',
            'manage_options',
            'wp-business-model-canvas-users',
            array($this, 'admin_users_page')
        );
        
        // Sous-menu - Configuration
        add_submenu_page(
            'wp-business-model-canvas-projects',
            'Configuration',
            'Configuration',
            'manage_options',
            'wp-business-model-canvas-config',
            array($this, 'canvas_config_page')
        );
    }
    
   
    /**
     * Page de gestion des projets
     */
    public function admin_projects_page() {
        include WP_BMC_ADMIN_DIR . 'Controllers/admin-projects.php';
    }
    
    /**
     * Page de gestion des utilisateurs
     */
    public function admin_users_page() {
        include WP_BMC_ADMIN_DIR . 'Controllers/admin-users.php';
    }
    
    // /**
    //  * Page de migration v2.0
    //  */
    // public function migration_page() {
    //     include WP_BMC_CORE_DIR . 'class-wp-bmc-migration-v2.php';
        
    //     // Vérifier l'état de la migration
    //     $status = WP_BMC_Migration_V2::check_migration_status();
        
    //     echo '<div class="wrap">';
    //     echo '<h1>Migration vers la v2.0</h1>';
        
    //     if ($status['needs_migration']) {
    //         echo '<div class="notice notice-warning">';
    //         echo '<h3>Migration nécessaire</h3>';
    //         echo '<p>Votre installation nécessite une migration vers la version 2.0. Les problèmes suivants ont été détectés :</p>';
    //         echo '<ul>';
    //         foreach ($status['issues'] as $issue) {
    //             echo '<li>' . esc_html($issue) . '</li>';
    //         }
    //         echo '</ul>';
    //         echo '</div>';
            
    //         echo '<form method="post" action="">';
    //         wp_nonce_field('wp_bmc_migration_v2', 'wp_bmc_migration_nonce');
    //         echo '<input type="hidden" name="action" value="run_migration_v2">';
    //         echo '<p><button type="submit" class="button button-primary">Lancer la migration</button></p>';
    //         echo '</form>';
    //     } else {
    //         echo '<div class="notice notice-success">';
    //         echo '<h3>Migration terminée</h3>';
    //         echo '<p>Votre installation est déjà à jour avec la version 2.0.</p>';
    //         echo '</div>';
    //     }
        
    //     // Traitement de la migration
    //     if (isset($_POST['action']) && $_POST['action'] === 'run_migration_v2' && 
    //         check_admin_referer('wp_bmc_migration_v2', 'wp_bmc_migration_nonce')) {
            
    //         $results = WP_BMC_Migration_V2::migrate_to_v2();
    //         WP_BMC_Migration_V2::display_migration_results($results);
    //     }
        
    //     echo '</div>';
    // }
    
    // /**
    //  * Page de vérification
    //  */
    // public function check_page() {
    //     echo '<div class="wrap">';
    //     echo '<h1>🔍 Vérification WP Business Model Canvas v2.0</h1>';
        
    //     if (class_exists('WP_BMC_Complete_Check')) {
    //         $results = WP_BMC_Complete_Check::run_complete_check();
    //         WP_BMC_Complete_Check::display_complete_results($results);
    //     } else {
    //         echo '<div class="notice notice-error"><p>Classe de vérification non trouvée.</p></div>';
    //     }
        
    //     echo '</div>';
    // }
    
    /**
     * Page de configuration du canvas
     */
    public function canvas_config_page() {
        include WP_BMC_PLUGIN_DIR . 'src/Admin/Controllers/admin-canvas-config.php';
    }
    
    /**
     * Charger les scripts d'administration
     */
    public function enqueue_admin_scripts($hook) {
        
        // Vérifier si on est sur une page du plugin BMC
        $bmc_pages = array(
            'toplevel_page_wp-business-model-canvas-projects',
            'bmc_page_wp-business-model-canvas-users',
            'bmc_page_wp-business-model-canvas-config'
        );
        
        if (!in_array($hook, $bmc_pages)) {
            return;
        }
        
        // Charger le système de toasts (toujours nécessaire)
        wp_enqueue_script(
            'wp-bmc-toast',
            WP_BMC_PLUGIN_URL . 'src/Shared/Assets/js/wp-bmc-toast.js',
            array('jquery'),
            WP_BMC_VERSION,
            true
        );
        
        wp_enqueue_style(
            'wp-bmc-toast-css',
            WP_BMC_PLUGIN_URL . 'src/Shared/Assets/css/wp-bmc-toast.css',
            array(),
            WP_BMC_VERSION
        );
        
        // Script principal d'administration (toujours nécessaire)
        wp_enqueue_script(
            'wp-bmc-admin',
            WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin.js',
            array('jquery', 'wp-bmc-toast'),
            WP_BMC_VERSION,
            true
        );
        
        // Charger les scripts spécifiques selon la page
        if (strpos($hook, 'wp-business-model-canvas-projects') !== false) {
            // Page des projets
            wp_enqueue_script(
                'wp-bmc-admin-projects',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-projects.js',
                array('jquery', 'wp-bmc-toast'),
                WP_BMC_VERSION,
                true
            );
            
            wp_enqueue_style(
                'wp-bmc-admin-projects-css',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/css/admin-projects.css',
                array(),
                WP_BMC_VERSION
            );
        }
        
        if (strpos($hook, 'wp-business-model-canvas-users') !== false) {
            // Page des utilisateurs
            wp_enqueue_script(
                'wp-bmc-admin-users',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-users.js',
                array('jquery', 'wp-bmc-toast'),
                WP_BMC_VERSION,
                true
            );

            wp_enqueue_style(
                'wp-bmc-admin-users-css',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/css/admin-users.css',
                array(),
                WP_BMC_VERSION
            );
        }
        
        if (strpos($hook, 'wp-business-model-canvas-config') !== false) {
            // Page de configuration
            wp_enqueue_script(
                'wp-bmc-admin-canvas-config',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-canvas-config.js',
                array('jquery', 'wp-bmc-toast'),
                WP_BMC_VERSION,
                true
            );
        }

        // Variables AJAX pour les scripts admin
        wp_localize_script('wp-bmc-admin', 'wp_bmc_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => admin_url(),
            'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
        ));
        
        // Variables AJAX pour les scripts spécifiques
        if (strpos($hook, 'wp-business-model-canvas-users') !== false) {
            wp_localize_script('wp-bmc-admin-users', 'wp_bmc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
            ));
        }
        
        if (strpos($hook, 'wp-business-model-canvas-projects') !== false) {
            wp_localize_script('wp-bmc-admin-projects', 'wp_bmc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
            ));
        }
        
        if (strpos($hook, 'wp-business-model-canvas-config') !== false) {
            wp_localize_script('wp-bmc-admin-canvas-config', 'wp_bmc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'nonce' => wp_create_nonce('wp_bmc_admin_nonce')
            ));
        }
    }
    
    /**
     * Charger les polices personnalisées
     */
    public function enqueue_fonts() {
        wp_enqueue_style(
            'wp-bmc-fonts',
            WP_BMC_PLUGIN_URL . 'src/Shared/Utils/fonts/urbanist.css',
            array(),
            WP_BMC_VERSION
        );
    }
    
    /**
     * Charger les scripts publics
     */
    public function enqueue_public_scripts() {
        // Charger les polices en premier
        $this->enqueue_fonts();
        
        wp_enqueue_style(
            'wp-bmc-public',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/public.css',
            array('wp-bmc-fonts'),
            WP_BMC_VERSION
        );
        
        // Charger Font Awesome pour les icônes (fichiers locaux)
        wp_enqueue_style(
            'font-awesome',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/font-awesome.min.css',
            array(),
            '6.0.0'
        );
        
        // Charger le système de Toast
        wp_enqueue_style(
            'wp-bmc-toast',
            WP_BMC_PLUGIN_URL . 'src/Shared/Assets/css/wp-bmc-toast.css',
            array(),
            WP_BMC_VERSION
        );
        
        wp_enqueue_script(
            'wp-bmc-toast',
            WP_BMC_PLUGIN_URL . 'src/Shared/Assets/js/wp-bmc-toast.js',
            array(),
            WP_BMC_VERSION,
            true
        );
        
        wp_enqueue_script(
            'wp-bmc-public',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/public.js',
            array('jquery', 'wp-bmc-toast'),
            WP_BMC_VERSION,
            true
        );
        
        // Charger le script d'authentification
        wp_enqueue_script(
            'wp-bmc-auth',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/auth.js',
            array('jquery', 'wp-bmc-toast'),
            WP_BMC_VERSION,
            true
        );
        
        // Charger le script du dashboard
        wp_enqueue_script(
            'wp-bmc-dashboard',
            WP_BMC_PLUGIN_URL . 'src/Public/Assets/js/dashboard.js',
            array('jquery', 'wp-bmc-toast'),
            WP_BMC_VERSION,
            true
        );
        
        // Si c'est un admin, charger aussi les styles admin
        if (current_user_can('manage_options')) {
            // Charger les polices pour l'admin
            $this->enqueue_fonts();
            
            wp_enqueue_style(
                'wp-bmc-admin',
                WP_BMC_PLUGIN_URL . 'src/Public/Assets/css/admin.css',
                array('wp-bmc-fonts', 'font-awesome'),
                WP_BMC_VERSION
            );
            
            // Charger aussi le script admin-dashboard pour les admins
            wp_enqueue_script(
                'wp-bmc-admin-dashboard',
                WP_BMC_PLUGIN_URL . 'src/Admin/Assets/js/admin-dashboard.js',
                array('jquery', 'wp-bmc-toast'),
                WP_BMC_VERSION,
                true
            );
            
            // Localiser les variables AJAX pour les admins
            wp_localize_script('wp-bmc-admin-dashboard', 'wp_bmc_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
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