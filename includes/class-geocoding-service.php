<?php
/**
 * Geocoding Service Class
 * 
 * Handles address-to-coordinates conversion using Google Geocoding API
 * Caches results in post meta to minimize API calls
 * 
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Burgland_Homes_Geocoding_Service
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct()
    {
        // Private constructor for singleton
    }

    /**
     * Get Google Maps API key from filter hook
     * 
     * @return string API key
     */
    private function get_api_key()
    {
        $api_key = apply_filters('burgland_homes_google_maps_api_key', '');
        return trim($api_key);
    }

    /**
     * Geocode an address to get latitude and longitude
     * 
     * @param string $address Street address
     * @param string $city City
     * @param string $state State
     * @param string $zip ZIP code
     * @return array|false Array with 'lat' and 'lng' keys, or false on failure
     */
    public function geocode_address($address, $city, $state, $zip)
    {
        $api_key = $this->get_api_key();
        
        if (empty($api_key)) {
            error_log('Burgland Homes Geocoding: API key not configured');
            return false;
        }

        // Build full address string
        $full_address = $this->build_address_string($address, $city, $state, $zip);
        
        if (empty($full_address)) {
            error_log('Burgland Homes Geocoding: Empty address provided');
            return false;
        }

        // Make API request
        $url = add_query_arg(
            array(
                'address' => urlencode($full_address),
                'key' => $api_key,
            ),
            'https://maps.googleapis.com/maps/api/geocode/json'
        );

        $response = wp_remote_get($url, array(
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            error_log('Burgland Homes Geocoding Error: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['status']) || $data['status'] !== 'OK') {
            $status = isset($data['status']) ? $data['status'] : 'UNKNOWN';
            error_log('Burgland Homes Geocoding: API returned status ' . $status . ' for address: ' . $full_address);
            return false;
        }

        if (empty($data['results'][0]['geometry']['location'])) {
            error_log('Burgland Homes Geocoding: No location data in API response');
            return false;
        }

        $location = $data['results'][0]['geometry']['location'];
        
        return array(
            'lat' => floatval($location['lat']),
            'lng' => floatval($location['lng']),
        );
    }

    /**
     * Build address string from components
     * 
     * @param string $address Street address
     * @param string $city City
     * @param string $state State
     * @param string $zip ZIP code
     * @return string Full address string
     */
    private function build_address_string($address, $city, $state, $zip)
    {
        $parts = array_filter(array(
            $address,
            $city,
            $state,
            $zip,
        ));

        return implode(', ', $parts);
    }

    /**
     * Get cached coordinates for a post
     * 
     * @param int $post_id Post ID
     * @return array|false Array with 'lat' and 'lng' keys, or false if not cached
     */
    public function get_cached_coordinates($post_id)
    {
        $lat = get_post_meta($post_id, '_geocoded_latitude', true);
        $lng = get_post_meta($post_id, '_geocoded_longitude', true);

        if (empty($lat) || empty($lng)) {
            return false;
        }

        return array(
            'lat' => floatval($lat),
            'lng' => floatval($lng),
        );
    }

    /**
     * Save coordinates to post meta
     * 
     * @param int $post_id Post ID
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool True on success
     */
    public function save_coordinates($post_id, $lat, $lng)
    {
        update_post_meta($post_id, '_geocoded_latitude', $lat);
        update_post_meta($post_id, '_geocoded_longitude', $lng);
        
        return true;
    }

    /**
     * Clear cached coordinates for a post
     * 
     * @param int $post_id Post ID
     */
    public function clear_coordinates($post_id)
    {
        delete_post_meta($post_id, '_geocoded_latitude');
        delete_post_meta($post_id, '_geocoded_longitude');
    }

    /**
     * Geocode and cache community address
     * 
     * @param int $community_id Community post ID
     * @return bool True if successfully geocoded and cached
     */
    public function geocode_community($community_id)
    {
        $address = get_post_meta($community_id, 'community_address', true);
        $city = get_post_meta($community_id, 'community_city', true);
        $state = get_post_meta($community_id, 'community_state', true);
        $zip = get_post_meta($community_id, 'community_zip', true);

        $coordinates = $this->geocode_address($address, $city, $state, $zip);

        if ($coordinates === false) {
            return false;
        }

        $this->save_coordinates($community_id, $coordinates['lat'], $coordinates['lng']);
        
        return true;
    }

    /**
     * Geocode and cache lot address
     * 
     * @param int $lot_id Lot post ID
     * @return bool True if successfully geocoded and cached
     */
    public function geocode_lot($lot_id)
    {
        $lot_address = get_post_meta($lot_id, 'lot_address', true);
        
        // Get city, state, zip from parent community
        $community_id = get_post_meta($lot_id, 'lot_community', true);
        $city = '';
        $state = '';
        $zip = '';
        
        if ($community_id) {
            $city = get_post_meta($community_id, 'community_city', true);
            $state = get_post_meta($community_id, 'community_state', true);
            $zip = get_post_meta($community_id, 'community_zip', true);
        }

        // If lot has a specific address, geocode it
        if (!empty($lot_address)) {
            $coordinates = $this->geocode_address($lot_address, $city, $state, $zip);

            if ($coordinates !== false) {
                $this->save_coordinates($lot_id, $coordinates['lat'], $coordinates['lng']);
                return true;
            }
        }

        // If no specific lot address or geocoding failed, clear coordinates
        // (lot will inherit from community when displayed)
        $this->clear_coordinates($lot_id);
        
        return false;
    }
}
