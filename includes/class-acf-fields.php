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
class Burgland_Homes_ACF_Fields
{

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('acf/include_fields', array($this, 'register_community_fields'));
        add_action('acf/include_fields', array($this, 'register_floor_plan_fields'));
        add_action('acf/include_fields', array($this, 'register_lot_fields'));

        // Pre-populate community field when adding from community context
        add_filter('acf/load_value/name=lot_community', array($this, 'prepopulate_lot_community'), 10, 3);
        add_filter('acf/load_value/name=floor_plan_community', array($this, 'prepopulate_floor_plan_community'), 10, 3);

        // Filter floor plan options based on selected community
        add_filter('acf/fields/post_object/query/name=lot_floor_plan', array($this, 'filter_floor_plans_by_community'), 10, 3);

        // Add inheritance mechanism for lot fields
        add_filter('acf/load_value/name=lot_bedrooms', array($this, 'load_inherited_values'), 10, 3);
        add_filter('acf/load_value/name=lot_bathrooms', array($this, 'load_inherited_values'), 10, 3);
        add_filter('acf/load_value/name=lot_square_feet', array($this, 'load_inherited_values'), 10, 3);
        add_filter('acf/load_value/name=lot_garage', array($this, 'load_inherited_values'), 10, 3);
        add_filter('acf/load_value/name=lot_stories', array($this, 'load_inherited_values'), 10, 3);
        add_filter('acf/load_value/name=lot_features', array($this, 'load_inherited_values'), 10, 3);

        // Add field instruction updates to show inherited values
        add_filter('acf/prepare_field', array($this, 'display_inherited_values'), 10, 2);
        
        // Handle orphaned lot reassignment
        add_action('acf/save_post', array($this, 'handle_lot_community_reassignment'), 20);
        
