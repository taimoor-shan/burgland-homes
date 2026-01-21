# Burgland Homes Plugin

A comprehensive WordPress plugin for managing new development communities, floor plans, and lots/homes.

## Features

### Custom Post Types
- **Communities**: Top-level developments with location, map coordinates, and amenities
- **Floor Plans**: Home designs associated with communities
- **Lots & Homes**: Individual properties linked to communities and floor plans

### Custom Taxonomies
- **Community Status**: Managed via `bh_community_status` taxonomy (Coming Soon, Active, Selling Fast, Sold Out).

## Developer Architecture

The plugin follows a modern, class-based singleton architecture to ensure separation of concerns and maintainability.

- **Singleton Pattern**: Core classes like `Burgland_Homes_Post_Types` and `Burgland_Homes_Taxonomies` use the `get_instance()` pattern to prevent multiple instantiations.
- **Data Provider Layer**: `Burgland_Homes_Data_Provider` centralizes complex database queries, abstraction the data layer from the presentation layer.
- **Template System**: `Burgland_Homes_Template_Loader` handles frontend rendering with support for theme-level overrides. Templates can be found in `templates/` and overridden by placing them in a `burgland-homes/` directory in the active theme.
- **Admin Interface**: A specialized admin dashboard is implemented in `class-admin.php` for at-a-glance statistics and inventory management.

## Data Relationships

The plugin manages complex relationships between neighborhoods, designs, and individual property units:

- **Community ↔ Floor Plan (Many-to-Many)**: Linked via ACF Relationship field (`floor_plans_communities`) on Floor Plans. Floor Plans store the post IDs of their associated Communities. This replaces the previous shadow taxonomy approach.
- **Lot ↔ Community (One-to-Many)**: Every Lot is assigned to a specific Community via the `lot_community` ACF field.
- **Lot ↔ Floor Plan (One-to-Many)**: Lots can be optionally assigned a Floor Plan template via the `lot_floor_plan` ACF field.

## Deletion & Cleanup Lifecycle

With ACF Relationship fields, the deletion lifecycle is simplified:

1. **Community Deletion**: When a community is deleted, it no longer affects Floor Plans or Lots. These posts remain independent, with only their relationship field references becoming invalid. This is preferable to the previous complex taxonomy cleanup.
2. **Floor Plan Deletion**: Deleting a Floor Plan does not delete associated Lots; they retain their assignment but lose template inheritance until reassigned.
3. **Lot Deletion**: Simply removes the lot from the community and floor plan assignments.

### ACF Fields Integration
The plugin uses Advanced Custom Fields (ACF) to manage detailed property information:

#### Community Fields
- Address, City, State, ZIP Code
- Latitude & Longitude (for map integration)
- Total Lots
- Price Range
- Amenities
- Video URL
- Brochure/PDF

#### Floor Plan Fields
- **Communities** (relationship field): Select which communities this floor plan is available in (many-to-many)
- Price, Bedrooms, Bathrooms
- Square Feet, Garage, Stories
- Features
- Image Gallery

#### Lot/Home Fields
- Community (relationship)
- Floor Plan (optional relationship)
- Lot Number, Size, Price
- Premium Lot flag
- Features
- Availability Date
- Image Gallery

### Admin Dashboard
Custom admin interface with:
- Statistics overview
- Quick actions
- Relationship management
- Custom columns showing related data

### Templates
Included single post templates for:
- Communities (with map integration hooks)
- Floor Plans
- Lots/Homes

### Relationships
- Floor Plans belong to Communities
- Lots belong to Communities
- Lots can optionally be assigned a Floor Plan
- Admin meta boxes show relationships

## Installation

1. Upload the `burgland-homes` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Install and activate Advanced Custom Fields (ACF) plugin (required)
4. Go to "Burgland Homes" in the admin menu to start managing properties

## Usage

### Creating a Community
1. Go to Burgland Homes > Communities > Add New
2. Enter community name and description
3. Fill in address and coordinates (for map display)
4. Add amenities, pricing, and other details
5. Assign a community status

