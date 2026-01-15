<?php
/**
 * Single Sidebar Contact Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type string $phone
 *     @type array $brochure
 *     @type string $button_text
 * }
 */
if (!defined('ABSPATH')) exit;
$title = isset($args['title']) ? $args['title'] : 'Interested?';
$phone = isset($args['phone']) ? $args['phone'] : '(800) 555-0192';
$brochure = isset($args['brochure']) ? $args['brochure'] : null;
$button_text = isset($args['button_text']) ? $args['button_text'] : 'Schedule a Visit';
?>
<div class="card mb-4 shadow-sm border-0 bg-light">
    <div class="card-body p-4">
        <h3 class="h5 card-title mb-4"><?php echo esc_html($title); ?></h3>
        <div class="d-grid gap-3">
            <a href="#contact-form" class="btn btn-primary btn-lg"><?php echo esc_html($button_text); ?></a>
            
            <?php if ($phone) : ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-telephone me-2"></i> <?php echo esc_html($phone); ?>
                </a>
            <?php endif; ?>

            <?php if ($brochure && !empty($brochure['url'])) : ?>
                <a href="<?php echo esc_url($brochure['url']); ?>" class="btn btn-outline-secondary" download>
                    <i class="bi bi-download me-2"></i> Download Brochure
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
