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
 *     @type string $price
 *     @type array $status { label, class }
 * }
 */
if (!defined('ABSPATH')) exit;
$data = $args;
?>
<section class="plugin-page-header py-3 mb-3">
      <h1 class="text-uppercase fw-semibold mb-0 text-primary">
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
    <div class="d-flex gap-3 mb-2 flex-wrap justify-content-between">
        <div class="">
            <?php if (!empty($data['address']) || (!empty($data['city']) || !empty($data['state']) || !empty($data['zip']))): ?>
                <div class="mb-2">
                    <p class="text-dark mb-1"><strong>Address:</strong></p>
                    <p class="mb-0">
                        <?php if (!empty($data['address'])): ?>
                            <?php echo esc_html($data['address']); ?><br>
                        <?php endif; ?>
                        <?php if (!empty($data['city']) || !empty($data['state']) || !empty($data['zip'])): ?>
                            <?php echo esc_html($data['city']); ?><?php if (!empty($data['city']) && (!empty($data['state']) || !empty($data['zip']))): ?>, <?php endif; ?><?php echo esc_html($data['state']); ?><?php if (!empty($data['zip'])): ?> <?php echo esc_html($data['zip']); ?><?php endif; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>


        <div>
            <?php if (!empty($data['price'])): ?>
                <h3 class="fw-semibold mb-2 text-primary"><span class="text-info small me-2 fw-normal">From</span><?php echo esc_html($data['price']); ?></h3>
                <?php if (!empty($data['status']) && (!empty($data['city']) || !empty($data['state']))): ?>
                    <h4 class="h4 mb-0 text-primary">
                        <?php echo esc_html($data['status']['label']); ?> in <?php echo esc_html($data['city']); ?><?php if (!empty($data['city']) && !empty($data['state'])): ?>, <?php endif; ?><?php echo esc_html($data['state']); ?>
                    </h4>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</section>