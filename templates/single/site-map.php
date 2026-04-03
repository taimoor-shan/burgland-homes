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
        <div class="">
            <h2 class="display-5 text-primary mb-4">Site Map</h2>

            <div class="bh-site-map-image">
                <a href="<?php echo esc_url($site_map['url']); ?>" 
                   data-fancybox="site-map"
                   data-caption="<?php echo esc_attr($site_map['alt'] ?: 'Community Site Map'); ?>">
                    <img
                        src="<?php echo esc_url($site_map['url']); ?>"
                        alt="<?php echo esc_attr($site_map['alt'] ?: 'Community Site Map'); ?>"
                        class="img-fluid h-auto rounded"
                        style="max-width: 500px; width: 100%;"
                        <?php if (!empty($site_map['width'])): ?>
                        width="<?php echo esc_attr($site_map['width']); ?>"
                        <?php endif; ?>
                        <?php if (!empty($site_map['height'])): ?>
                        height="<?php echo esc_attr($site_map['height']); ?>"
                        <?php endif; ?>
                        loading="lazy" />
                </a>
            </div>
        </div>
    </div>
</section>