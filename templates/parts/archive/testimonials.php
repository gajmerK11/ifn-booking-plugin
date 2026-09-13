<?php
/**
 * Traveller testimonials.
 *
 * This section is the theme's, rendered by the theme's own reusable template
 * part, and that is deliberate. The design file says so in as many words — its
 * carousel is annotated "ported from iflynepal.local, token for token" — so the
 * component the design is asking for is a component the site already has,
 * complete with the drag, the cloned bands, the keyboard handling, the dimming
 * of the off-centre cards and the platform strip beneath them. Rebuilding it
 * here would be a second copy of all of that to keep in step, and the first one
 * to drift would be the one the homepage is not using.
 *
 * The same reasoning as the hero, which is the theme's component for the same
 * reason.
 *
 * The reviews themselves are the theme's Testimonials post type, which the theme
 * owns against the usual advice — recorded in the project context, not
 * re-argued here.
 *
 * The coupling is guarded rather than assumed. Under another theme the part and
 * its helpers are gone, and this falls back to a plain, readable list of the
 * same reviews; if even the post type has gone with the theme, the section
 * leaves itself out entirely.
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
 * The section is opt-in, and what opts it in is the assignment.
 *
 * This used to be gated on the section's heading being written, because the
 * band showed every review on the site: testimonials are published site-wide,
 * so "are there any" was true on every archive from the moment the first one
 * existed, and without a gate the band appeared on all five type archives
 * whether anyone meant it to or not.
 *
 * A review can now be assigned to an archive — Testimonials > Display On Page
 * lists them — so the archive shows the reviews assigned to it and no others,
 * and an archive nobody has assigned a review to has nothing to draw. That is
 * the gate, and it is the same one the rest of the site uses. The heading is
 * back to being what it is everywhere else: the section's copy, not a switch.
 */
if ( ! post_type_exists( 'ifly_testimonial' ) ) {
	return;
}

$iflynepal_eyebrow = iflynepal_archive_field( $iflynepal_id, 'testimonials_eyebrow' );

/*
 * The target is named rather than left to the theme's 'current', which would
 * resolve to the same thing on an archive this part is rendered on. Named,
 * because this part is handed the term it is drawing and a section that draws
 * one term's reviews while reading another's would be a hard thing to see.
 *
 * The kicker is passed only when there is one: the part's own default is a
 * written line, and passing an empty string in its place takes the section's
 * whole head with it — including the carousel's previous and next buttons.
 */
if ( locate_template( 'template-parts/sections/testimonials.php' ) && function_exists( 'iflynepal_get_testimonials' ) ) {
	$iflynepal_section_args = array(
		'id'     => 'iflynepal-proof',
		'target' => 'term:' . $iflynepal_id,
	);

	if ( '' !== $iflynepal_eyebrow ) {
		$iflynepal_section_args['kicker'] = $iflynepal_eyebrow;
	}

	get_template_part( 'template-parts/sections/testimonials', null, $iflynepal_section_args );

	return;
}

/* ------------------------------------------------------------- fallback */

/*
 * Another theme. Meta keys are read directly rather than through the theme's
 * accessor, because that function disappeared with the theme, and a fatal error
 * on a live archive is not an acceptable failure mode for a band of marketing
 * copy.
 */
$iflynepal_reviews = get_posts(
	array(
		'post_type'        => 'ifly_testimonial',
		'post_status'      => 'publish',
		'numberposts'      => 9,
		'suppress_filters' => false,
		'meta_key'         => '_iflynepal_display_page', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The set is a handful of posts.
		'meta_value'       => 'term:' . $iflynepal_id,   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Exact match on the assignment.
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
?>

<section class="iflynepal-section iflynepal-reviews" id="iflynepal-proof">
	<div class="iflynepal-container">
		<?php if ( '' !== $iflynepal_eyebrow ) : ?>
			<p class="iflynepal-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></p>
		<?php endif; ?>

		<ul class="iflynepal-reviews__track">
			<?php foreach ( $iflynepal_cards as $iflynepal_card ) : ?>
				<li class="iflynepal-reviews__item">
					<figure class="iflynepal-reviews__card">
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
