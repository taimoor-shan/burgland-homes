<?php
/**
 * Communities Filter Handler
 * Handles AJAX filtering for communities archive page
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Communities_Filter
 */
class Burgland_Homes_Communities_Filter {
    
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
        // Register AJAX handlers
        add_action('wp_ajax_filter_communities', array($this, 'filter_communities'));
        add_action('wp_ajax_nopriv_filter_communities', array($this, 'filter_communities'));
    }
    
    /**
     * Handle AJAX filter request
     */
    public function filter_communities() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'burgland_communities_filter')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Get filter parameters
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $price_range = isset($_POST['price_range']) ? sanitize_text_field($_POST['price_range']) : '';
        
        // Build query arguments
        $query_args = array(
            'post_type' => 'bh_community',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        // Add status filter
        if (!empty($status)) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'bh_community_status',
                    'field' => 'slug',
                    'terms' => $status,
                ),
            );
        }
        
        // Add price range meta query
        if (!empty($price_range)) {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'community_price_range',
                    'value' => '',
                    'compare' => '!=',
                ),
            );
        }
        
        // Execute query
        $communities = new WP_Query($query_args);
        
        // Generate HTML
        $html = '';
        
        if ($communities->have_posts()) {
            ob_start();
            
            while ($communities->have_posts()) {
                $communities->the_post();
                
                // Get price range for filtering
                $community_price_range = get_post_meta(get_the_ID(), 'community_price_range', true);
                
                // Filter by price range
                if (!empty($price_range) && !empty($community_price_range)) {
                    $skip = $this->should_skip_by_price($price_range, $community_price_range);
                    if ($skip) {
                        continue;
                    }
                }

                // Get custom fields for map data
                $latitude = get_post_meta(get_the_ID(), 'community_latitude', true);
                $longitude = get_post_meta(get_the_ID(), 'community_longitude', true);
                ?>
                <div class="col-md-6 community-card-wrapper" 
                     data-lat="<?php echo esc_attr($latitude); ?>" 
                     data-lng="<?php echo esc_attr($longitude); ?>"
                     data-id="<?php echo esc_attr(get_the_ID()); ?>">
                    <?php Burgland_Homes_Template_Loader::get_instance()->render_card(get_the_ID()); ?>
                </div>
                <?php
            }
            
            $html = ob_get_clean();
            wp_reset_postdata();
        } else {
            // No communities found
            $html = '<div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    <p class="mb-0">No communities found matching your criteria. Please adjust your filters.</p>
                </div>
            </div>';
        }
        
        // Send success response
        wp_send_json_success(array(
            'html' => $html,
            'count' => $communities->found_posts,
        ));
    }
    
    /**
     * Check if community should be skipped based on price range filter
     */
    private function should_skip_by_price($price_range_filter, $community_price_range) {
        // Extract numeric value from price range
        $price_numeric = preg_replace('/[^0-9]/', '', $community_price_range);
        
        // Get the first price in the range (starting price)
        if (strlen($price_numeric) >= 6) {
            $price_numeric = intval(substr($price_numeric, 0, 6));
        } else {
            // If no valid price, don't skip
            return false;
        }
        
        // Apply filter logic
        switch ($price_range_filter) {
            case 'under-300k':
                return $price_numeric >= 300000;
                
            case '300k-500k':
                return $price_numeric < 300000 || $price_numeric > 500000;
                
            case 'over-500k':
                return $price_numeric <= 500000;
                
            default:
                return false;
        }
    }
    
}
