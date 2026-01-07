<?php
/**
 * Archive Community Template
 * 
 * Template for displaying all communities with filters and map
 * 
 * @package Burgland_Homes
 */

get_header();

// Get all community status terms
$status_terms = get_terms(array(
    'taxonomy' => 'bh_community_status',
    'hide_empty' => false,
));

// Get filter values from URL
$selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$selected_price = isset($_GET['price_range']) ? sanitize_text_field($_GET['price_range']) : '';
?>

<main id="site-main">
  <div class="communities-archive">
    
    <!-- Page Header -->
    <section class="page-header bg-light py-4">
      <div class="container">
        <h1 class="display-5 fw-light mb-2">Our Communities</h1>
        <p class="lead text-muted mb-0">Discover your perfect home in one of our beautiful communities</p>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section bg-white border-bottom py-4">
      <div class="container">
        <form id="community-filters" class="row g-3 align-items-end">
          
          <!-- Status Filter -->
          <div class="col-md-6">
            <label for="status-filter" class="form-label fw-semibold">Community Status</label>
            <select name="status" id="status-filter" class="form-select">
              <option value="">All Communities</option>
              <?php if (!empty($status_terms) && !is_wp_error($status_terms)): ?>
                <?php foreach ($status_terms as $term): ?>
                  <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected_status, $term->slug); ?>>
                    <?php echo esc_html($term->name); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <!-- Price Range Filter -->
          <div class="col-md-6">
            <label for="price-filter" class="form-label fw-semibold">Price Range</label>
            <select name="price_range" id="price-filter" class="form-select">
              <option value="">All Price Ranges</option>
              <option value="under-300k" <?php selected($selected_price, 'under-300k'); ?>>Under $300,000</option>
              <option value="300k-500k" <?php selected($selected_price, '300k-500k'); ?>>$300,000 - $500,000</option>
              <option value="over-500k" <?php selected($selected_price, 'over-500k'); ?>>Over $500,000</option>
            </select>
          </div>

        </form>
      </div>
    </section>

    <!-- Main Content: Two Column Layout -->
    <section class="communities-content py-5">
      <div class="container-fluid">
        <div class="row g-4">
          
          <!-- Left Column: Community Cards -->
          <div class="col-lg-6">
            <div id="communities-grid" class="communities-grid">
              <div class="loading-spinner text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3">Loading communities...</p>
              </div>
              
              <div id="communities-list" class="row g-4">
                <?php
                // Build query args
                $query_args = array(
                    'post_type' => 'bh_community',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC',
                );

                // Add status filter
                if ($selected_status) {
                    $query_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'bh_community_status',
                            'field' => 'slug',
                            'terms' => $selected_status,
                        ),
                    );
                }

                // Add price range meta query
                if ($selected_price) {
                    $query_args['meta_query'] = array(
                        array(
                            'key' => 'community_price_range',
                            'value' => '',
                            'compare' => '!=',
                        ),
                    );
                }

                $communities = new WP_Query($query_args);

                if ($communities->have_posts()):
                    while ($communities->have_posts()): $communities->the_post();
                        // Get custom fields
                        $city = get_post_meta(get_the_ID(), 'community_city', true);
                        $state = get_post_meta(get_the_ID(), 'community_state', true);
                        $price_range = get_post_meta(get_the_ID(), 'community_price_range', true);
                        $latitude = get_post_meta(get_the_ID(), 'community_latitude', true);
                        $longitude = get_post_meta(get_the_ID(), 'community_longitude', true);

                        // Get status
                        $status_terms_post = wp_get_post_terms(get_the_ID(), 'bh_community_status');
                        $status_label = '';
                        $status_class = 'primary';
                        
                        if (!empty($status_terms_post) && !is_wp_error($status_terms_post)) {
                            $status_label = $status_terms_post[0]->name;
                            $status = $status_terms_post[0]->slug;
                            
                            switch ($status) {
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

                        // Filter by price range if selected
                        if ($selected_price && $price_range) {
                            $skip = false;
                            $price_numeric = preg_replace('/[^0-9]/', '', $price_range);
                            $price_numeric = intval(substr($price_numeric, 0, 6)); // Get first price in range
                            
                            if ($selected_price === 'under-300k' && $price_numeric >= 300000) {
                                $skip = true;
                            } elseif ($selected_price === '300k-500k' && ($price_numeric < 300000 || $price_numeric > 500000)) {
                                $skip = true;
                            } elseif ($selected_price === 'over-500k' && $price_numeric <= 500000) {
                                $skip = true;
                            }
                            
                            if ($skip) continue;
                        }
                ?>
                        <div class="col-md-6 community-card-wrapper" 
                             data-lat="<?php echo esc_attr($latitude); ?>" 
                             data-lng="<?php echo esc_attr($longitude); ?>"
                             data-id="<?php echo get_the_ID(); ?>">
                          <div class="card community-card h-100 shadow-sm">
                            <?php if (has_post_thumbnail()): ?>
                              <div class="position-relative">
                                <a href="<?php the_permalink(); ?>">
                                  <?php the_post_thumbnail('medium_large', array('class' => 'card-img-top community-card-img')); ?>
                                </a>
                                <?php if ($status_label): ?>
                                  <span class="badge bg-<?php echo esc_attr($status_class); ?> position-absolute top-0 end-0 m-3">
                                    <?php echo esc_html($status_label); ?>
                                  </span>
                                <?php endif; ?>
                              </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                              <h3 class="card-title h5 mb-2">
                                <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark stretched-link">
                                  <?php the_title(); ?>
                                </a>
                              </h3>
                              
                              <?php if ($city && $state): ?>
                                <p class="card-text text-muted mb-2">
                                  <i class="bi bi-geo-alt-fill"></i>
                                  <?php echo esc_html($city . ', ' . $state); ?>
                                </p>
                              <?php endif; ?>
                              
                              <?php if ($price_range): ?>
                                <p class="card-text text-primary fw-semibold mb-2">
                                  <?php echo esc_html($price_range); ?>
                                </p>
                              <?php endif; ?>
                              
                              <?php if (has_excerpt()): ?>
                                <p class="card-text text-muted small mb-3">
                                  <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </p>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                ?>
                    <div class="col-12">
                      <div class="alert alert-info text-center" role="alert">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        <p class="mb-0">No communities found matching your criteria. Please adjust your filters.</p>
                      </div>
                    </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Right Column: Map -->
          <div class="col-lg-6">
            <div class="map-container sticky-top" style="top: 20px;">
              <div id="communities-map" style="height: calc(100vh - 100px); min-height: 600px; background: #e9ecef; border-radius: 8px;">
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                  <div class="text-center">
                    <i class="bi bi-map fs-1 d-block mb-3"></i>
                    <p>Map loading...</p>
                    <small>Please ensure you have added the Google Maps API key</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

  </div>
</main>

<?php get_footer(); ?>
