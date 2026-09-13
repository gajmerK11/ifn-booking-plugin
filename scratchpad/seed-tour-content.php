<?php
/**
 * Brings Tour up to the same state as Retreat: the three Tour categories get the
 * copy their two sections need, and the Tour catalogue gains one fully written
 * package so the single-package template can be read with real content in it.
 *
 * The words come from the design files, not from here:
 *   - the category copy from nepal-tour/nepal-tour-category-archive-design.html
 *   - the package from nepal-tour/nepal-tour-package-single-design.html, pulled
 *     into scratchpad/tour-single.json by scratchpad/extract_tour.py
 *
 * ⚠ The Tour single design writes a day as prose with an Overnight / Altitude /
 * Travel foot, and draws an altitude-profile chart. Neither is built: the
 * instruction was that the Tour single is to be *exactly* the Retreat single,
 * and the model behind it is the Retreat model. So a day's prose paragraphs
 * become the day's timeline rows, the day's foot line is appended to its
 * sub-line so the altitude and the overnight stop survive, and the chart is
 * left for P29 to decide.
 *
 * Sample content on a dev site, not client content — the same standing as P24.
 * Dry run by default; --apply writes; --drop removes everything it made.
 */

define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = 'iflynepal.local';
require 'C:/Users/asus/Local Sites/iflynepal/app/public/wp-load.php';

$apply = in_array( '--apply', $argv, true );
$drop  = in_array( '--drop', $argv, true );
$tag   = $apply || $drop ? '' : '[dry] ';

function ifn_say( $line ) {
	global $tag;
	echo $tag . $line . "\n";
}

/** The package this seeder owns, found by its slug so a re-run updates rather than duplicates. */
const IFN_TOUR_PACKAGE_SLUG = '15-day-badimalika-trek';

/* ------------------------------------------------------------ the categories */

/*
 * Eleven fields each, the same eleven Retreat > Mindfulness carries. The Cultural
 * hero is the design file's own; the other two leads are written in its voice
 * from the package cards that sit in each category, and are the only words here
 * that are not lifted from a design — flagged rather than passed off.
 */
$categories = array(
	'adventure' => array(
		'hero_heading'              => 'Trekking &amp; <em>Adventure</em>',
		'hero_lead'                 => 'Everest, Annapurna and Manaslu, and further-off routes like Makalu and Rolwaling, walked with guides who know the passes in every season.',
		'hero_cta_label'            => 'See the tours',
		'hero_cta_url'              => '#tours',
		'hero_cta_alt_label'        => 'Talk to a trip planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the tour that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, style and price, then open any tour for its full itinerary and dates.',
		'listing_annotation_static' => 'Walk it',
		'listing_annotation_words'  => "slowly\nhigher\nyour way",
	),
	'culture'   => array(
		'hero_heading'              => 'Culture &amp; <em>Festivals</em>',
		'hero_lead'                 => 'Sacred sites, heritage cities and remote communities with guides who give every place its context.',
		'hero_cta_label'            => 'See the tours',
		'hero_cta_url'              => '#tours',
		'hero_cta_alt_label'        => 'Talk to a trip planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the tour that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, style and price, then open any tour for its full itinerary and dates.',
		'listing_annotation_static' => 'See it',
		'listing_annotation_words'  => "up close\nin season\nwith context",
	),
	'nature'    => array(
		'hero_heading'              => 'Nature &amp; <em>Community</em>',
		'hero_lead'                 => 'Chitwan by jeep, canoe and guided walk, and community programmes where you stay with the families you are working alongside.',
		'hero_cta_label'            => 'See the tours',
		'hero_cta_url'              => '#tours',
		'hero_cta_alt_label'        => 'Talk to a trip planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the tour that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, style and price, then open any tour for its full itinerary and dates.',
		'listing_annotation_static' => 'Stay',
		'listing_annotation_words'  => "a while\nwith people\noff the road",
	),
);

/* ------------------------------------------------- images, reused from the library */

$image_ids = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'numberposts'    => 12,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'DESC',
	)
);

/* ------------------------------------------------------------------- the drop */

