# ACF Lifecycle Fix - Complete Summary

## Problem: ACF Fields Disappearing in Admin

**Symptom:** When editing a Community post and relating it to a Floor Plan (or vice versa), all ACF fields would disappear from the admin edit screen. Data remained in database and showed on frontend.

## Root Cause: Recursive ACF Loading

**The Fatal Flaw:**
```php
// INSIDE acf/prepare_field hook:
add_filter('acf/prepare_field', array($this, 'display_inherited_values'), 10, 2);

public function display_inherited_values($field) {
    $floor_plan_id = get_field('lot_floor_plan', $post_id); // ← RECURSIVE!
    $value = get_field($floor_plan_field, $floor_plan_id);  // ← RECURSIVE!
}

// INSIDE acf/load_value hook:
add_filter('acf/load_value/name=lot_bedrooms', array($this, 'load_inherited_values'), 10, 3);

public function load_inherited_values($value, $post_id, $field) {
    $floor_plan_id = get_field('lot_floor_plan', $post_id); // ← RECURSIVE!
    return get_field($floor_plan_field, $floor_plan_id);    // ← RECURSIVE!
}
```

**What Happens:**
1. Admin loads Community edit screen
2. ACF starts loading fields → triggers `acf/prepare_field`
3. `display_inherited_values()` runs → calls `get_field()`
4. `get_field()` triggers NEW `acf/load_value` cycle
5. `load_inherited_values()` runs → calls `get_field()` again
6. **INFINITE RECURSION** → ACF abandons rendering
7. Fields disappear ✗

## The Fix: Use Raw Post Meta

### Changed 3 Methods

#### 1. filter_floor_plans_by_community (Line 566)
```php
// BEFORE (BAD):
$community_id = get_field('lot_community', $post_id);

// AFTER (GOOD):
$community_id = get_post_meta($post_id, 'lot_community', true);
```

#### 2. display_inherited_values (Line 622) 
```php
// BEFORE (BAD):
$floor_plan_id = get_field('lot_floor_plan', $post_id);
$floor_plan_value = get_field($floor_plan_field, $floor_plan_id);

// AFTER (GOOD):
$floor_plan_id = get_post_meta($post_id, 'lot_floor_plan', true);
$floor_plan_value = get_post_meta($floor_plan_id, $floor_plan_field, true);
```

#### 3. load_inherited_values (Line 688)
```php
// BEFORE (BAD):
$floor_plan_id = get_field('lot_floor_plan', $post_id);
$floor_plan_value = get_field($floor_plan_field, $floor_plan_id);

// AFTER (GOOD):
$floor_plan_id = get_post_meta($post_id, 'lot_floor_plan', true);
$floor_plan_value = get_post_meta($floor_plan_id, $floor_plan_field, true);
```

### Additional Safety Improvements

1. **Field name validation first** - check if field is in inherited list before any processing
2. **Admin context check** - `is_admin()` to prevent frontend overhead
3. **Post ID validation** - `is_numeric($post_id)` to prevent invalid IDs
4. **Array handling** - handle array values (like features) properly

## Files Modified

- **`includes/class-acf-fields.php`** (+58 lines, -24 lines)
  - Fixed 3 methods to use `get_post_meta()` instead of `get_field()`
  - Added defensive validation checks
  - Added admin context scoping

## Testing Checklist

- [ ] Edit Community → Verify all ACF fields visible
- [ ] Edit Floor Plan, assign to Community → Save
- [ ] Return to Community edit → **Verify ACF fields still display**
- [ ] Edit Lot, assign Floor Plan → **Verify inherited values show**
- [ ] Edit Lot with Community → **Verify floor plan dropdown filters correctly**

## ACF Lifecycle Rules (Critical)

### ✅ DO:
```php
add_filter('acf/load_value/name=lot_bedrooms', function($value, $post_id, $field) {
    $floor_plan_id = get_post_meta($post_id, 'lot_floor_plan', true); // ← GOOD
    $inherited = get_post_meta($floor_plan_id, 'floor_plan_bedrooms', true); // ← GOOD
    return $value ?: $inherited;
}, 10, 3);
```

### ❌ DON'T:
```php
add_filter('acf/load_value/name=lot_bedrooms', function($value, $post_id, $field) {
    $floor_plan_id = get_field('lot_floor_plan', $post_id); // ← BAD (recursive!)
    return get_field('floor_plan_bedrooms', $floor_plan_id); // ← BAD (recursive!)
}, 10, 3);
```

### The Golden Rules

1. **NEVER** call `get_field()` inside:
   - `acf/load_value`
   - `acf/prepare_field`
   - `acf/update_value`
   - `acf/format_value`

2. **ALWAYS** use raw WordPress functions:
   - `get_post_meta($post_id, 'field_name', true)`
   - `update_post_meta($post_id, 'field_name', $value)`

3. **Validate early:**
   - Check field name FIRST
   - Check `is_admin()` if admin-only
   - Validate post ID and post type

## Verification

```bash
# Confirm no get_field() in ACF hooks
grep -n "get_field(" includes/class-acf-fields.php | head -20

# Should only see get_field() in:
# - get_inherited_value() (public utility, NOT a hook)
# - Logging methods (NOT hooks)
# - NEVER in display_inherited_values, load_inherited_values, or filter methods
```

---

**Status:** ✅ FIXED  
**Type:** ACF Lifecycle Misuse  
**Severity:** Critical  
**Solution:** Replaced `get_field()` with `get_post_meta()` in all ACF hooks
