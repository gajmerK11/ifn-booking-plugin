<?php
/**
 * The package card grid, and the filter rail beside it.
 *
 * The cards are a query, not fields: they are the packages filed under this
 * term and its children. Up to three facets — Activity (the term's child
 * categories), Duration and Budget — sit in a rail down the left-hand side,
 * the arrangement a shopper already knows from every catalogue they have used,
 * and every one of them is built from what is actually on the page rather than
 * typed in anywhere. A facet with nothing to divide is left out entirely.
 *
 * The rail used to be a row of pills between the heading and the grid. It was
 * moved on request, and the move is more than a position: a row has to stay
 * short enough to fit across the page, so three facets stacked into three rows
 * pushed the cards down and left the reader scanning horizontally for a control
 * they were about to use vertically. A rail has the height to hold every facet
 * open at once, with its options in a column the eye reads in one pass, and it
 * stays on screen while the grid scrolls past it.
 *
 * Filtering is client-side over an already-rendered grid. Everything is in the
 * DOM, so the controls are a progressive enhancement and the page is complete
 * with JavaScript off. The facets combine with AND — a visitor narrowing to
 * "Yoga" and "6–14 days" sees only cards matching both.
 *
 * The rail is a <details> that the server always renders open. On desktop its
 * summary is hidden and there is nothing to close it with; on a phone the
 * summary is the "Filters" button and the panel collapses behind it.
 * filters.js closes it on a narrow viewport on load, so the enhancement is the
 * collapsing rather than the opening — with JavaScript off the facets are
 * simply all visible, which is longer but never broken.
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

$iflynepal_id       = $iflynepal_term->term_id;
$iflynepal_packages = iflynepal_archive_packages( $iflynepal_id, iflynepal_archive_package_limit( $iflynepal_term ) );
$iflynepal_is_type  = 0 === (int) $iflynepal_term->parent;

if ( empty( $iflynepal_packages ) ) {
	return;
}

$iflynepal_children = iflynepal_archive_filter_terms( $iflynepal_id, $iflynepal_packages );
$iflynepal_facets   = iflynepal_archive_facets( $iflynepal_id, $iflynepal_packages );

/*
 * The facets are split by where they are drawn, not by what they are. Activity
 * stays where it has always been — a row of pills directly above the cards —
 * and the two range facets go into the rail beside them. That is
 * client-directed and it is also the division the two kinds of control
 * already had: Activity is the one facet whose options are real terms with
 * real URLs, which is why it alone drives the "view all <category>" link under
 * the grid, so it belongs with the grid. Duration and Budget exist only as a
 * narrowing of what is on this page.
 */
$iflynepal_pill_facets = array();
$iflynepal_rail_facets = array();

foreach ( $iflynepal_facets as $iflynepal_facet ) {
	if ( 'pills' === $iflynepal_facet['style'] ) {
		$iflynepal_pill_facets[] = $iflynepal_facet;
	} else {
		$iflynepal_rail_facets[] = $iflynepal_facet;
	}
}

$iflynepal_has_rail = ! empty( $iflynepal_rail_facets );

/*
 * Two separate modifiers, because they answer two questions that used to be
 * the same one and are not any more.
 *
 * --no-rail is about the layout: with nothing to put beside the grid, the grid
 * takes the whole width and there is no second column to make.
 *
 * --no-filters is about the head: a leaf category with no controls of any kind
 * gets the larger gap under its heading that the design gives it (44px,
 * matching retreats-nepal-category-archive-design.html's #packages override).
 * A category with the pill row still has a control under that heading, so it
 * keeps the type archive's own spacing.
 */
$iflynepal_listing_class  = 'iflynepal-section iflynepal-listing';
$iflynepal_listing_class .= $iflynepal_has_rail ? '' : ' iflynepal-listing--no-rail';
$iflynepal_listing_class .= empty( $iflynepal_facets ) ? ' iflynepal-listing--no-filters' : '';
?>

