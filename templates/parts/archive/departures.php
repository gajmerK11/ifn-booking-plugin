<?php
/**
 * Upcoming departures — the heading band and the rail of cards beneath it.
 *
 * Transcribed from the `#departures` section of
 * retreats-nepal-archive-design.html: a heading with the two rail buttons on
 * its baseline, and a horizontal scroll-snapping rail of photograph cards, each
 * carrying a remaining-places pill, a date, a title and a foot of duration and
 * price.
 *
 * The cards are fields, not a query. They are scheduled small-group dates the
 * office is selling, which is a different thing from the fixed departure dates
 * on a package — see the note in the schema before deriving one from the other.
 *
 * Nothing here is enforced: the pill is text an editor types, with no seat count
 * behind it. That is the client's stated rule for the whole plugin.
 *
 * Opt-in like every other field-driven section: a term that has written none of
 * the heading copy gets no band, and a band with no cards is the heading alone,
 * exactly as it was before the cards existed.
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

$iflynepal_id = $iflynepal_term->term_id;

if ( ! iflynepal_archive_has_any( $iflynepal_id, array( 'departures_eyebrow', 'departures_heading', 'departures_lead' ) ) ) {
	return;
}

$iflynepal_departures = iflynepal_archive_cards( $iflynepal_id, 'departure_cards' );

/* One string, read once: it is printed on every card in the rail. */
$iflynepal_reserve = __( 'Reserve', 'iflynepal' );
?>

<section class="iflynepal-section iflynepal-section--mist iflynepal-departures" id="iflynepal-departures">
	<div class="iflynepal-container">
		<div class="iflynepal-departures__head">
			<?php
			iflynepal_archive_the_head( $iflynepal_id, 'departures' );

			/*
			 * The buttons are drawn only when there is a rail to step. They are
			 * an enhancement over a region that already scrolls by touch, wheel
			 * and keyboard, so they start disabled and the script takes that off
			 * — with JavaScript unavailable they never become dead controls.
			 */
			if ( ! empty( $iflynepal_departures ) ) :
				?>
				<div class="iflynepal-rail-nav" data-iflynepal-anim>
					<button class="iflynepal-rail-btn iflynepal-rail-btn--prev" type="button" disabled aria-label="<?php esc_attr_e( 'Previous departures', 'iflynepal' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7"/></svg>
					</button>
					<button class="iflynepal-rail-btn iflynepal-rail-btn--next" type="button" disabled aria-label="<?php esc_attr_e( 'Next departures', 'iflynepal' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7"/></svg>
					</button>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $iflynepal_departures ) ) : ?>
			<?php
			/*
			 * A real scrolling region, so it is reachable by keyboard and reads
			 * as scrollable to assistive technology. tabindex makes the box
			 * focusable for arrow keys, which a scroll container does not get on
			 * its own in every browser.
			 */
			?>
			<div class="iflynepal-departure-rail" tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Upcoming departures', 'iflynepal' ); ?>">
				<?php foreach ( $iflynepal_departures as $iflynepal_card ) : ?>
					<article class="iflynepal-departure" data-iflynepal-anim>
						<?php
						$iflynepal_image_id = absint( $iflynepal_card['image'] );

						if ( $iflynepal_image_id ) {
							/*
							 * The photograph is the card's ground, under a
							 * gradient, with the title over it saying what the
							 * card is — an alt repeating that is read twice.
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

						<?php if ( '' !== $iflynepal_card['pill'] ) : ?>
							<span class="iflynepal-pill iflynepal-departure__pill"><?php echo esc_html( $iflynepal_card['pill'] ); ?></span>
						<?php endif; ?>

						<div class="iflynepal-departure__body">
							<?php if ( '' !== $iflynepal_card['date'] ) : ?>
								<span class="iflynepal-departure__date"><?php echo esc_html( $iflynepal_card['date'] ); ?></span>
							<?php endif; ?>

							<h3 class="iflynepal-departure__title"><?php echo esc_html( $iflynepal_card['title'] ); ?></h3>

							<?php
							/*
							 * The foot is drawn when either fact is written. The
							 * duration and the price are printed into their own
							 * spans whether or not they hold anything, because
							 * they are what hold Reserve at the right-hand end
							 * and the price in the middle — dropping an empty one
							 * would slide the other two along the rule.
							 *
							 * Reserve is a link when the card carries one and
							 * plain text when it does not. A button that goes
							 * nowhere is worse than a label, and the label still
							 * has to hold the right-hand end of the rule either
							 * way — so the element changes, the row does not.
							 */
							if ( '' !== $iflynepal_card['duration'] || '' !== $iflynepal_card['price'] ) :
								?>
								<div class="iflynepal-departure__meta">
									<span><?php echo esc_html( $iflynepal_card['duration'] ); ?></span>
									<span><?php echo esc_html( $iflynepal_card['price'] ); ?></span>
									<?php if ( '' !== $iflynepal_card['link'] ) : ?>
										<a class="iflynepal-departure__reserve" href="<?php echo esc_url( $iflynepal_card['link'] ); ?>">
											<?php echo esc_html( $iflynepal_reserve ); ?>
										</a>
									<?php else : ?>
										<span class="iflynepal-departure__reserve"><?php echo esc_html( $iflynepal_reserve ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