if ( $drop ) {
	foreach ( array_keys( $categories ) as $slug ) {
		$term = get_term_by( 'slug', $slug, IFLYNEPAL_PACKAGE_TAXONOMY );

		if ( ! $term ) {
			continue;
		}

		foreach ( iflynepal_package_type_archive_fields_for_term( $term ) as $key => $field ) {
			delete_term_meta( $term->term_id, iflynepal_archive_meta_key( $key ) );
		}

		ifn_say( sprintf( 'cleared the archive copy on %s', $term->name ) );
	}

	$existing = get_page_by_path( IFN_TOUR_PACKAGE_SLUG, OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

	if ( $existing ) {
		wp_delete_post( $existing->ID, true );
		ifn_say( sprintf( 'deleted package #%d %s', $existing->ID, $existing->post_title ) );
	}

	ifn_say( 'done' );

	return;
}

/* ------------------------------------------------------------- 1. categories */

foreach ( $categories as $slug => $fields ) {
	$term = get_term_by( 'slug', $slug, IFLYNEPAL_PACKAGE_TAXONOMY );

	if ( ! $term ) {
		ifn_say( sprintf( 'MISSING term %s — skipped', $slug ) );
		continue;
	}

	/*
	 * The hero photograph is borrowed from a package already filed under this
	 * category, so a trekking page opens on a trekking picture. Picking at
	 * random put a classroom on Trekking & Adventure, which is how the retreat
	 * archive ended up with the legal pages' hero (§5.3j). All of them are
	 * stock stand-ins until the client's own photography lands (P12).
	 */
	$in_category = get_posts(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'numberposts' => 4,
			'fields'      => 'ids',
			'tax_query'   => array( array( 'taxonomy' => IFLYNEPAL_PACKAGE_TAXONOMY, 'field' => 'term_id', 'terms' => $term->term_id ) ),
		)
	);

	$hero = 0;

	foreach ( $in_category as $candidate ) {
		$thumb = get_post_thumbnail_id( $candidate );

		if ( $thumb ) {
			$hero = $thumb;
			break;
		}
	}

	$fields['hero_image'] = (string) ( $hero ? $hero : ( $image_ids ? $image_ids[0] : 0 ) );

	if ( ! $apply ) {
		ifn_say( sprintf( 'fill %-10s (%s) with %d fields', $slug, $term->name, count( $fields ) ) );
		continue;
	}

	foreach ( $fields as $key => $value ) {
		update_term_meta( $term->term_id, iflynepal_archive_meta_key( $key ), $value );
	}

	ifn_say( sprintf( 'filled %-10s (%s) — %d fields', $slug, $term->name, count( $fields ) ) );
}

/* ---------------------------------------------------------- 2. the package */

$json = __DIR__ . '/tour-single.json';

if ( ! file_exists( $json ) ) {
	ifn_say( 'tour-single.json is missing — run scratchpad/extract_tour.py first' );

	return;
}

$design = json_decode( file_get_contents( $json ), true );

if ( ! is_array( $design ) ) {
	ifn_say( 'tour-single.json could not be read' );

	return;
}

$glance_map = array(
	'Destination'   => 'glance_destination',
	'Duration'      => 'glance_duration',
	'Activities'    => 'glance_activities',
	'Meals'         => 'glance_meals',
	'Accommodation' => 'glance_stay',
	'Max altitude'  => 'glance_checkin',
	'Group size'    => 'glance_group',
	'Best time'     => 'glance_best_time',
);

$fields = array(
	'pill'              => 'Trekking',
	'duration'          => '15 days',
	'suitability'       => 'Bajura, Far-Western Nepal',
	'price'             => 'From US$3,200',
	'peek'              => 'A fifteen-day pilgrimage trek to a temple at 4,200m, through a district most itineraries never touch.',

	'heading'           => $design['heading'],
	'overview_intro'    => $design['overview_intro'],
	'overview_body'     => $design['overview_body'],
	'highlights'        => implode( "\n", $design['highlights'] ),

	'itinerary_heading' => $design['itinerary_heading'],
	'dates_heading'     => $design['dates_heading'],
	'dates_lead'        => $design['dates_lead'],
	'price_amount'      => $design['price_amount'],
	'price_currency'    => $design['price_currency'],
	'duration_days'     => '15',

	'price_eyebrow'     => 'All inclusive price',
	'price_foot'        => $design['price_foot'],
	'price_points'      => implode( "\n", array_slice( $design['highlights'], 0, 5 ) ),

	'expert_name'       => 'Prem',
	'expert_place'      => 'Nepal',
	'expert_label'      => '+977-9841771010',
	'expert_link'       => 'https://wa.me/9779841771010',

	'packing_heading'   => $design['packing_heading'],
	'packing_items'     => implode( "\n", $design['packing_items'] ),

	'map_heading'       => $design['map_heading'],
	'map_embed'         => $design['map_embed'],
	'map_place'         => $design['map_place'],
	'map_note'          => 'Far-Western Nepal, Bajura district',
	'map_link'          => 'https://www.google.com/maps/search/?api=1&query=Badimalika+Temple+Bajura',

	'faq_heading'       => $design['faq_heading'],
	'faq_lead'          => $design['faq_lead'],
);

foreach ( $design['glance'] as $pair ) {
	list( $label, $value ) = $pair;

	if ( isset( $glance_map[ $label ] ) ) {
		$fields[ $glance_map[ $label ] ] = $value;
	}
}

