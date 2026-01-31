<?php
/**
 * Plugin Name: Burgland Homes
 * Plugin URI: https://burglandhomes.com
 * Description: Comprehensive real estate management plugin for new development communities, floor plans, and lots/homes.
 * Version: 1.0.3
 * Author: Burgland Homes
 * Author URI: https://burglandhomes.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: burgland-homes
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('BURGLAND_HOMES_VERSION', '1.0.3');
define('BURGLAND_HOMES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BURGLAND_HOMES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BURGLAND_HOMES_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Burgland Homes Plugin Class
 */
class Burgland_Homes_Plugin {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance of class
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
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core functionality
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-acf-fields.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-admin.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-relationships.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-templates.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-gallery.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-communities-filter.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-utilities.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-geocoding-service.php';
        
        // NEW: Data layer and template system
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-data-provider.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-template-loader.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/functions.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('after_setup_theme', array($this, 'init'), 5);
        
        // Geocoding hooks
        add_action('save_post_bh_community', array($this, 'geocode_on_save'), 20, 3);
        add_action('save_post_bh_lot', array($this, 'geocode_on_save'), 20, 3);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('burgland-homes', false, dirname(BURGLAND_HOMES_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize core classes
        Burgland_Homes_Post_Types::get_instance();
        Burgland_Homes_Taxonomies::get_instance();
        Burgland_Homes_ACF_Fields::get_instance();
        Burgland_Homes_Admin::get_instance();
        Burgland_Homes_Relationships::get_instance();
        Burgland_Homes_Templates::get_instance();
        Burgland_Homes_Gallery::get_instance();
        Burgland_Homes_Communities_Filter::get_instance();
        Burgland_Homes_Utilities::get_instance();
        
        // NEW: Initialize new data and template system
        Burgland_Homes_Data_Provider::get_instance();
        Burgland_Homes_Template_Loader::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register post types and taxonomies
        Burgland_Homes_Post_Types::get_instance()->register_post_types();
        Burgland_Homes_Taxonomies::get_instance()->register_taxonomies();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        set_transient('burgland_homes_activated', true, 60);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Geocode address on post save
     * 
     * @param int $post_id Post ID
     * @param WP_Post $post Post object
     * @param bool $update Whether this is an update or new post
     */
    public function geocode_on_save($post_id, $post, $update) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if not published
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Get geocoding service
        $geocoding_service = Burgland_Homes_Geocoding_Service::get_instance();
        
        // Geocode based on post type
        if ($post->post_type === 'bh_community') {
            $geocoding_service->geocode_community($post_id);
        } elseif ($post->post_type === 'bh_lot') {
            $geocoding_service->geocode_lot($post_id);
        }
    }
}

/**
 * Initialize the plugin
 */
function burgland_homes() {
    return Burgland_Homes_Plugin::get_instance();
}

// Start the plugin
burgland_homes();
