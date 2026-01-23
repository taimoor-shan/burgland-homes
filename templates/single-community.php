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
$brochure = get_field('community_brochure', $post_id);
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

// Render Breadcrumbs at top
$template_loader->render_single_component('breadcrumbs', array(
    'breadcrumbs' => $breadcrumbs
));

// Start Content Capture
ob_start();
?>
    <!-- Gallery -->
    <?php $template_loader->render_single_component('gallery', array(
        'images' => $gallery_images,
        'featured_image' => $community['thumbnail']
    )); ?>

     <!-- Actions -->
    <?php $template_loader->render_single_component('actions', array(
        'brochure' => $brochure,
        'video_url' => $video_url,
        'map_anchor' => '#community-map',
        'map_url' => $community['map_url']
    )); ?>

    <!-- Header (after gallery) -->
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


    <!-- Overview Stats -->

    <!-- Description -->
    <?php $template_loader->render_single_component('description', array(
        'title' => 'About This Community',
        'content' => apply_filters('the_content', get_post_field('post_content', $post_id))
    )); ?>

    <!-- Amenities -->
    <?php if (!empty($amenities)) {
        $template_loader->render_single_component('amenities', array(
            'items' => $amenities
        ));
    } ?>


    <!-- Available Homes/Lots Grid -->
    <?php $template_loader->render_single_component('lots-grid', array(
        'community_id' => $post_id
    )); ?>

    <!-- Related Items (Floor Plans) -->
    <?php 
    // This could be moved to a component too if reused
    $floor_plans = $data_provider->get_featured_communities(array('post_type' => 'bh_floor_plan', 'limit' => -1)); // This is wrong, need a generic getter
    // For now keep the query here or use a new method in data provider
    ?>
        <!-- Site Map -->
    <?php if (!empty($site_map)) {
        $template_loader->render_single_component('site-map', array(
            'site_map' => $site_map
        ));
    } ?>

<?php
$content = ob_get_clean();

// Start Sidebar Capture
ob_start();
?>
    <?php $template_loader->render_single_component('sidebar-contact', array(
        'title' => 'Interested in This Community?',
        'brochure' => $brochure
    )); ?>

    <?php $template_loader->render_single_component('sidebar-quick-info', array(
        'info' => $quick_info
    )); ?>
<?php
$sidebar = ob_get_clean();

// Render Layout
$template_loader->render_single_component('layout', array(
    'content' => $content,
    'sidebar' => $sidebar
));

get_footer();
