<?php
/**
 * The dates and prices section of a package page.
 *
 * The calendar, the traveller stepper and the running total are a quotation, not
 * a reservation: any date can be picked, nothing is held, and no availability,
 * capacity or inventory exists behind any of it. That is the client's explicit
 * and repeated instruction for this whole plugin, not an unfinished feature.
 *
 * The calendar grid itself is drawn by assets/js/package/package.js. With
 * JavaScript off the section still states the price, what is included and what
 * is not, which is the part a visitor actually needs before they enquire.
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

$iflynepal_price = iflynepal_package_field( $iflynepal_id, 'price_amount' );

if ( '' === $iflynepal_price ) {
	return;
}

$iflynepal_currency = iflynepal_package_field( $iflynepal_id, 'price_currency' );

/*
 * The run the calendar highlights. Floored at one day, because a package with
 * nothing typed in the field still has to let somebody pick a date, and a run
 * of zero days would highlight nothing and quote for nothing.
 */
$iflynepal_days     = max( 1, (int) iflynepal_package_field( $iflynepal_id, 'duration_days' ) );
$iflynepal_heading  = iflynepal_package_field( $iflynepal_id, 'dates_heading' );
$iflynepal_included = iflynepal_package_field_lines( $iflynepal_id, 'included' );
$iflynepal_excluded = iflynepal_package_field_lines( $iflynepal_id, 'excluded' );
?>

