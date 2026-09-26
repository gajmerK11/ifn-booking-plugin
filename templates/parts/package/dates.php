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

/*
 * Group-size pricing, where the package has it. The calculator works from the
 * same ladder the price card shows (templates/parts/package/aside.php) so the
 * two can never quote different numbers for the same traveller count, and a
 * package priced only by tiers still gets a calculator: the flat price_amount
 * is no longer the only way to say what a trip costs.
 */
$iflynepal_tiers = iflynepal_package_price_tiers( $iflynepal_id );

if ( '' === $iflynepal_price && ! $iflynepal_tiers ) {
	return;
}

if ( '' === $iflynepal_price ) {
	/* The first rung is what the summary quotes before anybody has said how many are coming. */
	$iflynepal_price = (string) $iflynepal_tiers[0]['price'];
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
		<span class="iflynepal-pkg-eyebrow"><?php echo esc_html( iflynepal_pkg_t( 'Dates & prices' ) ); ?></span>
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
			<b><?php echo esc_html( iflynepal_pkg_t( 'your dates, your call' ) ); ?></b>
			<svg viewBox="0 0 42 52" fill="none" focusable="false">
				<path d="M6 4c16 2 28 12 30 38" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 6" stroke-linecap="round"/>
				<path d="M29 36l7 8 5-10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</span>

		<div class="iflynepal-pkg-booker"
			id="ifnpkg-booker"
			data-price="<?php echo esc_attr( $iflynepal_price ); ?>"
			data-currency="<?php echo esc_attr( '' !== $iflynepal_currency ? $iflynepal_currency : 'USD' ); ?>"
			data-days="<?php echo esc_attr( (string) $iflynepal_days ); ?>"
			data-tiers="<?php echo esc_attr( wp_json_encode( $iflynepal_tiers ) ); ?>">

			<div class="iflynepal-pkg-cal">
				<div class="iflynepal-pkg-cal-head">
					<strong id="ifnpkg-cal-month" aria-live="polite"></strong>
					<div class="iflynepal-pkg-cal-nav">
						<button type="button" id="ifnpkg-cal-prev" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Previous month' ) ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-left"/></svg></button>
						<button type="button" id="ifnpkg-cal-next" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Next month' ) ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-right"/></svg></button>
					</div>
				</div>
				<div class="iflynepal-pkg-cal-grid" id="ifnpkg-cal-grid"></div>
				<div class="iflynepal-pkg-cal-quick" id="ifnpkg-cal-quick"><small><?php echo esc_html( iflynepal_pkg_t( 'Next weekends' ) ); ?></small></div>
			</div>

			<div class="iflynepal-pkg-book-sum">
				<h3><?php echo esc_html( iflynepal_pkg_t( 'Your trip' ) ); ?></h3>

				<div class="iflynepal-pkg-sum-row">
					<div class="iflynepal-pkg-sum-dates">
						<div>
							<small><?php echo esc_html( iflynepal_pkg_t( 'Starts' ) ); ?></small>
							<b id="ifnpkg-sum-start" class="iflynepal-pkg-is-empty"><?php echo esc_html( iflynepal_pkg_t( 'Pick a date' ) ); ?></b>
						</div>
						<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
						<div>
							<small><?php echo esc_html( iflynepal_pkg_t( 'Ends' ) ); ?></small>
							<b id="ifnpkg-sum-end" class="iflynepal-pkg-is-empty">&mdash;</b>
						</div>
					</div>

					<div class="iflynepal-pkg-pax">
						<span>
							<?php echo esc_html( iflynepal_pkg_t( 'Travellers' ) ); ?>
							<?php

							$iflynepal_group = iflynepal_package_field( $iflynepal_id, 'glance_group' );

							if ( '' !== $iflynepal_group ) :
								?>
								<small><?php echo esc_html( $iflynepal_group ); ?></small>
							<?php endif; ?>
						</span>
						<div class="iflynepal-pkg-stepper">
							<button type="button" id="ifnpkg-pax-minus" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Remove a traveller' ) ); ?>" disabled>&minus;</button>
							<output id="ifnpkg-pax-out" aria-live="polite">0</output>
							<button type="button" id="ifnpkg-pax-plus" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Add a traveller' ) ); ?>">+</button>
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
					$iflynepal_duration = sprintf( iflynepal_pkg_tn( '%d day', '%d days', $iflynepal_days ), $iflynepal_days );
				}
				?>
				<p class="iflynepal-pkg-sum-dur"><?php echo esc_html( iflynepal_pkg_t( 'Trip duration:' ) ); ?> <b><?php echo esc_html( $iflynepal_duration ); ?></b></p>

				<ul class="iflynepal-pkg-sum-lines">
					<li><span><?php echo esc_html( iflynepal_pkg_t( 'Per person' ) ); ?></span><span id="ifnpkg-sum-each"></span></li>
					<li><span><?php echo esc_html( iflynepal_pkg_t( 'Travellers' ) ); ?></span><span id="ifnpkg-sum-pax">&times; 0</span></li>
					<li class="iflynepal-pkg-total"><span><?php echo esc_html( iflynepal_pkg_t( 'Total' ) ); ?></span><output id="ifnpkg-sum-total"></output></li>
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
						<?php echo esc_html( iflynepal_pkg_t( 'Book now' ) ); ?>
						<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
					</a>
					<p class="iflynepal-pkg-sum-note" id="ifnpkg-book-note"><?php echo esc_html( iflynepal_pkg_t( 'Pick a start date and how many are travelling to continue.' ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $iflynepal_included ) || ! empty( $iflynepal_excluded ) ) : ?>
		<div class="iflynepal-pkg-price-split" data-iflynepal-anim>
			<?php if ( ! empty( $iflynepal_included ) ) : ?>
				<div class="iflynepal-pkg-price-col">
					<h3><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg><?php echo esc_html( iflynepal_pkg_t( 'Included in the price' ) ); ?></h3>
					<ul class="iflynepal-pkg-check-list">
						<?php foreach ( $iflynepal_included as $iflynepal_line ) : ?>
							<li><span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg></span><?php echo esc_html( $iflynepal_line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $iflynepal_excluded ) ) : ?>
				<div class="iflynepal-pkg-price-col">
					<h3><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-x"/></svg><?php echo esc_html( iflynepal_pkg_t( 'Not included' ) ); ?></h3>
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
