<?php

/**
 * Single Header Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type string $lot_number
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
if (!defined('ABSPATH'))
    exit;
$data = $args;
?>
<section class="plugin-page-header">
    <div class="row align-items-center">
        <div class="col-12">
            <?php if (!empty($data['status'])): ?>
                <span
                    class="badge bg-<?php echo esc_attr($data['status']['class']); ?> my-3"><?php echo esc_html($data['status']['label']); ?></span>
            <?php endif; ?>
            <h1 class="text-dark mb-3">
                <?php echo esc_html($data['title']); ?>

            </h1>
            <?php if (!empty($data['lot_number'])): ?>
                <p class="mb-3">Homesite #<?php echo esc_html($data['lot_number']); ?></p>
            <?php endif; ?>

            <!-- Address (single-line format for single pages, without <br> tag, clickable to map) -->
            <?php if (!empty($data['address']) || !empty($data['city']) || !empty($data['state']) || !empty($data['zip'])): ?>

                <p class="mb-4">
                    <?php if (!empty($data['map_url'])): ?>
                        <a href="<?php echo esc_url($data['map_url']); ?>" class="text-dark locationLink" target="_blank"
                            rel="noopener noreferrer">
                            <?php echo burgland_homes_format_address($data, true); ?>
                        </a>
                    <?php else: ?>
                        <?php echo burgland_homes_format_address($data, true); ?>
                    <?php endif; ?>
                </p>

            <?php endif; ?>

            <?php if (!empty($data['price'])): ?>
                <h3 class="fw-semibold mb-1 text-info">
                    <?php if (isset($data['post_type']) && in_array($data['post_type'], array('bh_community', 'bh_floor_plan'))): ?>
                        <span class="small me-1">From</span>
                    <?php endif; ?>
                    <?php echo esc_html($data['price']); ?>
                </h3>

                <!-- Price Disclaimer -->
                <?php
                $disclaimer = '';
                $classes = 'small lh-sm italic';

                // Get price disclaimer based on post type
                if (!empty($data['post_type'])) {
                    switch ($data['post_type']) {
                        case 'bh_community':
                            $disclaimer = get_option('bh_price_disclaimer_community');
                            break;
                        case 'bh_floor_plan':
                            $disclaimer = get_option('bh_price_disclaimer_floor_plan');
                            break;
                        case 'bh_lot':
                            $disclaimer = get_option('bh_price_disclaimer_lot');
                            break;
                    }
                }

                // Render only if we actually have disclaimer content
                if (!empty($disclaimer)) {
                    echo '<p class="' . esc_attr($classes) . '">' . wp_kses_post($disclaimer) . '</p>';
                }
                ?>

            <?php endif; ?>
            <!-- Specs -->

            <?php if (!empty($data['specs'])): ?>
                <div class="mt-4 mt-lg-5">
                    <div class="row bh-header-specs justify-content-start g-4">
                        <?php
                        // Map Bootstrap Icons to Font Awesome classes
                        $icon_map = array(
                            'house-door' => 'fa-solid fa-bed',
                            'droplet' => 'fa-solid fa-bath',
                            'arrows-angle-expand' => 'fa-solid fa-ruler-combined',
                            'car-front' => 'fa-solid fa-car',
                        );

                        $total_specs = count($data['specs']);
                        $current_spec = 0;

                        foreach ($data['specs'] as $spec) {
                            $current_spec++;
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
                                        <i class="<?php echo esc_attr($icon_class); ?> text-muted"></i>
                                    <?php endif; ?>
                                    <span class="spec-label text-primary mt-0"><?php echo esc_html($label); ?></span>
                                </span>
                            </div>
                            <?php if ($current_spec < $total_specs): ?>
                                <div class="col-auto d-flex align-items-center">
                                    <span class="separator"></span>
                                </div>
                            <?php endif; ?>
                        <?php } ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>