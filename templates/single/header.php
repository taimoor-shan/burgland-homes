<?php
/**
 * Single Header Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type string $subtitle
 *     @type string $price
 *     @type array $breadcrumbs
 *     @type array $status { label, class }
 * }
 */
if (!defined('ABSPATH')) exit;
$data = $args;
?>
<section class="page-header bg-light py-5 mb-5">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo home_url(); ?>">Home</a></li>
                <?php foreach ($data['breadcrumbs'] as $crumb): ?>
                    <?php if (!empty($crumb['url'])): ?>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['label']); ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($crumb['label']); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        
        <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
            <h1 class="display-4 fw-light mb-0"><?php echo esc_html($data['title']); ?></h1>
            <?php if (!empty($data['status'])): ?>
                <span class="badge bg-<?php echo esc_attr($data['status']['class']); ?> text-uppercase fs-6">
                    <?php echo esc_html($data['status']['label']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($data['subtitle'])): ?>
            <p class="lead text-muted mb-2"><?php echo esc_html($data['subtitle']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($data['price'])): ?>
            <p class="h3 text-primary fw-bold mb-0"><?php echo esc_html($data['price']); ?></p>
        <?php endif; ?>
    </div>
</section>
