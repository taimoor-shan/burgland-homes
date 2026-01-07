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
            'rewrite'               => array('slug' => 'communities'),
        );

        register_post_type('bh_community', $args);
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
            'taxonomies'            => array(),
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
