<?php
/**
 * Custom Post Types Registration
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Post_Types
 */
class Burgland_Homes_Post_Types {
    
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
        // Register post types immediately when class is instantiated
        // The parent plugin calls this at the right time
        $this->register_post_types();
        
        // Hook into later init action to register taxonomies
        add_action('init', array($this, 'register_post_type_taxonomies'), 11);
        
        // Add cleanup hooks for post deletion
        add_action('before_delete_post', array($this, 'cleanup_post_data'), 10);
        add_action('delete_post', array($this, 'cleanup_post_data_permanent'), 10);
        add_action('wp_trash_post', array($this, 'log_post_trash'), 10);
    }
    
    /**
     * Register taxonomies specific to post types
     */
    public function register_post_type_taxonomies() {
        // Add the floor plan communities taxonomy to the floor plan post type
        register_taxonomy_for_object_type('bh_floor_plan_community', 'bh_floor_plan');
        
        // Hook into floor plan saving to handle taxonomy terms
        add_action('save_post_bh_floor_plan', array($this, 'save_floor_plan_communities'), 10, 3);
    }
    
    /**
     * Save floor plan communities taxonomy terms
     */
    public function save_floor_plan_communities($post_id, $post, $update) {
        // Check if not an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Only process if our nonce exists (this is from our custom meta box)
        // If nonce doesn't exist, it means this save is from another source (like ACF)
        // and we should not interfere
        if (!isset($_POST['bh_floor_plan_community_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['bh_floor_plan_community_nonce'], 'bh_floor_plan_community_nonce')) {
            return;
        }
        
        // Handle the taxonomy terms
        if (isset($_POST['bh_floor_plan_community'])) {
            $selected_terms = array_map('intval', $_POST['bh_floor_plan_community']);
            wp_set_object_terms($post_id, $selected_terms, 'bh_floor_plan_community');
        } else {
            // If no terms selected, clear all terms
            wp_set_object_terms($post_id, array(), 'bh_floor_plan_community');
        }
    }
    
    /**
     * Log post trash action
     */
    public function log_post_trash($post_id) {
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            error_log(sprintf(
                'Burgland Homes: Post #%d (%s) type %s moved to trash',
                $post_id,
                $post->post_title,
                $post->post_type
            ));
        }
    }
    
    /**
     * Cleanup post data before deletion (fires on trash and permanent delete)
     */
    public function cleanup_post_data($post_id) {
        $post = get_post($post_id);
        
        if (!$post || !in_array($post->post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            return;
        }
        
        error_log(sprintf(
            'Burgland Homes: Cleaning up post #%d (%s) type %s before deletion',
            $post_id,
            $post->post_title,
            $post->post_type
        ));
        
        // Get all post meta before deletion for logging
        $all_meta = get_post_meta($post_id);
        $meta_count = count($all_meta);
        
        error_log(sprintf(
            'Burgland Homes: Post #%d has %d meta entries to clean',
            $post_id,
            $meta_count
        ));
    }
    
    /**
     * Cleanup post data after permanent deletion
     * This ensures slug is freed up and all metadata is removed
     */
    public function cleanup_post_data_permanent($post_id) {
        $post = get_post($post_id);
        
        // Post might already be deleted, check the global deleted post
        if (!$post) {
            global $wpdb;
            // Try to get post from database one last time
            $post = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $wpdb->posts WHERE ID = %d",
                $post_id
            ));
        }
        
        if (!$post) {
            return;
        }
        
        // Only handle our custom post types
        if (!in_array($post->post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            return;
        }
        
        global $wpdb;
        
        error_log(sprintf(
            'Burgland Homes: Running permanent cleanup for post #%d (%s) type %s',
            $post_id,
            is_object($post) ? $post->post_title : 'Unknown',
            $post->post_type
        ));
        
        // Force delete all post meta to ensure clean database
        $deleted_meta = $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->postmeta WHERE post_id = %d",
            $post_id
        ));
        
        error_log(sprintf(
            'Burgland Homes: Deleted %d meta entries for post #%d',
            $deleted_meta,
            $post_id
        ));
        
        // Delete all term relationships
        $deleted_terms = $wpdb->query($wpdb->prepare(
            "DELETE FROM $wpdb->term_relationships WHERE object_id = %d",
            $post_id
        ));
        
        error_log(sprintf(
            'Burgland Homes: Deleted %d term relationships for post #%d',
            $deleted_terms,
            $post_id
        ));
        
        // Clean up post meta cache
        clean_post_cache($post_id);
        
        error_log(sprintf(
            'Burgland Homes: Completed cleanup for post #%d - slug "%s" should now be available',
            $post_id,
            is_object($post) ? $post->post_name : 'unknown'
        ));
    }
    
    /**
     * Register all custom post types
     */
    public function register_post_types() {
        $this->register_community_cpt();
        $this->register_floor_plan_cpt();
        $this->register_lot_cpt();
    }
    
    /**
     * Register Community Custom Post Type
     */
    private function register_community_cpt() {
        $labels = array(
            'name'                  => _x('Communities', 'Post Type General Name', 'burgland-homes'),
            'singular_name'         => _x('Community', 'Post Type Singular Name', 'burgland-homes'),
            'menu_name'             => __('Communities', 'burgland-homes'),
            'name_admin_bar'        => __('Community', 'burgland-homes'),
            'archives'              => __('Community Archives', 'burgland-homes'),
            'attributes'            => __('Community Attributes', 'burgland-homes'),
            'parent_item_colon'     => __('Parent Community:', 'burgland-homes'),
            'all_items'             => __('All Communities', 'burgland-homes'),
            'add_new_item'          => __('Add New Community', 'burgland-homes'),
            'add_new'               => __('Add New', 'burgland-homes'),
            'new_item'              => __('New Community', 'burgland-homes'),
            'edit_item'             => __('Edit Community', 'burgland-homes'),
            'update_item'           => __('Update Community', 'burgland-homes'),
            'view_item'             => __('View Community', 'burgland-homes'),
            'view_items'            => __('View Communities', 'burgland-homes'),
            'search_items'          => __('Search Community', 'burgland-homes'),
            'not_found'             => __('Not found', 'burgland-homes'),
            'not_found_in_trash'    => __('Not found in Trash', 'burgland-homes'),
            'featured_image'        => __('Community Image', 'burgland-homes'),
            'set_featured_image'    => __('Set community image', 'burgland-homes'),
            'remove_featured_image' => __('Remove community image', 'burgland-homes'),
            'use_featured_image'    => __('Use as community image', 'burgland-homes'),
            'insert_into_item'      => __('Insert into community', 'burgland-homes'),
            'uploaded_to_this_item' => __('Uploaded to this community', 'burgland-homes'),
            'items_list'            => __('Communities list', 'burgland-homes'),
            'items_list_navigation' => __('Communities list navigation', 'burgland-homes'),
            'filter_items_list'     => __('Filter communities list', 'burgland-homes'),
        );

        $args = array(
            'label'                 => __('Community', 'burgland-homes'),
            'description'           => __('New development communities', 'burgland-homes'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'burgland-homes',
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-admin-multisite',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array(
                'slug' => 'communities',
                'with_front' => false,
            ),
            // CRITICAL: Ensure deleted posts don't block slug reuse
            'delete_with_user'      => false,
        );

        register_post_type('bh_community', $args);
        
        error_log('Burgland Homes: Registered bh_community post type');
    }
    
    /**
     * Register Floor Plan Custom Post Type
     */
    private function register_floor_plan_cpt() {
        $labels = array(
            'name'                  => _x('Floor Plans', 'Post Type General Name', 'burgland-homes'),
            'singular_name'         => _x('Floor Plan', 'Post Type Singular Name', 'burgland-homes'),
            'menu_name'             => __('Floor Plans', 'burgland-homes'),
            'name_admin_bar'        => __('Floor Plan', 'burgland-homes'),
            'archives'              => __('Floor Plan Archives', 'burgland-homes'),
            'attributes'            => __('Floor Plan Attributes', 'burgland-homes'),
            'parent_item_colon'     => __('Parent Floor Plan:', 'burgland-homes'),
            'all_items'             => __('All Floor Plans', 'burgland-homes'),
            'add_new_item'          => __('Add New Floor Plan', 'burgland-homes'),
            'add_new'               => __('Add New', 'burgland-homes'),
            'new_item'              => __('New Floor Plan', 'burgland-homes'),
            'edit_item'             => __('Edit Floor Plan', 'burgland-homes'),
            'update_item'           => __('Update Floor Plan', 'burgland-homes'),
            'view_item'             => __('View Floor Plan', 'burgland-homes'),
            'view_items'            => __('View Floor Plans', 'burgland-homes'),
            'search_items'          => __('Search Floor Plan', 'burgland-homes'),
            'not_found'             => __('Not found', 'burgland-homes'),
            'not_found_in_trash'    => __('Not found in Trash', 'burgland-homes'),
            'featured_image'        => __('Floor Plan Image', 'burgland-homes'),
            'set_featured_image'    => __('Set floor plan image', 'burgland-homes'),
            'remove_featured_image' => __('Remove floor plan image', 'burgland-homes'),
            'use_featured_image'    => __('Use as floor plan image', 'burgland-homes'),
            'insert_into_item'      => __('Insert into floor plan', 'burgland-homes'),
            'uploaded_to_this_item' => __('Uploaded to this floor plan', 'burgland-homes'),
            'items_list'            => __('Floor Plans list', 'burgland-homes'),
            'items_list_navigation' => __('Floor Plans list navigation', 'burgland-homes'),
            'filter_items_list'     => __('Filter floor plans list', 'burgland-homes'),
        );

        $args = array(
            'label'                 => __('Floor Plan', 'burgland-homes'),
            'description'           => __('Floor plans for properties', 'burgland-homes'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'revisions'),
            'taxonomies'            => array('bh_floor_plan_community'), // Add the new taxonomy
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'burgland-homes',
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-layout',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'floor-plans'),
        );

        register_post_type('bh_floor_plan', $args);
    }
    
    /**
     * Register Lot/Home Custom Post Type
     */
    private function register_lot_cpt() {
        $labels = array(
            'name'                  => _x('Lots & Homes', 'Post Type General Name', 'burgland-homes'),
            'singular_name'         => _x('Lot/Home', 'Post Type Singular Name', 'burgland-homes'),
            'menu_name'             => __('Lots & Homes', 'burgland-homes'),
            'name_admin_bar'        => __('Lot/Home', 'burgland-homes'),
            'archives'              => __('Lot/Home Archives', 'burgland-homes'),
            'attributes'            => __('Lot/Home Attributes', 'burgland-homes'),
            'parent_item_colon'     => __('Parent Lot/Home:', 'burgland-homes'),
            'all_items'             => __('All Lots & Homes', 'burgland-homes'),
            'add_new_item'          => __('Add New Lot/Home', 'burgland-homes'),
            'add_new'               => __('Add New', 'burgland-homes'),
            'new_item'              => __('New Lot/Home', 'burgland-homes'),
            'edit_item'             => __('Edit Lot/Home', 'burgland-homes'),
            'update_item'           => __('Update Lot/Home', 'burgland-homes'),
            'view_item'             => __('View Lot/Home', 'burgland-homes'),
            'view_items'            => __('View Lots & Homes', 'burgland-homes'),
            'search_items'          => __('Search Lot/Home', 'burgland-homes'),
            'not_found'             => __('Not found', 'burgland-homes'),
            'not_found_in_trash'    => __('Not found in Trash', 'burgland-homes'),
            'featured_image'        => __('Lot/Home Image', 'burgland-homes'),
            'set_featured_image'    => __('Set lot/home image', 'burgland-homes'),
            'remove_featured_image' => __('Remove lot/home image', 'burgland-homes'),
            'use_featured_image'    => __('Use as lot/home image', 'burgland-homes'),
            'insert_into_item'      => __('Insert into lot/home', 'burgland-homes'),
            'uploaded_to_this_item' => __('Uploaded to this lot/home', 'burgland-homes'),
            'items_list'            => __('Lots & Homes list', 'burgland-homes'),
            'items_list_navigation' => __('Lots & Homes list navigation', 'burgland-homes'),
            'filter_items_list'     => __('Filter lots & homes list', 'burgland-homes'),
        );

        $args = array(
            'label'                 => __('Lot/Home', 'burgland-homes'),
            'description'           => __('Available lots and homes for sale', 'burgland-homes'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'revisions'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'burgland-homes',
            'menu_position'         => 7,
            'menu_icon'             => 'dashicons-location',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rewrite'               => array('slug' => 'lots'),
        );

        register_post_type('bh_lot', $args);
    }
}