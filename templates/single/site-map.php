<?php
/**
 * Site Map Component
 * 
 * Displays community site map image
 * 
 * @param array $args {
 *     @type array $site_map ACF image array with 'url', 'alt', 'width', 'height' keys
 * }
 */
if (!defined('ABSPATH')) exit;

$site_map = isset($args['site_map']) ? $args['site_map'] : null;

// Exit if no site map image
if (empty($site_map) || empty($site_map['url'])) {
    return;
}
?>

<section class="bh-site-map mb-5" id="community-map">
    <div class="">
        <div class="card-body p-4">
            <h2 class="h3 mb-3 text-primary">Site Map</h2>
            <div class="bh-site-map-image">
                <img 
                    src="<?php echo esc_url($site_map['url']); ?>" 
                    alt="<?php echo esc_attr($site_map['alt'] ?: 'Community Site Map'); ?>"
                    class="img-fluid w-100 h-auto rounded"
                    <?php if (!empty($site_map['width'])): ?>
                        width="<?php echo esc_attr($site_map['width']); ?>"
                    <?php endif; ?>
                    <?php if (!empty($site_map['height'])): ?>
                        height="<?php echo esc_attr($site_map['height']); ?>"
                    <?php endif; ?>
                    loading="lazy"
                />
            </div>
        </div>
    </div>
</section>
