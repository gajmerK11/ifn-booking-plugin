<?php
/**
 * The gallery lightbox, and the mobile booking bar.
 *
 * Both live at the end of the page because both are overlays: the lightbox is a
 * modal dialog and the bar is fixed to the bottom of the viewport, and neither
 * belongs inside the reading order of the content it floats over.
 *
 * The photographs are printed into the markup as a list the script reads, rather
 * than fetched or localized: they are already known at render time, and a
 * lightbox that needs a round trip before it can show the second photograph is a
 * lightbox that stutters.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'id' and 'photos'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_id     = isset( $args['id'] ) ? (int) $args['id'] : 0;
$iflynepal_photos = isset( $args['photos'] ) && is_array( $args['photos'] ) ? $args['photos'] : array();

if ( ! $iflynepal_id ) {
	return;
}

$iflynepal_price = iflynepal_package_field( $iflynepal_id, 'price_amount' );

if ( '' !== $iflynepal_price ) :
	$iflynepal_currency = iflynepal_package_field( $iflynepal_id, 'price_currency' );
	?>
	<div class="iflynepal-pkg-book-bar" id="ifnpkg-book-bar" aria-hidden="true">
		<div>
			<small><?php echo esc_html( iflynepal_pkg_t( 'From' ) ); ?></small>
			<strong>
				<?php echo esc_html( trim( $iflynepal_currency . ' ' . $iflynepal_price ) ); ?>
				<span>/ <?php echo esc_html( iflynepal_pkg_t( 'person' ) ); ?></span>
			</strong>
		</div>
		<a class="iflynepal-pkg-button iflynepal-pkg-button--primary" href="#ifnpkg-dates" tabindex="-1"><?php echo esc_html( iflynepal_pkg_t( 'Book now' ) ); ?></a>
	</div>
	<?php
endif;

if ( count( $iflynepal_photos ) < 1 ) {
	return;
}
?>

<div class="iflynepal-pkg-lightbox" id="ifnpkg-lightbox" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Photo gallery' ) ); ?>" hidden>
	<div class="iflynepal-pkg-lb-top">
		<span class="iflynepal-pkg-lb-count" id="ifnpkg-lb-count"></span>
		<button class="iflynepal-pkg-lb-btn" type="button" id="ifnpkg-lb-close" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Close gallery' ) ); ?>">
			<svg class="iflynepal-pkg-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 6.5l11 11M17.5 6.5l-11 11"/></svg>
		</button>
	</div>

	<div class="iflynepal-pkg-lb-stage">
		<button class="iflynepal-pkg-lb-btn iflynepal-pkg-lb-prev" type="button" id="ifnpkg-lb-prev" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Previous photo' ) ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-left"/></svg></button>
		<figure class="iflynepal-pkg-lb-figure">
			<img id="ifnpkg-lb-img" src="" alt="" />
			<figcaption id="ifnpkg-lb-cap"></figcaption>
		</figure>
		<button class="iflynepal-pkg-lb-btn iflynepal-pkg-lb-next" type="button" id="ifnpkg-lb-next" aria-label="<?php echo esc_attr( iflynepal_pkg_t( 'Next photo' ) ); ?>"><svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-right"/></svg></button>
	</div>

	<div class="iflynepal-pkg-lb-thumbs" id="ifnpkg-lb-thumbs"></div>
</div>

<?php
/*
 * The set itself. A <template> is inert markup: it is never rendered, never
 * loaded and never read by a screen reader, which is what makes it the right
 * place to park eight full-size photographs the page may never open.
 */
?>
<template id="ifnpkg-photos">
	<?php foreach ( $iflynepal_photos as $iflynepal_photo ) : ?>
		<?php
		$iflynepal_full  = wp_get_attachment_image_url( $iflynepal_photo, 'full' );
		$iflynepal_thumb = wp_get_attachment_image_url( $iflynepal_photo, 'thumbnail' );

		if ( ! $iflynepal_full ) {
			continue;
		}
		?>
		<span data-full="<?php echo esc_url( $iflynepal_full ); ?>"
			data-thumb="<?php echo esc_url( $iflynepal_thumb ? $iflynepal_thumb : $iflynepal_full ); ?>"
			data-alt="<?php echo esc_attr( (string) get_post_meta( $iflynepal_photo, '_wp_attachment_image_alt', true ) ); ?>"></span>
	<?php endforeach; ?>
</template>
