<?php

/**
 * Archive Community Template
 * 
 * Template for displaying all communities with filters and map
 * 
 * @package Burgland_Homes
 */

get_header();
b5st_mainbody_before();

// Get all community status categories (hierarchical)
$status_categories = get_terms(array(
    'taxonomy' => 'bh_community_status',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
));

// Get filter values from URL
$selected_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$selected_price = isset($_GET['price_range']) ? sanitize_text_field($_GET['price_range']) : '';
$selected_bedrooms = isset($_GET['bedrooms']) ? sanitize_text_field($_GET['bedrooms']) : '';
$selected_bathrooms = isset($_GET['bathrooms']) ? sanitize_text_field($_GET['bathrooms']) : '';

// Get unique bedroom and bathroom options from floor plans
$bedrooms_options = array();
$bathrooms_options = array();

$floor_plans_query = new WP_Query(array(
    'post_type' => 'bh_floor_plan',
    'posts_per_page' => -1,
    'post_status' => 'publish',
));

if ($floor_plans_query->have_posts()) {
    while ($floor_plans_query->have_posts()) {
        $floor_plans_query->the_post();
        $bedrooms = get_post_meta(get_the_ID(), 'floor_plan_bedrooms', true);
        $bathrooms = get_post_meta(get_the_ID(), 'floor_plan_bathrooms', true);

        if (!empty($bedrooms) && !in_array($bedrooms, $bedrooms_options)) {
            $bedrooms_options[] = $bedrooms;
        }
        if (!empty($bathrooms) && !in_array($bathrooms, $bathrooms_options)) {
            $bathrooms_options[] = $bathrooms;
        }
    }
    wp_reset_postdata();
}

sort($bedrooms_options, SORT_NUMERIC);
sort($bathrooms_options, SORT_NUMERIC);

// Get archive settings from options
$archive_title = get_option('bh_archive_communities_title', 'Florida');
$archive_subtitle = get_option('bh_archive_communities_subtitle', 'New Home Communities');
$archive_image_id = get_option('bh_archive_communities_image', '');
$background_url = $archive_image_id ? wp_get_attachment_url($archive_image_id) : '';
?>

