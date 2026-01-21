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
        'featured_image' => $floor_plan['thumbnail']
    )); ?>

    <!-- Header (after gallery) -->
    <?php $template_loader->render_single_component('header', array(
        'title' => $floor_plan['title'],
        'price' => $floor_plan['price']
    )); ?>

    <!-- Specs -->
    <?php 
    $specs = array();
    if ($floor_plan['bedrooms']) $specs[] = array('label' => 'Bedrooms', 'value' => $floor_plan['bedrooms'], 'icon' => 'house-door');
    if ($floor_plan['bathrooms']) $specs[] = array('label' => 'Bathrooms', 'value' => $floor_plan['bathrooms'], 'icon' => 'droplet');
    if ($floor_plan['square_feet']) $specs[] = array('label' => 'Square Feet', 'value' => number_format($floor_plan['square_feet']), 'icon' => 'arrows-angle-expand');
    if ($floor_plan['garage']) $specs[] = array('label' => 'Garage', 'value' => $floor_plan['garage'], 'icon' => 'car-front');
    
    if (!empty($specs)) {
        $template_loader->render_single_component('specs', array(
            'title' => 'Specifications',
            'specs' => $specs
        ));
    }
    ?>

    <!-- Description -->
    <?php $template_loader->render_single_component('description', array(
        'title' => 'Description',
        'content' => apply_filters('the_content', get_post_field('post_content', $post_id))
    )); ?>

    <!-- Features -->
    <?php if (!empty($features)) {
        $template_loader->render_single_component('amenities', array(
            'title' => 'Features & Amenities',
            'items' => $features
        ));
    } ?>

<?php
$content = ob_get_clean();

// Start Sidebar Capture
ob_start();
?>
    <?php $template_loader->render_single_component('sidebar-contact', array(
        'title' => 'Interested in this floor plan?',
        'button_text' => 'Schedule a Tour'
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
