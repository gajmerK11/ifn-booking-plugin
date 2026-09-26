<?php
/**
 * The booking aside — the price card and the expert card.
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

$iflynepal_price  = iflynepal_package_field( $iflynepal_id, 'price_amount' );
$iflynepal_expert = iflynepal_package_field( $iflynepal_id, 'expert_name' );

/*
 * Group-size pricing, when the package has any. A package with tiers prices
 * itself from them and the flat price_amount is not shown at all: two numbers
 * on one card, one of which is not what anybody would be charged, is worse than
 * either on its own. The card therefore renders when *either* is filled in.
 */
$iflynepal_tiers = iflynepal_package_price_tiers( $iflynepal_id );

/*
 * The aside used to return early when a package carried neither a price nor an
 * expert. It no longer can: Inquire now lives in it, and an enquiry is the one
 * thing every package has to offer whether or not anybody has typed a price yet
 * — a package with an empty price field would otherwise be a page with no way of
 * asking about it at all. Without a price card the enquiry card below stands in
 * its place, so the aside is never empty.
 */

$iflynepal_currency = iflynepal_package_field( $iflynepal_id, 'price_currency' );
$iflynepal_points   = iflynepal_package_field_lines( $iflynepal_id, 'price_points' );
$iflynepal_inquire  = iflynepal_package_field( $iflynepal_id, 'inquire_link' );
$iflynepal_foot     = iflynepal_package_field( $iflynepal_id, 'price_foot' );
$iflynepal_eyebrow  = iflynepal_package_field( $iflynepal_id, 'price_eyebrow' );

/*
 * Client-directed, 13 Sep 2026: the aside's Book now is the gateway's own
 * button once a package has one, not a link that scrolls to the Dates panel
 * and asks a visitor to find a second button there. Same markup, same helper
 * (includes/payment/payment-buttons.php) that templates/parts/package/dates.php
 * uses for the calendar's own copy — no payment logic is duplicated, only the
 * shortcode's rendered output is printed twice on the page.
 */
$iflynepal_pay = iflynepal_package_payment_markup( $iflynepal_id );

/*
 * The price is split so the design can set the currency small and raised
 * beside the figure. Split here rather than asking an editor to type two
 * fields: they type one number, and the presentation is the template's
 * problem. It is a closure because the card prints a figure once with a flat
 * price and once per rung with a ladder, and one of the two would otherwise
 * be a copy of the other's typography.
 *
 * Whole numbers only, client-directed: a price of 3360 reads as "3,360 /
 * person", not "3,360.00 / person" — nobody prices a trip to the cent, and
 * the trailing .00 was also what pushed "/ person" onto its own line for a
 * four-digit price, since the flex row it shares with the figure had to fit
 * ".00 / person" as well as the wider number.
 */
$iflynepal_figure = static function ( $iflynepal_amount, $iflynepal_unit ) use ( $iflynepal_currency ) {
	?>
	<strong>
		<?php if ( '' !== $iflynepal_currency ) : ?>
			<sup><?php echo esc_html( $iflynepal_currency ); ?></sup>
		<?php endif; ?>
		<?php echo esc_html( number_format( (float) $iflynepal_amount, 0, '.', ',' ) ); ?>
	</strong>
	<span>/ <?php echo esc_html( $iflynepal_unit ); ?></span>
	<?php
};
?>

