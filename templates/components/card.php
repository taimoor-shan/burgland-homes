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
    <div class="bh-card-image position-relative">
        <img src="<?php echo esc_url($thumbnail); ?>"
            class="card-img-top"
            alt="<?php echo esc_attr($data['title']); ?>"
            style="aspect-ratio: 9/5; object-fit: cover;">

        <?php if (!empty($data['badges'])): ?>
            <div class="bh-card-badges position-absolute top-0 start-0 p-2 d-flex flex-column gap-1">
                <?php foreach ($data['badges'] as $badge): ?>
                    <span class="badge bg-<?php echo esc_attr($badge['class']); ?> text-white">
                        <?php echo esc_html($badge['label']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body d-flex flex-column p-4 pt-3">
        <h3 class="card-title h4 mb-1">
            <?php echo esc_html($data['title']); ?>

        </h3>
        <?php if (!empty($data['price'])): ?>
            <h5 class="bh-card-price text-info mb-2">
                <?php if ($show_from_label): ?>
                    <span class="me-1 small">From<sup>*</sup></span>
                <?php endif; ?>
                <span class="fw"><?php echo esc_html($data['price']); ?></span>
            </h5>
        <?php endif; ?>


        <?php if (!empty($data['specs'])): ?>
            <div class="bh-card-specs mb-3 text-dark h6">
                <?php
                $spec_labels = array();
                foreach ($data['specs'] as $spec) {
                    // Remove trailing .00 from decimal values
                    $label = $spec['label'];
                    $label = preg_replace('/\.00(?=\s|$)/', '', $label);
                    $spec_labels[] = esc_html($label);
                }
                echo implode(' &nbsp; | &nbsp; ', $spec_labels);
                ?>
            </div>
        <?php endif; ?>
        <?php if ($card_type === 'community' && !empty($data['address'])): ?>
            <!-- Address with line break between street and city/state/zip for better card layout -->
            <div class="bh-card-footer mt-auto d-flex align-items-center justify-content-between w-100 gap-3">
                <div>
                    <p class="text-dark mb-1">Address:</p>
                    <p class="text-info mb-0">
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
        <?php elseif ($card_type === 'lot' && !empty($data['floor_plan_name'])): ?>
            <div class="bh-card-footer mt-auto d-flex align-items-center justify-content-between w-100 gap-3">
                <div>
                    <p class="text-info mb-0">
                        <?php if (!empty($floor_plan_url)): ?>
                            <a href="<?php echo esc_url($floor_plan_url); ?>" class="bh-card-footer-link" onclick="event.stopPropagation();">
                                <?php echo esc_html($data['floor_plan_name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="bh-card-footer-link"><?php echo esc_html($data['floor_plan_name']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>