<?php

/**
 * Floor Plan Lots Grid Component with Improved Dynamic Filters
 * 
 * Displays a filterable grid of available lots that use this floor plan
 * 
 * @param array $args {
 *     @type int $floor_plan_id - The floor plan ID to filter lots
 * }
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) exit;

$floor_plan_id = isset($args['floor_plan_id']) ? intval($args['floor_plan_id']) : 0;

if (!$floor_plan_id) {
    return;
}

$data_provider = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();

// Get all lots that reference this floor plan
$lots_query = new WP_Query(array(
    'post_type' => 'bh_lot',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'lot_floor_plan',
            'value' => $floor_plan_id,
            'compare' => '='
        )
    ),
    'orderby' => 'meta_value_num',
    'meta_key' => 'lot_price',
    'order' => 'ASC'
));

// Initialize filter data collection
$bedrooms_options = array();
$bathrooms_options = array();
$sqft_values = array(); // Collect actual sqft values
$lot_cards_data = array(); // Store lot data for rendering

if ($lots_query->have_posts()) {
    while ($lots_query->have_posts()) {
        $lots_query->the_post();
        $lot_id = get_the_ID();

        // Use data provider to handle inheritance from floor plans
        $lot_data = $data_provider->get_lot_data($lot_id);

        // Collect bedrooms
        $bedrooms = isset($lot_data['bedrooms']) ? $lot_data['bedrooms'] : '';
        if ($bedrooms !== '' && !in_array($bedrooms, $bedrooms_options)) {
            $bedrooms_options[] = $bedrooms;
        }

        // Collect bathrooms
        $bathrooms = isset($lot_data['bathrooms']) ? $lot_data['bathrooms'] : '';
        if ($bathrooms !== '' && !in_array($bathrooms, $bathrooms_options)) {
            $bathrooms_options[] = $bathrooms;
        }

        // Collect sqft values
        $sqft = isset($lot_data['square_feet']) ? $lot_data['square_feet'] : '';
        if ($sqft !== '' && is_numeric($sqft)) {
            $sqft_values[] = intval($sqft);
        }

        // Store lot data for rendering (avoid double loop)
        $price_numeric = 0;
        if (!empty($lot_data['price'])) {
            $price_numeric = intval(preg_replace('/[^0-9]/', '', $lot_data['price']));
        }
        $sqft_numeric = !empty($lot_data['square_feet']) ? intval($lot_data['square_feet']) : 0;

        $lot_cards_data[] = array(
            'lot_id' => $lot_id,
            'lot_data' => $lot_data,
            'bedrooms' => $lot_data['bedrooms'],
            'bathrooms' => $lot_data['bathrooms'],
            'sqft_numeric' => $sqft_numeric,
            'price_numeric' => $price_numeric
        );
    }
    wp_reset_postdata();
}

sort($bedrooms_options, SORT_NUMERIC);
sort($bathrooms_options, SORT_NUMERIC);

/**
 * Generate intelligent SQFT ranges based on data distribution
 * 
 * Algorithm:
 * 1. Determine ideal number of ranges based on total homes count
 * 2. Calculate adaptive step size based on data spread
 * 3. Create ranges that actually contain data
 * 4. Round to human-friendly numbers
 */
