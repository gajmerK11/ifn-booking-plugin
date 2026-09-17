<?php
/**
 * The overview section of a package page.
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

$iflynepal_intro      = iflynepal_package_rich_paragraphs( iflynepal_package_field( $iflynepal_id, 'overview_intro' ) );
$iflynepal_body       = iflynepal_package_rich_paragraphs( iflynepal_package_field( $iflynepal_id, 'overview_body' ) );
$iflynepal_highlights = iflynepal_package_rich_lines( iflynepal_package_field( $iflynepal_id, 'highlights' ) );

if ( empty( $iflynepal_intro ) && empty( $iflynepal_body ) && empty( $iflynepal_highlights ) ) {
	return;
}
?>

<section class="iflynepal-pkg-t-section" id="ifnpkg-overview" aria-label="<?php esc_attr_e( 'Overview', 'iflynepal' ); ?>">
	<?php if ( ! empty( $iflynepal_intro ) ) : ?>
		<p class="iflynepal-pkg-intro" data-iflynepal-anim>
			<?php
			// Each paragraph is already run through wp_kses_post()/esc_html() by iflynepal_package_rich_paragraphs().
			echo implode( ' ', $iflynepal_intro ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $iflynepal_body ) ) : ?>
		<div class="iflynepal-pkg-prose iflynepal-pkg-prose--after" data-iflynepal-anim>
			<?php foreach ( $iflynepal_body as $iflynepal_paragraph ) : ?>
				<p>
					<?php
					// Already run through wp_kses_post()/esc_html() by iflynepal_package_rich_paragraphs().
					echo $iflynepal_paragraph; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $iflynepal_highlights ) ) : ?>
		<?php
		/*
		 * Heading and list are one card, so the reveal is one reveal — animating
		 * the two separately inside a single panel makes the box arrive in two
		 * pieces.
		 *
		 * The badge is decoration and says nothing the heading does not, so it is
		 * hidden from assistive tech — a screen reader should hear "Highlights",
		 * not "Highlights, image".
		 */
		?>
		<div class="iflynepal-pkg-highlights" data-iflynepal-anim>
			<h3 class="iflynepal-pkg-sub-h iflynepal-pkg-sub-h--badged">
				<span class="iflynepal-pkg-sub-h__badge" aria-hidden="true">
					<svg class="iflynepal-pkg-ico" viewBox="0 0 24 24" focusable="false"><use href="#ifnpkg-i-spark"/></svg>
				</span>
				<?php esc_html_e( 'Highlights', 'iflynepal' ); ?>
			</h3>
			<ul class="iflynepal-pkg-check-list">
				<?php foreach ( $iflynepal_highlights as $iflynepal_highlight ) : ?>
					<li>
						<span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg></span>
						<?php
						// Already run through wp_kses_post()/esc_html() by iflynepal_package_rich_lines().
						echo $iflynepal_highlight; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
