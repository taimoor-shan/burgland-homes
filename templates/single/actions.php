<?php

/**
 * Single Actions Component - Sticky Scrollspy Navigation
 * 
 * @param array $args {
 *     @type array $sections Array of sections { id, label } for navigation links
 *     @type array $brochure { url, title } (Optional - Community/Lot brochure)
 *     @type array $floor_plan_pdf (Optional - Floor plan PDF { url, title })
 *     @type array $design_packages (Optional - Design packages PDF { url, title })
 *     @type string $map_anchor (Optional - ID anchor for site map, community pages only)
 * }
 */
if (!defined('ABSPATH')) exit;

$sections = isset($args['sections']) ? $args['sections'] : array();
$brochure = isset($args['brochure']) ? $args['brochure'] : null;
$floor_plan_pdf = isset($args['floor_plan_pdf']) ? $args['floor_plan_pdf'] : null;
$design_packages = isset($args['design_packages']) ? $args['design_packages'] : null;
$map_anchor = isset($args['map_anchor']) ? $args['map_anchor'] : '';

// Count navigation items
$has_nav_items = !empty($sections);

// Count action buttons
$has_buttons = ($brochure && !empty($brochure['url'])) || 
               ($floor_plan_pdf && !empty($floor_plan_pdf['url'])) || 
               ($design_packages && !empty($design_packages['url'])) ||
               !empty($map_anchor);

// Only show navigation if there are items
if (!$has_nav_items && !$has_buttons) {
    return;
}
?>
<nav id="subnav-detail" class="navbar-subnav navbar navbar-expand-lg border-bottom bg-grey py-0" style="position: sticky; top: var(--header-height, 134px); z-index: 1020;" aria-label="Secondary Navigation">
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
                            <i class="fa-solid fa-file-circle-check me-1"></i>
                            <?php echo esc_html($brochure['title'] ?? 'Brochure'); ?>
                            <span class="visually-hidden">PDF Download</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($floor_plan_pdf && !empty($floor_plan_pdf['url'])): ?>
                    <li class="nav-item d-none d-lg-block me-2">
                        <a href="<?php echo esc_url($floor_plan_pdf['url']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            <?php echo esc_html($floor_plan_pdf['title'] ?? 'Floor Plan'); ?>
                            <span class="visually-hidden">PDF Download</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($design_packages && !empty($design_packages['url'])): ?>
                    <li class="nav-item d-none d-lg-block me-2">
                        <a href="<?php echo esc_url($design_packages['url']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fa-solid fa-palette me-1"></i>
                            <?php echo esc_html($design_packages['title'] ?? 'Design Packages'); ?>
                            <span class="visually-hidden">PDF Download</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (!empty($map_anchor)): ?>
                    <li class="nav-item d-none d-lg-block">
                        <a href="<?php echo esc_url($map_anchor); ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-map-location-dot me-1"></i>
                            Site Map
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</nav>