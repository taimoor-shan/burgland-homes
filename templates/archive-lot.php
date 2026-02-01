<?php
/**
 * Archive Lots/Homes Template
 *
 * Displays a filterable grid of all lots/homes across communities.
 * Layout and filtering are based on the single-community lots grid component,
 * but without restricting results to a single community.
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$data_provider   = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();

// Query all lots/homes
$lots_query = new WP_Query(array(
    'post_type'      => 'bh_lot',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'meta_value_num',
    'meta_key'       => 'lot_price',
    'order'          => 'ASC',
));

// Initialize filter data collection
$bedrooms_options        = array();
$bathrooms_options       = array();
$sqft_values             = array();
$lot_cards_data          = array();
$community_ids_for_filter = array();

if ($lots_query->have_posts()) {
    while ($lots_query->have_posts()) {
        $lots_query->the_post();
        $lot_id = get_the_ID();

        // Use data provider to handle inheritance from floor plans
        $lot_data = $data_provider->get_lot_data($lot_id);

        // Collect bedrooms
        $bedrooms = isset($lot_data['bedrooms']) ? $lot_data['bedrooms'] : '';
        if ($bedrooms !== '' && !in_array($bedrooms, $bedrooms_options, true)) {
            $bedrooms_options[] = $bedrooms;
        }

        // Collect bathrooms
        $bathrooms = isset($lot_data['bathrooms']) ? $lot_data['bathrooms'] : '';
        if ($bathrooms !== '' && !in_array($bathrooms, $bathrooms_options, true)) {
            $bathrooms_options[] = $bathrooms;
        }

        // Collect sqft values
        $sqft = isset($lot_data['square_feet']) ? $lot_data['square_feet'] : '';
        if ($sqft !== '' && is_numeric($sqft)) {
            $sqft_values[] = (int) $sqft;
        }

        // Determine numeric values for data attributes
        $price_numeric = 0;
        if (!empty($lot_data['price'])) {
            $price_numeric = (int) preg_replace('/[^0-9]/', '', $lot_data['price']);
        }
        $sqft_numeric = !empty($lot_data['square_feet']) ? (int) $lot_data['square_feet'] : 0;

        // Community relationship for filter
        $lot_community_id = get_post_meta($lot_id, 'lot_community', true);
        $communities      = array();
        if (!empty($lot_community_id)) {
            $communities[]           = (int) $lot_community_id;
            $community_ids_for_filter[] = (int) $lot_community_id;
        }

        // Store lot data for rendering
        $lot_cards_data[] = array(
            'lot_id'       => $lot_id,
            'lot_data'     => $lot_data,
            'lot_location' => $data_provider->get_lot_location_data($lot_id),
            'bedrooms'     => $bedrooms,
            'bathrooms'    => $bathrooms,
            'sqft_numeric' => $sqft_numeric,
            'price_numeric'=> $price_numeric,
            'communities'  => $communities,
        );
    }
    wp_reset_postdata();
}

sort($bedrooms_options, SORT_NUMERIC);
sort($bathrooms_options, SORT_NUMERIC);

/**
 * Generate intelligent SQFT ranges based on data distribution
 * (copied from single/lots-grid.php for consistency)
 *
 * @param array $sqft_values
 * @param int   $total_homes
 * @return array
 */
