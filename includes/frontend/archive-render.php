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
 * The block carries the reveal hook, which is what the design puts `data-anim`
 * on: a section heading rises into place as it is scrolled to, and the rest of
 * the section follows it. See assets/js/archive/reveal.js.
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

	printf( '<div class="%s" data-iflynepal-anim>', esc_attr( trim( 'iflynepal-section-head ' . $classes ) ) );

	if ( '' !== $eyebrow ) {
		printf( '<span class="iflynepal-eyebrow">%s</span>', esc_html( $eyebrow ) );
	}

	if ( '' !== $heading ) {
		echo '<h2>';
		iflynepal_archive_the_heading( $term_id, $prefix . '_heading' );
		echo '</h2>';
	}

	if ( '' !== $lead ) {
		/*
		 * The lead is a plain textarea, so a line break an editor typed is the
		 * only way they can ask for one — and the design does ask, under the
		 * plans heading. Escaped first and marked up second: nl2br only ever
		 * adds <br> to a string that already has no markup left in it.
		 */
		printf(
			'<p class="iflynepal-lead">%s</p>',
			nl2br( esc_html( $lead ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above; nl2br only adds <br>.
		);
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
 * The sliders mark on the collapsed "Filters" button.
 *
 * Only ever seen on a narrow viewport, where the rail is shut behind its
 * summary and the word "Filters" is standing in for a panel the visitor cannot
 * see. The mark is what makes that button read as a control at a glance rather
 * than as another heading in a column of them.
 *
 * Drawn to the same 24-unit box and the same open, single-weight stroke as
 * iflynepal_booking_arrow_icon(), so the two sit in one family; the stroke
 * itself comes from .iflynepal-ico in catalogue.css rather than from an
 * attribute here, which is what lets it inherit currentColor with the rest.
 *
 * @since 1.0.0
 *
 * @return string SVG markup. Static, contains no input, safe to echo.
 */
function iflynepal_booking_filter_icon() {
	return '<svg class="iflynepal-ico iflynepal-ico-filter" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>';
}

/**
 * Echoes a pair of buttons, skipping either one that has no label or no link.
 *
 * The two styles are a parameter because the design does not use the same pair
 * twice: the hero is a solid navy action beside a ghost outline on the
 * photograph, and the closing card is a white action beside the same ghost. The
 * arrow follows the primary action only, and only where the design draws one.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $prefix  Section prefix, e.g. 'hero'.
 * @param string $wrapper Class for the wrapping div. The theme's hero styles the
 *                        buttons through iflynepal-hero__actions, so a hero has
 *                        to pass that rather than the generic class.
 * @param array  $styles  Optional. 'primary' and 'secondary' button modifiers,
 *                        and 'arrow' for whether the primary carries one.
 * @return void
 */
function iflynepal_archive_the_actions( $term_id, $prefix, $wrapper = 'iflynepal-actions', $styles = array() ) {
	$styles = wp_parse_args(
		$styles,
		array(
			'primary'   => 'iflynepal-button--dark',
			'secondary' => 'iflynepal-button--ghost',
			'arrow'     => true,
		)
	);

	$buttons = array(
		array(
			'label' => iflynepal_archive_field( $term_id, $prefix . '_cta_label' ),
			'url'   => iflynepal_archive_field( $term_id, $prefix . '_cta_url' ),
			'class' => 'iflynepal-button ' . $styles['primary'],
			'arrow' => (bool) $styles['arrow'],
		),
		array(
			'label' => iflynepal_archive_field( $term_id, $prefix . '_cta_alt_label' ),
			'url'   => iflynepal_archive_field( $term_id, $prefix . '_cta_alt_url' ),
			'class' => 'iflynepal-button ' . $styles['secondary'],
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
			'<a class="%1$s" %2$s%3$s>%4$s%5$s</a>',
			esc_attr( $button['class'] ),
			iflynepal_booking_anchor_attr( $button['url'] ),
			iflynepal_booking_external_link_attr( $button['url'] ),
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
 * @param int $limit   Posts to fetch. -1 for every package in the branch.
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
 * The most cards a type archive's grid shows at once.
 *
 * Six, which is two full rows of the design's three-column grid at every width
 * it has three columns — a cap that lands mid-row reads as the grid having run
 * out rather than having been trimmed. Client-directed.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_VISIBLE_CARDS = 6;

/**
 * How many cards a term's grid is worth fetching.
 *
 * A **category** renders its whole branch. It is the end of the road — there is
 * nowhere further to send a visitor who wants the rest — and it is the page the
 * type archive's own "view all" button points at, so it has to be able to
 * honour that promise. Client-directed.
 *
 * A **type** archive keeps its cap. Its grid is a sampler with a way out of it,
 * and shipping the whole catalogue to every visitor so that JavaScript can hide
 * most of it is the thing the cap exists to prevent.
 *
 * @since 1.0.0
 *
 * @param WP_Term $term The term being viewed.
 * @return int Posts to fetch, or -1 for all of them.
 */
function iflynepal_archive_package_limit( $term ) {
	$limit = ( $term instanceof WP_Term && $term->parent > 0 ) ? -1 : 24;

	/**
	 * Filters how many packages an archive's grid fetches.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $limit Posts to fetch, -1 for all.
	 * @param WP_Term $term  The term being viewed.
	 */
	return (int) apply_filters( 'iflynepal_archive_package_limit', $limit, $term );
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
 * A package's numeric price, or null when it has none worth bucketing.
 *
 * Price_amount is free text — the details box asks for digits only, but
 * nothing enforces that — so this reads out the numeral in it rather than
 * trusting the stored string to already be one.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return float|null Price, or null when the field holds no usable number.
 */
function iflynepal_archive_package_price( $post_id ) {
	$price = iflynepal_parse_price_text( iflynepal_package_field( $post_id, 'price' ) );

	if ( null !== $price ) {
		return $price;
	}

	return iflynepal_parse_price_text( iflynepal_package_field( $post_id, 'price_amount' ) );
}

/**
 * Reads a written price into a number.
 *
 * 🔴 The Package Card's own Price is the source, with the numeric Price on the
 * Dates and price panel as the fallback — the same order, and for the same
 * reason, as iflynepal_package_duration_range(). Measured on this catalogue:
 * 16 of 18 published packages carry the card text and 4 carry the number, so
 * reading the number alone left the Budget facet describing a fifth of the
 * catalogue and silently dropping the rest.
 *
 * Where the two disagree the card wins, because the card is what the visitor is
 * reading. One package here says "From US$425" on its card and 320 in the
 * field; a visitor filtering by budget is choosing against the former.
 *
 * Takes the FIRST number in the text. Card prices are written as a floor —
 * "From $800" — so the first number is the one the package can be had for,
 * which is what a budget filter is being asked about.
 *
 * Currency symbols, codes and thousands separators are dropped, so "From
 * US$3,200" reads as 3200. Text with no number in it ("On request", "Free")
 * returns null and the package is offered under no budget rather than under
 * one nobody wrote down.
 *
 * @since 1.0.0
 *
 * @param string $text A price as typed.
 * @return float|null The number, or null when the text holds no usable one.
 */
function iflynepal_parse_price_text( $text ) {
	$text = trim( wp_strip_all_tags( (string) $text ) );

	if ( '' === $text ) {
		return null;
	}

	/*
	 * The separator has to go before the number is read, not after: "3,200"
	 * left alone matches as "3" and a package priced in thousands would be
	 * filed in the cheapest bucket on the page.
	 */
	$text = str_replace( array( ',', ' ' ), '', $text );

	if ( ! preg_match( '/(\d+(?:\.\d+)?)/', $text, $m ) ) {
		return null;
	}

	$price = (float) $m[1];

	return $price > 0 ? $price : null;
}

/**
 * A package's trip length in days, or null when it has none.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return int|null Days, or null when the field is empty or unusable.
 */
function iflynepal_archive_package_days( $post_id ) {
	$days = absint( iflynepal_package_field( $post_id, 'duration_days' ) );

	return $days > 0 ? $days : null;
}

/**
 * A package's trip length, read from the Duration field on its Package Card.
 *
 * 🔴 The card's free-text Duration is the source, not the numeric Trip duration
 * (days) on the Dates and price panel, and the difference is not academic: on
 * this catalogue 16 of 18 published packages carry the card text and only 4
 * carry the number. Reading the number meant the Duration facet described a
 * fifth of the catalogue and silently dropped the rest — a package saying
 * "3 Weeks" on its own card was not on offer under any duration at all, which
 * is what prompted this change.
 *
 * The number is still read, as a fallback, for a package that has one and no
 * card text. It is a second place the same fact can live; where they disagree
 * the card wins, because the card is what the visitor is reading.
 *
 * Everything comes back in days. Weeks and months are converted here so that
 * one comparison serves every wording, and nothing downstream has to carry a
 * unit around: 7 and 30 are the multipliers, a month being a calendar month
 * only to somebody who is not selling a trip.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array{min:int,max:int}|null Days, inclusive. Null when the package
 *                                     says nothing usable about its length.
 */
function iflynepal_package_duration_range( $post_id ) {
	$parsed = iflynepal_parse_duration_text( iflynepal_package_field( $post_id, 'duration' ) );

	if ( null !== $parsed ) {
		return $parsed;
	}

	$days = absint( iflynepal_package_field( $post_id, 'duration_days' ) );

	return $days > 0 ? array(
		'min' => $days,
		'max' => $days,
	) : null;
}

/**
 * Reads a written trip length into a span of days.
 *
 * Handles what editors actually type, which this catalogue shows to be a number
 * or two and a unit: "15 days", "3 Weeks", "2 to 4 weeks", "3-30 DAYS",
 * "1 to 8 days". Case is ignored, and the separator may be the word "to", a
 * hyphen, an en or em dash, a slash or "and".
 *
 * 🔴 A range is kept as a range rather than collapsed to one number, and that
 * decision is the whole behaviour of the filter. A trek advertised "7 to 24
 * days" genuinely can be walked in ten; collapsing it to its minimum would hide
 * it from a visitor filtering for ten to fourteen days, and collapsing it to
 * its maximum would hide it from everyone else. Kept as a span, it is offered
 * under every length it can actually be done in — see
 * iflynepal_archive_duration_keys().
 *
 * Returns null rather than guessing when there is no number in the text at all.
 * "Flexible" and "Year-round" are things an editor may reasonably type, and a
 * package that has not said how long it is should be absent from the facet, not
 * filed under a length nobody claimed.
 *
 * @since 1.0.0
 *
 * @param string $text The card's Duration field, as typed.
 * @return array{min:int,max:int}|null Days, inclusive.
 */
function iflynepal_parse_duration_text( $text ) {
	$text = strtolower( trim( wp_strip_all_tags( (string) $text ) ) );

	if ( '' === $text ) {
		return null;
	}

	/*
	 * The unit is decided by the whole string, not by a word next to a number:
	 * "2 to 4 weeks" names its unit once, at the end, and a per-number search
	 * would read the 2 as bare and the 4 as weeks.
	 */
	$multiplier = 1;

	if ( preg_match( '/\bmonths?\b/', $text ) ) {
		$multiplier = 30;
	} elseif ( preg_match( '/\bweeks?\b/', $text ) ) {
		$multiplier = 7;
	}

	if ( preg_match( '/(\d+)\s*(?:to|through|and|[-\x{2010}-\x{2015}\/])\s*(\d+)/u', $text, $m ) ) {
		$low  = (int) $m[1];
		$high = (int) $m[2];
	} elseif ( preg_match( '/(\d+)/', $text, $m ) ) {
		$low  = (int) $m[1];
		$high = $low;
	} else {
		return null;
	}

	/* "10-3 days" is a typo, not an empty range; read it as the span it names. */
	if ( $high < $low ) {
		list( $low, $high ) = array( $high, $low );
	}

	$min = $low * $multiplier;
	$max = $high * $multiplier;

	return $min > 0 ? array(
		'min' => $min,
		'max' => $max,
	) : null;
}

/**
 * Whether a span of days reaches into a bucket at all.
 *
 * Overlap, not containment: a bucket is on offer to a package if any length the
 * package can be booked at falls inside it.
 *
 * @since 1.0.0
 *
 * @param array $range  Days, with 'min' and 'max'.
 * @param array $bucket A bucket with 'min' and 'max' (max null for open-ended).
 * @return bool
 */
function iflynepal_duration_range_overlaps( $range, $bucket ) {
	if ( $range['max'] < $bucket['min'] ) {
		return false;
	}

	return null === $bucket['max'] || $range['min'] <= $bucket['max'];
}

/**
 * Which of a set of min/max buckets a number falls into.
 *
 * Shared by the duration and budget facets: both are a sorted list of
 * {min, max} ranges with the last one open-ended, and a value belongs to the
 * first range that holds it.
 *
 * @since 1.0.0
 *
 * @param float   $value   The number to place.
 * @param array[] $buckets Rows with 'key', 'min', 'max' (max may be null).
 * @return string Matching bucket key, or '' when none holds it.
 */
function iflynepal_archive_bucket_match( $value, $buckets ) {
	foreach ( $buckets as $bucket ) {
		if ( $value < $bucket['min'] ) {
			continue;
		}

		if ( null === $bucket['max'] || $value <= $bucket['max'] ) {
			return $bucket['key'];
		}
	}

	return '';
}

/**
 * The trip-length buckets worth offering on this archive.
 *
 * Reuses the same length options a visitor is offered on the homepage picker
 * (Packages > Settings > Trip finder length options) — duration_days is the
 * field both read, so there is one idea of "duration" on the site instead of
 * two competing sets of ranges. Only buckets that actually match one of the
 * packages on this page are offered, the same restraint
 * iflynepal_archive_filter_terms() already applies to categories: a range
 * nothing here fits is a control that can only ever empty the grid.
 *
 * @since 1.0.0
 *
 * @param WP_Post[] $packages The packages rendered on this archive.
 * @return array[] Matching buckets, each with 'key' and 'label'.
 */
function iflynepal_archive_duration_terms( $packages ) {
	$buckets = iflynepal_trip_finder_durations();
	$in_use  = array();

	foreach ( $packages as $package ) {
		foreach ( iflynepal_archive_duration_keys( $package->ID, $buckets ) as $key ) {
			$in_use[ $key ] = true;
		}
	}

	return array_values(
		array_filter(
			$buckets,
			static function ( $bucket ) use ( $in_use ) {
				return isset( $in_use[ $bucket['key'] ] );
			}
		)
	);
}

/**
 * The facet narrowing a request arrives already asking for.
 *
 * The type archive's "view all" button links to a category archive and carries
 * whatever Duration and Budget the visitor had chosen, so the narrowing
 * survives the jump instead of being silently dropped on arrival. This reads
 * those back.
 *
 * `?days=` is the same key the hero picker and /explore/ already use, so one
 * spelling of "how long" serves every page that asks. `?budget=` is this
 * archive's own, because budget buckets are computed per page from the prices
 * actually on it.
 *
 * 🔴 A value is honoured only if the facet being rendered actually offers it,
 * and that check is the whole safety of this. The query string is
 * visitor-controlled, and budget keys in particular are derived from a price
 * spread — a bucket that exists on the Retreat archive may not exist on the
 * category the visitor lands on. An unknown value is dropped rather than
 * pre-selecting nothing-at-all and leaving the grid empty with no control
 * showing why. Same rule /explore/ applies to an unrecognised type slug.
 *
 * @since 1.0.0
 *
 * @param string  $facet   'duration' or 'budget'.
 * @param array[] $options The options that facet is drawing, each with a 'key'.
 * @return string The option key to start pressed, or '' for "All".
 */
function iflynepal_archive_requested_filter( $facet, $options ) {
	$param = 'duration' === $facet ? 'days' : $facet;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A GET-only narrowing of a public listing; nothing is written and nothing is acted on but a display filter.
	if ( ! isset( $_GET[ $param ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- As above.
	$requested = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );

	foreach ( $options as $option ) {
		if ( $option['key'] === $requested ) {
			return $requested;
		}
	}

	return '';
}

/**
 * What each duration unit is called above its block of options.
 *
 * Kept out of the facet builder so the two strings sit beside each other rather
 * than inside a conditional, and so a third unit — months, if a volunteering
 * placement ever runs that long — is one entry here.
 *
 * @since 1.0.0
 *
 * @return array<string,string> Unit key => sub-heading.
 */
function iflynepal_archive_duration_unit_names() {
	return array(
		'days'  => __( 'Days', 'iflynepal' ),
		'weeks' => __( 'Weeks', 'iflynepal' ),
	);
}

/**
 * Every duration bucket a package can honestly be offered under.
 *
 * A package's length is a span, not a point — "7 to 24 days" is the norm on
 * this catalogue rather than the exception — so this returns every bucket that
 * span reaches into, and the card carries all of them. That is why
 * data-duration is a list and why filters.js matches it by inclusion, exactly
 * as it already matches data-categories, rather than by equality.
 *
 * A package that says nothing usable about its length gets no keys and appears
 * under no duration. It is still in the grid under "All"; it is simply not
 * claimed to be a length nobody wrote down.
 *
 * @since 1.0.0
 *
 * @param int          $post_id Package.
 * @param array[]|null $buckets Optional. The buckets to place it in, to save
 *                              re-reading the setting inside a loop.
 * @return string[] Bucket keys, in the buckets' own order.
 */
function iflynepal_archive_duration_keys( $post_id, $buckets = null ) {
	$range = iflynepal_package_duration_range( $post_id );

	if ( null === $range ) {
		return array();
	}

	$buckets = null === $buckets ? iflynepal_trip_finder_durations() : $buckets;
	$keys    = array();

	foreach ( $buckets as $bucket ) {
		if ( iflynepal_duration_range_overlaps( $range, $bucket ) ) {
			$keys[] = $bucket['key'];
		}
	}

	return $keys;
}

/**
 * A step size that divides a span into a handful of round-number buckets.
 *
 * The "nice numbers" rounding used for chart axis ticks: rather than chopping
 * a price range into equal but odd-looking thirds, it steps in 1s, 2s or 5s of
 * whatever magnitude the range calls for, so a $940 spread reads as buckets of
 * $250 or $500 rather than $313.33.
 *
 * @since 1.0.0
 *
 * @param float $rough_step The step a plain equal split would produce.
 * @return float A round step at or near that size.
 */
function iflynepal_archive_nice_step( $rough_step ) {
	if ( $rough_step <= 0 ) {
		return 1.0;
	}

	$magnitude = pow( 10, floor( log10( $rough_step ) ) );
	$residual  = $rough_step / $magnitude;

	if ( $residual < 1.5 ) {
		$step = 1;
	} elseif ( $residual < 3 ) {
		$step = 2;
	} elseif ( $residual < 7 ) {
		$step = 5;
	} else {
		$step = 10;
	}

	return $step * $magnitude;
}

/**
 * The price buckets worth offering on this archive.
 *
 * Built from the prices actually on the page rather than fixed thresholds: a
 * mindfulness retreat and an Everest trek have nothing in common to price
 * against, so a hardcoded "$500 / $1,000 / $2,000" ladder would sit wrong on
 * one of them by design. Two distinct prices are needed before a Budget
 * filter is worth showing at all — one price has nothing to divide it from.
 *
 * Stepping evenly from the lowest price to the highest can still land a
 * bucket no package actually falls in — five packages clustered at the low
 * and high ends of a wide range easily skip the middle step entirely. Those
 * empty buckets are dropped before this returns, the same restraint
 * iflynepal_archive_filter_terms() and iflynepal_archive_duration_terms()
 * already apply: a button that can only ever empty the grid is not offered.
 *
 * @since 1.0.0
 *
 * @param WP_Post[] $packages The packages rendered on this archive.
 * @return array[] Buckets, each with 'key', 'label', 'min', 'max' (max null on the last).
 */
function iflynepal_archive_budget_terms( $packages ) {
	$prices   = array();
	$currency = '';

	foreach ( $packages as $package ) {
		$price = iflynepal_archive_package_price( $package->ID );

		if ( null === $price ) {
			continue;
		}

		$prices[] = $price;

		if ( '' === $currency ) {
			$currency = trim( iflynepal_package_field( $package->ID, 'price_currency' ) );
		}
	}

	$prices = array_unique( $prices );

	if ( count( $prices ) < 2 ) {
		return array();
	}

	$min  = min( $prices );
	$max  = max( $prices );
	$step = iflynepal_archive_nice_step( ( $max - $min ) / 3 );
	$edge = floor( $min / $step ) * $step;

	$buckets = array();

	while ( $edge < $max ) {
		$next    = $edge + $step;
		$is_last = $next >= $max;
		$top     = $is_last ? null : $next;

		$buckets[] = array(
			'key'   => $edge . '-' . ( $is_last ? 'plus' : $next ),
			'label' => iflynepal_archive_budget_label( $edge, $top, $currency ),
			'min'   => $edge,
			'max'   => $top,
		);

		$edge = $next;
	}

	$in_use = array();

	foreach ( $prices as $price ) {
		$in_use[ iflynepal_archive_bucket_match( $price, $buckets ) ] = true;
	}

	return array_values(
		array_filter(
			$buckets,
			static function ( $bucket ) use ( $in_use ) {
				return isset( $in_use[ $bucket['key'] ] );
			}
		)
	);
}

/**
 * One budget bucket's label, e.g. "USD 500–1,000" or "USD 2,000+".
 *
 * Plain numbers and a dash or a plus sign carry nothing to translate, so this
 * builds the string directly rather than routing it through a translator
 * comment that would have nothing to explain.
 *
 * @since 1.0.0
 *
 * @param float  $min      Lower edge.
 * @param float  $max      Upper edge, or null when open-ended.
 * @param string $currency Currency code from the packages priced, e.g. "USD".
 * @return string
 */
function iflynepal_archive_budget_label( $min, $max, $currency ) {
	$prefix = '' === $currency ? '' : $currency . ' ';
	$low    = number_format_i18n( $min );

	if ( null === $max ) {
		return $prefix . $low . '+';
	}

	return $prefix . $low . '–' . number_format_i18n( $max );
}

/**
 * A package's budget-filter key, from the same buckets the filter row uses.
 *
 * @since 1.0.0
 *
 * @param int     $post_id Package.
 * @param array[] $buckets Buckets from iflynepal_archive_budget_terms().
 * @return string Bucket key, or '' when the package has no usable price.
 */
function iflynepal_archive_budget_key( $post_id, $buckets ) {
	$price = iflynepal_archive_package_price( $post_id );

	return null === $price ? '' : iflynepal_archive_bucket_match( $price, $buckets );
}

/**
 * Every facet this archive can offer, normalised into one shape.
 *
 * The three facets are built by three different rules — Activity from the
 * child terms that hold packages, Duration from the site-wide trip-length
 * buckets a package here matches, Budget from the spread of prices this set
 * actually has — but the rail draws all three identically, so the differences
 * stop here rather than being repeated three times in the template.
 *
 * Every option carries the number of packages on this page that answer to it.
 * The count is not decoration: it is the same restraint
 * iflynepal_archive_filter_terms() already applies, made visible. A facet only
 * offers what the grid can actually produce, and the count says how much of it
 * before the visitor spends a click finding out.
 *
 * Counts are of the whole rendered set, not of what the other facets are
 * currently narrowed to — nothing here knows what is selected, because nothing
 * is selected until the page is in a browser. assets/js/archive/filters.js
 * recomputes them against the live selection on every press; these are the
 * numbers a visitor with JavaScript off sees, and they are correct for the
 * unfiltered grid that visitor is looking at.
 *
 * @since 1.0.0
 *
 * @param int       $term_id  Package type term being viewed.
 * @param WP_Post[] $packages The packages rendered on this archive.
 * 'style' is how the rail draws the facet: 'pills' for Activity, which keeps
 * the shape it had when the filters were a row above the grid, and 'list' for
 * the two that are ranges rather than names. Every facet carries a count per
 * option whichever style it is drawn in — only the list style prints one, but
 * the number is what decides whether an option is offered at all, so keeping
 * the shape uniform makes moving a facet between the two styles one word here
 * rather than a second branch everywhere downstream.
 *
 * @return array[] Facets, each with 'facet', 'label', 'aria', 'style', 'total'
 *                 and 'options' (each option 'key', 'label', 'count'). A facet
 *                 with nothing to divide is left out.
 */
function iflynepal_archive_facets( $term_id, $packages ) {
	$children         = iflynepal_archive_filter_terms( $term_id, $packages );
	$durations        = iflynepal_archive_duration_terms( $packages );
	$budgets          = iflynepal_archive_budget_terms( $packages );
	$duration_buckets = iflynepal_trip_finder_durations();

	$counts = array(
		'categories' => array(),
		'duration'   => array(),
		'budget'     => array(),
	);

	foreach ( $packages as $package ) {
		/*
		 * A package contributes to every ancestor category at once, which is
		 * what makes a parent category's count agree with the cards its filter
		 * actually shows — the same reason the card carries its whole ancestor
		 * chain in data-categories rather than only its own term.
		 */
		foreach ( iflynepal_package_filter_slugs( $package->ID ) as $slug ) {
			$counts['categories'][ $slug ] = isset( $counts['categories'][ $slug ] ) ? $counts['categories'][ $slug ] + 1 : 1;
		}

		foreach ( iflynepal_archive_duration_keys( $package->ID, $duration_buckets ) as $duration_key ) {
			$counts['duration'][ $duration_key ] = isset( $counts['duration'][ $duration_key ] ) ? $counts['duration'][ $duration_key ] + 1 : 1;
		}

		$budget_key = iflynepal_archive_budget_key( $package->ID, $budgets );

		if ( '' !== $budget_key ) {
			$counts['budget'][ $budget_key ] = isset( $counts['budget'][ $budget_key ] ) ? $counts['budget'][ $budget_key ] + 1 : 1;
		}
	}

	$total  = count( $packages );
	$facets = array();

	if ( ! empty( $children ) ) {
		$options = array();

		foreach ( $children as $child ) {
			$options[] = array(
				'key'   => $child->slug,
				'label' => $child->name,
				'count' => isset( $counts['categories'][ $child->slug ] ) ? $counts['categories'][ $child->slug ] : 0,
			);
		}

		$facets[] = array(
			'facet'   => 'categories',
			'label'   => __( 'Activity', 'iflynepal' ),
			'aria'    => __( 'Filter by activity', 'iflynepal' ),
			'style'   => 'pills',
			'total'   => $total,
			'options' => $options,
		);
	}

	if ( ! empty( $durations ) ) {
		$options = array();
		$units   = array();

		foreach ( $durations as $bucket ) {
			$unit           = isset( $bucket['unit'] ) ? $bucket['unit'] : 'days';
			$units[ $unit ] = true;

			$options[] = array(
				'key'   => $bucket['key'],
				'label' => $bucket['label'],
				'unit'  => $unit,
				'count' => isset( $counts['duration'][ $bucket['key'] ] ) ? $counts['duration'][ $bucket['key'] ] : 0,
			);
		}

		/*
		 * Day-length and week-length options are drawn as two separate blocks
		 * under one heading, each with its own sub-label — client-directed,
		 * because week-paced packages are coming and "15+ days" is not how
		 * anybody shops for a month in a homestay.
		 *
		 * 🔴 They are two blocks of ONE facet, not two facets, and that is
		 * not a shortcut. A package carries a single Trip duration (days), so it
		 * falls into exactly one bucket: two independently-pressable controls
		 * combined with AND — "6–9 days" and "2–4 weeks" at once — could only
		 * ever produce an empty grid, whatever was in the catalogue. One list,
		 * split by unit where it helps the reader, is the same offer without the
		 * dead end, and it needs nothing from filters.js at all.
		 *
		 * The sub-labels are only worth drawing when both units are actually on
		 * offer. A catalogue of day trips gets the plain list it has always had,
		 * with no "Days" heading over the only kind of row there is.
		 */
		$split = count( $units ) > 1;

		$facets[] = array(
			'selected'   => iflynepal_archive_requested_filter( 'duration', $options ),
			'facet'      => 'duration',
			'label'      => __( 'Duration', 'iflynepal' ),
			'aria'       => __( 'Filter by duration', 'iflynepal' ),
			'style'      => 'list',
			'total'      => $total,
			'unit_names' => $split ? iflynepal_archive_duration_unit_names() : array(),
			'options'    => $options,
		);
	}

	if ( ! empty( $budgets ) ) {
		$options = array();

		foreach ( $budgets as $bucket ) {
			$options[] = array(
				'key'   => $bucket['key'],
				'label' => $bucket['label'],
				'count' => isset( $counts['budget'][ $bucket['key'] ] ) ? $counts['budget'][ $bucket['key'] ] : 0,
			);
		}

		$facets[] = array(
			'selected' => iflynepal_archive_requested_filter( 'budget', $options ),
			'facet'    => 'budget',
			'label'    => __( 'Budget', 'iflynepal' ),
			'aria'     => __( 'Filter by budget', 'iflynepal' ),
			'style'    => 'list',
			'total'    => $total,
			'options'  => $options,
		);
	}

	return $facets;
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
