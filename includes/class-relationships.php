<?php
/**
 * Relationships Management
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Relationships
 */
class Burgland_Homes_Relationships {
    
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
        // Relationship meta boxes removed - they were causing ACF field loading conflicts
        // Related posts can be viewed through the Data Provider API or custom admin columns
    }
}
