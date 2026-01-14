<?php
/**
 * Relationships Management
 *
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Burgland_Homes_Relationships
 */
class Burgland_Homes_Relationships {
    
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
        add_action('add_meta_boxes', array($this, 'add_relationship_meta_boxes'));
    }
    
    /**
     * Add relationship meta boxes
     */
    public function add_relationship_meta_boxes() {
        // Add meta box to Community to show related floor plans and lots
        add_meta_box(
            'bh_community_relationships',
            __('Related Floor Plans & Lots', 'burgland-homes'),
            array($this, 'render_community_relationships'),
            'bh_community',
            'side',
            'default'
        );
        
        // Add meta box to Floor Plan to show related lots
        add_meta_box(
            'bh_floor_plan_relationships',
            __('Related Lots', 'burgland-homes'),
            array($this, 'render_floor_plan_relationships'),
            'bh_floor_plan',
            'side',
            'default'
        );
    }
    
    /**
     * Render Community relationships meta box
     */
    public function render_community_relationships($post) {
        $community_id = $post->ID;
        $community_post = get_post($community_id);
        
        // Get related floor plans via taxonomy
        $term_slug = sanitize_title($community_post->post_name);
        $term = get_term_by('slug', $term_slug, 'bh_floor_plan_community');
        
        if (!$term) {
            $term = get_term_by('name', $community_post->post_title, 'bh_floor_plan_community');
        }
        
        $floor_plan_query_args = array(
            'post_type' => 'bh_floor_plan',
            'posts_per_page' => -1,
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
        
        $floor_plans = new WP_Query($floor_plan_query_args);
        
        // Get related lots
        $lots = new WP_Query(array(
            'post_type' => 'bh_lot',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'lot_community',
                    'value' => $community_id,
                ),
            ),
        ));
        
        echo '<div style="margin-bottom: 15px;">';
        echo '<strong>' . __('Floor Plans:', 'burgland-homes') . '</strong> ' . $floor_plans->found_posts;
        if ($floor_plans->have_posts()) {
            echo '<ul style="margin: 5px 0; padding-left: 20px;">';
            while ($floor_plans->have_posts()) {
                $floor_plans->the_post();
                echo '<li><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a></li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        }
        echo '</div>';
        
        echo '<div>';
        echo '<strong>' . __('Lots/Homes:', 'burgland-homes') . '</strong> ' . $lots->found_posts;
        if ($lots->have_posts()) {
            echo '<ul style="margin: 5px 0; padding-left: 20px;">';
            while ($lots->have_posts()) {
                $lots->the_post();
                $status_terms = wp_get_post_terms(get_the_ID(), 'bh_lot_status');
                $status = $status_terms && !is_wp_error($status_terms) ? ' (' . $status_terms[0]->name . ')' : '';
                echo '<li><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a>' . $status . '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        }
        echo '</div>';
    }
    
    /**
     * Render Floor Plan relationships meta box
     */
    public function render_floor_plan_relationships($post) {
        $floor_plan_id = $post->ID;
        
        // Get related lots
        $lots = new WP_Query(array(
            'post_type' => 'bh_lot',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'lot_floor_plan',
                    'value' => $floor_plan_id,
                ),
            ),
        ));
        
        echo '<div>';
        echo '<strong>' . __('Lots using this floor plan:', 'burgland-homes') . '</strong> ' . $lots->found_posts;
        if ($lots->have_posts()) {
            echo '<ul style="margin: 5px 0; padding-left: 20px;">';
            while ($lots->have_posts()) {
                $lots->the_post();
                $community_id = get_post_meta(get_the_ID(), 'lot_community', true);
                $community_name = '';
                if ($community_id) {
                    $community = get_post($community_id);
                    $community_name = ' - ' . $community->post_title;
                }
                echo '<li><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a>' . $community_name . '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p style="margin: 5px 0;">' . __('No lots assigned yet', 'burgland-homes') . '</p>';
        }
        echo '</div>';
    }
}
