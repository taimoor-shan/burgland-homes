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
<section class="plugin-page-header py-3 mb-3">
    <div class="row align-items-center g-4">

        <div class="col-lg-7 col-xl-8">
            <h1 class=" text-primary">
                <?php echo esc_html($data['title']); ?>
                <?php if (!empty($data['title_suffix'])): ?>
                    <span class="text-lowercase fw-normal"> at </span>
                    <?php if (!empty($data['title_suffix_url'])): ?>
                        <a href="<?php echo esc_url($data['title_suffix_url']); ?>" class="text-info text-decoration-none hover-underline"><?php echo esc_html($data['title_suffix']); ?></a>
                    <?php else: ?>
                        <?php echo esc_html($data['title_suffix']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </h1>
            <!-- Specs -->

            <?php if (!empty($data['specs'])): ?>
                <div class="mb-2">
                    <p class="mb-0 h5 text-dark">
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
                    </p>
                </div>
            <?php endif; ?>
            <!-- Address (single-line format for single pages, without <br> tag, clickable to map) -->
            <?php if (!empty($data['address']) || !empty($data['city']) || !empty($data['state']) || !empty($data['zip'])): ?>
                <div class="mb-2">
                    <p class="mb-0">
                        <strong>Address:</strong>
                        <?php if (!empty($data['map_url'])): ?>
                            <a href="<?php echo esc_url($data['map_url']); ?>" class="text-info text-decoration-none hover-underline" target="_blank" rel="noopener noreferrer">
                                <?php echo burgland_homes_format_address($data, false); ?>
                            </a>
                        <?php else: ?>
                            <?php echo burgland_homes_format_address($data, false); ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-5 col-xl-4">
            <?php if (!empty($data['price'])): ?>
                <h3 class="fw-semibold mb-2 text-primary text-end"><span class="text-info small me-2 fw-normal">From</span><?php echo esc_html($data['price']); ?></h3>

              
                    <?php
                    if (!empty($data['post_type']) && $data['post_type'] === 'bh_floor_plan') {
                        echo '<p class=" mb-0 text-muted text-end">Hello world Floor</p>';
                    } elseif (!empty($data['post_type']) && $data['post_type'] === 'bh_lot') {
                        echo '<p class="mb-0 text-muted text-end lh-1.1">* Inventory home price above includes pre-selected
homesite, flex options & design upgrades.</p>';
                    } else {
                        if (!empty($data['city']) || !empty($data['state'])) {?>
                          <h5 class=" mb-0 text-primary text-end"><?php
                            echo esc_html($data['status']['label']) . ' in ' . esc_html($data['city']);
                            if (!empty($data['city']) && !empty($data['state'])) {
                                echo ', ';
                            }
                            echo esc_html($data['state']);
                            ?></h5><?php
                        }
                    }
                    ?>
              

            <?php endif; ?>
        </div>

    </div>
</section>