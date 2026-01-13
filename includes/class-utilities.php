<?php
/**
 * Utility Functions
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Utilities
 */
class Burgland_Homes_Utilities {
    
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
        // Hook into activation to sync communities with taxonomy
        add_action('init', array($this, 'maybe_sync_communities_to_taxonomy'));
    }
    
    /**
     * Sync existing communities to the floor plan communities taxonomy
     */
    public function maybe_sync_communities_to_taxonomy() {
        // Run this only once after plugin update/install
        $synced = get_option('bh_floor_plan_comms_synced', false);
        
        if (!$synced) {
            $this->sync_existing_communities();
            update_option('bh_floor_plan_comms_synced', true);
        }
    }
    
    /**
     * Sync existing communities to the floor plan communities taxonomy
     */
    public function sync_existing_communities() {
        // Get all existing communities
        $communities = get_posts(array(
            'post_type' => 'bh_community',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($communities as $community) {
            $term_name = $community->post_title;
            $term_slug = sanitize_title($community->post_name);
            $term_description = $community->post_content;
            
            // Check if a term with this slug already exists
            $term_exists = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
            
            if (!$term_exists) {
                // Check if term exists by name only (in case of slug conflict)
                $term_exists_by_name = get_term_by('name', $term_name, 'bh_floor_plan_community');
                
                if (!$term_exists_by_name) {
                    // Create the term if it doesn't exist
                    wp_insert_term(
                        $term_name,
                        'bh_floor_plan_community',
                        array(
                            'description' => $term_description,
                            'slug' => $term_slug,
                        )
                    );
                }
            }
        }
    }
}