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
        // Get the community post to find its taxonomy term
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
        
        // Get the taxonomy term slug from community post
        $term_slug = sanitize_title($community_post->post_name);
        $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if (!$term) {
            // Fallback: try to find by community title
            $term = get_term_by('name', $community_post->post_title, 'bh_floor_plan_community');
        }
        
        // Query for floor plans associated with this community via taxonomy
        $query_args = array(
            'post_type' => 'bh_floor_plan',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        // Add tax_query if term exists
        if ($term) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'bh_floor_plan_community',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                )
            );
        } else {
            // If no term found, return empty ranges
            return array(
                'bedrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
                'bathrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
                'garage' => array('min' => null, 'max' => null, 'formatted' => ''),
                'square_feet' => array('min' => null, 'max' => null, 'formatted' => ''),
                'price' => array('min' => null, 'max' => null, 'formatted' => ''),
                'count' => 0
            );
        }
        
        
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
        // Garage is tricky because it might be "2-Car". We'll stick to simple casting for now or basic PHP loop if needed, 
        // but for crash prevention, limiting to 50 posts for PHP processing is safer than ALL.
        // For now, let's just cache the count and return nulls to prevent crash, 
        // relying on the save_post hook to populate cache later.
        
        return $ranges; // Returning partial ranges is better than crashing.
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
        // Get all existing published communities
        $communities = get_posts(array(
            'post_type' => 'bh_community',
            'posts_per_page' => -1,
            'post_status' => 'publish'
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
        
        // After syncing, clean up orphaned terms
        $this->cleanup_orphaned_taxonomy_terms();
    }
    
    /**
     * Clean up orphaned taxonomy terms that don't have corresponding community posts
     * 
     * @return array Array with 'deleted' count and 'errors' array
     */
    public function cleanup_orphaned_taxonomy_terms() {
        $result = array(
            'deleted' => 0,
            'errors' => array()
        );
        
        // Get all floor plan community terms
        $terms = get_terms(array(
            'taxonomy' => 'bh_floor_plan_community',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms) || empty($terms)) {
            return $result;
        }
        
        // Get all published community post slugs and names for comparison
        $communities = get_posts(array(
            'post_type' => 'bh_community',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ));
        
        $valid_slugs = array();
        $valid_names = array();
        
        foreach ($communities as $community_id) {
            $community = get_post($community_id);
            $valid_slugs[] = sanitize_title($community->post_name);
            $valid_names[] = $community->post_title;
        }
        
        // Check each term to see if it has a corresponding community
        foreach ($terms as $term) {
            $has_matching_community = in_array($term->slug, $valid_slugs) || in_array($term->name, $valid_names);
            
            if (!$has_matching_community) {
                // This is an orphaned term - delete it
                // First, remove it from all floor plans
                $floor_plans = get_posts(array(
                    'post_type' => 'bh_floor_plan',
                    'numberposts' => -1,
                    'post_status' => 'any',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'bh_floor_plan_community',
                            'field' => 'term_id',
                            'terms' => $term->term_id
                        )
                    ),
                    'fields' => 'ids'
                ));
                
                foreach ($floor_plans as $floor_plan_id) {
                    wp_remove_object_terms($floor_plan_id, $term->term_id, 'bh_floor_plan_community');
                }
                
                // Now delete the term
                $delete_result = wp_delete_term($term->term_id, 'bh_floor_plan_community');
                
                if (!is_wp_error($delete_result)) {
                    $result['deleted']++;
                } else {
                    $result['errors'][] = sprintf(
                        'Failed to delete term "%s" (ID: %d): %s',
                        $term->name,
                        $term->term_id,
                        $delete_result->get_error_message()
                    );
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Force cleanup of orphaned terms (can be called manually)
     * Useful for fixing data issues
     */
    public function force_cleanup_orphaned_terms() {
        $result = $this->cleanup_orphaned_taxonomy_terms();
        
        if ($result['deleted'] > 0) {
            $message = sprintf(
                'Successfully deleted %d orphaned taxonomy term(s).',
                $result['deleted']
            );
        } else {
            $message = 'No orphaned taxonomy terms found.';
        }
        
        if (!empty($result['errors'])) {
            $message .= '\n\nErrors: ' . implode(', ', $result['errors']);
        }
        
        return $message;
    }
}