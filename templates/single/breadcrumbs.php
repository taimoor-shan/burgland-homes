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
<section class="breadcrumbs-section bg-light py-3 mt-10">
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
