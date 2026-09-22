<?php
/**
 * The packing list and the map.
 *
 * One band holding both, as the design has it. Either half can be absent: a
 * package with no map embed gets the packing list at full width rather than a
 * grey rectangle where a map would be.
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

$iflynepal_items = iflynepal_package_field_lines( $iflynepal_id, 'packing_items' );
$iflynepal_embed = iflynepal_package_field( $iflynepal_id, 'map_embed' );

if ( empty( $iflynepal_items ) && '' === $iflynepal_embed ) {
	return;
}
?>

<section class="iflynepal-pkg-band iflynepal-pkg-band--mist" id="ifnpkg-packing" aria-labelledby="ifnpkg-packing-h">
	<div class="iflynepal-pkg-pack-map">
		<?php if ( ! empty( $iflynepal_items ) ) : ?>
			<div data-iflynepal-anim>
				<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Packing list', 'iflynepal' ); ?></span>
				<?php $iflynepal_heading = iflynepal_package_field( $iflynepal_id, 'packing_heading' ); ?>
				<?php if ( '' !== $iflynepal_heading ) : ?>
					<h2 id="ifnpkg-packing-h" class="iflynepal-pkg-band-h"><?php iflynepal_package_the_heading( $iflynepal_heading ); ?></h2>
				<?php endif; ?>

				<ul class="iflynepal-pkg-pack-list">
					<?php foreach ( $iflynepal_items as $iflynepal_item ) : ?>
						<li>
							<span class="iflynepal-pkg-mark"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-bag"/></svg></span>
							<?php echo esc_html( $iflynepal_item ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $iflynepal_embed ) : ?>
			<?php
			$iflynepal_place    = iflynepal_package_field( $iflynepal_id, 'map_place' );
			$iflynepal_note     = iflynepal_package_field( $iflynepal_id, 'map_note' );
			$iflynepal_map_link = iflynepal_package_field( $iflynepal_id, 'map_link' );
			$iflynepal_map_head = iflynepal_package_field( $iflynepal_id, 'map_heading' );
			?>
			<div id="ifnpkg-map" data-iflynepal-anim>
				<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'Map', 'iflynepal' ); ?></span>
				<?php if ( '' !== $iflynepal_map_head ) : ?>
					<h2 class="iflynepal-pkg-band-h"><?php iflynepal_package_the_heading( $iflynepal_map_head ); ?></h2>
				<?php endif; ?>

				<div class="iflynepal-pkg-map-card">
					<?php
					/*
					 * Lazy-loaded on purpose: an embedded map is a third-party
					 * document with its own scripts, and loading it before it is
					 * anywhere near the viewport costs the page its budget for
					 * nothing.
					 */
					?>
					<iframe
						title="<?php echo esc_attr( '' !== $iflynepal_place ? $iflynepal_place : __( 'Map', 'iflynepal' ) ); ?>"
						src="<?php echo esc_url( $iflynepal_embed ); ?>"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"></iframe>

					<?php if ( '' !== $iflynepal_place || '' !== $iflynepal_map_link ) : ?>
						<div class="iflynepal-pkg-map-foot">
							<div>
								<?php if ( '' !== $iflynepal_place ) : ?>
									<strong><?php echo esc_html( $iflynepal_place ); ?></strong>
								<?php endif; ?>
								<?php if ( '' !== $iflynepal_note ) : ?>
									<span><?php echo esc_html( $iflynepal_note ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( '' !== $iflynepal_map_link ) : ?>
								<a <?php echo iflynepal_booking_anchor_attr( $iflynepal_map_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?> target="_blank" rel="noopener">
									<?php esc_html_e( 'Open in Maps', 'iflynepal' ); ?>
									<svg class="iflynepal-pkg-link-arrow" aria-hidden="true"><use href="#ifnpkg-i-arrow"/></svg>
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
