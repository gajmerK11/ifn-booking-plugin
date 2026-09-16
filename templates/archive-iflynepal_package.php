<?php
/**
 * The whole-catalogue archive — /packages/.
 *
 * The fallback root: everything, regardless of type. It has no term behind it,
 * so there is no archive content model to read — the marketing copy belongs to
 * a type, and this page is all of them.
 *
 * It used to be a flat, paginated wall of every published package in one grid,
 * which is what a WordPress archive does by default rather than something
 * anybody designed. It is now the same shape as /explore/: one band per package
 * type, three cards, a "View all" button into that type's own archive.
 * Client-directed, and it earns the change twice over — this page is where the
 * explore page's own "Browse all packages" escape hatch sends a visitor who
 * found nothing, so landing them on an undesigned list was the worst possible
 * answer to "show me what you do have".
 *
 * 🔴 Both pages draw their bands with iflynepal_explore_the_type_section(), not
 * with a copy of it. That function owns the heading, the three cards, the
 * alternating mist band and the View-all button; a second implementation here
 * would be two things to keep in step, and "make it look exactly like the
 * explore page" is a requirement that only stays true if there is one of them.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * The loop WordPress has already run for this archive is deliberately unused.
 * It is paginated, capped at the site's posts-per-page and ordered by date,
 * none of which this page wants: the bands are per type, three each, and the
 * "rest" is reached through the button rather than through page 2. Querying per
 * type is the only way to know a type has nothing and leave its band out.
 */
$iflynepal_sections = array();

foreach ( iflynepal_explore_top_level_types() as $iflynepal_type ) {
	$iflynepal_found = iflynepal_explore_packages_for_term( $iflynepal_type->term_id, IFLYNEPAL_EXPLORE_CARDS_PER_TYPE );

	if ( $iflynepal_found ) {
		$iflynepal_sections[] = array(
			'term'     => $iflynepal_type,
			'packages' => $iflynepal_found,
		);
	}
}

get_header();
?>

<main id="main" class="iflynepal-archive iflynepal-archive--all">
	<?php
	/*
	 * The title band, matching the explore page's own: centred head, eyebrow
	 * above the heading, mist ground. The heading is an <h1> here and an <h2>
	 * there, and the difference is correct rather than an inconsistency — this
	 * is a real archive whose title is the page's subject, while the explore
	 * page is a Page whose own title the theme never prints.
	 */
	?>
	<section class="iflynepal-section iflynepal-section--mist" data-iflynepal-fade>
		<div class="iflynepal-container">
			<div class="iflynepal-section-head iflynepal-section-head--center">
				<span class="iflynepal-eyebrow"><?php esc_html_e( 'Everything we run', 'iflynepal' ); ?></span>
				<h1><?php post_type_archive_title(); ?></h1>
				<p class="iflynepal-lead"><?php esc_html_e( 'Every kind of trip we run, a few from each. Open any one to see the whole list.', 'iflynepal' ); ?></p>
			</div>
		</div>
	</section>

	<?php if ( $iflynepal_sections ) : ?>
		<?php
		foreach ( $iflynepal_sections as $iflynepal_index => $iflynepal_section ) {
			iflynepal_explore_the_type_section( $iflynepal_section['term'], $iflynepal_section['packages'], $iflynepal_index );
		}
		?>
	<?php else : ?>
		<?php
		/*
		 * Nothing published anywhere. Not the explore page's "nothing matches"
		 * notice, which offers a link to this page — the one the visitor is
		 * already on.
		 */
		?>
		<section class="iflynepal-section" data-iflynepal-fade>
			<div class="iflynepal-container">
				<div class="iflynepal-section-head iflynepal-section-head--center">
					<p class="iflynepal-lead"><?php esc_html_e( 'No packages have been published yet.', 'iflynepal' ); ?></p>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
