<?php
/**
 * Gallery Management
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Gallery
 * 
 * Handles image gallery functionality for Communities, Floor Plans, and Lots
 */
class Burgland_Homes_Gallery {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_gallery_assets'));
        add_action('add_meta_boxes', array($this, 'add_gallery_metabox'));
        add_action('save_post', array($this, 'save_gallery_meta'));
    }
    
    /**
     * Enqueue gallery assets
     */
    public function enqueue_gallery_assets($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue jQuery UI Sortable (for drag-and-drop)
        wp_enqueue_script('jquery-ui-sortable');
        
        // Enqueue custom gallery scripts
        wp_enqueue_script(
            'burgland-homes-gallery',
            BURGLAND_HOMES_PLUGIN_URL . 'assets/js/gallery-metabox.js',
            array('jquery', 'jquery-ui-sortable'),
            BURGLAND_HOMES_VERSION,
            true
        );
        
        // Enqueue custom gallery styles
        wp_enqueue_style(
            'burgland-homes-gallery',
            BURGLAND_HOMES_PLUGIN_URL . 'assets/css/gallery-metabox.css',
            array(),
            BURGLAND_HOMES_VERSION
        );
    }
    
    /**
     * Add gallery meta box to custom post types
     */
    public function add_gallery_metabox($post_type) {
        $types = array('bh_community', 'bh_floor_plan', 'bh_lot');
        
        if (in_array($post_type, $types)) {
            add_meta_box(
                'bh-gallery-metabox',
                __('Image Gallery', 'burgland-homes'),
                array($this, 'render_gallery_metabox'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render gallery meta box
     */
    public function render_gallery_metabox($post) {
        wp_nonce_field(basename(__FILE__), 'bh_gallery_meta_nonce');
        $ids = get_post_meta($post->ID, 'bh_gallery_ids', true);
        
        ?>
        <div class="bh-gallery-metabox-wrapper">
            <p class="description">
                <?php _e('Add, rearrange, and manage images for this listing. You can drag and drop to reorder images.', 'burgland-homes'); ?>
            </p>
            
            <div class="bh-gallery-actions" style="margin: 15px 0;">
                <a class="bh-gallery-add button button-primary" href="#" 
                   data-uploader-title="<?php esc_attr_e('Add Images to Gallery', 'burgland-homes'); ?>" 
                   data-uploader-button-text="<?php esc_attr_e('Add Images', 'burgland-homes'); ?>">
                    <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                    <?php _e('Add Images', 'burgland-homes'); ?>
                </a>
            </div>

            <ul id="bh-gallery-metabox-list" class="bh-gallery-list">
                <?php 
                if ($ids && is_array($ids)) {
                    foreach ($ids as $key => $value) {
                        $image = wp_get_attachment_image_src($value, 'thumbnail');
                        $full_image = wp_get_attachment_image_src($value, 'full');
                        if ($image) {
                            ?>
                            <li class="bh-gallery-item">
                                <input type="hidden" name="bh_gallery_ids[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($value); ?>">
                                <div class="bh-gallery-image-wrapper">
                                    <img class="bh-image-preview" src="<?php echo esc_url($image[0]); ?>" alt="">
                                    <div class="bh-gallery-item-actions">
                                        <a class="bh-change-image button button-small" href="#" 
                                           data-uploader-title="<?php esc_attr_e('Change Image', 'burgland-homes'); ?>" 
                                           data-uploader-button-text="<?php esc_attr_e('Select Image', 'burgland-homes'); ?>"
                                           title="<?php esc_attr_e('Change image', 'burgland-homes'); ?>">
                                            <span class="dashicons dashicons-update"></span>
                                        </a>
                                        <a class="bh-remove-image button button-small" href="#"
                                           title="<?php esc_attr_e('Remove image', 'burgland-homes'); ?>">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                        <a class="bh-preview-image button button-small" href="<?php echo esc_url($full_image[0]); ?>" target="_blank"
                                           title="<?php esc_attr_e('Preview image', 'burgland-homes'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                    </div>
                                    <div class="bh-gallery-drag-handle" title="<?php esc_attr_e('Drag to reorder', 'burgland-homes'); ?>">
                                        <span class="dashicons dashicons-move"></span>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                    }
                }
                ?>
            </ul>
            
            <?php if (empty($ids)): ?>
            <div class="bh-gallery-empty-state" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-format-gallery" style="font-size: 48px; color: #ccc; height: 48px; width: 48px;"></span>
                <p style="color: #666; margin: 10px 0 0 0;">
                    <?php _e('No images in gallery yet. Click "Add Images" to get started.', 'burgland-homes'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .bh-gallery-empty-state {
                display: none;
            }
            #bh-gallery-metabox-list:empty + .bh-gallery-empty-state {
                display: block;
            }
        </style>
        <?php
    }
    
    /**
     * Save gallery meta
     */
    public function save_gallery_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['bh_gallery_meta_nonce']) || !wp_verify_nonce($_POST['bh_gallery_meta_nonce'], basename(__FILE__))) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save or delete gallery IDs
        if (isset($_POST['bh_gallery_ids']) && is_array($_POST['bh_gallery_ids'])) {
            $gallery_ids = array_map('intval', $_POST['bh_gallery_ids']);
            update_post_meta($post_id, 'bh_gallery_ids', $gallery_ids);
        } else {
            delete_post_meta($post_id, 'bh_gallery_ids');
        }
    }
    
    /**
     * Get gallery images for a post
     * 
     * @param int $post_id Post ID
     * @param string $size Image size
     * @return array Array of image data
     */
    public static function get_gallery_images($post_id, $size = 'full') {
        $ids = get_post_meta($post_id, 'bh_gallery_ids', true);
        $images = array();
        
        if ($ids && is_array($ids)) {
            foreach ($ids as $id) {
                $image = wp_get_attachment_image_src($id, $size);
                if ($image) {
                    $images[] = array(
                        'id' => $id,
                        'url' => $image[0],
                        'width' => $image[1],
                        'height' => $image[2],
                        'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
                        'title' => get_the_title($id),
                        'caption' => wp_get_attachment_caption($id),
                    );
                }
            }
        }
        
        return $images;
    }
}
