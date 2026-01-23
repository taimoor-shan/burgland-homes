<?php
/**
 * Single Description Component
 * 
 * @param array $args {
 *     @type string $title
 *     @type string $content
 * }
 */
if (!defined('ABSPATH')) exit;
$title = isset($args['title']) ? $args['title'] : 'About';
$content = isset($args['content']) ? $args['content'] : '';
?>
<div class="mb-5">
    <div class="pb-4 border-bottom">
        <!-- <h2 class="h3 card-title mb-3"><?php echo esc_html($title); ?></h2> -->
        <div class="bh-read-more-wrapper">
            <div class="content bh-read-more-content">
                <?php echo wp_kses_post($content); ?>
            </div>
            <button type="button" class="btn btn-info" id="bh-read-more-btn"
                    data-text-more="<?php echo esc_attr__('Read More', 'burgland-homes'); ?>" 
                    data-text-less="<?php echo esc_attr__('Read Less', 'burgland-homes'); ?>">
                <?php echo esc_html__('Read More', 'burgland-homes'); ?>
            </button>
        </div>
    </div>
</div>
