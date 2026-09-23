<?php
/**
 * Archive hero.
 *
 * Deliberately the theme's `iflynepal-hero` component, class for class, rather
 * than a hero of the plugin's own. That component is what the theme's
 * assets/js/homepage/hero/hero.js watches to dock the site header — one file
 * drives every hero on the site — so a hero built with different class names
 * would leave the header transparent over the image and unreadable. It reuses
 * the wp-block-cover classes for the same reason the theme's own heroes do.
 *
 * The term name is the fallback headline. An archive without an H1 is a real
 * defect, and a package type exists before anybody writes its copy.
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

$iflynepal_id       = $iflynepal_term->term_id;
$iflynepal_heading  = iflynepal_archive_field( $iflynepal_id, 'hero_heading' );
$iflynepal_lead     = iflynepal_archive_field( $iflynepal_id, 'hero_lead' );
$iflynepal_image_id = absint( iflynepal_archive_field( $iflynepal_id, 'hero_image' ) );

if ( '' === $iflynepal_heading ) {
	$iflynepal_heading = esc_html( $iflynepal_term->name );
}

/*
 * A category hero carries its own modifier. The two archives share this part
 * and every class on it, but they do not carry the same copy: a top-level type
 * introduces a whole catalogue in a sentence or two, a category says one thing
 * about one branch. Only the short one can be set on a single line, so the
 * class is what lets the stylesheet tell them apart.
 */
$iflynepal_hero_class = 'wp-block-cover iflynepal-hero iflynepal-hero--page iflynepal-hero--package-type';

if ( $iflynepal_term->parent ) {
	$iflynepal_hero_class .= ' iflynepal-hero--package-category';
}
?>
<section class="<?php echo esc_attr( $iflynepal_hero_class ); ?>">

	<?php if ( $iflynepal_image_id ) : ?>
		<div class="iflynepal-hero__media" aria-hidden="true">
			<?php
			/*
			 * The LCP image on this page: eager, decoded synchronously and never
			 * lazy-loaded, which is the opposite of every other image here.
			 */
			echo wp_get_attachment_image( // core-escaped markup.
				$iflynepal_image_id,
				'full',
				false,
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

			<h1 class="wp-block-heading iflynepal-hero__title">
				<?php echo iflynepal_booking_kses_text( $iflynepal_heading ); // kses filtered. ?>
			</h1>

			<?php if ( '' !== $iflynepal_lead ) : ?>
				<p class="iflynepal-hero__lead"><?php echo iflynepal_booking_kses_text( $iflynepal_lead ); // kses filtered. ?></p>
			<?php endif; ?>

			<?php iflynepal_archive_the_actions( $iflynepal_id, 'hero', 'wp-block-buttons iflynepal-hero__actions' ); ?>

		</div>
	</div>

</section>
