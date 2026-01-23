<?php

/**
 * Single Actions Component
 * 
 * @param array $args {
 *     @type array $brochure { url, title } (Optional - Community/Lot brochure)
 *     @type string $video_url (Optional - YouTube/Vimeo URL)
 *     @type string $map_anchor (Optional - ID anchor for site map, community pages only)
 *     @type string $map_url (Optional - Google Maps URL for clickable address)
 *     @type array $floor_plan_pdf (Optional - Floor plan PDF { url, title }, shown on lot & floor plan singles)
 * }
 */
if (!defined('ABSPATH')) exit;
$brochure = isset($args['brochure']) ? $args['brochure'] : null;
$video_url = isset($args['video_url']) ? $args['video_url'] : '';
$map_anchor = isset($args['map_anchor']) ? $args['map_anchor'] : '';
$map_url = isset($args['map_url']) ? $args['map_url'] : '';
$floor_plan_pdf = isset($args['floor_plan_pdf']) ? $args['floor_plan_pdf'] : null;

// Count how many buttons will be displayed
$button_count = 0;
if ($brochure && !empty($brochure['url'])) $button_count++;
if ($floor_plan_pdf && !empty($floor_plan_pdf['url'])) $button_count++;
if (!empty($video_url)) $button_count++;
if (!empty($map_anchor)) $button_count++;
if (!empty($map_url)) $button_count++;

// Only show container if there are buttons to display
if ($button_count === 0) {
    return;
}
?>
<div class="btn-group gap-1 d-flex mb-3" role="group">
    <?php if ($brochure && !empty($brochure['url'])) : ?>
        <a href="<?php echo esc_url($brochure['url']); ?>" class="btn btn-primary  flex-fill text-center" download>
            <i class="fa-solid fa-file-circle-check me-2"></i> Brochure
        </a>
    <?php endif; ?>

    <?php if ($floor_plan_pdf && !empty($floor_plan_pdf['url'])) : ?>
        <a href="<?php echo esc_url($floor_plan_pdf['url']); ?>" class="btn btn-primary  flex-fill text-center" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-file-pdf me-2"></i> Floor Plan
        </a>
    <?php endif; ?>

    <?php if (!empty($video_url)) : ?>
        <a href="<?php echo esc_url($video_url); ?>" class="btn btn-primary  flex-fill video-lightbox" data-type="video">
            <i class="fa-solid fa-video me-2"></i> Watch Video
        </a>
    <?php endif; ?>

    <?php if (!empty($map_anchor)) : ?>
        <a href="<?php echo esc_url($map_anchor); ?>" class="btn btn-primary  flex-fill text-center">
            <i class="fa-solid fa-map-location-dot me-2"></i> Site Map
        </a>
    <?php endif; ?>

    <?php if (!empty($map_url)) : ?>
        <a href="<?php echo esc_url($map_url); ?>" target="_blank" class="btn btn-primary  flex-fill text-center">
            <i class="fa-solid fa-diamond-turn-right me-2"></i> Get Directions
        </a>
    <?php endif; ?>
</div>