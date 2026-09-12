<?php
/**
 * The itinerary section of a package page.
 *
 * Two views of one set of days: the full day-by-day accordion, and the short
 * itinerary. Both are rendered, and the tab switch only shows and hides them —
 * so the whole itinerary is in the page with JavaScript off, and a printed page
 * carries everything.
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

$iflynepal_days = iflynepal_package_cards( $iflynepal_id, 'itinerary_days' );

if ( empty( $iflynepal_days ) ) {
	return;
}

$iflynepal_heading   = iflynepal_package_field( $iflynepal_id, 'itinerary_heading' );
$iflynepal_has_short = false;

foreach ( $iflynepal_days as $iflynepal_day ) {
	if ( '' !== $iflynepal_day['summary'] ) {
		$iflynepal_has_short = true;

		break;
	}
}
?>

<section class="iflynepal-pkg-t-section" id="ifnpkg-itinerary" aria-labelledby="ifnpkg-itinerary-h">
	<div class="iflynepal-pkg-t-head" data-iflynepal-anim>
		<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Itinerary', 'iflynepal' ); ?></span>
		<?php if ( '' !== $iflynepal_heading ) : ?>
			<h2 id="ifnpkg-itinerary-h"><?php iflynepal_package_the_heading( $iflynepal_heading ); ?></h2>
		<?php endif; ?>
	</div>

	<div class="iflynepal-pkg-itin-bar" data-iflynepal-anim>
		<?php
		/*
		 * The short itinerary leads. A visitor deciding whether this trip is the
		 * one wants the shape of it in two lines a day, and the hour-by-hour
		 * timeline is what they read once they have decided — so the summary is
		 * the tab that opens, and day-by-day is the one they reach for.
		 */
		?>
		<?php if ( $iflynepal_has_short ) : ?>
			<div class="iflynepal-pkg-seg" role="tablist" aria-label="<?php esc_attr_e( 'Itinerary view', 'iflynepal' ); ?>">
				<button type="button" role="tab" id="ifnpkg-tab-short" aria-controls="ifnpkg-panel-short" aria-selected="true"><?php esc_html_e( 'Short itinerary', 'iflynepal' ); ?></button>
				<button type="button" role="tab" id="ifnpkg-tab-full" aria-controls="ifnpkg-panel-full" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Day by day', 'iflynepal' ); ?></button>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Expand all acts on the day-by-day accordions, so it is hidden while the
		 * summary is showing — a control that opens things nobody can see reads
		 * as broken. The tab switch takes it from here; this is only its opening
		 * state, and a package with no short itinerary never hides it because
		 * there is no other panel to be on.
		 */
		?>
		<button class="iflynepal-pkg-text-btn" type="button" id="ifnpkg-expand-all" aria-expanded="false"<?php echo $iflynepal_has_short ? ' hidden' : ''; ?>><?php esc_html_e( 'Expand all', 'iflynepal' ); ?></button>
	</div>

	<?php if ( $iflynepal_has_short ) : ?>
		<div id="ifnpkg-panel-short" role="tabpanel" aria-labelledby="ifnpkg-tab-short" data-iflynepal-anim>
			<div class="iflynepal-pkg-summary-days">
				<?php foreach ( $iflynepal_days as $iflynepal_index => $iflynepal_day ) : ?>
					<?php if ( '' === $iflynepal_day['summary'] ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<div class="iflynepal-pkg-sum-day">
						<span class="iflynepal-pkg-day-badge">
							<small><?php esc_html_e( 'Day', 'iflynepal' ); ?></small>
							<b><?php echo esc_html( (string) ( $iflynepal_index + 1 ) ); ?></b>
						</span>
						<div>
							<h3><?php echo esc_html( $iflynepal_day['title'] ); ?></h3>
							<p><?php echo esc_html( $iflynepal_day['summary'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div id="ifnpkg-panel-full" role="tabpanel" aria-labelledby="ifnpkg-tab-full"<?php echo $iflynepal_has_short ? ' hidden' : ''; ?> data-iflynepal-anim>
		<?php foreach ( $iflynepal_days as $iflynepal_index => $iflynepal_day ) : ?>
			<?php
			$iflynepal_number   = $iflynepal_index + 1;
			$iflynepal_open     = 0 === $iflynepal_index;
			$iflynepal_panel_id = 'ifnpkg-day-' . $iflynepal_number;
			$iflynepal_stops    = iflynepal_package_timeline( $iflynepal_day['timeline'] );
			?>
			<article class="iflynepal-pkg-day<?php echo $iflynepal_open ? ' iflynepal-pkg-is-open' : ''; ?>">
				<h3 class="iflynepal-pkg-day-h">
					<button class="iflynepal-pkg-day-toggle" type="button" aria-expanded="<?php echo $iflynepal_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $iflynepal_panel_id ); ?>">
						<span class="iflynepal-pkg-day-num"><?php esc_html_e( 'Day', 'iflynepal' ); ?><b><?php echo esc_html( (string) $iflynepal_number ); ?></b></span>
						<span>
							<span class="iflynepal-pkg-dt-title"><?php echo esc_html( $iflynepal_day['title'] ); ?></span>
							<?php if ( '' !== $iflynepal_day['meta'] ) : ?>
								<span class="iflynepal-pkg-dt-sub"><?php echo esc_html( $iflynepal_day['meta'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="iflynepal-pkg-chev"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-down"/></svg></span>
					</button>
				</h3>

				<div class="iflynepal-pkg-day-panel" id="<?php echo esc_attr( $iflynepal_panel_id ); ?>">
					<div>
						<?php if ( ! empty( $iflynepal_stops ) ) : ?>
							<ol class="iflynepal-pkg-timeline">
								<?php foreach ( $iflynepal_stops as $iflynepal_stop ) : ?>
									<li>
										<time><?php echo esc_html( $iflynepal_stop['time'] ); ?></time>
										<p><?php echo esc_html( $iflynepal_stop['text'] ); ?></p>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>

						<?php
						/*
						 * The day's accommodation and meals notes were removed from
						 * the content model on the client's instruction, so the foot
						 * of the card they filled is gone with them. Nothing reads
						 * $day['stay'] or $day['meals'] any more: they are not
						 * declared parts, so iflynepal_package_cards() no longer
						 * puts them on a row and a template that asked for one would
						 * be reading a key that is not there.
						 */
						?>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

</section>
