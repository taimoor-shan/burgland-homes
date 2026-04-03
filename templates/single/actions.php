<?php

/**
 * Single Actions Component - Sticky Scrollspy Navigation
 * 
 * @param array $args {
 *     @type array $sections Array of sections { id, label } for navigation links
 *     @type array $brochure (Optional - Community Brochure or Floor Plan Brochure { url, title })
 *     @type array $design_package (Optional - Design Package { url, title })
 *     @type string $map_anchor (Optional - ID anchor for site map, community pages only)
 * }
 */
if (!defined('ABSPATH')) exit;

$sections = isset($args['sections']) ? $args['sections'] : array();
$brochure = isset($args['brochure']) ? $args['brochure'] : null;
$design_package = isset($args['design_package']) ? $args['design_package'] : null;
$map_anchor = isset($args['map_anchor']) ? $args['map_anchor'] : '';
$brochure_label = isset($args['brochure_label']) ? $args['brochure_label'] : 'Brochure';

// Count navigation items
$has_nav_items = !empty($sections);

// Count action buttons
$has_buttons = ($brochure && !empty($brochure['url'])) || 
               ($design_package && !empty($design_package['url'])) ||
               !empty($map_anchor);

// Only show navigation if there are items
if (!$has_nav_items && !$has_buttons) {
    return;
}
?>
<nav id="subnav-detail" class="d-none d-lg-block navbar-subnav navbar navbar-expand-lg border-bottom bg-grey py-0" style="position: sticky; top: var(--header-height, 144px); z-index: 10;" aria-label="Secondary Navigation">
    <div class="container-fluid">
        <?php if ($has_nav_items): ?>
            <ul class="navbar-nav me-auto" id="scrollspy-nav">
                <?php foreach ($sections as $section): ?>
                    <?php if (!empty($section['id']) && !empty($section['label'])): ?>
                        <li class="nav-item">
                            <a href="#<?php echo esc_attr($section['id']); ?>" class="nav-link px-lg-3 py-lg-4 text-capitalize"><?php echo esc_html($section['label']); ?></a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($has_buttons): ?>
            <ul class="navbar-nav ms-auto">
                <?php if ($brochure && !empty($brochure['url'])): ?>
                    <li class="nav-item d-none d-lg-block me-2">
                        <a href="<?php echo esc_url($brochure['url']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            <?php echo esc_html($brochure_label); ?>
                            <span class="visually-hidden">PDF Download</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($design_package && !empty($design_package['url'])): ?>
                    <li class="nav-item d-none d-lg-block me-2">
                        <a href="<?php echo esc_url($design_package['url']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-palette me-1"></i>
                            Design Package
                            <span class="visually-hidden">PDF Download</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</nav>