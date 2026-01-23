<?php
/**
 * Data Provider - Public API for retrieving plugin data
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Data_Provider
 */
class Burgland_Homes_Data_Provider {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Spec configuration
     */
    private $spec_config = array(
        'bedrooms' => array('suffix' => ' Bed', 'icon' => 'house-door'),
        'bathrooms' => array('suffix' => ' Bath', 'icon' => 'droplet'),
        'square_feet' => array('suffix' => ' sqft', 'icon' => 'arrows-angle-expand', 'format' => true),
        'garage' => array('suffix' => ' Car', 'icon' => 'car-front'),
    );
    
    /**
     * Status configuration for different post types
     */
    private $status_config = array(
        'community' => array(
            'active' => array('label' => 'Active', 'class' => 'success'),
            'selling-fast' => array('label' => 'Selling Fast', 'class' => 'warning'),
            'sold-out' => array('label' => 'Sold Out', 'class' => 'secondary'),
            'coming-soon' => array('label' => 'Coming Soon', 'class' => 'info'),
        ),
        'lot' => array(
            'empty_lot' => array('label' => 'Empty Lot', 'class' => 'secondary'),
            'home_assigned' => array('label' => 'Home Assigned', 'class' => 'info'),
            'under_construction' => array('label' => 'Under Construction', 'class' => 'warning'),
            'move_in_ready' => array('label' => 'Move-in Ready', 'class' => 'success'),
            'sold' => array('label' => 'Sold', 'class' => 'dark'),
        )
    );
    
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
     * Get standardized card data for any post type
     * 
     * @param int $post_id
     * @return array
     */
    public function get_card_data($post_id) {
        // Validate post ID
        if (!$post_id || !get_post($post_id)) {
            return array();
        }
        
        $post_type = get_post_type($post_id);
        $data = array();

        switch ($post_type) {
            case 'bh_community':
                $raw = $this->get_community_data($post_id);
                $data = array(
                    'id' => $post_id,
                    'type' => 'community',
                    'title' => $raw['title'],
                    'url' => $raw['permalink'],
                    'image' => $raw['thumbnail'],
                    'price' => $raw['price_range'],
                    'badges' => array(),
                    'specs' => array(),
                    'address' => $this->format_full_address($raw),
                    'address_plain' => $this->format_plain_address($raw),
                    'map_url' => $this->format_map_url($raw)
                );
                
                if ($raw['status_label']) {
                    $data['badges'][] = array(
                        'label' => $raw['status_label'],
                        'class' => $raw['status_class']
                    );
                }
                
                // Build specs from floor plan ranges
                $data['specs'] = $this->build_specs_from_ranges($raw['floor_plan_ranges']);
                break;

            case 'bh_lot':
                $raw = $this->get_lot_data($post_id);
                $data = array(
                    'id' => $post_id,
                    'type' => 'lot',
                    'title' => $raw['title'],
                    'url' => $raw['permalink'],
                    'image' => $raw['thumbnail'],
                    'price' => $raw['price'] ? 'Priced at: ' . $raw['price'] : '',
                    'badges' => array(),
                    'specs' => array(),
                    'floor_plan_info' => $raw['floor_plan_name'] ? 'Floor Plan: ' . $raw['floor_plan_name'] : '',
                    'floor_plan_name' => $raw['floor_plan_name'],
                    'floor_plan_url' => $raw['floor_plan_id'] ? get_permalink($raw['floor_plan_id']) : ''
                );

                if ($raw['status_label']) {
                    $data['badges'][] = array(
                        'label' => $raw['status_label'],
                        'class' => $raw['status_class']
                    );
                }

                if ($raw['premium']) {
                    $data['badges'][] = array(
                        'label' => 'Premium',
                        'class' => 'warning text-dark'
                    );
                }

                $data['specs'] = $this->build_specs($raw);
                break;

            case 'bh_floor_plan':
                $raw = $this->get_floor_plan_data($post_id);
                $data = array(
                    'id' => $post_id,
                    'type' => 'floor-plan',
                    'title' => $raw['title'],
                    'url' => $raw['permalink'],
                    'image' => $raw['thumbnail'],
                    'price' => $raw['price'],
                    'badges' => array(),
                    'specs' => array()
                );

                $data['specs'] = $this->build_specs($raw);
                break;
        }

        return apply_filters('burgland_homes_card_data', $data, $post_id, $post_type);
    }

