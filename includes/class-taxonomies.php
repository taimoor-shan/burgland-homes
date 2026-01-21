<?php
/**
 * Custom Taxonomies Registration
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Taxonomies
 */
class Burgland_Homes_Taxonomies {
    
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
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    /**
     * Register all custom taxonomies
     */
    public function register_taxonomies() {
        $this->register_community_status_taxonomy();
    }

    /**
     * Register Community Status Taxonomy
     */
    private function register_community_status_taxonomy() {
        $labels = array(
            'name'                       => _x('Community Status', 'Taxonomy General Name', 'burgland-homes'),
            'singular_name'              => _x('Community Status', 'Taxonomy Singular Name', 'burgland-homes'),
            'menu_name'                  => __('Community Status', 'burgland-homes'),
            'all_items'                  => __('All Statuses', 'burgland-homes'),
            'parent_item'                => __('Parent Status', 'burgland-homes'),
            'parent_item_colon'          => __('Parent Status:', 'burgland-homes'),
            'new_item_name'              => __('New Status Name', 'burgland-homes'),
            'add_new_item'               => __('Add New Status', 'burgland-homes'),
            'edit_item'                  => __('Edit Status', 'burgland-homes'),
            'update_item'                => __('Update Status', 'burgland-homes'),
            'view_item'                  => __('View Status', 'burgland-homes'),
            'separate_items_with_commas' => __('Separate statuses with commas', 'burgland-homes'),
            'add_or_remove_items'        => __('Add or remove statuses', 'burgland-homes'),
            'choose_from_most_used'      => __('Choose from the most used', 'burgland-homes'),
            'popular_items'              => __('Popular Statuses', 'burgland-homes'),
            'search_items'               => __('Search Statuses', 'burgland-homes'),
            'not_found'                  => __('Not Found', 'burgland-homes'),
            'no_terms'                   => __('No statuses', 'burgland-homes'),
            'items_list'                 => __('Statuses list', 'burgland-homes'),
            'items_list_navigation'      => __('Statuses list navigation', 'burgland-homes'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'community-status'),
        );

        register_taxonomy('bh_community_status', array('bh_community'), $args);
    }
}
