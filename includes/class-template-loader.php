<?php
/**
 * Template Loader with Theme Override Support
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Template_Loader
 */
class Burgland_Homes_Template_Loader {
    
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
    private function __construct() {}
    
    /**
     * Locate template with theme override support
     * 
     * Template hierarchy:
     * 1. theme/burgland-homes/{template-name}.php
     * 2. plugin/templates/{template-name}.php
     * 
     * @param string $template_name
     * @param array $args Data to pass to template
     * @param string $template_path Optional subfolder in theme
     * @return string Template path or empty string
     */
    public function locate_template($template_name, $args = array(), $template_path = '') {
        if (!$template_path) {
            $template_path = 'burgland-homes/';
        }
        
        // Check theme override first
        $theme_template = locate_template(array(
            trailingslashit($template_path) . $template_name,
            $template_name,
        ));
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Fallback to plugin template
        $plugin_template = BURGLAND_HOMES_PLUGIN_DIR . 'templates/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return '';
    }
    
    /**
     * Load template with data
     * 
     * @param string $template_name
     * @param array $args Data to pass to template
     * @param bool $return Whether to return output or echo
     * @return string|void
     */
    public function load_template($template_name, $args = array(), $return = false) {
        $template_path = $this->locate_template($template_name);
        
        if (!$template_path) {
            if (WP_DEBUG) {
                error_log(sprintf('Burgland Homes: Template %s not found', $template_name));
            }
            return '';
        }
        
        // Make args available as variables
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        // Apply filter for args modification
        $args = apply_filters('burgland_homes_template_args', $args, $template_name);
        $template_path = apply_filters('burgland_homes_template_path', $template_path, $template_name, $args);
        
        if ($return) {
            ob_start();
        }
        
        do_action('burgland_homes_before_template', $template_name, $args);
        
        include $template_path;
        
        do_action('burgland_homes_after_template', $template_name, $args);
        
        if ($return) {
            return ob_get_clean();
        }
    }
    
    /**
     * Render featured communities section
     * 
     * @param array $args
     * @return void
     */
    public function render_featured_communities($args = array()) {
        $data_provider = Burgland_Homes_Data_Provider::get_instance();
        $communities = $data_provider->get_featured_communities($args);
        
        $template_args = array(
            'communities' => $communities,
            'map_id' => 'featured-communities-map-' . uniqid(),
            'google_maps_api_key' => apply_filters('burgland_homes_google_maps_api_key', ''),
        );
        
        $this->load_template('featured-communities.php', $template_args);
    }
    
    /**
     * Render a single entity component
     * 
     * @param string $component
     * @param array $args
     * @return void
     */
    public function render_single_component($component, $args = array()) {
        $this->load_template('single/' . $component . '.php', $args);
    }

    /**
     * Render generic card for any post type
     * 
     * @param int $post_id
     * @return void
     */
    public function render_card($post_id) {
        $data_provider = Burgland_Homes_Data_Provider::get_instance();
        $card_data = $data_provider->get_card_data($post_id);
        
        $this->load_template('components/card.php', array('data' => $card_data));
    }

    /**
     * Render community card
     * 
     * @param int $community_id
     * @return void
     */
    public function render_community_card($community_id) {
        $this->render_card($community_id);
    }
    
    /**
     * Render lot card
     * 
     * @param int $lot_id
     * @return void
     */
    public function render_lot_card($lot_id) {
        $this->render_card($lot_id);
    }
    
    /**
     * Render floor plan card
     * 
     * @param int $floor_plan_id
     * @return void
     */
    public function render_floor_plan_card($floor_plan_id) {
        $this->render_card($floor_plan_id);
    }
}
