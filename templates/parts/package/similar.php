<?php
/**
 * The similar-packages section.
 *
 * A query, not fields: the other packages filed under this one's own category,
 * its primary term, deepest first, the same one iflynepal_package_related() and
 * the eyebrow above the title already read (see iflynepal_package_primary_type()).
 *
 * The card is the exact card the catalogue uses (parts/card-package), not a
 * bespoke one, so a package written once looks identical wherever it appears:
 * this page, the type archive and the category archive. It runs in the same
 * .iflynepal-cards grid too, which is why this section is a top-level band
 * rather than nested in the narrower content column: the grid is meant to run
 * the page's full measure, the way it does on an archive.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'id'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_id = isset( $args['id'] ) ? (int) $args['id'] : 0;

if ( ! $iflynepal_id ) {
	return;
}

$iflynepal_related = iflynepal_package_related( $iflynepal_id );

if ( empty( $iflynepal_related ) ) {
	return;
}

/*
 * The same category the related query itself used. Read again here rather
 * than passed back from it, because the "View all" button needs the term
 * object (its name and its own archive link), not just the packages filed
 * under it.
 */
$iflynepal_term      = iflynepal_package_primary_type( $iflynepal_id );
$iflynepal_term_link = $iflynepal_term instanceof WP_Term ? get_term_link( $iflynepal_term ) : '';

if ( is_wp_error( $iflynepal_term_link ) ) {
	$iflynepal_term_link = '';
}
?>

<section class="iflynepal-pkg-band iflynepal-pkg-band--mist iflynepal-pkg-container" id="ifnpkg-similar" aria-labelledby="ifnpkg-similar-h">
	<div data-iflynepal-anim>
		<span class="iflynepal-pkg-eyebrow"><?php echo esc_html( iflynepal_pkg_t( 'Keep exploring' ) ); ?></span>
		<h2 id="ifnpkg-similar-h" class="iflynepal-pkg-band-h">
			<?php
			/*
			 * Two rows by request, so the break is written into the string
			 * rather than left to the viewport to decide. Whole sentence,
			 * break included, travels to translators together: the same
			 * choice the ink-marked headings elsewhere on this page make.
			 */
			echo wp_kses( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses escapes.
				iflynepal_pkg_t( 'Similar <span class="iflynepal-ink-mark">packages</span><br>you may like.' ),
				array(
					'span' => array( 'class' => array() ),
					'br'   => array(),
				)
			);
			?>
		</h2>
	</div>

	<div class="iflynepal-cards">
		<?php
		foreach ( $iflynepal_related as $iflynepal_package ) {
			iflynepal_booking_get_part( 'parts/card-package', array( 'package' => $iflynepal_package ) );
		}
		?>
	</div>

	<?php if ( $iflynepal_term instanceof WP_Term && '' !== $iflynepal_term_link ) : ?>
		<div class="iflynepal-pkg-similar-foot">
			<a class="iflynepal-button iflynepal-button--dark" href="<?php echo esc_url( $iflynepal_term_link ); ?>">
				<?php
				printf(
					esc_html( iflynepal_pkg_t( 'View all %s packages' ) ),
					esc_html( $iflynepal_term->name )
				);
				?>
				<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no input. ?>
			</a>
		</div>
	<?php endif; ?>
</section>
