<?php
/**
 * The multi-type "explore" results page — `[iflynepal_type_explorer]`.
 *
 * The client's own words: a visitor picks one or more trip types (Retreat AND
 * Tour, say), lands on one page showing a taste of each, and only commits to a
 * real category archive by pressing that type's own "View all" button. A
 * taxonomy archive cannot do this natively — one URL answers to exactly one
 * term — so this is deliberately not a WordPress archive of any kind. It is a
 * transit page: nothing is stored, nothing is queried until the request is on
 * the server, and the same URL always renders the same thing.
 *
 * Which types a visitor asked for lives entirely in the query string
 * (`?types[]=retreat-nepal&types[]=nepal-tour`, or a comma-joined
 * `?types=retreat-nepal,nepal-tour` — both are read, because the picker that
 * will eventually submit this is a separate, not-yet-built piece of work, and
 * nothing here should have to change once its exact submission shape is
 * decided). No rewrite rule is needed for that: `$_GET` is available on any
 * URL regardless of what WordPress's own query vars know about, which is the
 * same reasoning the archive's own card-filter row relies on for working with
 * JavaScript off.
 *
 * An editor drops the shortcode into an ordinary Page — there is no per-type
 * copy here for term meta to own, unlike the archive content model, so a
 * normal Page is the right home and gives the client an editable spot for
 * whatever intro copy they want above the results.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cards to show per selected type.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_EXPLORE_CARDS_PER_TYPE = 3;

/**
 * Every top-level package type there is, taxonomy order.
 *
 * The one place both iflynepal_explore_selected_types() (to validate a
 * requested slug against) and iflynepal_booking_render_type_explorer() (to
 * fall back to when duration is the only thing asked for) get this list, so
 * the two cannot disagree about what "every type" means.
 *
 * @since 1.0.0
 *
 * @return WP_Term[] Top-level package_type terms, taxonomy order.
 */
function iflynepal_explore_top_level_types() {
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

	return is_wp_error( $top_level ) || ! $top_level ? array() : $top_level;
}

/**
 * The top-level package types a visitor explicitly ticked, in canonical order.
 *
 * "Canonical" meaning: always the taxonomy's own order, never the order the
 * slugs appeared in the query string. That is what makes the same URL render
 * identically no matter how its params were assembled — load-bearing once
 * Cloudflare starts caching this page by its full URL, and simply less
 * surprising regardless.
 *
 * A slug that is not a real, top-level package type is silently dropped
 * rather than erroring — the query string is visitor-controlled, so it gets
 * the same "bad input is just ignored" treatment as the archive's own filter
 * row. When nothing survives that (no `types` param, or every value junk),
 * an empty array is returned — NOT every type. Ticking no category and
 * nothing else is still "nothing to show"; it is
 * iflynepal_booking_render_type_explorer() that decides an empty result here
 * means "every type" the moment a duration bucket was picked instead, since
 * duration-alone is its own valid search (client-directed, 14 Sep 2026,
 * later the same day) and category-alone-with-nothing-ticked is not.
 *
 * @since 1.0.0
 *
 * @return WP_Term[] Explicitly requested top-level package_type terms.
 */
