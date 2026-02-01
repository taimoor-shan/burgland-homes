<?php

/**
 * Single Floor Plan Template
 * 
 * @package Burgland_Homes
 */

get_header();

$data_provider = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();
$post_id = get_the_ID();

// Get standardized floor plan data
$floor_plan = $data_provider->get_floor_plan_data($post_id);

// Additional data
$gallery_images = Burgland_Homes_Gallery::get_gallery_images($post_id, 'full');
// Get the first community from the ACF relationship field
$community_ids = get_field('floor_plans_communities', $post_id);
$community_id = is_array($community_ids) && !empty($community_ids) ? $community_ids[0] : null;
$community = $community_id ? get_post($community_id) : null;
$features = get_post_meta($post_id, 'floor_plan_features', true);
if (is_string($features)) {
    $features = array_filter(array_map('trim', explode("\n", $features)));
}
$floor_plan_brochure = !empty($floor_plan['brochure']) ? $floor_plan['brochure'] : null;
$design_package = !empty($floor_plan['design_package']) ? $floor_plan['design_package'] : null;
$fp_thumbnail = get_field('floor_plan_thumbnail', $post_id); // Floor Plan Thumbnail

// Breadcrumbs
$breadcrumbs = array(
    array('label' => 'Floor Plans', 'url' => get_post_type_archive_link('bh_floor_plan')),
);
if ($community) {
    $breadcrumbs[] = array('label' => $community->post_title, 'url' => get_permalink($community->ID));
}
$breadcrumbs[] = array('label' => $floor_plan['title']);

// Quick Info Sidebar
$quick_info = array();
if ($floor_plan['bedrooms']) $quick_info[] = array('label' => 'Bedrooms', 'value' => $floor_plan['bedrooms']);
if ($floor_plan['bathrooms']) $quick_info[] = array('label' => 'Bathrooms', 'value' => $floor_plan['bathrooms']);
if ($floor_plan['square_feet']) $quick_info[] = array('label' => 'Square Feet', 'value' => number_format($floor_plan['square_feet']));

// Start Content Capture
ob_start();
?>

<section id="overview" class="overview-detail">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-12 col-lg-6 col-xxl-5 pt-5 pt-lg-4 px-lg-3 px-xl-5 px-xxxl-7">
                <?php $template_loader->render_single_component('breadcrumbs', array(
                    'breadcrumbs' => $breadcrumbs
                ));
                ?>

                <!-- Header -->
                <?php
                $header_specs = array();
                if ($floor_plan['bedrooms']) $header_specs[] = array('label' => $floor_plan['bedrooms'] . ' Bed', 'icon' => 'house-door');
                if ($floor_plan['bathrooms']) $header_specs[] = array('label' => $floor_plan['bathrooms'] . ' Bath', 'icon' => 'droplet');
                if ($floor_plan['square_feet']) $header_specs[] = array('label' => number_format($floor_plan['square_feet']) . ' sqft', 'icon' => 'arrows-angle-expand');
                if ($floor_plan['garage']) $header_specs[] = array('label' => $floor_plan['garage'] . ' Car', 'icon' => 'car-front');

                $template_loader->render_single_component('header', array(
                    'title' => $floor_plan['title'],
                    'price' => $floor_plan['price'],
                    'specs' => $header_specs,
                    'post_type' => 'bh_floor_plan'
                )); ?>

            </div>
            <div class="col-12 col-lg-6 col-xxl-7 pe-lg-0 mb-3 mb-lg-0">
                <!-- Gallery -->
                <?php $template_loader->render_single_component('gallery', array(
                    'images' => $gallery_images,
                    'featured_image' => $floor_plan['thumbnail']
                )); ?>
            </div>
        </div>

        <?php if ($floor_plan_brochure) { ?>
            <div class="row d-lg-none">
                <div class="col-12">
                    <a role="button" href="<?php echo esc_url(is_array($floor_plan_brochure) ? $floor_plan_brochure['url'] : $floor_plan_brochure); ?>"
                        class="btn btn-sm btn-outline-primary w-100 mb-3" target="_blank">
                        Floor Plan Brochure
                        <span class="visually-hidden">PDF Download</span>
                    </a>
                </div>
            </div>
        <?php }

        if ($design_package) { ?>
            <div class="row d-lg-none">
                <div class="col-12">
                    <a role="button" href="<?php echo esc_url(is_array($design_package) ? $design_package['url'] : $design_package); ?>"
                        class="btn btn-sm btn-outline-primary w-100 mb-3" target="_blank">
                        Design Package
                        <span class="visually-hidden">PDF Download</span>
                    </a>
                </div>
            </div>
        <?php } ?>

    </div>
</section>

