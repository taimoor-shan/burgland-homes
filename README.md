# Burgland Homes Plugin

A comprehensive WordPress plugin for managing new development communities, floor plans, and lots/homes.

## Features

### Custom Post Types
- **Communities**: Top-level developments with location, map coordinates, and amenities
- **Floor Plans**: Home designs associated with communities
- **Lots & Homes**: Individual properties linked to communities and floor plans

### Custom Taxonomies
- **Floor Plan Communities**: System-controlled shadow taxonomy that syncs automatically with Community posts.
- **Community Status**: Managed via `bh_community_status` taxonomy (Coming Soon, Active, Selling Fast, Sold Out).

## Developer Architecture

The plugin follows a modern, class-based singleton architecture to ensure separation of concerns and maintainability.

- **Singleton Pattern**: Core classes like `Burgland_Homes_Post_Types` and `Burgland_Homes_Taxonomies` use the `get_instance()` pattern to prevent multiple instantiations.
- **Data Provider Layer**: `Burgland_Homes_Data_Provider` centralizes complex database queries, abstraction the data layer from the presentation layer.
- **Template System**: `Burgland_Homes_Template_Loader` handles frontend rendering with support for theme-level overrides. Templates can be found in `templates/` and overridden by placing them in a `burgland-homes/` directory in the active theme.
- **Admin Interface**: A specialized admin dashboard is implemented in `class-admin.php` for at-a-glance statistics and inventory management.

## Data Relationships

The plugin manages complex relationships between neighborhoods, designs, and individual property units:

- **Community ↔ Floor Plan (Many-to-Many)**: Linked via the `bh_floor_plan_community` shadow taxonomy. When a Community is published, a corresponding term is created or updated. Floor Plans are then assigned these terms.
- **Lot ↔ Community (One-to-Many)**: Every Lot is assigned to a specific Community via the `lot_community` post metadata.
- **Lot ↔ Floor Plan (One-to-Many)**: Lots can be optionally assigned a Floor Plan template via the `lot_floor_plan` metadata.

## Deletion & Cleanup Lifecycle

To maintain database integrity and prevent orphaned data, the plugin implements a strict cleanup lifecycle:

1. **Community Deletion**: When a community is trashed or deleted, the plugin:
   - Identifies and removes the linked taxonomy term from all Floor Plans.
   - Clears post and relationship caches for all affected objects.
   - Removes orphaned records from the term relationship database tables.
   - Sets any associated Lots to `draft` status and flags them as orphaned to prevent broken frontend links.
2. **Permanent Cleanup**: The `cleanup_post_data_permanent` hook ensures that when a post is permanently deleted, all its metadata is purged and its URL slug is fully released for reuse.

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
- Community (relationship)
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
2. Select the community this floor plan belongs to
3. Enter specifications (bedrooms, bathrooms, sq ft, etc.)
4. Add features and images
5. Set the starting price

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
