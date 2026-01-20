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
        
        $floor_plans_query = new WP_Query($query_args);
        
        // Initialize min/max values
        $ranges = array(
            'bedrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'bathrooms' => array('min' => null, 'max' => null, 'formatted' => ''),
            'garage' => array('min' => null, 'max' => null, 'formatted' => ''),
            'square_feet' => array('min' => null, 'max' => null, 'formatted' => ''),
            'price' => array('min' => null, 'max' => null, 'formatted' => ''),
            'count' => 0
        );
        
        // If no floor plans found, return null values
        if (!$floor_plans_query->have_posts()) {
            return $ranges;
        }
        
        $ranges['count'] = $floor_plans_query->found_posts;
        
        // Arrays to hold all values for each field
        $bedrooms_values = array();
        $bathrooms_values = array();
        $garage_values = array();
        $square_feet_values = array();
        $price_values = array();
        
        // Loop through floor plans and collect values
        while ($floor_plans_query->have_posts()) {
            $floor_plans_query->the_post();
            $fp_id = get_the_ID();
            
            // Get bedroom value
            $bedrooms = get_post_meta($fp_id, 'floor_plan_bedrooms', true);
            if ($bedrooms !== '' && $bedrooms !== null) {
                // Handle cases where bedrooms might be a number or text like '2.5'
                if (is_numeric($bedrooms)) {
                    $bedrooms_values[] = floatval($bedrooms);
                } else {
                    // Attempt to extract numeric value from text like '2.5'
                    $numeric_bedrooms = $this->extract_numeric_value($bedrooms);
                    if ($numeric_bedrooms !== null) {
                        $bedrooms_values[] = $numeric_bedrooms;
                    }
                }
            }
            
            // Get bathroom value
            $bathrooms = get_post_meta($fp_id, 'floor_plan_bathrooms', true);
            if ($bathrooms !== '' && $bathrooms !== null) {
                if (is_numeric($bathrooms)) {
                    $bathrooms_values[] = floatval($bathrooms);
                } else {
                    $numeric_bathrooms = $this->extract_numeric_value($bathrooms);
                    if ($numeric_bathrooms !== null) {
                        $bathrooms_values[] = $numeric_bathrooms;
                    }
                }
            }
            
            // Get garage value
            $garage = get_post_meta($fp_id, 'floor_plan_garage', true);
            if ($garage !== '' && $garage !== null) {
                if (is_numeric($garage)) {
                    $garage_values[] = floatval($garage);
                } else {
                    $numeric_garage = $this->extract_numeric_value($garage);
                    if ($numeric_garage !== null) {
                        $garage_values[] = $numeric_garage;
                    }
                }
            }
            
            // Get square feet value
            $square_feet = get_post_meta($fp_id, 'floor_plan_square_feet', true);
            if ($square_feet !== '' && $square_feet !== null) {
                if (is_numeric($square_feet)) {
                    $square_feet_values[] = intval($square_feet);
                } else {
                    $numeric_square_feet = $this->extract_numeric_value($square_feet);
                    if ($numeric_square_feet !== null) {
                        $square_feet_values[] = intval($numeric_square_feet);
                    }
                }
            }
            
            // Get price value
            $price = get_post_meta($fp_id, 'floor_plan_price', true);
            if ($price !== '' && $price !== null) {
                // Extract numeric value from price (handles formats like '$589,900', '$725,000', etc.)
                $numeric_price = $this->extract_numeric_value($price);
                if ($numeric_price !== null) {
                    $price_values[] = intval($numeric_price);
                }
            }
        }
        
        wp_reset_postdata();
        
        // Calculate min/max for each field
        if (!empty($bedrooms_values)) {
            $ranges['bedrooms']['min'] = min($bedrooms_values);
            $ranges['bedrooms']['max'] = max($bedrooms_values);
            $ranges['bedrooms']['formatted'] = $this->format_generic_range($ranges['bedrooms']['min'], $ranges['bedrooms']['max']);
        }
        
        if (!empty($bathrooms_values)) {
            $ranges['bathrooms']['min'] = min($bathrooms_values);
            $ranges['bathrooms']['max'] = max($bathrooms_values);
            $ranges['bathrooms']['formatted'] = $this->format_generic_range($ranges['bathrooms']['min'], $ranges['bathrooms']['max']);
        }
        
        if (!empty($garage_values)) {
            $ranges['garage']['min'] = min($garage_values);
            $ranges['garage']['max'] = max($garage_values);
            $ranges['garage']['formatted'] = $this->format_generic_range($ranges['garage']['min'], $ranges['garage']['max']);
        }
        
        if (!empty($square_feet_values)) {
            $ranges['square_feet']['min'] = min($square_feet_values);
            $ranges['square_feet']['max'] = max($square_feet_values);
            $ranges['square_feet']['formatted'] = $this->format_generic_range($ranges['square_feet']['min'], $ranges['square_feet']['max'], true);
        }
        
        if (!empty($price_values)) {
            $ranges['price']['min'] = min($price_values);
            $ranges['price']['max'] = max($price_values);
            $ranges['price']['formatted'] = $this->format_price_range($ranges['price']['min'], $ranges['price']['max']);
        }
        
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