<!-- Actions -->
<?php
// Build dynamic navigation sections based on available content
$nav_sections = array();

// Overview is always present
$nav_sections[] = array('id' => 'overview', 'label' => 'Overview');

// Check if there's description content
// if (!empty(get_post_field('post_content', $post_id))) {
//     $nav_sections[] = array('id' => 'description', 'label' => 'About');
// }

// Check if there are features
if (!empty($features)) {
    $nav_sections[] = array('id' => 'features', 'label' => 'Features');
}

// Check if there are gallery images
if (!empty($gallery_images)) {
    $nav_sections[] = array('id' => 'gallery-section', 'label' => 'Elevations');
}
// Check if there are gallery images
if (!empty($fp_thumbnail)) {
    $nav_sections[] = array('id' => 'floor-plan-thumbnail', 'label' => 'Floor Plan');
}

// Check if there are available lots
$lots_query = new WP_Query(array(
    'post_type' => 'bh_lot',
    'posts_per_page' => 1,
    'meta_query' => array(
        array(
            'key' => 'lot_floor_plan',
            'value' => $post_id,
        )
    )
));
if ($lots_query->have_posts()) {
    $nav_sections[] = array('id' => 'available-lots', 'label' => 'Available Lots');
}
wp_reset_postdata();

$template_loader->render_single_component('actions', array(
    'sections' => $nav_sections,
    'brochure' => $floor_plan_brochure,
    'brochure_label' => 'Floor Plan Brochure',
    'design_package' => $design_package
)); ?>

<div class="container-fluid py-lg-4">
    <div class="container">
        <div class="row py-5">
            <div class="col-12 col-lg-8 pe-lg-5">
                <!-- Description -->
                <div id="description">
                    <?php $template_loader->render_single_component('description', array(
                        'title' => '',
                        'content' => apply_filters('the_content', get_post_field('post_content', $post_id))
                    )); ?>

                    <!-- Features -->
                    <?php if (!empty($features)) { ?>
                        <div id="features" class="mt-4">
                            <?php $template_loader->render_single_component('amenities', array(
                                'items' => $features
                            )); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-12 col-lg-4 pt-4 pt-lg-0">
                <?php $template_loader->render_single_component('sidebar-contact', array(
                    'title' => 'Interested in This Floor Plan?',
                    'button_text' => 'Schedule a Tour'
                )); ?>


            </div>
        </div>
    </div>
</div>

<div class="container-fluid bg-light">
    <div class="container">
        <!-- Gallery Section -->
        <?php if (!empty($gallery_images)) { ?>
            <div id="gallery-section" class="py-5 py-lg-7">
                <h2 class="h3 mb-4  display-5 text-primary">Available Elevations</h2>

                <div class="row g-3">
                    <?php foreach ($gallery_images as $image) :
                        $caption = $image['caption'] ?? '';
                    ?>
                        <div class="col-md-3 col-sm-6">
                            <figure class="m-0 position-relative">
                                <a href="<?php echo esc_url($image['url']); ?>"
                                    data-fancybox="single-gallery-3"
                                    data-caption="<?php echo esc_attr($caption ?: $image['alt'] ?? ''); ?>">
                                    <img src="<?php echo esc_url($image['url']); ?>"
                                        alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                                        class="img-fluid rounded shadow-sm hover-shadow-lg"
                                        style="width:100%; height:200px; object-fit:cover;">
                                </a>
                                <?php if ($caption) : ?>
                                    <figcaption class="figCaptionRelative text-primary">
                                        <?php echo esc_html($caption); ?>
                                    </figcaption>
                                <?php endif; ?>
                            </figure>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php } ?>

        <!-- Floor Plan Thumbnail -->
        <?php
       
        if ($fp_thumbnail) { ?>
            <div id="floor-plan-thumbnail" class="pb-lg-7 pb-5">
                <h2 class="h3 mb-4 display-5 text-primary">Floor Plan Layout</h2>
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <figure class="m-0 position-relative">
                            <a href="<?php echo esc_url($fp_thumbnail['url']); ?>"
                                data-fancybox="single-gallery-2"
                                data-caption="Floor Plan Layout">
                                <img src="<?php echo esc_url($fp_thumbnail['url']); ?>"
                                    alt="Floor Plan Layout"
                                    class="img-fluid rounded shadow-sm hover-shadow-lg"
                                    style="width:100%;">
                            </a>
                        </figure>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- Related Lots grid -->
        <div id="available-lots" class="pb-lg-7 pb-5">
            <?php $template_loader->render_single_component('floor-plan-lots-grid', array(
                'floor_plan_id' => $post_id
            )); ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Render Layout
$template_loader->render_single_component('layout', array(
    'content' => $content,
));

get_footer();
