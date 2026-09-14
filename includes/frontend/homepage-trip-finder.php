<?php
/**
 * The homepage hero's trip-finder picker, answered from the plugin's side.
 *
 * Same shape as includes/frontend/homepage-reasons.php and
 * homepage-departures.php: the theme owns the hero's markup, its layout and
 * the picker control itself, and asks one filter for the two facts it cannot
 * know on its own — which top-level package types exist, and where the
 * results for a visitor's selection are shown. The theme never learns what a
 * package_type term is.
 *
 * The results page is the `[iflynepal_type_explorer]` shortcode
 * (includes/frontend/type-explorer.php) — an ordinary editor-owned Page,
 * chosen once at Packages > Settings rather than assumed at a fixed slug, so
 * the picker degrades to simply not rendering until that choice is made
 * instead of linking to a page that may not exist.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The top-level package types offered in the picker.
 *
 * Every top-level term, unconditionally — unlike the archive's own filter row
 * (iflynepal_archive_filter_terms()), which only offers a button once a
 * package on the page actually answers to it. That rule exists to stop a
 * filter button from emptying the grid it sits above; this picker links out
 * to each type's own archive rather than filtering anything in place, so a
 * type with nothing published yet is still a real category worth offering —
 * the same reasoning the site's main navigation already follows.
 *
 * @since 1.0.0
 *
 * @return array[] Each with 'slug' and 'label', in taxonomy order.
 */
function iflynepal_trip_finder_types() {
	if ( ! taxonomy_exists( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return array();
	}

	$top_level = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'parent'     => 0,
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $top_level ) || ! $top_level ) {
		return array();
	}

	$types = array();

	foreach ( $top_level as $term ) {
		$types[] = array(
			'slug'  => $term->slug,
			'label' => $term->name,
		);
	}

	return $types;
}

/**
 * The URL the picker's form submits to.
 *
 * Read through iflynepal_booking_setting() rather than get_option()
 * directly, the same reasoning as every other setting in this plugin: a row
 * written by anything but the settings form is still sanitized on the way
 * out.
 *
 * Empty until an editor has chosen a page at Packages > Settings, or once
 * that page has been trashed or unpublished out from under the setting — a
 * link to a 404 is worse than no picker at all, and this is what
 * iflynepal_has_trip_finder() gates the whole section on.
 *
 * @since 1.0.0
 *
 * @return string Permalink, or '' when unconfigured.
 */
function iflynepal_trip_finder_url() {
	$page_id = absint( iflynepal_booking_setting( 'trip_finder_page' ) );

	if ( ! $page_id || 'publish' !== get_post_status( $page_id ) ) {
		return '';
	}

	$permalink = get_permalink( $page_id );

	return $permalink ? $permalink : '';
}

/**
 * The trip-length buckets offered in the picker's "I have" field, and the
 * numeric range each one matches a package's Trip duration (days) field
 * against.
 *
 * Fixed, not derived — unlike the type list above, a length bracket is not
 * taxonomy data, so there is nothing to read it from. Taken from the design
 * file's own <select id="days"> verbatim (3–5 / 6–9 / 10–14 / 15+), which is
 * also why there are exactly four: adding a fifth is a design decision, not
 * a data one.
 *
 * 'max' is null for the open-ended top bucket — "15 or more days" has no
 * ceiling to compare against, and iflynepal_explore_duration_matches()
 * (includes/frontend/type-explorer.php) reads a null max as "no upper bound"
 * rather than as "no bucket".
 *
 * @since 1.0.0
 *
 * @return array[] Each with 'key', 'label', 'min' and 'max' (int|null).
 */
function iflynepal_trip_finder_durations() {
	return array(
		array(
			'key'   => '3-5',
			'label' => __( '3–5 days', 'iflynepal' ),
			'min'   => 3,
			'max'   => 5,
		),
		array(
			'key'   => '6-9',
			'label' => __( '6–9 days', 'iflynepal' ),
			'min'   => 6,
			'max'   => 9,
		),
		array(
			'key'   => '10-14',
			'label' => __( '10–14 days', 'iflynepal' ),
			'min'   => 10,
			'max'   => 14,
		),
		array(
			'key'   => '15-plus',
			'label' => __( '15+ days', 'iflynepal' ),
			'min'   => 15,
			'max'   => null,
		),
	);
}

/**
 * Answers the theme's "what does the trip-finder picker need" filter.
 *
 * @since 1.0.0
 *
 * @param array $payload Carries 'types', 'url' and 'durations', all empty by default.
 * @return array Same shape, filled in.
 */
function iflynepal_booking_homepage_trip_finder( $payload ) {
	$payload              = is_array( $payload ) ? $payload : array();
	$payload['types']     = iflynepal_trip_finder_types();
	$payload['url']       = iflynepal_trip_finder_url();
	$payload['durations'] = iflynepal_trip_finder_durations();

	return $payload;
}
add_filter( 'iflynepal_homepage_trip_finder', 'iflynepal_booking_homepage_trip_finder' );