function burgland_generate_sqft_ranges_for_floor_plan($sqft_values, $total_homes) {
    if (empty($sqft_values)) {
        return array();
    }

    sort($sqft_values);
    $sqft_min = min($sqft_values);
    $sqft_max = max($sqft_values);
    $range_spread = $sqft_max - $sqft_min;

    // Edge case: all homes have same or very similar sqft
    if ($range_spread < 100) {
        return array();
    }

    // Determine ideal number of ranges based on home count
    // Fewer homes = fewer ranges, more homes = more ranges
    if ($total_homes <= 3) {
        $ideal_ranges = 2;
    } elseif ($total_homes <= 6) {
        $ideal_ranges = 3;
    } elseif ($total_homes <= 12) {
        $ideal_ranges = 4;
    } elseif ($total_homes <= 20) {
        $ideal_ranges = 5;
    } elseif ($total_homes <= 30) {
        $ideal_ranges = 6;
    } else {
        $ideal_ranges = 7;
    }

    // Calculate ideal step size
    $ideal_step = ceil($range_spread / $ideal_ranges);

    // Round step to human-friendly increments
    $step_options = array(100, 250, 500, 750, 1000, 1500, 2000, 2500, 5000, 10000, 25000, 50000);
    $step = 500; // default
    
    foreach ($step_options as $option) {
        if ($ideal_step <= $option) {
            $step = $option;
            break;
        }
    }

    // Start from a rounded number
    $start = floor($sqft_min / $step) * $step;
    
    // Generate ranges
    $ranges = array();
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

$sqft_ranges = burgland_generate_sqft_ranges_for_floor_plan($sqft_values, count($lot_cards_data));
?>

<section class="bh-lots-grid-section py-4 px-3 bg-light border mb-5">
    <div class="container-fluid">
        <!-- Section Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-primary">Available Homes with this Floor Plan</h2>
                <!-- Results Count -->
                <h6 class="text-dark">
                    Showing <span id="lots-count"><?php echo count($lot_cards_data); ?></span> Inventory Home(s) available
                </h6>
            </div>
        </div>

        <!-- Only show filters if there are lots -->
        <?php if (!empty($lot_cards_data)) : ?>

        <!-- Filters Section -->
        <section class="bh-filters mb-4">
            <form id="lots-filters" class="row g-3 align-items-end" data-floor-plan-id="<?php echo esc_attr($floor_plan_id); ?>">

                <!-- Square Footage Range -->
                <?php if (!empty($sqft_ranges)): ?>
                <div class="col-md-3">
                    <label for="sqft-filter" class="form-label fw-semibold">Sqft Range</label>
                    <select name="sqft_range" id="sqft-filter" class="form-select">
                        <option value="">All Sizes</option>
                        <?php foreach ($sqft_ranges as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Bedrooms Filter -->
                <?php if (!empty($bedrooms_options)): ?>
                <div class="col-md-3">
                    <label for="bedrooms-filter" class="form-label fw-semibold">Bedrooms</label>
                    <select name="bedrooms" id="bedrooms-filter" class="form-select">
                        <option value="">All Bedrooms</option>
                        <?php foreach ($bedrooms_options as $bedrooms): ?>
                            <option value="<?php echo esc_attr($bedrooms); ?>">
                                <?php echo esc_html($bedrooms); ?> Bed<?php echo $bedrooms > 1 ? 's' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Bathrooms Filter -->
                <?php if (!empty($bathrooms_options)): ?>
                <div class="col-md-3">
                    <label for="bathrooms-filter" class="form-label fw-semibold">Bathrooms</label>
                    <select name="bathrooms" id="bathrooms-filter" class="form-select">
                        <option value="">All Bathrooms</option>
                        <?php foreach ($bathrooms_options as $bathrooms): ?>
                            <option value="<?php echo esc_attr($bathrooms); ?>">
                                <?php echo esc_html($bathrooms); ?> Bath<?php echo $bathrooms > 1 ? 's' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Sort Order -->
                <div class="col-md-3">
                    <label for="sort-order" class="form-label fw-semibold">Sort By</label>
                    <select name="sort_order" id="sort-order" class="form-select">
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                        <option value="sqft-asc">Sqft: Low to High</option>
                        <option value="sqft-desc">Sqft: High to Low</option>
                    </select>
                </div>

                <!-- Reset Filters Button -->
                <div class="col-md-3">
                    <button type="button" id="reset-filters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> Reset Filters
                    </button>
                </div>

            </form>
        </section>

        <?php endif; ?>

        <!-- Lots Grid -->
        <div id="lots-grid-container">
            <div class="loading-spinner text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3">Loading homes...</p>
            </div>

            <div id="lots-grid" class="row g-4">
                <?php
                if (!empty($lot_cards_data)):
                    foreach ($lot_cards_data as $lot_card):
                ?>
                        <div class="col-md-6 col-lg-4 lot-card-wrapper"
                            data-bedrooms="<?php echo esc_attr($lot_card['bedrooms']); ?>"
                            data-bathrooms="<?php echo esc_attr($lot_card['bathrooms']); ?>"
                            data-sqft="<?php echo esc_attr($lot_card['sqft_numeric']); ?>"
                            data-price="<?php echo esc_attr($lot_card['price_numeric']); ?>"
                            data-lot-id="<?php echo esc_attr($lot_card['lot_id']); ?>">
                            <?php
                            // Render lot card using the template loader's render_card method
                            $template_loader->render_card($lot_card['lot_id']);
                            ?>
                        </div>
                    <?php
                    endforeach;
                else:
                    ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                            <p class="mb-0">No homes available with this floor plan at the moment. Please check back later.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- No Results Message (Hidden by default) -->
            <div id="no-lots-message" class="col-12" style="display: none;">
                <div class="alert alert-info text-center" role="alert">
                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                    <p class="mb-0">No homes found matching your criteria. Please adjust your filters.</p>
                </div>
            </div>
        </div>

    </div>
</section>