### Adding Floor Plans
1. Go to Burgland Homes > Floor Plans > Add New
2. Enter floor plan name and description
3. In the "Communities" field, select which communities this floor plan is available in (you can select multiple)
4. Enter specifications (bedrooms, bathrooms, sq ft, etc.)
5. Add features and images
6. Set the starting price

### Managing Lots
1. Go to Burgland Homes > Lots & Homes > Add New
2. Select the community
3. Optionally assign a floor plan
4. Enter lot number, size, and price
5. Set lot status (Available, Reserved, Sold, etc.)

## Map Integration

The Community single template includes hooks for map integration:
- Display area with coordinates: `#community-map`
- Data attributes: `data-lat`, `data-lng`, `data-address`
- Action hook: `burgland_homes_after_community_map`

Add your preferred map service (Google Maps, Mapbox, Leaflet, etc.) using these hooks.

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Advanced Custom Fields (ACF) plugin (Professional version recommended)

## Directory Structure

- `assets/`: CSS and JS files for admin and frontend.
- `components/`: Modular PHP components for reusable UI elements.
- `includes/`: Core business logic and class definitions.
- `templates/`: Default frontend templates (Community, Floor Plan, Lot).
- `languages/`: Translation files.

## Migration Guide (Floor Plan Communities Taxonomy to ACF Relationship)

**v2.0.0 Breaking Change**: The plugin now uses ACF Relationship fields instead of WordPress taxonomies for Community-Floor Plan relationships.

### Steps to Update:

1. **Backup your database** before updating.
2. **Update the plugin** to the latest version.
3. **Configure ACF Field**: Navigate to any Floor Plan in the admin. Look for the new "Communities" field (ACF Relationship field). This field allows you to select multiple communities for each floor plan.
4. **Reassign Floor Plans to Communities**: Use the new "Communities" field in each Floor Plan's editor to select their associated communities.
5. **Verify your data**: Navigate to a Community in the admin and confirm floor plans display correctly in the "Floor Plans" tab.
6. **Test frontend**: Verify that lot filtering and community pages work as expected.

### Why This Change?

The previous taxonomy-based approach required complex synchronization hooks and cleanup logic. The new ACF Relationship field approach is simpler, more explicit, eliminates ~250 lines of sync/cleanup code, and aligns with WordPress best practices for direct post-to-post relationships.

### Troubleshooting: ACF Fields Not Displaying

If ACF fields disappear from the admin interface after establishing relationships:

1. **Clear all caches**: Disable any caching plugins temporarily and clear browser cache.
2. **Check for PHP errors**: Enable WordPress debug mode (`WP_DEBUG`) and check `wp-content/debug.log`.
3. **Verify taxonomy cleanup**: The old `bh_floor_plan_community` taxonomy should no longer exist. To remove it completely:
   - Go to WordPress Admin > Settings > Permalinks
   - Click "Save Changes" (this flushes rewrite rules)
   - Check if the issue persists
4. **Deactivate conflicting plugins**: Temporarily deactivate other plugins to identify conflicts.
5. **Re-save ACF field groups**: Navigate to Custom Fields in WordPress admin and re-save each field group.
6. **Database cleanup** (advanced): If issues persist, the old taxonomy data may need manual removal:
   ```sql
   -- Remove orphaned term relationships
   DELETE FROM wp_term_relationships WHERE term_taxonomy_id IN (
     SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy = 'bh_floor_plan_community'
   );
   -- Remove taxonomy terms
   DELETE FROM wp_term_taxonomy WHERE taxonomy = 'bh_floor_plan_community';
   -- Remove term entries
   DELETE FROM wp_terms WHERE term_id NOT IN (SELECT term_id FROM wp_term_taxonomy);
   ```
   **⚠️ Always backup before running SQL queries!**

## Changelog

### Version 1.0.0
- Initial release
- Custom Post Types for Communities, Floor Plans, and Lots
- ACF field groups
- Custom admin dashboard
- Relationship management
- Single post templates

## Support
For support, please contact the development team.

## License
GPL v2 or later
