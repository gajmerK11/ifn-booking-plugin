<?php
/**
 * The whole-catalogue archive — /packages/.
 *
 * This is the fallback root: everything, regardless of type. It has no term
 * behind it, so there is no archive content model to read — the marketing copy
 * belongs to a type, and this page is all of them. It is deliberately the plain
 * one: a heading, the grid, and the pagination.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="iflynepal-archive iflynepal-archive--all">
	<section class="iflynepal-section">
		<div class="iflynepal-container">
			<div class="iflynepal-section-head">
				<h1><?php post_type_archive_title(); ?></h1>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="iflynepal-cards">
					<?php
					while ( have_posts() ) {
						the_post();
						iflynepal_booking_get_part( 'parts/card-package', array( 'package' => get_post() ) );
					}
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => esc_html__( 'Previous', 'iflynepal' ),
						'next_text' => esc_html__( 'Next', 'iflynepal' ),
					)
				);
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'No packages have been published yet.', 'iflynepal' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
