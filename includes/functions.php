<?php
/**
 * Public API Functions
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get featured communities data
 * 
 * @param array $args Query arguments
 * @return array
 */
function burgland_homes_get_featured_communities($args = array()) {
    $data_provider = Burgland_Homes_Data_Provider::get_instance();
    return $data_provider->get_featured_communities($args);
}

/**
 * Render featured communities section
 * 
 * @param array $args
 * @return void
 */
function burgland_homes_render_featured_communities($args = array()) {
    $template_loader = Burgland_Homes_Template_Loader::get_instance();
    $template_loader->render_featured_communities($args);
}

/**
 * Get available lots data
 * 
 * @param array $args
 * @return array
 */
function burgland_homes_get_available_lots($args = array()) {
    $data_provider = Burgland_Homes_Data_Provider::get_instance();
    return $data_provider->get_available_lots($args);
}

/**
 * Render community card
 * 
 * @param int $community_id
 * @return void
 */
function burgland_homes_render_community_card($community_id) {
    $template_loader = Burgland_Homes_Template_Loader::get_instance();
    $template_loader->render_community_card($community_id);
}

/**
 * Render lot card
 * 
 * @param int $lot_id
 * @return void
 */
function burgland_homes_render_lot_card($lot_id) {
    $template_loader = Burgland_Homes_Template_Loader::get_instance();
    $template_loader->render_lot_card($lot_id);
}

/**
 * Render floor plan card
 * 
 * @param int $floor_plan_id
 * @return void
 */
function burgland_homes_render_floor_plan_card($floor_plan_id) {
    $template_loader = Burgland_Homes_Template_Loader::get_instance();
    $template_loader->render_floor_plan_card($floor_plan_id);
}

/**
 * Get community data
 * 
 * @param int $community_id
 * @return array
 */
function burgland_homes_get_community_data($community_id) {
    $data_provider = Burgland_Homes_Data_Provider::get_instance();
    return $data_provider->get_community_data($community_id);
}

/**
 * Get lot data
 * 
 * @param int $lot_id
 * @return array
 */
function burgland_homes_get_lot_data($lot_id) {
    $data_provider = Burgland_Homes_Data_Provider::get_instance();
    return $data_provider->get_lot_data($lot_id);
}

/**
 * Get floor plan data
 * 
 * @param int $floor_plan_id
 * @return array
 */
function burgland_homes_get_floor_plan_data($floor_plan_id) {
    $data_provider = Burgland_Homes_Data_Provider::get_instance();
    return $data_provider->get_floor_plan_data($floor_plan_id);
}
