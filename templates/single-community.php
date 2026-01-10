<?php
/**
 * Single Community Template
 * 
 * Template for displaying individual community posts
 * 
 * @package Burgland_Homes
 */

get_header();

// Get custom fields
$address = get_post_meta(get_the_ID(), 'community_address', true);
$city = get_post_meta(get_the_ID(), 'community_city', true);
$state = get_post_meta(get_the_ID(), 'community_state', true);
$zip = get_post_meta(get_the_ID(), 'community_zip', true);
$latitude = get_post_meta(get_the_ID(), 'community_latitude', true);
$longitude = get_post_meta(get_the_ID(), 'community_longitude', true);
$total_lots = get_post_meta(get_the_ID(), 'community_total_lots', true);
$price_range = get_post_meta(get_the_ID(), 'community_price_range', true);
$amenities = get_post_meta(get_the_ID(), 'community_amenities', true);
$video_url = get_post_meta(get_the_ID(), 'community_video_url', true);
$brochure = get_post_meta(get_the_ID(), 'community_brochure', true);

// Parse amenities if it's a string
if (is_string($amenities)) {
    $amenities = array_filter(array_map('trim', explode("\n", $amenities)));
}

// Get community status
$status_terms = wp_get_post_terms(get_the_ID(), 'bh_community_status');
$status_label = '';
$status_class = 'primary';

if (!empty($status_terms) && !is_wp_error($status_terms)) {
    $status_label = $status_terms[0]->name;
    $status = $status_terms[0]->slug;
    
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

// Get related floor plans
$floor_plans = new WP_Query(array(
    'post_type' => 'bh_floor_plan',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'floor_plan_community',
            'value' => get_the_ID(),
        ),
    ),
));

// Get available lots
$available_lots = new WP_Query(array(
    'post_type' => 'bh_lot',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => 'lot_community',
            'value' => get_the_ID(),
        ),
    ),
    'tax_query' => array(
        array(
            'taxonomy' => 'bh_lot_status',
            'field' => 'slug',
            'terms' => 'available',
        ),
    ),
));
?>

