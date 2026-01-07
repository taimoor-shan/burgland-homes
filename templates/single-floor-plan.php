<?php
/**
 * Single Floor Plan Template
 * 
 * Template for displaying individual floor plan posts
 * 
 * @package Burgland_Homes
 */

get_header();

// Get custom fields
$community_id = get_post_meta(get_the_ID(), 'floor_plan_community', true);
$price = get_post_meta(get_the_ID(), 'floor_plan_price', true);
$bedrooms = get_post_meta(get_the_ID(), 'floor_plan_bedrooms', true);
$bathrooms = get_post_meta(get_the_ID(), 'floor_plan_bathrooms', true);
$square_feet = get_post_meta(get_the_ID(), 'floor_plan_square_feet', true);
$features = get_post_meta(get_the_ID(), 'floor_plan_features', true);
$garage = get_post_meta(get_the_ID(), 'floor_plan_garage', true);
$stories = get_post_meta(get_the_ID(), 'floor_plan_stories', true);

// Parse features if it's a string
if (is_string($features)) {
    $features = array_filter(array_map('trim', explode("\n", $features)));
}
?>

<main id="site-main">
  <?php if (have_posts()): while (have_posts()): the_post(); ?>

      <article <?php post_class('floor-plan-single'); ?>>
        
        <!-- Page Header -->
        <section class="page-header bg-light py-5">
          <div class="container">
            <div class="row align-items-center">
              <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-3">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo home_url(); ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo get_post_type_archive_link('bh_floor_plan'); ?>">Floor Plans</a></li>
                    <?php if ($community_id): $community = get_post($community_id); ?>
                      <li class="breadcrumb-item"><a href="<?php echo get_permalink($community_id); ?>"><?php echo esc_html($community->post_title); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?php the_title(); ?></li>
                  </ol>
                </nav>
                <h1 class="display-4 fw-light mb-3"><?php the_title(); ?></h1>
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
            <div class=\"row g-5\">
              
              <!-- Floor Plan Image -->
              <div class="col-lg-8">
                <?php if (has_post_thumbnail()): ?>
                  <div class="mb-4">
                    <?php the_post_thumbnail('large', array('class' => 'img-fluid rounded shadow')); ?>
                  </div>
                <?php endif; ?>
                
                <!-- Specifications -->
                <?php if ($bedrooms || $bathrooms || $square_feet): ?>
                  <div class="card mb-4">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Specifications</h2>
                      <div class="row g-4">
                        <?php if ($bedrooms): ?>
                          <div class="col-md-4">
                            <div class="d-flex align-items-center gap-3">
                              <i class="bi bi-house-door fs-3 text-primary"></i>
                              <div>
                                <p class="small text-muted mb-0">Bedrooms</p>
                                <p class="h5 mb-0"><?php echo esc_html($bedrooms); ?></p>
                              </div>
                            </div>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($bathrooms): ?>
                          <div class="col-md-4">
                            <div class="d-flex align-items-center gap-3">
                              <i class="bi bi-droplet fs-3 text-primary"></i>
                              <div>
                                <p class="small text-muted mb-0">Bathrooms</p>
                                <p class="h5 mb-0"><?php echo esc_html($bathrooms); ?></p>
                              </div>
                            </div>
                          </div>
                        <?php endif; ?>
                        
                        <?php if ($square_feet): ?>
                          <div class="col-md-4">
                            <div class="d-flex align-items-center gap-3">
                              <i class="bi bi-arrows-angle-expand fs-3 text-primary"></i>
                              <div>
                                <p class="small text-muted mb-0">Square Feet</p>
                                <p class="h5 mb-0"><?php echo esc_html(number_format($square_feet)); ?></p>
                              </div>
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
                    <h2 class="h5 card-title mb-3">Description</h2>
                    <div class="content">
                      <?php the_content(); ?>
                    </div>
                  </div>
                </div>
                
                <!-- Features -->
                <?php if (!empty($features) && is_array($features)): ?>
                  <div class="card">
                    <div class="card-body">
                      <h2 class="h5 card-title mb-3">Features & Amenities</h2>
                      <div class="row">
                        <?php foreach ($features as $feature): ?>
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
                    <h3 class="h5 card-title mb-4">Interested in this floor plan?</h3>
                    <div class="d-grid gap-3">
                      <a href="#contact-form" class="btn btn-primary btn-lg">Schedule a Tour</a>
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
                        <a href="<?php echo get_post_type_archive_link('bh_floor_plan'); ?>" class="btn btn-outline-secondary">
                          <i class="bi bi-arrow-left me-2"></i>
                          Back to Floor Plans
                        </a>
                      <?php endif; ?>
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
