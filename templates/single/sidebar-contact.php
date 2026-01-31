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
<!-- <div class="card mb-4 shadow-sm border-0 bg-light">
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
</div> -->

<h2 class="h3 fw-light mb-4">

    Our Online New Home Counselors

</h2>
<div class="osc-lockup">
    <div class="row align-items-center mb-4">
        <div class="col-4">
            <div class="oi-aspect one-one">
                <img src="https://media.parksquarehomes.com/393/2025/8/1/Bianca_NewPic_080125.JPG?width=364&amp;height=300&amp;fit=bounds&amp;b=d960e52&amp;ois=9811eb1"
                    srcset="https://media.parksquarehomes.com/393/2025/8/1/Bianca_NewPic_080125.JPG?width=300&amp;height=247&amp;fit=bounds&amp;b=d960e52&amp;ois=a92eace 300w, https://media.parksquarehomes.com/393/2025/8/1/Bianca_NewPic_080125.JPG?width=364&amp;height=300&amp;fit=bounds&amp;b=d960e52&amp;ois=9811eb1 400w, https://media.parksquarehomes.com/393/2025/8/1/Bianca_NewPic_080125.JPG?width=911&amp;height=750&amp;fit=bounds&amp;b=d960e52&amp;ois=44ef419 1000w, https://media.parksquarehomes.com/393/2025/8/1/Bianca_NewPic_080125.JPG?width=1088&amp;height=896&amp;fit=bounds&amp;b=d960e52&amp;ois=efc8b35 1920w"
                    sizes="(min-width: 1400px) 800px, 100vw" loading="lazy" class="oi-aspect-img img-fluid"
                    alt="Bianca_NewPic Ai. 1,811sf New Home in Venice, FL">
            </div>
        </div>
        <div class="col-8">
            <p class="osc-names">Contact Bianca </p>
            <a href="tel:844.774.4636" class="osc-phone">844.774.4636</a>
        </div>
    </div>
</div>

<a role="button" href="#contact" class="btn btn-lg btn-outline-primary w-100">Get Started</a>