<?php

/**
 * Single Header Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type string $title_suffix (Optional - additional title text)
 *     @type string $title_suffix_url (Optional - URL for title suffix link)
 *     @type string $address (Optional - for address display)
 *     @type string $city (Optional - for city)
 *     @type string $state (Optional - for state)
 *     @type string $zip (Optional - for zip code)
 *     @type string $map_url (Optional - Google Maps URL for clickable address)
 *     @type string $price
 *     @type array $status { label, class }
 *     @type array $specs (Optional - property specs) [
 *         @type string $label
 *         @type string $icon
 *     ]
 * }
 */
if (!defined('ABSPATH')) exit;
$data = $args;
?>
<section class="plugin-page-header">
    <div class="row align-items-center">
        <div class="col-12">
            <h1 class="text-primary mb-2 lh-2">
                <?php echo esc_html($data['title']); ?>
                <?php if (!empty($data['title_suffix'])): ?>
                    <span class="text-lowercase fw-normal"> at </span>
                    <?php if (!empty($data['title_suffix_url'])): ?>
                        <a href="<?php echo esc_url($data['title_suffix_url']); ?>" class="text-decoration-none hover-underline"><?php echo esc_html($data['title_suffix']); ?></a>
                    <?php else: ?>
                        <?php echo esc_html($data['title_suffix']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </h1>

            <!-- Address (single-line format for single pages, without <br> tag, clickable to map) -->
            <?php if (!empty($data['address']) || !empty($data['city']) || !empty($data['state']) || !empty($data['zip'])): ?>

                <p class="mb-3">
                    <?php if (!empty($data['map_url'])): ?>
                        <a href="<?php echo esc_url($data['map_url']); ?>" class="text-muted text-decoration-none hover-underline lh-1" target="_blank" rel="noopener noreferrer">
                            <?php echo burgland_homes_format_address($data, true); ?>
                        </a>
                    <?php else: ?>
                        <?php echo burgland_homes_format_address($data, true); ?>
                    <?php endif; ?>
                </p>

            <?php endif; ?>

            <?php if (!empty($data['price'])): ?>
                <h3 class="fw-semibold mb-1 text-info"><span class="small me-1">From</span><?php echo esc_html($data['price']); ?></h3>

                <!-- Disclaimer -->
                <?php
                $classes = '';

                if (!empty($data['post_type']) && $data['post_type'] === 'bh_floor_plan') {
                    $classes .= ' text-muted text-end';
                    $content = 'Hello world Floor';
                } elseif (!empty($data['post_type']) && $data['post_type'] === 'bh_lot') {
                    $classes .= ' text-muted lh-1.1';
                    $content = '* Inventory home price above includes pre-selected homesite, flex options & design upgrades.';
                } else {
                    $content = '';
                }

                // Render only if we actually have content
                if (!empty($content)) {
                    echo '<p class="' . esc_attr($classes) . '">' . $content . '</p>';
                }
                ?>



            <?php endif; ?>
            <!-- Specs -->

            <?php if (!empty($data['specs'])): ?>
                <div class="mt-4">
                    <div class="row g-5 bh-header-specs">
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
                            <div class="col-auto">
                                <span class="spec-item">
                                    <?php if ($icon_class): ?>
                                        <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                    <?php endif; ?>
                                    <span class="spec-label"><?php echo esc_html($label); ?></span>
                                </span>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php endif; ?>



        </div>

    </div>
</section>