function burgland_homes_generate_sqft_ranges_for_archive($sqft_values, $total_homes) {
    if (empty($sqft_values)) {
        return array();
    }

    sort($sqft_values);
    $sqft_min     = min($sqft_values);
    $sqft_max     = max($sqft_values);
    $range_spread = $sqft_max - $sqft_min;

    // Edge case: all homes have same or very similar sqft
    if ($range_spread < 100) {
        return array();
    }

    // Determine ideal number of ranges based on home count
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

$sqft_ranges = burgland_homes_generate_sqft_ranges_for_archive($sqft_values, count($lot_cards_data));

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

// Get archive settings from options
$archive_title = get_option('bh_archive_lots_title', 'Available Homes');
$archive_subtitle = get_option('bh_archive_lots_subtitle', 'Find Your Dream Property');
$archive_image_id = get_option('bh_archive_lots_image', '');
$background_url = $archive_image_id ? wp_get_attachment_url($archive_image_id) : '';
?>

<main id="site-main">
    <!-- Page Header Component -->
    <header class="page-header container-fluid d-flex justify-content-start align-items-end" style="background: <?php echo $background_url ? 'url(' . esc_url($background_url) . ') center center no-repeat' : '#f8f9fa'; ?>; background-size: cover;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 text-start">
                    <h1 class="display-5">
                        <?php echo esc_html($archive_title); ?>
                    </h1>
                    
                    <?php if ($archive_subtitle): ?>
                        <p class="page-excerpt text-uppercase text-white lead">
                            <?php echo esc_html($archive_subtitle); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <div class="lots-archive">
        <!-- Filters Section -->
        <section class="bh-filters filters-section border-bottom py-4 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-dark mb-3">
                            Showing <span id="lots-count"><?php echo count($lot_cards_data); ?></span> Inventory Home(s) across all communities. 
                            <button type="button" id="reset-filters" class="border-0 text-secondary text-underline ms-3">
                                Reset Filters
                            </button>
                        </h6>
                        
                        <!-- Mobile Filter Toggle Button -->
                        <div class="d-md-none mb-3">
                            <button type="button" id="filter-toggle" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-between">
                                <span>Filter By</span>
                                <i class="bi bi-chevron-down filter-toggle-icon"></i>
                            </button>
                        </div>

                        <!-- Filter Form Container -->
                        <div id="filter-form-container" class="filter-form-container">
                            <form id="lots-filters" class="row g-3 align-items-end">
                            <!-- Community Filter -->
                            <?php if (!empty($community_options)) : ?>
                                <div class="col-md-3">
                                    <label for="community-filter" class="form-label text-info">Community</label>
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
                                <div class="col-md-3">
                                    <label for="sqft-filter" class="form-label text-info">Sqft Range</label>
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
                                <div class="col-md-3">
                                    <label for="bedrooms-filter" class="form-label text-info">Bedrooms</label>
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
                                <div class="col-md-3">
                                    <label for="bathrooms-filter" class="form-label text-info">Bathrooms</label>
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
                        </form>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <!-- Main Content: Two Column Layout -->
        <section class="lots-content">
            <div class="container-fluid bg-warning">
                <div class="row">
                    <!-- Left Column: Lot Cards -->
                    <div class="col-lg-6 bh-listings-column col-12 col-lg-7 order-1 order-lg-2 pt-4 pe-lg-4">
                        <div id="lots-grid" class="row g-4 align-items-stretch mb-5">
                            <?php if (!empty($lot_cards_data)) : ?>
                                <?php foreach ($lot_cards_data as $lot_card) : ?>
                                    <?php
                                    $communities_attr = '';
                                    if (!empty($lot_card['communities'])) {
                                        $communities_attr = implode(',', array_map('intval', $lot_card['communities']));
                                    }
                                    $lot_location = $lot_card['lot_location'];
                                    ?>
                                    <div class="col-md-6 lot-card-wrapper"
                                        data-lat="<?php echo esc_attr($lot_location['latitude']); ?>"
                                        data-lng="<?php echo esc_attr($lot_location['longitude']); ?>"
                                        data-id="<?php echo esc_attr($lot_card['lot_id']); ?>"
                                        data-bedrooms="<?php echo esc_attr($lot_card['bedrooms']); ?>"
                                        data-bathrooms="<?php echo esc_attr($lot_card['bathrooms']); ?>"
                                        data-sqft="<?php echo esc_attr($lot_card['sqft_numeric']); ?>"
                                        data-price="<?php echo esc_attr($lot_card['price_numeric']); ?>"
                                        data-communities="<?php echo esc_attr($communities_attr); ?>">
                                        <?php $template_loader->render_card($lot_card['lot_id']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center" role="alert">
                                        <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                        <p class="mb-0">No homes are currently available. Please check back later.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- No Results Message (Hidden by default) -->
                            <div id="no-lots-message" class="col-12" style="display: none;">
                                <div class="alert alert-info text-center" role="alert">
                                    <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                    <p class="mb-0">No homes found matching your criteria. Please adjust your filters.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Map -->
                    <div class="col-12 col-lg-5 order-2 order-lg-1 ps-lg-0 bh-map-column">
                        <div class="bh-map-container map-container ">
                            <div id="lots-map" style="height: calc(100vh - 180px); min-height: 600px; background: #e9ecef; border-radius: 8px;">
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <div class="text-center">
                                        <i class="bi bi-map fs-1 d-block mb-3"></i>
                                        <p>Map loading...</p>
                                        <small>Please ensure you have added the Google Maps API key</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php get_footer();
