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
 *     @type string $address (Optional - for communities)
 *     @type string $floor_plan_info (Optional - for lots)
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
$card_type = isset($data['type']) ? $data['type'] : '';
$show_from_label = in_array($card_type, array('community', 'floor-plan'), true);
$map_url = !empty($data['map_url']) ? $data['map_url'] : '';
$floor_plan_url = !empty($data['floor_plan_url']) ? $data['floor_plan_url'] : '';
?>

<div
    class="bh-card card h-100 shadow-sm overflow-hidden bh-card-<?php echo esc_attr($data['type']); ?> community-card"
    role="link"
    tabindex="0"
    data-href="<?php echo esc_url($data['url']); ?>"
    style="cursor: pointer;">
    <div class=" position-relative oi-aspect sixteen-nine">
        <?php if (!empty($data['image_caption'])) : ?>
            <figcaption class="figCaption">
                <?php echo esc_html($data['image_caption']); ?>
            </figcaption>
        <?php endif; ?>
        <img src="<?php echo esc_url($thumbnail); ?>"
            class="card-img-top oi-aspect-img"
            alt="<?php echo esc_attr($data['title']); ?>">

        <?php if (!empty($data['badges'])): ?>
            <div class="bh-card-badges">
                <?php foreach ($data['badges'] as $badge): ?>
                    <span class="badge bg-<?php echo esc_attr($badge['class']); ?> text-white">
                        <?php echo esc_html($badge['label']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body d-flex flex-column p-4 pt-3">
        <h2 class="card-title mb-2 h4">
            <a href="<?php echo esc_url($data['url']); ?>" class="text-decoration-none text-dark">
                <?php echo esc_html($data['title']); ?>
            </a>
        </h2>
        <?php if (!empty($data['address'])): ?>
            <!-- Address with line break between street and city/state/zip for better card layout -->
            <div class="bh-card-footer mt-auto d-flex align-items-center justify-content-between w-100 gap-3">
                <div>
                    <p class="mb-3">
                        <?php if (!empty($map_url)): ?>
                            <a href="<?php echo esc_url($map_url); ?>" class="bh-card-footer-link" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">
                                <?php echo wp_kses($data['address'], array('br' => array())); ?>
                            </a>
                        <?php else: ?>
                            <span class="bh-card-footer-link"><?php echo wp_kses($data['address'], array('br' => array())); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($card_type === 'lot' && !empty($data['floor_plan_name'])): ?>
            <div class="bh-card-footer <?php echo empty($data['address']) ? 'mt-auto' : ''; ?> d-flex align-items-center justify-content-between w-100 gap-3">
                <div>
                    <p class="">
                        <b>Floor Plan:</b>
                        <?php if (!empty($floor_plan_url)): ?>
                            <a href="<?php echo esc_url($floor_plan_url); ?>" class="bh-card-footer-link text-info" onclick="event.stopPropagation();">
                                <?php echo esc_html($data['floor_plan_name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="bh-card-footer-link"><?php echo esc_html($data['floor_plan_name']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($data['price'])): ?>
            <h3 class="text-info h4 border-bottom pb-3">
                <?php if ($show_from_label): ?>
                    <span class="me-1 small">From<sup>*</sup></span>
                <?php endif; ?>
                <span class="fw"><?php echo esc_html($data['price']); ?></span>
            </h3>
        <?php endif; ?>




        <?php if (!empty($data['specs'])): ?>
            <div class="">
                <div class="row g-2 g-lg-3 bh-header-specs bh-card-specs my-0">
                    <?php
                    // Map Bootstrap Icons to Font Awesome classes
                    $icon_map = array(
                        'house-door' => 'fa-solid fa-bed',
                        'droplet' => 'fa-solid fa-bath',
                        'arrows-angle-expand' => 'fa-solid fa-ruler-combined',
                        'car-front' => 'fa-solid fa-car',
                    );

                    foreach ($data['specs'] as $spec) {
                        // Remove trailing .00 from decimal values
                        $label = $spec['label'];
                        $label = preg_replace('/\.00(?=\s|$)/', '', $label);

                        // Convert Bootstrap icon to Font Awesome if needed
                        $icon_class = '';
                        if (!empty($spec['icon'])) {
                            $icon_class = isset($icon_map[$spec['icon']]) ? $icon_map[$spec['icon']] : $spec['icon'];
                        }
                    ?>
                        <div class="col-3">
                            <span class="spec-item">
                                <?php if ($icon_class): ?>
                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                <?php endif; ?>
                                <span class="spec-label"><?php echo wp_kses($label, array('sup' => array())); ?></span>
                            </span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $button_text = 'Learn More'; // default

        if ($card_type === 'lot') {
            $button_text = 'View Home';
        } elseif ($card_type === 'floor-plan') {
            $button_text = 'View Floor Plan';
        } elseif ($card_type === 'community') {
            $button_text = 'View Community';
        }
        ?>

        <span class="btn btn-sm btn-outline-secondary w-100 mt-3">
            <?= esc_html($button_text); ?>
        </span>
    </div>
</div>