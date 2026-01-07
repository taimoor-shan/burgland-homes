<?php
/**
 * Template Management
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Templates
 */
class Burgland_Homes_Templates {
    
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
        add_filter('template_include', array($this, 'load_plugin_templates'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Load plugin templates
     */
    public function load_plugin_templates($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        // Check for single templates
        if (is_singular('bh_community')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/single-community.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_singular('bh_floor_plan')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/single-floor-plan.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_singular('bh_lot')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/single-lot.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        // Check for archive templates
        if (is_post_type_archive('bh_community')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/archive-community.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('bh_floor_plan')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/archive-floor-plan.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('bh_lot')) {
            $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/archive-lot.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Enqueue assets for templates
     */
    public function enqueue_assets() {
        // Enqueue assets for communities archive page
        if (is_post_type_archive('bh_community')) {
            // Enqueue CSS
            wp_enqueue_style(
                'burgland-homes-communities-archive',
                BURGLAND_HOMES_PLUGIN_URL . 'assets/css/communities-archive.css',
                array(),
                BURGLAND_HOMES_VERSION
            );
            
            // Enqueue Bootstrap Icons (if not already loaded by theme)
            wp_enqueue_style(
                'bootstrap-icons',
                'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
                array(),
                '1.11.0'
            );
            
            // Enqueue JavaScript
            wp_enqueue_script(
                'burgland-homes-communities-archive',
                BURGLAND_HOMES_PLUGIN_URL . 'assets/js/communities-archive.js',
                array('jquery'),
                BURGLAND_HOMES_VERSION,
                true
            );
            
            // Localize script with AJAX URL and nonce
            wp_localize_script(
                'burgland-homes-communities-archive',
                'burglandHomesArchive',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('burgland_communities_filter'),
                )
            );
            
            // Enqueue Google Maps API
            $google_maps_api_key = apply_filters('burgland_homes_google_maps_api_key', '');
            
            if (!empty($google_maps_api_key)) {
                wp_enqueue_script(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&callback=initCommunitiesMap',
                    array('burgland-homes-communities-archive'),
                    null,
                    true
                );
            }
        }
    }
}