foreach ( $design['price_cols'] as $col ) {
	$key = false !== stripos( $col['head'], 'not' ) ? 'excluded' : 'included';

	$fields[ $key ] = implode( "\n", $col['items'] );
}

// Days: title, sub-line (with the design's foot appended), the first paragraph as
// the short summary, and every paragraph as a timeline row.
$days = array();

foreach ( $design['days'] as $day ) {
	$meta = $day['sub'];

	if ( ! empty( $day['foot'] ) ) {
		$meta = trim( $meta . ' · ' . implode( ' · ', $day['foot'] ) );
	}

	$rows = array();

	foreach ( $day['rows'] as $row ) {
		$rows[] = array(
			'time' => $row[0],
			'text' => $row[1],
		);
	}

	$days[] = array(
		'title'    => $day['title'],
		'meta'     => $meta,
		'summary'  => isset( $day['rows'][0][1] ) ? $day['rows'][0][1] : '',
		'timeline' => $rows,
	);
}

$faqs = array();

foreach ( $design['faqs'] as $faq ) {
	$faqs[] = array(
		'q' => $faq['q'],
		'a' => $faq['a'],
	);
}

if ( ! $apply ) {
	ifn_say(
		sprintf(
			'make package "%s" — %d fields, %d days (%d timeline rows), %d FAQs, %d gallery photos',
			wp_strip_all_tags( $design['heading'] ),
			count( $fields ),
			count( $days ),
			array_sum( array_map( function ( $d ) { return count( $d['timeline'] ); }, $days ) ),
			count( $faqs ),
			min( 8, count( $image_ids ) )
		)
	);
	ifn_say( 'done' );

	return;
}

$existing = get_page_by_path( IFN_TOUR_PACKAGE_SLUG, OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

$post_data = array(
	'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
	'post_status' => 'publish',
	'post_title'  => wp_slash( wp_strip_all_tags( $design['heading'] ) ),
	'post_name'   => IFN_TOUR_PACKAGE_SLUG,
);

if ( $existing ) {
	$post_data['ID'] = $existing->ID;
	$package_id      = wp_update_post( $post_data, true );
} else {
	$package_id = wp_insert_post( $post_data, true );
}

if ( is_wp_error( $package_id ) ) {
	ifn_say( 'FAILED to create the package: ' . $package_id->get_error_message() );

	return;
}

// Filed under Tour > Trekking & Adventure, which is what a trek is.
$adventure = get_term_by( 'slug', 'adventure', IFLYNEPAL_PACKAGE_TAXONOMY );
$tour      = get_term_by( 'slug', 'nepal-tour', IFLYNEPAL_PACKAGE_TAXONOMY );
$terms     = array_filter( array( $adventure ? $adventure->term_id : 0, $tour ? $tour->term_id : 0 ) );

wp_set_object_terms( $package_id, $terms, IFLYNEPAL_PACKAGE_TAXONOMY );

foreach ( $fields as $key => $value ) {
	update_post_meta( $package_id, iflynepal_package_meta_key( $key ), $value );
}

update_post_meta( $package_id, iflynepal_package_meta_key( 'itinerary_days' ), $days );
update_post_meta( $package_id, iflynepal_package_meta_key( 'faq_items' ), $faqs );

/*
 * The lead photograph is the one the Himalayan Trekking card already uses, so
 * the trek page opens on a mountain rather than on whatever was uploaded last.
 * The rest of the gallery is made up from the library; every one of them is a
 * stand-in (P12).
 */
$lead = 0;
$sibling = get_page_by_path( 'himalayan-trekking', OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

if ( $sibling ) {
	$lead = get_post_thumbnail_id( $sibling->ID );
}

$gallery = array_values( array_unique( array_merge( $lead ? array( $lead ) : array(), $image_ids ) ) );

if ( $gallery ) {
	set_post_thumbnail( $package_id, $gallery[0] );
	update_post_meta( $package_id, iflynepal_package_meta_key( 'gallery' ), implode( ',', array_slice( $gallery, 0, 8 ) ) );
}

ifn_say(
	sprintf(
		'made package #%d "%s" — %d fields, %d days, %d FAQs, %d photos, terms: %s',
		$package_id,
		get_the_title( $package_id ),
		count( $fields ),
		count( $days ),
		count( $faqs ),
		count( array_slice( $image_ids, 0, 8 ) ),
		implode( ', ', wp_get_post_terms( $package_id, IFLYNEPAL_PACKAGE_TAXONOMY, array( 'fields' => 'slugs' ) ) )
	)
);
ifn_say( 'URL: ' . get_permalink( $package_id ) );
ifn_say( 'done' );
