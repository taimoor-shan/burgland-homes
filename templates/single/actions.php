<?php
/**
 * Single Actions Component
 * 
 * @param array $args {
 *     @type array $brochure { url, title }
 *     @type string $video_url
 *     @type string $map_anchor
 * }
 */
if (!defined('ABSPATH')) exit;
$brochure = isset($args['brochure']) ? $args['brochure'] : null;
$video_url = isset($args['video_url']) ? $args['video_url'] : '';
$map_anchor = isset($args['map_anchor']) ? $args['map_anchor'] : '#community-map';
?>
<div class="btn-group gap-2 d-flex mb-4" role="group">
    <?php if ($brochure && !empty($brochure['url'])) : ?>
        <a href="<?php echo esc_url($brochure['url']); ?>" class="btn btn-primary btn-lg flex-fill text-center" download>
            <i class="fa-solid fa-file-circle-check me-2"></i> Brochure
        </a>
    <?php endif; ?>

    <?php if (!empty($video_url)) : ?>
        <a href="<?php echo esc_url($video_url); ?>" class="btn btn-primary btn-lg flex-fill video-lightbox" data-type="video">
            <i class="fa-solid fa-video me-2"></i> Watch Video
        </a>
    <?php endif; ?>

    <a href="<?php echo esc_url($map_anchor); ?>" class="btn btn-primary btn-lg flex-fill text-center">
        <i class="fa-solid fa-map-location-dot me-2"></i> Site Map
    </a>
</div>
