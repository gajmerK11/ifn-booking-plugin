<?php
/**
 * The wide feature band.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'term'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_term = isset( $args['term'] ) ? $args['term'] : null;

if ( ! $iflynepal_term instanceof WP_Term ) {
	return;
}

$iflynepal_id = $iflynepal_term->term_id;

if ( ! iflynepal_archive_has_any( $iflynepal_id, array( 'feature_heading', 'feature_image', 'feature_caption' ) ) ) {
	return;
}

$iflynepal_caption = iflynepal_archive_field( $iflynepal_id, 'feature_caption' );
?>

<section class="iflynepal-section iflynepal-feature">
	<div class="iflynepal-container iflynepal-feature__panel">
		<?php if ( iflynepal_archive_field( $iflynepal_id, 'feature_image' ) ) : ?>
			<div class="iflynepal-feature__media" aria-hidden="true">
				<?php iflynepal_archive_the_image( $iflynepal_id, 'feature_image', 'full', array( 'loading' => 'lazy' ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== iflynepal_archive_field( $iflynepal_id, 'feature_heading' ) ) : ?>
			<h2 class="iflynepal-feature__heading">
				<?php iflynepal_archive_the_heading( $iflynepal_id, 'feature_heading' ); ?>
			</h2>
		<?php endif; ?>

		<?php if ( '' !== $iflynepal_caption ) : ?>
			<p class="iflynepal-feature__caption"><?php echo esc_html( $iflynepal_caption ); ?></p>
		<?php endif; ?>
	</div>
</section>
