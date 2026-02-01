<?php

/**
 * Single Sidebar Contact Component
 * 
 * Displays a featured team member/agent contact card
 * 
 * @param array $args {
 *     @type array  $team_member Team member data array (optional - will auto-fetch if not provided)
 *     @type string $heading Optional heading text
 *     @type string $button_text Optional button text
 *     @type string $button_link Optional button link
 * }
 */
if (!defined('ABSPATH')) exit;

// Extract arguments
$team_member = isset($args['team_member']) ? $args['team_member'] : null;
$heading = isset($args['heading']) ? $args['heading'] : (isset($args['title']) ? $args['title'] : 'Our Online New Home Counselors');
$button_text = isset($args['button_text']) ? $args['button_text'] : 'Get Started';
$button_link = isset($args['button_link']) ? $args['button_link'] : '#contact';

// If no team member data provided, fetch featured team member automatically
if (!$team_member) {
    if (function_exists('burgland_homes_get_featured_team_members')) {
        $team_members = burgland_homes_get_featured_team_members(array(
            'limit' => 1,
            'featured' => true,
        ));
        
        // If no featured member, get any team member
        if (empty($team_members)) {
            $team_members = burgland_homes_get_featured_team_members(array(
                'limit' => 1,
                'featured' => false,
            ));
        }
        
        if (!empty($team_members)) {
            $team_member = $team_members[0];
        }
    }
}

// If still no team member data, don't render
if (!$team_member) {
    return;
}

// Extract team member data
$name = isset($team_member['name']) ? $team_member['name'] : '';
$phone = isset($team_member['phone']) ? $team_member['phone'] : '';
$email = isset($team_member['email']) ? $team_member['email'] : '';
$thumbnail = isset($team_member['thumbnail']) ? $team_member['thumbnail'] : '';
$thumbnail_full = isset($team_member['thumbnail_full']) ? $team_member['thumbnail_full'] : '';
$position = isset($team_member['position']) ? $team_member['position'] : '';

// Use thumbnail or full image
$image_url = $thumbnail ? $thumbnail : $thumbnail_full;
?>

<h2 class="h3 fw-light mb-4 text-primary">
    <?php echo esc_html($heading); ?>
</h2>
<div class="osc-lockup">
    <div class="row align-items-center mb-4">
        <?php if ($image_url): ?>
        <div class="col-4">
            <div class="oi-aspect one-one">
                <img src="<?php echo esc_url($image_url); ?>"
                    loading="lazy" class="oi-aspect-img img-fluid"
                    alt="<?php echo esc_attr($name); ?>">
            </div>
        </div>
        <?php endif; ?>
        <div class="<?php echo $image_url ? 'col-8' : 'col-12'; ?>">
            <?php if ($name): ?>
            <p class="h4 mb-1"><?php echo esc_html($name); ?></p>
            <?php endif; ?>
            <?php if ($position): ?>
            <p class="text-muted small mb-2"><?php echo esc_html($position); ?></p>
            <?php endif; ?>
            <?php if ($phone): ?>
            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="osc-phone d-block"><?php echo esc_html($phone); ?></a>
            <?php endif; ?>
            <?php if ($email): ?>
            <a href="mailto:<?php echo esc_attr($email); ?>" class="d-block mt-1"><?php echo esc_html($email); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($button_text)): ?>
<a role="button" href="<?php echo esc_url($button_link); ?>" class="btn btn-outline-primary w-100"><?php echo esc_html($button_text); ?></a>
<?php endif; ?>