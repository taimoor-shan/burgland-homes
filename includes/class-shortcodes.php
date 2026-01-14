<?php
/**
 * Shortcode Management
 *
 * @package Burgland_Homes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Burgland_Homes_Shortcodes
 */
class Burgland_Homes_Shortcodes {

	/**
	 * Single instance
	 */
	private static $instance = null;

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_shortcode( 'featured_communities', array( $this, 'featured_communities_shortcode' ) );
		
		// Enqueue assets for shortcode when needed
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_shortcode_assets' ) );
	}

	/**
	 * Featured communities shortcode
	 */
	public function featured_communities_shortcode( $atts ) {
		// Parse attributes
		$atts = shortcode_atts(
			array(
				'limit' => 6,
				'order' => 'ASC',
				'orderby' => 'title',
			),
			$atts,
			'featured_communities'
		);

		// Query for featured communities
		$query_args = array(
			'post_type'      => 'bh_community',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
			'post__not_in'   => array( get_the_ID() ), // Exclude current post
		);

		$communities = new WP_Query( $query_args );

		ob_start();

		if ( $communities->have_posts() ) {
			// Get communities data for map
			$communities_data = array();
			
			while ( $communities->have_posts() ) {
				$communities->the_post();
				
				// Get custom fields
				$city          = get_post_meta( get_the_ID(), 'community_city', true );
				$state         = get_post_meta( get_the_ID(), 'community_state', true );
				$price_range   = get_post_meta( get_the_ID(), 'community_price_range', true );
				$latitude      = get_post_meta( get_the_ID(), 'community_latitude', true );
				$longitude     = get_post_meta( get_the_ID(), 'community_longitude', true );
				
				// Get floor plan ranges using utility function
				$utilities = Burgland_Homes_Utilities::get_instance();
				$floor_plan_ranges = $utilities->get_floor_plan_ranges( get_the_ID() );
				
				// Use dynamic price range if available, fallback to static field
				$display_price = !empty( $floor_plan_ranges['price']['formatted'] ) 
					? $floor_plan_ranges['price']['formatted'] 
					: $price_range;

				// Get status
				$status_terms_post = wp_get_post_terms( get_the_ID(), 'bh_community_status' );
				$status_label      = '';
				$status_class      = 'primary';

				if ( ! empty( $status_terms_post ) && ! is_wp_error( $status_terms_post ) ) {
					$status_label = $status_terms_post[0]->name;
					$status       = $status_terms_post[0]->slug;

					switch ( $status ) {
						case 'active':
							$status_class = 'success';
							break;
						case 'selling-fast':
							$status_class = 'warning';
							break;
						case 'sold-out':
							$status_class = 'secondary';
							break;
						case 'coming-soon':
							$status_class = 'info';
							break;
					}
				}

				$post_id = get_the_ID();
							
				$communities_data[] = array(
					'id'                => $post_id,
					'title'             => get_the_title( $post_id ),
					'excerpt'           => has_excerpt( $post_id ) ? wp_trim_words( get_the_excerpt( $post_id ), 15 ) : '',
					'permalink'         => get_permalink( $post_id ),
					'city'              => $city,
					'state'             => $state,
					'price_range'       => $display_price,
					'latitude'          => $latitude,
					'longitude'         => $longitude,
					'has_thumbnail'     => has_post_thumbnail( $post_id ),
					'thumbnail'         => has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'medium_large' ) : '',
					'status_label'      => $status_label,
					'status_class'      => $status_class,
					'floor_plan_ranges' => $floor_plan_ranges,
				);
			}
			wp_reset_postdata();

			// Output the HTML structure for the featured communities
			$this->render_featured_communities( $communities_data );

			// Add unique CSS for shortcode to prevent conflicts
			$this->add_shortcode_specific_css();
		} else {
			echo '<div class="alert alert-info text-center" role="alert">';
			echo '<i class="bi bi-info-circle fs-3 d-block mb-2"></i>';
			echo '<p class="mb-0">No communities found.</p>';
			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Add shortcode-specific CSS to prevent conflicts
	 */
	private function add_shortcode_specific_css() {
		?>
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
		<?php
	}

	/**
	 * Render featured communities HTML
	 */
	private function render_featured_communities( $communities_data ) {
		// Enqueue assets for this shortcode
		$this->enqueue_shortcode_assets();
		
		// Generate unique ID for this map instance
		$map_id = 'featured-communities-map-' . uniqid();
		?>
		<div class="featured-communities-shortcode">
			<div class="row g-4">
				<!-- Left Column: Community Cards -->
				<div class="col-lg-6">
					<h2 class="h2 text-uppercase text-primary">Featured Communities</h2>
					<p class="mb-4">Browse our featured communities below:</p>
					<div class="featured-communities-grid">
						
							<?php foreach ( $communities_data as $community ) : ?>
								<div class="community-card-wrapper"
									data-lat="<?php echo esc_attr( $community['latitude'] ); ?>"
									data-lng="<?php echo esc_attr( $community['longitude'] ); ?>"
									data-id="<?php echo esc_attr( $community['id'] ); ?>">
									<div class="card community-card h-100 shadow-sm">
										<?php if ( $community['has_thumbnail'] ) : ?>
											<div class="position-relative">
												<a href="<?php echo esc_url( $community['permalink'] ); ?>">
													<img src="<?php echo esc_url( $community['thumbnail'] ); ?>" 
														class="card-img-top community-card-img" 
														alt="<?php echo esc_attr( $community['title'] ); ?>">
												</a>
												<?php if ( $community['status_label'] ) : ?>
													<span class="badge bg-<?php echo esc_attr( $community['status_class'] ); ?> position-absolute top-0 start-0 m-2">
														<?php echo esc_html( $community['status_label'] ); ?>
													</span>
												<?php endif; ?>
											</div>
										<?php endif; ?>

										<div class="card-body d-flex flex-column">
											<h3 class="card-title h5 mb-2">
												<a href="<?php echo esc_url( $community['permalink'] ); ?>" class="text-decoration-none text-dark stretched-link">
													<?php echo esc_html( $community['title'] ); ?>
												</a>
											</h3>

											<?php if ( $community['city'] && $community['state'] ) : ?>
												<p class="card-text text-muted mb-2">
													<i class="bi bi-geo-alt-fill"></i>
													<?php echo esc_html( $community['city'] . ', ' . $community['state'] ); ?>
												</p>
											<?php endif; ?>

											<?php if ( $community['price_range'] ) : ?>
												<p class="card-text text-primary fw-semibold mb-0">
													<?php echo esc_html( $community['price_range'] ); ?>
												</p>
											<?php endif; ?>
								
											<?php 
											// Display floor plan ranges if available
											$fp_ranges = $community['floor_plan_ranges'];
											if ( $fp_ranges['bedrooms']['min'] !== null || $fp_ranges['bathrooms']['min'] !== null || 
												 $fp_ranges['garage']['min'] !== null || $fp_ranges['square_feet']['min'] !== null ): ?>
												<div class="floor-plan-ranges mt-2">
													<div class="d-flex flex-wrap gap-2 small">
														<?php if ( $fp_ranges['bedrooms']['min'] !== null ): ?>
															<span class="badge bg-light text-dark border">
																<i class="bi bi-house-door me-1"></i>
																<?php if ( $fp_ranges['bedrooms']['min'] == $fp_ranges['bedrooms']['max'] ): ?>
																	<?php echo esc_html( $fp_ranges['bedrooms']['min'] ); ?> bed
																<?php else: ?>
																	<?php echo esc_html( $fp_ranges['bedrooms']['min'] . '-' . $fp_ranges['bedrooms']['max'] ); ?> bed
																<?php endif; ?>
															</span>
														<?php endif; ?>
								
														<?php if ( $fp_ranges['bathrooms']['min'] !== null ): ?>
															<span class="badge bg-light text-dark border">
																<i class="bi bi-droplet me-1"></i>
																<?php if ( $fp_ranges['bathrooms']['min'] == $fp_ranges['bathrooms']['max'] ): ?>
																	<?php echo esc_html( $fp_ranges['bathrooms']['min'] ); ?> bath
																<?php else: ?>
																	<?php echo esc_html( $fp_ranges['bathrooms']['min'] . '-' . $fp_ranges['bathrooms']['max'] ); ?> bath
																<?php endif; ?>
															</span>
														<?php endif; ?>
								
														<?php if ( $fp_ranges['square_feet']['min'] !== null ): ?>
															<span class="badge bg-light text-dark border">
																<i class="bi bi-rulers me-1"></i>
																<?php if ( $fp_ranges['square_feet']['min'] == $fp_ranges['square_feet']['max'] ): ?>
																	<?php echo number_format( esc_html( $fp_ranges['square_feet']['min'] ) ); ?> sqft
																<?php else: ?>
																	<?php echo number_format( esc_html( $fp_ranges['square_feet']['min'] ) ) . '-' . number_format( esc_html( $fp_ranges['square_feet']['max'] ) ); ?> sqft
																<?php endif; ?>
															</span>
														<?php endif; ?>
													</div>
												</div>
											<?php endif; ?>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						
					</div>
				</div>

				<!-- Right Column: Map -->
				<div class="col-lg-6">
					<div class="map-container sticky-top" style="top: 20px;">
						<div id="<?php echo esc_attr( $map_id ); ?>" class="featured-communities-map-canvas" style="height: calc(100vh - 180px); min-height: 600px; background: #e9ecef; border-radius: 8px; border: 2px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
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
					// Wait a bit and try again if jQuery isn't loaded yet
					setTimeout(initFeaturedMapWithJQuery, 100);
					return;
				}
				
				jQuery(function($) {
					var featuredMap = null;
					var featuredMarkers = [];
					var featuredInfoWindows = [];
					var featuredCommunityData = <?php echo json_encode( $communities_data ); ?>;
					var mapContainerId = '<?php echo esc_js( $map_id ); ?>';
					
					function initFeaturedMap() {
						// Check if Google Maps is loaded
						if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
							console.warn('Google Maps API not loaded.');
							return;
						}
						
						var mapElement = document.getElementById(mapContainerId);
						if (!mapElement) return;

						// Calculate center of all communities
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

						// Create map
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

						// Add markers for all communities
						addFeaturedMarkers();
					}
					
					function addFeaturedMarkers() {
						if (!featuredMap) return;

						// Clear existing markers
						clearFeaturedMarkers();

						featuredCommunityData.forEach(function(community) {
							if (!community.latitude || !community.longitude) return;

							// Create marker
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

							// Create info window content
							const infoWindowContent = `
									<div class="map-infowindow">
										<div class="p-3">
											${community.thumbnail ? '<img src="' + community.thumbnail + '" alt="' + community.title + '" style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px;">' : ''}
											<h6 class="mt-2 mb-1">${community.title}</h6>
											${community.city && community.state ? '<p class="mb-1 text-muted small"><i class="bi bi-geo-alt me-1"></i>' + community.city + ', ' + community.state + '</p>' : ''}
											${community.price_range ? '<p class="mb-1 text-primary small"><strong>' + community.price_range + '</strong></p>' : ''}
											${community.status_label ? '<span class="badge bg-${community.status_class} small">' + community.status_label + '</span>' : ''}
											<a href="${community.permalink}" class="btn btn-primary btn-sm mt-2">View Details</a>
										</div>
									</div>
								`;

							// Create info window
							const infoWindow = new google.maps.InfoWindow({
								content: infoWindowContent,
								maxWidth: 300
							});

							// Add click listener to marker
							marker.addListener('click', function() {
								// Close all other info windows
								closeAllFeaturedInfoWindows();
								
								// Open this info window
								infoWindow.open(featuredMap, marker);
							});

							// Store marker and info window
							featuredMarkers.push(marker);
							featuredInfoWindows.push(infoWindow);
						});

						// Adjust map bounds to fit all markers
						if (featuredMarkers.length > 1) {
							const bounds = new google.maps.LatLngBounds();
							featuredMarkers.forEach(function(marker) {
								bounds.extend(marker.getPosition());
							});
							featuredMap.fitBounds(bounds);
							
							// Limit max zoom
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

					// Initialize when document is ready
					if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
						initFeaturedMap();
					} else {
						// Push to global queue
						window.burglandHomesMapsQueue = window.burglandHomesMapsQueue || [];
						window.burglandHomesMapsQueue.push(initFeaturedMap);
					}
				});
			}
			
			// Initialize the map once jQuery is available
			if (typeof jQuery !== 'undefined') {
				jQuery(document).ready(function() {
					initFeaturedMapWithJQuery();
				});
			} else {
				// Wait for jQuery to load
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
		<?php
	}

	/**
	 * Maybe enqueue shortcode assets
	 */
	public function maybe_enqueue_shortcode_assets() {
		global $post;
		
		// Check if the shortcode is used in the current page/post content
		if ( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'featured_communities' ) || 
			strpos( $post->post_content, '[featured_communities' ) !== false ) ) {
			$this->enqueue_shortcode_assets();
		}
	}

	/**
	 * Enqueue assets for shortcode
	 */
	public function enqueue_shortcode_assets() {
		// Enqueue CSS
		wp_enqueue_style(
			'burgland-homes-shortcode',
			BURGLAND_HOMES_PLUGIN_URL . 'assets/css/plugin.css',
			array(),
			BURGLAND_HOMES_VERSION
		);
					
		// Enqueue Bootstrap Icons (if not already loaded by theme)
		wp_enqueue_style(
			'bootstrap-icons',
			'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css',
			array(),
			'1.11.0'
		);
					
		// Add inline CSS to ensure proper styling regardless of theme conflicts
		$inline_css = "
		.featured-communities-shortcode .community-card {
			border: 1px solid #e0e0e0;
			border-radius: 10px;
			overflow: hidden;
			transition: all 0.3s ease;
			cursor: pointer;
			background: #ffffff;
		}
		.featured-communities-shortcode .community-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
			border-color: #0d6efd;
		}
		.featured-communities-shortcode .community-card-img {
			height: 220px;
			object-fit: cover;
			width: 100%;
			transition: transform 0.3s ease;
		}
		.featured-communities-shortcode .community-card:hover .community-card-img {
			transform: scale(1.05);
		}
		.featured-communities-shortcode .community-card .card-body {
			padding: 1.25rem;
		}
		.featured-communities-shortcode .community-card .card-title {
			font-weight: 600;
			margin-bottom: 0.75rem;
			color: #212529;
			line-height: 1.4;
		}
		.featured-communities-shortcode .community-card .card-title a {
			color: inherit;
			text-decoration: none;
		}
		.featured-communities-shortcode .community-card .card-title a:hover {
			color: #0d6efd;
		}
		.featured-communities-shortcode .community-card .card-text {
			font-size: 0.9rem;
			line-height: 1.5;
		}
		.featured-communities-shortcode .community-card .bi-geo-alt-fill {
			color: #6c757d;
			margin-right: 0.25rem;
		}
		.featured-communities-shortcode .community-card .text-primary {
			color: #0d6efd !important;
			font-size: 1.1rem;
		}
		.featured-communities-shortcode .community-card .badge {
			font-size: 0.75rem;
			font-weight: 600;
			padding: 0.4rem 0.8rem;
			letter-spacing: 0.5px;
		}
		";
		// wp_add_inline_style( 'burgland-homes-shortcode', $inline_css );
		
		// Enqueue jQuery if not already enqueued
		wp_enqueue_script( 'jquery' );
					
		// Enqueue Google Maps API with callback for both archive and shortcode
		$google_maps_api_key = apply_filters( 'burgland_homes_google_maps_api_key', '' );
					

		if ( ! empty( $google_maps_api_key ) ) {
			// Check if the script is already enqueued to prevent conflicts
			if ( ! wp_script_is( 'google-maps-featured', 'enqueued' ) ) {
				wp_enqueue_script(
					'google-maps-featured',
					'https://maps.googleapis.com/maps/api/js?key=' . $google_maps_api_key . '&callback=initFeaturedCommunitiesMap',
					array('jquery'),
					null,
					true
				);
				
				// Initialize the queue array and callback function before the script loads
				wp_add_inline_script( 'google-maps-featured', '
					window.burglandHomesMapsQueue = window.burglandHomesMapsQueue || [];
					window.initFeaturedCommunitiesMap = function() {
						window.burglandHomesMapLoaded = true;
						if (window.burglandHomesMapsQueue) {
							window.burglandHomesMapsQueue.forEach(function(initFn) {
								if (typeof initFn === "function") {
									initFn();
								}
							});
							window.burglandHomesMapsQueue = []; 
						}
					};
				', 'before' );
			}
		}
	}
}