<?php
/**
 * Admin Interface
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Admin
 */
class Burgland_Homes_Admin {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('manage_bh_community_posts_columns', array($this, 'community_columns'));
        add_filter('manage_bh_floor_plan_posts_columns', array($this, 'floor_plan_columns'));
        add_filter('manage_bh_lot_posts_columns', array($this, 'lot_columns'));
        add_action('manage_bh_community_posts_custom_column', array($this, 'community_column_content'), 10, 2);
        add_action('manage_bh_floor_plan_posts_custom_column', array($this, 'floor_plan_column_content'), 10, 2);
        add_action('manage_bh_lot_posts_custom_column', array($this, 'lot_column_content'), 10, 2);
        
        // Make community columns sortable
        add_filter('manage_edit-bh_community_sortable_columns', array($this, 'community_sortable_columns'));
        
        // Add filters to lot list for community
        add_action('restrict_manage_posts', array($this, 'add_lot_community_filter'));
        add_filter('parse_query', array($this, 'filter_lots_by_community'));
        
        // Add row actions for communities
        add_filter('post_row_actions', array($this, 'community_row_actions'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Burgland Homes', 'burgland-homes'),
            __('Burgland Homes', 'burgland-homes'),
            'edit_posts',
            'burgland-homes',
            array($this, 'render_dashboard'),
            'dashicons-building',
            20
        );
        
        add_submenu_page(
            'burgland-homes',
            __('Dashboard', 'burgland-homes'),
            __('Dashboard', 'burgland-homes'),
            'edit_posts',
            'burgland-homes',
            array($this, 'render_dashboard')
        );
        
        // Add Community Management page (hidden from menu, accessed via row action)
        add_submenu_page(
            null, // Hidden from menu
            __('Manage Community', 'burgland-homes'),
            __('Manage Community', 'burgland-homes'),
            'edit_posts',
            'burgland-homes-manage-community',
            array($this, 'render_community_management')
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        // Get statistics
        $communities_posts = wp_count_posts('bh_community');
        $communities_count = isset($communities_posts->publish) ? $communities_posts->publish : 0;
        
        $floor_plans_posts = wp_count_posts('bh_floor_plan');
        $floor_plans_count = isset($floor_plans_posts->publish) ? $floor_plans_posts->publish : 0;
        
        $lots_posts = wp_count_posts('bh_lot');
        $lots_count = isset($lots_posts->publish) ? $lots_posts->publish : 0;
        
        // Get available lots count
        $available_lots = new WP_Query(array(
            'post_type' => 'bh_lot',
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bh_lot_status',
                    'field' => 'slug',
                    'terms' => 'available',
                ),
            ),
            'fields' => 'ids',
        ));
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Burgland Homes Dashboard', 'burgland-homes'); ?></h1>
            
            <div class="burgland-homes-dashboard" style="margin-top: 30px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    
                    <div class="dashboard-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 class="dashboard-card-title" style="margin-top: 0; color: #1e40af;">
                            <span class="dashicons dashicons-admin-multisite" style="font-size: 30px;"></span>
                            <span style=""><?php echo esc_html($communities_count); ?></span>
                        </h2>
                        <p style="margin: 0; font-size: 16px;">Communities</p>
                        <a href="<?php echo admin_url('edit.php?post_type=bh_community'); ?>" class="button" style="margin-top: 10px;">
                            View All
                        </a>
                    </div>
                    
                    <div class="dashboard-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 class="dashboard-card-title" style="margin-top: 0; color: #059669;">
                            <span class="dashicons dashicons-layout" style="font-size: 30px;"></span>
                            <span><?php echo esc_html($floor_plans_count); ?></span>
                        </h2>
                        <p style="margin: 0; font-size: 16px;">Floor Plans</p>
                        <a href="<?php echo admin_url('edit.php?post_type=bh_floor_plan'); ?>" class="button" style="margin-top: 10px;">
                            View All
                        </a>
                    </div>
                    
