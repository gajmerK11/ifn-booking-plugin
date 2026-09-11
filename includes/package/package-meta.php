<?php
/**
 * The facts a single package carries.
 *
 * The card in the design is not a title and an excerpt. It shows a short type
 * pill, a duration, a place or a suitability note, a from-price, and a line of
 * copy that slides up on hover — and the single package page repeats all of it.
 * None of that is post content, so none of it can be typed into the editor: it
 * is structured data about the package and it lives in post meta.
 *
 * Same schema-first shape as the archive content model: one declarative list
 * that the meta box renders from, the save routine sanitizes from, and the
 * templates read from, so the three cannot drift apart.
 *
 * Fixed departure dates are here too. They are informational only — a line of
 * upcoming dates next to the booking button. There is deliberately no capacity,
 * slot or availability logic attached to them anywhere in this plugin, and none
 * should be added: overbooking is handled by the office, offline. Dates are
 * stored as one ISO date per line and sorted, filtered and counted at render
 * time, never stored derived.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prefix every package detail meta key carries.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_META_PREFIX = '_iflynepal_package_';

/**
 * The facts stored against a package.
 *
 * @since 1.0.0
 *
 * @return array[] Field definitions keyed by schema key.
 */
function iflynepal_package_detail_fields() {
	$fields = array(
		'pill'          => array(
			'label' => __( 'Card label', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'The small badge on the card image, e.g. "Yoga" or "Ayurveda".', 'iflynepal' ),
		),
		'duration'      => array(
			'label' => __( 'Duration', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'Written as it should read, e.g. "3–30 days".', 'iflynepal' ),
		),
		'suitability'   => array(
			'label' => __( 'Place or suitability', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'The second fact on the card, e.g. "Kathmandu" or "Beginner friendly".', 'iflynepal' ),
		),
		'price'         => array(
			'label' => __( 'Price', 'iflynepal' ),
			'type'  => 'text',
			'help'  => __( 'Written exactly as it should read, e.g. "From US$425". Shown as typed — no currency conversion happens here.', 'iflynepal' ),
		),
		'peek'          => array(
			'label' => __( 'Hover summary', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'One or two lines revealed over the card image. Left empty, the card shows no summary — the package\'s own text is never used here.', 'iflynepal' ),
		),
		'highlights'    => array(
			'label' => __( 'Highlights', 'iflynepal' ),
			'type'  => 'lines',
			'help'  => __( 'One per line, listed on the single package page.', 'iflynepal' ),
		),
		'buffer_notice' => array(
			'label' => __( 'Confirmation notice', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'The static line beside the booking button, e.g. "Trekking and volunteering bookings are confirmed within 5–6 days." Informational only — nothing is delayed or enforced.', 'iflynepal' ),
		),
		'departures'    => array(
			'label' => __( 'Fixed departure dates', 'iflynepal' ),
			'type'  => 'dates',
			'help'  => __( 'One date per line, as YYYY-MM-DD. Past dates are dropped automatically. Leave empty for a package that runs year-round.', 'iflynepal' ),
		),
		'booking'       => array(
			'label' => __( 'Booking button shortcode', 'iflynepal' ),
			'type'  => 'textarea',
			'help'  => __( 'The PayPal buy-now shortcode for this package. Pasted from the gateway plugin and rendered as-is.', 'iflynepal' ),
		),
	);

	/**
	 * Filters the per-package detail fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array[] $fields Field definitions keyed by schema key.
	 */
	return apply_filters( 'iflynepal_package_detail_fields', $fields );
}

/**
 * The full meta key for a package detail field.
 *
 * @since 1.0.0
 *
 * @param string $key Schema key, e.g. 'price'.
 * @return string Meta key.
 */
function iflynepal_package_meta_key( $key ) {
	return IFLYNEPAL_PACKAGE_META_PREFIX . $key;
}

/**
 * One stored package detail.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Package.
 * @param string $key     Schema key.
 * @return string Stored value, empty string when unset.
 */
function iflynepal_package_field( $post_id, $key ) {
	return (string) get_post_meta( (int) $post_id, iflynepal_package_meta_key( $key ), true );
}

/**
 * A `lines` package field as a list.
 *
 * @since 1.0.0
 *
 * @param int    $post_id Package.
 * @param string $key     Schema key.
 * @return string[] Non-empty lines, in order.
 */
function iflynepal_package_field_lines( $post_id, $key ) {
	$lines = preg_split( '/\R/', iflynepal_package_field( $post_id, $key ) );

	return array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
}

/**
 * Sanitizes one package detail according to its type.
 *
 * @since 1.0.0
 *
 * @param mixed  $value Raw submitted value, already unslashed.
 * @param string $type  Field type.
 * @return string Value as it should be stored.
 */
function iflynepal_package_sanitize_value( $value, $type ) {
	switch ( $type ) {
		case 'dates':
			return implode( "\n", iflynepal_package_sanitize_dates( $value ) );

		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $value );

		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Keeps only the lines of a dates field that are real calendar dates.
 *
 * Checked with checkdate() rather than a regex, because 2026-02-30 matches any
 * reasonable pattern and is still not a day. Duplicates are dropped and the
 * result is sorted, so the stored value is always in the order it reads in.
 *
 * @since 1.0.0
 *
 * @param string $value Raw textarea value.
 * @return string[] Valid Y-m-d dates, unique and ascending.
 */
function iflynepal_package_sanitize_dates( $value ) {
	$dates = array();

	foreach ( preg_split( '/\R/', (string) $value ) as $line ) {
		$line = trim( $line );

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $line, $parts ) ) {
			continue;
		}

		if ( ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			continue;
		}

		$dates[ $line ] = $line;
	}

	sort( $dates );

	return $dates;
}

/**
 * Upcoming fixed departures for a package.
 *
 * Derived on every read: which dates are still ahead depends on today, so a
 * stored "next departure" would be wrong by the following morning. Uses the
 * site's timezone, not the server's, or a date rolls over at the wrong hour.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @param int $limit   Most dates to return. 0 for all of them.
 * @return string[] Y-m-d dates from today onwards, ascending.
 */
function iflynepal_package_upcoming_departures( $post_id, $limit = 0 ) {
	$today = current_time( 'Y-m-d' );
	$dates = array();

	foreach ( iflynepal_package_field_lines( $post_id, 'departures' ) as $date ) {
		if ( $date >= $today ) {
			$dates[] = $date;
		}
	}

	sort( $dates );

	return $limit > 0 ? array_slice( $dates, 0, (int) $limit ) : $dates;
}