function iflynepal_explore_selected_types() {
	$top_level = iflynepal_explore_top_level_types();

	if ( ! $top_level ) {
		return array();
	}

	$requested = array();

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display filter, changes nothing server-side.
	if ( isset( $_GET['types'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per value below (sanitize_title()); the sniff cannot see through the loop.
		foreach ( (array) wp_unslash( $_GET['types'] ) as $iflynepal_raw ) {
			foreach ( explode( ',', (string) $iflynepal_raw ) as $iflynepal_piece ) {
				$slug = sanitize_title( $iflynepal_piece );

				if ( '' !== $slug ) {
					$requested[ $slug ] = true;
				}
			}
		}
	}

	if ( empty( $requested ) ) {
		return array();
	}

	$selected = array();

	foreach ( $top_level as $term ) {
		if ( isset( $requested[ $term->slug ] ) ) {
			$selected[] = $term;
		}
	}

	// Every requested slug was junk or belonged to a child term — nothing to show.
	return $selected;
}

/**
 * The trip-length bucket a visitor asked for, if any.
 *
 * A `days` value that does not match one of iflynepal_trip_finder_durations()'s
 * four keys — missing, mistyped, or a slug from a future picker version this
 * page has not been updated for — is read as "no preference" rather than
 * rejected, the same "bad input is just ignored" rule iflynepal_explore_selected_types()
 * uses for a junk type slug.
 *
 * @since 1.0.0
 *
 * @return array{key: string, label: string, min: int, max: int|null}|null
 */
function iflynepal_explore_selected_duration() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display filter, changes nothing server-side.
	if ( ! isset( $_GET['days'] ) ) {
		return null;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Same as above.
	$key = sanitize_title( wp_unslash( $_GET['days'] ) );

	foreach ( iflynepal_trip_finder_durations() as $bucket ) {
		if ( $bucket['key'] === $key ) {
			return $bucket;
		}
	}

	return null;
}

/**
 * The packages filed under a term, optionally narrowed to a trip-length
 * bucket.
 *
 * A duration-aware sibling of iflynepal_archive_packages() rather than an
 * added parameter on it: the real archive template that function serves has
 * no length filter and should not gain one silently as a side effect of this
 * page's own needs.
 *
 * @since 1.0.0
 *
 * @param int        $term_id  Package type term.
 * @param int        $limit    Posts to fetch.
 * @param array|null $duration Bucket from iflynepal_explore_selected_duration(),
 *                              or null for no length filter.
 * @return WP_Post[] Packages.
 */
function iflynepal_explore_packages_for_term( $term_id, $limit, $duration = null ) {
	$args = array(
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
	);

	if ( is_array( $duration ) ) {
		/*
		 * 'type' => 'NUMERIC' matters: Trip duration (days) is stored as
		 * plain post meta (a string, as all post meta is), and without it
		 * BETWEEN and >= would compare "10" against "9" as text and put it
		 * before "9", not after.
		 */
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => iflynepal_package_meta_key( 'duration_days' ),
				'type'    => 'NUMERIC',
				'value'   => null === $duration['max'] ? (int) $duration['min'] : array( (int) $duration['min'], (int) $duration['max'] ),
				'compare' => null === $duration['max'] ? '>=' : 'BETWEEN',
			),
		);
	}

	return get_posts( $args );
}

/**
 * Renders one type's section: heading, up to three cards, a "View all" link.
 *
 * Takes an already-fetched package list rather than fetching its own — the
 * caller (iflynepal_booking_render_type_explorer()) has to know, before
 * printing anything, whether ANY section across ALL selected types has a
 * result, so it can show one friendly empty-state message instead of a page
 * that is technically not empty but has nothing in it. Fetching twice would
 * both waste the query and risk the two calls disagreeing.
 *
 * 🔴 The wrapper around the button is deliberately `.iflynepal-actions`, not
 * `.iflynepal-listing__foot` — that class belongs to the archive's own
 * multiple-links cross-fade (§5.3k of the project doc): it starts collapsed
 * to zero height (`grid-template-rows: 0fr`) and only opens once
 * `assets/js/archive/filters.js` adds `.is-open`, which nothing on this page
 * ever does. Reusing it here would render a button that is present in the
 * DOM and permanently invisible.
 *
 * @since 1.0.0
 *
 * Every other section (by position among the sections actually drawn, not
 * by the type's own position in the taxonomy) carries `.iflynepal-section--mist`
 * — the same pale-band-plus-ring treatment the real archive alternates its
 * own sections with (catalogue.css), so a multi-category result reads as a
 * considered page rather than a stack of identical white blocks.
 *
 * @param WP_Term   $term     Top-level package type.
 * @param WP_Post[] $packages Non-empty. Packages to show, already queried and
 *                            already narrowed to any duration bucket.
 * @param int       $index    Position among the sections actually drawn,
 *                            zero-based.
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_explore_the_type_section( $term, $packages, $index = 0 ) {
	$archive_url   = get_term_link( $term );
	$section_class = 0 === $index % 2 ? 'iflynepal-section iflynepal-listing' : 'iflynepal-section iflynepal-listing iflynepal-section--mist';
	?>
	<section class="<?php echo esc_attr( $section_class ); ?>" data-iflynepal-fade>
		<div class="iflynepal-container">
			<div class="iflynepal-listing__head">
				<h2><?php echo esc_html( $term->name ); ?></h2>
			</div>

			<div class="iflynepal-cards">
				<?php
				foreach ( $packages as $package ) {
					iflynepal_booking_get_part( 'parts/card-package', array( 'package' => $package ) );
				}
				?>
			</div>

			<?php if ( ! is_wp_error( $archive_url ) ) : ?>
				<div class="iflynepal-actions">
					<a class="iflynepal-button iflynepal-button--dark" href="<?php echo esc_url( $archive_url ); ?>">
						<?php
						printf(
							/* translators: %s: package type name, e.g. "Retreat". */
							esc_html__( 'View all %s packages', 'iflynepal' ),
							esc_html( $term->name )
						);
						?>
						<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * The "Your ... trips" line at the top of the page, describing what was
 * asked for rather than just what came back.
 *
 * Built from the EXPLICITLY ticked types, never the fallback-expanded list
 * iflynepal_booking_render_type_explorer() may have substituted for them:
 * a duration-only search should read "Trips, 10-14 days", not name all five
 * types as if every one had been ticked by hand.
 *
 * wp_sprintf_l() rather than a hand-rolled implode(): it is core's own
 * "A, B and C" list joiner, already translatable and already correct for a
 * two-item list ("A and B", no comma).
 *
 * The chosen types and the chosen duration are each wrapped in
 * `.iflynepal-ink-mark` — the site's own hand-drawn-underline treatment
 * (catalogue.css, drawn by assets/js/archive/annotation.js), the same
 * "editor writes the span, the underline is CSS" mechanism the archive's own
 * headings use. Here it marks whichever parts are the actual answer to the
 * search rather than an editor's typed word, which is why this function
 * builds the mark itself instead of an editor typing one into a field.
 *
 * @since 1.0.0
 *
 * @param WP_Term[]  $explicit_types Types the visitor actually ticked. Empty
 *                                   when the page is running on duration alone.
 * @param array|null $duration       Bucket from iflynepal_explore_selected_duration().
 * @return string Escaped HTML, ready to echo.
 */