                    <div class="dashboard-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 class="dashboard-card-title" style="margin-top: 0; color: #dc2626;">
                            <span class="dashicons dashicons-location" style="font-size: 30px;"></span>
                            <span><?php echo esc_html($lots_count); ?></span>
                        </h2>
                        <p style="margin: 0; font-size: 16px;">Total Lots</p>
                        <a href="<?php echo admin_url('edit.php?post_type=bh_lot'); ?>" class="button" style="margin-top: 10px;">
                            View All
                        </a>
                    </div>
                    
                    <div class="dashboard-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 class="dashboard-card-title" style="margin-top: 0; color: #16a34a;">
                            <span class="dashicons dashicons-yes-alt" style="font-size: 30px;"></span>
                            <span><?php echo esc_html($available_lots->found_posts); ?></span>
                        </h2>
                        <p style="margin: 0; font-size: 16px;">Available Lots</p>
                        <a href="<?php echo admin_url('edit.php?post_type=bh_lot&bh_lot_status=available'); ?>" class="button" style="margin-top: 10px;">
                            View Available
                        </a>
                    </div>
                    
                </div>
                
                <div style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2>Quick Actions</h2>
                    <p>
                        <a href="<?php echo admin_url('post-new.php?post_type=bh_community'); ?>" class="button button-primary">
                            Add New Community
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=bh_floor_plan'); ?>" class="button">
                            Add New Floor Plan
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=bh_lot'); ?>" class="button">
                            Add New Lot/Home
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        // Check if we're on our plugin's admin pages
        $is_plugin_page = false;
        
        // Check for custom post type pages
        if ($screen && in_array($screen->post_type, array('bh_community', 'bh_floor_plan', 'bh_lot'))) {
            $is_plugin_page = true;
        }
        
        // Check for main plugin dashboard and community management pages
        if ($screen && ($screen->id === 'toplevel_page_burgland-homes' || 
                       $screen->id === 'burgland-homes_page_burgland-homes-manage-community')) {
            $is_plugin_page = true;
        }
        
        // Also check for other potential plugin pages
        if ($screen && strpos($screen->id, 'burgland-homes') !== false) {
            $is_plugin_page = true;
        }
        
        if (!$is_plugin_page) {
            return;
        }
        
