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

if ( '' === $iflynepal_price && '' === $iflynepal_expert ) {
	return;
}

$iflynepal_currency = iflynepal_package_field( $iflynepal_id, 'price_currency' );
$iflynepal_points   = iflynepal_package_field_lines( $iflynepal_id, 'price_points' );
$iflynepal_inquire  = iflynepal_package_field( $iflynepal_id, 'inquire_link' );
$iflynepal_foot     = iflynepal_package_field( $iflynepal_id, 'price_foot' );
$iflynepal_eyebrow  = iflynepal_package_field( $iflynepal_id, 'price_eyebrow' );
?>

<aside class="iflynepal-pkg-trip-aside" aria-label="<?php esc_attr_e( 'Price and booking', 'iflynepal' ); ?>">
	<?php if ( '' !== $iflynepal_price ) : ?>
		<div class="iflynepal-pkg-price-card" id="ifnpkg-price-card">
			<?php if ( '' !== $iflynepal_eyebrow ) : ?>
				<span class="iflynepal-pkg-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></span>
			<?php endif; ?>

			<?php
			/*
			 * The price is split so the design can set the currency small and
			 * raised and the pence small beside the figure. Split here rather
			 * than asking an editor to type three fields: they type one number,
			 * and the presentation is the template's problem.
			 */
			$iflynepal_parts = explode( '.', number_format( (float) $iflynepal_price, 2, '.', ',' ) );
			?>
			<div class="iflynepal-pkg-price-from">
				<small><?php esc_html_e( 'From', 'iflynepal' ); ?></small>
				<strong>
					<?php if ( '' !== $iflynepal_currency ) : ?>
						<sup><?php echo esc_html( $iflynepal_currency ); ?></sup>
					<?php endif; ?>
					<?php echo esc_html( $iflynepal_parts[0] ); ?>
				</strong>
				<span>.<?php echo esc_html( $iflynepal_parts[1] ); ?> / <?php esc_html_e( 'person', 'iflynepal' ); ?></span>
			</div>

			<?php if ( ! empty( $iflynepal_points ) ) : ?>
				<ul class="iflynepal-pkg-check-list">
					<?php foreach ( $iflynepal_points as $iflynepal_point ) : ?>
						<li><span class="iflynepal-pkg-tick"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-check"/></svg></span><?php echo esc_html( $iflynepal_point ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="iflynepal-pkg-price-actions">
				<a class="iflynepal-pkg-button iflynepal-pkg-button--primary iflynepal-pkg-button--block" href="#ifnpkg-dates">
					<?php esc_html_e( 'Book now', 'iflynepal' ); ?>
					<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
				</a>

				<?php if ( '' !== $iflynepal_inquire ) : ?>
					<a class="iflynepal-pkg-button iflynepal-pkg-button--outline iflynepal-pkg-button--block" href="<?php echo esc_url( $iflynepal_inquire ); ?>">
						<?php esc_html_e( 'Inquire now', 'iflynepal' ); ?>
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
	<?php endif; ?>

	<?php if ( '' !== $iflynepal_expert ) : ?>
		<?php
		$iflynepal_photo = absint( iflynepal_package_field( $iflynepal_id, 'expert_image' ) );
		$iflynepal_place = iflynepal_package_field( $iflynepal_id, 'expert_place' );
		$iflynepal_label = iflynepal_package_field( $iflynepal_id, 'expert_label' );
		$iflynepal_link  = iflynepal_package_field( $iflynepal_id, 'expert_link' );
		?>
		<div class="iflynepal-pkg-expert">
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
				<small><?php esc_html_e( 'Speak to an expert', 'iflynepal' ); ?></small>
				<strong>
					<?php echo esc_html( $iflynepal_expert ); ?>
					<?php if ( '' !== $iflynepal_place ) : ?>
						<span>&middot; <?php echo esc_html( $iflynepal_place ); ?></span>
					<?php endif; ?>
				</strong>
			</div>

			<?php if ( '' !== $iflynepal_link && '' !== $iflynepal_label ) : ?>
				<a class="iflynepal-pkg-button iflynepal-pkg-button--block" href="<?php echo esc_url( $iflynepal_link ); ?>">
					<svg class="iflynepal-pkg-ico iflynepal-pkg-ico--fill" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8a9.1 9.1 0 0 0-7.9 13.7L2.8 21.2l4.8-1.3A9.1 9.1 0 1 0 12 2.8zm0 16.6c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A7.5 7.5 0 1 1 12 19.4zm4.1-5.6c-.2-.1-1.3-.7-1.6-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 2 .8 2.4.6 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.5-.3z"/></svg>
					<?php echo esc_html( $iflynepal_label ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</aside>
