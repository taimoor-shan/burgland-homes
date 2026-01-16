<?php

/**
 * Unified Card Component
 * 
 * Reusable component to display a card for any entity (Community, Floor Plan, Lot)
 * 
 * @param array $data {
 *     @type int $id
 *     @type string $type
 *     @type string $title
 *     @type string $url
 *     @type string $image
 *     @type string $price
 *     @type string $footer_text
 *     @type array $badges [
 *         @type string $label
 *         @type string $class
 *     ]
 *     @type array $specs [
 *         @type string $label
 *         @type string $icon
 *     ]
 * }
 */

if (!defined('ABSPATH')) {
    exit;
}

$data = isset($args['data']) ? $args['data'] : array();

if (empty($data)) {
    return;
}

$default_image = 'https://via.placeholder.com/800x600?text=' . urlencode($data['title']);
$thumbnail = !empty($data['image']) ? $data['image'] : $default_image;
?>

<a href="<?php echo esc_url($data['url']); ?>" class="bh-card card h-100 shadow-sm overflow-hidden rounded-3 bh-card-<?php echo esc_attr($data['type']); ?> community-card">

    <div class="bh-card-image position-relative">
        <img src="<?php echo esc_url($thumbnail); ?>"
            class="card-img-top"
            alt="<?php echo esc_attr($data['title']); ?>"
            style="aspect-ratio: 9/5; object-fit: cover;">

        <?php if (!empty($data['badges'])): ?>
            <div class="bh-card-badges position-absolute top-0 start-0 p-2 d-flex flex-column gap-1">
                <?php foreach ($data['badges'] as $badge): ?>
                    <span class="badge bg-<?php echo esc_attr($badge['class']); ?> text-white fw-semibold">
                        <?php echo esc_html($badge['label']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>


    <div class="card-body d-flex flex-column p-4">
        <h3 class="card-title h4 mb-3">
            <?php echo esc_html($data['title']); ?>

        </h3>
        <?php if (!empty($data['footer_text'])): ?>
            <?php echo esc_html($data['footer_text']); ?>
        <?php endif; ?>

        <?php if (!empty($data['specs'])): ?>
            <div class="bh-card-specs d-flex flex-wrap gap-3 mb-3 text-muted small">
                <?php foreach ($data['specs'] as $spec): ?>
                    <div class="spec-item d-flex align-items-center gap-2">
                        <?php if (!empty($spec['icon'])): ?>
                            <i class="bi bi-<?php echo esc_attr($spec['icon']); ?>"></i>
                        <?php endif; ?>
                        <span><?php echo esc_html($spec['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <div class="bh-card-footer mt-auto pt-3 border-top d-flex align-items-center justify-content-between w-100 gap-3">
            <?php if (!empty($data['price'])): ?>
                <h6 class="bh-card-price text-primary fw-semibold mb-0">
                   From <?php echo esc_html($data['price']); ?>
                </h6>
            <?php endif; ?>
         

        </div>

    </div>
</a>