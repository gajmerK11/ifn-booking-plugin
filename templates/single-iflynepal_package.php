<?php
/**
 * A single package.
 *
 * Built from the approved design,
 * Downloads/iFlyNepal/retreats-nepal/retreats-nepal-package-single-design.html,
 * section for section: the photo gallery, the breadcrumb and share row, the
 * title and at-a-glance table, the sticky section nav, the overview, the
 * itinerary, the dates block with its calendar, the booking aside, the packing
 * list and map, the FAQs and the similar-retreats rail.
 *
 * Every string on the page comes from a field on the Package Details box or from
 * the post itself. Nothing is hardcoded out of the design file, and every section
 * is opt-in: a package that has not been written gets the sections it has and
 * none of the ones it has not.
 *
 * There is no hero. The design opens on a white gallery directly under a solid
 * header, which is why this template answers `iflynepal_has_hero` with false and
 * the header docks from the first frame.
 *
 * NOTHING HERE IS A RESERVATION. The calendar picks any date, the total is
 * arithmetic, and no availability or capacity exists behind either.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$iflynepal_id      = get_the_ID();
	$iflynepal_gallery = iflynepal_package_gallery( $iflynepal_id );
	$iflynepal_lead    = get_post_thumbnail_id( $iflynepal_id );

	/*
	 * The featured image is the lead photograph, and the gallery is the rest.
	 * Keeping them one list here rather than two in the markup is what lets the
	 * count, the lightbox and the "view all" button all agree without being told
	 * about each other.
	 */
	$iflynepal_photos   = array_values( array_unique( array_filter( array_merge( array( $iflynepal_lead ), $iflynepal_gallery ) ) ) );
	$iflynepal_heading  = iflynepal_package_field( $iflynepal_id, 'heading' );

	/*
	 * The map image opens in the same lightbox as the gallery, but is not one
	 * of "the photographs" the gallery counts itself by — its own aria-labels
	 * and its "View all N photos" button both read $iflynepal_photos, and a
	 * map graphic inflating that count would advertise more trip photos than
	 * the grid actually shows. So the lightbox gets a second, longer list of
	 * its own, and only packing-map.php's tile points at the extra slot on
	 * the end of it.
	 */
	$iflynepal_lightbox_photos = $iflynepal_photos;
	$iflynepal_map_image       = absint( iflynepal_package_field( $iflynepal_id, 'map_image' ) );
	$iflynepal_map_lb_index    = false;

	if ( $iflynepal_map_image ) {
		$iflynepal_lightbox_photos = array_values( array_unique( array_filter( array_merge( $iflynepal_lightbox_photos, array( $iflynepal_map_image ) ) ) ) );
		$iflynepal_map_lb_index    = array_search( $iflynepal_map_image, $iflynepal_lightbox_photos, true );
	}
	$iflynepal_sections = iflynepal_package_page_sections( $iflynepal_id );

	/*
	 * A featured video takes the lead tile, and the featured image becomes its
	 * poster frame and its fallback: the video is what the page opens on when
	 * there is one, and the photograph is what it opens on when there is not.
	 *
	 * The photographs are unchanged by this — the featured image stays in the
	 * gallery and in the lightbox, it simply moves from the lead tile into the
	 * thumbnails, which is what the offset below is for. The lightbox is photos
	 * only: it is a photo viewer, and the video is already playing on the page.
	 */
	$iflynepal_video    = iflynepal_package_video( $iflynepal_id );
	$iflynepal_is_video = ! empty( $iflynepal_video );
	$iflynepal_offset   = $iflynepal_is_video ? 0 : 1;
	$iflynepal_poster   = $iflynepal_lead ? wp_get_attachment_image_url( $iflynepal_lead, 'large' ) : '';
	?>

	<main id="main" class="iflynepal-package">
		<?php iflynepal_booking_get_part( 'parts/package/icons' ); ?>

		<?php if ( ! empty( $iflynepal_photos ) || $iflynepal_is_video ) : ?>
			<section class="iflynepal-pkg-gallery iflynepal-pkg-container" aria-label="<?php esc_attr_e( 'Photo gallery', 'iflynepal' ); ?>">
				<div class="iflynepal-pkg-gallery-frame">
					<div class="iflynepal-pkg-gallery-grid" id="ifnpkg-gallery">
						<div class="iflynepal-pkg-g-lead-wrap">
							<?php if ( $iflynepal_is_video ) : ?>
								<?php
								/*
								 * Muted, looping and inline, because it is the page's
								 * opening image rather than something to sit and
								 * watch — and because a browser refuses to autoplay
								 * anything with sound. The poster is the featured
								 * image, so the tile is never empty while the file
								 * loads and the LCP still has something to paint.
								 *
								 * The browser's own controls are deliberately off: on
								 * a silent looping backdrop they put a scrubber, a
								 * volume slider and a running duration across the
								 * bottom of the lead photograph, none of which this
								 * is for. What replaces them is one button in the
								 * middle — moving content has to be stoppable (WCAG
								 * 2.2.2), and that is the whole of what is needed.
								 *
								 * The button is written into the markup rather than
								 * added by the script, so it is there and it works
								 * from the first frame; it carries aria-pressed
								 * because it is a toggle, and the script keeps that
								 * in step with the video's real state.
								 */
								?>
								<div class="iflynepal-pkg-g-tile iflynepal-pkg-g-tile--lead iflynepal-pkg-g-tile--video" data-iflynepal-video>
									<video
										class="iflynepal-pkg-g-video"
										autoplay
										muted
										loop
										playsinline
										preload="metadata"
										data-iflynepal-video-media
										<?php echo $iflynepal_poster ? ' poster="' . esc_url( $iflynepal_poster ) . '"' : ''; ?>>
										<source src="<?php echo esc_url( $iflynepal_video['url'] ); ?>" type="<?php echo esc_attr( $iflynepal_video['mime'] ); ?>" />
									</video>

									<button class="iflynepal-pkg-g-video-btn" type="button"
										data-iflynepal-video-toggle
										aria-pressed="true"
										data-label-play="<?php esc_attr_e( 'Play video', 'iflynepal' ); ?>"
										data-label-pause="<?php esc_attr_e( 'Pause video', 'iflynepal' ); ?>"
										aria-label="<?php esc_attr_e( 'Pause video', 'iflynepal' ); ?>">
										<svg class="iflynepal-pkg-g-video-ico iflynepal-pkg-g-video-ico--play" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<path d="M8 5.5v13l11-6.5z" fill="currentColor"/>
										</svg>
										<svg class="iflynepal-pkg-g-video-ico iflynepal-pkg-g-video-ico--pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
											<path d="M8 5h3v14H8zM13 5h3v14h-3z" fill="currentColor"/>
										</svg>
									</button>
								</div>
							<?php else : ?>
								<?php
								/*
								 * Each tile is a button, not a link: it opens the
								 * lightbox rather than going anywhere, and a link
								 * that goes nowhere is a link a keyboard user has to
								 * find out about the hard way.
								 */
								?>
								<button class="iflynepal-pkg-g-tile iflynepal-pkg-g-tile--lead" type="button" data-index="0"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %d: how many photographs there are. */ __( 'Open photo 1 of %d', 'iflynepal' ), count( $iflynepal_photos ) ) ); ?>">
									<?php
									// Core-escaped markup. The lead photo is the LCP image on this page.
									echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$iflynepal_photos[0],
										'large',
										false,
										array(
											'fetchpriority' => 'high',
											'decoding' => 'sync',
											'loading'  => 'eager',
										)
									);
									?>
								</button>
							<?php endif; ?>

							<?php if ( count( $iflynepal_photos ) > $iflynepal_offset ) : ?>
								<button class="iflynepal-pkg-g-all" type="button" data-index="0">
									<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-grid"/></svg>
									<?php
									printf(
										/* translators: %d: how many photographs there are. */
										esc_html( _n( 'View %d photo', 'View all %d photos', count( $iflynepal_photos ), 'iflynepal' ) ),
										count( $iflynepal_photos )
									);
									?>
								</button>
							<?php endif; ?>
						</div>

						<?php
						/*
						 * Four thumbnails beside the lead, as the design lays it
						 * out. The rest of the set exists only in the lightbox —
						 * which is what the "view all" button is for.
						 */
						$iflynepal_thumbs = array_slice( $iflynepal_photos, $iflynepal_offset, 4 );

						if ( ! empty( $iflynepal_thumbs ) ) :
							?>
							<div class="iflynepal-pkg-g-thumbs">
								<?php foreach ( $iflynepal_thumbs as $iflynepal_index => $iflynepal_photo ) : ?>
									<button class="iflynepal-pkg-g-tile" type="button" data-index="<?php echo esc_attr( (string) ( $iflynepal_index + $iflynepal_offset ) ); ?>"
										aria-label="<?php echo esc_attr( sprintf( /* translators: 1: photo number, 2: how many photographs there are. */ __( 'Open photo %1$d of %2$d', 'iflynepal' ), $iflynepal_index + $iflynepal_offset + 1, count( $iflynepal_photos ) ) ); ?>">
										<?php
										// Core-escaped markup.
										echo wp_get_attachment_image( $iflynepal_photo, 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="iflynepal-pkg-gallery-meta">
					<?php iflynepal_package_the_breadcrumb( $iflynepal_id ); ?>
					<?php iflynepal_package_the_share( $iflynepal_id ); ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="iflynepal-pkg-trip-title iflynepal-pkg-container">
			<?php
			$iflynepal_type = iflynepal_package_primary_type( $iflynepal_id );

			if ( $iflynepal_type instanceof WP_Term ) :
				?>
				<span class="iflynepal-pkg-eyebrow" data-iflynepal-anim><?php echo esc_html( $iflynepal_type->name ); ?></span>
			<?php endif; ?>

			<h1 id="ifnpkg-trip-title">
				<?php
				if ( '' !== $iflynepal_heading ) {
					echo wp_kses(
						$iflynepal_heading,
						array(
							'em'   => array(),
							'span' => array( 'class' => array() ),
						)
					);
				} else {
					the_title();
				}
				?>
			</h1>

			<?php
			$iflynepal_glance = iflynepal_package_glance( $iflynepal_id );

			if ( ! empty( $iflynepal_glance ) ) :
				/*
				 * The design's grid lines are a border on every cell with two of
				 * them taken off again, by `:nth-child(4n)` and
				 * `:nth-last-child(-n+4)`. Those rules mean "the last column" and
				 * "the last row" only while every row is full, which is true of
				 * the design's own eight facts and not of a package where an
				 * editor has left one blank — and the single rounded rectangle
				 * the container draws around the whole table has the same
				 * problem one level up: it has no way to stop partway through a
				 * row, so a short last row left its right/bottom edges and its
				 * bottom corners running on past the last real fact with nothing
				 * above them.
				 *
				 * Rather than teach one box to draw two different shapes, any
				 * complete rows go in `.iflynepal-pkg-glance` exactly as the
				 * design has it — a package whose fact count is already a
				 * multiple of four renders only this, unchanged — and whatever
				 * is left over (one to three facts) goes in a second, smaller
				 * box, `.iflynepal-pkg-glance-tail`, sized and rounded for
				 * exactly the facts it holds. See package.css.
				 */
				$iflynepal_count = count( $iflynepal_glance );
				$iflynepal_full  = array_slice( $iflynepal_glance, 0, $iflynepal_count - ( $iflynepal_count % 4 ) );
				$iflynepal_tail  = array_slice( $iflynepal_glance, count( $iflynepal_full ) );

				/**
				 * One at-a-glance cell's markup.
				 *
				 * @param array $iflynepal_row Has 'label', 'value' and 'icon'.
				 * @return void
				 */
				$iflynepal_glance_item = function ( $iflynepal_row ) {
					?>
					<div class="iflynepal-pkg-glance-item">
						<span class="iflynepal-pkg-glance-ico">
							<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-<?php echo esc_attr( $iflynepal_row['icon'] ); ?>"/></svg>
						</span>
						<div>
							<small><?php echo esc_html( $iflynepal_row['label'] ); ?></small>
							<strong><?php echo esc_html( $iflynepal_row['value'] ); ?></strong>
						</div>
					</div>
					<?php
				};
				?>
				<div class="iflynepal-pkg-trip-glance" data-iflynepal-anim>
					<span class="iflynepal-pkg-eyebrow"><?php esc_html_e( 'At a glance', 'iflynepal' ); ?></span>
					<?php if ( ! empty( $iflynepal_full ) ) : ?>
						<div class="iflynepal-pkg-glance<?php echo ! empty( $iflynepal_tail ) ? ' iflynepal-pkg-glance--capped' : ''; ?>">
							<?php foreach ( $iflynepal_full as $iflynepal_row ) : ?>
								<?php $iflynepal_glance_item( $iflynepal_row ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $iflynepal_tail ) ) : ?>
						<div
							class="iflynepal-pkg-glance-tail<?php echo ! empty( $iflynepal_full ) ? ' iflynepal-pkg-glance-tail--attached' : ''; ?>"
							style="--iflynepal-glance-tail-n:<?php echo (int) count( $iflynepal_tail ); ?>"
						>
							<?php foreach ( $iflynepal_tail as $iflynepal_row ) : ?>
								<?php $iflynepal_glance_item( $iflynepal_row ); ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>

		<div class="iflynepal-pkg-container iflynepal-pkg-page-body">
			<?php if ( count( $iflynepal_sections ) > 1 ) : ?>
				<nav class="iflynepal-pkg-side-nav" id="ifnpkg-side-nav" aria-label="<?php esc_attr_e( 'Package sections', 'iflynepal' ); ?>">
					<span class="iflynepal-pkg-side-label"><?php esc_html_e( 'Explore this package', 'iflynepal' ); ?></span>
					<ol class="iflynepal-pkg-side-links">
						<?php foreach ( $iflynepal_sections as $iflynepal_slug => $iflynepal_section ) : ?>
							<li>
								<a <?php echo iflynepal_booking_anchor_attr( '#ifnpkg-' . $iflynepal_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?><?php echo $iflynepal_section['first'] ? ' class="iflynepal-pkg-is-active" aria-current="true"' : ''; ?>>
									<svg class="iflynepal-pkg-ico" aria-hidden="true"><use href="#ifnpkg-i-<?php echo esc_attr( $iflynepal_section['icon'] ); ?>"/></svg>
									<?php echo esc_html( $iflynepal_section['label'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>

			<div class="iflynepal-pkg-page-main">
				<div class="iflynepal-pkg-trip-layout">
					<div class="iflynepal-pkg-trip-main">
						<?php
						iflynepal_booking_get_part( 'parts/package/overview', array( 'id' => $iflynepal_id ) );
						iflynepal_booking_get_part( 'parts/package/itinerary', array( 'id' => $iflynepal_id ) );
						iflynepal_booking_get_part( 'parts/package/dates', array( 'id' => $iflynepal_id ) );
						?>
					</div>

					<?php iflynepal_booking_get_part( 'parts/package/aside', array( 'id' => $iflynepal_id ) ); ?>
				</div>

				<?php
				/*
				 * The later bands sit inside the content column, not outside it,
				 * and that is the design's own arrangement rather than a nicety.
				 * Two things depend on it: the sticky section nav is the other
				 * column of this grid, so a band that leaves the grid scrolls
				 * out from under the nav it is listed in; and a band is only as
				 * wide as the column, which is what keeps its text measure the
				 * same as the itinerary's above it.
				 *
				 * The mist bands still reach both screen edges — their tint is
				 * painted by a ::before bled out to -100vw, which is also why
				 * the page root carries `overflow-x: clip`.
				 */
				iflynepal_booking_get_part(
					'parts/package/packing-map',
					array(
						'id'       => $iflynepal_id,
						'lb_index' => $iflynepal_map_lb_index,
					)
				);
				iflynepal_booking_get_part( 'parts/package/faqs', array( 'id' => $iflynepal_id ) );
				?>
			</div>
		</div>

		<?php
		/*
		 * The similar-packages grid sits outside the two-column body, not
		 * inside it: it borrows the archive's own card grid and heading
		 * classes, which run the page's full measure, not the narrower
		 * column the itinerary and dates text sit in. Its anchor still works
		 * from the side nav: the scroll tracker in package.js finds each
		 * section by its id, not by where it lives in the markup.
		 */
		iflynepal_booking_get_part( 'parts/package/similar', array( 'id' => $iflynepal_id ) );

		iflynepal_booking_get_part(
			'parts/package/lightbox',
			array(
				'id'     => $iflynepal_id,
				'photos' => $iflynepal_lightbox_photos,
			)
		);
		?>
	</main>

	<?php
endwhile;

get_footer();