function iflynepal_explore_selection_summary( $explicit_types, $duration ) {
	if ( $explicit_types ) {
		$type_list = wp_sprintf_l( '%l', wp_list_pluck( $explicit_types, 'name' ) );
		$bits      = array(
			sprintf(
				/* translators: %s: an ink-marked, "and"-joined list of package type names, e.g. "Retreat and Tour". Already escaped HTML. */
				esc_html__( 'Your %s trips', 'iflynepal' ),
				'<span class="iflynepal-ink-mark">' . esc_html( $type_list ) . '</span>'
			),
		);
	} else {
		$bits = array( esc_html__( 'Trips', 'iflynepal' ) );
	}

	if ( $duration ) {
		$bits[] = '<span class="iflynepal-ink-mark">' . esc_html( $duration['label'] ) . '</span>';
	}

	return implode( ', ', $bits );
}

/**
 * The friendly "nothing matched" notice, shown instead of a page that is
 * technically not empty but has no cards in it anywhere.
 *
 * Always offers a way out rather than being a dead end, the same rule the
 * card filter row, the departures rail and the payment box all follow
 * elsewhere in this plugin: a search that can come up empty always leaves
 * the visitor a next step.
 *
 * @since 1.0.0
 *
 * @param array|null $duration Bucket from iflynepal_explore_selected_duration(),
 *                              so the message can name it when that is why
 *                              nothing matched.
 * @return void
 */
