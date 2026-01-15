<?php
/**
 * Single Gallery Component
 * 
 * @param array $args {
 *     @type array $images [ { url, title, alt } ]
 *     @type string $featured_image
 * }
 */
if (!defined('ABSPATH')) exit;
$images = isset($args['images']) ? $args['images'] : array();
$featured_image = isset($args['featured_image']) ? $args['featured_image'] : '';
?>
<div class="plugin-slider mb-4">
    <?php if (!empty($images)) : ?>
        <div class="plugin-slider__swiper swiper rounded shadow overflow-hidden">
            <div class="swiper-wrapper">
                <?php foreach ($images as $image) : ?>
                    <div class="swiper-slide">
                        <a href="<?php echo esc_url($image['url']); ?>" class="glightbox" data-gallery="single-gallery">
                            <img src="<?php echo esc_url($image['url']); ?>" 
                                 alt="<?php echo esc_attr($image['alt']); ?>" 
                                 class="w-100" style="aspect-ratio: 16/9; object-fit: cover;">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    <?php elseif ($featured_image) : ?>
        <div class="plugin-slider__single rounded shadow overflow-hidden">
            <a href="<?php echo esc_url($featured_image); ?>" class="glightbox">
                <img src="<?php echo esc_url($featured_image); ?>" class="img-fluid w-100" style="aspect-ratio: 16/9; object-fit: cover;">
            </a>
        </div>
    <?php endif; ?>
</div>
