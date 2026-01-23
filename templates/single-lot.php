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
        'featured_image' => $lot['thumbnail']
    )); ?>

    <!-- Actions -->
    <?php $template_loader->render_single_component('actions', array(
        'map_url' => $lot['map_url'],
        'brochure' => !empty($lot['brochure']) ? $lot['brochure'] : null,
        'floor_plan_pdf' => !empty($lot['floor_plan_pdf']) ? $lot['floor_plan_pdf'] : null
    )); ?>

    <!-- Header (after gallery) -->
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


    <!-- Description -->
    <?php $template_loader->render_single_component('description', array(
        'title' => 'Description',
        'content' => apply_filters('the_content', get_post_field('post_content', $post_id))
    )); ?>

    <!-- Features -->
    <?php if (!empty($features)) {
        $template_loader->render_single_component('amenities', array(
            'title' => 'Lot Features',
            'items' => $features
        ));
    } ?>

<?php
$content = ob_get_clean();

// Start Sidebar Capture
ob_start();
?>
    <?php $template_loader->render_single_component('sidebar-contact', array(
        'title' => $lot['status_label'] === 'Sold' ? 'This lot is sold' : 'Interested in this lot?',
        'button_text' => $lot['status_label'] === 'Sold' ? 'Contact for Others' : 'Reserve Now'
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
