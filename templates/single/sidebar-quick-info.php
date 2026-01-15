<?php
/**
 * Single Sidebar Quick Info Component
 * 
 * @param array $args {
 *     @type array $info [ { label, value } ]
 * }
 */
if (!defined('ABSPATH')) exit;
$info = isset($args['info']) ? $args['info'] : array();

if (empty($info)) return;
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h3 class="h6 card-title mb-3 text-uppercase fw-bold text-muted" style="letter-spacing: 1px;">Quick Info</h3>
        <ul class="list-unstyled mb-0">
            <?php foreach ($info as $item) : ?>
                <li class="mb-2 pb-2 border-bottom last-child-border-0">
                    <span class="text-muted"><?php echo esc_html($item['label']); ?>:</span>
                    <strong class="text-dark ms-1"><?php echo esc_html($item['value']); ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
