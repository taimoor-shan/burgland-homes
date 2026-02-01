<?php
/**
 * Single Sidebar Location Component
 * 
 * @param array $args {
 *     @type string $map_url Google Maps URL for the location
 * }
 */
if (!defined('ABSPATH')) exit;

$map_url = isset($args['map_url']) ? $args['map_url'] : '';

if (empty($map_url)) return;
?>

       
     
            <a href="<?php echo esc_url($map_url); ?>" class="btn btn-outline-secondary btn-sm w-100" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-geo-alt me-2"></i> Get Directions
            </a>
