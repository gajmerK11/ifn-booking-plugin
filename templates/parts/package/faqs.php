<?php
/**
 * The FAQ band of a package page.
 *
 * Native <details> elements: they open and close, are searchable by the
 * browser's own find, and work with no script at all. The design's accordion is
 * exactly this, styled.
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

$iflynepal_items = iflynepal_package_cards( $iflynepal_id, 'faq_items' );

if ( empty( $iflynepal_items ) ) {
	return;
}

$iflynepal_heading = iflynepal_package_field( $iflynepal_id, 'faq_heading' );
$iflynepal_lead    = iflynepal_package_field( $iflynepal_id, 'faq_lead' );
?>

<section class="iflynepal-pkg-band" id="ifnpkg-faqs" aria-labelledby="ifnpkg-faqs-h">
	<div class="iflynepal-pkg-faq-layout">
		<div data-iflynepal-anim>
			<span class="iflynepal-pkg-eyebrow"><?php echo esc_html( iflynepal_pkg_t( 'Before you book' ) ); ?></span>
			<?php if ( '' !== $iflynepal_heading ) : ?>
				<h2 id="ifnpkg-faqs-h"><?php iflynepal_package_the_heading( $iflynepal_heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $iflynepal_lead ) : ?>
				<p class="iflynepal-pkg-lead">
					<?php
					echo wp_kses( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses escapes.
						$iflynepal_lead,
						array(
							'b'      => array(),
							'strong' => array(),
							'em'     => array(),
							'br'     => array(),
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<div class="iflynepal-pkg-faq-list" data-iflynepal-anim>
			<?php foreach ( $iflynepal_items as $iflynepal_index => $iflynepal_item ) : ?>
				<?php if ( '' === $iflynepal_item['q'] ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<details<?php echo 0 === $iflynepal_index ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $iflynepal_item['q'] ); ?></summary>
					<p><?php echo esc_html( $iflynepal_item['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
