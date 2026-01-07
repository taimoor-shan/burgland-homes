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
                
                // Get custom fields
                $city = get_post_meta(get_the_ID(), 'community_city', true);
                $state = get_post_meta(get_the_ID(), 'community_state', true);
                $community_price_range = get_post_meta(get_the_ID(), 'community_price_range', true);
                $latitude = get_post_meta(get_the_ID(), 'community_latitude', true);
                $longitude = get_post_meta(get_the_ID(), 'community_longitude', true);
                
                // Get status
                $status_terms = wp_get_post_terms(get_the_ID(), 'bh_community_status');
                $status_label = '';
                $status_class = 'primary';
                
                if (!empty($status_terms) && !is_wp_error($status_terms)) {
                    $status_label = $status_terms[0]->name;
                    $status_slug = $status_terms[0]->slug;
                    
                    switch ($status_slug) {
                        case 'active':
                            $status_class = 'success';
                            break;
                        case 'selling-fast':
                            $status_class = 'warning';
                            break;
                        case 'sold-out':
                            $status_class = 'secondary';
                            break;
                        case 'coming-soon':
                            $status_class = 'info';
                            break;
                    }
                }
                
                // Filter by price range
                if (!empty($price_range) && !empty($community_price_range)) {
                    $skip = $this->should_skip_by_price($price_range, $community_price_range);
                    if ($skip) {
                        continue;
                    }
                }
                
                // Render community card
                $this->render_community_card(array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail('medium_large', array('class' => 'card-img-top community-card-img')),
                    'city' => $city,
                    'state' => $state,
                    'price_range' => $community_price_range,
                    'excerpt' => get_the_excerpt(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status_label' => $status_label,
                    'status_class' => $status_class,
                ));
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
    
    /**
     * Render community card HTML
     */
    private function render_community_card($data) {
        ?>
        <div class="col-md-6 community-card-wrapper" 
             data-lat="<?php echo esc_attr($data['latitude']); ?>" 
             data-lng="<?php echo esc_attr($data['longitude']); ?>"
             data-id="<?php echo esc_attr($data['id']); ?>">
          <div class="card community-card h-100 shadow-sm">
            <?php if (!empty($data['thumbnail'])): ?>
              <div class="position-relative">
                <a href="<?php echo esc_url($data['permalink']); ?>">
                  <?php echo $data['thumbnail']; ?>
                </a>
                <?php if (!empty($data['status_label'])): ?>
                  <span class="badge bg-<?php echo esc_attr($data['status_class']); ?> position-absolute top-0 end-0 m-3">
                    <?php echo esc_html($data['status_label']); ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <div class="card-body d-flex flex-column">
              <h3 class="card-title h5 mb-2">
                <a href="<?php echo esc_url($data['permalink']); ?>" class="text-decoration-none text-dark stretched-link">
                  <?php echo esc_html($data['title']); ?>
                </a>
              </h3>
              
              <?php if (!empty($data['city']) && !empty($data['state'])): ?>
                <p class="card-text text-muted mb-2">
                  <i class="bi bi-geo-alt-fill"></i>
                  <?php echo esc_html($data['city'] . ', ' . $data['state']); ?>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($data['price_range'])): ?>
                <p class="card-text text-primary fw-semibold mb-2">
                  <?php echo esc_html($data['price_range']); ?>
                </p>
              <?php endif; ?>
              
              <?php if (!empty($data['excerpt'])): ?>
                <p class="card-text text-muted small mb-3">
                  <?php echo wp_trim_words($data['excerpt'], 15); ?>
                </p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php
    }
}
