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
$brochure = !empty($lot['brochure']) ? $lot['brochure'] : null;
$floor_plan_pdf = !empty($lot['floor_plan_pdf']) ? $lot['floor_plan_pdf'] : null;

// Handle ACF file fields - extract URL if they're arrays
if (is_array($brochure) && isset($brochure['url'])) {
    $brochure = $brochure['url'];
}
if (is_array($floor_plan_pdf) && isset($floor_plan_pdf['url'])) {
    $floor_plan_pdf = $floor_plan_pdf['url'];
}

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
if ($lot['lot_size']) $quick_info[] = array('label' => 'Lot Size', 'value' => $lot['lot_size']);
if ($lot['price']) $quick_info[] = array('label' => 'Price', 'value' => $lot['price']);

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
                if ($lot['bedrooms']) $header_specs[] = array('label' => $lot['bedrooms'] . ' Bed', 'icon' => 'house-door');
                if ($lot['bathrooms']) $header_specs[] = array('label' => $lot['bathrooms'] . ' Bath', 'icon' => 'droplet');
                if ($lot['square_feet']) $header_specs[] = array('label' => number_format($lot['square_feet']) . ' sqft', 'icon' => 'arrows-angle-expand');
                if ($lot['garage']) $header_specs[] = array('label' => $lot['garage'] . ' Car', 'icon' => 'car-front');
                
                $template_loader->render_single_component('header', array(
                    'title' => $lot['lot_number'] ?: $lot['title'],
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

        <?php if ($floor_plan_pdf) { ?>
        <div class="row d-lg-none">
            <div class="col-12">
                <a role="button" href="<?php echo esc_url($floor_plan_pdf); ?>"
                    class="btn btn-sm btn-outline-primary w-100 mb-3" target="_blank">
                    Floor Plan
                </a>
            </div>
        </div>
        <?php } ?>

        <?php if ($brochure) { ?>
        <div class="row d-lg-none">
            <div class="col-12">
                <a role="button" href="<?php echo esc_url($brochure); ?>"
                    class="btn btn-sm btn-outline-primary w-100 mb-3" target="_blank">
                    Brochure
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
if (!empty(get_post_field('post_content', $post_id))) {
    $nav_sections[] = array('id' => 'description', 'label' => 'About');
}

// Check if there are features
if (!empty($features)) {
    $nav_sections[] = array('id' => 'features', 'label' => 'Features');
}

$template_loader->render_single_component('actions', array(
    'sections' => $nav_sections,
    'map_url' => $lot['map_url'],
    'brochure' => $brochure,
    'floor_plan_pdf' => $floor_plan_pdf
)); ?>

<div class="container">
    <div class="row py-5">
        <div class="col-12 col-lg-8">
            <!-- Description -->
            <div id="description">
                <?php $template_loader->render_single_component('description', array(
                    'title' => 'About This Lot',
                    'content' => apply_filters('the_content', get_post_field('post_content', $post_id))
                )); ?>
            </div>

            <!-- Features -->
            <?php if (!empty($features)) { ?>
            <div id="features">
                <?php $template_loader->render_single_component('amenities', array(
                    'title' => 'Lot Features',
                    'items' => $features
                )); ?>
            </div>
            <?php } ?>
        </div>
        <div class="col-12 col-lg-4">
            <?php $template_loader->render_single_component('sidebar-contact', array(
                'title' => $lot['status_label'] === 'Sold' ? 'This lot is sold' : 'Interested in This Lot?',
                'button_text' => $lot['status_label'] === 'Sold' ? 'Contact for Others' : 'Reserve Now',
                'brochure' => $brochure
            )); ?>

            <?php $template_loader->render_single_component('sidebar-quick-info', array(
                'info' => $quick_info
            )); ?>

            <?php $template_loader->render_single_component('sidebar-location', array(
                'map_url' => $lot['map_url']
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
