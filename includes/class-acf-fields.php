<?php
/**
 * ACF Fields Registration
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_ACF_Fields
 */
class Burgland_Homes_ACF_Fields {
    
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
        add_action('acf/include_fields', array($this, 'register_community_fields'));
        add_action('acf/include_fields', array($this, 'register_floor_plan_fields'));
        add_action('acf/include_fields', array($this, 'register_lot_fields'));
        
        // Pre-populate community field when adding from community context
        add_filter('acf/load_value/name=lot_community', array($this, 'prepopulate_lot_community'), 10, 3);
        add_filter('acf/load_value/name=floor_plan_community', array($this, 'prepopulate_floor_plan_community'), 10, 3);
    }
    
    /**
     * Register Community ACF Fields
     */
    public function register_community_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        acf_add_local_field_group(array(
            'key' => 'group_community_details',
            'title' => 'Community Details',
            'fields' => array(
                array(
                    'key' => 'field_community_address',
                    'label' => 'Address',
                    'name' => 'community_address',
                    'type' => 'text',
                    'instructions' => 'Full address of the community',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_city',
                    'label' => 'City',
                    'name' => 'community_city',
                    'type' => 'text',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_state',
                    'label' => 'State',
                    'name' => 'community_state',
                    'type' => 'text',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_zip',
                    'label' => 'ZIP Code',
                    'name' => 'community_zip',
                    'type' => 'text',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_latitude',
                    'label' => 'Latitude',
                    'name' => 'community_latitude',
                    'type' => 'text',
                    'instructions' => 'For map display (e.g., 40.7128)',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_longitude',
                    'label' => 'Longitude',
                    'name' => 'community_longitude',
                    'type' => 'text',
                    'instructions' => 'For map display (e.g., -74.0060)',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_total_lots',
                    'label' => 'Total Lots',
                    'name' => 'community_total_lots',
                    'type' => 'number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_price_range',
                    'label' => 'Price Range',
                    'name' => 'community_price_range',
                    'type' => 'text',
                    'instructions' => 'e.g., $350,000 - $650,000',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_amenities',
                    'label' => 'Amenities',
                    'name' => 'community_amenities',
                    'type' => 'textarea',
                    'instructions' => 'One amenity per line',
                    'required' => 0,
                    'rows' => 5,
                ),
                array(
                    'key' => 'field_community_video_url',
                    'label' => 'Video URL',
                    'name' => 'community_video_url',
                    'type' => 'url',
                    'instructions' => 'YouTube or Vimeo URL',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_brochure',
                    'label' => 'Brochure/PDF',
                    'name' => 'community_brochure',
                    'type' => 'file',
                    'instructions' => 'Upload community brochure',
                    'required' => 0,
                    'return_format' => 'array',
                    'library' => 'all',
                    'mime_types' => 'pdf',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'bh_community',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ));
    }
    
    /**
     * Register Floor Plan ACF Fields
     */
    public function register_floor_plan_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        acf_add_local_field_group(array(
            'key' => 'group_floor_plan_details',
            'title' => 'Floor Plan Details',
            'fields' => array(
                array(
                    'key' => 'field_fp_community',
                    'label' => 'Community',
                    'name' => 'floor_plan_community',
                    'type' => 'post_object',
                    'instructions' => 'Select the community this floor plan belongs to',
                    'required' => 1,
                    'post_type' => array('bh_community'),
                    'return_format' => 'id',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_fp_price',
                    'label' => 'Starting Price',
                    'name' => 'floor_plan_price',
                    'type' => 'text',
                    'instructions' => 'e.g., $450,000',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_fp_bedrooms',
                    'label' => 'Bedrooms',
                    'name' => 'floor_plan_bedrooms',
                    'type' => 'number',
                    'required' => 0,
                    'min' => 1,
                    'max' => 10,
                ),
                array(
                    'key' => 'field_fp_bathrooms',
                    'label' => 'Bathrooms',
                    'name' => 'floor_plan_bathrooms',
                    'type' => 'text',
                    'instructions' => 'e.g., 2.5',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_fp_square_feet',
                    'label' => 'Square Feet',
                    'name' => 'floor_plan_square_feet',
                    'type' => 'number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_fp_garage',
                    'label' => 'Garage',
                    'name' => 'floor_plan_garage',
                    'type' => 'text',
                    'instructions' => 'e.g., 2-Car Garage',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_fp_stories',
                    'label' => 'Stories',
                    'name' => 'floor_plan_stories',
                    'type' => 'number',
                    'required' => 0,
                    'min' => 1,
                    'max' => 3,
                ),
                array(
                    'key' => 'field_fp_features',
                    'label' => 'Features',
                    'name' => 'floor_plan_features',
                    'type' => 'textarea',
                    'instructions' => 'One feature per line',
                    'required' => 0,
                    'rows' => 5,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'bh_floor_plan',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ));
    }
    
    /**
     * Register Lot/Home ACF Fields
     */
    public function register_lot_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        acf_add_local_field_group(array(
            'key' => 'group_lot_details',
            'title' => 'Lot/Home Details',
            'fields' => array(
                array(
                    'key' => 'field_lot_community',
                    'label' => 'Community',
                    'name' => 'lot_community',
                    'type' => 'post_object',
                    'instructions' => 'Select the community this lot belongs to',
                    'required' => 1,
                    'post_type' => array('bh_community'),
                    'return_format' => 'id',
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_lot_state',
                    'label' => 'Lot/Home State',
                    'name' => 'lot_state',
                    'type' => 'select',
                    'instructions' => 'Select the current state of this lot/home in its lifecycle',
                    'required' => 1,
                    'choices' => array(
                        'empty_lot' => 'Empty Lot',
                        'home_assigned' => 'Home Assigned (To-Be-Built)',
                        'under_construction' => 'Under Construction',
                        'move_in_ready' => 'Move-In Ready',
                        'sold' => 'Sold',
                    ),
                    'default_value' => 'empty_lot',
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 1,
                    'return_format' => 'value',
                ),
                array(
                    'key' => 'field_lot_number',
                    'label' => 'Lot Number',
                    'name' => 'lot_number',
                    'type' => 'text',
                    'instructions' => 'e.g., Lot 15',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_lot_floor_plan',
                    'label' => 'Floor Plan',
                    'name' => 'lot_floor_plan',
                    'type' => 'post_object',
                    'instructions' => 'Select floor plan for this home (optional for empty lots, required otherwise)',
                    'required' => 0,
                    'post_type' => array('bh_floor_plan'),
                    'return_format' => 'id',
                    'ui' => 1,
                    'allow_null' => 1,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_lot_state',
                                'operator' => '!=',
                                'value' => 'empty_lot',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_lot_size',
                    'label' => 'Lot Size',
                    'name' => 'lot_size',
                    'type' => 'text',
                    'instructions' => 'e.g., 0.5 acres or 8,000 sq ft',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_lot_price',
                    'label' => 'Home Price',
                    'name' => 'lot_price',
                    'type' => 'text',
                    'instructions' => 'e.g., $485,000',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_lot_state',
                                'operator' => '!=',
                                'value' => 'empty_lot',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_lot_bedrooms',
                    'label' => 'Bedrooms',
                    'name' => 'lot_bedrooms',
                    'type' => 'number',
                    'instructions' => 'Number of bedrooms',
                    'required' => 0,
                    'min' => 1,
                    'max' => 10,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_lot_state',
                                'operator' => '!=',
                                'value' => 'empty_lot',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_lot_bathrooms',
                    'label' => 'Bathrooms',
                    'name' => 'lot_bathrooms',
                    'type' => 'text',
                    'instructions' => 'e.g., 2.5',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_lot_state',
                                'operator' => '!=',
                                'value' => 'empty_lot',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_lot_square_feet',
                    'label' => 'Square Feet',
                    'name' => 'lot_square_feet',
                    'type' => 'number',
                    'instructions' => 'Total living area in square feet',
                    'required' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_lot_state',
                                'operator' => '!=',
                                'value' => 'empty_lot',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_lot_premium',
                    'label' => 'Premium Lot',
                    'name' => 'lot_premium',
                    'type' => 'true_false',
                    'instructions' => 'Check if this is a premium lot',
                    'required' => 0,
                    'default_value' => 0,
                ),
                array(
                    'key' => 'field_lot_features',
                    'label' => 'Lot Features',
                    'name' => 'lot_features',
                    'type' => 'textarea',
                    'instructions' => 'One feature per line (e.g., Corner lot, Cul-de-sac)',
                    'required' => 0,
                    'rows' => 4,
                ),
                array(
                    'key' => 'field_lot_availability_date',
                    'label' => 'Availability Date',
                    'name' => 'lot_availability_date',
                    'type' => 'date_picker',
                    'instructions' => 'When will this lot be available?',
                    'required' => 0,
                    'display_format' => 'm/d/Y',
                    'return_format' => 'Y-m-d',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'bh_lot',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
        ));
    }
    
    /**
     * Pre-populate lot community field from URL parameter
     */
    public function prepopulate_lot_community($value, $post_id, $field) {
        // Only for new posts
        if ($post_id !== 'new_post') {
            return $value;
        }
        
        // Check if community_id is in URL
        if (isset($_GET['community_id']) && !empty($_GET['community_id'])) {
            return intval($_GET['community_id']);
        }
        
        return $value;
    }
    
    /**
     * Pre-populate floor plan community field from URL parameter
     */
    public function prepopulate_floor_plan_community($value, $post_id, $field) {
        // Only for new posts
        if ($post_id !== 'new_post') {
            return $value;
        }
        
        // Check if community_id is in URL
        if (isset($_GET['community_id']) && !empty($_GET['community_id'])) {
            return intval($_GET['community_id']);
        }
        
        return $value;
    }
}
