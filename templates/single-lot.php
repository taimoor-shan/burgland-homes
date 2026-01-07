<?php
/**
 * Single Lot Template
 * 
 * Template for displaying individual lot posts
 * 
 * @package Burgland_Homes
 */

get_header();

// Get custom fields
$community_id = get_post_meta(get_the_ID(), 'lot_community', true);
$floor_plan_id = get_post_meta(get_the_ID(), 'lot_floor_plan', true);
$lot_number = get_post_meta(get_the_ID(), 'lot_number', true) ?: get_the_title();
$lot_size = get_post_meta(get_the_ID(), 'lot_size', true);
$price = get_post_meta(get_the_ID(), 'lot_price', true);
$lot_features = get_post_meta(get_the_ID(), 'lot_features', true);
$premium = get_post_meta(get_the_ID(), 'lot_premium', true);
$availability_date = get_post_meta(get_the_ID(), 'lot_availability_date', true);

// Parse features if it's a string
if (is_string($lot_features)) {
    $lot_features = array_filter(array_map('trim', explode("\n", $lot_features)));
}

// Get lot status
$status_terms = wp_get_post_terms(get_the_ID(), 'bh_lot_status');
$status = 'available';
$status_label = 'Available';
$status_class = 'success';

if (!empty($status_terms) && !is_wp_error($status_terms)) {
    $status = $status_terms[0]->slug;
    $status_label = $status_terms[0]->name;
    
    switch ($status) {
        case 'available':
            $status_class = 'success';
            break;
        case 'reserved':
            $status_class = 'warning';
            break;
        case 'sold':
            $status_class = 'secondary';
            break;
        default:
            $status_class = 'primary';
    }
}
?>

<main id="site-main">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>

      <article <?php post_class('lot-single'); ?>>
        
        <!-- Page Header -->
        <section class="page-header bg-light py-5">
          <div class="container">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-3">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo home_url(); ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo get_post_type_archive_link('bh_lot'); ?>">Available Lots</a></li>
                    <?php if ($community_id): $community = get_post($community_id); ?>
                      <li class="breadcrumb-item"><a href="<?php echo get_permalink($community_id); ?>"><?php echo esc_html($community->post_title); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($lot_number); ?></li>
                  </ol>
                </nav>
                <div class="d-flex align-items-center gap-3 mb-3">
                  <h1 class="display-4 fw-light mb-0"><?php echo esc_html($lot_number); ?></h1>
                  <span class="badge bg-<?php echo esc_attr($status_class); ?> text-uppercase fs-6"><?php echo esc_html($status_label); ?></span>
                </div>
                <?php if ($price): ?>
                  <p class="h3 text-primary fw-bold"><?php echo esc_html($price); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <!-- Main Content -->
        <section class="py-5">
          <div class="container">
            <div class="row g-5">
              
              <!-- Lot Details -->
              <div class="col-lg-8">
                <?php if (has_post_thumbnail()): ?>
                  <div class="mb-4">
                    <?php the_post_thumbnail('large', array('class' => 'img-fluid rounded shadow')); ?>
                  </div>
                <?php endif; ?>
                
                <!-- Lot Information -->
                <div class="card mb-4">
                  <div class="card-body">
                    <h2 class="h5 card-title mb-3">Lot Information</h2>
                    <div class="row g-4">
                      <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3">
                          <i class="bi bi-geo-alt fs-3 text-primary"></i>
                          <div>
                            <p class="small text-muted mb-0">Lot Number</p>
                            <p class="h5 mb-0"><?php echo esc_html($lot_number); ?></p>
                          </div>
                        </div>
                      </div>
                      
                      <?php if ($lot_size): ?>
                        <div class="col-md-6">
                          <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-arrows-angle-expand fs-3 text-primary"></i>
                            <div>
                              <p class="small text-muted mb-0">Lot Size</p>
                              <p class="h5 mb-0"><?php echo esc_html($lot_size); ?></p>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <?php if ($price): ?>
                        <div class="col-md-6">
                          <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-currency-dollar fs-3 text-primary"></i>
                            <div>
                              <p class="small text-muted mb-0">Price</p>
                              <p class="h5 mb-0"><?php echo esc_html($price); ?></p>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3">
                          <i class="bi bi-tag fs-3 text-primary"></i>
                          <div>
                            <p class="small text-muted mb-0">Status</p>
                            <p class="h5 mb-0">
                              <span class="badge bg-<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Description -->
                <div class="card mb-4">
                  <div class="card-body">
                    <h2 class="h5 card-title mb-3">Description</h2>
                    <div class="content">
                      <?php the_content(); ?>
                    </div>
                  </div>
                </div>
                
                <!-- Lot Features -->
                <?php if (!empty($lot_features) && is_array($lot_features)): ?>
                  <div class="card">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Lot Features</h2>
                      <div class="row">
                        <?php foreach ($lot_features as $feature): ?>
                          <div class="col-md-6 mb-2">
                            <div class="d-flex align-items-center gap-2">
                              <i class="bi bi-check2-circle text-success"></i>
                              <span><?php echo esc_html($feature); ?></span>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
              
              <!-- Sidebar -->
              <div class="col-lg-4">
                <div class="card sticky-top" style="top: 100px;">
                  <div class="card-body">
                    <?php if ($status === 'available'): ?>
                      <h3 class="h5 card-title mb-4">Interested in this lot?</h3>
                      <div class="d-grid gap-3">
                        <a href="#contact-form" class="btn btn-primary btn-lg">Reserve Now</a>
                        <a href="tel:8005550192" class="btn btn-outline-primary">
                          <i class="bi bi-telephone me-2"></i>
                          (800) 555-0192
                        </a>
                      <?php if ($community_id): ?>
                        <a href="<?php echo get_permalink($community_id); ?>" class="btn btn-outline-secondary">
                          <i class="bi bi-arrow-left me-2"></i>
                          Back to Community
                        </a>
                      <?php else: ?>
                        <a href="<?php echo get_post_type_archive_link('bh_lot'); ?>" class="btn btn-outline-secondary">
                          <i class="bi bi-arrow-left me-2"></i>
                          Back to All Lots
                        </a>
                      <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <h3 class="h5 card-title mb-4">This lot is <?php echo esc_html(strtolower($status_label)); ?></h3>
                      <p class="text-muted mb-4">Please contact us for information about other available lots.</p>
                      <div class="d-grid gap-3">
                        <a href="tel:8005550192" class="btn btn-primary">
                          <i class="bi bi-telephone me-2"></i>
                          (800) 555-0192
                        </a>
                        <a href="<?php echo get_post_type_archive_link('bh_lot'); ?>" class="btn btn-outline-secondary">
                          <i class="bi bi-arrow-left me-2"></i>
                          View Available Lots
                        </a>
                      </div>
                    <?php endif; ?>
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
