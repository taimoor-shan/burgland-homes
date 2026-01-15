<?php
/**
 * Single Layout Wrapper
 * 
 * @param array $args {
 *     @type string $content Main content HTML
 *     @type string $sidebar Sidebar HTML
 * }
 */
if (!defined('ABSPATH')) exit;
?>
<main id="site-main" class="bg-white">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <div class="main-content-area">
                    <?php echo $args['content']; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sidebar-area sticky-top" style="top: 100px;">
                    <?php echo $args['sidebar']; ?>
                </div>
            </div>
        </div>
    </div>
</main>
