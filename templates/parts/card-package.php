<?php
/**
 * One package card.
 *
 * Used by the type archive, the whole-catalogue archive and the related rail on
 * a single package, so a change to a card is a change in one file.
 *
 * Every fact on it degrades: a package with no price, no duration and no card
 * label still renders as a titled card with an image and a link. That matters
 * because the catalogue is being filled in while the site is being built.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'package'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_package = isset( $args['package'] ) ? $args['package'] : null;

if ( ! $iflynepal_package instanceof WP_Post ) {
	return;
}

$iflynepal_post_id = $iflynepal_package->ID;
$iflynepal_pill    = iflynepal_package_field( $iflynepal_post_id, 'pill' );
$iflynepal_price   = iflynepal_package_field( $iflynepal_post_id, 'price' );
$iflynepal_peek    = iflynepal_package_field( $iflynepal_post_id, 'peek' );

/*
 * The hover summary is the Hover summary field and nothing else.
 *
 * It used to fall back to get_the_excerpt(), which looks harmless until you
 * notice that WordPress manufactures an excerpt out of the first 55 words of the
 * post body when the Excerpt field is empty — so an archive card started
 * reprinting the package's itinerary, cut mid-sentence with an ellipsis. An
 * archive shows the copy written for the archive; the package's own body belongs
 * on the package's own page.
 */

$iflynepal_facts = array_filter(
	array(
		iflynepal_package_field( $iflynepal_post_id, 'duration' ),
		iflynepal_package_field( $iflynepal_post_id, 'suitability' ),
	),
	'strlen'
);

/*
 * Term slugs drive the client-side filter row on the archive. Ancestors are
 * included, so a package filed under a sub-category still answers to the
 * category filter above it — see iflynepal_package_filter_slugs().
 */
$iflynepal_slugs = iflynepal_package_filter_slugs( $iflynepal_post_id );
?>

<article class="iflynepal-card" data-categories="<?php echo esc_attr( implode( ' ', $iflynepal_slugs ) ); ?>">
	<div class="iflynepal-card__media">
		<?php
		if ( has_post_thumbnail( $iflynepal_post_id ) ) {
			echo get_the_post_thumbnail( // core-escaped markup.
				$iflynepal_post_id,
				'large',
				array(
					'loading' => 'lazy',
					'alt'     => esc_attr( get_the_title( $iflynepal_post_id ) ),
				)
			);
		}
		?>

		<?php if ( '' !== $iflynepal_pill ) : ?>
			<span class="iflynepal-pill"><?php echo esc_html( $iflynepal_pill ); ?></span>
		<?php endif; ?>

		<?php if ( '' !== $iflynepal_peek ) : ?>
			<div class="iflynepal-card__peek"><p><?php echo esc_html( $iflynepal_peek ); ?></p></div>
		<?php endif; ?>
	</div>

	<div class="iflynepal-card__body">
		<?php if ( ! empty( $iflynepal_facts ) ) : ?>
			<div class="iflynepal-card__meta">
				<?php foreach ( $iflynepal_facts as $iflynepal_fact ) : ?>
					<span><?php echo esc_html( $iflynepal_fact ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<h3 class="iflynepal-card__title">
			<a href="<?php echo esc_url( get_permalink( $iflynepal_package ) ); ?>">
				<?php echo esc_html( get_the_title( $iflynepal_package ) ); ?>
			</a>
		</h3>

		<div class="iflynepal-card__foot">
			<?php if ( '' !== $iflynepal_price ) : ?>
				<span class="iflynepal-card__price"><?php echo esc_html( $iflynepal_price ); ?></span>
			<?php endif; ?>

			<a class="iflynepal-card__link" href="<?php echo esc_url( get_permalink( $iflynepal_package ) ); ?>">
				<?php esc_html_e( 'View details', 'iflynepal' ); ?>
			</a>
		</div>
	</div>
</article>