        wp_enqueue_style(
            'burgland-homes-admin',
            BURGLAND_HOMES_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BURGLAND_HOMES_VERSION
        );
    }
    
    /**
     * Add custom columns for Community
     */
    public function community_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['community_status'] = __('Status', 'burgland-homes');
        $new_columns['total_lots'] = __('Total Lots', 'burgland-homes');
        $new_columns['available_lots'] = __('Available', 'burgland-homes');
        $new_columns['sold_lots'] = __('Sold', 'burgland-homes');
        $new_columns['reserved_lots'] = __('Reserved', 'burgland-homes');
        $new_columns['floor_plans'] = __('Floor Plans', 'burgland-homes');
        $new_columns['location'] = __('Location', 'burgland-homes');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Add custom columns for Floor Plan
     */
    public function floor_plan_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['community'] = __('Community', 'burgland-homes');
        $new_columns['bedrooms'] = __('Bedrooms', 'burgland-homes');
        $new_columns['bathrooms'] = __('Bathrooms', 'burgland-homes');
        $new_columns['price'] = __('Price', 'burgland-homes');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Add custom columns for Lot
     */
    public function lot_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['community'] = __('Community', 'burgland-homes');
        $new_columns['lot_state'] = __('State', 'burgland-homes');
        $new_columns['floor_plan'] = __('Floor Plan', 'burgland-homes');
        $new_columns['price'] = __('Price', 'burgland-homes');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Display custom column content for Community
     */
    public function community_column_content($column, $post_id) {
        switch ($column) {
            case 'community_status':
                $terms = get_the_terms($post_id, 'bh_community_status');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html($terms[0]->name);
                }
                break;
            case 'total_lots':
                // Count actual lots in database
                $total_lots = $this->get_community_lots_count($post_id);
                if ($total_lots > 0) {
                    $url = admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $post_id . '&tab=lots');
                    echo '<a href="' . esc_url($url) . '" style="font-weight: 600;">' . esc_html($total_lots) . '</a>';
                } else {
                    echo '—';
                }
                break;
            case 'available_lots':
                $count = $this->get_community_lots_count($post_id, 'available');
                echo '<span style="color: #16a34a; font-weight: 600;">' . esc_html($count) . '</span>';
                break;
            case 'sold_lots':
                $count = $this->get_community_lots_count($post_id, 'sold');
                echo '<span style="color: #dc2626; font-weight: 600;">' . esc_html($count) . '</span>';
                break;
            case 'reserved_lots':
                $count = $this->get_community_lots_count($post_id, 'reserved');
                echo '<span style="color: #ea580c; font-weight: 600;">' . esc_html($count) . '</span>';
                break;
            case 'floor_plans':
                $count = $this->get_community_floor_plans_count($post_id);
                if ($count > 0) {
                    $url = admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $post_id . '&tab=floor-plans');
                    echo '<a href="' . esc_url($url) . '">' . esc_html($count) . '</a>';
                } else {
                    echo '—';
                }
                break;
            case 'location':
                $city = get_post_meta($post_id, 'community_city', true);
                $state = get_post_meta($post_id, 'community_state', true);
                if ($city && $state) {
                    echo esc_html($city . ', ' . $state);
                } elseif ($city) {
                    echo esc_html($city);
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Display custom column content for Floor Plan
     */
    public function floor_plan_column_content($column, $post_id) {
        switch ($column) {
            case 'community':
                // Get community via taxonomy instead of meta field
                $terms = wp_get_post_terms($post_id, 'bh_floor_plan_community');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $community_names = array();
                    foreach ($terms as $term) {
                        // Find the community post by term slug
                        $community_posts = get_posts(array(
                            'post_type' => 'bh_community',
                            'name' => $term->slug,
                            'posts_per_page' => 1,
                        ));
                        
                        if (!empty($community_posts)) {
                            $community = $community_posts[0];
                            $community_names[] = '<a href="' . get_edit_post_link($community->ID) . '">' . esc_html($community->post_title) . '</a>';
                        } else {
                            $community_names[] = esc_html($term->name);
                        }
                    }
                    echo implode(', ', $community_names);
                } else {
                    echo '—';
                }
                break;
            case 'bedrooms':
                $bedrooms = get_post_meta($post_id, 'floor_plan_bedrooms', true);
                echo $bedrooms ? esc_html($bedrooms) : '—';
                break;
            case 'bathrooms':
                $bathrooms = get_post_meta($post_id, 'floor_plan_bathrooms', true);
                echo $bathrooms ? esc_html($bathrooms) : '—';
                break;
            case 'price':
                $price = get_post_meta($post_id, 'floor_plan_price', true);
                echo $price ? esc_html($price) : '—';
                break;
        }
    }
    
    /**
     * Display custom column content for Lot
     */
    public function lot_column_content($column, $post_id) {
        switch ($column) {
            case 'community':
                $community_id = get_post_meta($post_id, 'lot_community', true);
                if ($community_id) {
                    $community = get_post($community_id);
                    if ($community) {
                        echo '<a href="' . get_edit_post_link($community_id) . '">' . esc_html($community->post_title) . '</a>';
                    }
                } else {
                    echo '—';
                }
                break;
            case 'lot_status':
                $terms = get_the_terms($post_id, 'bh_lot_status');
                if ($terms && !is_wp_error($terms)) {
                    echo esc_html($terms[0]->name);
                }
                break;
            case 'floor_plan':
                $floor_plan_id = get_post_meta($post_id, 'lot_floor_plan', true);
                if ($floor_plan_id) {
                    $floor_plan = get_post($floor_plan_id);
                    if ($floor_plan) {
                        echo '<a href="' . get_edit_post_link($floor_plan_id) . '">' . esc_html($floor_plan->post_title) . '</a>';
                    }
                } else {
                    echo '—';
                }
                break;
            case 'price':
                $price = get_post_meta($post_id, 'lot_price', true);
                echo $price ? esc_html($price) : '—';
                break;
        }
    }
    
    /**
     * Get community lots count by status
     */
    private function get_community_lots_count($community_id, $status = null) {
        $args = array(
            'post_type' => 'bh_lot',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'lot_community',
                    'value' => $community_id,
                ),
            ),
        );
        
        if ($status) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'bh_lot_status',
                    'field' => 'slug',
                    'terms' => $status,
                ),
            );
        }
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Get community floor plans count
     */
    private function get_community_floor_plans_count($community_id) {
        $community_post = get_post($community_id);
        if (!$community_post) {
            return 0;
        }
        
        // Get the taxonomy term
        $term_slug = sanitize_title($community_post->post_name);
        $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if (!$term) {
            $term = get_term_by('name', $community_post->post_title, 'bh_floor_plan_community');
        }
        
        if (!$term) {
            return 0;
        }
        
        $args = array(
            'post_type' => 'bh_floor_plan',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bh_floor_plan_community',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ),
            ),
        );
        
        $query = new WP_Query($args);
        return $query->found_posts;
    }
    
    /**
     * Make community columns sortable
     */
    public function community_sortable_columns($columns) {
        $columns['total_lots'] = 'total_lots';
        $columns['available_lots'] = 'available_lots';
        $columns['location'] = 'location';
        return $columns;
    }
    
    /**
     * Add row actions for communities
     */
    public function community_row_actions($actions, $post) {
        if ($post->post_type === 'bh_community') {
            $manage_url = admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $post->ID);
            $actions['manage_community'] = '<a href="' . esc_url($manage_url) . '">' . __('Manage Community', 'burgland-homes') . '</a>';
        }
        return $actions;
    }
    
    /**
     * Add community filter to lots list
     */
    public function add_lot_community_filter($post_type) {
        if ($post_type !== 'bh_lot') {
            return;
        }
        
        $communities = get_posts(array(
            'post_type' => 'bh_community',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (empty($communities)) {
            return;
        }
        
        $selected = isset($_GET['bh_community_filter']) ? $_GET['bh_community_filter'] : '';
        
        echo '<select name="bh_community_filter" id="bh_community_filter">';
        echo '<option value="">' . __('All Communities', 'burgland-homes') . '</option>';
        foreach ($communities as $community) {
            echo '<option value="' . esc_attr($community->ID) . '" ' . selected($selected, $community->ID, false) . '>';
            echo esc_html($community->post_title);
            echo '</option>';
        }
        echo '</select>';
    }
    
    /**
     * Filter lots by community
     */
    public function filter_lots_by_community($query) {
        global $pagenow;
        
        if (!is_admin() || $pagenow !== 'edit.php' || !isset($_GET['post_type']) || $_GET['post_type'] !== 'bh_lot') {
            return $query;
        }
        
        if (isset($_GET['bh_community_filter']) && $_GET['bh_community_filter'] !== '') {
            $query->set('meta_query', array(
                array(
                    'key' => 'lot_community',
                    'value' => $_GET['bh_community_filter'],
                ),
            ));
        }
        
        return $query;
    }
    
    /**
     * Render Community Management page
     */
    public function render_community_management() {
        if (!isset($_GET['community_id'])) {
            echo '<div class="wrap"><h1>' . __('Invalid Community', 'burgland-homes') . '</h1></div>';
            return;
        }
        
        $community_id = intval($_GET['community_id']);
        $community = get_post($community_id);
        
        if (!$community || $community->post_type !== 'bh_community') {
            echo '<div class="wrap"><h1>' . __('Community Not Found', 'burgland-homes') . '</h1></div>';
            return;
        }
        
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
        
        // Get statistics
        $total_lots = $this->get_community_lots_count($community_id);
        $available_lots = $this->get_community_lots_count($community_id, 'available');
        $sold_lots = $this->get_community_lots_count($community_id, 'sold');
        $reserved_lots = $this->get_community_lots_count($community_id, 'reserved');
        $total_floor_plans = $this->get_community_floor_plans_count($community_id);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php echo esc_html($community->post_title); ?>
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    <?php _e('Community Management', 'burgland-homes'); ?>
                </span>
            </h1>
            <a href="<?php echo get_edit_post_link($community_id); ?>" class="page-title-action">
                <?php _e('Edit Community Details', 'burgland-homes'); ?>
            </a>
            <hr class="wp-header-end">
            
            <!-- Stats Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 28px; color: #1e40af;"><?php echo esc_html($total_lots); ?></h3>
                    <p style="margin: 0; color: #666;"><?php _e('Total Lots', 'burgland-homes'); ?></p>
                </div>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 28px; color: #16a34a;"><?php echo esc_html($available_lots); ?></h3>
                    <p style="margin: 0; color: #666;"><?php _e('Available', 'burgland-homes'); ?></p>
                </div>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 28px; color: #dc2626;"><?php echo esc_html($sold_lots); ?></h3>
                    <p style="margin: 0; color: #666;"><?php _e('Sold', 'burgland-homes'); ?></p>
                </div>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 28px; color: #ea580c;"><?php echo esc_html($reserved_lots); ?></h3>
                    <p style="margin: 0; color: #666;"><?php _e('Reserved', 'burgland-homes'); ?></p>
                </div>
                <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3 style="margin: 0 0 5px 0; font-size: 28px; color: #059669;"><?php echo esc_html($total_floor_plans); ?></h3>
                    <p style="margin: 0; color: #666;"><?php _e('Floor Plans', 'burgland-homes'); ?></p>
                </div>
            </div>
            
            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $community_id . '&tab=overview')); ?>" 
                   class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Overview', 'burgland-homes'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $community_id . '&tab=lots')); ?>" 
                   class="nav-tab <?php echo $active_tab === 'lots' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Lots', 'burgland-homes'); ?> (<?php echo esc_html($total_lots); ?>)
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $community_id . '&tab=floor-plans')); ?>" 
                   class="nav-tab <?php echo $active_tab === 'floor-plans' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Floor Plans', 'burgland-homes'); ?> (<?php echo esc_html($total_floor_plans); ?>)
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=burgland-homes-manage-community&community_id=' . $community_id . '&tab=map')); ?>" 
                   class="nav-tab <?php echo $active_tab === 'map' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Map', 'burgland-homes'); ?>
                </a>
            </h2>
            
            <div class="tab-content" style="margin-top: 20px;">
                <?php
                switch ($active_tab) {
                    case 'lots':
                        $this->render_community_lots_tab($community_id);
                        break;
                    case 'floor-plans':
                        $this->render_community_floor_plans_tab($community_id);
                        break;
                    case 'map':
                        $this->render_community_map_tab($community_id);
                        break;
                    case 'overview':
                    default:
                        $this->render_community_overview_tab($community_id);
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Overview Tab
     */
    private function render_community_overview_tab($community_id) {
        $community = get_post($community_id);
        $address = get_post_meta($community_id, 'community_address', true);
        $city = get_post_meta($community_id, 'community_city', true);
        $state = get_post_meta($community_id, 'community_state', true);
        $zip = get_post_meta($community_id, 'community_zip', true);
        $price_range = get_post_meta($community_id, 'community_price_range', true);
        $amenities = get_post_meta($community_id, 'community_amenities', true);
        
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php _e('Community Information', 'burgland-homes'); ?></h2>
            
            <?php if ($community->post_content): ?>
            <div style="margin-bottom: 20px;">
                <h3><?php _e('Description', 'burgland-homes'); ?></h3>
                <?php echo wpautop($community->post_content); ?>
            </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h3><?php _e('Location', 'burgland-homes'); ?></h3>
                    <?php if ($address): ?>
                        <p><strong><?php _e('Address:', 'burgland-homes'); ?></strong> <?php echo esc_html($address); ?></p>
                    <?php endif; ?>
                    <?php if ($city || $state || $zip): ?>
                        <p><strong><?php _e('City/State/ZIP:', 'burgland-homes'); ?></strong> 
                        <?php echo esc_html(trim($city . ', ' . $state . ' ' . $zip, ', ')); ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3><?php _e('Pricing', 'burgland-homes'); ?></h3>
                    <?php if ($price_range): ?>
                        <p><strong><?php _e('Price Range:', 'burgland-homes'); ?></strong> <?php echo esc_html($price_range); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($amenities): ?>
            <div style="margin-top: 20px;">
                <h3><?php _e('Amenities', 'burgland-homes'); ?></h3>
                <?php echo wpautop(esc_html($amenities)); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Lots Tab
     */
    private function render_community_lots_tab($community_id) {
        $add_lot_url = admin_url('post-new.php?post_type=bh_lot&community_id=' . $community_id);
        
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;"><?php _e('Lots in this Community', 'burgland-homes'); ?></h2>
                <a href="<?php echo esc_url($add_lot_url); ?>" class="button button-primary">
                    <?php _e('Add New Lot', 'burgland-homes'); ?>
                </a>
            </div>
            
            <?php
            $lots_query = new WP_Query(array(
                'post_type' => 'bh_lot',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'lot_community',
                        'value' => $community_id,
                    ),
                ),
            ));
            
            if ($lots_query->have_posts()): 
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Lot', 'burgland-homes'); ?></th>
                            <th><?php _e('Status', 'burgland-homes'); ?></th>
                            <th><?php _e('Floor Plan', 'burgland-homes'); ?></th>
                            <th><?php _e('Price', 'burgland-homes'); ?></th>
                            <th><?php _e('Size', 'burgland-homes'); ?></th>
                            <th><?php _e('Actions', 'burgland-homes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($lots_query->have_posts()): $lots_query->the_post(); 
                            $lot_id = get_the_ID();
                            $status_terms = wp_get_post_terms($lot_id, 'bh_lot_status');
                            $status = $status_terms && !is_wp_error($status_terms) ? $status_terms[0]->name : '—';
                            $floor_plan_id = get_post_meta($lot_id, 'lot_floor_plan', true);
                            $floor_plan_name = $floor_plan_id ? get_the_title($floor_plan_id) : '—';
                            $price = get_post_meta($lot_id, 'lot_price', true);
                            $size = get_post_meta($lot_id, 'lot_size', true);
                        ?>
                        <tr>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo esc_html($status); ?></td>
                            <td><?php echo esc_html($floor_plan_name); ?></td>
                            <td><?php echo $price ? esc_html($price) : '—'; ?></td>
                            <td><?php echo $size ? esc_html($size) : '—'; ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($lot_id); ?>" class="button button-small">
                                    <?php _e('Edit', 'burgland-homes'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php 
                wp_reset_postdata();
            else: 
            ?>
                <p style="text-align: center; padding: 40px 0; color: #666;">
                    <?php _e('No lots added yet.', 'burgland-homes'); ?>
                    <br><br>
                    <a href="<?php echo esc_url($add_lot_url); ?>" class="button button-primary">
                        <?php _e('Add Your First Lot', 'burgland-homes'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Floor Plans Tab
     */
    private function render_community_floor_plans_tab($community_id) {
        $add_floor_plan_url = admin_url('post-new.php?post_type=bh_floor_plan&community_id=' . $community_id);
        $community_post = get_post($community_id);
        
        // Get the taxonomy term
        $term_slug = sanitize_title($community_post->post_name);
        $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if (!$term) {
            $term = get_term_by('name', $community_post->post_title, 'bh_floor_plan_community');
        }
        
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;"><?php _e('Floor Plans in this Community', 'burgland-homes'); ?></h2>
                <a href="<?php echo esc_url($add_floor_plan_url); ?>" class="button button-primary">
                    <?php _e('Add New Floor Plan', 'burgland-homes'); ?>
                </a>
            </div>
            
            <?php
            $floor_plan_query_args = array(
                'post_type' => 'bh_floor_plan',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            );
            
            if ($term) {
                $floor_plan_query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'bh_floor_plan_community',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ),
                );
            }
            
            $floor_plans_query = new WP_Query($floor_plan_query_args);
            
            if ($floor_plans_query->have_posts()): 
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Floor Plan', 'burgland-homes'); ?></th>
                            <th><?php _e('Bedrooms', 'burgland-homes'); ?></th>
                            <th><?php _e('Bathrooms', 'burgland-homes'); ?></th>
                            <th><?php _e('Square Feet', 'burgland-homes'); ?></th>
                            <th><?php _e('Price', 'burgland-homes'); ?></th>
                            <th><?php _e('Actions', 'burgland-homes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($floor_plans_query->have_posts()): $floor_plans_query->the_post(); 
                            $fp_id = get_the_ID();
                            $bedrooms = get_post_meta($fp_id, 'floor_plan_bedrooms', true);
                            $bathrooms = get_post_meta($fp_id, 'floor_plan_bathrooms', true);
                            $sqft = get_post_meta($fp_id, 'floor_plan_square_feet', true);
                            $price = get_post_meta($fp_id, 'floor_plan_price', true);
                        ?>
                        <tr>
                            <td><strong><?php the_title(); ?></strong></td>
                            <td><?php echo $bedrooms ? esc_html($bedrooms) : '—'; ?></td>
                            <td><?php echo $bathrooms ? esc_html($bathrooms) : '—'; ?></td>
                            <td><?php echo $sqft ? esc_html(number_format($sqft)) . ' sq ft' : '—'; ?></td>
                            <td><?php echo $price ? esc_html($price) : '—'; ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($fp_id); ?>" class="button button-small">
                                    <?php _e('Edit', 'burgland-homes'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php 
                wp_reset_postdata();
            else: 
            ?>
                <p style="text-align: center; padding: 40px 0; color: #666;">
                    <?php _e('No floor plans added yet.', 'burgland-homes'); ?>
                    <br><br>
                    <a href="<?php echo esc_url($add_floor_plan_url); ?>" class="button button-primary">
                        <?php _e('Add Your First Floor Plan', 'burgland-homes'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render Map Tab
     */
    private function render_community_map_tab($community_id) {
        $latitude = get_post_meta($community_id, 'community_latitude', true);
        $longitude = get_post_meta($community_id, 'community_longitude', true);
        
        ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2><?php _e('Community Map', 'burgland-homes'); ?></h2>
            
            <?php if ($latitude && $longitude): ?>
                <div id="community-admin-map" 
                     style="height: 500px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center;"
                     data-lat="<?php echo esc_attr($latitude); ?>"
                     data-lng="<?php echo esc_attr($longitude); ?>">
                    <div style="text-align: center; color: #666;">
                        <p style="font-size: 18px; margin: 0 0 10px 0;"><?php _e('Map Integration Ready', 'burgland-homes'); ?></p>
                        <p style="margin: 0;">
                            <?php printf(
                                __('Coordinates: %s, %s', 'burgland-homes'),
                                esc_html($latitude),
                                esc_html($longitude)
                            ); ?>
                        </p>
                        <p style="margin: 10px 0 0 0; font-size: 14px;">
                            <?php _e('Integrate your preferred mapping service (Google Maps, Mapbox, etc.)', 'burgland-homes'); ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php _e('Please add latitude and longitude coordinates to enable map display.', 'burgland-homes'); ?>
                        <a href="<?php echo get_edit_post_link($community_id); ?>"><?php _e('Edit Community Details', 'burgland-homes'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
