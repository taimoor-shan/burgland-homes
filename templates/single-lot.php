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

    <!-- Header (after gallery) -->
    <?php $template_loader->render_single_component('header', array(
        'title' => $lot['lot_number'] ?: $lot['title'],
        'title_suffix' => $community ? $community->post_title : '',
        'title_suffix_url' => $community ? get_permalink($community->ID) : '',
        'address' => $lot['address'],
        'city' => $lot['city'],
        'state' => $lot['state'],
        'zip' => $lot['zip'],
        'price' => $lot['price'],
        'status' => array(
            'label' => $lot['status_label'],
            'class' => $lot['status_class']
        )
    )); ?>

    <!-- Information -->
    <?php 
    $specs = array();
    $specs[] = array('label' => 'Lot Number', 'value' => $lot['lot_number'] ?: $lot['title'], 'icon' => 'geo-alt');
    if ($lot['lot_size']) $specs[] = array('label' => 'Lot Size', 'value' => $lot['lot_size'], 'icon' => 'arrows-angle-expand');
    if ($lot['price']) $specs[] = array('label' => 'Price', 'value' => $lot['price'], 'icon' => 'currency-dollar');
    $specs[] = array('label' => 'Status', 'value' => $lot['status_label'], 'icon' => 'tag');
    
    $template_loader->render_single_component('specs', array(
        'title' => 'Lot Information',
        'specs' => $specs
    ));
    ?>

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