<main id="site-main">
    <header class="page-header container-fluid d-flex justify-content-start align-items-end"
        style="background: <?php echo $background_url ? 'url(' . esc_url($background_url) . ') center center no-repeat' : '#f8f9fa'; ?>; background-size: cover;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 text-start">
                    <h1 class="display-5">
                        <?php echo esc_html($archive_title); ?>
                    </h1>

                    <?php if ($archive_subtitle): ?>
                        <p class="page-excerpt text-uppercase text-white fw-300 lead">
                            <?php echo esc_html($archive_subtitle); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    <div class="communities-archive">
        <!-- Filters Section -->
        <section class="bh-filters filters-section border-bottom py-4 bg-light">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <!-- Mobile Filter Toggle Button -->
                        <div class="d-md-none mb-3">
                            <button type="button" id="filter-toggle"
                                class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-between">
                                <span>Filter By</span>
                                <i class="bi bi-chevron-down filter-toggle-icon"></i>
                            </button>
                        </div>

                        <!-- Filter Form Container -->
                        <div id="filter-form-container" class="filter-form-container">
                            <form id="community-filters" class="row g-3 align-items-end">
                                <!-- Status Filter (Category Style) -->
                                <div class="col-md-3">
                                    <label for="status-filter" class="form-label text-info">Community Status</label>
                                    <select name="status" id="status-filter" class="form-select">
                                        <option value="">All Communities</option>
                                        <?php if (!empty($status_categories) && !is_wp_error($status_categories)): ?>
                                            <?php foreach ($status_categories as $category): ?>
                                                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_status, $category->slug); ?>>
                                                    <?php echo esc_html($category->name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Price Range Filter -->
                                <div class="col-md-3">
                                    <label for="price-filter" class="form-label text-info">Price Range</label>
                                    <select name="price_range" id="price-filter" class="form-select">
                                        <option value="">All Price Ranges</option>
                                        <option value="under-300k" <?php selected($selected_price, 'under-300k'); ?>>
                                            Under $300,000</option>
                                        <option value="300k-500k" <?php selected($selected_price, '300k-500k'); ?>>
                                            $300,000 - $500,000</option>
                                        <option value="over-500k" <?php selected($selected_price, 'over-500k'); ?>>Over
                                            $500,000</option>
                                    </select>
                                </div>

                                <!-- Bedrooms Filter -->
                                <?php if (!empty($bedrooms_options)): ?>
                                    <div class="col-md-3">
                                        <label for="bedrooms-filter" class="form-label text-info">Bedrooms</label>
                                        <select name="bedrooms" id="bedrooms-filter" class="form-select">
                                            <option value="">All Bedrooms</option>
                                            <?php foreach ($bedrooms_options as $bedrooms): ?>
                                                <option value="<?php echo esc_attr($bedrooms); ?>" <?php selected($selected_bedrooms, $bedrooms); ?>>
                                                    <?php echo esc_html($bedrooms); ?>
                                                    Bed<?php echo $bedrooms > 1 ? 's' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <!-- Bathrooms Filter -->
                                <?php if (!empty($bathrooms_options)): ?>
                                    <div class="col-md-3">
                                        <label for="bathrooms-filter" class="form-label text-info">Bathrooms</label>
                                        <select name="bathrooms" id="bathrooms-filter" class="form-select">
                                            <option value="">All Bathrooms</option>
                                            <?php foreach ($bathrooms_options as $bathrooms): ?>
                                                <option value="<?php echo esc_attr($bathrooms); ?>" <?php selected($selected_bathrooms, $bathrooms); ?>>
                                                    <?php echo esc_html($bathrooms); ?>
                                                    Bath<?php echo $bathrooms > 1 ? 's' : ''; ?>
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

        <section class="communities-content bh-split-scroll-wrapper">
            <div class="bh-split-section">
                <div class="container-fluid bg-warning">
                    <div class="row">


                        <!-- Left Column:Community Cards -->

                        <div class="col-lg-6 bh-listings-column col-12 col-lg-7 pt-4 ps-lg-4">
                            <div id="communities-grid" class="communities-grid bh-listings-grid">
                                <div class="loading-spinner text-center py-5" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-3">Loading communities...</p>
                                </div>

                                <div id="communities-list" class="row g-4 align-items-stretch mb-5">
                                    <?php
                                    // Build optimized query
                                    $query_args = [
                                        'post_type' => 'bh_community',
                                        'posts_per_page' => -1,
                                        'post_status' => 'publish',
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ];

                                    // Build tax_query array
                                    $tax_query = [];
                                    if ($selected_status) {
                                        $tax_query[] = [
                                            'taxonomy' => 'bh_community_status',
                                            'field' => 'slug',
                                            'terms' => $selected_status,
                                        ];
                                    }
                                    if (!empty($tax_query)) {
                                        $query_args['tax_query'] = $tax_query;
                                    }

                                    // Build meta_query for price filtering
                                    $meta_query = [];
                                    if ($selected_price) {
                                        $price_ranges = [
                                            'under-300k' => ['max' => 300000],
                                            '300k-500k' => ['min' => 300000, 'max' => 500000],
                                            'over-500k' => ['min' => 500000],
                                        ];

                                        if (isset($price_ranges[$selected_price])) {
                                            $meta_query[] = [
                                                'key' => 'community_price_range',
                                                'value' => '',
                                                'compare' => '!=',
                                            ];

                                            // Note: Price range filtering still happens post-query
                                            // because the field stores formatted strings, not numbers
                                            // Consider storing numeric price in separate meta field
                                        }
                                    }
                                    if (!empty($meta_query)) {
                                        $query_args['meta_query'] = $meta_query;
                                    }

                                    $communities = new WP_Query($query_args);

                                    if ($communities->have_posts()):
                                        while ($communities->have_posts()):
                                            $communities->the_post();
                                            $post_id = get_the_ID();

                                            // Get meta data
                                            $latitude = get_post_meta($post_id, '_geocoded_latitude', true);
                                            $longitude = get_post_meta($post_id, '_geocoded_longitude', true);
                                            $price_range = get_post_meta($post_id, 'community_price_range', true);

                                            // Apply price filter if needed
                                            if ($selected_price && $price_range && !apply_price_filter($price_range, $selected_price)) {
                                                continue;
                                            }
                                            ?>
                                            <div class="col-md-6 community-card-wrapper"
                                                data-lat="<?php echo esc_attr($latitude); ?>"
                                                data-lng="<?php echo esc_attr($longitude); ?>"
                                                data-id="<?php echo esc_attr($post_id); ?>">
                                                <?php Burgland_Homes_Template_Loader::get_instance()->render_card($post_id); ?>
                                            </div>
                                            <?php
                                        endwhile;
                                        wp_reset_postdata();
                                    else:
                                        ?>
                                        <div class="col-12">
                                            <div class="alert alert-info text-center" role="alert">
                                                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                                                <p class="mb-0">No communities found matching your criteria. Please adjust
                                                    your filters.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- Right Column: Map Sec-->

                        <div class="col-12 col-lg-5  pe-lg-0 bh-map-column">
                            <div id="communities-map">
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

<?php get_footer(); ?>