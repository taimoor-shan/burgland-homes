<?php
/**
 * Custom Taxonomies Registration
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Taxonomies
 */
class Burgland_Homes_Taxonomies {
    
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
        add_action('init', array($this, 'register_taxonomies')); 
        
        // Prevent manual editing of terms
        add_filter('get_edit_term_link', array($this, 'prevent_manual_term_edit'), 10, 3);
        add_action('load-edit-tags.php', array($this, 'redirect_manual_term_edit'));
        add_action('wp_ajax_add-tag', array($this, 'prevent_ajax_term_creation'), 0);
        
        // Hook into community post type operations to sync terms
        add_action('save_post_bh_community', array($this, 'sync_community_term'), 10, 3);
        add_action('before_delete_post', array($this, 'maybe_delete_community_term'));
    }
    
    /**
     * Register all custom taxonomies
     */
    public function register_taxonomies() {
        $this->register_floor_plan_communities_taxonomy();
        $this->register_community_status_taxonomy();
    }

       
    /**
     * Register Community Status Taxonomy
     */
    private function register_community_status_taxonomy() {
        $labels = array(
            'name'                       => _x('Community Status', 'Taxonomy General Name', 'burgland-homes'),
            'singular_name'              => _x('Community Status', 'Taxonomy Singular Name', 'burgland-homes'),
            'menu_name'                  => __('Community Status', 'burgland-homes'),
            'all_items'                  => __('All Statuses', 'burgland-homes'),
            'parent_item'                => __('Parent Status', 'burgland-homes'),
            'parent_item_colon'          => __('Parent Status:', 'burgland-homes'),
            'new_item_name'              => __('New Status Name', 'burgland-homes'),
            'add_new_item'               => __('Add New Status', 'burgland-homes'),
            'edit_item'                  => __('Edit Status', 'burgland-homes'),
            'update_item'                => __('Update Status', 'burgland-homes'),
            'view_item'                  => __('View Status', 'burgland-homes'),
            'separate_items_with_commas' => __('Separate statuses with commas', 'burgland-homes'),
            'add_or_remove_items'        => __('Add or remove statuses', 'burgland-homes'),
            'choose_from_most_used'      => __('Choose from the most used', 'burgland-homes'),
            'popular_items'              => __('Popular Statuses', 'burgland-homes'),
            'search_items'               => __('Search Statuses', 'burgland-homes'),
            'not_found'                  => __('Not Found', 'burgland-homes'),
            'no_terms'                   => __('No statuses', 'burgland-homes'),
            'items_list'                 => __('Statuses list', 'burgland-homes'),
            'items_list_navigation'      => __('Statuses list navigation', 'burgland-homes'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array('slug' => 'community-status'),
        );

        register_taxonomy('bh_community_status', array('bh_community'), $args);
    }
    
    /**
     * Register Floor Plan Communities Taxonomy
     * This is a system-controlled taxonomy that syncs with bh_community posts
     * Users can only assign existing terms, not create new ones
     */
    private function register_floor_plan_communities_taxonomy() {
        $labels = array(
            'name'                       => _x('Floor Plan Communities', 'Taxonomy General Name', 'burgland-homes'),
            'singular_name'              => _x('Floor Plan Community', 'Taxonomy Singular Name', 'burgland-homes'),
            'menu_name'                  => __('Floor Plan Communities', 'burgland-homes'),
            'all_items'                  => __('All Communities', 'burgland-homes'),
            'parent_item'                => __('Parent Community', 'burgland-homes'),
            'parent_item_colon'          => __('Parent Community:', 'burgland-homes'),
            'new_item_name'              => __('New Community Name', 'burgland-homes'),
            'add_new_item'               => __('Add New Community', 'burgland-homes'),
            'edit_item'                  => __('Edit Community', 'burgland-homes'),
            'update_item'                => __('Update Community', 'burgland-homes'),
            'view_item'                  => __('View Community', 'burgland-homes'),
            'separate_items_with_commas' => __('Separate communities with commas', 'burgland-homes'),
            'add_or_remove_items'        => __('Add or remove communities', 'burgland-homes'),
            'choose_from_most_used'      => __('Choose from the most used communities', 'burgland-homes'),
            'popular_items'              => __('Popular Communities', 'burgland-homes'),
            'search_items'               => __('Search Communities', 'burgland-homes'),
            'not_found'                  => __('Not Found', 'burgland-homes'),
            'no_terms'                   => __('No communities', 'burgland-homes'),
            'items_list'                 => __('Floor Plan Communities list', 'burgland-homes'),
            'items_list_navigation'      => __('Floor Plan Communities list navigation', 'burgland-homes'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false, // Not hierarchical like categories, behaves like tags
            'public'                     => false, // Make it non-public to hide from UI except where needed
            'show_ui'                    => true, // Still show in admin for assignment purposes
            'show_in_menu'               => false, // Don't show in main menu
            'show_in_quick_edit'         => false, // Disable quick edit
            'show_admin_column'          => true, // Show in admin columns for floor plans
            'show_in_nav_menus'          => false, // Don't show in menus
            'show_tagcloud'              => false, // Don't show tag cloud
            'show_in_rest'               => true, // Enable Gutenberg support
            'rest_base'                  => 'floor-plan-communities',
            'meta_box_cb'                => array($this, 'floor_plan_community_meta_box'), // Custom meta box
            'update_count_callback'      => '_update_post_term_count',
            'capabilities' => array(
                'manage_terms' => 'do_not_allow',
                'edit_terms'   => 'do_not_allow',
                'delete_terms' => 'do_not_allow',
                'assign_terms' => 'edit_posts'
            )
        );
        
        register_taxonomy('bh_floor_plan_community', array('bh_floor_plan'), $args);
    }
    
    /**
     * Custom meta box for floor plan community assignment
     */
    public function floor_plan_community_meta_box($post) {
        $post_id = $post->ID;
        $terms = wp_get_object_terms($post_id, 'bh_floor_plan_community');
        $all_terms = get_terms(array(
            'taxonomy' => 'bh_floor_plan_community',
            'hide_empty' => false,
        ));
        
        $term_ids = array();
        foreach ($terms as $term) {
            $term_ids[] = $term->term_id;
        }
        
        echo '<div class="bh-floor-plan-community-selector">
            <input type="hidden" name="bh_floor_plan_community_nonce" value="' . wp_create_nonce('bh_floor_plan_community_nonce') . '" />
            <p>' . __('Select communities this floor plan is available in:', 'burgland-homes') . '</p>
            <div class="bh-floor-plan-community-checkboxes" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
';
        
        foreach ($all_terms as $term) {
            $checked = in_array($term->term_id, $term_ids) ? 'checked="checked"' : '';
            echo '<p><label><input type="checkbox" name="bh_floor_plan_community[]" value="' . $term->term_id . '" ' . $checked . '> ' . esc_html($term->name) . '</label></p>';
        }
        
        echo '</div>
        </div>';
    }
    
    /**
     * Prevent manual term editing
     */
    public function prevent_manual_term_edit($link, $term_id, $taxonomy) {
        if ($taxonomy === 'bh_floor_plan_community') {
            return false; // Remove the edit link
        }
        return $link;
    }
    
    /**
     * Redirect attempts to manually edit terms
     */
    public function redirect_manual_term_edit() {
        global $taxonomy;
        if ($taxonomy === 'bh_floor_plan_community') {
            wp_redirect(admin_url('edit.php?post_type=bh_floor_plan'));
            exit;
        }
    }
    
    /**
     * Prevent AJAX term creation
     */
    public function prevent_ajax_term_creation() {
        if ($_POST['taxonomy'] === 'bh_floor_plan_community') {
            wp_die(__('Manual term creation is not allowed for this taxonomy.', 'burgland-homes'));
        }
    }
    
    /**
     * Sync community to taxonomy term
     */
    public function sync_community_term($post_id, $post, $update) {
        if ($post->post_type !== 'bh_community') {
            return;
        }
        
        $term_name = $post->post_title;
        $term_slug = sanitize_title($post->post_name);
        $term_description = $post->post_content;
        
        // Check if term exists
        $existing_term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if ($existing_term) {
            // Update existing term if name changed
            if ($existing_term->name !== $term_name) {
                wp_update_term($existing_term->term_id, 'bh_floor_plan_community', array(
                    'name' => $term_name,
                    'slug' => $term_slug,
                    'description' => $term_description
                ));
            }
        } else {
            // Check if term exists by name (in case slug changed)
            $existing_term_by_name = get_term_by('name', $term_name, 'bh_floor_plan_community');
            if ($existing_term_by_name) {
                // Update slug if it changed
                wp_update_term($existing_term_by_name->term_id, 'bh_floor_plan_community', array(
                    'slug' => $term_slug,
                    'description' => $term_description
                ));
            } else {
                // Create new term
                wp_insert_term(
                    $term_name,
                    'bh_floor_plan_community',
                    array(
                        'slug' => $term_slug,
                        'description' => $term_description
                    )
                );
            }
        }
    }
    
    /**
     * Maybe delete community term when community is deleted
     */
    public function maybe_delete_community_term($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'bh_community') {
            return;
        }
        
        // Find and delete the corresponding term
        $term_slug = sanitize_title($post->post_name);
        $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if ($term) {
            // Remove term from all floor plans first
            $floor_plans = get_posts(array(
                'post_type' => 'bh_floor_plan',
                'numberposts' => -1,
                'post_status' => 'any',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'bh_floor_plan_community',
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    )
                )
            ));
            
            foreach ($floor_plans as $floor_plan) {
                wp_remove_object_terms($floor_plan->ID, $term->term_id, 'bh_floor_plan_community');
            }
            
            // Now delete the term
            wp_delete_term($term->term_id, 'bh_floor_plan_community');
        }
    }
}