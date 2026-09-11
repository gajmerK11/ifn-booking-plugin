<?php
/**
 * The "reasons to come" tiles.
 *
 * Which tiles exist is derived from the stored values by
 * iflynepal_archive_group(), so filling slots 1 and 3 renders two tiles and
 * renumbers nothing. No count is stored anywhere.
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

$iflynepal_id    = $iflynepal_term->term_id;
$iflynepal_tiles = iflynepal_archive_group(
	$iflynepal_id,
	'benefit',
	IFLYNEPAL_ARCHIVE_BENEFIT_SLOTS,
	array( 'image', 'title', 'text' ),
	'title'
);

if ( empty( $iflynepal_tiles ) ) {
	return;
}
?>

<section class="iflynepal-section iflynepal-section--mist iflynepal-benefits" id="iflynepal-why">
	<div class="iflynepal-container">
		<?php iflynepal_archive_the_head( $iflynepal_id, 'benefits', 'iflynepal-section-head--center' ); ?>

		<div class="iflynepal-benefits__grid">
			<?php foreach ( $iflynepal_tiles as $iflynepal_tile ) : ?>
				<article class="iflynepal-benefit">
					<?php
					$iflynepal_image_id = absint( $iflynepal_tile['image'] );

					if ( $iflynepal_image_id ) {
						echo wp_get_attachment_image( // core-escaped markup.
							$iflynepal_image_id,
							'medium_large',
							false,
							array(
								'loading' => 'lazy',
								'alt'     => esc_attr( $iflynepal_tile['title'] ),
							)
						);
					}
					?>

					<div class="iflynepal-benefit__body">
						<h3 class="iflynepal-benefit__title"><?php echo esc_html( $iflynepal_tile['title'] ); ?></h3>

						<?php if ( '' !== $iflynepal_tile['text'] ) : ?>
							<p><?php echo esc_html( $iflynepal_tile['text'] ); ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
