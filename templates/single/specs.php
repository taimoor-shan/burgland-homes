<?php
/**
 * Single Specs Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type array $specs [ { label, value, icon } ]
 * }
 */
if (!defined('ABSPATH')) exit;
$title = isset($args['title']) ? $args['title'] : 'Specifications';
$specs = isset($args['specs']) ? $args['specs'] : array();
?>
<div class="card mb-4">
    <div class="card-body pb-4">
        <h3 class="card-title mb-4"><?php echo esc_html($title); ?></h3>
        <div class="row g-4">
            <?php foreach ($specs as $spec) : ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($spec['icon'])): ?>
                            <i class="bi bi-<?php echo esc_attr($spec['icon']); ?> fs-3 text-primary"></i>
                        <?php endif; ?>
                        <div>
                            <p class="small text-muted mb-0"><?php echo esc_html($spec['label']); ?></p>
                            <p class="h5 mb-0 fw-bold"><?php echo esc_html($spec['value']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
