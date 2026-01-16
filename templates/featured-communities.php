<?php
/**
 * Featured Communities Template
 * 
 * Can be overridden by theme at: theme/burgland-homes/featured-communities.php
 * 
 * @var array $communities Communities data array
 * @var string $map_id Unique map ID
 * @var string $google_maps_api_key Google Maps API key
 * @package Burgland_Homes
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue required assets
$template_loader = Burgland_Homes_Template_Loader::get_instance();
do_action('burgland_homes_enqueue_featured_communities_assets');
?>

<div class="featured-communities-shortcode">
    <div class="row g-4">
        <!-- Left Column: Community Cards -->
        <div class="col-lg-6">
            <h2 class="h2 text-uppercase text-primary">Featured Communities</h2>
            <p class="mb-4">Browse our featured communities below:</p>
            <div class="featured-communities-grid row">
                
                    <?php foreach ($communities as $community): ?>
                        <div class="col-lg-6">
                            <?php $template_loader->render_card($community['id']); ?>
                        </div>
                    <?php endforeach; ?>
             
            </div>
        </div>

        <!-- Right Column: Map -->
        <div class="col-lg-6">
            <div class="map-container sticky-top" style="top: 20px;">
                <div id="<?php echo esc_attr($map_id); ?>" class="featured-communities-map-canvas" style="height: calc(100vh - 180px); min-height: 600px; background: #e9ecef; border-radius: 8px; border: 2px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="bi bi-map fs-1 d-block mb-3"></i>
                            <p>Map loading...</p>
                            <small>Please ensure you have added the Google Maps API key</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
function initFeaturedMapWithJQuery() {
    if (typeof jQuery === 'undefined') {
        setTimeout(initFeaturedMapWithJQuery, 100);
        return;
    }
    
    jQuery(function($) {
        var featuredMap = null;
        var featuredMarkers = [];
        var featuredInfoWindows = [];
        var featuredCommunityData = <?php echo json_encode($communities); ?>;
        var mapContainerId = '<?php echo esc_js($map_id); ?>';
        
        function initFeaturedMap() {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.warn('Google Maps API not loaded.');
                return;
            }
            
            var mapElement = document.getElementById(mapContainerId);
            if (!mapElement) return;

            let centerLat = 0;
            let centerLng = 0;
            let validCount = 0;

            featuredCommunityData.forEach(function(community) {
                if (community.latitude && community.longitude) {
                    centerLat += parseFloat(community.latitude);
                    centerLng += parseFloat(community.longitude);
                    validCount++;
                }
            });

            if (validCount === 0) {
                $(mapElement).html(
                    '<div class="d-flex align-items-center justify-content-center h-100 text-center p-4">' +
                        '<div>' +
                            '<i class="bi bi-map fs-1 text-muted d-block mb-3"></i>' +
                            '<p class="text-muted">No communities with location data available.</p>' +
                        '</div>' +
                    '</div>'
                );
                return;
            }

            centerLat = centerLat / validCount;
            centerLng = centerLng / validCount;

            featuredMap = new google.maps.Map(mapElement, {
                center: { lat: centerLat, lng: centerLng },
                zoom: validCount === 1 ? 12 : 8,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }
                ]
            });

            addFeaturedMarkers();
        }
        
        function addFeaturedMarkers() {
            if (!featuredMap) return;

            clearFeaturedMarkers();

            featuredCommunityData.forEach(function(community) {
                if (!community.latitude || !community.longitude) return;

                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(community.latitude), lng: parseFloat(community.longitude) },
                    map: featuredMap,
                    title: community.title,
                    animation: google.maps.Animation.DROP,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
                            '<svg width="32" height="42" xmlns="http://www.w3.org/2000/svg">' +
                                '<path d="M16 0C7.2 0 0 7.2 0 16c0 8.8 16 26 16 26s16-17.2 16-26c0-8.8-7.2-16-16-16z" fill="#0d6efd"/>' +
                                '<circle cx="16" cy="16" r="6" fill="white"/>' +
                            '</svg>'
                        ),
                        scaledSize: new google.maps.Size(32, 42),
                        anchor: new google.maps.Point(16, 42)
                    }
                });

                const infoWindowContent = `
                    <div class="map-infowindow">
                        <div class="p-3">
                            ${community.thumbnail ? '<img src="' + community.thumbnail + '" alt="' + community.title + '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">' : ''}
                            <h6 class="mt-2 mb-1">${community.title}</h6>
                            ${community.city && community.state ? '<p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-1"></i>' + community.city + ', ' + community.state + '</p>' : ''}
                            ${community.price_range ? '<p class="mb-1 text-primary small"><strong>' + community.price_range + '</strong></p>' : ''}
                            ${community.status_label ? '<span class="badge bg-' + community.status_class + ' small">' + community.status_label + '</span>' : ''}
                            <a href="${community.permalink}" class="btn btn-primary btn-sm mt-2">View Details</a>
                        </div>
                    </div>
                `;

                const infoWindow = new google.maps.InfoWindow({
                    content: infoWindowContent,
                    maxWidth: 300
                });

                marker.addListener('click', function() {
                    closeAllFeaturedInfoWindows();
                    infoWindow.open(featuredMap, marker);
                });

                featuredMarkers.push(marker);
                featuredInfoWindows.push(infoWindow);
            });

            if (featuredMarkers.length > 1) {
                const bounds = new google.maps.LatLngBounds();
                featuredMarkers.forEach(function(marker) {
                    bounds.extend(marker.getPosition());
                });
                featuredMap.fitBounds(bounds);
                
                google.maps.event.addListenerOnce(featuredMap, 'bounds_changed', function() {
                    if (featuredMap.getZoom() > 15) {
                        featuredMap.setZoom(15);
                    }
                });
            }
        }

        function clearFeaturedMarkers() {
            featuredMarkers.forEach(function(marker) {
                marker.setMap(null);
            });
            featuredMarkers = [];
            
            closeAllFeaturedInfoWindows();
            featuredInfoWindows = [];
        }

        function closeAllFeaturedInfoWindows() {
            featuredInfoWindows.forEach(function(infoWindow) {
                infoWindow.close();
            });
        }

        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initFeaturedMap();
        } else {
            window.burglandHomesMapsQueue = window.burglandHomesMapsQueue || [];
            window.burglandHomesMapsQueue.push(initFeaturedMap);
        }
    });
}

if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function() {
        initFeaturedMapWithJQuery();
    });
} else {
    var jqCheckInterval = setInterval(function() {
        if (typeof jQuery !== 'undefined') {
            clearInterval(jqCheckInterval);
            jQuery(document).ready(function() {
                initFeaturedMapWithJQuery();
            });
        }
    }, 50);
}
</script>

<style type="text/css">
.featured-communities-shortcode .row.g-4 {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.5rem;
}
.featured-communities-shortcode .col-lg-6 {
    flex: 0 0 auto;
    width: 50%;
    padding: 0 0.5rem;
}
.featured-communities-shortcode .col-md-12 {
    width: 100%;
}
@media (max-width: 991.98px) {
    .featured-communities-shortcode .col-lg-6 {
        width: 100%;
    }
    .featured-communities-shortcode .map-container {
        position: relative !important;
        top: 0 !important;
        margin-top: 2rem;
    }
    .featured-communities-shortcode .featured-communities-map-canvas {
        height: 500px !important;
        min-height: 500px !important;
    }
}
@media (max-width: 767.98px) {
    .featured-communities-shortcode .community-card-wrapper {
        width: 100%;
    }
}
</style>
