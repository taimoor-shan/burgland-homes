<?php
/**
 * Single Amenities Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type array $items
 * }
 */
if (!defined('ABSPATH')) exit;
$title = isset($args['title']) ? $args['title'] : 'Amenities';
$items = isset($args['items']) ? $args['items'] : array();

if (empty($items)) return;
?>
<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5 card-title mb-3"><?php echo esc_html($title); ?></h2>
        <div class="row">
            <?php foreach ($items as $item): ?>
                <div class="col-md-6 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span><?php echo esc_html($item); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