<section class="iflynepal-pkg-t-section" id="ifnpkg-dates" aria-labelledby="ifnpkg-dates-h">
	<div class="iflynepal-pkg-t-head" data-iflynepal-anim>
		<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Dates & prices', 'iflynepal' ); ?></span>
		<?php if ( '' !== $iflynepal_heading ) : ?>
			<h2 id="ifnpkg-dates-h"><?php iflynepal_package_the_heading( $iflynepal_heading ); ?></h2>
		<?php endif; ?>

		<?php
		$iflynepal_lead = iflynepal_package_field( $iflynepal_id, 'dates_lead' );

		if ( '' !== $iflynepal_lead ) :
			?>
			<p class="iflynepal-pkg-lead"><?php echo esc_html( $iflynepal_lead ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	/*
	 * The booker's numbers reach the script as data attributes rather than as a
	 * localized object: they belong to this package, and a second package on a
	 * page would need a second object with a different name.
	 */
	?>
	<div class="iflynepal-pkg-booker-wrap" data-iflynepal-anim>
		<?php
		/*
		 * The handwritten note leaning on the calendar. Decoration, and marked as
		 * such: it is aria-hidden and it repeats nothing a visitor needs, because
		 * the same fact is stated plainly by the calendar underneath it.
		 *
		 * It is not a field. Every other string on this page is an editor's, but
		 * this one is a caption on a control rather than copy about the trip — it
		 * says what the calendar does, and the calendar does the same thing on
		 * every package. The clearance it hangs in is the design's
		 * `#dates .t-head { margin-bottom: 70px }`.
		 *
		 * Client-directed wording, and client-directed placement: it sits at the
		 * calendar's right-hand edge rather than a fifth of the way across it. The
		 * arrow is mirrored to follow — see the EXTRAS block in build_css.py.
		 */
		?>
		<span class="iflynepal-pkg-annot iflynepal-pkg-annot--dates" aria-hidden="true">
			<b><?php esc_html_e( 'your dates, your call', 'iflynepal' ); ?></b>
			<svg viewBox="0 0 42 52" fill="none" focusable="false">
				<path d="M6 4c16 2 28 12 30 38" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 6" stroke-linecap="round"/>
				<path d="M29 36l7 8 5-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</span>

		<div class="iflynepal-pkg-booker"
			id="ifnpkg-booker"
			data-price="<?php echo esc_attr( $iflynepal_price ); ?>"
			data-currency="<?php echo esc_attr( '' !== $iflynepal_currency ? $iflynepal_currency : 'USD' ); ?>"
			data-days="<?php echo esc_attr( (string) $iflynepal_days ); ?>">

			<div class="iflynepal-pkg-cal">
				<div class="iflynepal-pkg-cal-head">
					<strong id="ifnpkg-cal-month" aria-live="polite"></strong>
					<div class="iflynepal-pkg-cal-nav">
						<button type="button" id="ifnpkg-cal-prev" aria-label="<?php esc_attr_e( 'Previous month', 'iflynepal' ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-left"/></svg></button>
						<button type="button" id="ifnpkg-cal-next" aria-label="<?php esc_attr_e( 'Next month', 'iflynepal' ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-right"/></svg></button>
					</div>
				</div>
				<div class="iflynepal-pkg-cal-grid" id="ifnpkg-cal-grid"></div>
				<div class="iflynepal-pkg-cal-quick" id="ifnpkg-cal-quick"><small><?php esc_html_e( 'Next weekends', 'iflynepal' ); ?></small></div>
			</div>

			<div class="iflynepal-pkg-book-sum">
				<h3><?php esc_html_e( 'Your trip', 'iflynepal' ); ?></h3>

				<div class="iflynepal-pkg-sum-row">
					<div class="iflynepal-pkg-sum-dates">
						<div>
							<small><?php esc_html_e( 'Starts', 'iflynepal' ); ?></small>
							<b id="ifnpkg-sum-start" class="iflynepal-pkg-is-empty"><?php esc_html_e( 'Pick a date', 'iflynepal' ); ?></b>
						</div>
						<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
						<div>
							<small><?php esc_html_e( 'Ends', 'iflynepal' ); ?></small>
							<b id="ifnpkg-sum-end" class="iflynepal-pkg-is-empty">&mdash;</b>
						</div>
					</div>

					<div class="iflynepal-pkg-pax">
						<span>
							<?php esc_html_e( 'Travellers', 'iflynepal' ); ?>
							<?php

							$iflynepal_group = iflynepal_package_field( $iflynepal_id, 'glance_group' );

							if ( '' !== $iflynepal_group ) :
								?>
								<small><?php echo esc_html( $iflynepal_group ); ?></small>
							<?php endif; ?>
						</span>
						<div class="iflynepal-pkg-stepper">
							<button type="button" id="ifnpkg-pax-minus" aria-label="<?php esc_attr_e( 'Remove a traveller', 'iflynepal' ); ?>" disabled>&minus;</button>
							<output id="ifnpkg-pax-out" aria-live="polite">0</output>
							<button type="button" id="ifnpkg-pax-plus" aria-label="<?php esc_attr_e( 'Add a traveller', 'iflynepal' ); ?>">+</button>
						</div>
					</div>
				</div>

				<?php
				/*
				 * The at-a-glance line wins when it has been written — it is the
				 * editor's own wording, and it can say things a number cannot
				 * ("3–30 days", "2 days, 1 night"). The duration field is the
				 * fallback, so this line is never blank on a package that has a
				 * length at all.
				 */
				$iflynepal_duration = iflynepal_package_field( $iflynepal_id, 'glance_duration' );

				if ( '' === $iflynepal_duration ) {
					/* translators: %d: how many days the trip runs. */
					$iflynepal_duration = sprintf( _n( '%d day', '%d days', $iflynepal_days, 'iflynepal' ), $iflynepal_days );
				}
				?>
				<p class="iflynepal-pkg-sum-dur"><?php esc_html_e( 'Trip duration:', 'iflynepal' ); ?> <b><?php echo esc_html( $iflynepal_duration ); ?></b></p>

				<ul class="iflynepal-pkg-sum-lines">
					<li><span><?php esc_html_e( 'Per person', 'iflynepal' ); ?></span><span id="ifnpkg-sum-each"></span></li>
					<li><span><?php esc_html_e( 'Travellers', 'iflynepal' ); ?></span><span id="ifnpkg-sum-pax">&times; 0</span></li>
					<li class="iflynepal-pkg-total"><span><?php esc_html_e( 'Total', 'iflynepal' ); ?></span><output id="ifnpkg-sum-total"></output></li>
				</ul>

				<?php
				/*
				 * Client-directed, 13 Sep 2026: no button and no CTA of any
				 * kind renders in this card once a package has a payment
				 * button configured — the gateway's own button lives once,
				 * in the price-card aside (templates/parts/package/aside.php),
				 * and this card is the quotation calculator, nothing else.
				 *
				 * Without a button configured at all, the original inert
				 * control still renders: it says what it is waiting for
				 * rather than silently doing nothing.
				 */
				$iflynepal_pay = iflynepal_package_payment_markup( $iflynepal_id );
				?>

				<?php if ( '' !== $iflynepal_pay ) : ?>
					<?php // Nothing renders here — see the note above. ?>
				<?php else : ?>
					<a class="iflynepal-pkg-button iflynepal-pkg-button--primary iflynepal-pkg-button--block" id="ifnpkg-book-btn" href="#ifnpkg-dates" aria-disabled="true">
						<?php esc_html_e( 'Book now', 'iflynepal' ); ?>
						<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
					</a>
					<p class="iflynepal-pkg-sum-note" id="ifnpkg-book-note"><?php esc_html_e( 'Pick a start date and how many are travelling to continue.', 'iflynepal' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $iflynepal_included ) || ! empty( $iflynepal_excluded ) ) : ?>
		<div class="iflynepal-pkg-price-split" data-iflynepal-anim>
			<?php if ( ! empty( $iflynepal_included ) ) : ?>
				<div class="iflynepal-pkg-price-col">
					<h3><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg><?php esc_html_e( 'Included in the price', 'iflynepal' ); ?></h3>
					<ul class="iflynepal-pkg-check-list">
						<?php foreach ( $iflynepal_included as $iflynepal_line ) : ?>
							<li><span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg></span><?php echo esc_html( $iflynepal_line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $iflynepal_excluded ) ) : ?>
				<div class="iflynepal-pkg-price-col">
					<h3><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-x"/></svg><?php esc_html_e( 'Not included', 'iflynepal' ); ?></h3>
					<ul class="iflynepal-pkg-check-list iflynepal-pkg-check-list--x">
						<?php foreach ( $iflynepal_excluded as $iflynepal_line ) : ?>
							<li><span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-x"/></svg></span><?php echo esc_html( $iflynepal_line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
