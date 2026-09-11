<?php
/**
 * The "reasons to come" tiles.
 *
 * The cards are a repeater an editor extends with a button, so the stored list
 * *is* the count — there are no empty slots to skip and nothing to renumber.
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
$iflynepal_tiles = iflynepal_archive_cards( $iflynepal_id, 'benefit_cards' );

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
