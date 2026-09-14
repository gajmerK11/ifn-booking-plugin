<?php
/**
 * The homepage's "A few good reasons" card grid, answered from the plugin's
 * side.
 *
 * Same shape as includes/frontend/homepage-departures.php and
 * testimonial-targets.php: the theme owns this section's copy, its layout and
 * its filter-button control, and asks one filter for everything it needs to
 * draw them. It never learns what a package type is — the filter buttons
 * themselves come back from here too, not just the cards, because deciding
 * which taxonomy terms exist and which of them are worth a button is exactly
 * the kind of question a theme should not have to answer.
 *
 * A package appears here only once an editor has ticked "Show in 'A few good
 * reasons'" on it — see iflynepal_package_detail_fields(). The section's own
 * copy says "a curated mix ... not an endless catalogue", so the query is
 * deliberately not "every published package": that would be the catalogue
 * the copy is promising visitors this is not.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Most cards to show on the grid.
 *
 * A visitor is not browsing here — the section exists precisely because the
 * whole catalogue is somewhere else — so the grid is capped rather than
 * paged. Nine keeps the design's three-column grid to three even rows,
 * the same cap the archive's own card-repeaters use elsewhere in this plugin.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_REASONS_CARD_MAX = 9;

/**
 * The packages ticked for the "A few good reasons" grid.
 *
 * @since 1.0.0
 *
 * @param int $limit Most packages to return.
 * @return WP_Post[] Packages, newest first.
 */
function iflynepal_reasons_packages( $limit = IFLYNEPAL_REASONS_CARD_MAX ) {
	return get_posts(
		array(
			'post_type'        => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => (int) $limit,
			'suppress_filters' => false,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => iflynepal_package_meta_key( 'show_reasons' ),
					'value' => '1',
				),
			),
		)
	);
}

/**
 * One package, as the fields the reasons card needs.
 *
 * Identical shape to the catalogue's own card (templates/parts/card-package.php)
 * and the departures card built from the same facts — only the copy written
 * for a card is ever shown here, never the package's own body. 'categories'
 * is what the filter buttons match against: every package_type term the
 * package answers to, ancestors included, so a package filed three levels
 * down still answers to its top-level type's button.
 *
 * @since 1.0.0
 *
 * @param WP_Post $package Package.
 * @return array Card fields.
 */
function iflynepal_reasons_card( $package ) {
	$post_id = $package->ID;

	return array(
		'id'          => $post_id,
		'permalink'   => get_permalink( $package ),
		'title'       => get_the_title( $package ),
		'image'       => has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'large' ) : '',
		'image_alt'   => get_the_title( $package ),
		'pill'        => iflynepal_package_field( $post_id, 'pill' ),
		'duration'    => iflynepal_package_field( $post_id, 'duration' ),
		'suitability' => iflynepal_package_field( $post_id, 'suitability' ),
		'price'       => iflynepal_package_field( $post_id, 'price' ),
		'excerpt'     => iflynepal_package_field( $post_id, 'peek' ),
		'categories'  => iflynepal_package_filter_slugs( $post_id ),
	);
}

/**
 * The top-level package types worth offering as filter buttons.
 *
 * A filter button that matches nothing is a dead control, so a type earns its
 * button only once one of the fetched cards actually answers to it — the same
 * "answered from what is already on the page" rule
 * iflynepal_archive_filter_terms() uses for the catalogue's own category row,
 * applied one taxonomy level higher: there the buttons are a type's child
 * categories, here they are the types themselves.
 *
 * @since 1.0.0
 *
 * @param array[] $cards Cards already built by iflynepal_reasons_card().
 * @return array[] Each with 'slug' and 'label', in term order.
 */
function iflynepal_reasons_filter_terms( $cards ) {
	if ( ! $cards || ! taxonomy_exists( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
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

	$in_use = array();

	foreach ( $cards as $card ) {
		foreach ( (array) $card['categories'] as $slug ) {
			$in_use[ $slug ] = true;
		}
	}

	$filters = array();

	foreach ( $top_level as $term ) {
		if ( isset( $in_use[ $term->slug ] ) ) {
			$filters[] = array(
				'slug'  => $term->slug,
				'label' => $term->name,
			);
		}
	}

	return $filters;
}

/**
 * Answers the theme's "what goes in the 'A few good reasons' grid" filter.
 *
 * @since 1.0.0
 *
 * @param array $payload Carries 'cards' and 'filters', both empty by default.
 * @return array Same shape, filled in.
 */
function iflynepal_booking_homepage_reasons( $payload ) {
	$payload  = is_array( $payload ) ? $payload : array();
	$packages = iflynepal_reasons_packages();
	$cards    = array();

	foreach ( $packages as $package ) {
		$cards[] = iflynepal_reasons_card( $package );
	}

	$payload['cards']   = array_merge( isset( $payload['cards'] ) ? $payload['cards'] : array(), $cards );
	$payload['filters'] = array_merge( isset( $payload['filters'] ) ? $payload['filters'] : array(), iflynepal_reasons_filter_terms( $cards ) );

	return $payload;
}
add_filter( 'iflynepal_homepage_reasons', 'iflynepal_booking_homepage_reasons' );
