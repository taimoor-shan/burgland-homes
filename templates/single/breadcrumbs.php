<?php
/**
 * Breadcrumbs Component
 * 
 * @param array $args {
 *     @type array $breadcrumbs Array of breadcrumb items
 * }
 */
if (!defined('ABSPATH')) exit;
$data = $args;
?>
<style>
.breadcrumbs-section .breadcrumb-item a {
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: text-decoration 0.2s ease;
}

.breadcrumbs-section .breadcrumb-item a:hover {
    text-decoration: underline;
}
</style>
<section class="breadcrumbs-section bg-light py-3">
    <div class="container-fluid px-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item text-info"><a href="<?php echo home_url(); ?>">Home</a></li>
                <?php foreach ($data['breadcrumbs'] as $crumb): ?>
                    <?php if (!empty($crumb['url'])): ?>
                        <li class="breadcrumb-item text-info"><a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['label']); ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo esc_html($crumb['label']); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
    </div>
</section>
