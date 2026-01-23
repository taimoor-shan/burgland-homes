<?php
/**
 * Archive Floor Plans Template
 *
 * Displays a filterable grid of all floor plans across communities.
 * Layout and filtering are based on the lots grid component.
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$data_provider   = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();

// Query all floor plans
$floor_plans_query = new WP_Query(array(
    'post_type'      => 'bh_floor_plan',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
));

// Initialize filter data collection
$bedrooms_options         = array();
$bathrooms_options        = array();
$sqft_values              = array();
$floor_plan_cards_data    = array();
$community_ids_for_filter = array();

if ($floor_plans_query->have_posts()) {
    while ($floor_plans_query->have_posts()) {
        $floor_plans_query->the_post();
        $floor_plan_id = get_the_ID();

        // Get standardized floor plan data
        $floor_plan_data = $data_provider->get_floor_plan_data($floor_plan_id);

        // Collect bedrooms
        $bedrooms = isset($floor_plan_data['bedrooms']) ? $floor_plan_data['bedrooms'] : '';
        if ($bedrooms !== '' && !in_array($bedrooms, $bedrooms_options, true)) {
            $bedrooms_options[] = $bedrooms;
        }

        // Collect bathrooms
        $bathrooms = isset($floor_plan_data['bathrooms']) ? $floor_plan_data['bathrooms'] : '';
        if ($bathrooms !== '' && !in_array($bathrooms, $bathrooms_options, true)) {
            $bathrooms_options[] = $bathrooms;
        }

        // Collect sqft values
        $sqft = isset($floor_plan_data['square_feet']) ? $floor_plan_data['square_feet'] : '';
        if ($sqft !== '' && is_numeric($sqft)) {
            $sqft_values[] = (int) $sqft;
        }

        // Determine numeric values for data attributes
        $price_numeric = 0;
        if (!empty($floor_plan_data['price'])) {
            $price_numeric = (int) preg_replace('/[^0-9]/', '', $floor_plan_data['price']);
        }
        $sqft_numeric = !empty($floor_plan_data['square_feet']) ? (int) $floor_plan_data['square_feet'] : 0;

        // Communities relationship for filter (ACF relationship field allowing multiple)
        $community_ids = get_field('floor_plans_communities', $floor_plan_id);
        $communities   = array();
        if (is_array($community_ids) && !empty($community_ids)) {
            foreach ($community_ids as $cid) {
                $cid = (int) $cid;
                if ($cid > 0) {
                    $communities[]             = $cid;
                    $community_ids_for_filter[] = $cid;
                }
            }
        }

        // Store floor plan data for rendering
        $floor_plan_cards_data[] = array(
            'floor_plan_id' => $floor_plan_id,
            'data'          => $floor_plan_data,
            'bedrooms'      => $bedrooms,
            'bathrooms'     => $bathrooms,
            'sqft_numeric'  => $sqft_numeric,
            'price_numeric' => $price_numeric,
            'communities'   => $communities,
        );
    }
    wp_reset_postdata();
}

sort($bedrooms_options, SORT_NUMERIC);
sort($bathrooms_options, SORT_NUMERIC);

/**
 * Generate intelligent SQFT ranges based on data distribution
 * (copied from lots grid for consistency)
 *
 * @param array $sqft_values
 * @param int   $total_items
 * @return array
 */
function burgland_homes_generate_sqft_ranges_for_floor_plans($sqft_values, $total_items) {
    if (empty($sqft_values)) {
        return array();
    }

    sort($sqft_values);
    $sqft_min     = min($sqft_values);
    $sqft_max     = max($sqft_values);
    $range_spread = $sqft_max - $sqft_min;

    // Edge case: all floor plans have same or very similar sqft
    if ($range_spread < 100) {
        return array();
    }

    // Determine ideal number of ranges based on count
    if ($total_items <= 3) {
        $ideal_ranges = 2;
    } elseif ($total_items <= 6) {
        $ideal_ranges = 3;
    } elseif ($total_items <= 12) {
        $ideal_ranges = 4;
    } elseif ($total_items <= 20) {
        $ideal_ranges = 5;
    } elseif ($total_items <= 30) {
        $ideal_ranges = 6;
    } else {
        $ideal_ranges = 7;
    }

    // Calculate ideal step size
    $ideal_step = (int) ceil($range_spread / $ideal_ranges);

    // Round step to human-friendly increments
    $step_options = array(100, 250, 500, 750, 1000, 1500, 2000, 2500, 5000, 10000, 25000, 50000);
    $step         = 500; // default

    foreach ($step_options as $option) {
        if ($ideal_step <= $option) {
            $step = $option;
            break;
        }
    }

    // Start from a rounded number
    $start = (int) floor($sqft_min / $step) * $step;

    // Generate ranges
    $ranges  = array();
    $current = $start;

    while ($current < $sqft_max) {
        $range_min = $current;
        $range_max = $current + $step - 1;

        // Check if this range contains any actual data
        $has_data = false;
        foreach ($sqft_values as $sqft) {
            if ($sqft >= $range_min && $sqft <= $range_max) {
                $has_data = true;
                break;
            }
        }

        // Only add ranges that contain data
        if ($has_data) {
            $ranges["$range_min-$range_max"] = number_format($range_min) . ' - ' . number_format($range_max) . ' sqft';
        }

        $current += $step;

        // Safety limit to prevent infinite loops
        if (count($ranges) > 15) {
            break;
        }
    }

    return $ranges;
}

