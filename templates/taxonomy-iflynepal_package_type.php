<?php
/**
 * A package type archive — /retreat-nepal/, and its child categories.
 *
 * Every string on this page comes from the archive content model on the term
 * (includes/archive/package-type-archive-schema.php). Nothing is hardcoded from
 * the design HTML: the design is the layout, the term meta is the copy. A term
 * whose fields are empty renders the sections it has and silently leaves out the
 * ones it does not, so a brand new package type is a working page from the
 * moment it is created and gets richer as the copy is written.
 *
 * The same file serves a top-level type and a child category, because they are
 * the same kind of thing: a term with packages under it. What differs is how
 * many sections each gets. A type archive is the landing page for a whole kind
 * of travel and wraps its grid in ten sections; a category archive is a filtered
 * view of the same catalogue and is the hero and the grid, which is what its
 * design shows. iflynepal_booking_archive_sections() answers that from the
 * term's depth.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_term = get_queried_object();

if ( ! $iflynepal_term instanceof WP_Term ) {
	return;
}

$iflynepal_args = array( 'term' => $iflynepal_term );

get_header();
?>

<main id="main" class="iflynepal-archive">
	<?php
	foreach ( iflynepal_booking_archive_sections( $iflynepal_term ) as $iflynepal_section ) {
		iflynepal_booking_get_part( 'parts/archive/' . $iflynepal_section, $iflynepal_args );
	}
	?>
</main>

<?php
get_footer();
