<?php
/**
 * Community Card Component
 * 
 * Shared component for rendering community cards in both shortcode and archive template
 * 
 * @package Burgland_Homes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Burgland_Homes_Community_Card_Component
 */
class Burgland_Homes_Community_Card_Component {

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
	 * Private constructor
	 */
	private function __construct() {}

	/**
	 * Render community card wrapper element with data attributes
	 * 
	 * @param int $community_id Community post ID
	 * @param array $args Additional arguments
	 * @return string HTML for community card wrapper
	 */
	public function render_community_card_wrapper( $community_id, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'class' => 'col-md-6 community-card-wrapper',
			'include_data_attributes' => true,
		) );

		$classes = $args['class'];
		$data_attrs = '';

		if ( $args['include_data_attributes'] ) {
			$latitude = get_post_meta( $community_id, 'community_latitude', true );
			$longitude = get_post_meta( $community_id, 'community_longitude', true );

			$data_attrs .= ' data-lat="' . esc_attr( $latitude ) . '"';
			$data_attrs .= ' data-lng="' . esc_attr( $longitude ) . '"';
			$data_attrs .= ' data-id="' . esc_attr( $community_id ) . '"';
		}

		return sprintf(
			'<div class="%s"%s>',
			esc_attr( $classes ),
			$data_attrs
		);
	}

	/**
	 * Render community card content
	 * 
	 * @param array $community_data Community data array
	 * @return string HTML for community card content
	 */
	public function render_community_card_content( $community_data ) {
		ob_start();
		?>
		<h3 class="card-title h5 mb-2">
			<a href="<?php echo esc_url( $community_data['permalink'] ); ?>" class="text-decoration-none text-dark stretched-link">
				<?php echo esc_html( $community_data['title'] ); ?>
			</a>
		</h3>

		<?php if ( $community_data['city'] && $community_data['state'] ) : ?>
			<p class="card-text text-muted mb-2">
				<i class="bi bi-geo-alt-fill"></i>
				<?php echo esc_html( $community_data['city'] . ', ' . $community_data['state'] ); ?>
			</p>
		<?php endif; ?>

		<?php if ( $community_data['price_range'] ) : ?>
			<p class="card-text text-primary fw-semibold mb-2">
				<?php echo esc_html( $community_data['price_range'] ); ?>
			</p>
		<?php endif; ?>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render complete community card
	 * 
	 * @param array $community_data Community data array
	 * @param array $wrapper_args Arguments for wrapper
	 * @return string HTML for complete community card
	 */
	public function render_community_card( $community_data, $wrapper_args = array() ) {
		$wrapper_args = wp_parse_args( $wrapper_args, array(
			'class' => 'col-md-6 community-card-wrapper',
			'include_data_attributes' => true,
		) );

		$community_id = $community_data['id'];

		// Get status
		$status_terms_post = wp_get_post_terms( $community_id, 'bh_community_status' );
		$status_label = '';
		$status_class = 'primary';

		if ( ! empty( $status_terms_post ) && ! is_wp_error( $status_terms_post ) ) {
			$status_label = $status_terms_post[0]->name;
			$status = $status_terms_post[0]->slug;

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

		$has_thumbnail = ! empty( $community_data['thumbnail'] ) || has_post_thumbnail( $community_id );
		$thumbnail_url = ! empty( $community_data['thumbnail'] ) ? $community_data['thumbnail'] : get_the_post_thumbnail_url( $community_id, 'medium_large' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_args['class'] ); ?>"
			<?php if ( $wrapper_args['include_data_attributes'] ) : ?>
				data-lat="<?php echo esc_attr( $community_data['latitude'] ?? get_post_meta( $community_id, 'community_latitude', true ) ); ?>"
				data-lng="<?php echo esc_attr( $community_data['longitude'] ?? get_post_meta( $community_id, 'community_longitude', true ) ); ?>"
				data-id="<?php echo esc_attr( $community_id ); ?>"
			<?php endif; ?>>
			<div class="card community-card h-100 shadow-sm">
				<?php if ( $has_thumbnail ) : ?>
					<div class="position-relative">
						<a href="<?php echo esc_url( $community_data['permalink'] ); ?>">
							<img src="<?php echo esc_url( $thumbnail_url ); ?>" 
								class="card-img-top community-card-img" 
								alt="<?php echo esc_attr( $community_data['title'] ); ?>">
						</a>
						<?php if ( $status_label ) : ?>
							<span class="badge bg-<?php echo esc_attr( $status_class ); ?> position-absolute top-0 end-0 m-3">
								<?php echo esc_html( $status_label ); ?>
							</span>
						<?php endif; ?>
                        
					</div>
				<?php endif; ?>

				<div class="card-body d-flex flex-column">
					<?php echo $this->render_community_card_content( $community_data ); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get standardized community data
	 * 
	 * @param int $community_id Community post ID
	 * @return array Standardized community data
	 */
	public function get_community_data( $community_id ) {
		// Get custom fields
		$city = get_post_meta( $community_id, 'community_city', true );
		$state = get_post_meta( $community_id, 'community_state', true );
		$price_range = get_post_meta( $community_id, 'community_price_range', true );
		$latitude = get_post_meta( $community_id, 'community_latitude', true );
		$longitude = get_post_meta( $community_id, 'community_longitude', true );

		return array(
			'id'            => $community_id,
			'title'         => get_the_title( $community_id ),
			'excerpt'       => has_excerpt( $community_id ) ? wp_trim_words( get_the_excerpt( $community_id ), 15 ) : get_the_excerpt( $community_id ),
			'permalink'     => get_permalink( $community_id ),
			'city'          => $city,
			'state'         => $state,
			'price_range'   => $price_range,
			'latitude'      => $latitude,
			'longitude'     => $longitude,
			'has_thumbnail' => has_post_thumbnail( $community_id ),
			'thumbnail'     => has_post_thumbnail( $community_id ) ? get_the_post_thumbnail_url( $community_id, 'medium_large' ) : '',
		);
	}
}