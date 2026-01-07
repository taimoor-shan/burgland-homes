# Burgland Homes Plugin

A comprehensive WordPress plugin for managing new development communities, floor plans, and lots/homes.

## Features

### Custom Post Types
- **Communities**: Top-level developments with location, map coordinates, and amenities
- **Floor Plans**: Home designs associated with communities
- **Lots & Homes**: Individual properties linked to communities and floor plans

### Custom Taxonomies
- **Lot Status**: Available, Reserved, Sold, Pending
- **Community Status**: Coming Soon, Active, Selling Fast, Sold Out

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
- Advanced Custom Fields (ACF) plugin

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
