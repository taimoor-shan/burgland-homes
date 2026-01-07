<?php
/**
 * Google Maps API Configuration
 * 
 * Add this code to your theme's functions.php file to enable Google Maps on the communities archive page.
 * Replace 'YOUR_GOOGLE_MAPS_API_KEY' with your actual API key.
 * 
 * To get a Google Maps API key:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project or select an existing one
 * 3. Enable the "Maps JavaScript API"
 * 4. Create credentials (API Key)
 * 5. Restrict the API key to your domain for security
 * 
 * @package Burgland_Homes
 */

// Add this to your theme's functions.php file:

add_filter('burgland_homes_google_maps_api_key', function() {
    return 'YOUR_GOOGLE_MAPS_API_KEY';
});
