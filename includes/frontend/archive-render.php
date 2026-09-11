<?php
/**
 * Rendering helpers shared by the archive templates.
 *
 * The templates ask questions here rather than reaching into the schema
 * themselves, so the rule for "is this section worth drawing" lives in one place
 * and reads the same everywhere.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The archive sections for a term, in the order the design lays them out.
 *
 * The schema is keyed in this order already, but the template asks for the list
 * rather than assuming it: a section added to the schema should appear on the
 * page without anybody remembering to edit a template.
 *
 * Which sections a term gets is the schema's question, not the template's — see
 * iflynepal_package_type_archive_sections_for_term(). A category renders the
 * hero and the grid; a top-level type renders all ten.
 *
 * @since 1.0.0
 *
 * @param WP_Term|int|null $term Term or term ID.
 * @return string[] Section keys.
 */
function iflynepal_booking_archive_sections( $term = null ) {
	return array_keys( iflynepal_package_type_archive_sections_for_term( $term ) );
}

/**
 * Whether any of the named fields on a term hold content.
 *
 * A section with nothing in it is left out entirely rather than drawn as an
 * empty band, which is what makes a half-written archive presentable.
 *
 * @since 1.0.0
 *
 * @param int      $term_id Package type term.
 * @param string[] $keys    Schema keys to test.
 * @return bool True when at least one is filled.
 */
