<?php
/**
 * The homepage's "Upcoming journeys" rail, answered from the plugin's side.
 *
 * The theme owns this section's copy, its layout and its month-chip control —
 * it is a homepage section like any other, edited at Appearance > Customize >
 * Homepage > Upcoming Journeys. It does not, and should not, know what a
 * package is. So, the same shape as the testimonial targets in
 * testimonial-targets.php, it asks one filter for the cards to show and this
 * file is the plugin's answer.
 *
 * A package appears here only once an editor has both ticked "Display on
 * homepage" and typed a "When available?" month on it — see
 * iflynepal_package_shows_on_homepage(). Nothing here enforces availability in
 * the booking sense: the same "no capacity, no inventory" rule as the fixed
 * departure dates and the departure cards applies. The month is informational,
 * and it is what the rail's chips are built from.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The packages marked for the homepage rail, in month order.
 *
 * Fetched rather than counted from a stored total: a package can be unticked
 * or lose its month at any time, and the list has to be right on the next
 * request, not the next save.
 *
 * @since 1.0.0
 *
 * @param int $limit Most packages to return.
 * @return WP_Post[] Packages, soonest month first.
 */
function iflynepal_upcoming_departure_packages( $limit = 12 ) {
	$posts = get_posts(
		array(
			'post_type'        => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => iflynepal_package_meta_key( 'show_homepage' ),
					'value' => '1',
				),
			),
		)
	);

	/*
	 * The tick alone is not enough — a package also needs a real month, or it
	 * has no chip to sit under. Filtered here rather than by a second meta
	 * clause: the tick is a plain equality, but "has a valid month" is a
	 * regexp checkdate-style question a meta_query cannot ask, so it is asked
	 * of iflynepal_package_shows_on_homepage() instead, which is the same rule
	 * the admin box's own preview would use.
	 */
	$posts = array_values(
		array_filter(
			$posts,
			function ( $post ) {
				return iflynepal_package_shows_on_homepage( $post->ID );
			}
		)
	);

	usort(
		$posts,
		function ( $a, $b ) {
			$compare = strcmp( iflynepal_package_available_month( $a->ID ), iflynepal_package_available_month( $b->ID ) );

			return 0 !== $compare ? $compare : strcasecmp( get_the_title( $a ), get_the_title( $b ) );
		}
	);

	if ( $limit > 0 && count( $posts ) > $limit ) {
		$posts = array_slice( $posts, 0, $limit );
	}

	return $posts;
}

/**
 * One package, as the fields the homepage card needs and nothing else.
 *
 * Same rule as every archive card in this plugin (templates/parts/card-package.php):
 * the copy on a card is the copy written for a card, never the package's own
 * body. The month label is the one fact this card carries that the catalogue
 * card does not — it is also the value the chip filter matches against.
 *
 * @since 1.0.0
 *
 * @param WP_Post $package Package.
 * @return array Card fields.
 */
function iflynepal_upcoming_departure_card( $package ) {
	$post_id = $package->ID;
	$image   = has_post_thumbnail( $post_id )
		? get_the_post_thumbnail_url( $post_id, 'large' )
		: '';

	return array(
		'id'          => $post_id,
		'permalink'   => get_permalink( $package ),
		'title'       => get_the_title( $package ),
		'image'       => $image,
		'image_alt'   => get_the_title( $package ),
		'pill'        => iflynepal_package_field( $post_id, 'pill' ),
		'duration'    => iflynepal_package_field( $post_id, 'duration' ),
		'price'       => iflynepal_package_field( $post_id, 'price' ),
		'excerpt'     => iflynepal_package_field( $post_id, 'peek' ),
		'month'       => iflynepal_package_available_month( $post_id ),
		'month_label' => iflynepal_package_available_month_label( $post_id ),
	);
}

/**
 * Answers the theme's "which packages go on the Upcoming Journeys rail" filter.
 *
 * @since 1.0.0
 *
 * @param array[] $cards Cards contributed so far by other plugins, if any.
 * @return array[] Cards, each shaped by iflynepal_upcoming_departure_card().
 */
function iflynepal_booking_upcoming_departures( $cards ) {
	foreach ( iflynepal_upcoming_departure_packages() as $package ) {
		$cards[] = iflynepal_upcoming_departure_card( $package );
	}

	return $cards;
}
add_filter( 'iflynepal_upcoming_departures', 'iflynepal_booking_upcoming_departures' );
