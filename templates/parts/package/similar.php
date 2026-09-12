<?php
/**
 * The similar-packages rail.
 *
 * A query, not fields: the other packages filed under this one's own type. The
 * card is the design's trip card, built from the same Package Card fields the
 * catalogue grid uses, so a package written once looks right everywhere it
 * appears.
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

$iflynepal_related = iflynepal_package_related( $iflynepal_id );

if ( empty( $iflynepal_related ) ) {
	return;
}
?>

<section class="iflynepal-pkg-band iflynepal-pkg-band--mist" id="ifnpkg-similar" aria-labelledby="ifnpkg-similar-h">
	<div>
		<div class="iflynepal-pkg-rail-head" data-iflynepal-anim>
			<div>
				<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Keep exploring', 'iflynepal' ); ?></span>
				<?php
				/*
				 * The design draws its hand underline beneath one word of this
				 * heading. There is no field behind it — this is the only
				 * heading on the page the template writes rather than an editor
				 * — so the mark travels inside the translatable string, whole
				 * sentence and all. A translator moves the span to whichever
				 * word carries the sense in their language, which is exactly
				 * what splitting it into a printf would take away from them.
				 */
				?>
				<h2 id="ifnpkg-similar-h">
					<?php
					echo wp_kses( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses escapes.
						__( 'Similar <span class="iflynepal-ink-mark">packages</span> you may like.', 'iflynepal' ),
						array( 'span' => array( 'class' => array() ) )
					);
					?>
				</h2>
			</div>

			<?php
			/*
			 * Rendered disabled, as the departures rail is: the rail scrolls by
			 * touch, wheel and keyboard on its own, so the buttons are an
			 * enhancement the script switches on.
			 */
			?>
			<div class="iflynepal-pkg-rail-arrows">
				<button type="button" id="ifnpkg-rail-prev" disabled aria-label="<?php esc_attr_e( 'Previous packages', 'iflynepal' ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-left"/></svg></button>
				<button type="button" id="ifnpkg-rail-next" disabled aria-label="<?php esc_attr_e( 'Next packages', 'iflynepal' ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-right"/></svg></button>
			</div>
		</div>

		<div class="iflynepal-pkg-rail" id="ifnpkg-rail" tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Similar packages', 'iflynepal' ); ?>">
			<?php foreach ( $iflynepal_related as $iflynepal_package ) : ?>
				<?php
				$iflynepal_pid  = $iflynepal_package->ID;
				$iflynepal_pill = iflynepal_package_field( $iflynepal_pid, 'pill' );
				$iflynepal_peek = iflynepal_package_field( $iflynepal_pid, 'peek' );
				$iflynepal_dur  = iflynepal_package_field( $iflynepal_pid, 'duration' );
				$iflynepal_suit = iflynepal_package_field( $iflynepal_pid, 'suitability' );
				$iflynepal_cost = iflynepal_package_field( $iflynepal_pid, 'price' );
				?>
				<article class="iflynepal-pkg-trip-card" data-iflynepal-anim>
					<div class="iflynepal-pkg-trip-img">
						<?php
						if ( has_post_thumbnail( $iflynepal_pid ) ) {
							// Core-escaped markup.
							echo get_the_post_thumbnail( $iflynepal_pid, 'medium_large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>

						<?php if ( '' !== $iflynepal_pill ) : ?>
							<span class="iflynepal-pkg-pill"><?php echo esc_html( $iflynepal_pill ); ?></span>
						<?php endif; ?>

						<?php if ( '' !== $iflynepal_peek ) : ?>
							<div class="iflynepal-pkg-trip-peek"><p><?php echo esc_html( $iflynepal_peek ); ?></p></div>
						<?php endif; ?>
					</div>

					<div class="iflynepal-pkg-trip-body">
						<?php if ( '' !== $iflynepal_dur || '' !== $iflynepal_suit ) : ?>
							<div class="iflynepal-pkg-trip-meta">
								<?php if ( '' !== $iflynepal_dur ) : ?>
									<span><?php echo esc_html( $iflynepal_dur ); ?></span>
								<?php endif; ?>
								<?php if ( '' !== $iflynepal_suit ) : ?>
									<span><?php echo esc_html( $iflynepal_suit ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<h3><?php echo esc_html( get_the_title( $iflynepal_pid ) ); ?></h3>

						<div class="iflynepal-pkg-trip-foot">
							<?php if ( '' !== $iflynepal_cost ) : ?>
								<strong><?php echo esc_html( $iflynepal_cost ); ?></strong>
							<?php endif; ?>

							<a href="<?php echo esc_url( get_permalink( $iflynepal_pid ) ); ?>">
								<?php esc_html_e( 'View', 'iflynepal' ); ?>
								<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
							</a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
