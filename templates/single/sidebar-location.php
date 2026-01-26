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
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-4">
        <h3 class="h6 card-title mb-3 text-uppercase fw-bold text-muted" style="letter-spacing: 1px;">Location</h3>
        <div class="d-grid">
            <a href="<?php echo esc_url($map_url); ?>" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-geo-alt me-2"></i> Get Directions
            </a>
        </div>
    </div>
</div>