function iflynepal_explore_the_empty_notice( $duration ) {
	$archive_url = get_post_type_archive_link( IFLYNEPAL_PACKAGE_POST_TYPE );
	?>
	<?php
	/*
	 * No `--mist` band and no eyebrow here, both for the same reason: the
	 * summary section right above this one already carries both, and this
	 * section always follows it directly (the summary is only ever followed
	 * by EITHER this or the results, never both). Repeating either one
	 * section down would read as a copy mistake — two identical bands or
	 * labels back to back — rather than a second, considered section.
	 */
	?>
	<section class="iflynepal-section" data-iflynepal-fade>
		<div class="iflynepal-container">
			<div class="iflynepal-section-head iflynepal-section-head--center">
				<h2><?php esc_html_e( 'Nothing matches, yet', 'iflynepal' ); ?></h2>
				<?php if ( $duration ) : ?>
					<p class="iflynepal-lead">
						<?php
						printf(
							/* translators: %s: the duration bucket's label, e.g. "10-14 days". */
							esc_html__( 'We do not have a trip matching %s just yet. Our team adds new departures often, so it is worth checking back, or get in touch and we will help you find one.', 'iflynepal' ),
							esc_html( $duration['label'] )
						);
						?>
					</p>
				<?php else : ?>
					<p class="iflynepal-lead"><?php esc_html_e( 'Nothing matches that just yet. Try a different combination, or browse everything we offer.', 'iflynepal' ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $archive_url && ! is_wp_error( $archive_url ) ) : ?>
				<div class="iflynepal-actions iflynepal-actions--center">
					<a class="iflynepal-button iflynepal-button--dark" href="<?php echo esc_url( $archive_url ); ?>">
						<?php esc_html_e( 'Browse all packages', 'iflynepal' ); ?>
						<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * `[iflynepal_type_explorer]` — the results view itself.
 *
 * A shortcode callback must return its markup rather than echo it, so the
 * per-type sections above (which do echo, the same as every other template
 * part in this plugin) are captured through an output buffer rather than
 * being rewritten to build up a string by hand.
 *
 * 🔴 The `.iflynepal-archive` wrapper is load-bearing, not decoration.
 * Every design token catalogue.css defines — `--ifn-navy`, `--ifn-mist`,
 * the radii, the easing curves, all of it — is declared on `.iflynepal-archive`
 * (and `.iflynepal-package`), never on `:root` (see the top of that file).
 * The real archive templates get this for free from their own
 * `<main class="iflynepal-archive">`; a shortcode dropped into an arbitrary
 * Page has no such ancestor, so without restating the class here every card,
 * button and spacing value on this page would silently fall back to
 * unstyled browser defaults — the same class of bug §5.3o of the project
 * doc records for the single-package page's own variable scope.
 *
 * A visitor who picked only a duration and no category still gets a page:
 * the search is "trips of this length, whichever kind", so an empty
 * category selection falls back to every top-level type rather than to
 * nothing — but only when a duration was actually asked for. Picking
 * nothing at all is still the one case that renders empty (14 Sep 2026),
 * because "search everything" and "searched for nothing" have to stay
 * distinguishable from the query string alone.
 *
 * @since 1.0.0
 *
 * @return string Markup, or an empty string when nothing was searched for or
 *               no type has anything to show.
 */
function iflynepal_booking_render_type_explorer() {
	$explicit_types = iflynepal_explore_selected_types();
	$duration       = iflynepal_explore_selected_duration();
	$types          = $explicit_types;

	if ( empty( $types ) ) {
		if ( null === $duration ) {
			return '';
		}

		$types = iflynepal_explore_top_level_types();
	}

	if ( empty( $types ) ) {
		return '';
	}

	/*
	 * Fetched up front, before anything prints, so it is known whether ANY
	 * section has a result before deciding whether to draw the sections or
	 * the one friendly empty-state message — a page that renders each
	 * section's own "nothing here" separately reads like several broken
	 * widgets rather than one search with no matches.
	 */
	$sections = array();

	foreach ( $types as $term ) {
		$packages = iflynepal_explore_packages_for_term( $term->term_id, IFLYNEPAL_EXPLORE_CARDS_PER_TYPE, $duration );

		if ( $packages ) {
			$sections[] = array(
				'term'     => $term,
				'packages' => $packages,
			);
		}
	}

	ob_start();
	?>
	<div class="iflynepal-archive iflynepal-type-explorer">
		<section class="iflynepal-section iflynepal-section--mist" data-iflynepal-fade>
			<div class="iflynepal-container">
				<div class="iflynepal-section-head iflynepal-section-head--center">
					<span class="iflynepal-eyebrow"><?php esc_html_e( 'Trip finder', 'iflynepal' ); ?></span>
					<?php
					/*
					 * <h2>, not <h1> — every other section on this page titles
					 * itself with <h2> (iflynepal_explore_the_type_section()),
					 * and .iflynepal-section-head's font rule only targets h2.
					 * The theme's own index.php (the template this shortcode's
					 * Page falls through to) never prints the Page's own title,
					 * so this remains the only heading on the page either way.
					 */
					?>
					<h2><?php echo iflynepal_explore_selection_summary( $explicit_types, $duration ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped internally, see the function. ?></h2>
				</div>
			</div>
		</section>

		<?php if ( $sections ) : ?>
			<?php
			foreach ( $sections as $iflynepal_index => $section ) {
				iflynepal_explore_the_type_section( $section['term'], $section['packages'], $iflynepal_index );
			}
			?>
		<?php else : ?>
			<?php iflynepal_explore_the_empty_notice( $duration ); ?>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'iflynepal_type_explorer', 'iflynepal_booking_render_type_explorer' );

/**
 * Whether the current request will render the shortcode.
 *
 * Answered from the queried post's own content, the ordinary way to make an
 * enqueue conditional on a shortcode being present — cheaper than always
 * loading the stylesheet and correct even though this page is not one of the
 * plugin's own templates from includes/frontend/template-loader.php.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function iflynepal_booking_has_type_explorer() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();

	return $post instanceof WP_Post && has_shortcode( $post->post_content, 'iflynepal_type_explorer' );
}

/**
 * Enqueues the catalogue stylesheet on whichever Page carries the shortcode.
 *
 * The card grid, the section heading, the button and the ink-mark underline
 * are all catalogue.css rules already; the one addition this page needed of
 * its own is the small `[data-iflynepal-fade]` reveal block at the end of
 * that file.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_type_explorer_styles() {
	if ( ! iflynepal_booking_has_type_explorer() ) {
		return;
	}

	wp_enqueue_style(
		'iflynepal-catalogue',
		IFLYNEPAL_BOOKING_URL . 'assets/css/catalogue.css',
		array(),
		iflynepal_booking_asset_version( 'assets/css/catalogue.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_type_explorer_styles', 20 );

/**
 * Enqueues this page's own motion.
 *
 * Two scripts, neither dependent on GSAP:
 *
 *  - assets/js/archive/annotation.js — the same script the real archive
 *    templates use for the `.iflynepal-ink-mark` underline. It is written
 *    generically (queries `.iflynepal-ink-mark` sitewide, does nothing with
 *    what it does not find), so it is reused rather than copied.
 *  - assets/js/explore/reveal.js — this page's own lightweight section
 *    fade, new. See the note above `[data-iflynepal-fade]` in catalogue.css
 *    for why this is not the archive's GSAP-driven reveal.js instead.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_enqueue_type_explorer_scripts() {
	if ( ! iflynepal_booking_has_type_explorer() ) {
		return;
	}

	wp_enqueue_script(
		'iflynepal-archive-annotation',
		IFLYNEPAL_BOOKING_URL . 'assets/js/archive/annotation.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/archive/annotation.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_enqueue_script(
		'iflynepal-explore-reveal',
		IFLYNEPAL_BOOKING_URL . 'assets/js/explore/reveal.js',
		array(),
		iflynepal_booking_asset_version( 'assets/js/explore/reveal.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'iflynepal_booking_enqueue_type_explorer_scripts', 20 );

/**
 * Docks the theme's header from the first frame on this page.
 *
 * 🔴 Found by looking at the running site, not by reading the code: the
 * header is `position: fixed` with a transparent background until the
 * theme's hero.js docks it — a treatment built for pages that open on a
 * photo, where white nav text needs the photo behind it to read. This
 * shortcode's Page has no hero and falls through to the theme's bare
 * `index.php` (no page template sets one either), so nothing was ever
 * docking it: the header rendered exactly as designed, transparent, over a
 * plain white body — which is a header with invisible white-on-white text,
 * not a missing one. Every other plain Page on this site (About, Contact,
 * Team…) avoids this because each has its own dedicated page template with
 * a real photo hero of its own; this is the first plain Page without one.
 *
 * Identical technique to iflynepal_booking_dock_header() in enqueue.php,
 * which does the same job for a single package — same class, same theme
 * component, so the docked appearance is the theme's rather than a second
 * copy of it here.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_dock_explore_header() {
	if ( ! iflynepal_booking_has_type_explorer() ) {
		return;
	}
	?>
	<script>
		document.addEventListener( 'DOMContentLoaded', function () {
			var header = document.getElementById( 'iflynepal-header' );

			if ( header ) {
				header.classList.add( 'is-docked' );
			}
		} );
	</script>
	<?php
}
add_action( 'wp_head', 'iflynepal_booking_dock_explore_header' );

/**
 * Marks the body so catalogue.css can close the gap the theme's own docked
 * header leaves.
 *
 * 🔴 The theme reserves top padding for the header's TALL, undocked height
 * (92px) unconditionally (`body:not(.has-iflynepal-hero) .site-main`) — a
 * fixed number written for the normal case, where a header starts tall and
 * only shrinks to its docked 70px once JS docks it after the visitor
 * scrolls. This page's header is docked (short) from the very first frame
 * instead, `iflynepal_booking_dock_explore_header()` above, so the generic
 * rule leaves a 22px sliver of plain white body between the header's real
 * bottom edge and the page's first section — invisible on a plain white
 * page, but a visible seam here between the solid navy header and the pale
 * mist band directly under it. This class is what catalogue.css's override
 * targets to close it, rather than editing the theme's own rule, which
 * would reopen the same 22px gap on the single-package template that rule
 * was written for.
 *
 * @since 1.0.0
 *
 * @param string[] $classes Existing body classes.
 * @return string[] With this page's class added, when relevant.
 */
function iflynepal_booking_explore_body_class( $classes ) {
	if ( iflynepal_booking_has_type_explorer() ) {
		$classes[] = 'iflynepal-type-explorer-page';
	}

	return $classes;
}
add_filter( 'body_class', 'iflynepal_booking_explore_body_class' );
