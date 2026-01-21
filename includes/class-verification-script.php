<?php
/**
 * Burgland Homes - ACF Field Verification & Database Cleanup Script
 * 
 * This script checks for and optionally removes old taxonomy data
 * Run this from WordPress admin or via WP-CLI
 * 
 * @package Burgland_Homes
 * @version 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check and report on ACF field status and old taxonomy data
 */
function bh_verify_acf_fields_status() {
    global $wpdb;
    
    $report = array(
        'status' => 'success',
        'messages' => array(),
        'warnings' => array(),
        'errors' => array(),
    );
    
    // Check if ACF is active
    if (!function_exists('acf_get_field_group')) {
        $report['errors'][] = 'ACF plugin is not active or not installed';
        $report['status'] = 'error';
        return $report;
    }
    
    // Check for ACF field groups
    $field_groups = acf_get_field_groups();
    $bh_groups = array_filter($field_groups, function($group) {
        return strpos($group['key'], 'group_community_') === 0 || 
               strpos($group['key'], 'group_floor_plan_') === 0 || 
               strpos($group['key'], 'group_lot_') === 0;
    });
    
    if (empty($bh_groups)) {
        $report['errors'][] = 'No Burgland Homes ACF field groups found';
        $report['status'] = 'error';
    } else {
        $report['messages'][] = sprintf('Found %d Burgland Homes ACF field groups', count($bh_groups));
    }
    
    // Check for old taxonomy data
    $old_taxonomy_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'bh_floor_plan_community'"
    );
    
    if ($old_taxonomy_count > 0) {
        $report['warnings'][] = sprintf(
            'Found %d old taxonomy terms that should be removed. Run cleanup to fix.',
            $old_taxonomy_count
        );
        $report['status'] = 'warning';
    }
    
    // Check for floor plans with ACF relationship field
    $floor_plans_with_communities = $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
         WHERE meta_key = 'floor_plans_communities' AND post_id IN (
             SELECT ID FROM {$wpdb->posts} WHERE post_type = 'bh_floor_plan' AND post_status = 'publish'
         )"
    );
    
    $total_floor_plans = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'bh_floor_plan' AND post_status = 'publish'"
    );
    
    if ($total_floor_plans > 0) {
        $report['messages'][] = sprintf(
            '%d of %d floor plans have community relationships configured (%.1f%%)',
            $floor_plans_with_communities,
            $total_floor_plans,
            ($floor_plans_with_communities / $total_floor_plans) * 100
        );
        
        if ($floor_plans_with_communities == 0 && $old_taxonomy_count > 0) {
            $report['warnings'][] = 'No ACF relationships found but old taxonomy data exists. Data migration may be needed.';
        }
    }
    
    // Check for orphaned term relationships
    $orphaned_relationships = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->term_relationships} 
         WHERE term_taxonomy_id IN (
             SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
             WHERE taxonomy = 'bh_floor_plan_community'
         )"
    );
    
    if ($orphaned_relationships > 0) {
        $report['warnings'][] = sprintf(
            'Found %d orphaned term relationships that should be removed',
            $orphaned_relationships
        );
    }
    
    return $report;
}

/**
 * Clean up old taxonomy data
 * 
 * @param bool $dry_run If true, only report what would be deleted
 * @return array Report of actions taken
 */
function bh_cleanup_old_taxonomy_data($dry_run = true) {
    global $wpdb;
    
    $report = array(
        'status' => 'success',
        'dry_run' => $dry_run,
        'actions' => array(),
    );
    
    // Count what will be deleted
    $term_relationships = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->term_relationships} 
         WHERE term_taxonomy_id IN (
             SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
             WHERE taxonomy = 'bh_floor_plan_community'
         )"
    );
    
    $term_taxonomy = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'bh_floor_plan_community'"
    );
    
    if ($dry_run) {
        $report['actions'][] = sprintf('Would delete %d term relationships', $term_relationships);
        $report['actions'][] = sprintf('Would delete %d taxonomy terms', $term_taxonomy);
        $report['actions'][] = 'Set $dry_run to false to execute cleanup';
    } else {
        // Delete term relationships
        $deleted_relationships = $wpdb->query(
            "DELETE FROM {$wpdb->term_relationships} 
             WHERE term_taxonomy_id IN (
                 SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} 
                 WHERE taxonomy = 'bh_floor_plan_community'
             )"
        );
        
        // Delete taxonomy terms
        $deleted_taxonomy = $wpdb->query(
            "DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'bh_floor_plan_community'"
        );
        
        // Delete orphaned terms
        $deleted_terms = $wpdb->query(
            "DELETE FROM {$wpdb->terms} WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->term_taxonomy})"
        );
        
        $report['actions'][] = sprintf('Deleted %d term relationships', $deleted_relationships);
        $report['actions'][] = sprintf('Deleted %d taxonomy terms', $deleted_taxonomy);
        $report['actions'][] = sprintf('Deleted %d orphaned terms', $deleted_terms);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        $report['actions'][] = 'Flushed rewrite rules';
    }
    
    return $report;
}

/**
 * Display admin notice with verification results
 */
function bh_display_verification_notice() {
    // Only show on Burgland Homes admin pages
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'burgland-homes') === false) {
        return;
    }
    
    $report = bh_verify_acf_fields_status();
    
    if ($report['status'] === 'error') {
        echo '<div class="notice notice-error"><p><strong>Burgland Homes ACF Status:</strong></p><ul>';
        foreach ($report['errors'] as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    } elseif ($report['status'] === 'warning') {
        echo '<div class="notice notice-warning"><p><strong>Burgland Homes Status:</strong></p><ul>';
        foreach ($report['warnings'] as $warning) {
            echo '<li>' . esc_html($warning) . '</li>';
        }
        if (!empty($report['messages'])) {
            foreach ($report['messages'] as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
        }
        echo '</ul><p><a href="' . admin_url('admin.php?page=burgland-homes&action=cleanup_taxonomy') . '" class="button button-primary">Run Cleanup</a></p></div>';
    } else {
        // Only show if explicitly requested
        if (isset($_GET['bh_verify'])) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Burgland Homes Status: All Clear!</strong></p><ul>';
            foreach ($report['messages'] as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul></div>';
        }
    }
}

// Uncomment to enable automatic verification on admin pages
// add_action('admin_notices', 'bh_display_verification_notice');

// Example usage via WP-CLI or custom admin page:
/*
// Verify status
$status = bh_verify_acf_fields_status();
print_r($status);

// Dry run cleanup
$dry_run = bh_cleanup_old_taxonomy_data(true);
print_r($dry_run);

// Actual cleanup (use with caution!)
$cleanup = bh_cleanup_old_taxonomy_data(false);
print_r($cleanup);
*/
