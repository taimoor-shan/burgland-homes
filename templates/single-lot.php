<?php

/**
 * Single Lot Template
 * 
 * @package Burgland_Homes
 */

get_header();

$data_provider = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();
$post_id = get_the_ID();

// Get standardized lot data
$lot = $data_provider->get_lot_data($post_id);

// Additional data
$gallery_images = Burgland_Homes_Gallery::get_gallery_images($post_id, 'full');
$community_id = get_post_meta($post_id, 'lot_community', true);
$community = $community_id ? get_post($community_id) : null;
$features = get_post_meta($post_id, 'lot_features', true);
if (is_string($features)) {
    $features = array_filter(array_map('trim', explode("\n", $features)));
}
$floor_plan_brochure = !empty($lot['floor_plan_brochure']) ? $lot['floor_plan_brochure'] : null;
$fp_thumbnail = !empty($lot['floor_plan_thumbnail']) ? $lot['floor_plan_thumbnail'] : null;
$community_site_map = !empty($lot['community_site_map']) ? $lot['community_site_map'] : null;

// Breadcrumbs
$breadcrumbs = array(
    array('label' => 'Available Lots', 'url' => get_post_type_archive_link('bh_lot')),
);
if ($community) {
    $breadcrumbs[] = array('label' => $community->post_title, 'url' => get_permalink($community->ID));
}
$breadcrumbs[] = array('label' => $lot['lot_number'] ?: $lot['title']);

// Quick Info Sidebar
$quick_info = array();
$quick_info[] = array('label' => 'Status', 'value' => $lot['status_label']);
if ($lot['lot_size'])
    $quick_info[] = array('label' => 'Lot Size', 'value' => $lot['lot_size']);
if ($lot['price'])
    $quick_info[] = array('label' => 'Price', 'value' => $lot['price']);

// Start Content Capture
ob_start();
?>

<section id="overview" class="overview-detail bg-light">
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
                if ($lot['bedrooms'])
                    $header_specs[] = array('label' => $lot['bedrooms'] . ' Bed', 'icon' => 'house-door');
                if ($lot['bathrooms'])
                    $header_specs[] = array('label' => $lot['bathrooms'] . ' Bath', 'icon' => 'droplet');
                if ($lot['square_feet'])
                    $header_specs[] = array('label' => number_format($lot['square_feet']) . ' sqft', 'icon' => 'arrows-angle-expand');
                if ($lot['garage'])
                    $header_specs[] = array('label' => $lot['garage'] . ' Car', 'icon' => 'car-front');

                $template_loader->render_single_component('header', array(
                    'title' => $lot['title'],
                    'lot_number' => $lot['lot_number'],
                    'title_suffix' => $community ? $community->post_title : '',
                    'title_suffix_url' => $community ? get_permalink($community->ID) : '',
                    'address' => $lot['address'],
                    'city' => $lot['city'],
                    'state' => $lot['state'],
                    'zip' => $lot['zip'],
                    'map_url' => $lot['map_url'],
                    'price' => $lot['price'],
                    'specs' => $header_specs,
                    'status' => array(
                        'label' => $lot['status_label'],
                        'class' => $lot['status_class']
                    ),
                    'post_type' => 'bh_lot'
                )); ?>

            </div>
            <div class="col-12 col-lg-6 col-xxl-7 pe-lg-0 mb-3 mb-lg-0">
                <!-- Gallery -->
                <?php $template_loader->render_single_component('gallery', array(
                    'images' => $gallery_images,
                    'featured_image' => $lot['thumbnail']
                )); ?>
            </div>
        </div>

        <?php if ($floor_plan_brochure) { ?>
            <div class="row d-lg-none">
                <div class="col-12">
                    <a role="button"
                        href="<?php echo esc_url(is_array($floor_plan_brochure) ? $floor_plan_brochure['url'] : $floor_plan_brochure); ?>"
                        class="btn btn-sm btn-outline-primary mb-3 w-100" target="_blank">
                        Floor Plan Brochure
                        <span class="visually-hidden">PDF Download</span>
                    </a>
                </div>
                <div class="col-12">
                    <?php if ($lot['map_url']) { ?>
                        <?php $template_loader->render_single_component('sidebar-location', array(
                            'map_url' => $lot['map_url']
                        ));
                    } ?>
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
if (!empty(get_post_field('post_content', $post_id))) {
    $nav_sections[] = array('id' => 'description', 'label' => 'About');
}



// Check if there are features
if (!empty($features)) {
    $nav_sections[] = array('id' => 'features', 'label' => 'Features');
}

// Check if there are gallery images
if (!empty($fp_thumbnail)) {
    $nav_sections[] = array('id' => 'floor-plan-thumbnail', 'label' => 'Floor Plan');
}

// Check if there is a community site map
if (!empty($community_site_map)) {
    $nav_sections[] = array('id' => 'community-site-map', 'label' => 'Site Map');
}

$template_loader->render_single_component('actions', array(
    'sections' => $nav_sections,
    'brochure' => $floor_plan_brochure,
    'brochure_label' => 'Floor Plan Brochure'
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
                        <div id="features">
                            <?php $template_loader->render_single_component('amenities', array(
                                'title' => 'Home Features',
                                'items' => $features
                            )); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-12 col-lg-4 py-3">
                <?php $template_loader->render_single_component('sidebar-contact', array(
                    'title' => isset($lot['status_label']) && $lot['status_label'] === 'Sold' ? 'This lot is sold' : 'Interested in This Home?',
                    'button_text' => isset($lot['status_label']) && $lot['status_label'] === 'Sold' ? 'Contact for Others' : 'Reserve Now',
                    'map_url' => isset($lot['map_url']) ? $lot['map_url'] : ''
                )); ?>

            </div>
        </div>
    </div>
</div>

<div class="container-fluid bg-light py-lg-7 py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-4 col-12">
                <?php

                if ($fp_thumbnail) { ?>
                    <div id="floor-plan-thumbnail" class="">
                        <h2 class="h3 mb-4 display-5 text-primary">Floor Plan Layout</h2>
                        <figure class="m-0 position-relative">
                            <a href="<?php echo esc_url($fp_thumbnail['url']); ?>" data-fancybox="single-gallery-2"
                                data-caption="Floor Plan Layout">
                                <img src="<?php echo esc_url($fp_thumbnail['url']); ?>" alt="Floor Plan Layout"
                                    class="img-fluid rounded shadow-sm hover-shadow-lg" style="width:100%;">
                            </a>
                        </figure>
                    </div>
                <?php } ?>
            </div>
            <div class="offset-lg-2 col-lg-6 col-12">
                <?php
                if ($community_site_map) { ?>
                    <div id="community-site-map" class="">
                        <h2 class="h3 mb-4 display-5 text-primary">Community Site Map</h2>
                        <figure class="m-0 position-relative">
                            <a href="<?php echo esc_url($community_site_map['url']); ?>" data-fancybox="single-gallery-map"
                                data-caption="Community Site Map">
                                <img src="<?php echo esc_url($community_site_map['sizes']['large']); ?>"
                                    alt="Community Site Map" class="img-fluid hover-shadow-lg" style="max-width:450px;">
                            </a>
                        </figure>

                    </div>
                <?php } ?>
            </div>

        </div>
        <!-- Floor Plan Thumbnail -->


        <!-- Community Site Map -->

    </div>
</div>

<?php
$content = ob_get_clean();

// Render Layout
$template_loader->render_single_component('layout', array(
    'content' => $content,
));

get_footer();