<section class="<?php echo esc_attr( $iflynepal_listing_class ); ?>" id="iflynepal-packages">
	<div class="iflynepal-container">
		<?php
		/*
		 * The note is no longer up here beside the heading. It is printed with
		 * the pill row instead, immediately above the cards — client-directed,
		 * and it is what the note is actually for: it annotates the grid, and
		 * beside a heading two hundred pixels above the first card it was
		 * pointing at nothing in particular.
		 *
		 * A category with no listing_heading/lead of its own (the norm — the
		 * content model gives copy fields to the type, not every category)
		 * must not print the head wrapper at all: iflynepal_archive_the_head()
		 * already echoes nothing for it, but the empty div was still carrying
		 * the head's margin-bottom, leaving a blank band above the cards with
		 * nothing in it to justify the space.
		 */
		ob_start();
		iflynepal_archive_the_head( $iflynepal_id, 'listing' );
		$iflynepal_head_markup = ob_get_clean();

		$iflynepal_note_static = iflynepal_archive_field( $iflynepal_id, 'listing_annotation_static' );
		$iflynepal_note_words  = iflynepal_archive_field_lines( $iflynepal_id, 'listing_annotation_words' );
		$iflynepal_has_note    = '' !== $iflynepal_note_static || ! empty( $iflynepal_note_words );

		if ( '' !== trim( $iflynepal_head_markup ) ) :
			?>
			<div class="iflynepal-listing__top">
				<div class="iflynepal-listing__head">
					<?php echo $iflynepal_head_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by iflynepal_archive_the_head(), which escapes its own output. ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="iflynepal-listing__layout">
			<?php
			/*
			 * One facet with something to divide is enough to be worth a rail:
			 * a package can be filed on the parent type alone with no child
			 * category of its own, and still have a duration or a price that
			 * splits the grid into meaningful sets.
			 *
			 * Each facet is its own single-select group with its own "All",
			 * and assets/js/archive/filters.js combines them with AND —
			 * narrowing by Activity does not reset Duration or Budget and vice
			 * versa.
			 */
			if ( $iflynepal_has_rail ) :
				?>
				<aside class="iflynepal-listing__rail" data-iflynepal-anim>
					<details class="iflynepal-facets" open>
						<?php
						/*
						 * The count in the summary is what a collapsed panel
						 * has instead of the rail itself: with the facets shut
						 * behind a button, the number of narrowings currently
						 * applied is the only thing on screen that says the
						 * grid is not the whole set. Hidden until filters.js
						 * has something to put in it, so a visitor with no
						 * JavaScript — who cannot collapse the panel and can
						 * see every selection — is not shown an empty badge.
						 */
						?>
						<summary class="iflynepal-facets__toggle">
							<span class="iflynepal-facets__toggle-label">
								<?php echo iflynepal_booking_filter_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
								<?php echo esc_html( iflynepal_pkg_t( 'Filters' ) ); ?>
							</span>
							<span class="iflynepal-facets__badge" data-iflynepal-active-count hidden></span>
						</summary>

						<div class="iflynepal-facets__body">
							<div class="iflynepal-facets__head">
								<?php
								/*
								 * "Find your trip", not "Refine" or "Filters".
								 * The site already has a voice for this exact
								 * act — the homepage hero's button is
								 * "Find My Trip", its picker is the Trip
								 * finder, and /explore/ heads its results
								 * "Your Retreat trips". A visitor who used the
								 * hero picker and landed here meets the same
								 * words for the same thing. "Refine" is the
								 * word a search index uses about its own
								 * result set, which is not what an archive of
								 * retreats is.
								 *
								 * Hidden below 1024px, where the summary above
								 * it already says "Filters" and the two would
								 * be the same sentence twice.
								 */
								?>
								<span class="iflynepal-facets__title"><?php echo esc_html( iflynepal_pkg_t( 'Find your trip' ) ); ?></span>
								<?php
								/*
								 * Hidden until something is narrowed. A reset
								 * that resets nothing is a control the visitor
								 * has to read before discovering it was never
								 * for them, and with JavaScript off there is no
								 * narrowing to undo at all.
								 */
								?>
								<button class="iflynepal-facets__clear" type="button" data-iflynepal-clear hidden>
									<?php echo esc_html( iflynepal_pkg_t( 'Clear all' ) ); ?>
								</button>
							</div>

							<div class="iflynepal-filter-panel">
								<?php
								foreach ( $iflynepal_rail_facets as $iflynepal_facet ) {
									iflynepal_booking_get_part( 'parts/archive/filter-group', array( 'facet' => $iflynepal_facet ) );
								}
								?>
							</div>
						</div>
					</details>
				</aside>
			<?php endif; ?>

			<div class="iflynepal-listing__main">
				<?php
				/*
				 * Activity, directly above the cards, exactly where it was
				 * before the rail existed. It sits inside the grid's own
				 * column rather than spanning the rail as well, so the row
				 * starts on the same left edge as the cards it filters.
				 */
				if ( ! empty( $iflynepal_pill_facets ) || $iflynepal_has_note ) :
					/*
					 * A category with a note and no pill row (no child
					 * categories of its own) has nothing left in the band
					 * once ≥1200px pulls the note out of flow to sit beside
					 * the heading instead — see .iflynepal-annot below. This
					 * class only matters at that width; it tells the
					 * stylesheet the band's margin-bottom is now spacing
					 * nothing, so the cards can sit level with the rail
					 * instead of leaving its phantom height as a gap.
					 */
					$iflynepal_controls_class  = 'iflynepal-listing__controls';
					$iflynepal_controls_class .= empty( $iflynepal_pill_facets ) ? ' iflynepal-listing__controls--note-only' : '';
					?>
					<div class="<?php echo esc_attr( $iflynepal_controls_class ); ?>">
						<?php if ( ! empty( $iflynepal_pill_facets ) ) : ?>
							<div class="iflynepal-listing__filters iflynepal-filter-panel" data-iflynepal-anim>
								<?php
								foreach ( $iflynepal_pill_facets as $iflynepal_facet ) {
									iflynepal_booking_get_part( 'parts/archive/filter-group', array( 'facet' => $iflynepal_facet ) );
								}
								?>
							</div>
						<?php endif; ?>

					<?php
					/*
					 * The hand-drawn note. Both halves are separate spans because
					 * only the tail re-types: the fixed part is set once and never
					 * touched, so nothing to the left of the tail can shift while
					 * a word grows. The words travel to the script as a data
					 * attribute rather than as text, so the markup carries no
					 * half-typed state.
					 */
					if ( $iflynepal_has_note ) :
						?>
						<span class="iflynepal-annot" aria-hidden="true" data-words="<?php echo esc_attr( wp_json_encode( $iflynepal_note_words ) ); ?>">
							<?php
							/*
							 * Drawn vertically in its own box and mirrored in CSS,
							 * so the curve sweeps back toward the heading it points
							 * away from.
							 */
							?>
							<svg class="iflynepal-annot__arrow" viewBox="0 0 46 126" fill="none" aria-hidden="true" focusable="false">
								<g transform="translate(46 0) rotate(90)">
									<path class="iflynepal-annot__dash" d="M2 34c14 6 29 9 45 8 20-1 38-8 58-19" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 7" stroke-linecap="round"/>
									<path class="iflynepal-annot__head" d="M91 15.5 107.5 22.5 99.5 37" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</g>
							</svg>
							<b>
								<?php
								/*
								 * The gap before the ending is a non-breaking space
								 * rather than a margin or a plain space: this span
								 * is an inline-block, so an ordinary trailing space
								 * would be trimmed off the end of its line box and
								 * the fixed part would butt straight into the
								 * ending. The script picks this text up as it
								 * stands, nbsp included, so the two cannot drift
								 * apart.
								 */
								?>
								<span class="iflynepal-annot__static"><?php echo esc_html( $iflynepal_note_static ); ?>&#160;</span>
								<span class="iflynepal-annot__word"><?php echo esc_html( isset( $iflynepal_note_words[0] ) ? $iflynepal_note_words[0] : '' ); ?></span>
							</b>
						</span>
						<?php endif; ?>
					</div>
					<?php
				endif;

				/*
				 * There is deliberately no "N packages" line above the grid.
				 * It was built and then removed at the client's instruction:
				 * the archive is a catalogue to look through, not a result set
				 * to audit, and a running total above the cards reads as a
				 * search page. The per-option counts in the rail already say
				 * how much each narrowing leaves, before it is pressed, which
				 * is the point in the sequence where the number is useful.
				 *
				 * What went with it is the only thing on the page that
				 * announced a filter's effect to a screen reader. The buttons'
				 * own aria-pressed still says which filter is on, which is
				 * where this page stood before the rail was built.
				 */
				?>
				<?php
				/*
				 * The cap is announced on the grid rather than applied to it.
				 * Every card is rendered and visible as the page is served, and
				 * assets/js/archive/filters.js trims the view to the first six
				 * that match — so a visitor with no JavaScript gets the whole
				 * grid rather than six cards and a button that cannot do
				 * anything. Same shape as the rail's collapse and the enquiry
				 * form's modal: the enhancement is the hiding.
				 *
				 * A category archive carries no cap at all. It renders its whole
				 * branch (iflynepal_archive_package_limit()) because it is where
				 * the type archive's own button sends people, and arriving at
				 * "view all" to find another six would be the same wall again.
				 */
				?>
				<div class="iflynepal-cards"<?php echo $iflynepal_is_type ? ' data-iflynepal-cap="' . esc_attr( IFLYNEPAL_ARCHIVE_VISIBLE_CARDS ) . '"' : ''; ?>>
					<?php
					$iflynepal_budgets = iflynepal_archive_budget_terms( $iflynepal_packages );

					foreach ( $iflynepal_packages as $iflynepal_package ) {
						/*
						 * Budget buckets are passed through because they are
						 * worked out from this archive's own packages, not a
						 * fixed site-wide list — the card cannot rebuild them
						 * itself. Duration needs nothing passed: it reads the
						 * same site-wide length buckets everywhere, so
						 * iflynepal_archive_duration_keys() looks them up on
						 * its own.
						 */
						iflynepal_booking_get_part(
							'parts/card-package',
							array(
								'package'        => $iflynepal_package,
								'budget_buckets' => $iflynepal_budgets,
							)
						);
					}
					?>
				</div>

				<?php
				/*
				 * The message a narrowing that matches nothing gets instead of
				 * an empty grid. It cannot normally be reached — filters.js
				 * disables an option whose combination would empty the grid —
				 * but "cannot normally" is not "cannot", and a blank space
				 * where the cards were is the one outcome that gives the
				 * visitor nothing to do next. Hidden until the script has a
				 * reason to show it.
				 */
				?>
				<?php if ( ! empty( $iflynepal_facets ) ) : ?>
					<p class="iflynepal-listing__empty" data-iflynepal-empty hidden>
						<?php echo esc_html( iflynepal_pkg_t( 'No packages match that combination.' ) ); ?>
						<button class="iflynepal-facets__clear" type="button" data-iflynepal-clear>
							<?php echo esc_html( iflynepal_pkg_t( 'Clear all filters' ) ); ?>
						</button>
					</p>
				<?php endif; ?>

				<?php
				/*
				 * One "view all" link per category, every one rendered and all
				 * but the active filter's hidden, so the URLs are the terms'
				 * own and nothing is assembled in JavaScript.
				 *
				 * "All" deliberately has no link: that filter is this archive,
				 * so its link would point at the page the visitor is already
				 * on. That is also what a visitor with JavaScript off sees, and
				 * the grid above them is already the whole set.
				 *
				 * The inner wrapper is not decoration. The links are stacked
				 * into one grid cell so swapping between two categories
				 * cross-fades in place instead of changing the block's height,
				 * and the outer element is the 0fr/1fr row that opens and
				 * closes that height when the link set goes from none to one.
				 * Height cannot be animated on the element that also lays the
				 * links out, so it takes two.
				 */
				if ( $iflynepal_is_type || ! empty( $iflynepal_children ) ) :
					?>
					<div class="iflynepal-listing__foot">
						<div class="iflynepal-listing__foot-inner">
						<?php
						/*
						 * The way out of the cap when no category is chosen:
						 * there is no single category archive to send the
						 * visitor to, so the rest are revealed here instead.
						 * Hidden until the script finds something behind the
						 * cap, which on a page without JavaScript is never —
						 * and on that page every card is already showing.
						 *
						 * A button, not a link: it changes what this page
						 * displays and goes nowhere.
						 */
						if ( $iflynepal_is_type ) :
							?>
							<button class="iflynepal-listing__all iflynepal-listing__reveal" type="button" data-iflynepal-reveal hidden>
								<?php
								/*
								 * Both plural forms travel as attributes rather
								 * than the script owning English of its own: the
								 * label is rewritten on every press, and a
								 * translated page has to keep saying it in the
								 * language the rest of the page is in.
								 */
								?>
								<span
									data-iflynepal-reveal-label
									data-one="<?php echo esc_attr( iflynepal_pkg_t( 'Show %s more package' ) ); ?>"
									data-many="<?php echo esc_attr( iflynepal_pkg_t( 'Show %s more packages' ) ); ?>"
								><?php echo esc_html( iflynepal_pkg_t( 'Show all matching packages' ) ); ?></span>
								<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
							</button>
						<?php endif; ?>
						<?php
						foreach ( $iflynepal_children as $iflynepal_child ) :
							$iflynepal_child_url = get_term_link( $iflynepal_child );

							if ( is_wp_error( $iflynepal_child_url ) ) {
								continue;
							}
							?>
							<a class="iflynepal-listing__all" href="<?php echo esc_url( $iflynepal_child_url ); ?>" data-filter="<?php echo esc_attr( $iflynepal_child->slug ); ?>">
								<?php
								printf(
									esc_html( iflynepal_pkg_t( 'View all %s packages' ) ),
									esc_html( $iflynepal_child->name )
								);
								?>
								<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
							</a>
						<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
