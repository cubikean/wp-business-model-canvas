<?php
/**
 * Widget FilteredGallery
 *
 * @package FilteredGallery
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe du widget FilteredGallery
 */
class FilteredGallery_Widget extends WP_Widget {
    
    /**
     * Constructeur du widget
     */
    public function __construct() {
        parent::__construct(
            'filtered_gallery_widget',
            __('Filtered Gallery', 'filtered-gallery'),
            array(
                'description' => __('Affiche une galerie d\'images avec filtres par catégories.', 'filtered-gallery'),
                'classname' => 'filtered-gallery-widget'
            )
        );
    }
    
    /**
     * Affichage du widget en frontend
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        // Titre du widget
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Paramètres de la galerie
        $gallery_args = array(
            'category' => !empty($instance['category']) ? $instance['category'] : '',
            'columns' => !empty($instance['columns']) ? intval($instance['columns']) : 3,
            'limit' => !empty($instance['limit']) ? intval($instance['limit']) : 6,
            'orderby' => !empty($instance['orderby']) ? $instance['orderby'] : 'date',
            'order' => !empty($instance['order']) ? $instance['order'] : 'DESC'
        );
        
        // Afficher la galerie
        echo do_shortcode('[filtered_gallery ' . $this->build_shortcode_attributes($gallery_args) . ']');
        
        echo $args['after_widget'];
    }
    
    /**
     * Formulaire d'administration du widget
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $category = !empty($instance['category']) ? $instance['category'] : '';
        $columns = !empty($instance['columns']) ? intval($instance['columns']) : 3;
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 6;
        $orderby = !empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $order = !empty($instance['order']) ? $instance['order'] : 'DESC';
        
        // Récupérer les catégories disponibles
        $categories = $this->get_categories();
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Titre:', 'filtered-gallery'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Catégorie:', 'filtered-gallery'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>">
                <option value=""><?php _e('Toutes les catégories', 'filtered-gallery'); ?></option>
                <?php foreach ($categories as $cat) : ?>
                    <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($category, $cat->slug); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e('Nombre de colonnes:', 'filtered-gallery'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>">
                <?php for ($i = 1; $i <= 6; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($columns, $i); ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Nombre d\'images:', 'filtered-gallery'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" min="1" max="50" value="<?php echo esc_attr($limit); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Trier par:', 'filtered-gallery'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
                <option value="date" <?php selected($orderby, 'date'); ?>><?php _e('Date', 'filtered-gallery'); ?></option>
                <option value="title" <?php selected($orderby, 'title'); ?>><?php _e('Titre', 'filtered-gallery'); ?></option>
                <option value="sort_order" <?php selected($orderby, 'sort_order'); ?>><?php _e('Ordre personnalisé', 'filtered-gallery'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('order'); ?>"><?php _e('Ordre:', 'filtered-gallery'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
                <option value="DESC" <?php selected($order, 'DESC'); ?>><?php _e('Décroissant', 'filtered-gallery'); ?></option>
                <option value="ASC" <?php selected($order, 'ASC'); ?>><?php _e('Croissant', 'filtered-gallery'); ?></option>
            </select>
        </p>
        
        <?php
    }
    
    /**
     * Sauvegarde des paramètres du widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['category'] = !empty($new_instance['category']) ? sanitize_text_field($new_instance['category']) : '';
        $instance['columns'] = !empty($new_instance['columns']) ? intval($new_instance['columns']) : 3;
        $instance['limit'] = !empty($new_instance['limit']) ? intval($new_instance['limit']) : 6;
        $instance['orderby'] = !empty($new_instance['orderby']) ? sanitize_text_field($new_instance['orderby']) : 'date';
        $instance['order'] = !empty($new_instance['order']) ? sanitize_text_field($new_instance['order']) : 'DESC';
        
        return $instance;
    }
    
    /**
     * Récupérer les catégories disponibles
     */
    private function get_categories() {
        global $wpdb;
        
        $table_categories = $wpdb->prefix . 'filtered_gallery_categories';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_categories'") != $table_categories) {
            return array();
        }
        
        return $wpdb->get_results("SELECT id, name, slug FROM $table_categories ORDER BY name ASC");
    }
    
    /**
     * Construire les attributs du shortcode
     */
    private function build_shortcode_attributes($args) {
        $attributes = array();
        
        if (!empty($args['category'])) {
            $attributes[] = 'category="' . esc_attr($args['category']) . '"';
        }
        
        if (!empty($args['columns'])) {
            $attributes[] = 'columns="' . intval($args['columns']) . '"';
        }
        
        if (!empty($args['limit'])) {
            $attributes[] = 'limit="' . intval($args['limit']) . '"';
        }
        
        if (!empty($args['orderby'])) {
            $attributes[] = 'orderby="' . esc_attr($args['orderby']) . '"';
        }
        
        if (!empty($args['order'])) {
            $attributes[] = 'order="' . esc_attr($args['order']) . '"';
        }
        
        return implode(' ', $attributes);
    }
} 