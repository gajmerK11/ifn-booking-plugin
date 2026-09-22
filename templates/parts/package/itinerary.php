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

/*
 * A package is paced in days or in weeks, never both — whichever of the two
 * card fields has entries wins, Days first. A week card carries no Elevation
 * and no Timeline (see itinerary_weeks in package-details-schema.php), so
 * the altitude chart and the day-by-day stops below simply have nothing to
 * draw for one and are left off, the same opt-in rule as everywhere else on
 * this page.
 */
$iflynepal_days  = iflynepal_package_cards( $iflynepal_id, 'itinerary_days' );
$iflynepal_weeks = empty( $iflynepal_days ) ? iflynepal_package_cards( $iflynepal_id, 'itinerary_weeks' ) : array();
$iflynepal_items = empty( $iflynepal_days ) ? $iflynepal_weeks : $iflynepal_days;

if ( empty( $iflynepal_items ) ) {
	return;
}

$iflynepal_unit = empty( $iflynepal_days ) ? __( 'Week', 'iflynepal' ) : __( 'Day', 'iflynepal' );

$iflynepal_heading  = iflynepal_package_field( $iflynepal_id, 'itinerary_heading' );
$iflynepal_altitude = iflynepal_package_altitude_profile( $iflynepal_id );

$iflynepal_has_short = false;

