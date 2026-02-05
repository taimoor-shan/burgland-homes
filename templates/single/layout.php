<?php

/**
 * Single Layout Wrapper
 * 
 * @param array $args {
 *     @type string $content Main content HTML
 *     @type string $sidebar Sidebar HTML
 * }
 */
if (!defined('ABSPATH'))
    exit;
?>
<main id="site-main" class="bg-white">
    <div class="container-fluid g-0">
        <div class="row mb-4">
            <div class="col-12">
                <div class="main-content-area">
                    <?php echo $args['content']; ?>
                    <?php echo $args['sidebar']; ?>
                </div>
            </div>
        </div>
        <div class="row border-top border-secondary" id="contact">

            <div class="col-12 col-lg-6 d-flex flex-column  bg-light  p-3 p-lg-4 p-xl-5 py-xxl-6 px-xxl-6">
                <div class="bg-white p-3 p-lg-4 p-xl-5">

                    <h2 class="fw-light text-primary mb-4 pt-3">Visit Our Community Sales Office</h2>
                    <a target="_blank" href="">
                        <address class="fs-5">
                            <?php b5st_display_address('', false); ?>
                        </address>
                    </a>
                    <h3 class="fs-5">Sales Office Hours:</h3>
                    <p class=" border-bottom pb-4"> <?php b5st_display_business_hours('text-muted', false); ?></p>
                    <?php
                    // Display featured agent using shortcode
                    if (function_exists('do_shortcode')) {
                        echo do_shortcode('[featured_agent heading="Have Questions?" button_text="" button_link=""]');
                    }
                    ?>

                </div>
            </div>
            <div class="col-12 col-lg-6  p-lg-3 bg-muted">
                <div class="py-3 p-lg-4 p-xl-5 h-100 d-flex flex-column justify-content-center single-column">
                    <div class="container text-white">
                        <div class="row">
                            <div class="col-12">
                                <?php echo do_shortcode('[hubspot type="form" portal="245075458" id="8d4073b6-6f8e-4011-8680-f9239dc2fc19"]'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>