<aside class="iflynepal-pkg-trip-aside" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Price and booking' ) ); ?>">
	<?php if ( '' !== $iflynepal_price || $iflynepal_tiers ) : ?>
		<div class="iflynepal-pkg-price-card" id="ifnpkg-price-card">
			<?php if ( '' !== $iflynepal_eyebrow ) : ?>
				<span class="iflynepal-pkg-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></span>
			<?php endif; ?>

			<?php if ( $iflynepal_tiers ) : ?>
				<?php
				/*
				 * The ladder, client-directed as plain lines rather than boxed rungs:
				 * a normal price, then a price per group size, set in the card's own
				 * type. Every rung is on the page from the first frame, because the
				 * discount for bringing more people is the thing being sold and a
				 * visitor who never touches the stepper below should still see it —
				 * the rung matching the current traveller count is only the one set
				 * large.
				 *
				 * The bounds and the price are on each line as data attributes:
				 * package.js re-reads them when the stepper changes rather than being
				 * handed a second copy of the same ladder, so the card and the
				 * calculator cannot drift apart. With JavaScript off the first rung
				 * stays large and every rung is still legible, which is the whole
				 * ladder either way.
				 */

				/*
				 * What the trip costs before any group discount, stated once at the
				 * top rather than struck through beside every rung. The flat price
				 * field is it where one is typed — that is exactly what that field
				 * has always meant — and the highest "before" price on the ladder
				 * stands in where it is not, so a package priced only by tiers still
				 * says what the discount is a discount from.
				 */
				$iflynepal_normal = (float) $iflynepal_price;

				foreach ( $iflynepal_tiers as $iflynepal_tier ) {
					$iflynepal_normal = max( $iflynepal_normal, $iflynepal_tier['was'] );
				}
				?>
				<div class="iflynepal-pkg-price-tiers" id="ifnpkg-price-tiers">
					<?php if ( $iflynepal_normal > 0 ) : ?>
						<p class="iflynepal-pkg-price-normal">
							<span><?php echo esc_html( iflynepal_pkg_t( 'Normal price:' ) ); ?></span>
							<s><?php echo esc_html( ( '' !== $iflynepal_currency ? $iflynepal_currency . ' ' : '' ) . number_format( $iflynepal_normal, 2, '.', ',' ) ); ?></s>
						</p>
					<?php endif; ?>

					<?php foreach ( $iflynepal_tiers as $iflynepal_index => $iflynepal_tier ) : ?>
						<div class="iflynepal-pkg-price-tier<?php echo 0 === $iflynepal_index ? ' iflynepal-pkg-is-active' : ''; ?>"
							data-from="<?php echo esc_attr( (string) $iflynepal_tier['from'] ); ?>"
							data-to="<?php echo esc_attr( (string) $iflynepal_tier['to'] ); ?>"
							data-price="<?php echo esc_attr( (string) $iflynepal_tier['price'] ); ?>">
							<span class="iflynepal-pkg-price-tier__pax">
								<?php
								printf( esc_html( iflynepal_pkg_t( '%s price:' ) ), esc_html( $iflynepal_tier['label'] ) );
								?>
							</span>
							<span class="iflynepal-pkg-price-from">
								<?php $iflynepal_figure( $iflynepal_tier['price'], iflynepal_pkg_t( 'pax' ) ); ?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="iflynepal-pkg-price-from">
					<small><?php echo esc_html( iflynepal_pkg_t( 'From' ) ); ?></small>
					<?php $iflynepal_figure( $iflynepal_price, iflynepal_pkg_t( 'person' ) ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $iflynepal_points ) ) : ?>
				<ul class="iflynepal-pkg-check-list">
					<?php foreach ( $iflynepal_points as $iflynepal_point ) : ?>
						<li><span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg></span><?php echo esc_html( $iflynepal_point ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="iflynepal-pkg-price-actions">
				<?php if ( '' !== $iflynepal_pay ) : ?>
					<?php
					/*
					 * Client-directed, 13 Sep 2026: the button does not work
					 * until a start date AND at least one traveller are picked
					 * below — the same principle the old inert Book now
					 * already used when no gateway button existed at all,
					 * applied here to a real one. Travellers default to zero
					 * rather than one, so this is the ordinary starting state
					 * for every visitor, not an edge case.
					 *
					 * `iflynepal-pkg-is-locked` is CSS only (pointer-events:
					 * none — see assets/css/package.css), which is what makes
					 * this gateway-agnostic: it works whether the markup
					 * underneath is a plain form or a script-rendered button
					 * in an iframe, neither of which this plugin can reach
					 * into to disable directly. package.js's total() toggles
					 * the class both ways — a traveller count that drops back
					 * to zero re-locks the button just as picking a date
					 * unlocked it. This is a UX nudge, not enforcement, the
					 * same standing as every other rule in this plugin:
					 * nothing stops the amount or the payment itself, only
					 * when the button becomes clickable.
					 */
					?>
					<div class="iflynepal-pkg-pay iflynepal-pkg-is-locked" id="ifnpkg-pay-aside" aria-disabled="true">
						<?php
						// Rendered by the gateway plugin's own shortcode handler, which escapes its own output.
						echo $iflynepal_pay; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
					<p class="iflynepal-pkg-sum-note" id="ifnpkg-pay-note"><?php echo esc_html( iflynepal_pkg_t( 'Pick a start date and how many are travelling below to book.' ) ); ?></p>
				<?php else : ?>
					<a class="iflynepal-pkg-button iflynepal-pkg-button--primary iflynepal-pkg-button--block" href="#ifnpkg-dates">
						<?php echo esc_html( iflynepal_pkg_t( 'Book now' ) ); ?>
						<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
					</a>
				<?php endif; ?>

				<?php
				/*
				 * Inquire now opens the enquiry form, which is rendered in the
				 * page footer by includes/enquiry/enquiry-form.php. It is not
				 * conditional on anything: an enquiry is the one thing every
				 * package has to offer.
				 *
				 * It is an anchor rather than a button, and its href is the
				 * form's own id, because with JavaScript off the form is an
				 * ordinary section at the foot of the page and this jumps to it
				 * — the enquiry still gets sent. enquiry.js intercepts the click
				 * and opens it as a modal instead.
				 */
				?>
				<a class="iflynepal-pkg-button iflynepal-pkg-button--outline iflynepal-pkg-button--block"
					href="#iflynepal-enquiry" data-iflynepal-enquiry-open>
					<?php echo esc_html( iflynepal_pkg_t( 'Inquire now' ) ); ?>
				</a>

				<?php
				/*
				 * The WhatsApp button is built from a number, not from a pasted
				 * link: the number lives once at Packages > Settings (a package
				 * may override it), and the message it opens the chat with is
				 * assembled by iflynepal_whatsapp_url() with the package's name
				 * filled in. Nothing is shown when no number is configured.
				 *
				 * ⚠ The Inquire link field used to *be* the Inquire now button,
				 * so a package with a link in it sent Inquire now wherever that
				 * link pointed and the enquiry form could not be reached at
				 * all. It is kept here only as a fallback for the wa.me links
				 * already stored in it, so nothing published stops working
				 * before the number is filled in — see the report.
				 */
				$iflynepal_whatsapp = iflynepal_whatsapp_url( $iflynepal_id );

				if ( '' === $iflynepal_whatsapp && '' !== $iflynepal_inquire ) {
					$iflynepal_host = strtolower( (string) wp_parse_url( $iflynepal_inquire, PHP_URL_HOST ) );

					if ( in_array( $iflynepal_host, array( 'wa.me', 'api.whatsapp.com', 'web.whatsapp.com' ), true ) ) {
						$iflynepal_whatsapp = $iflynepal_inquire;
					}
				}
				?>

				<?php if ( '' !== $iflynepal_whatsapp ) : ?>
					<a class="iflynepal-pkg-button iflynepal-pkg-button--outline iflynepal-pkg-button--block"
						<?php echo iflynepal_booking_anchor_attr( $iflynepal_whatsapp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?>
						target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( iflynepal_pkg_t( 'Chat on WhatsApp' ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $iflynepal_foot ) : ?>
				<p class="iflynepal-pkg-price-foot">
					<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-shield"/></svg>
					<?php echo esc_html( $iflynepal_foot ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<?php
		/*
		 * The stand-in for the price card. Same trigger, so a package priced
		 * later simply gains the card above and loses this one.
		 */
		?>
		<div class="iflynepal-enquiry-card">
			<h2 class="iflynepal-enquiry-card__title"><?php echo esc_html( iflynepal_pkg_t( 'Ask us about this journey' ) ); ?></h2>
			<p class="iflynepal-enquiry-card__text">
				<?php echo esc_html( iflynepal_pkg_t( 'Tell us your dates and group size and we will send you a price.' ) ); ?>
			</p>
			<a class="iflynepal-pkg-button iflynepal-pkg-button--primary iflynepal-pkg-button--block"
				href="#iflynepal-enquiry" data-iflynepal-enquiry-open>
				<?php echo esc_html( iflynepal_pkg_t( 'Inquire now' ) ); ?>
			</a>

			<?php $iflynepal_chat = iflynepal_whatsapp_url( $iflynepal_id ); ?>

			<?php if ( '' !== $iflynepal_chat ) : ?>
				<a class="iflynepal-enquiry-card__chat" <?php echo iflynepal_booking_anchor_attr( $iflynepal_chat ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?> target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( iflynepal_pkg_t( 'Or chat on WhatsApp' ) ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $iflynepal_expert ) : ?>
		<?php
		$iflynepal_photo = absint( iflynepal_package_field( $iflynepal_id, 'expert_image' ) );
		$iflynepal_place = iflynepal_package_field( $iflynepal_id, 'expert_place' );
		$iflynepal_label = iflynepal_package_field( $iflynepal_id, 'expert_label' );
		$iflynepal_link  = iflynepal_package_field( $iflynepal_id, 'expert_link' );
		?>
		<div class="iflynepal-pkg-expert<?php echo $iflynepal_photo ? '' : ' iflynepal-pkg-expert--no-photo'; ?>">
			<?php
			if ( $iflynepal_photo ) {
				// Core-escaped markup. The alt names the person, who is the subject here.
				echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$iflynepal_photo,
					'thumbnail',
					false,
					array(
						'loading' => 'lazy',
						'alt'     => $iflynepal_expert,
					)
				);
			}
			?>

			<div>
				<small><?php echo esc_html( iflynepal_pkg_t( 'Speak to an expert' ) ); ?></small>
				<strong>
					<?php echo esc_html( $iflynepal_expert ); ?>
					<?php if ( '' !== $iflynepal_place ) : ?>
						<span>&middot; <?php echo esc_html( $iflynepal_place ); ?></span>
					<?php endif; ?>
				</strong>
			</div>

			<?php if ( '' !== $iflynepal_link && '' !== $iflynepal_label ) : ?>
				<a class="iflynepal-pkg-button iflynepal-pkg-button--block" <?php echo iflynepal_booking_anchor_attr( $iflynepal_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?>>
					<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8a9.1 9.1 0 0 0-7.9 13.7L2.8 21.2l4.8-1.3A9.1 9.1 0 1 0 12 2.8zm0 16.6c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A7.5 7.5 0 1 1 12 19.4zm4.1-5.6c-.2-.1-1.3-.7-1.6-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 2 .8 2.4.6 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.5-.3z"/></svg>
					<?php echo esc_html( $iflynepal_label ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</aside>
