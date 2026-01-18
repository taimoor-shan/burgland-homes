<?php
/**
 * Lots Grid Component with Filters
 * 
 * Displays a filterable grid of available lots for a specific community
 * 
 * @param array $args {
 *     @type int $community_id - The community ID to filter lots
 * }
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) exit;

$community_id = isset($args['community_id']) ? intval($args['community_id']) : 0;

if (!$community_id) {
    return;
}

$data_provider = Burgland_Homes_Data_Provider::get_instance();
$template_loader = Burgland_Homes_Template_Loader::get_instance();

// Get all lots for this community
$lots_query = new WP_Query(array(
    'post_type' => 'bh_lot',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'lot_community',
            'value' => $community_id,
            'compare' => '='
        )
    ),
    'orderby' => 'meta_value_num',
    'meta_key' => 'lot_price',
    'order' => 'ASC'
));

// Get unique values for filters
$bedrooms_options = array();
$bathrooms_options = array();
$sqft_min = PHP_INT_MAX;
$sqft_max = 0;

if ($lots_query->have_posts()) {
    while ($lots_query->have_posts()) {
        $lots_query->the_post();
        $lot_id = get_the_ID();
        
        $bedrooms = get_post_meta($lot_id, 'lot_bedrooms', true);
        $bathrooms = get_post_meta($lot_id, 'lot_bathrooms', true);
        $sqft = get_post_meta($lot_id, 'lot_square_feet', true);
        
        if ($bedrooms && !in_array($bedrooms, $bedrooms_options)) {
            $bedrooms_options[] = $bedrooms;
        }
        
        if ($bathrooms && !in_array($bathrooms, $bathrooms_options)) {
            $bathrooms_options[] = $bathrooms;
        }
        
        if ($sqft && is_numeric($sqft)) {
            $sqft_min = min($sqft_min, intval($sqft));
            $sqft_max = max($sqft_max, intval($sqft));
        }
    }
    wp_reset_postdata();
}

sort($bedrooms_options);
sort($bathrooms_options);

if ($sqft_min === PHP_INT_MAX) {
    $sqft_min = 0;
}
?>

<section class="bh-lots-grid-section py-5">
    <div class="container-fluid">
        <!-- Section Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="display-6 fw-light mb-2">Available Homes</h2>
                <p class="lead text-muted mb-0">Explore available homes in this community</p>
            </div>
        </div>

        <!-- Filters Section -->
        <section class="bh-filters bg-white border rounded-3 p-4 mb-4">
            <form id="lots-filters" class="row g-3 align-items-end" data-community-id="<?php echo esc_attr($community_id); ?>">
                
                <!-- Square Footage Range -->
                <div class="col-md-3">
                    <label for="sqft-filter" class="form-label fw-semibold">Sqft Range</label>
                    <select name="sqft_range" id="sqft-filter" class="form-select">
                        <option value="">All Square Footage</option>
                        <?php if ($sqft_min > 0 && $sqft_max > 0): ?>
                            <option value="0-1500">Under 1,500 sqft</option>
                            <option value="1500-2000">1,500 - 2,000 sqft</option>
                            <option value="2000-2500">2,000 - 2,500 sqft</option>
                            <option value="2500-3000">2,500 - 3,000 sqft</option>
                            <option value="3000+">Over 3,000 sqft</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Bedrooms Filter -->
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

                <!-- Bathrooms Filter -->
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

            </form>
        </section>

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
                // Reset and loop through lots again for display
                $lots_query->rewind_posts();
                
                if ($lots_query->have_posts()):
                    while ($lots_query->have_posts()): $lots_query->the_post();
                        $lot_id = get_the_ID();
                        
                        // Get lot data for filtering attributes
                        $lot_data = $data_provider->get_lot_data($lot_id);
                        
                        // Extract numeric price for sorting
                        $price_numeric = 0;
                        if (!empty($lot_data['price'])) {
                            $price_numeric = intval(preg_replace('/[^0-9]/', '', $lot_data['price']));
                        }
                        
                        // Extract numeric sqft for sorting
                        $sqft_numeric = !empty($lot_data['square_feet']) ? intval($lot_data['square_feet']) : 0;
                        ?>
                        <div class="col-md-6 col-lg-4 lot-card-wrapper" 
                             data-bedrooms="<?php echo esc_attr($lot_data['bedrooms']); ?>"
                             data-bathrooms="<?php echo esc_attr($lot_data['bathrooms']); ?>"
                             data-sqft="<?php echo esc_attr($sqft_numeric); ?>"
                             data-price="<?php echo esc_attr($price_numeric); ?>"
                             data-lot-id="<?php echo esc_attr($lot_id); ?>">
                            <?php 
                            // Render lot card using the template loader's render_card method
                            $template_loader->render_card($lot_id);
                            ?>
                        </div>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                    ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center" role="alert">
                            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                            <p class="mb-0">No homes available in this community at the moment. Please check back later.</p>
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

        <!-- Results Count -->
        <div class="row mt-3">
            <div class="col-12">
                <p class="text-muted text-center">
                    Showing <span id="lots-count"><?php echo $lots_query->found_posts; ?></span> home(s)
                </p>
            </div>
        </div>
    </div>
</section>
