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
        
        // Add Community Status button to Communities list page
        add_action('manage_posts_extra_tablenav', array($this, 'add_community_status_button'), 10, 1);
        
        // Handle cleanup action
        add_action('admin_post_bh_cleanup_orphaned_terms', array($this, 'handle_cleanup_orphaned_terms'));
        
        // Add orphaned lot warnings
        add_action('admin_notices', array($this, 'show_orphaned_lot_notice'));
        add_action('edit_form_after_title', array($this, 'show_lot_community_warning'));
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
            null, // Hidden from menu - only accessible via direct link
            __('Manage Community', 'burgland-homes'),
            __('Manage Community', 'burgland-homes'),
            'edit_posts',
            'burgland-homes-manage-community',
            array($this, 'render_community_management')
        );
        
        // Add Archive Settings page (hidden from menu, accessed via dashboard button)
        add_submenu_page(
            null, // Hidden from menu - only accessible via dashboard button
            __('Archive Settings', 'burgland-homes'),
            __('Archive Settings', 'burgland-homes'),
            'manage_options',
            'burgland-homes-archive-settings',
            array($this, 'render_archive_settings')
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
            
            <?php
            // Display cleanup success/error messages
            if (isset($_GET['cleanup'])) {
                $message_type = sanitize_text_field($_GET['cleanup']);
                $deleted_count = isset($_GET['deleted']) ? intval($_GET['deleted']) : 0;
                
                $class = $message_type === 'success' ? 'notice-success' : 'notice-warning';
                $message = $deleted_count > 0 
                    ? sprintf(__('%d orphaned taxonomy term(s) have been successfully deleted.', 'burgland-homes'), $deleted_count)
                    : __('No orphaned taxonomy terms found. Your data is clean!', 'burgland-homes');
                
                echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
            ?>
            
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
                
                <div style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2>Archive Pages Settings</h2>
                    <p>Customize the header section for your archive pages (Communities, Floor Plans, and Lots).</p>
                    <a href="<?php echo admin_url('admin.php?page=burgland-homes-archive-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-settings" style="vertical-align: middle;"></span>
                        Manage Archive Settings
                    </a>
                    <p class="description" style="margin-top: 10px;">Configure title, subtitle, and featured images for each archive page.</p>
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
                       $screen->id === 'burgland-homes_page_burgland-homes-manage-community' ||
                       $screen->id === 'burgland-homes_page_burgland-homes-archive-settings')) {
            $is_plugin_page = true;
        }
        
        // Also check for other potential plugin pages
        if ($screen && strpos($screen->id, 'burgland-homes') !== false) {
            $is_plugin_page = true;
        }
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Enqueue media uploader for archive settings page
        if ($screen && $screen->id === 'burgland-homes_page_burgland-homes-archive-settings') {
            wp_enqueue_media();
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
                // Get status category (hierarchical taxonomy like Categories)
                $terms = get_the_terms($post_id, 'bh_community_status');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = array();
                    foreach ($terms as $term) {
                        $term_names[] = esc_html($term->name);
                    }
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
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
                // Get communities via ACF relationship field
                $community_ids = get_field('floor_plans_communities', $post_id);
                if (!empty($community_ids)) {
                    if (!is_array($community_ids)) {
                        $community_ids = array($community_ids);
                    }
                    $community_names = array();
                    foreach ($community_ids as $community_id) {
                        $community = get_post($community_id);
                        if ($community) {
                            $community_names[] = '<a href="' . get_edit_post_link($community->ID) . '">' . esc_html($community->post_title) . '</a>';
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
        $args = array(
            'post_type' => 'bh_floor_plan',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'floor_plans_communities',
                    'value' => '"' . $community_id . '"',
                    'compare' => 'LIKE',
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
     * Add Community Status button next to "Add Community" button
     */
    public function add_community_status_button($which) {
        global $typenow;
        
        // Only show on Communities list page and at the top of the table
        if ($typenow === 'bh_community' && $which === 'top') {
            $status_url = admin_url('edit-tags.php?taxonomy=bh_community_status&post_type=bh_community');
            ?>
            <div class="alignleft actions">
                <a href="<?php echo esc_url($status_url); ?>" class="button">
                    <span class="dashicons dashicons-category" style="margin-top: 3px;"></span>
                    <?php _e('Manage Status', 'burgland-homes'); ?>
                </a>
            </div>
            <?php
        }
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
                'meta_query' => array(
                    array(
                        'key' => 'floor_plans_communities',
                        'value' => '"' . $community_id . '"',
                        'compare' => 'LIKE',
                    ),
                ),
            );
            
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
        $latitude = get_post_meta($community_id, '_geocoded_latitude', true);
        $longitude = get_post_meta($community_id, '_geocoded_longitude', true);
        
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
    
    /**
     * Handle cleanup orphaned terms action
     */
    public function handle_cleanup_orphaned_terms() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'burgland-homes'));
        }
        
        // Verify nonce
        if (!isset($_POST['bh_cleanup_nonce']) || !wp_verify_nonce($_POST['bh_cleanup_nonce'], 'bh_cleanup_orphaned_terms')) {
            wp_die(__('Security check failed.', 'burgland-homes'));
        }
        
        // Run cleanup
        $utilities = Burgland_Homes_Utilities::get_instance();
        $result = $utilities->cleanup_orphaned_taxonomy_terms();
        
        // Prepare message
        $message_type = 'success';
        if ($result['deleted'] > 0) {
            $message = sprintf(
                __('%d orphaned taxonomy term(s) have been successfully deleted.', 'burgland-homes'),
                $result['deleted']
            );
        } else {
            $message = __('No orphaned taxonomy terms found. Your data is clean!', 'burgland-homes');
        }
        
        if (!empty($result['errors'])) {
            $message_type = 'warning';
            $message .= ' ' . __('However, some errors occurred:', 'burgland-homes') . ' ' . implode(', ', $result['errors']);
        }
        
        // Redirect back with message
        wp_redirect(add_query_arg(
            array(
                'page' => 'burgland-homes',
                'cleanup' => $message_type,
                'deleted' => $result['deleted']
            ),
            admin_url('admin.php')
        ));
        exit;
    }
    
    /**
     * Show notice for orphaned lots in admin
     */
    public function show_orphaned_lot_notice() {
        $screen = get_current_screen();
        
        if ($screen && $screen->post_type === 'bh_lot' && $screen->base === 'edit') {
            // Check if there are any orphaned lots
            $orphaned_lots = get_posts(array(
                'post_type' => 'bh_lot',
                'post_status' => 'draft',
                'meta_query' => array(
                    array(
                        'key' => '_bh_orphaned_lot',
                        'value' => '1',
                        'compare' => '='
                    )
                ),
                'fields' => 'ids',
                'posts_per_page' => -1
            ));
            
            if (!empty($orphaned_lots)) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Warning:', 'burgland-homes'); ?></strong>
                        <?php printf(
                            __('%d lot(s) have been set to draft because their associated community was deleted. Please reassign them to a valid community.', 'burgland-homes'),
                            count($orphaned_lots)
                        ); ?>
                    </p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Show warning on individual lot edit screen if orphaned
     */
    public function show_lot_community_warning($post) {
        if ($post->post_type !== 'bh_lot') {
            return;
        }
        
        $is_orphaned = get_post_meta($post->ID, '_bh_orphaned_lot', true);
        
        if ($is_orphaned) {
            $deleted_community_id = get_post_meta($post->ID, '_bh_deleted_community_id', true);
            ?>
            <div class="notice notice-error inline" style="margin: 10px 0;">
                <p>
                    <strong><?php _e('⚠️ Orphaned Lot:', 'burgland-homes'); ?></strong>
                    <?php _e('This lot\'s associated community has been deleted. Please select a new community below to restore this lot.', 'burgland-homes'); ?>
                    <?php if ($deleted_community_id): ?>
                        <br><small><?php printf(__('Previous community ID: %d', 'burgland-homes'), $deleted_community_id); ?></small>
                    <?php endif; ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Render Archive Settings page
     */
    public function render_archive_settings() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'burgland-homes'));
        }
        
        // Handle form submission
        if (isset($_POST['bh_save_archive_settings']) && check_admin_referer('bh_archive_settings', 'bh_archive_settings_nonce')) {
            $this->save_archive_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Archive settings saved successfully!', 'burgland-homes') . '</p></div>';
        }
        
        // Get current settings
        $communities_title = get_option('bh_archive_communities_title', 'Florida');
        $communities_subtitle = get_option('bh_archive_communities_subtitle', 'New Home Communities');
        $communities_image = get_option('bh_archive_communities_image', '');
        
        $floor_plans_title = get_option('bh_archive_floor_plans_title', 'Our Floor Plans');
        $floor_plans_subtitle = get_option('bh_archive_floor_plans_subtitle', 'Find Your Perfect Home Design');
        $floor_plans_image = get_option('bh_archive_floor_plans_image', '');
        
        $lots_title = get_option('bh_archive_lots_title', 'Available Homes');
        $lots_subtitle = get_option('bh_archive_lots_subtitle', 'Find Your Dream Property');
        $lots_image = get_option('bh_archive_lots_image', '');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Archive Pages Settings', 'burgland-homes'); ?></h1>
            <p><?php _e('Customize the header section for each archive page.', 'burgland-homes'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('bh_archive_settings', 'bh_archive_settings_nonce'); ?>
                
                <div style="display: grid; gap: 30px; margin-top: 30px;">
                    
                    <!-- Communities Archive -->
                    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                            <span class="dashicons dashicons-admin-multisite" style="color: #1e40af;"></span>
                            <?php _e('Communities Archive', 'burgland-homes'); ?>
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="communities_title"><?php _e('Title', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="communities_title" name="communities_title" 
                                           value="<?php echo esc_attr($communities_title); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Main heading for the communities archive page', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="communities_subtitle"><?php _e('Subtitle', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="communities_subtitle" name="communities_subtitle" 
                                           value="<?php echo esc_attr($communities_subtitle); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Subtitle text below the main heading', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="communities_image"><?php _e('Featured Image', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <div class="bh-image-upload-wrapper">
                                        <input type="hidden" id="communities_image" name="communities_image" 
                                               value="<?php echo esc_attr($communities_image); ?>" />
                                        <button type="button" class="button bh-upload-image-button" data-target="communities_image">
                                            <?php _e('Choose Image', 'burgland-homes'); ?>
                                        </button>
                                        <button type="button" class="button bh-remove-image-button" data-target="communities_image" 
                                                style="<?php echo empty($communities_image) ? 'display:none;' : ''; ?>">
                                            <?php _e('Remove Image', 'burgland-homes'); ?>
                                        </button>
                                        <div class="bh-image-preview" style="margin-top: 10px;">
                                            <?php if ($communities_image): ?>
                                                <img src="<?php echo esc_url(wp_get_attachment_url($communities_image)); ?>" 
                                                     style="max-width: 300px; height: auto; display: block;" />
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description"><?php _e('Background image for the header section', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Floor Plans Archive -->
                    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                            <span class="dashicons dashicons-layout" style="color: #059669;"></span>
                            <?php _e('Floor Plans Archive', 'burgland-homes'); ?>
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="floor_plans_title"><?php _e('Title', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="floor_plans_title" name="floor_plans_title" 
                                           value="<?php echo esc_attr($floor_plans_title); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Main heading for the floor plans archive page', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="floor_plans_subtitle"><?php _e('Subtitle', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="floor_plans_subtitle" name="floor_plans_subtitle" 
                                           value="<?php echo esc_attr($floor_plans_subtitle); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Subtitle text below the main heading', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="floor_plans_image"><?php _e('Featured Image', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <div class="bh-image-upload-wrapper">
                                        <input type="hidden" id="floor_plans_image" name="floor_plans_image" 
                                               value="<?php echo esc_attr($floor_plans_image); ?>" />
                                        <button type="button" class="button bh-upload-image-button" data-target="floor_plans_image">
                                            <?php _e('Choose Image', 'burgland-homes'); ?>
                                        </button>
                                        <button type="button" class="button bh-remove-image-button" data-target="floor_plans_image" 
                                                style="<?php echo empty($floor_plans_image) ? 'display:none;' : ''; ?>">
                                            <?php _e('Remove Image', 'burgland-homes'); ?>
                                        </button>
                                        <div class="bh-image-preview" style="margin-top: 10px;">
                                            <?php if ($floor_plans_image): ?>
                                                <img src="<?php echo esc_url(wp_get_attachment_url($floor_plans_image)); ?>" 
                                                     style="max-width: 300px; height: auto; display: block;" />
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description"><?php _e('Background image for the header section', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Lots/Homes Archive -->
                    <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                        <h2 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                            <span class="dashicons dashicons-location" style="color: #dc2626;"></span>
                            <?php _e('Lots/Homes Archive', 'burgland-homes'); ?>
                        </h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="lots_title"><?php _e('Title', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="lots_title" name="lots_title" 
                                           value="<?php echo esc_attr($lots_title); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Main heading for the lots/homes archive page', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="lots_subtitle"><?php _e('Subtitle', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="lots_subtitle" name="lots_subtitle" 
                                           value="<?php echo esc_attr($lots_subtitle); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Subtitle text below the main heading', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="lots_image"><?php _e('Featured Image', 'burgland-homes'); ?></label>
                                </th>
                                <td>
                                    <div class="bh-image-upload-wrapper">
                                        <input type="hidden" id="lots_image" name="lots_image" 
                                               value="<?php echo esc_attr($lots_image); ?>" />
                                        <button type="button" class="button bh-upload-image-button" data-target="lots_image">
                                            <?php _e('Choose Image', 'burgland-homes'); ?>
                                        </button>
                                        <button type="button" class="button bh-remove-image-button" data-target="lots_image" 
                                                style="<?php echo empty($lots_image) ? 'display:none;' : ''; ?>">
                                            <?php _e('Remove Image', 'burgland-homes'); ?>
                                        </button>
                                        <div class="bh-image-preview" style="margin-top: 10px;">
                                            <?php if ($lots_image): ?>
                                                <img src="<?php echo esc_url(wp_get_attachment_url($lots_image)); ?>" 
                                                     style="max-width: 300px; height: auto; display: block;" />
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="description"><?php _e('Background image for the header section', 'burgland-homes'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                </div>
                
                <p class="submit">
                    <input type="submit" name="bh_save_archive_settings" class="button button-primary" 
                           value="<?php esc_attr_e('Save Settings', 'burgland-homes'); ?>" />
                    <a href="<?php echo admin_url('admin.php?page=burgland-homes'); ?>" class="button">
                        <?php _e('Back to Dashboard', 'burgland-homes'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Media uploader
            var mediaUploader;
            
            $('.bh-upload-image-button').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetId = button.data('target');
                var targetInput = $('#' + targetId);
                var wrapper = button.closest('.bh-image-upload-wrapper');
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: '<?php _e('Choose Image', 'burgland-homes'); ?>',
                    button: {
                        text: '<?php _e('Use This Image', 'burgland-homes'); ?>'
                    },
                    multiple: false
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    targetInput.val(attachment.id);
                    wrapper.find('.bh-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto; display: block;" />');
                    wrapper.find('.bh-remove-image-button').show();
                });
                
                mediaUploader.open();
            });
            
            $('.bh-remove-image-button').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetId = button.data('target');
                var wrapper = button.closest('.bh-image-upload-wrapper');
                
                $('#' + targetId).val('');
                wrapper.find('.bh-image-preview').html('');
                button.hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save archive settings
     */
    private function save_archive_settings() {
        // Communities
        update_option('bh_archive_communities_title', sanitize_text_field($_POST['communities_title']));
        update_option('bh_archive_communities_subtitle', sanitize_text_field($_POST['communities_subtitle']));
        update_option('bh_archive_communities_image', intval($_POST['communities_image']));
        
        // Floor Plans
        update_option('bh_archive_floor_plans_title', sanitize_text_field($_POST['floor_plans_title']));
        update_option('bh_archive_floor_plans_subtitle', sanitize_text_field($_POST['floor_plans_subtitle']));
        update_option('bh_archive_floor_plans_image', intval($_POST['floor_plans_image']));
        
        // Lots
        update_option('bh_archive_lots_title', sanitize_text_field($_POST['lots_title']));
        update_option('bh_archive_lots_subtitle', sanitize_text_field($_POST['lots_subtitle']));
        update_option('bh_archive_lots_image', intval($_POST['lots_image']));
    }
}
