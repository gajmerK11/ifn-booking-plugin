<?php
/**
 * One package.
 *
 * The booking button is the gateway plugin's own shortcode, pasted per package
 * and rendered through do_shortcode(). Nothing about the payment is
 * reimplemented here: the button is the gateway's, the redirect is the
 * gateway's, and no card detail passes through this template or this server.
 *
 * The confirmation notice beside it is static text. It is the client's "5–6 day
 * buffer" and it informs, it does not delay: no date is validated against it and
 * nothing is held.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$iflynepal_post_id   = get_the_ID();
	$iflynepal_type      = iflynepal_package_primary_type( $iflynepal_post_id );
	$iflynepal_price     = iflynepal_package_field( $iflynepal_post_id, 'price' );
	$iflynepal_notice    = iflynepal_package_field( $iflynepal_post_id, 'buffer_notice' );
	$iflynepal_booking   = iflynepal_package_field( $iflynepal_post_id, 'booking' );
	$iflynepal_highlight = iflynepal_package_field_lines( $iflynepal_post_id, 'highlights' );
	$iflynepal_dates     = iflynepal_package_upcoming_departures( $iflynepal_post_id, 6 );

	$iflynepal_facts = array_filter(
		array(
			iflynepal_package_field( $iflynepal_post_id, 'duration' ),
			iflynepal_package_field( $iflynepal_post_id, 'suitability' ),
			iflynepal_package_field( $iflynepal_post_id, 'pill' ),
		),
		'strlen'
	);
	?>

<main id="main" class="iflynepal-package">
	<?php // Same component as every other hero on the site — see templates/parts/archive/hero.php. ?>
	<section class="wp-block-cover iflynepal-hero iflynepal-hero--page iflynepal-hero--package">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="iflynepal-hero__media" aria-hidden="true">
				<?php
				the_post_thumbnail(
					'full',
					array(
						'class'         => 'iflynepal-hero__still',
						'alt'           => '',
						'fetchpriority' => 'high',
						'loading'       => 'eager',
						'decoding'      => 'sync',
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="wp-block-cover__inner-container">
			<div class="wp-block-group iflynepal-hero__copy">
				<?php if ( $iflynepal_type instanceof WP_Term ) : ?>
					<p class="iflynepal-hero__kicker">
						<a href="<?php echo esc_url( get_term_link( $iflynepal_type ) ); ?>">
							<?php echo esc_html( $iflynepal_type->name ); ?>
						</a>
					</p>
				<?php endif; ?>

				<h1 class="wp-block-heading iflynepal-hero__title"><?php the_title(); ?></h1>

				<?php if ( ! empty( $iflynepal_facts ) ) : ?>
					<div class="iflynepal-card__meta">
						<?php foreach ( $iflynepal_facts as $iflynepal_fact ) : ?>
							<span><?php echo esc_html( $iflynepal_fact ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="iflynepal-section">
		<div class="iflynepal-container iflynepal-package__layout">
			<div class="iflynepal-package__content">
				<?php the_content(); ?>

				<?php if ( ! empty( $iflynepal_highlight ) ) : ?>
					<h2><?php esc_html_e( 'Highlights', 'iflynepal' ); ?></h2>
					<ul class="iflynepal-package__highlights">
						<?php foreach ( $iflynepal_highlight as $iflynepal_line ) : ?>
							<li><?php echo esc_html( $iflynepal_line ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<aside class="iflynepal-package__aside">
				<div class="iflynepal-booking-card">
					<?php if ( '' !== $iflynepal_price ) : ?>
						<p class="iflynepal-booking-card__price"><?php echo esc_html( $iflynepal_price ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $iflynepal_dates ) ) : ?>
						<p class="iflynepal-booking-card__departures">
							<b><?php esc_html_e( 'Upcoming departures', 'iflynepal' ); ?></b>
							<?php
							$iflynepal_formatted = array_map( 'iflynepal_booking_format_date', $iflynepal_dates );
							echo esc_html( implode( ', ', $iflynepal_formatted ) );
							?>
						</p>
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_booking ) : ?>
						<div class="iflynepal-booking-card__button">
							<?php echo do_shortcode( $iflynepal_booking ); // gateway shortcode output. ?>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_notice ) : ?>
						<p class="iflynepal-booking-card__notice"><?php echo esc_html( $iflynepal_notice ); ?></p>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>

	<?php
	// Other packages of the same type, the current one left out.
	if ( $iflynepal_type instanceof WP_Term ) :
		$iflynepal_related = array();

		foreach ( iflynepal_archive_packages( $iflynepal_type->term_id, 7 ) as $iflynepal_candidate ) {
			if ( $iflynepal_candidate->ID !== $iflynepal_post_id ) {
				$iflynepal_related[] = $iflynepal_candidate;
			}
		}

		$iflynepal_related = array_slice( $iflynepal_related, 0, 3 );

		if ( ! empty( $iflynepal_related ) ) :
			?>
			<section class="iflynepal-section iflynepal-section--mist">
				<div class="iflynepal-container">
					<div class="iflynepal-section-head">
						<h2><?php esc_html_e( 'More like this', 'iflynepal' ); ?></h2>
					</div>

					<div class="iflynepal-cards">
						<?php
						foreach ( $iflynepal_related as $iflynepal_card ) {
							iflynepal_booking_get_part( 'parts/card-package', array( 'package' => $iflynepal_card ) );
						}
						?>
					</div>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>
</main>

	<?php
endwhile;

get_footer();
