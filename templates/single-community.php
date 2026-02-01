<?php

/**
 * Single Community Template
 * 
 * @package Burgland_Homes
 */

get_header();

$data_provider = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();
$post_id = get_the_ID();

// Get standardized community data
$community = $data_provider->get_community_data($post_id);

// Additional data for single view
$gallery_images = Burgland_Homes_Gallery::get_gallery_images($post_id, 'full');
$brochure = !empty($community['brochure']) ? $community['brochure'] : null;
$design_package = !empty($community['design_package']) ? $community['design_package'] : null;
$video_url = get_field('community_video_url', $post_id);
$site_map = get_field('community_site_map', $post_id);
$amenities = get_field('community_amenities', $post_id);
if (is_string($amenities)) {
    $amenities = array_filter(array_map('trim', explode("\n", $amenities)));
}

// Breadcrumbs
$breadcrumbs = array(
    array('label' => 'Communities', 'url' => get_post_type_archive_link('bh_community')),
    array('label' => $community['title']),
);

// Quick Info Sidebar
$quick_info = array();
if ($community['floor_plan_ranges']['count'] > 0) {
    $quick_info[] = array('label' => 'Floor Plans', 'value' => $community['floor_plan_ranges']['count']);
}
if (!empty($community['price_range'])) {
    $quick_info[] = array('label' => 'Price Range', 'value' => $community['price_range']);
}



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
                <?php
                $header_specs = array();
                $spec_labels = array(
                    'bedrooms' => 'Bed',
                    'bathrooms' => 'Bath',
                    'square_feet' => 'sqft',
                    'garage' => 'Car'
                );
                $spec_icons = array(
                    'bedrooms' => 'house-door',
                    'bathrooms' => 'droplet',
                    'square_feet' => 'arrows-angle-expand',
                    'garage' => 'car-front'
                );

                foreach (array('bedrooms', 'bathrooms', 'garage', 'square_feet') as $key) {
                    if (isset($community['floor_plan_ranges'][$key]) && $community['floor_plan_ranges'][$key]['min'] !== null) {
                        $header_specs[] = array(
                            'label' => $community['floor_plan_ranges'][$key]['formatted'] . ' ' . $spec_labels[$key],
                            'icon' => $spec_icons[$key]
                        );
                    }
                }

                $template_loader->render_single_component('header', array(
                    'title' => $community['title'],
                    'address' => $community['address'],
                    'city' => $community['city'],
                    'state' => $community['state'],
                    'zip' => $community['zip'],
                    'map_url' => $community['map_url'],
                    'price' => $community['price_range'],
                    'specs' => $header_specs,
                    'status' => array(
                        'label' => $community['status_label'],
                        'class' => $community['status_class']
                    ),
                    'post_type' => 'bh_community'
                )); ?>



            </div>
            <div class="col-12 col-lg-6 col-xxl-7 pe-lg-0 mb-3 mb-lg-0">
                <!-- Gallery -->
                <?php $template_loader->render_single_component('gallery', array(
                    'images' => $gallery_images,
                    'featured_image' => $community['thumbnail'],
                    'video_url' => $video_url
                )); ?>
            </div>
        </div>

        <?php if ($brochure && !empty($brochure['url'])) { ?>
            <div class="row d-lg-none">
                <div class="col-12">
                    <a role="button" href="<?php echo esc_url($brochure['url']); ?>"
                        class="btn btn-sm btn-outline-primary w-100 mb-3" target="_blank">
                        Community Brochure
                        <span class="visually-hidden">PDF Download</span>
                    </a>
                </div>
            </div>
        <?php }

        if ($design_package && !empty($design_package['url'])) { ?>
            <div class="row d-lg-none">
                <div class="col-12">
                    <a role="button" href="<?php echo esc_url($design_package['url']); ?>"
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
// 1

// Check if there are available lots/homes
$lots_query = new WP_Query(array(
    'post_type' => 'bh_lot',
    'posts_per_page' => 1,
    'meta_query' => array(
        array(
            'key' => 'lot_community',
            'value' => $post_id,
        )
    )
));
if ($lots_query->have_posts()) {
    $nav_sections[] = array('id' => 'available-homes', 'label' => 'Available Homes');
}
wp_reset_postdata();

// Check if site map exists
if (!empty($site_map)) {
    $nav_sections[] = array('id' => 'community-map', 'label' => 'Site Map');
}

// Check if amenities exist
// if (!empty($amenities)) {
//     $nav_sections[] = array('id' => 'amenities', 'label' => 'Amenities');
// }

$template_loader->render_single_component('actions', array(
    'sections' => $nav_sections,
    'brochure' => $brochure,
    'brochure_label' => 'Community Brochure',
    'design_package' => $design_package,
    'map_anchor' => '#community-map'
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

                    <!-- Amenities -->
                    <?php if (!empty($amenities)) { ?>
                        <div id="amenities">
                            <?php $template_loader->render_single_component('amenities', array(
                                'items' => $amenities
                            )); ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-12 col-lg-4 py-3">
                <?php $template_loader->render_single_component('sidebar-contact', array(
                    'title' => 'Interested in This Community?',
                    'brochure' => $brochure
                )); ?>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid bg-light">
    <div class="container">
        <!-- Available Homes/Lots Grid -->
        <div id="available-homes" class="pt-lg-7 pt-5">
            <?php $template_loader->render_single_component('lots-grid', array(
                'community_id' => $post_id
            )); ?>
        </div>

        <!-- Available Floor Plans Grid -->
        <div id="available-floor-plans" class="py-lg-7 py-5">
            <?php $template_loader->render_single_component('floor-plans-grid', array(
                'community_id' => $post_id
            )); ?>
        </div>
    </div>
</div>


<div class="container-fluid py-lg-6 py-5">
    <div class="container">
        <!-- Site Map -->
        <?php if (!empty($site_map)) {
            $template_loader->render_single_component('site-map', array(
                'site_map' => $site_map
            ));
        } ?>
    </div>
</div>




<?php
$content = ob_get_clean();
// Render Layout
$template_loader->render_single_component('layout', array(
    'content' => $content,
));

get_footer();