$sqft_ranges = burgland_homes_generate_sqft_ranges_for_floor_plans($sqft_values, count($floor_plan_cards_data));

// Build community filter options based on related communities
$community_options = array();
if (!empty($community_ids_for_filter)) {
    $unique_ids = array_unique(array_map('intval', $community_ids_for_filter));

    if (!empty($unique_ids)) {
        $communities = get_posts(array(
            'post_type'      => 'bh_community',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'post__in'       => $unique_ids,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));

        foreach ($communities as $community) {
            $community_options[$community->ID] = $community->post_title;
        }
    }
}
?>

<main id="site-main">
    <section class="bh-lots-grid-section py-4 px-3 bg-light border mb-5">
        <div class="container-fluid">
            <!-- Section Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="text-primary">Available Floor Plans</h1>
                    <h6 class="text-dark">
                        Showing <span id="lots-count"><?php echo count($floor_plan_cards_data); ?></span> Floor Plan(s) across all communities
                    </h6>
                </div>
            </div>

            <!-- Filters Section -->
            <section class="bh-filters mb-4">
                <form id="lots-filters" class="row g-3 align-items-end">

                    <!-- Community Filter -->
                    <?php if (!empty($community_options)) : ?>
                        <div class="col-md-3 col-lg-2">
                            <label for="community-filter" class="form-label fw-semibold">Community</label>
                            <select name="community" id="community-filter" class="form-select">
                                <option value="">All Communities</option>
                                <?php foreach ($community_options as $community_id => $community_label) : ?>
                                    <option value="<?php echo esc_attr($community_id); ?>"><?php echo esc_html($community_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Square Footage Range -->
                    <?php if (!empty($sqft_ranges)) : ?>
                        <div class="col-md-3 col-lg-2">
                            <label for="sqft-filter" class="form-label fw-semibold">Sqft Range</label>
                            <select name="sqft_range" id="sqft-filter" class="form-select">
                                <option value="">All Sizes</option>
                                <?php foreach ($sqft_ranges as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Bedrooms Filter -->
                    <?php if (!empty($bedrooms_options)) : ?>
                        <div class="col-md-3 col-lg-2">
                            <label for="bedrooms-filter" class="form-label fw-semibold">Bedrooms</label>
                            <select name="bedrooms" id="bedrooms-filter" class="form-select">
                                <option value="">All Bedrooms</option>
                                <?php foreach ($bedrooms_options as $bedrooms) : ?>
                                    <option value="<?php echo esc_attr($bedrooms); ?>">
                                        <?php echo esc_html($bedrooms); ?> Bed<?php echo $bedrooms > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Bathrooms Filter -->
                    <?php if (!empty($bathrooms_options)) : ?>
                        <div class="col-md-3 col-lg-2">
                            <label for="bathrooms-filter" class="form-label fw-semibold">Bathrooms</label>
                            <select name="bathrooms" id="bathrooms-filter" class="form-select">
                                <option value="">All Bathrooms</option>
                                <?php foreach ($bathrooms_options as $bathrooms) : ?>
                                    <option value="<?php echo esc_attr($bathrooms); ?>">
                                        <?php echo esc_html($bathrooms); ?> Bath<?php echo $bathrooms > 1 ? 's' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Sort Order -->
                    <div class="col-md-3 col-lg-2">
                        <label for="sort-order" class="form-label fw-semibold">Sort By</label>
                        <select name="sort_order" id="sort-order" class="form-select">
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="sqft-asc">Sqft: Low to High</option>
                            <option value="sqft-desc">Sqft: High to Low</option>
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <div class="col-md-3 col-lg-2">
                        <button type="button" id="reset-filters" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Reset Filters
                        </button>
                    </div>

                </form>
            </section>

            <!-- Floor Plans Grid -->
            <div id="lots-grid-container">
                <div class="loading-spinner text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3">Loading floor plans...</p>
                </div>

                <div id="lots-grid" class="row g-4">
                    <?php if (!empty($floor_plan_cards_data)) : ?>
                        <?php foreach ($floor_plan_cards_data as $card) : ?>
                            <?php
                            $communities_attr = '';
                            if (!empty($card['communities'])) {
                                $communities_attr = implode(',', array_map('intval', $card['communities']));
                            }
                            ?>
                            <div class="col-md-6 col-lg-3 lot-card-wrapper"
                                data-bedrooms="<?php echo esc_attr($card['bedrooms']); ?>"
                                data-bathrooms="<?php echo esc_attr($card['bathrooms']); ?>"
                                data-sqft="<?php echo esc_attr($card['sqft_numeric']); ?>"
                                data-price="<?php echo esc_attr($card['price_numeric']); ?>"
                                data-lot-id="<?php echo esc_attr($card['floor_plan_id']); ?>"
                                data-communities="<?php echo esc_attr($communities_attr); ?>">
                                <?php $template_loader->render_card($card['floor_plan_id']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center" role="alert">
                                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                <p class="mb-0">No floor plans are currently available. Please check back later.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- No Results Message (Hidden by default) -->
                <div id="no-lots-message" class="col-12" style="display: none;">
                    <div class="alert alert-info text-center" role="alert">
                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                        <p class="mb-0">No floor plans found matching your criteria. Please adjust your filters.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<?php get_footer();
