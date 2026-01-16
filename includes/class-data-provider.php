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
                    'footer_text' => sprintf('%s, %s', $raw['city'], $raw['state'])
                );
                
                if ($raw['status_label']) {
                    $data['badges'][] = array(
                        'label' => $raw['status_label'],
                        'class' => $raw['status_class']
                    );
                }
                
                if (isset($raw['floor_plan_ranges']['bedrooms']) && !empty($raw['floor_plan_ranges']['bedrooms']['formatted'])) {
                    $data['specs'][] = array('label' => $raw['floor_plan_ranges']['bedrooms']['formatted'] . ' Bed', 'icon' => 'house-door');
                }
                
                if (isset($raw['floor_plan_ranges']['bathrooms']) && !empty($raw['floor_plan_ranges']['bathrooms']['formatted'])) {
                    $data['specs'][] = array('label' => $raw['floor_plan_ranges']['bathrooms']['formatted'] . ' Bath', 'icon' => 'droplet');
                }
                
                if (isset($raw['floor_plan_ranges']['square_feet']) && !empty($raw['floor_plan_ranges']['square_feet']['formatted'])) {
                    $data['specs'][] = array('label' => $raw['floor_plan_ranges']['square_feet']['formatted'] . ' sqft', 'icon' => 'arrows-angle-expand');
                }
                
                if (isset($raw['floor_plan_ranges']['garage']) && !empty($raw['floor_plan_ranges']['garage']['formatted'])) {
                    $data['specs'][] = array('label' => $raw['floor_plan_ranges']['garage']['formatted'] . ' Car', 'icon' => 'car-front');
                }
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
                    'footer_text' => $raw['floor_plan_name'] ? 'Floor Plan: ' . $raw['floor_plan_name'] : ''
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

                if ($raw['bedrooms']) $data['specs'][] = array('label' => $raw['bedrooms'] . ' Bed', 'icon' => 'house-door');
                if ($raw['bathrooms']) $data['specs'][] = array('label' => $raw['bathrooms'] . ' Bath', 'icon' => 'droplet');
                if ($raw['square_feet']) $data['specs'][] = array('label' => number_format($raw['square_feet']) . ' sqft', 'icon' => 'arrows-angle-expand');
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
                    'specs' => array(),
                    'footer_text' => ''
                );

                if ($raw['bedrooms']) $data['specs'][] = array('label' => $raw['bedrooms'] . ' Bed', 'icon' => 'house-door');
                if ($raw['bathrooms']) $data['specs'][] = array('label' => $raw['bathrooms'] . ' Bath', 'icon' => 'droplet');
                if ($raw['square_feet']) $data['specs'][] = array('label' => number_format($raw['square_feet']) . ' sqft', 'icon' => 'arrows-angle-expand');
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
                $data[] = $this->get_card_data(get_the_ID());
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
        
        $query_args = array(
            'post_type' => 'bh_community',
            'posts_per_page' => intval($args['limit']),
            'post_status' => 'publish',
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'post__not_in' => $args['exclude'],
        );
        
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
        $utilities = Burgland_Homes_Utilities::get_instance();
        $floor_plan_ranges = $utilities->get_floor_plan_ranges($community_id);
        
        // Get status
        $status_terms = wp_get_post_terms($community_id, 'bh_community_status');
        $status_label = '';
        $status_class = 'primary';
        
        if (!empty($status_terms) && !is_wp_error($status_terms)) {
            $status_label = $status_terms[0]->name;
            $status = $status_terms[0]->slug;
            
            $status_class_map = array(
                'active' => 'success',
                'selling-fast' => 'warning',
                'sold-out' => 'secondary',
                'coming-soon' => 'info',
            );
            
            $status_class = isset($status_class_map[$status]) ? $status_class_map[$status] : 'primary';
        }
        
        $price_range = get_post_meta($community_id, 'community_price_range', true);
        $display_price = !empty($floor_plan_ranges['price']['formatted']) 
            ? $floor_plan_ranges['price']['formatted'] 
            : $price_range;
        
        return apply_filters('burgland_homes_community_data', array(
            'id' => $community_id,
            'title' => get_the_title($community_id),
            'excerpt' => has_excerpt($community_id) ? wp_trim_words(get_the_excerpt($community_id), 15) : '',
            'permalink' => get_permalink($community_id),
            'city' => get_post_meta($community_id, 'community_city', true),
            'state' => get_post_meta($community_id, 'community_state', true),
            'price_range' => $display_price,
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
        
        $query_args = array(
            'post_type' => 'bh_lot',
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
        );
        
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
        $floor_plan_id = get_post_meta($lot_id, 'lot_floor_plan', true);
        $floor_plan_name = '';
        
        if ($floor_plan_id) {
            $floor_plan = get_post($floor_plan_id);
            if ($floor_plan) {
                $floor_plan_name = $floor_plan->post_title;
            }
        }
        
        $lot_state = get_post_meta($lot_id, 'lot_state', true);
        $status_map = array(
            'empty_lot' => array('label' => 'Empty Lot', 'class' => 'secondary'),
            'home_assigned' => array('label' => 'Home Assigned', 'class' => 'info'),
            'under_construction' => array('label' => 'Under Construction', 'class' => 'warning'),
            'move_in_ready' => array('label' => 'Move-in Ready', 'class' => 'success'),
            'sold' => array('label' => 'Sold', 'class' => 'dark'),
        );
        
        $status_info = isset($status_map[$lot_state]) ? $status_map[$lot_state] : array(
            'label' => ucfirst(str_replace('_', ' ', $lot_state)),
            'class' => 'primary'
        );
        
        return apply_filters('burgland_homes_lot_data', array(
            'id' => $lot_id,
            'title' => get_the_title($lot_id),
            'permalink' => get_permalink($lot_id),
            'thumbnail' => get_the_post_thumbnail_url($lot_id, 'large'),
            'floor_plan_id' => $floor_plan_id,
            'floor_plan_name' => $floor_plan_name,
            'lot_number' => get_post_meta($lot_id, 'lot_number', true),
            'lot_size' => get_post_meta($lot_id, 'lot_size', true),
            'price' => get_post_meta($lot_id, 'lot_price', true),
            'premium' => get_post_meta($lot_id, 'lot_premium', true),
            'status_label' => $status_info['label'],
            'status_class' => $status_info['class'],
            'bedrooms' => get_post_meta($lot_id, 'lot_bedrooms', true),
            'bathrooms' => get_post_meta($lot_id, 'lot_bathrooms', true),
            'square_feet' => get_post_meta($lot_id, 'lot_square_feet', true),
            'garage' => get_post_meta($lot_id, 'lot_garage', true),
            'stories' => get_post_meta($lot_id, 'lot_stories', true),
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
        ), $floor_plan_id);
    }
}