<main id="site-main">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>

      <article <?php post_class('community-single'); ?>>
        
        <!-- Page Header -->
        <section class="mt-10 bg-light py-5">
          <div class="container">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-3">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo home_url(); ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo get_post_type_archive_link('bh_community'); ?>">Communities</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php the_title(); ?></li>
                  </ol>
                </nav>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <h1 class="display-4 fw-light mb-0"><?php the_title(); ?></h1>
                  <?php if ($status_label): ?>
                    <span class="badge bg-<?php echo esc_attr($status_class); ?> text-uppercase fs-6"><?php echo esc_html($status_label); ?></span>
                  <?php endif; ?>
                </div>
                <?php if ($city && $state): ?>
                  <p class="lead text-muted">
                    <i class="bi bi-geo-alt"></i>
                    <?php echo esc_html($city . ', ' . $state); ?>
                  </p>
                <?php endif; ?>
                <?php if ($price_range): ?>
                  <p class="h4 text-primary fw-bold"><?php echo esc_html($price_range); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- Featured Image -->
        <?php if (has_post_thumbnail()): ?>
          <section class="community-hero">
            <div class="container-fluid px-0">
              <?php the_post_thumbnail('full', array('class' => 'img-fluid w-100', 'style' => 'max-height: 500px; object-fit: cover;')); ?>
            </div>
          </section>
        <?php endif; ?>

        <!-- Main Content -->
        <section class="py-5">
          <div class="container">
            <div class="row g-5">
              
              <!-- Left Column -->
              <div class="col-lg-8">
                
                <!-- Community Stats -->
                <?php if ($total_lots || $floor_plans->found_posts || $available_lots->found_posts): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Community Overview</h2>
                      <div class="row g-4 text-center">
                        <?php if ($total_lots): ?>
                          <div class="col-md-4">
                            <div class="p-3">
                              <i class="bi bi-geo-alt fs-1 text-primary d-block mb-2"></i>
                              <p class="h3 mb-1"><?php echo esc_html($total_lots); ?></p>
                              <p class="small text-muted mb-0">Total Lots</p>
                            </div>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($floor_plans->found_posts): ?>
                          <div class="col-md-4">
                            <div class="p-3">
                              <i class="bi bi-layout fs-1 text-success d-block mb-2"></i>
                              <p class="h3 mb-1"><?php echo esc_html($floor_plans->found_posts); ?></p>
                              <p class="small text-muted mb-0">Floor Plans</p>
                            </div>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($available_lots->found_posts): ?>
                          <div class="col-md-4">
                            <div class="p-3">
                              <i class="bi bi-check2-circle fs-1 text-info d-block mb-2"></i>
                              <p class="h3 mb-1"><?php echo esc_html($available_lots->found_posts); ?></p>
                              <p class="small text-muted mb-0">Available Now</p>
                            </div>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                
                <!-- Description -->
                <div class="card mb-4">
                  <div class="card-body">
                    <h2 class="h5 card-title mb-3">About This Community</h2>
                    <div class="content">
                      <?php the_content(); ?>
                    </div>
                  </div>
                </div>
                
                <!-- Map Integration Hook -->
                <?php if ($latitude && $longitude): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Location</h2>
                      <div id="community-map" 
                           data-lat="<?php echo esc_attr($latitude); ?>" 
                           data-lng="<?php echo esc_attr($longitude); ?>"
                           data-address="<?php echo esc_attr($address); ?>"
                           style="height: 400px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                          <p class="text-muted mb-2">
                            <i class="bi bi-geo-alt fs-3 d-block mb-2"></i>
                            <?php if ($address): ?>
                              <?php echo esc_html($address); ?><br>
                            <?php endif; ?>
                            <?php echo esc_html($city . ', ' . $state . ' ' . $zip); ?>
                          </p>
                          <small class="text-muted">Map integration: Add your preferred map service here (Google Maps, Mapbox, Leaflet, etc.)</small>
                        </div>
                      </div>
                      <?php
                      // Hook for map integration
                      do_action('burgland_homes_after_community_map', get_the_ID(), $latitude, $longitude);
                      ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <!-- Amenities -->
                <?php if (!empty($amenities) && is_array($amenities)): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Community Amenities</h2>
                      <div class="row">
                        <?php foreach ($amenities as $amenity): ?>
                          <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center gap-2">
                              <i class="bi bi-check2-circle text-success"></i>
                              <span><?php echo esc_html($amenity); ?></span>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                
                <!-- Video -->
                <?php if ($video_url): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Community Video</h2>
                      <div class="ratio ratio-16x9">
                        <?php echo wp_oembed_get($video_url); ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                
                <!-- Floor Plans -->
                <?php if ($floor_plans->have_posts()): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-4">Available Floor Plans</h2>
                      <div class="row g-4">
                        <?php while ($floor_plans->have_posts()): $floor_plans->the_post(); 
                          $fp_bedrooms = get_post_meta(get_the_ID(), 'floor_plan_bedrooms', true);
                          $fp_bathrooms = get_post_meta(get_the_ID(), 'floor_plan_bathrooms', true);
                          $fp_sqft = get_post_meta(get_the_ID(), 'floor_plan_square_feet', true);
                          $fp_price = get_post_meta(get_the_ID(), 'floor_plan_price', true);
                        ?>
                          <div class="col-md-6">
                            <div class="card h-100">
                              <?php if (has_post_thumbnail()): ?>
                                <a href="<?php the_permalink(); ?>">
                                  <?php the_post_thumbnail('medium', array('class' => 'card-img-top')); ?>
                                </a>
                              <?php endif; ?>
                              <div class="card-body">
                                <h3 class="h6 card-title">
                                  <a href="<?php the_permalink(); ?>" class="text-decoration-none"><?php the_title(); ?></a>
                                </h3>
                                <?php if ($fp_price): ?>
                                  <p class="text-primary fw-bold mb-2"><?php echo esc_html($fp_price); ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-3 text-muted small">
                                  <?php if ($fp_bedrooms): ?>
                                    <span><i class="bi bi-house-door"></i> <?php echo esc_html($fp_bedrooms); ?> Bed</span>
                                  <?php endif; ?>
                                  <?php if ($fp_bathrooms): ?>
                                    <span><i class="bi bi-droplet"></i> <?php echo esc_html($fp_bathrooms); ?> Bath</span>
                                  <?php endif; ?>
                                  <?php if ($fp_sqft): ?>
                                    <span><i class="bi bi-arrows-angle-expand"></i> <?php echo esc_html(number_format($fp_sqft)); ?> sq ft</span>
                                  <?php endif; ?>
                                </div>
                              </div>
                              <div class="card-footer bg-transparent">
                                <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                              </div>
                            </div>
                          </div>
                        <?php endwhile; wp_reset_postdata(); ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                
              </div>
              
              <!-- Sidebar -->
              <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                  
                  <!-- Contact Card -->
                  <div class="card mb-4">
                    <div class="card-body">
                      <h3 class="h5 card-title mb-4">Interested in This Community?</h3>
                      <div class="d-grid gap-3">
                        <a href="#contact-form" class="btn btn-primary btn-lg">Schedule a Visit</a>
                        <a href="tel:8005550192" class="btn btn-outline-primary">
                          <i class="bi bi-telephone me-2"></i>
                          (800) 555-0192
                        </a>
                        <?php if ($brochure && is_array($brochure)): ?>
                          <a href="<?php echo esc_url($brochure['url']); ?>" class="btn btn-outline-secondary" download>
                            <i class="bi bi-download me-2"></i>
                            Download Brochure
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Quick Info -->
                  <div class="card">
                    <div class="card-body">
                      <h3 class="h6 card-title mb-3">Quick Info</h3>
                      <ul class="list-unstyled mb-0">
                        <?php if ($available_lots->found_posts): ?>
                          <li class="mb-2">
                            <strong>Available Lots:</strong> <?php echo esc_html($available_lots->found_posts); ?>
                          </li>
                        <?php endif; ?>
                        <?php if ($price_range): ?>
                          <li class="mb-2">
                            <strong>Price Range:</strong> <?php echo esc_html($price_range); ?>
                          </li>
                        <?php endif; ?>
                        <?php if ($floor_plans->found_posts): ?>
                          <li class="mb-2">
                            <strong>Floor Plans:</strong> <?php echo esc_html($floor_plans->found_posts); ?>
                          </li>
                        <?php endif; ?>
                      </ul>
                    </div>
                  </div>
                  
                </div>
              </div>
              
            </div>
          </div>
        </section>

      </article>

  <?php endwhile; endif; ?>
</main>

<?php
get_footer();
?>
