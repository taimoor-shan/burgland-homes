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
        // REMOVED: Automatic sync on init causes crashes and data loss on large sites
        // add_action('init', array($this, 'maybe_sync_communities_to_taxonomy'));
    }
    
    /**
     * Get min/max values from associated floor plans for a community
     * 
     * @param int $community_id The community post ID
     * @return array Array containing min/max values for bedrooms, bathrooms, garage, square feet, and price
     */
    public function get_floor_plan_ranges($community_id) {
        // Get the community post
        $community_post = get_post($community_id);
        if (!$community_post) {
            return array(
                'bedrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
                'bathrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
                'garage' => array('min' => null, 'max' => null, 'formatted' => ''),
                'square_feet' => array('min' => null, 'max' => null, 'formatted' => ''),
                'price' => array('min' => null, 'max' => null, 'formatted' => ''),
                'count' => 0
            );
        }
        
        // Query for floor plans associated with this community via ACF relationship field
        $query_args = array(
            'post_type' => 'bh_floor_plan',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'floor_plans_communities',
                    'value' => '"' . $community_id . '"',
                    'compare' => 'LIKE',
                ),
            ),
        );
        
        
        // Use optimized query fetching only IDs to prevent memory exhaustion
        $query_args['fields'] = 'ids';
        $ids_query = new WP_Query($query_args);
        
        $ranges = array(
            'bedrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'bathrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'garage' => array('min' => null, 'max' => null, 'formatted' => ''),
            'square_feet' => array('min' => null, 'max' => null, 'formatted' => ''),
            'price' => array('min' => null, 'max' => null, 'formatted' => ''),
            'count' => 0
        );
        
        if (!$ids_query->have_posts()) {
            return $ranges;
        }
        
        // Check for cached ranges first
        $cached_ranges = get_post_meta($community_id, '_bh_floor_plan_ranges', true);
        if ($cached_ranges && is_array($cached_ranges) && !empty($cached_ranges)) {
            return $cached_ranges;
        }

        // Initialize empty ranges
        $ranges = array(
            'bedrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'bathrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'garage' => array('min' => null, 'max' => null, 'formatted' => ''),
            'square_feet' => array('min' => null, 'max' => null, 'formatted' => ''),
            'price' => array('min' => null, 'max' => null, 'formatted' => ''),
            'count' => 0
        );

        $ranges['count'] = $ids_query->found_posts;
        
        // Use direct SQL for aggregations to avoid loading objects
        global $wpdb;
        $post_ids = $ids_query->posts;
        if (empty($post_ids)) return $ranges;
        
        $ids_placeholder = implode(',', array_map('intval', $post_ids));
        
        // Helper to get min/max for a meta key
        $get_min_max = function($meta_key, $is_numeric = true) use ($wpdb, $ids_placeholder) {
            // Complex SQL to handle numeric casting safely
            $sql = "
                SELECT 
                    MIN(CAST(meta_value AS DECIMAL(10,2))) as min_val, 
                    MAX(CAST(meta_value AS DECIMAL(10,2))) as max_val 
                FROM {$wpdb->postmeta} 
                WHERE post_id IN ($ids_placeholder) 
                AND meta_key = %s 
                AND meta_value != ''
            ";
            
            return $wpdb->get_row($wpdb->prepare($sql, $meta_key));
        };
        
        // Bedrooms
        $beds = $get_min_max('floor_plan_bedrooms');
        if ($beds) {
            $ranges['bedrooms']['min'] = $beds->min_val;
            $ranges['bedrooms']['max'] = $beds->max_val;
            $ranges['bedrooms']['formatted'] = $this->format_generic_range($beds->min_val, $beds->max_val);
        }
        
        // Bathrooms
        $baths = $get_min_max('floor_plan_bathrooms');
        if ($baths) {
            $ranges['bathrooms']['min'] = $baths->min_val;
            $ranges['bathrooms']['max'] = $baths->max_val;
            $ranges['bathrooms']['formatted'] = $this->format_generic_range($baths->min_val, $baths->max_val);
        }
        
        // Garage
        $garage_data = $get_min_max('floor_plan_garage');
        if ($garage_data) {
            $ranges['garage']['min'] = $garage_data->min_val;
            $ranges['garage']['max'] = $garage_data->max_val;
            $ranges['garage']['formatted'] = $this->format_generic_range($garage_data->min_val, $garage_data->max_val);
        }
        
        // Square Feet
        $sqft = $get_min_max('floor_plan_square_feet');
        if ($sqft) {
            $ranges['square_feet']['min'] = $sqft->min_val;
            $ranges['square_feet']['max'] = $sqft->max_val;
            $ranges['square_feet']['formatted'] = $this->format_generic_range($sqft->min_val, $sqft->max_val, true);
        }
        
        // Price
        $price_data = $get_min_max('floor_plan_price');
        if ($price_data) {
            $ranges['price']['min'] = $price_data->min_val;
            $ranges['price']['max'] = $price_data->max_val;
            $ranges['price']['formatted'] = $this->format_price_range($price_data->min_val, $price_data->max_val);
        }
        
        // Cache the ranges for future requests
        update_post_meta($community_id, '_bh_floor_plan_ranges', $ranges);
        
        return $ranges;
    }

    /**
     * Format a generic min/max range
     * 
     * @param mixed $min
     * @param mixed $max
     * @param bool $is_numeric Whether to use number formatting (for sqft)
     * @return string
     */
    private function format_generic_range($min, $max, $is_numeric = false) {
        if ($min === null || $max === null) return '';
        
        $f_min = $is_numeric ? number_format($min) : $min;
        $f_max = $is_numeric ? number_format($max) : $max;
        
        return ($min == $max) ? $f_min : "$f_min - $f_max";
    }
    
    /**
     * Format price range for display
     * 
     * @param int $min_price Minimum price
     * @param int $max_price Maximum price
     * @return string Formatted price range
     */
    public function format_price_range($min_price, $max_price) {
        if ($min_price === null || $max_price === null) {
            return '';
        }
        
        // Format prices with dollar sign and commas
        $formatted_min = '$' . number_format($min_price, 0);
        $formatted_max = '$' . number_format($max_price, 0);
        
        // If min and max are the same, return single price
        if ($min_price == $max_price) {
            return $formatted_min;
        }
        
        // Return price range
        return $formatted_min . ' - ' . $formatted_max;
    }
    
    /**
     * Extract numeric value from string (handles formats like '2.5', '2-Car', etc.)
     * 
     * @param string $value The input value
     * @return float|null The extracted numeric value or null if not found
     */
    private function extract_numeric_value($value) {
        if (empty($value)) {
            return null;
        }
        
        // Remove commas and other non-numeric characters except decimal point
        $clean_value = preg_replace('/[^-0-9.]/', '', $value);
        
        if (is_numeric($clean_value)) {
            return floatval($clean_value);
        }
        
        // Fallback for cases where there might be other text (e.g., "2-Car")
        $pattern = '/[0-9]+\.?[0-9]*/';
        preg_match($pattern, $value, $matches);
        
        if (!empty($matches[0])) {
            return floatval($matches[0]);
        }
        
        return null;
    }
    
    /**
     * Legacy method - No longer needed after v2.0.0 refactor
     * The taxonomy system has been replaced with ACF Relationship fields
     * 
     * @deprecated 2.0.0 Use ACF Relationship fields instead
     */
    public function maybe_sync_communities_to_taxonomy() {
        // This method is deprecated and no longer needed
        // The bh_floor_plan_community taxonomy has been removed in v2.0.0
        error_log('Burgland Homes: maybe_sync_communities_to_taxonomy() is deprecated and should not be called');
    }
    
    /**
     * Legacy method - No longer needed after v2.0.0 refactor
     * 
     * @deprecated 2.0.0 Use ACF Relationship fields instead
     */
    public function sync_existing_communities() {
        // This method is deprecated and no longer needed
        error_log('Burgland Homes: sync_existing_communities() is deprecated and should not be called');
    }
    
    /**
     * Legacy method - No longer needed after v2.0.0 refactor
     * 
     * @deprecated 2.0.0 Use ACF Relationship fields instead
     * @return array Empty result array
     */
    public function cleanup_orphaned_taxonomy_terms() {
        // This method is deprecated and no longer needed
        error_log('Burgland Homes: cleanup_orphaned_taxonomy_terms() is deprecated and should not be called');
        return array(
            'deleted' => 0,
            'errors' => array()
        );
    }
    
    /**
     * Legacy method - No longer needed after v2.0.0 refactor
     * 
     * @deprecated 2.0.0 Use ACF Relationship fields instead
     */
    public function force_cleanup_orphaned_terms() {
        // This method is deprecated and no longer needed
        error_log('Burgland Homes: force_cleanup_orphaned_terms() is deprecated and should not be called');
        return 'This method is deprecated. The taxonomy system has been replaced with ACF Relationship fields in v2.0.0.';
    }
}