        // Add debug logging for ACF save operations
        add_action('acf/save_post', array($this, 'log_acf_save'), 5); // Priority 5 to log early
        add_action('acf/save_post', array($this, 'log_acf_save_complete'), 999); // Priority 999 to log at end
    }

    /**
     * Register Community ACF Fields
     */
    public function register_community_fields()
    {
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
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_community_city',
                    'label' => 'City',
                    'name' => 'community_city',
                    'type' => 'text',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_state',
                    'label' => 'State',
                    'name' => 'community_state',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                    'type' => 'text',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_zip',
                    'label' => 'ZIP Code',
                    'name' => 'community_zip',
                    'type' => 'text',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_community_latitude',
                    'label' => 'Latitude',
                    'name' => 'community_latitude',
                    'type' => 'text',
                    'instructions' => 'For map display (e.g., 40.7128)',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_longitude',
                    'label' => 'Longitude',
                    'name' => 'community_longitude',
                    'type' => 'text',
                    'instructions' => 'For map display (e.g., -74.0060)',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_total_lots',
                    'label' => 'Total Lots',
                    'name' => 'community_total_lots',
                    'wrapper' => array(
                        'width' => '50',
                    ),
                    'type' => 'number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_community_price_range',
                    'label' => 'Price Range',
                    'name' => 'community_price_range',
                    'type' => 'text',
                    'instructions' => 'e.g., $350,000 - $650,000',
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
    public function register_floor_plan_fields()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_floor_plan_details',
            'title' => 'Floor Plan Details',
            'fields' => array(
                // array(
                //     'key' => 'field_fp_community',
                //     'label' => 'Community',
                //     'name' => 'floor_plan_community',
                //     'type' => 'post_object',
                //     'instructions' => 'Select the community this floor plan belongs to',
                //     'required' => 1,
                //     'post_type' => array('bh_community'),
                //     'return_format' => 'id',
                //     'ui' => 1,
                // ),
                array(
                    'key' => 'field_fp_price',
                    'label' => 'Starting Price',
                    'name' => 'floor_plan_price',
                    'type' => 'text',
                    // 'instructions' => 'e.g., $450,000',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_fp_bedrooms',
                    'label' => 'Bedrooms',
                    'name' => 'floor_plan_bedrooms',
                    'type' => 'number',
                    'required' => 0,
                    'min' => 1,
                    'max' => 10,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_fp_bathrooms',
                    'label' => 'Bathrooms',
                    'name' => 'floor_plan_bathrooms',
                    'type' => 'text',
                    // 'instructions' => 'e.g., 2.5',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_fp_square_feet',
                    'label' => 'Square Feet',
                    'name' => 'floor_plan_square_feet',
                    'type' => 'number',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_fp_garage',
                    'label' => 'Garage',
                    'name' => 'floor_plan_garage',
                    'type' => 'text',
                    // 'instructions' => 'e.g., 2-Car Garage',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_fp_stories',
                    'label' => 'Stories',
                    'name' => 'floor_plan_stories',
                    'type' => 'number',
                    'required' => 0,
                    'min' => 1,
                    'max' => 3,
                    'wrapper' => array(
                        'width' => '50',
                    ),
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
                   array(
                    'key' => 'field_fp_brochure',
                    'label' => 'Brochure/PDF',
                    'name' => 'floor_plan_brochure',
                    'type' => 'file',
                    'instructions' => 'Upload floor plan brochure',
                    'required' => 0,
                    'return_format' => 'array',
                    'library' => 'all',
                    'mime_types' => 'pdf',
                       'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                   array(
                    'key' => 'field_fp_thumbnail',
                    'label' => 'Thumbnail',
                    'name' => 'floor_plan_thumbnail',
                    'type' => 'file',
                    'instructions' => 'Upload floor plan thumbnail',
                    'required' => 0,
                    'return_format' => 'array',
                    'library' => 'all',
                    'mime_types' => 'jpg,jpeg,png',
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
    public function register_lot_fields()
    {
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_lot_number',
                    'label' => 'Lot Number',
                    'name' => 'lot_number',
                    'type' => 'text',
                    'instructions' => 'e.g., Lot 15',
                    'required' => 1,
                       'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_lot_address',
                    'label' => 'Street Address',
                    'name' => 'lot_address',
                    'type' => 'text',
                    'instructions' => 'Specific street address for this lot (e.g., 123 Main Street)',
                    'required' => 0,
                       'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_lot_floor_plan',
                    'label' => 'Floor Plan',
                    'name' => 'lot_floor_plan',
                    'type' => 'post_object',
                    'instructions' => 'Select floor plan for this home (shows only floor plans from the selected community)',
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_lot_size',
                    'label' => 'Lot Size',
                    'name' => 'lot_size',
                    'type' => 'text',
                    'instructions' => 'e.g., 0.5 acres or 8,000 sq ft',
                    'required' => 0,
                    'wrapper' => array(
                        'width' => '50',
                    ),
                ),
                array(
                    'key' => 'field_lot_price',
                    'label' => 'Home Price',
                    'name' => 'lot_price',
                    'type' => 'text',
                    'instructions' => 'e.g., $485,000',
                    'wrapper' => array(
                        'width' => '50',    
                    ),
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
                       'wrapper' => array(
                        'width' => '50',
                    ),
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
    public function prepopulate_lot_community($value, $post_id, $field)
    {
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
    public function prepopulate_floor_plan_community($value, $post_id, $field)
    {
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
     * Filter floor plan options by community
     * Only show floor plans that belong to the same community as the lot
     */
    public function filter_floor_plans_by_community($args, $field, $post_id)
    {
        // Get the community ID for this lot
        $community_id = null;
        
        // For new posts, check URL parameter
        if ($post_id === 'new_post' && isset($_GET['community_id']) && !empty($_GET['community_id'])) {
            $community_id = intval($_GET['community_id']);
        } 
        // For existing posts, get from ACF field
        elseif ($post_id && $post_id !== 'new_post') {
            $community_id = get_field('lot_community', $post_id);
        }
        
        // If we have a community, filter floor plans by taxonomy
        if ($community_id) {
            $community_post = get_post($community_id);
            
            if ($community_post) {
                // Get the taxonomy term for this community
                $term_slug = sanitize_title($community_post->post_name);
                $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
                
                // Fallback to title-based lookup
                if (!$term) {
                    $term = get_term_by('name', $community_post->post_title, 'bh_floor_plan_community');
                }
                
                // Add tax_query to filter by community
                if ($term) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'bh_floor_plan_community',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                        ),
                    );
                }
            }
        }
        
        return $args;
    }

    /**
     * Get value from associated floor plan if not set on lot
     */
    public function get_inherited_value($lot_post_id, $field_name, $floor_plan_field_name = null)
    {
        if (!$floor_plan_field_name) {
            $floor_plan_field_name = $field_name;
        }

        // Get the lot's floor plan assignment
        $floor_plan_id = get_field('lot_floor_plan', $lot_post_id);

        if ($floor_plan_id) {
            // Get the value from the floor plan
            $floor_plan_value = get_field($floor_plan_field_name, $floor_plan_id);

            // If floor plan has a value and lot doesn't have its own value, use the floor plan's value
            $lot_value = get_field($field_name, $lot_post_id);

            if ($lot_value === false || $lot_value === null || $lot_value === '') {
                return $floor_plan_value;
            }
        }

        return get_field($field_name, $lot_post_id);
    }

    /**
     * Hook to display inherited values in admin
     */
    public function display_inherited_values($field)
    {
        // Get the post ID from the field or global context
        $post_id = isset($field['post_id']) ? $field['post_id'] : get_the_ID();

        if (!$post_id || get_post_type($post_id) !== 'bh_lot') {
            return $field;
        }

        $inherited_fields = array(
            'lot_bedrooms' => 'floor_plan_bedrooms',
            'lot_bathrooms' => 'floor_plan_bathrooms',
            'lot_square_feet' => 'floor_plan_square_feet',
            'lot_garage' => 'floor_plan_garage',
            'lot_stories' => 'floor_plan_stories',
            'lot_features' => 'floor_plan_features'
        );

        if (isset($inherited_fields[$field['name']])) {
            $floor_plan_field = $inherited_fields[$field['name']];
            $floor_plan_id = get_field('lot_floor_plan', $post_id);

            if ($floor_plan_id) {
                $floor_plan_value = get_field($floor_plan_field, $floor_plan_id);

                if ($floor_plan_value) {
                    $field['instructions'] = ($field['instructions'] ? $field['instructions'] . ' ' : '') .
                        '<em>Inherited from floor plan: ' . $floor_plan_value . '</em>';
                }
            }
        }

        return $field;
    }

    /**
     * Filter to load inherited values
     */
    public function load_inherited_values($value, $post_id, $field)
    {
        if (get_post_type($post_id) !== 'bh_lot') {
            return $value;
        }

        $inherited_fields = array(
            'lot_bedrooms' => 'floor_plan_bedrooms',
            'lot_bathrooms' => 'floor_plan_bathrooms',
            'lot_square_feet' => 'floor_plan_square_feet',
            'lot_garage' => 'floor_plan_garage',
            'lot_stories' => 'floor_plan_stories',
            'lot_features' => 'floor_plan_features'
        );

        if (isset($inherited_fields[$field['name']])) {
            $floor_plan_field = $inherited_fields[$field['name']];
            $floor_plan_id = get_field('lot_floor_plan', $post_id);

            if ($floor_plan_id) {
                $floor_plan_value = get_field($floor_plan_field, $floor_plan_id);

                // Only inherit if the lot doesn't have its own value
                if (($value === false || $value === null || $value === '') && $floor_plan_value) {
                    return $floor_plan_value;
                }
            }
        }

        return $value;
    }

    /**
     * Public method to get inherited value for use in templates
     */
    public function get_lot_inherited_value($post_id, $field_name)
    {
        $inherited_fields = array(
            'bedrooms' => 'floor_plan_bedrooms',
            'bathrooms' => 'floor_plan_bathrooms',
            'square_feet' => 'floor_plan_square_feet',
            'garage' => 'floor_plan_garage',
            'stories' => 'floor_plan_stories',
            'features' => 'floor_plan_features'
        );

        $lot_field_name = 'lot_' . $field_name;
        $floor_plan_field_name = isset($inherited_fields[$field_name]) ? $inherited_fields[$field_name] : 'floor_plan_' . $field_name;

        return $this->get_inherited_value($post_id, $lot_field_name, $floor_plan_field_name);
    }
    
    /**
     * Log ACF save operation start
     */
    public function log_acf_save($post_id) {
        // Skip for new posts or options pages
        if ($post_id === 'new_post' || $post_id === 'options') {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        // Only log for our custom post types
        if (!in_array($post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            return;
        }
        
        $post = get_post($post_id);
        error_log(sprintf(
            'Burgland Homes ACF: Starting ACF save for %s #%d (%s)',
            $post_type,
            $post_id,
            $post ? $post->post_title : 'Unknown'
        ));
        
        // Log what ACF fields are being saved
        if (isset($_POST['acf']) && is_array($_POST['acf'])) {
            $field_count = count($_POST['acf']);
            error_log(sprintf(
                'Burgland Homes ACF: Attempting to save %d ACF fields for post #%d',
                $field_count,
                $post_id
            ));
            
            // Log individual field names (but not values for security)
            foreach ($_POST['acf'] as $field_key => $field_value) {
                $field_obj = acf_get_field($field_key);
                if ($field_obj) {
                    error_log(sprintf(
                        'Burgland Homes ACF: Saving field "%s" (%s) for post #%d',
                        $field_obj['name'],
                        $field_obj['type'],
                        $post_id
                    ));
                }
            }
        } else {
            error_log(sprintf(
                'Burgland Homes ACF: No ACF data in $_POST for post #%d',
                $post_id
            ));
        }
    }
    
    /**
     * Log ACF save operation completion
     */
    public function log_acf_save_complete($post_id) {
        // Skip for new posts or options pages
        if ($post_id === 'new_post' || $post_id === 'options') {
            return;
        }
        
        $post_type = get_post_type($post_id);
        
        // Only log for our custom post types
        if (!in_array($post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            return;
        }
        
        error_log(sprintf(
            'Burgland Homes ACF: Completed ACF save for %s #%d',
            $post_type,
            $post_id
        ));
        
        // Verify fields were actually saved
        if (isset($_POST['acf']) && is_array($_POST['acf'])) {
            foreach ($_POST['acf'] as $field_key => $field_value) {
                $field_obj = acf_get_field($field_key);
                if ($field_obj) {
                    $saved_value = get_field($field_obj['name'], $post_id, false);
                    if ($saved_value !== false && $saved_value !== null) {
                        error_log(sprintf(
                            'Burgland Homes ACF: ✓ Field "%s" saved successfully for post #%d',
                            $field_obj['name'],
                            $post_id
                        ));
                    } else {
                        error_log(sprintf(
                            'Burgland Homes ACF: ✗ WARNING: Field "%s" may not have saved for post #%d',
                            $field_obj['name'],
                            $post_id
                        ));
                    }
                }
            }
        }
    }
    
    /**
     * Handle lot community reassignment - clear orphaned flags
     */
    public function handle_lot_community_reassignment($post_id) {
        // Only for lot post type
        if (get_post_type($post_id) !== 'bh_lot') {
            return;
        }
        
        // Check if this lot was orphaned
        $is_orphaned = get_post_meta($post_id, '_bh_orphaned_lot', true);
        
        if (!$is_orphaned) {
            return;
        }
        
        // Check if a community has been assigned
        $community_id = get_post_meta($post_id, 'lot_community', true);
        
        if ($community_id && get_post_status($community_id) === 'publish') {
            // Clear orphaned flags
            delete_post_meta($post_id, '_bh_orphaned_lot');
            delete_post_meta($post_id, '_bh_deleted_community_id');
            
            // Log the recovery
            error_log(sprintf(
                'Burgland Homes: Lot #%d orphaned flags cleared after reassignment to community #%d',
                $post_id,
                $community_id
            ));
        }
    }
}
