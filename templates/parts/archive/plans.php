<?php
/**
 * The booking plan columns.
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

$iflynepal_id    = $iflynepal_term->term_id;
$iflynepal_plans = iflynepal_archive_group(
	$iflynepal_id,
	'plan',
	IFLYNEPAL_ARCHIVE_PLAN_SLOTS,
	array( 'name', 'subtitle', 'price', 'price_note', 'features', 'cta_label', 'cta_url', 'featured' ),
	'name'
);

if ( empty( $iflynepal_plans ) ) {
	return;
}
?>

<section class="iflynepal-section iflynepal-plans" id="iflynepal-plans">
	<div class="iflynepal-container">
		<?php iflynepal_archive_the_head( $iflynepal_id, 'plans' ); ?>

		<div class="iflynepal-plans__grid">
			<?php foreach ( $iflynepal_plans as $iflynepal_plan ) : ?>
				<article class="iflynepal-plan<?php echo $iflynepal_plan['featured'] ? ' is-featured' : ''; ?>">
					<h3 class="iflynepal-plan__name"><?php echo esc_html( $iflynepal_plan['name'] ); ?></h3>

					<?php if ( '' !== $iflynepal_plan['subtitle'] ) : ?>
						<p class="iflynepal-plan__sub"><?php echo esc_html( $iflynepal_plan['subtitle'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_plan['price'] ) : ?>
						<div class="iflynepal-plan__price">
							<?php echo esc_html( $iflynepal_plan['price'] ); ?>
							<?php if ( '' !== $iflynepal_plan['price_note'] ) : ?>
								<small><?php echo esc_html( $iflynepal_plan['price_note'] ); ?></small>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php
					$iflynepal_features = iflynepal_archive_field_lines( $iflynepal_id, 'plan_' . $iflynepal_plan['index'] . '_features' );

					if ( ! empty( $iflynepal_features ) ) :
						?>
						<ul class="iflynepal-plan__features">
							<?php foreach ( $iflynepal_features as $iflynepal_feature ) : ?>
								<li><?php echo esc_html( $iflynepal_feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_plan['cta_label'] && '' !== $iflynepal_plan['cta_url'] ) : ?>
						<a class="iflynepal-button iflynepal-button--dark" href="<?php echo esc_url( $iflynepal_plan['cta_url'] ); ?>">
							<?php echo esc_html( $iflynepal_plan['cta_label'] ); ?>
						</a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
