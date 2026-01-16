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
        
        // Handle asset enqueuing for featured communities (triggered by template or manual call)
        add_action('burgland_homes_enqueue_featured_communities_assets', array($this, 'enqueue_featured_communities_assets'));
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
        // Define relevant post types
        $post_types = array('bh_community', 'bh_floor_plan', 'bh_lot');
        
        // Check if we're on a plugin-related page
        $is_plugin_archive = is_post_type_archive($post_types);
        $is_plugin_single = is_singular($post_types);
        
        if ($is_plugin_archive || $is_plugin_single) {
            // 1. Enqueue Vendor Assets (Swiper, GLightbox) - ONLY FOR SINGLE PAGES
            if ($is_plugin_single) {
                $this->enqueue_vendor_assets();
            }

            // 2. Enqueue Base Assets (CSS, JS, Icons)
            $this->enqueue_base_assets($is_plugin_single);
            
            // 3. Enqueue Google Maps
            $this->enqueue_google_maps();
            
            // 4. Archive-specific localization for filtering
            if ($is_plugin_archive) {
                $this->localize_archive_data();
            }
        }
    }

    /**
     * Enqueue vendor assets (Swiper, GLightbox)
     */
    private function enqueue_vendor_assets() {
        // GLightbox
        wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', array(), '3.2.0');
        wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', array(), '3.2.0', true);

        // Swiper
        wp_enqueue_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css', array(), '11.0.0');
        wp_enqueue_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', array(), '11.0.0', true);
    }

    /**
     * Enqueue base plugin assets
     */
    private function enqueue_base_assets($include_vendors = false) {
        $css_url = BURGLAND_HOMES_PLUGIN_URL . 'assets/css/';
        $version = BURGLAND_HOMES_VERSION;

        // 1. Global Styles (Tokens, Utilities)
        wp_enqueue_style('burgland-homes-plugin', $css_url . 'plugin.css', array(), $version);

        // 2. Component Styles
        wp_enqueue_style('bh-badges-buttons', $css_url . 'components/badges-buttons.css', array('burgland-homes-plugin'), $version);
        wp_enqueue_style('bh-card', $css_url . 'components/card.css', array('burgland-homes-plugin'), $version);
        wp_enqueue_style('bh-filters', $css_url . 'components/filters.css', array('burgland-homes-plugin'), $version);
        wp_enqueue_style('bh-map', $css_url . 'components/map.css', array('burgland-homes-plugin'), $version);
        wp_enqueue_style('bh-slider', $css_url . 'components/slider.css', array('burgland-homes-plugin'), $version);

        wp_enqueue_style(
            'bootstrap-icons',
            'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
            array(),
            '1.11.0'
        );

        // Enqueue plugin JS with dynamic dependencies
        $deps = array('jquery');
        if ($include_vendors) {
            $deps[] = 'swiper';
            $deps[] = 'glightbox';
        }

        wp_enqueue_script(
            'burgland-homes-plugin',
            BURGLAND_HOMES_PLUGIN_URL . 'assets/js/plugin.js',
            $deps,
            BURGLAND_HOMES_VERSION,
            true
        );
    }

    /**
     * Localize archive data for AJAX filtering
     */
    private function localize_archive_data() {
        // Localize script with AJAX URL and nonce
        wp_localize_script(
            'burgland-homes-plugin',
            'burglandHomesArchive',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('burgland_communities_filter'),
            )
        );
    }

    /**
     * Enqueue assets for featured communities component
     */
    public function enqueue_featured_communities_assets() {
        $this->enqueue_base_assets();
        $this->enqueue_google_maps();
    }

    /**
     * Helper to enqueue Google Maps API with a unified callback system
     */
    private function enqueue_google_maps() {
        $google_maps_api_key = apply_filters('burgland_homes_google_maps_api_key', '');
        
        if (empty($google_maps_api_key)) {
            return;
        }

        $handle = 'google-maps-api';
        $url = 'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&callback=burgland_homes_init_maps';
        
        if (!wp_script_is($handle, 'enqueued')) {
            wp_enqueue_script(
                $handle,
                $url,
                array('jquery'),
                null,
                true
            );

            // Add the inline script logic for the map queue
            wp_add_inline_script($handle, '
                window.burglandHomesMapsQueue = window.burglandHomesMapsQueue || [];
                window.burgland_homes_init_maps = function() {
                    window.burglandHomesMapLoaded = true;
                    
                    // 1. Trigger Archive Map if callback exists
                    if (typeof window.initCommunitiesMap === "function") {
                        window.initCommunitiesMap();
                    }
                    
                    // 2. Flush Generic Queue (used by Featured Communities and others)
                    if (window.burglandHomesMapsQueue && window.burglandHomesMapsQueue.length > 0) {
                        window.burglandHomesMapsQueue.forEach(function(initFn) {
                            if (typeof initFn === "function") {
                                initFn();
                            }
                        });
                        window.burglandHomesMapsQueue = []; 
                    }
                };
            ', 'before');
        }
    }
}
