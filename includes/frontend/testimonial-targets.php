<?php
/**
 * Package type archives, offered as places a testimonial can be shown.
 *
 * The theme owns the Testimonials post type and its "Display On Page" field. It
 * knows about pages and nothing else, which is correct: a theme cannot be
 * expected to know that a plugin has given the site five more archives. So it
 * exposes one filter naming the places a review may be assigned to, and this is
 * the plugin's answer to it.
 *
 * The theme resolves *which* target the current request stands for on its own,
 * from the queried object, so there is no second filter for that and no list of
 * plugin templates inside the theme. This file only says which targets exist.
 *
 * A target is `term:{term ID}`, not a slug: the first segment of a catalogue URL
 * is a term slug an editor may change at any time (§5.3a), and an assignment
 * that came apart when somebody renamed an archive would be a silent one.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the package type archives to the testimonial's list of places.
 *
 * Only the archives that draw the band are offered. A category — any term with a
 * parent — gets the hero and the grid and nothing else (§5.3b), so offering one
 * here would let an editor make an assignment that could never render and give
 * them nothing on screen to say why. The question is asked of the schema rather
 * than answered again here, so the two cannot disagree.
 *
 * @since 1.0.0
 *
 * @param array[] $groups Target groups, keyed by group key.
 * @return array[] Filtered groups.
 */
function iflynepal_booking_testimonial_targets( $groups ) {
	if ( ! taxonomy_exists( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return $groups;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return $groups;
	}

	$options = array();

	foreach ( $terms as $term ) {
		$sections = iflynepal_package_type_archive_sections_for_term( $term );

		if ( ! array_key_exists( 'testimonials', $sections ) ) {
			continue;
		}

		$options[ 'term:' . (int) $term->term_id ] = sprintf(
			/* translators: %s: the package type, as "Retreat". */
			__( '%s archive', 'iflynepal' ),
			$term->name
		);
	}

	if ( ! $options ) {
		return $groups;
	}

	$groups['iflynepal_package_archives'] = array(
		'label'   => __( 'Package archives', 'iflynepal' ),
		'options' => $options,
	);

	return $groups;
}
add_filter( 'iflynepal_testimonial_display_targets', 'iflynepal_booking_testimonial_targets' );
