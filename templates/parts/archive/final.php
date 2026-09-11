<?php
/**
 * The closing call to action.
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

if ( ! iflynepal_archive_has_any( $iflynepal_id, array( 'final_heading', 'final_lead', 'final_cta_label' ) ) ) {
	return;
}

$iflynepal_eyebrow = iflynepal_archive_field( $iflynepal_id, 'final_eyebrow' );
$iflynepal_lead    = iflynepal_archive_field( $iflynepal_id, 'final_lead' );
?>

<section class="iflynepal-final">
	<div class="iflynepal-container iflynepal-final__card">
		<?php if ( iflynepal_archive_field( $iflynepal_id, 'final_image' ) ) : ?>
			<div class="iflynepal-final__media" aria-hidden="true">
				<?php iflynepal_archive_the_image( $iflynepal_id, 'final_image', 'full', array( 'loading' => 'lazy' ) ); ?>
			</div>
		<?php endif; ?>

		<div class="iflynepal-final__copy">
			<?php if ( '' !== $iflynepal_eyebrow ) : ?>
				<span class="iflynepal-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></span>
			<?php endif; ?>

			<?php if ( '' !== iflynepal_archive_field( $iflynepal_id, 'final_heading' ) ) : ?>
				<h2><?php iflynepal_archive_the_heading( $iflynepal_id, 'final_heading' ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $iflynepal_lead ) : ?>
				<p><?php echo esc_html( $iflynepal_lead ); ?></p>
			<?php endif; ?>

			<?php iflynepal_archive_the_actions( $iflynepal_id, 'final' ); ?>
		</div>
	</div>
</section>
