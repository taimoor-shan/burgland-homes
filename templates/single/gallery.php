<?php

/**
 * Single Gallery Component
 * 
 * @param array $args {
 *     @type array $images [ { url, title, alt } ]
 *     @type string $featured_image
 *     @type string $video_url
 * }
 */
if (!defined('ABSPATH')) exit;
$images = isset($args['images']) ? $args['images'] : array();
$featured_image_id = $args['featured_image_id'] ?? 0;
$featured_image = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';

$video_url = isset($args['video_url']) ? $args['video_url'] : '';
?>
<div class="plugin-slider">
    <?php if (!empty($images)) : ?>
        <div class="plugin-slider__swiper swiper rounded shadow overflow-hidden position-relative">

            <div class="position-absolute top-0 end-0 m-3 z-3 d-flex gap-2">
                <?php if ($video_url) : ?>
                    <a href="<?php echo esc_url($video_url); ?>" class="btn btn-sm btn-outline-primary bg-light" data-fancybox data-type="iframe">
                        <i class="fa-solid fa-video me-1"></i> Video
                    </a>
                <?php endif; ?>
                <a href="#" class="btn btn-sm btn-outline-primary bg-light" onclick="event.preventDefault(); this.closest('.plugin-slider').querySelector('.swiper-wrapper [data-fancybox], .plugin-slider__single [data-fancybox]').click();">
                    <i class="fa-solid fa-image me-1"></i>
                    <span class="fw-bold"><?php echo count($images); ?> Photos</span>
                </a>
            </div>
            
            <div class="swiper-wrapper">
                <?php foreach ($images as $image) :
                    $image_id = isset($image['id']) ? (int) $image['id'] : 0;
                    $caption  = $image_id ? wp_get_attachment_caption($image_id) : '';
                ?>
                    <div class="swiper-slide">
                        <figure class="m-0 position-relative">
                            <?php if ($caption) : ?>
                                <figcaption class="figCaption">
                                    <?php echo esc_html($caption); ?>
                                </figcaption>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url($image['url']); ?>"
                                data-fancybox="single-gallery"
                                data-caption="<?php echo esc_attr($caption ?: $image['alt'] ?? ''); ?>">

                                <img src="<?php echo esc_url($image['url']); ?>"
                                    alt="<?php echo esc_attr($image['alt'] ?? ''); ?>"
                                    class="w-100"
                                    style="aspect-ratio: 16/9; object-fit: cover;">
                            </a>
                        </figure>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <!-- <div class="swiper-pagination"></div> -->
        </div>
    <?php elseif ($featured_image_id) :
        $caption = wp_get_attachment_caption($featured_image_id);
    ?>
        <div class="plugin-slider__single overflow-hidden position-relative">
            <figure class="m-0 position-relative">
                <?php if ($caption) : ?>
                    <figcaption class="figCaption">
                        <?php echo esc_html($caption); ?>
                    </figcaption>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(wp_get_attachment_url($featured_image_id)); ?>" data-fancybox="single-gallery" data-caption="<?php echo esc_attr($caption ?? ''); ?>">
                    <?php echo wp_get_attachment_image(
                        $featured_image_id,
                        'full',
                        false,
                        [
                            'class' => 'img-fluid w-100',
                            'style' => 'aspect-ratio: 16/9; object-fit: cover;',
                        ]
                    ); ?>
                </a>
            </figure>
        </div>
    <?php endif; ?>

</div>