    /**
     * Get posts with standardized card data
     * 
     * @param array $args
     * @return array
     */
    public function get_posts($args = array()) {
        $defaults = array(
            'post_type' => 'bh_community',
            'posts_per_page' => 6,
            'post_status' => 'publish',
        );
        
        $query_args = wp_parse_args($args, $defaults);
        $query = new WP_Query($query_args);
        $data = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $card_data = $this->get_card_data(get_the_ID());
                if (!empty($card_data)) {
                    $data[] = $card_data;
                }
            }
            wp_reset_postdata();
        }
        
        return $data;
    }

    /**
     * Get featured communities data
     * 
     * @param array $args Query arguments
     * @return array Structured data array
     */
    public function get_featured_communities($args = array()) {
        $defaults = array(
            'limit' => 6,
            'order' => 'ASC',
            'orderby' => 'title',
            'exclude' => array(),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = $this->build_base_query_args('bh_community', array(
            'posts_per_page' => intval($args['limit']),
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'post__not_in' => $args['exclude'],
        ));
        
        $communities = new WP_Query($query_args);
        $data = array();
        
        if ($communities->have_posts()) {
            while ($communities->have_posts()) {
                $communities->the_post();
                $post_id = get_the_ID();
                
                $data[] = $this->get_community_data($post_id);
            }
            wp_reset_postdata();
        }
        
        return apply_filters('burgland_homes_featured_communities_data', $data, $args);
    }
    
    /**
     * Get single community data
     * 
     * @param int $community_id
     * @return array
     */
    public function get_community_data($community_id) {
        // Validate community ID
        if (!$community_id || !get_post($community_id)) {
            return array();
        }
        
        $utilities = Burgland_Homes_Utilities::get_instance();
        $floor_plan_ranges = $utilities->get_floor_plan_ranges($community_id);
        
        // Get status
        $status_terms = wp_get_post_terms($community_id, 'bh_community_status');
        $status_label = '';
        $status_class = 'primary';
        
        if (!empty($status_terms) && !is_wp_error($status_terms)) {
            $status_label = $status_terms[0]->name;
            $status = $status_terms[0]->slug;
            
            $status_config = $this->get_status_config('community');
            $status_class = isset($status_config[$status]) ? $status_config[$status]['class'] : 'primary';
        }
        
        // Get price range directly from community ACF field
        $price_range = get_post_meta($community_id, 'community_price_range', true);
        
        return apply_filters('burgland_homes_community_data', array(
            'id' => $community_id,
            'title' => get_the_title($community_id),
            'excerpt' => has_excerpt($community_id) ? wp_trim_words(get_the_excerpt($community_id), 15) : '',
            'permalink' => get_permalink($community_id),
            'address' => get_post_meta($community_id, 'community_address', true),
            'city' => get_post_meta($community_id, 'community_city', true),
            'state' => get_post_meta($community_id, 'community_state', true),
            'zip' => get_post_meta($community_id, 'community_zip', true),
            'map_url' => $this->format_map_url(array(
                'address' => get_post_meta($community_id, 'community_address', true),
                'city' => get_post_meta($community_id, 'community_city', true),
                'state' => get_post_meta($community_id, 'community_state', true),
                'zip' => get_post_meta($community_id, 'community_zip', true),
            )),
            'price_range' => $price_range,
            'latitude' => get_post_meta($community_id, 'community_latitude', true),
            'longitude' => get_post_meta($community_id, 'community_longitude', true),
            'has_thumbnail' => has_post_thumbnail($community_id),
            'thumbnail' => has_post_thumbnail($community_id) ? get_the_post_thumbnail_url($community_id, 'medium_large') : '',
            'status_label' => $status_label,
            'status_class' => $status_class,
            'floor_plan_ranges' => $floor_plan_ranges,
        ), $community_id);
    }
    
    /**
     * Get available lots
     * 
     * @param array $args
     * @return array
     */
    public function get_available_lots($args = array()) {
        $defaults = array(
            'limit' => 6,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'exclude_states' => array('sold', 'empty_lot'),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = $this->build_base_query_args('bh_lot', array(
            'posts_per_page' => intval($args['limit']),
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'lot_state',
                    'value' => $args['exclude_states'],
                    'compare' => 'NOT IN'
                )
            )
        ));
        
        $lots = new WP_Query($query_args);
        $data = array();
        
        if ($lots->have_posts()) {
            while ($lots->have_posts()) {
                $lots->the_post();
                $data[] = $this->get_lot_data(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        return apply_filters('burgland_homes_available_lots_data', $data, $args);
    }
    
    /**
     * Get single lot data
     * 
     * @param int $lot_id
     * @return array
     */
    public function get_lot_data($lot_id) {
        // Validate lot ID
        if (!$lot_id || !get_post($lot_id)) {
            return array();
        }
        $floor_plan_id = get_post_meta($lot_id, 'lot_floor_plan', true);
        $floor_plan_name = '';
        $floor_plan_data = array();
        
        if ($floor_plan_id) {
            $floor_plan = get_post($floor_plan_id);
            if ($floor_plan) {
                $floor_plan_name = $floor_plan->post_title;
                // Get floor plan data for inheritance
                $floor_plan_data = $this->get_floor_plan_data($floor_plan_id);
            }
        }
        
        // Get parent community to inherit city/state
        $community_id = get_post_meta($lot_id, 'lot_community', true);
        $city = '';
        $state = '';
        $zip = '';
        
        if ($community_id) {
            $city = get_post_meta($community_id, 'community_city', true);
            $state = get_post_meta($community_id, 'community_state', true);
            $zip = get_post_meta($community_id, 'community_zip', true);
        }
        
        $lot_state = get_post_meta($lot_id, 'lot_state', true);
        $status_config = $this->get_status_config('lot');
        
        $status_info = isset($status_config[$lot_state]) ? $status_config[$lot_state] : array(
            'label' => ucfirst(str_replace('_', ' ', $lot_state)),
            'class' => 'primary'
        );
        
        // Get lot specs with floor plan inheritance
        $bedrooms = get_post_meta($lot_id, 'lot_bedrooms', true);
        $bathrooms = get_post_meta($lot_id, 'lot_bathrooms', true);
        $square_feet = get_post_meta($lot_id, 'lot_square_feet', true);
        $garage = get_post_meta($lot_id, 'lot_garage', true);
        
        // Inherit from floor plan if lot values are empty
        if (empty($bedrooms) && !empty($floor_plan_data['bedrooms'])) {
            $bedrooms = $floor_plan_data['bedrooms'];
        }
        if (empty($bathrooms) && !empty($floor_plan_data['bathrooms'])) {
            $bathrooms = $floor_plan_data['bathrooms'];
        }
        if (empty($square_feet) && !empty($floor_plan_data['square_feet'])) {
            $square_feet = $floor_plan_data['square_feet'];
        }
        if (empty($garage) && !empty($floor_plan_data['garage'])) {
            $garage = $floor_plan_data['garage'];
        }
        
        return apply_filters('burgland_homes_lot_data', array(
            'id' => $lot_id,
            'title' => get_the_title($lot_id),
            'permalink' => get_permalink($lot_id),
            'thumbnail' => get_the_post_thumbnail_url($lot_id, 'large'),
            'floor_plan_id' => $floor_plan_id,
            'floor_plan_name' => $floor_plan_name,
            'lot_number' => get_post_meta($lot_id, 'lot_number', true),
            'address' => get_post_meta($lot_id, 'lot_address', true),
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'map_url' => $this->format_map_url(array(
                'address' => get_post_meta($lot_id, 'lot_address', true),
                'city' => $city,
                'state' => $state,
                'zip' => $zip,
            )),
            'lot_size' => get_post_meta($lot_id, 'lot_size', true),
            'price' => get_post_meta($lot_id, 'lot_price', true),
            'premium' => get_post_meta($lot_id, 'lot_premium', true),
            'status_label' => $status_info['label'],
            'status_class' => $status_info['class'],
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'square_feet' => $square_feet,
            'garage' => $garage,
            'stories' => get_post_meta($lot_id, 'lot_stories', true),
            'brochure' => get_field('lot_brochure', $lot_id),
            'floor_plan_pdf' => !empty($floor_plan_data['pdf']) ? $floor_plan_data['pdf'] : null,
        ), $lot_id);
    }
    
    /**
     * Get floor plan data
     * 
     * @param int $floor_plan_id
     * @return array
     */
    public function get_floor_plan_data($floor_plan_id) {
        return apply_filters('burgland_homes_floor_plan_data', array(
            'id' => $floor_plan_id,
            'title' => get_the_title($floor_plan_id),
            'permalink' => get_permalink($floor_plan_id),
            'thumbnail' => get_the_post_thumbnail_url($floor_plan_id, 'large'),
            'price' => get_post_meta($floor_plan_id, 'floor_plan_price', true),
            'bedrooms' => get_post_meta($floor_plan_id, 'floor_plan_bedrooms', true),
            'bathrooms' => get_post_meta($floor_plan_id, 'floor_plan_bathrooms', true),
            'square_feet' => get_post_meta($floor_plan_id, 'floor_plan_square_feet', true),
            'garage' => get_post_meta($floor_plan_id, 'floor_plan_garage', true),
            'stories' => get_post_meta($floor_plan_id, 'floor_plan_stories', true),
            'features' => get_post_meta($floor_plan_id, 'floor_plan_features', true),
            'pdf' => get_field('floor_plan_brochure', $floor_plan_id),
        ), $floor_plan_id);
    }

    /**
     * Format plain address from data array
     *
     * @param array $data Data array containing address components
     * @return string Formatted address
     */
    private function format_plain_address($data) {
        $address = isset($data['address']) ? $data['address'] : '';
        $city = isset($data['city']) ? $data['city'] : '';
        $state = isset($data['state']) ? $data['state'] : '';
        $zip = isset($data['zip']) ? $data['zip'] : '';

        $parts = array_filter(array($address, $city, $state, $zip));
        return trim(implode(', ', $parts), ', ');
    }

    /**
     * Build Google Maps URL from address data
     *
     * @param array $data Data array containing address components
     * @return string Google Maps URL
     */
    private function format_map_url($data) {
        $address = $this->format_plain_address($data);
        if (!$address) {
            return '';
        }

        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address);
    }

    /**
     * Build specs array from configuration and raw data
     * 
     * @param array $raw_data Raw data containing spec fields
     * @return array Array of formatted spec items
     */
    private function build_specs($raw_data) {
        $specs = array();
        
        foreach ($this->spec_config as $key => $config) {
            if (!empty($raw_data[$key])) {
                $value = isset($config['format']) && $config['format'] 
                    ? number_format($raw_data[$key]) 
                    : $raw_data[$key];
                
                $specs[] = array(
                    'label' => $value . $config['suffix'],
                    'icon' => $config['icon']
                );
            }
        }
        
        return $specs;
    }
    
    /**
     * Build specs array from floor plan ranges (for communities)
     * 
     * @param array $ranges Floor plan ranges with formatted values
     * @return array Array of formatted spec items
     */
    private function build_specs_from_ranges($ranges) {
        $specs = array();
        
        foreach ($this->spec_config as $key => $config) {
            if (isset($ranges[$key]) && !empty($ranges[$key]['formatted'])) {
                $specs[] = array(
                    'label' => $ranges[$key]['formatted'] . $config['suffix'],
                    'icon' => $config['icon']
                );
            }
        }
        
        return $specs;
    }
    
    /**
     * Get status configuration for a post type
     * 
     * @param string $type Post type ('community' or 'lot')
     * @return array Status configuration array
     */
    private function get_status_config($type) {
        return isset($this->status_config[$type]) ? $this->status_config[$type] : array();
    }
    
    /**
     * Build base query arguments for common post type queries
     * 
     * @param string $post_type Post type to query
     * @param array $additional Additional query arguments to merge
     * @return array Merged query arguments
     */
    private function build_base_query_args($post_type, $additional = array()) {
        $base_args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
        );
        
        return wp_parse_args($additional, $base_args);
    }
    
    /**
     * Format full address from data array
     * 
     * @param array $data Data array containing address components
     * @param bool $with_line_break Whether to add <br> tag between address and city/state/zip
     * @return string Formatted address
     */
    private function format_full_address($data, $with_line_break = true) {
        $address = isset($data['address']) ? $data['address'] : '';
        $city = isset($data['city']) ? $data['city'] : '';
        $state = isset($data['state']) ? $data['state'] : '';
        $zip = isset($data['zip']) ? $data['zip'] : '';

        $full = '';
        if ($address) {
            $full .= $address;
        }

        if ($city || $state || $zip) {
            if ($full) {
                $full .= $with_line_break ? '<br>' : ' ';
            }
            $full .= trim($city . ', ' . $state . ' ' . $zip, ', ');
        }

        return $full;
    }
}
