<?php
/**
 * The package card grid, and the filter row above it.
 *
 * The cards are a query, not fields: they are the packages filed under this
 * term and its children. The filter buttons are the term's child categories
 * that actually hold packages, so the filter set grows and shrinks with the
 * catalogue and never needs editing.
 *
 * Filtering is client-side over an already-rendered grid. Everything is in the
 * DOM, so the buttons work as a progressive enhancement and the page is
 * complete with JavaScript off.
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
$iflynepal_packages = iflynepal_archive_packages( $iflynepal_id );

if ( empty( $iflynepal_packages ) ) {
	return;
}

$iflynepal_children = iflynepal_archive_filter_terms( $iflynepal_id, $iflynepal_packages );
?>

<section class="iflynepal-section iflynepal-listing" id="iflynepal-packages">
	<div class="iflynepal-container">
		<div class="iflynepal-listing__head">
			<?php
			iflynepal_archive_the_head( $iflynepal_id, 'listing' );

			/*
			 * The hand-drawn note. Both halves are separate spans because only
			 * the tail re-types: the fixed part is set once and never touched,
			 * so nothing to the left of the tail can shift while a word grows.
			 * The words travel to the script as a data attribute rather than as
			 * text, so the markup carries no half-typed state.
			 */
			$iflynepal_note_static = iflynepal_archive_field( $iflynepal_id, 'listing_annotation_static' );
			$iflynepal_note_words  = iflynepal_archive_field_lines( $iflynepal_id, 'listing_annotation_words' );

			if ( '' !== $iflynepal_note_static || ! empty( $iflynepal_note_words ) ) :
				?>
				<span class="iflynepal-annot" aria-hidden="true" data-words="<?php echo esc_attr( wp_json_encode( $iflynepal_note_words ) ); ?>">
					<?php
					/*
					 * Drawn vertically in its own box and mirrored in CSS, so the
					 * curve sweeps back toward the heading it points away from.
					 */
					?>
					<svg class="iflynepal-annot__arrow" viewBox="0 0 46 126" fill="none" aria-hidden="true" focusable="false">
						<g transform="translate(46 0) rotate(90)">
							<path class="iflynepal-annot__dash" d="M2 34c14 6 29 9 45 8 20-1 38-8 58-19" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 7" stroke-linecap="round"/>
							<path class="iflynepal-annot__head" d="M91 15.5 107.5 22.5 99.5 37" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</g>
					</svg>
					<b>
						<span class="iflynepal-annot__static"><?php echo esc_html( $iflynepal_note_static ); ?></span>
						<span class="iflynepal-annot__word"><?php echo esc_html( isset( $iflynepal_note_words[0] ) ? $iflynepal_note_words[0] : '' ); ?></span>
					</b>
				</span>
			<?php endif; ?>
		</div>

		<?php
		/*
		 * One category with packages in it is enough to be worth a filter row:
		 * a package can be filed on the parent type alone, so even a single
		 * category splits the grid into two meaningful sets.
		 */
		if ( ! empty( $iflynepal_children ) ) :
			?>
			<div class="iflynepal-filter-row" role="group" aria-label="<?php esc_attr_e( 'Filter packages', 'iflynepal' ); ?>">
				<button class="iflynepal-filter-btn is-active" type="button" data-filter="all" aria-pressed="true">
					<?php esc_html_e( 'All', 'iflynepal' ); ?>
				</button>
				<?php foreach ( $iflynepal_children as $iflynepal_child ) : ?>
					<button class="iflynepal-filter-btn" type="button" data-filter="<?php echo esc_attr( $iflynepal_child->slug ); ?>" aria-pressed="false">
						<?php echo esc_html( $iflynepal_child->name ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="iflynepal-cards">
			<?php
			foreach ( $iflynepal_packages as $iflynepal_package ) {
				iflynepal_booking_get_part( 'parts/card-package', array( 'package' => $iflynepal_package ) );
			}
			?>
		</div>
	</div>
</section>
