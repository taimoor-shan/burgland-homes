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
     * Flag to prevent infinite loops during sync
     */
    private $syncing = false;
    
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
        // CRITICAL: Changed priority from 25 to 50 to ensure ACF fields are saved first
        // ACF saves at priority 10, our sync must happen AFTER that
        add_action('save_post_bh_community', array($this, 'sync_community_term'), 50, 3);
        add_action('before_delete_post', array($this, 'maybe_delete_community_term'), 10);
        add_action('transition_post_status', array($this, 'handle_community_status_change'), 10, 3);
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
     * CRITICAL: Each community must have its OWN unique term, identified by stored post ID
     */
    public function sync_community_term($post_id, $post, $update) {
        // Prevent infinite loops
        if ($this->syncing) {
            error_log('Burgland Homes: sync_community_term skipped - already syncing');
            return;
        }
        
        // Verify this is a community post
        if ($post->post_type !== 'bh_community') {
            return;
        }
        
        // Skip autosaves and revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            error_log('Burgland Homes: sync_community_term skipped - autosave');
            return;
        }
        
        if (wp_is_post_revision($post_id)) {
            error_log('Burgland Homes: sync_community_term skipped - revision');
            return;
        }
        
        // Only sync published communities
        if ($post->post_status !== 'publish') {
            error_log(sprintf(
                'Burgland Homes: sync_community_term skipped - status is %s (not publish)',
                $post->post_status
            ));
            return;
        }
        
        error_log(sprintf(
            'Burgland Homes: Starting taxonomy sync for community #%d (%s)',
            $post_id,
            $post->post_title
        ));
        
        // Set syncing flag
        $this->syncing = true;
        
        $term_name = $post->post_title;
        $term_slug = $post->post_name ? sanitize_title($post->post_name) : sanitize_title($term_name);
        $term_description = $post->post_content;
        
        // Empty title check
        if (empty($term_name)) {
            error_log(sprintf(
                'Burgland Homes: Cannot sync community #%d - empty title',
                $post_id
            ));
            $this->syncing = false;
            return;
        }
        
        // CRITICAL FIX: First, check if THIS community already has a term (by stored meta)
        $existing_term_id = get_post_meta($post_id, '_bh_taxonomy_term_id', true);
        
        if ($existing_term_id) {
            $existing_term = get_term($existing_term_id, 'bh_floor_plan_community');
            
            if ($existing_term && !is_wp_error($existing_term)) {
                // Verify this term actually belongs to this community
                $term_community_id = get_term_meta($existing_term->term_id, '_bh_community_post_id', true);
                
                if ($term_community_id == $post_id) {
                    // This is OUR term - update it
                    error_log(sprintf(
                        'Burgland Homes: Updating OUR existing term #%d for community #%d',
                        $existing_term->term_id,
                        $post_id
                    ));
                    
                    wp_update_term($existing_term->term_id, 'bh_floor_plan_community', array(
                        'name' => $term_name,
                        'slug' => $term_slug,
                        'description' => $term_description
                    ));
                    
                    $this->syncing = false;
                    error_log(sprintf(
                        'Burgland Homes: Completed taxonomy sync for community #%d (updated existing term)',
                        $post_id
                    ));
                    return;
                } else {
                    // Term exists but belongs to different community - orphaned reference, clear it
                    error_log(sprintf(
                        'Burgland Homes: WARNING - Term #%d belongs to community #%d, not #%d. Clearing orphaned reference.',
                        $existing_term->term_id,
                        $term_community_id,
                        $post_id
                    ));
                    delete_post_meta($post_id, '_bh_taxonomy_term_id');
                }
            } else {
                // Term no longer exists - clear the reference
                error_log(sprintf(
                    'Burgland Homes: Stored term #%d no longer exists for community #%d. Clearing reference.',
                    $existing_term_id,
                    $post_id
                ));
                delete_post_meta($post_id, '_bh_taxonomy_term_id');
            }
        }
        
        // CRITICAL FIX: Check if a term with this slug exists AND belongs to another community
        $term_by_slug = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if ($term_by_slug) {
            $slug_term_community_id = get_term_meta($term_by_slug->term_id, '_bh_community_post_id', true);
            
            if ($slug_term_community_id && $slug_term_community_id != $post_id) {
                // This term belongs to a DIFFERENT community - generate unique slug
                error_log(sprintf(
                    'Burgland Homes: Term with slug "%s" belongs to community #%d, not #%d. Generating unique slug.',
                    $term_slug,
                    $slug_term_community_id,
                    $post_id
                ));
                $term_slug = $term_slug . '-' . $post_id;
            } elseif (!$slug_term_community_id) {
                // Orphaned term with no owner - DELETE IT and create fresh
                error_log(sprintf(
                    'Burgland Homes: Found orphaned term #%d with slug "%s". Deleting before creating new.',
                    $term_by_slug->term_id,
                    $term_slug
                ));
                
                // Remove from all floor plans first
                $this->cleanup_orphaned_term($term_by_slug->term_id);
                wp_delete_term($term_by_slug->term_id, 'bh_floor_plan_community');
            }
        }
        
        // CRITICAL FIX: Also check by name - and DELETE orphaned terms
        $term_by_name = get_term_by('name', $term_name, 'bh_floor_plan_community');
        
        if ($term_by_name) {
            $name_term_community_id = get_term_meta($term_by_name->term_id, '_bh_community_post_id', true);
            
            if ($name_term_community_id && $name_term_community_id != $post_id) {
                // This term belongs to a DIFFERENT community - we'll create a new one with modified name
                error_log(sprintf(
                    'Burgland Homes: Term with name "%s" belongs to community #%d. Creating separate term for #%d.',
                    $term_name,
                    $name_term_community_id,
                    $post_id
                ));
            } elseif (!$name_term_community_id) {
                // Orphaned term with no owner - DELETE IT
                error_log(sprintf(
                    'Burgland Homes: Found orphaned term #%d with name "%s". Deleting before creating new.',
                    $term_by_name->term_id,
                    $term_name
                ));
                
                $this->cleanup_orphaned_term($term_by_name->term_id);
                wp_delete_term($term_by_name->term_id, 'bh_floor_plan_community');
            } else {
                // This term belongs to US - update it
                error_log(sprintf(
                    'Burgland Homes: Found our term #%d by name for community #%d. Updating.',
                    $term_by_name->term_id,
                    $post_id
                ));
                
                wp_update_term($term_by_name->term_id, 'bh_floor_plan_community', array(
                    'name' => $term_name,
                    'slug' => $term_slug,
                    'description' => $term_description
                ));
                
                // Store the relationship
                update_post_meta($post_id, '_bh_taxonomy_term_id', $term_by_name->term_id);
                
                $this->syncing = false;
                error_log(sprintf(
                    'Burgland Homes: Completed taxonomy sync for community #%d',
                    $post_id
                ));
                return;
            }
        }
        
        // Create NEW term for this community
        error_log(sprintf(
            'Burgland Homes: Creating NEW taxonomy term for community #%d (%s, slug: %s)',
            $post_id,
            $term_name,
            $term_slug
        ));
        
        $result = wp_insert_term(
            $term_name,
            'bh_floor_plan_community',
            array(
                'slug' => $term_slug,
                'description' => $term_description
            )
        );
        
        if (is_wp_error($result)) {
            error_log('Burgland Homes: Failed to create taxonomy term for community ' . $post_id . ': ' . $result->get_error_message());
            
            // If slug conflict, try with post ID appended
            if ($result->get_error_code() === 'term_exists') {
                $unique_slug = $term_slug . '-' . $post_id;
                error_log(sprintf(
                    'Burgland Homes: Retrying with unique slug: %s',
                    $unique_slug
                ));
                
                $result = wp_insert_term(
                    $term_name,
                    'bh_floor_plan_community',
                    array(
                        'slug' => $unique_slug,
                        'description' => $term_description
                    )
                );
            }
        }
        
        if (!is_wp_error($result)) {
            // Store bidirectional relationship
            $term_id = $result['term_id'];
            update_post_meta($post_id, '_bh_taxonomy_term_id', $term_id);
            update_term_meta($term_id, '_bh_community_post_id', $post_id);
            
            error_log(sprintf(
                'Burgland Homes: Successfully created taxonomy term #%d for community #%d (stored relationship)',
                $term_id,
                $post_id
            ));
        } else {
            error_log('Burgland Homes: FINAL FAILURE to create taxonomy term for community ' . $post_id . ': ' . $result->get_error_message());
        }
        
        // Reset syncing flag
        error_log(sprintf(
            'Burgland Homes: Completed taxonomy sync for community #%d',
            $post_id
        ));
        $this->syncing = false;
    }
    
    /**
     * Clean up an orphaned term - remove from all floor plans
     */
    private function cleanup_orphaned_term($term_id) {
        $floor_plans = get_posts(array(
            'post_type' => 'bh_floor_plan',
            'numberposts' => -1,
            'post_status' => 'any',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bh_floor_plan_community',
                    'field' => 'term_id',
                    'terms' => $term_id
                )
            ),
            'fields' => 'ids'
        ));
        
        foreach ($floor_plans as $fp_id) {
            wp_remove_object_terms($fp_id, $term_id, 'bh_floor_plan_community');
            clean_post_cache($fp_id);
        }
        
        clean_term_cache($term_id, 'bh_floor_plan_community');
        
        error_log(sprintf(
            'Burgland Homes: Cleaned up orphaned term #%d from %d floor plans',
            $term_id,
            count($floor_plans)
        ));
    }
    
    /**
     * Handle community status changes (trash, delete, restore)
     */
    public function handle_community_status_change($new_status, $old_status, $post) {
        // Only handle community post type
        if ($post->post_type !== 'bh_community') {
            return;
        }
        
        // Prevent infinite loops
        if ($this->syncing) {
            return;
        }
        
        $this->syncing = true;
        
        // If transitioning to trash or any non-publish status, remove the term
        if ($new_status === 'trash' || ($old_status === 'publish' && $new_status !== 'publish')) {
            $this->remove_community_term($post->ID, $post);
        }
        // If transitioning to publish from trash/draft, sync the term
        elseif ($new_status === 'publish' && $old_status !== 'publish') {
            $this->sync_community_term($post->ID, $post, true);
        }
        
        $this->syncing = false;
    }
    
    /**
     * Delete community term when community is permanently deleted
     */
    public function maybe_delete_community_term($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'bh_community') {
            return;
        }
        
        // Prevent infinite loops
        if ($this->syncing) {
            return;
        }
        
        $this->syncing = true;
        $this->remove_community_term($post_id, $post);
        $this->syncing = false;
    }
    
    /**
     * Remove community term and clean up relationships
     */
    private function remove_community_term($post_id, $post) {
        error_log(sprintf(
            'Burgland Homes: Starting removal of taxonomy term for community #%d (%s)',
            $post_id,
            $post->post_title
        ));
        
        // CRITICAL FIX: First check if we have a stored term ID
        $stored_term_id = get_post_meta($post_id, '_bh_taxonomy_term_id', true);
        $term = null;
        
        if ($stored_term_id) {
            $term = get_term($stored_term_id, 'bh_floor_plan_community');
            if ($term && !is_wp_error($term)) {
                // Verify ownership
                $term_community_id = get_term_meta($term->term_id, '_bh_community_post_id', true);
                if ($term_community_id != $post_id) {
                    error_log(sprintf(
                        'Burgland Homes: Term #%d does not belong to community #%d (belongs to #%d). Skipping term deletion.',
                        $term->term_id,
                        $post_id,
                        $term_community_id
                    ));
                    $term = null;
                }
            } else {
                $term = null;
            }
        }
        
        // Fallback: Find term by slug if no stored reference
        if (!$term) {
            $term_slug = $post->post_name ? sanitize_title($post->post_name) : sanitize_title($post->post_title);
            $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
            
            if ($term && !is_wp_error($term)) {
                // Verify ownership before deleting
                $term_community_id = get_term_meta($term->term_id, '_bh_community_post_id', true);
                if ($term_community_id && $term_community_id != $post_id) {
                    error_log(sprintf(
                        'Burgland Homes: Term #%d with slug "%s" belongs to different community #%d. Skipping.',
                        $term->term_id,
                        $term_slug,
                        $term_community_id
                    ));
                    $term = null;
                }
            }
        }
        
        // Fallback: Try by name
        if (!$term) {
            $term = get_term_by('name', $post->post_title, 'bh_floor_plan_community');
            
            if ($term && !is_wp_error($term)) {
                // Verify ownership before deleting
                $term_community_id = get_term_meta($term->term_id, '_bh_community_post_id', true);
                if ($term_community_id && $term_community_id != $post_id) {
                    error_log(sprintf(
                        'Burgland Homes: Term #%d with name "%s" belongs to different community #%d. Skipping.',
                        $term->term_id,
                        $post->post_title,
                        $term_community_id
                    ));
                    $term = null;
                }
            }
        }
        
        if (!$term || is_wp_error($term)) {
            error_log(sprintf(
                'Burgland Homes: No taxonomy term found for community #%d',
                $post_id
            ));
            // Clean up any orphaned meta
            delete_post_meta($post_id, '_bh_taxonomy_term_id');
            // Still handle orphaned lots
            $this->handle_orphaned_lots($post_id);
            return;
        }
        
        error_log(sprintf(
            'Burgland Homes: Found taxonomy term #%d ("%s") for community #%d',
            $term->term_id,
            $term->name,
            $post_id
        ));
        
        // Get all floor plans using this term
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
            ),
            'fields' => 'ids' // Only get IDs for better performance
        ));
        
        error_log(sprintf(
            'Burgland Homes: Found %d floor plans using taxonomy term #%d',
            count($floor_plans),
            $term->term_id
        ));
        
        // Remove term from all floor plans AND clean up metadata
        if (!empty($floor_plans)) {
            foreach ($floor_plans as $floor_plan_id) {
                error_log(sprintf(
                    'Burgland Homes: Removing term #%d from floor plan #%d',
                    $term->term_id,
                    $floor_plan_id
                ));
                
                // Remove the term relationship
                $removed = wp_remove_object_terms($floor_plan_id, $term->term_id, 'bh_floor_plan_community');
                
                if (is_wp_error($removed)) {
                    error_log(sprintf(
                        'Burgland Homes: ERROR removing term from floor plan #%d: %s',
                        $floor_plan_id,
                        $removed->get_error_message()
                    ));
                } else {
                    error_log(sprintf(
                        'Burgland Homes: Successfully removed term from floor plan #%d',
                        $floor_plan_id
                    ));
                }
                
                // CRITICAL: Clean up taxonomy cache for this floor plan
                clean_post_cache($floor_plan_id);
                wp_cache_delete($floor_plan_id, 'bh_floor_plan_community' . '_relationships');
            }
        }
        
        // CRITICAL: Force clean the term cache before deletion
        clean_term_cache($term->term_id, 'bh_floor_plan_community');
        
        // Delete the term
        error_log(sprintf(
            'Burgland Homes: Deleting taxonomy term #%d ("%s")',
            $term->term_id,
            $term->name
        ));
        
        $result = wp_delete_term($term->term_id, 'bh_floor_plan_community');
        
        // Log any errors
        if (is_wp_error($result)) {
            error_log('Burgland Homes: Failed to delete taxonomy term for community ' . $post_id . ': ' . $result->get_error_message());
        } else {
            error_log(sprintf(
                'Burgland Homes: Successfully deleted taxonomy term #%d for community #%d',
                $term->term_id,
                $post_id
            ));
        }
        
        // Clean up bidirectional relationship meta
        delete_post_meta($post_id, '_bh_taxonomy_term_id');
        delete_term_meta($term->term_id, '_bh_community_post_id');
        
        // CRITICAL: Clean up term count cache
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d",
            $term->term_taxonomy_id
        ));
        
        error_log(sprintf(
            'Burgland Homes: Cleaned up orphaned term relationships for term #%d',
            $term->term_id
        ));
        
        // Handle orphaned lots
        $this->handle_orphaned_lots($post_id);
        
        error_log(sprintf(
            'Burgland Homes: Completed taxonomy term removal for community #%d',
            $post_id
        ));
    }
    
    /**
     * Handle lots when their community is deleted
     * Sets lots to draft status to prevent broken relationships
     */
    private function handle_orphaned_lots($community_id) {
        // Get all lots associated with this community
        $lots = get_posts(array(
            'post_type' => 'bh_lot',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => 'lot_community',
                    'value' => $community_id,
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));
        
        if (empty($lots)) {
            return;
        }
        
        // Set lots to draft status and flag as orphaned
        foreach ($lots as $lot_id) {
            // Change post status to draft
            wp_update_post(array(
                'ID' => $lot_id,
                'post_status' => 'draft'
            ));
            
            // Add admin notice meta so we can show a warning in the lot editor
            update_post_meta($lot_id, '_bh_orphaned_lot', 1);
            update_post_meta($lot_id, '_bh_deleted_community_id', $community_id);
            
            // Log the action
            error_log(sprintf(
                'Burgland Homes: Lot #%d set to draft due to community #%d deletion',
                $lot_id,
                $community_id
            ));
        }
    }
}