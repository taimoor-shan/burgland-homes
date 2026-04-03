<?php
/**
 * Breadcrumbs Component
 * 
 * @param array $args {
 *     @type array $breadcrumbs Array of breadcrumb items
 * }
 */
if (!defined('ABSPATH'))
    exit;
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

<?php if (false): ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-3">
            <?php foreach ($data['breadcrumbs'] as $crumb): ?>
                <?php if (!empty($crumb['url'])): ?>
                    <li class="breadcrumb-item text-info"><a
                            href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['label']); ?></a></li>

                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>

<?php endif; ?>