function iflynepal_archive_has_any( $term_id, $keys ) {
	foreach ( (array) $keys as $key ) {
		if ( '' !== trim( iflynepal_archive_field( $term_id, $key ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Echoes a heading field with its inline emphasis intact.
 *
 * `rich` fields are stored already run through the plugin's kses filter, but
 * escaping happens at output as a rule, not at storage, so it runs again here.
 * Anything else and the <em> an editor wrote would print as text.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $key     Schema key.
 * @return void
 */
function iflynepal_archive_the_heading( $term_id, $key ) {
	echo iflynepal_booking_kses_text( iflynepal_archive_field( $term_id, $key ) ); // kses filtered.
}

/**
 * Echoes the standard eyebrow / heading / lead block of a section.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $prefix  Section prefix, e.g. 'benefits'.
 * @param string $classes Extra classes for the wrapper.
 * @return void
 */
function iflynepal_archive_the_head( $term_id, $prefix, $classes = '' ) {
	$eyebrow = iflynepal_archive_field( $term_id, $prefix . '_eyebrow' );
	$heading = iflynepal_archive_field( $term_id, $prefix . '_heading' );
	$lead    = iflynepal_archive_field( $term_id, $prefix . '_lead' );

	if ( '' === $eyebrow && '' === $heading && '' === $lead ) {
		return;
	}

	printf( '<div class="iflynepal-section-head %s">', esc_attr( $classes ) );

	if ( '' !== $eyebrow ) {
		printf( '<span class="iflynepal-eyebrow">%s</span>', esc_html( $eyebrow ) );
	}

	if ( '' !== $heading ) {
		echo '<h2>';
		iflynepal_archive_the_heading( $term_id, $prefix . '_heading' );
		echo '</h2>';
	}

	if ( '' !== $lead ) {
		printf( '<p class="iflynepal-lead">%s</p>', esc_html( $lead ) );
	}

	echo '</div>';
}

/**
 * Echoes a stored image field as a responsive <img>.
 *
 * Stored as an attachment ID, never a URL, so the image keeps working when the
 * site moves domain and WordPress can pick the right size for the viewport.
 *
 * @since 1.0.0
 *
 * @param int    $term_id  Package type term.
 * @param string $key      Schema key.
 * @param string $size     Registered image size.
 * @param array  $attr     Extra attributes for wp_get_attachment_image().
 * @return void
 */
function iflynepal_archive_the_image( $term_id, $key, $size = 'large', $attr = array() ) {
	$attachment_id = absint( iflynepal_archive_field( $term_id, $key ) );

	if ( ! $attachment_id ) {
		return;
	}

	echo wp_get_attachment_image( $attachment_id, $size, false, $attr ); // core-escaped markup.
}

/**
 * The arrow that trails a primary button label.
 *
 * Character for character the theme's own arrow — the same viewBox, the same
 * path, the same two classes. That is the point: .iflynepal-ico sizes and
 * strokes it from the button's own colour, and .iflynepal-ico-arr is what the
 * theme's `.iflynepal-button:hover` rule nudges 4px to the right. Drawing a
 * different arrow here would be a second one to keep in step, and it would not
 * move on hover.
 *
 * Inline rather than an <img>: it inherits currentColor, costs no request, and
 * is six lines of path data.
 *
 * aria-hidden with no focusable attribute — the arrow is decoration, the button
 * label already says where the link goes, and IE-era focusable="false" is what
 * the theme carries for the same reason.
 *
 * @since 1.0.0
 *
 * @return string SVG markup. Static, contains no input, safe to echo.
 */
function iflynepal_booking_arrow_icon() {
	return '<svg class="iflynepal-ico iflynepal-ico-arr" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h15M13 6l6 6-6 6"/></svg>';
}

/**
 * Echoes a pair of buttons, skipping either one that has no label or no link.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $prefix  Section prefix, e.g. 'hero'.
 * @param string $wrapper Class for the wrapping div. The theme's hero styles the
 *                        buttons through iflynepal-hero__actions, so a hero has
 *                        to pass that rather than the generic class.
 * @return void
 */
function iflynepal_archive_the_actions( $term_id, $prefix, $wrapper = 'iflynepal-actions' ) {
	$buttons = array(
		array(
			'label' => iflynepal_archive_field( $term_id, $prefix . '_cta_label' ),
			'url'   => iflynepal_archive_field( $term_id, $prefix . '_cta_url' ),
			'class' => 'iflynepal-button iflynepal-button--dark',
			'arrow' => true,
		),
		array(
			'label' => iflynepal_archive_field( $term_id, $prefix . '_cta_alt_label' ),
			'url'   => iflynepal_archive_field( $term_id, $prefix . '_cta_alt_url' ),
			'class' => 'iflynepal-button iflynepal-button--light',
			'arrow' => false,
		),
	);

	$drawable = array();

	foreach ( $buttons as $button ) {
		if ( '' !== $button['label'] && '' !== $button['url'] ) {
			$drawable[] = $button;
		}
	}

	if ( empty( $drawable ) ) {
		return;
	}

	printf( '<div class="%s">', esc_attr( $wrapper ) );

	foreach ( $drawable as $button ) {
		printf(
			'<a class="%1$s" href="%2$s">%3$s%4$s</a>',
			esc_attr( $button['class'] ),
			esc_url( $button['url'] ),
			esc_html( $button['label'] ),
			$button['arrow'] ? iflynepal_booking_arrow_icon() : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input.
		);
	}

	echo '</div>';
}

/**
 * The packages filed under a term, for the card grid.
 *
 * Child terms are included, so /retreat-nepal/ shows everything in the branch
 * and not only the packages filed against the parent by hand.
 *
 * @since 1.0.0
 *
 * @param int $term_id Package type term.
 * @param int $limit   Posts to fetch.
 * @return WP_Post[] Packages.
 */
function iflynepal_archive_packages( $term_id, $limit = 24 ) {
	return get_posts(
		array(
			'post_type'        => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => (int) $limit,
			'suppress_filters' => false,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => IFLYNEPAL_PACKAGE_TAXONOMY,
					'field'            => 'term_id',
					'terms'            => (int) $term_id,
					'include_children' => true,
				),
			),
		)
	);
}

/**
 * The child terms of a package type, for the card filter row.
 *
 * @since 1.0.0
 *
 * @param int $term_id Package type term.
 * @return WP_Term[] Child terms holding at least one package.
 */
function iflynepal_archive_child_terms( $term_id ) {
	/*
	 * hide_empty is false here deliberately: its count tallies only the posts
	 * filed *directly* on a term, so a category whose packages all sit in its
	 * own sub-categories counts as empty — while the archive goes on showing
	 * its cards, because the card query walks the whole branch.
	 *
	 * Whether a category has anything in it is answered instead by
	 * iflynepal_archive_filter_terms(), from the packages actually on the page.
	 */
	$terms = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'parent'     => (int) $term_id,
			'hide_empty' => false,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * The child categories worth offering as filters.
 *
 * A filter button that matches nothing is a dead control: it empties the grid
 * and gives the visitor no way of knowing that is all there was. So a category
 * earns its button only once its branch holds at least one of the packages on
 * the page.
 *
 * "Its branch" is the point. A category can hold nothing directly and still be
 * full — its packages may all sit in sub-categories beneath it. The question is
 * answered from the cards already rendered rather than by counting: each
 * package's filter slugs carry its ancestors, so a package filed three levels
 * down contributes every category above it. The card loop has already primed
 * the term cache, so this costs nothing extra.
 *
 * @since 1.0.0
 *
 * @param int       $term_id  Package type term whose children are offered.
 * @param WP_Post[] $packages The packages rendered on this archive.
 * @return WP_Term[] Child terms holding at least one of those packages.
 */
function iflynepal_archive_filter_terms( $term_id, $packages ) {
	$children = iflynepal_archive_child_terms( $term_id );

	if ( empty( $children ) || empty( $packages ) ) {
		return array();
	}

	$in_use = array();

	foreach ( $packages as $package ) {
		foreach ( iflynepal_package_filter_slugs( $package->ID ) as $slug ) {
			$in_use[ $slug ] = true;
		}
	}

	$filters = array();

	foreach ( $children as $child ) {
		if ( isset( $in_use[ $child->slug ] ) ) {
			$filters[] = $child;
		}
	}

	return $filters;
}

/**
 * Every package type slug a package should answer to when filtered.
 *
 * A package filed under "Retreat > Mindfulness > Yoga" has to appear under the
 * Mindfulness filter as well as its own, because the archive's own query pulls
 * packages from the whole branch — get_the_terms() alone would list only `yoga`
 * and the card would be hidden by a filter that is showing its siblings.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return string[] Term slugs, each term's ancestors included, no duplicates.
 */
function iflynepal_package_filter_slugs( $post_id ) {
	$terms = get_the_terms( $post_id, IFLYNEPAL_PACKAGE_TAXONOMY );
	$slugs = array();

	if ( ! is_array( $terms ) ) {
		return $slugs;
	}

	foreach ( $terms as $term ) {
		$slugs[ $term->slug ] = $term->slug;

		foreach ( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, IFLYNEPAL_PACKAGE_TAXONOMY );

			if ( $ancestor instanceof WP_Term ) {
				$slugs[ $ancestor->slug ] = $ancestor->slug;
			}
		}
	}

	return array_values( $slugs );
}

/**
 * A date as the archive prints it.
 *
 * @since 1.0.0
 *
 * @param string $date Y-m-d date.
 * @return string Localised date, or the input when it will not parse.
 */
function iflynepal_booking_format_date( $date ) {
	$timestamp = strtotime( $date . ' 12:00:00' );

	if ( ! $timestamp ) {
		return $date;
	}

	return wp_date( get_option( 'date_format' ), $timestamp );
}
