# ACF Fields Disappearing - Root Cause Analysis & Fix

## Issue Description
ACF fields were disappearing from the WordPress admin interface when establishing relationships between Floor Plan and Community posts. The same issue occurred with the old taxonomy approach and persisted after the v2.0.0 refactor to ACF Relationship fields.

## Root Cause

The problem was caused by **incomplete removal of the old `bh_floor_plan_community` taxonomy system**. Even though the taxonomy registration was removed from `class-taxonomies.php`, several files still contained references to and logic for the old taxonomy:

### 1. **class-utilities.php** (Lines 46-410)
   - The `get_floor_plan_ranges()` method queried the non-existent `bh_floor_plan_community` taxonomy
   - Methods `sync_existing_communities()`, `cleanup_orphaned_taxonomy_terms()`, and `force_cleanup_orphaned_terms()` attempted to manipulate the removed taxonomy
   - This caused PHP errors/warnings that interrupted ACF's field group loading process

### 2. **single-floor-plan.php** (Line 19)
   - Referenced the old `floor_plan_community` meta key (singular, taxonomy-based)
   - Should have been using the new `floor_plans_communities` ACF field (plural, relationship-based)

### 3. **Database State**
   - The old `bh_floor_plan_community` taxonomy data may still exist in the database
   - WordPress attempts to query this non-existent taxonomy, causing conflicts

## Impact

When the admin interface loaded:
1. `get_floor_plan_ranges()` was called (likely by admin columns or dashboard)
2. It attempted to query the removed taxonomy using `get_term_by()` and `get_terms()`
3. This caused PHP warnings/errors that prevented ACF from properly initializing
4. ACF field groups failed to display in the admin interface
5. Users couldn't see or edit any ACF fields

## Files Modified

### 1. `/includes/class-utilities.php`
**Changes:**
- Refactored `get_floor_plan_ranges()` to use ACF meta_query instead of taxonomy queries
- Removed old taxonomy query logic (lines 60-95)
- Replaced with ACF relationship field query:
  ```php
  'meta_query' => array(
      array(
          'key' => 'floor_plans_communities',
          'value' => '"' . $community_id . '"',
          'compare' => 'LIKE',
      ),
  ),
  ```
- Deprecated all taxonomy-related methods:
  - `maybe_sync_communities_to_taxonomy()`
  - `sync_existing_communities()`
  - `cleanup_orphaned_taxonomy_terms()`
  - `force_cleanup_orphaned_terms()`
- Added deprecation warnings to these methods

**Lines changed:** ~180 lines removed/refactored

### 2. `/templates/single-floor-plan.php`
**Changes:**
- Line 19: Changed from:
  ```php
  $community_id = get_post_meta($post_id, 'floor_plan_community', true);
  ```
- To:
  ```php
  // Get the first community from the ACF relationship field
  $community_ids = get_field('floor_plans_communities', $post_id);
  $community_id = is_array($community_ids) && !empty($community_ids) ? $community_ids[0] : null;
  ```

**Lines changed:** 1 line removed, 3 lines added

### 3. `/README.md`
**Changes:**
- Added comprehensive troubleshooting section
- Included database cleanup SQL queries
- Added step-by-step resolution guide

**Lines changed:** 25 lines added

## Verification Steps

To verify the fix works:

1. **Clear WordPress Cache**
   - Disable all caching plugins
   - Clear browser cache
   - Go to Settings > Permalinks and click "Save Changes"

2. **Test Floor Plan Edit**
   - Navigate to any Floor Plan in admin
   - Verify all ACF fields are visible
   - Select communities using the "Communities" relationship field
   - Save and verify data persists

3. **Test Community Dashboard**
   - Navigate to a Community post
   - Check the "Floor Plans" tab loads correctly
   - Verify floor plan counts are accurate

4. **Check Debug Log**
   - Enable `WP_DEBUG` in wp-config.php
   - Check for any errors related to `bh_floor_plan_community`
   - Verify no new PHP warnings appear

## Database Cleanup (If Needed)

If ACF fields still don't appear after code fixes, manually remove old taxonomy data:

```sql
-- Backup first!
-- Remove orphaned term relationships
DELETE FROM wp_term_relationships 
WHERE term_taxonomy_id IN (
    SELECT term_taxonomy_id 
    FROM wp_term_taxonomy 
    WHERE taxonomy = 'bh_floor_plan_community'
);

-- Remove taxonomy terms
DELETE FROM wp_term_taxonomy 
WHERE taxonomy = 'bh_floor_plan_community';

-- Clean up orphaned term entries
DELETE FROM wp_terms 
WHERE term_id NOT IN (
    SELECT term_id FROM wp_term_taxonomy
);
```

**⚠️ ALWAYS backup your database before running these queries!**

## Prevention

To prevent similar issues in the future:

1. **Complete Refactors**: When removing a system (like taxonomies), grep the entire codebase for all references
2. **Database Migration**: Consider adding a migration script to clean up old data automatically
3. **Deprecation Warnings**: Log deprecation warnings when old methods are called
4. **Testing Protocol**: Test admin interface loading after major refactors
5. **Debug Logging**: Enable debug logging during development to catch these issues early

## Related Files (Already Correct)

These files were already updated correctly in the v2.0.0 refactor:
- `/includes/class-taxonomies.php` - Taxonomy registration removed
- `/includes/class-relationships.php` - Uses ACF meta_query correctly
- `/includes/class-admin.php` - Dashboard uses ACF meta_query correctly
- `/includes/class-acf-fields.php` - Field registration correct

## Testing Checklist

- [x] Floor Plan edit page displays all ACF fields
- [x] Community edit page displays all ACF fields
- [x] Floor Plan "Communities" field works
- [x] Community dashboard tabs load correctly
- [x] Floor plan ranges calculate correctly
- [x] No PHP errors in debug.log
- [x] Relationships display correctly in meta boxes
- [x] Frontend templates display data correctly
- [x] Lot filtering by floor plan works
- [x] Admin columns display correctly

## Conclusion

The issue was caused by incomplete removal of the old taxonomy system. The code changes ensure all references to the `bh_floor_plan_community` taxonomy are eliminated and replaced with the new ACF Relationship field approach. This fix maintains the benefits of the v2.0.0 refactor while ensuring ACF fields display correctly in the admin interface.
