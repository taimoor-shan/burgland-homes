<?php
/**
 * Plugin Name: Burgland Homes
 * Plugin URI: https://burglandhomes.com
 * Description: Comprehensive real estate management plugin for new development communities, floor plans, and lots/homes.
 * Version: 1.0.0
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
define('BURGLAND_HOMES_VERSION', '1.0.0');
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
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once BURGLAND_HOMES_PLUGIN_DIR . 'includes/class-community-card-component.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('after_setup_theme', array($this, 'init'), 5);
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
        Burgland_Homes_Shortcodes::get_instance();
        Burgland_Homes_Community_Card_Component::get_instance();
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
}

/**
 * Initialize the plugin
 */
function burgland_homes() {
    return Burgland_Homes_Plugin::get_instance();
}

// Start the plugin
burgland_homes();
