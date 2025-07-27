<?php
/**
 * Classe Shortcode FilteredGallery
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour gérer les shortcodes
 */
class FilteredGallery_Shortcode {
    
    /**
     * Instance unique de la classe
     */
    private static $instance = null;
    
    /**
     * Constructeur de la classe
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialisation des hooks
     */
    private function init_hooks() {
        add_shortcode('filtered_gallery', array($this, 'gallery_shortcode'));
        add_shortcode('filtered_gallery_simple', array($this, 'simple_gallery_shortcode'));
    }
    
    /**
     * Shortcode principal pour la galerie filtrée
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_filters' => 'true',
            'show_categories' => 'true',
            'lightbox' => 'true',
            'lazy_loading' => 'true'
        ), $atts, 'filtered_gallery');
        
        // Récupérer les images
        $images = $this->get_gallery_images($atts);
        
        // Récupérer les catégories pour les filtres
        $categories = $this->get_categories();
        
        // Générer un ID unique pour la galerie
        $gallery_id = 'filtered-gallery-' . uniqid();
        
        // Générer le HTML
        ob_start();
        ?>
        <div class="filtered-gallery" id="<?php echo esc_attr($gallery_id); ?>">
            <?php if ($atts['show_filters'] === 'true' && !empty($categories)) : ?>
                <div class="filtered-gallery-filters">
                    <a href="#" class="filtered-gallery-filter active" data-category=""><?php _e('Toutes', 'filtered-gallery'); ?></a>
                    <?php foreach ($categories as $category) : ?>
                        <a href="#" class="filtered-gallery-filter" data-category="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="filtered-gallery-container">
                <?php if (!empty($images)) : ?>
                    <ul class="filtered-gallery-grid columns-<?php echo intval($atts['columns']); ?>">
                        <?php foreach ($images as $image) : ?>
                            <li class="filtered-gallery-item" data-category="<?php echo esc_attr($image->category_slug ?? ''); ?>">
                                <img src="<?php echo esc_url($image->image_url ); ?>" 
                                     alt="<?php echo esc_attr($image->alt_text ?: $image->title); ?>"
                                     data-full="<?php echo esc_url($image->image_url); ?>"
                                     <?php echo $atts['lazy_loading'] === 'true' ? 'loading="lazy"' : ''; ?>>
                                
                                <?php if ($atts['show_categories'] === 'true' && !empty($image->category_name)) : ?>
                                    <div class="filtered-gallery-category"><?php echo esc_html($image->category_name); ?></div>
                                <?php endif; ?>
                                
                                <div class="filtered-gallery-overlay">
                                    <h3 class="filtered-gallery-title"><?php echo esc_html($image->title); ?></h3>
                                    <?php if (!empty($image->description)) : ?>
                                        <p class="filtered-gallery-description"><?php echo esc_html($image->description); ?></p>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="filtered-gallery-no-images">
                        <h3><?php _e('Aucune image trouvée', 'filtered-gallery'); ?></h3>
                        <p><?php _e('Aucune image n\'est disponible dans cette galerie.', 'filtered-gallery'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode simple pour afficher une galerie sans filtres
     */
    public function simple_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts, 'filtered_gallery_simple');
        
        // Récupérer les images
        $images = $this->get_gallery_images($atts);
        
        // Générer le HTML
        ob_start();
        ?>
        <div class="filtered-gallery-simple">
            <?php if (!empty($images)) : ?>
                <ul class="filtered-gallery-grid columns-<?php echo intval($atts['columns']); ?>">
                    <?php foreach ($images as $image) : ?>
                        <li class="filtered-gallery-item">
                            <img src="<?php echo esc_url($image->image_url ); ?>" 
                                 alt="<?php echo esc_attr($image->alt_text ?: $image->title); ?>"
                                 data-full="<?php echo esc_url($image->image_url); ?>"
                                 loading="lazy">
                            
                            <div class="filtered-gallery-overlay">
                                <h3 class="filtered-gallery-title"><?php echo esc_html($image->title); ?></h3>
                                <?php if (!empty($image->description)) : ?>
                                    <p class="filtered-gallery-description"><?php echo esc_html($image->description); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="filtered-gallery-no-images">
                    <h3><?php _e('Aucune image trouvée', 'filtered-gallery'); ?></h3>
                    <p><?php _e('Aucune image n\'est disponible dans cette galerie.', 'filtered-gallery'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Récupérer les images de la galerie
     */
    private function get_gallery_images($args = array()) {
        global $wpdb;
        
        $table_images = $wpdb->prefix . 'filtered_gallery_images';
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        $where = array();
        $join = "LEFT JOIN $table_categories ON $table_images.category_id = $table_categories.id";
        
        if (!empty($args['category'])) {
            $where[] = $wpdb->prepare("$table_categories.slug = %s", $args['category']);
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Gérer l'ordre de tri
        $orderby = $args['orderby'];
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        switch ($orderby) {
            case 'title':
                $order_clause = "ORDER BY $table_images.title $order";
                break;
            case 'sort_order':
                $order_clause = "ORDER BY $table_images.sort_order ASC, $table_images.created_at DESC";
                break;
            default:
                $order_clause = "ORDER BY $table_images.created_at $order";
                break;
        }
        
        $limit_clause = $args['limit'] > 0 ? $wpdb->prepare("LIMIT %d", $args['limit']) : '';
        
        $sql = "SELECT $table_images.*, $table_categories.name as category_name, $table_categories.slug as category_slug 
                FROM $table_images 
                $join 
                $where_clause 
                $order_clause 
                $limit_clause";
        
        $results = $wpdb->get_results($sql);
        
        // Enrichir les résultats avec les données d'attachement WordPress
        foreach ($results as $result) {
            $attachment = get_post($result->attachment_id);
            if ($attachment) {
                $result->image_url = wp_get_attachment_image_url($result->attachment_id, 'full');
                $result->thumbnail_url = wp_get_attachment_image_url($result->attachment_id, 'full');
                $result->alt_text = get_post_meta($result->attachment_id, '_wp_attachment_image_alt', true);
            } else {
                // Si l'attachement n'existe plus, supprimer de la galerie
                $wpdb->delete($table_images, array('id' => $result->id), array('%d'));
                continue;
            }
        }
        
        return $results;
    }
    
    /**
     * Récupérer les catégories
     */
    private function get_categories() {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_categories'") != $table_categories) {
            return array();
        }
        
        return $wpdb->get_results("SELECT * FROM $table_categories ORDER BY name ASC");
    }
    
    /**
     * Générer le HTML des éléments de galerie pour AJAX
     */
    public function generate_gallery_items_html($images) {
        ob_start();
        if (!empty($images)) {
            foreach ($images as $image) {
                ?>
                <li class="filtered-gallery-item" data-category="<?php echo esc_attr($image->category_slug ?? ''); ?>">
                    <img src="<?php echo esc_url($image->image_url ); ?>" 
                         alt="<?php echo esc_attr($image->alt_text ?: $image->title); ?>"
                         data-full="<?php echo esc_url($image->image_url); ?>"
                         loading="lazy">
                    
                    <?php if (!empty($image->category_name)) : ?>
                        <div class="filtered-gallery-category"><?php echo esc_html($image->category_name); ?></div>
                    <?php endif; ?>
                    
                    <div class="filtered-gallery-overlay">
                        <h3 class="filtered-gallery-title"><?php echo esc_html($image->title); ?></h3>
                        <?php if (!empty($image->description)) : ?>
                            <p class="filtered-gallery-description"><?php echo esc_html($image->description); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
                <?php
            }
        } else {
            ?>
            <div class="filtered-gallery-no-images">
                <h3><?php _e('Aucune image trouvée', 'filtered-gallery'); ?></h3>
                <p><?php _e('Aucune image n\'est disponible dans cette catégorie.', 'filtered-gallery'); ?></p>
            </div>
            <?php
        }
        return ob_get_clean();
    }
    
    /**
     * Fonction utilitaire pour créer un shortcode programmatiquement
     */
    public function create_shortcode($args = array()) {
        $defaults = array(
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_filters' => 'true',
            'show_categories' => 'true',
            'lightbox' => 'true',
            'lazy_loading' => 'true'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $shortcode = '[filtered_gallery';
        foreach ($args as $key => $value) {
            if ($value !== '' && $value !== -1) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        
        return $shortcode;
    }
}

// Initialiser la classe shortcode
FilteredGallery_Shortcode::get_instance(); 