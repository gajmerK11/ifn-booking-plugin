<?php
/**
 * The "reasons to come" tiles.
 *
 * Photo cards rather than a ruled table of text: a picture under a navy scrim
 * with the label at the bottom edge, and the reason itself held back until the
 * card is hovered, so the set reads as six reasons rather than six paragraphs.
 *
 * The number on each card is its position, derived here and never stored. A
 * stored number is the thing that goes stale the moment a card is removed from
 * the middle of the list.
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
			<?php foreach ( $iflynepal_tiles as $iflynepal_index => $iflynepal_tile ) : ?>
				<article class="iflynepal-benefit" data-iflynepal-anim>
					<?php
					$iflynepal_image_id = absint( $iflynepal_tile['image'] );

					if ( $iflynepal_image_id ) {
						/*
						 * The photograph is the card's background, not its
						 * subject: the heading over it says what the card is
						 * about, so an alt text repeating it would be read
						 * twice. Empty alt, as decoration takes.
						 */
						echo wp_get_attachment_image( // core-escaped markup.
							$iflynepal_image_id,
							'large',
							false,
							array(
								'loading' => 'lazy',
								'alt'     => '',
							)
						);
					}
					?>

					<div class="iflynepal-benefit__body">
						<?php
						/*
						 * Decoration: the number is a visual marker for the set,
						 * and read aloud in front of every heading it is noise.
						 */
						?>
						<span class="iflynepal-benefit__num" aria-hidden="true">
							<?php echo esc_html( sprintf( '%02d', $iflynepal_index + 1 ) ); ?>
						</span>

						<h3 class="iflynepal-benefit__title"><?php echo esc_html( $iflynepal_tile['title'] ); ?></h3>

						<?php if ( '' !== $iflynepal_tile['text'] ) : ?>
							<p><?php echo iflynepal_booking_kses_text( $iflynepal_tile['text'] ); // kses filtered. ?></p>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
