<?php
/**
 * Traveller testimonials.
 *
 * The reviews are the theme's Testimonials post type, which the theme owns
 * against the usual advice — recorded in the project context, not re-argued
 * here. The plugin reads them rather than storing its own, because a second
 * store of the same reviews is worse than the coupling.
 *
 * That coupling is guarded, not assumed: the post type belongs to the theme, so
 * a theme switch removes it, and this section then has nothing to draw and
 * leaves itself out. Meta keys are read directly rather than through the theme's
 * accessor for the same reason — the function disappears with the theme, and a
 * fatal error on a live archive is not an acceptable failure mode for a section
 * of marketing copy.
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

/*
 * The reviews are a query, but the section is opt-in.
 *
 * Testimonials are published site-wide and are not filed against a package
 * type, so "are there any" is true on every archive from the moment the first
 * one exists — which would put this band on all five type archives whether or
 * not anyone meant it to be there. The editor says yes by writing the section's
 * heading; an untouched term gets no testimonials band.
 *
 * It does not gate the package grid: the grid is what the archive is for, and
 * its heading is decoration rather than a switch.
 */
if ( ! iflynepal_archive_has_any( $iflynepal_id, array( 'testimonials_eyebrow' ) ) ) {
	return;
}

if ( ! post_type_exists( 'ifly_testimonial' ) ) {
	return;
}

$iflynepal_reviews = get_posts(
	array(
		'post_type'        => 'ifly_testimonial',
		'post_status'      => 'publish',
		'numberposts'      => 9,
		'suppress_filters' => false,
	)
);

$iflynepal_cards = array();

foreach ( $iflynepal_reviews as $iflynepal_review ) {
	$iflynepal_body = (string) get_post_meta( $iflynepal_review->ID, '_iflynepal_review_body', true );

	// A review with nothing quoted in it is a draft in all but status.
	if ( '' === trim( $iflynepal_body ) ) {
		continue;
	}

	$iflynepal_cards[] = array(
		'body'    => $iflynepal_body,
		'name'    => (string) get_post_meta( $iflynepal_review->ID, '_iflynepal_reviewer_name', true ),
		'country' => (string) get_post_meta( $iflynepal_review->ID, '_iflynepal_reviewer_country', true ),
		'photo'   => (int) get_post_meta( $iflynepal_review->ID, '_iflynepal_reviewer_photo', true ),
	);
}

if ( empty( $iflynepal_cards ) ) {
	return;
}

$iflynepal_eyebrow = iflynepal_archive_field( $iflynepal_id, 'testimonials_eyebrow' );
?>

<section class="iflynepal-section iflynepal-testimonials" id="iflynepal-proof">
	<div class="iflynepal-container">
		<?php if ( '' !== $iflynepal_eyebrow ) : ?>
			<p class="iflynepal-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></p>
		<?php endif; ?>

		<ul class="iflynepal-testimonials__track">
			<?php foreach ( $iflynepal_cards as $iflynepal_card ) : ?>
				<li class="iflynepal-testimonials__item">
					<figure class="iflynepal-testimonials__card">
						<blockquote><?php echo esc_html( $iflynepal_card['body'] ); ?></blockquote>
						<figcaption>
							<?php
							if ( $iflynepal_card['photo'] ) {
								echo wp_get_attachment_image( // core-escaped markup.
									$iflynepal_card['photo'],
									'thumbnail',
									false,
									array(
										'loading' => 'lazy',
										'alt'     => '',
									)
								);
							}
							?>
							<span>
								<b><?php echo esc_html( $iflynepal_card['name'] ); ?></b>
								<?php if ( '' !== $iflynepal_card['country'] ) : ?>
									<small><?php echo esc_html( $iflynepal_card['country'] ); ?></small>
								<?php endif; ?>
							</span>
						</figcaption>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
