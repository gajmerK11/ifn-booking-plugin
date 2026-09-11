<?php
/**
 * The upcoming departures rail.
 *
 * The dates are a query over the packages in this branch, not fields: each
 * package carries its own fixed departure dates and the rail is the next few of
 * them, soonest first. Only the section's heading is editable.
 *
 * This is informational. A date here is a line of text next to a link — there is
 * no capacity behind it, nothing is reserved by clicking it, and nothing closes
 * when it passes except its own display.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'term'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_term = isset( $args['term'] ) ? $args['term'] : null;

if ( ! $iflynepal_term instanceof WP_Term ) {
	return;
}

$iflynepal_id = $iflynepal_term->term_id;

/*
 * Opt-in, for the same reason as the testimonials band: the dates come from the
 * packages, so this section would appear the moment anybody typed a departure
 * date into any package, on an archive nobody had written a word of. The
 * editor turns it on by writing its heading.
 */
if ( ! iflynepal_archive_has_any( $iflynepal_id, array( 'departures_eyebrow', 'departures_heading', 'departures_lead' ) ) ) {
	return;
}

$iflynepal_departures = array();

foreach ( iflynepal_archive_packages( $iflynepal_id, 50 ) as $iflynepal_package ) {
	foreach ( iflynepal_package_upcoming_departures( $iflynepal_package->ID, 3 ) as $iflynepal_date ) {
		$iflynepal_departures[] = array(
			'date'    => $iflynepal_date,
			'package' => $iflynepal_package,
		);
	}
}

if ( empty( $iflynepal_departures ) ) {
	return;
}

usort(
	$iflynepal_departures,
	static function ( $a, $b ) {
		return strcmp( $a['date'], $b['date'] );
	}
);

$iflynepal_departures = array_slice( $iflynepal_departures, 0, 12 );
?>

<section class="iflynepal-section iflynepal-section--mist iflynepal-departures" id="iflynepal-departures">
	<div class="iflynepal-container">
		<div class="iflynepal-departures__head">
			<?php iflynepal_archive_the_head( $iflynepal_id, 'departures' ); ?>

			<div class="iflynepal-rail-nav">
				<button class="iflynepal-rail-btn" type="button" data-rail="prev" aria-label="<?php esc_attr_e( 'Previous departures', 'iflynepal' ); ?>">&larr;</button>
				<button class="iflynepal-rail-btn" type="button" data-rail="next" aria-label="<?php esc_attr_e( 'Next departures', 'iflynepal' ); ?>">&rarr;</button>
			</div>
		</div>

		<div class="iflynepal-departures__rail">
			<?php foreach ( $iflynepal_departures as $iflynepal_departure ) : ?>
				<a class="iflynepal-departure" href="<?php echo esc_url( get_permalink( $iflynepal_departure['package'] ) ); ?>">
					<?php
					if ( has_post_thumbnail( $iflynepal_departure['package'] ) ) {
						echo get_the_post_thumbnail( // core-escaped markup.
							$iflynepal_departure['package'],
							'medium_large',
							array(
								'loading' => 'lazy',
								'alt'     => '',
							)
						);
					}
					?>
					<span class="iflynepal-pill"><?php echo esc_html( iflynepal_booking_format_date( $iflynepal_departure['date'] ) ); ?></span>
					<span class="iflynepal-departure__title"><?php echo esc_html( get_the_title( $iflynepal_departure['package'] ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