foreach ( $iflynepal_items as $iflynepal_item ) {
	if ( '' !== $iflynepal_item['summary'] ) {
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

	<?php if ( ! empty( $iflynepal_altitude ) ) : ?>
		<?php
		$iflynepal_alt_label_bits = array();

		foreach ( $iflynepal_altitude['points'] as $iflynepal_alt_point ) {
			$iflynepal_alt_label_bits[] = sprintf(
				/* translators: 1: day number, 2: elevation in metres. */
				__( 'day %1$d, %2$s metres', 'iflynepal' ),
				$iflynepal_alt_point['day'],
				number_format_i18n( $iflynepal_alt_point['metres'] )
			);
		}

		$iflynepal_alt_label = sprintf(
			/* translators: %s: a comma-separated list of "day N, X metres". */
			__( 'Line chart of altitude by day: %s.', 'iflynepal' ),
			implode( ', ', $iflynepal_alt_label_bits )
		);

		$iflynepal_alt_area = $iflynepal_altitude['left'] . ',' . $iflynepal_altitude['bottom'];

		foreach ( $iflynepal_altitude['points'] as $iflynepal_alt_point ) {
			$iflynepal_alt_area .= ' ' . $iflynepal_alt_point['x'] . ',' . $iflynepal_alt_point['y'];
		}

		$iflynepal_alt_area .= ' ' . $iflynepal_altitude['right'] . ',' . $iflynepal_altitude['bottom'];

		$iflynepal_alt_line = array();

		foreach ( $iflynepal_altitude['points'] as $iflynepal_alt_point ) {
			$iflynepal_alt_line[] = $iflynepal_alt_point['x'] . ',' . $iflynepal_alt_point['y'];
		}

		$iflynepal_alt_peak = $iflynepal_altitude['points'][ $iflynepal_altitude['peak_index'] ];
		?>
		<div class="iflynepal-pkg-alt-card" data-iflynepal-anim>
			<div class="iflynepal-pkg-alt-head">
				<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Altitude profile', 'iflynepal' ); ?></span>
			</div>
			<div class="iflynepal-pkg-alt-scroll">
				<svg class="iflynepal-pkg-alt-svg" viewBox="0 0 660 230" role="img" aria-label="<?php echo esc_attr( $iflynepal_alt_label ); ?>">
					<defs>
						<linearGradient id="ifnpkg-alt-fill" x1="0" y1="0" x2="0" y2="1">
							<stop offset="0" stop-color="#0B58D5" stop-opacity=".22"/>
							<stop offset="1" stop-color="#0B58D5" stop-opacity="0"/>
						</linearGradient>
					</defs>

					<g class="iflynepal-pkg-alt-grid">
						<?php foreach ( $iflynepal_altitude['gridlines'] as $iflynepal_alt_grid ) : ?>
							<line x1="<?php echo esc_attr( (string) $iflynepal_altitude['left'] ); ?>" x2="<?php echo esc_attr( (string) $iflynepal_altitude['right'] ); ?>" y1="<?php echo esc_attr( (string) $iflynepal_alt_grid['y'] ); ?>" y2="<?php echo esc_attr( (string) $iflynepal_alt_grid['y'] ); ?>"/>
							<text x="<?php echo esc_attr( (string) ( $iflynepal_altitude['left'] - 10 ) ); ?>" y="<?php echo esc_attr( (string) ( $iflynepal_alt_grid['y'] + 4 ) ); ?>" text-anchor="end"><?php echo esc_html( number_format_i18n( $iflynepal_alt_grid['metres'] ) . 'm' ); ?></text>
						<?php endforeach; ?>
					</g>

					<polygon class="iflynepal-pkg-alt-area" points="<?php echo esc_attr( $iflynepal_alt_area ); ?>" fill="url(#ifnpkg-alt-fill)"/>
					<polyline class="iflynepal-pkg-alt-line" points="<?php echo esc_attr( implode( ' ', $iflynepal_alt_line ) ); ?>"/>

					<g class="iflynepal-pkg-alt-dots">
						<?php foreach ( $iflynepal_altitude['points'] as $iflynepal_alt_index => $iflynepal_alt_point ) : ?>
							<?php $iflynepal_alt_is_peak = ( $iflynepal_alt_index === $iflynepal_altitude['peak_index'] ); ?>
							<circle class="<?php echo esc_attr( $iflynepal_alt_is_peak ? 'iflynepal-pkg-is-peak' : '' ); ?>" cx="<?php echo esc_attr( (string) $iflynepal_alt_point['x'] ); ?>" cy="<?php echo esc_attr( (string) $iflynepal_alt_point['y'] ); ?>" r="<?php echo esc_attr( $iflynepal_alt_is_peak ? '6' : '3.5' ); ?>"><title><?php echo esc_html( sprintf( /* translators: 1: day number, 2: elevation in metres. */ __( 'Day %1$d: %2$sm', 'iflynepal' ), $iflynepal_alt_point['day'], number_format_i18n( $iflynepal_alt_point['metres'] ) ) ); ?></title></circle>
						<?php endforeach; ?>
					</g>

					<g class="iflynepal-pkg-alt-flag">
						<g transform="translate(<?php echo esc_attr( (string) $iflynepal_alt_peak['x'] ); ?> <?php echo esc_attr( (string) $iflynepal_alt_peak['y'] ); ?>)">
							<line y1="-8" y2="-18"/>
							<text y="-24" text-anchor="middle"><?php echo esc_html( trim( $iflynepal_alt_peak['title'] . ' ' . number_format_i18n( $iflynepal_alt_peak['metres'] ) . 'm' ) ); ?></text>
						</g>
					</g>

					<g class="iflynepal-pkg-alt-days">
						<text x="<?php echo esc_attr( (string) ( $iflynepal_altitude['left'] - 10 ) ); ?>" y="<?php echo esc_attr( (string) $iflynepal_altitude['axis_y'] ); ?>" text-anchor="end"><?php esc_html_e( 'Day', 'iflynepal' ); ?></text>
						<?php foreach ( $iflynepal_altitude['points'] as $iflynepal_alt_index => $iflynepal_alt_point ) : ?>
							<text class="<?php echo esc_attr( $iflynepal_alt_index === $iflynepal_altitude['peak_index'] ? 'iflynepal-pkg-is-peak' : '' ); ?>" x="<?php echo esc_attr( (string) $iflynepal_alt_point['x'] ); ?>" y="<?php echo esc_attr( (string) $iflynepal_altitude['axis_y'] ); ?>" text-anchor="middle"><?php echo esc_html( (string) $iflynepal_alt_point['day'] ); ?></text>
						<?php endforeach; ?>
					</g>
				</svg>
			</div>
		</div>
	<?php endif; ?>

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
				<span class="iflynepal-pkg-seg-fill" id="ifnpkg-seg-fill" aria-hidden="true"></span>
				<button type="button" role="tab" id="ifnpkg-tab-short" aria-controls="ifnpkg-panel-short" aria-selected="true"><?php esc_html_e( 'Short itinerary', 'iflynepal' ); ?></button>
				<button type="button" role="tab" id="ifnpkg-tab-full" aria-controls="ifnpkg-panel-full" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Detailed Itinerary', 'iflynepal' ); ?></button>
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
				<?php foreach ( $iflynepal_items as $iflynepal_index => $iflynepal_item ) : ?>
					<?php if ( '' === $iflynepal_item['summary'] ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<?php
					/*
					 * The badge reads whatever the card's label says, and counts the
					 * cards when it says nothing — see the `number` part in
					 * package-details-schema.php. Anything longer than a plain
					 * number ("3-4") is set smaller so it still fits the badge.
					 */
					$iflynepal_badge = trim( (string) $iflynepal_item['number'] );

					if ( '' === $iflynepal_badge ) {
						$iflynepal_badge = (string) ( $iflynepal_index + 1 );
					}
					?>
					<div class="iflynepal-pkg-sum-day">
						<span class="iflynepal-pkg-day-badge<?php echo strlen( $iflynepal_badge ) > 2 ? ' iflynepal-pkg-is-wide' : ''; ?>">
							<small><?php echo esc_html( $iflynepal_unit ); ?></small>
							<b><?php echo esc_html( $iflynepal_badge ); ?></b>
						</span>
						<div>
							<h3><?php echo esc_html( $iflynepal_item['title'] ); ?></h3>
							<p><?php echo esc_html( $iflynepal_item['summary'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div id="ifnpkg-panel-full" role="tabpanel" aria-labelledby="ifnpkg-tab-full"<?php echo $iflynepal_has_short ? ' hidden' : ''; ?> data-iflynepal-anim>
		<?php foreach ( $iflynepal_items as $iflynepal_index => $iflynepal_item ) : ?>
			<?php
			$iflynepal_number   = $iflynepal_index + 1;
			$iflynepal_badge    = trim( (string) $iflynepal_item['number'] );
			$iflynepal_badge    = '' === $iflynepal_badge ? (string) $iflynepal_number : $iflynepal_badge;
			$iflynepal_open     = 0 === $iflynepal_index;
			$iflynepal_panel_id = 'ifnpkg-day-' . $iflynepal_number;
			$iflynepal_stops    = isset( $iflynepal_item['timeline'] ) ? iflynepal_package_timeline( $iflynepal_item['timeline'] ) : array();
			$iflynepal_prose    = isset( $iflynepal_item['description'] ) ? trim( (string) $iflynepal_item['description'] ) : '';
			?>
			<article class="iflynepal-pkg-day<?php echo $iflynepal_open ? ' iflynepal-pkg-is-open' : ''; ?>">
				<h3 class="iflynepal-pkg-day-h">
					<button class="iflynepal-pkg-day-toggle" type="button" aria-expanded="<?php echo $iflynepal_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $iflynepal_panel_id ); ?>">
						<span class="iflynepal-pkg-day-num<?php echo strlen( $iflynepal_badge ) > 2 ? ' iflynepal-pkg-is-wide' : ''; ?>"><?php echo esc_html( $iflynepal_unit ); ?><b><?php echo esc_html( $iflynepal_badge ); ?></b></span>
						<?php
						/*
						 * A day with no summary line under its title is one line, not
						 * two, and a one-line block left at the top of a 52px badge
						 * reads as a missing second line. The modifier centres it
						 * against the badge and sets it a little larger, which is the
						 * whole of the difference.
						 */
						?>
						<span class="<?php echo '' === $iflynepal_item['meta'] ? 'iflynepal-pkg-dt--solo' : ''; ?>">
							<span class="iflynepal-pkg-dt-title"><?php echo esc_html( $iflynepal_item['title'] ); ?></span>
							<?php if ( '' !== $iflynepal_item['meta'] ) : ?>
								<span class="iflynepal-pkg-dt-sub"><?php echo esc_html( $iflynepal_item['meta'] ); ?></span>
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
						 * The day told in prose, under its stops, laid out as it was
						 * written: the part is a wp_editor(), so its paragraphs,
						 * lists and emphasis arrive as markup and are printed rather
						 * than rebuilt. wpautop() still runs, for the days written
						 * before the editor replaced the plain textarea — it wraps
						 * the loose lines those hold and leaves anything already in
						 * a block tag alone. wp_kses_post() is what keeps this to
						 * the tags the toolbar can make; it is the same pass the
						 * value already went through on save.
						 */
						?>
						<?php if ( '' !== $iflynepal_prose ) : ?>
							<div class="iflynepal-pkg-day-prose">
								<?php echo wp_kses_post( wpautop( $iflynepal_prose ) ); ?>
							</div>
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
