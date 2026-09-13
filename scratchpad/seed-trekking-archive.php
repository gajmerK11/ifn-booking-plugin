<?php
/**
 * Fills the Trekking archive from trekking/trekking-archive-design.html.
 *
 * Same shape as seed-tour-archive.php (P14's own precedent): the design uses
 * the same nine sections as Retreat and Tour, so nothing had to be built for
 * it — only the copy, the three categories the filter row is derived from,
 * and packages for the grid. The dark "altitude" panel section (day-by-day
 * gain, one card and one photograph) is deliberately left unbuilt, matching
 * Retreat (where the client removed it) and Tour (where it is still P29's
 * open question) — this document's own instruction was to build Tour the
 * way Retreat was built, and Trekking follows the same precedent.
 *
 * The copy is the design file's, transcribed. The packages and the departure
 * cards are SAMPLE CONTENT for design review — same standing as P24 — and
 * `--drop` takes all of it away again.
 *
 * Images are existing attachments from the media library, reused rather than
 * sideloaded: the design's own photographs are stock/Unsplash stand-ins and
 * the real photography is still owed (P12).
 *
 * Usage:
 *   php seed-trekking-archive.php            dry run
 *   php seed-trekking-archive.php --apply    write
 *   php seed-trekking-archive.php --drop     remove everything it wrote
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );

$_SERVER['HTTP_HOST'] = 'iflynepal.local';

require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply = in_array( '--apply', $argv, true );
$drop  = in_array( '--drop', $argv, true );

$term = get_term_by( 'name', 'Trek', IFLYNEPAL_PACKAGE_TAXONOMY );

if ( ! $term ) {
	exit( "No Trek term.\n" );
}

$term_id = (int) $term->term_id;

/* ------------------------------------------------------------- categories */

$categories = array(
	'Classic routes'         => 'classic-routes',
	'Remote & restricted'    => 'remote-restricted',
	'Short & near Kathmandu' => 'short-near-kathmandu',
);

/* ---------------------------------------------------------------- packages */

$packages = array(
	array(
		'title'       => 'Everest Region',
		'category'    => 'classic-routes',
		'pill'        => 'Everest',
		'duration'    => '7 to 24 days',
		'suitability' => 'Teahouse',
		'price'       => 'From $700',
		'peek'        => 'Everest Base Camp, Gokyo Lakes and the high passes, through Sherpa villages and Sagarmatha National Park.',
	),
	array(
		'title'       => 'Annapurna Region',
		'category'    => 'classic-routes',
		'pill'        => 'Annapurna',
		'duration'    => '5 to 16 days',
		'suitability' => 'Teahouse',
		'price'       => 'From $400',
		'peek'        => 'Annapurna Base Camp, the Circuit, Poon Hill sunrise and Mardi Himal, with Pokhara at either end.',
	),
	array(
		'title'       => 'Manaslu & Tsum Valley',
		'category'    => 'remote-restricted',
		'pill'        => 'Manaslu',
		'duration'    => '14 to 16 days',
		'suitability' => 'Restricted area',
		'price'       => 'From $1,100',
		'peek'        => 'The Manaslu Circuit and Tsum Valley: Tibetan-Buddhist villages on restricted-area trails with far fewer trekkers.',
	),
	array(
		'title'       => 'Langtang & Helambu',
		'category'    => 'short-near-kathmandu',
		'pill'        => 'Langtang',
		'duration'    => '3 to 16 days',
		'suitability' => 'Near Kathmandu',
		'price'       => 'From $350',
		'peek'        => 'Langtang Valley, Gosainkunda and short ridge walks like Chisapani and Ama Yangri, all a drive from Kathmandu.',
	),
	array(
		'title'       => 'Kanchenjunga & Makalu',
		'category'    => 'remote-restricted',
		'pill'        => 'Far East',
		'duration'    => '18 to 20 days',
		'suitability' => 'Teahouse or camping',
		'price'       => 'From $1,575',
		'peek'        => 'Kanchenjunga and Makalu base camps: long, quiet routes to the third- and fifth-highest mountains on Earth.',
	),
	array(
		'title'       => 'Dolpo, Rara & the Far West',
		'category'    => 'remote-restricted',
		'pill'        => 'Far West',
		'duration'    => '7 to 16 days',
		'suitability' => 'Remote',
		'price'       => 'From $1,000',
		'peek'        => 'Lower Dolpo, Rara Lake, Api Base Camp and Lower Mustang, for walkers who want Nepal without the crowds.',
	),
);

/* --------------------------------------------------------------- the copy */

$whatsapp = 'https://wa.me/9841771010';

$fields = array(
	// Hero.
	'hero_heading'              => 'The Himalaya, on foot. <em>Your</em> trek, paced for the altitude.',
	'hero_lead'                 => 'From a three-day ridge walk above Kathmandu to three weeks beneath Kanchenjunga, Nepal has a trek for every kind of walker. Pick a region below or tell us how high you want to go.',
	'hero_cta_label'            => 'Browse trek regions',
	'hero_cta_url'              => '#iflynepal-packages',
	'hero_cta_alt_label'        => 'WhatsApp a trek planner',
	'hero_cta_alt_url'          => $whatsapp,

	// Package grid.
	'listing_heading'           => 'Six regions to trek in <span class="iflynepal-ink-mark">Nepal</span>, pick your altitude.',
	'listing_lead'              => 'Most guests arrive set on Everest and leave curious about somewhere quieter. Every region below can be lengthened, shortened or paired with another once you’re talking to a trek planner.',
	'listing_annotation_static' => 'Walk it',
	'listing_annotation_words'  => "higher\nfarther\nyour way",

	// Reasons to come.
	'benefits_eyebrow'          => 'Why trek with one local team',
	'benefits_heading'          => 'What trekkers say made the trip work',
	'benefits_lead'             => 'Reasons past guests gave for walking with a single Nepal-based operator instead of piecing a trek together on arrival.',

	// Booking plans.
	'plans_eyebrow'             => 'Plans',
	'plans_heading'             => 'Three ways to book a <span class="iflynepal-ink-mark">Nepal trek</span>',
	'plans_lead'                => "Any trek on this page can run under any of these three plans.\nPrices shown are per person for a representative 14-day trek.",

	'plan_1_name'               => 'Group Departure',
	'plan_1_subtitle'           => 'Join a scheduled trek with a fixed small group',
	'plan_1_price'              => '$1,200',
	'plan_1_price_note'         => '/ 14 days',
	'plan_1_features'           => "Teahouse stays on the trail, hotel in Kathmandu\nFixed group of 12 to 16 trekkers\nAll meals on the trek\nLicensed guide, porters and permits included\nFixed departure dates, see below",
	'plan_1_cta_label'          => 'See group dates',
	'plan_1_cta_url'            => '#iflynepal-departures',

	'plan_2_name'               => 'Private & Tailored',
	'plan_2_subtitle'           => 'Your pace, your route, start any week',
	'plan_2_price'              => '$1,650',
	'plan_2_price_note'         => '/ 14 days',
	'plan_2_features'           => "Private licensed guide and porter team\nExtra acclimatisation days added where you need them\nSide trips like Gokyo or a high pass on request\nStart any week of the season\nFree 20-minute planning call before you book",
	'plan_2_cta_label'          => 'Ask about a private trek',
	'plan_2_cta_url'            => $whatsapp,
	'plan_2_featured'           => '1',

	'plan_3_name'               => 'Remote & Camping',
	'plan_3_subtitle'           => 'Fully supported routes beyond the teahouses',
	'plan_3_price'              => 'from $1,575',
	'plan_3_price_note'         => '/ 18 days',
	'plan_3_features'           => "Tents, cook and camp crew where lodges run out\nRestricted-area permits and liaison arranged\nSmall groups for Kanchenjunga, Makalu and Dolpo\nSatellite contact and evacuation plan on every trip\nOptional peak climbs such as Pisang or Cholatse",
	'plan_3_cta_label'          => 'Ask about remote treks',
	'plan_3_cta_url'            => $whatsapp,

	// Upcoming departures.
	'departures_eyebrow'        => 'Upcoming departures',
	'departures_heading'        => 'Next available group dates',
	'departures_lead'           => 'Private and camping treks start any week of the season. Group dates below are capped and do close.',
	'departures_foot'           => 'Sample dates for design review. Live dates and spot counts are pulled from the booking system in production.',

	// Comparison table.
	'compare_eyebrow'           => 'Doing the homework for you',
	'compare_heading'           => 'How a Nepal trek compares',
	'compare_lead'              => 'Written for the walker choosing where to go, not to declare a winner. The Himalaya and the Alps both reward a trek, for different reasons.',
	'compare_footnote'          => 'Competitor figures are general market ranges observed across public trek listings in 2026, not quotes from any single operator. Ask us for a like-for-like comparison against a specific trek you’re considering.',

	// Testimonials.
	'testimonials_eyebrow'      => 'What trekkers say',

	// FAQ.
	'faq_eyebrow'               => 'Before you book',
	'faq_heading'               => 'Questions every trekker asks <em>first</em>.',

	// Closing call to action.
	'final_eyebrow'             => 'Start with how high you want to go',
	'final_heading'             => 'Tell us the trek you have in mind. We’ll build the <em>route</em>.',
	'final_lead'                => 'A 20-minute call with a trek planner, no obligation, before you commit to any dates.',
	'final_cta_label'           => 'WhatsApp a trek planner',
	'final_cta_url'             => $whatsapp,
	'final_cta_alt_label'       => 'Schedule a call',
	'final_cta_alt_url'         => 'tel:+9779841771010',
);

$benefit_cards = array(
	array( 'title' => 'One licensed guide, start to finish', 'text' => 'Nepal requires a licensed guide in its national parks. Yours meets you in Kathmandu and walks every day of the trek with you.' ),
	array( 'title' => 'Permits handled before you land', 'text' => 'Sagarmatha, Annapurna and restricted-area permits are arranged ahead of time, so day one is a flight, not a queue at an office.' ),
	array( 'title' => 'Altitude safety is built into the pace', 'text' => 'Acclimatisation days are scheduled into every trek above 3,000m as standard, with a walk high, sleep low rule on each one.' ),
	array( 'title' => 'Porters paid and equipped properly', 'text' => 'One porter for every two trekkers, carrying fair loads on fair wages, with the gear and insurance the mountains demand.' ),
	array( 'title' => 'Teahouse or camping, your call', 'text' => 'Classic routes run teahouse to teahouse. Remote ones like Kanchenjunga or Dolpo can go fully supported with tents and a cook.' ),
	array( 'title' => 'One price, confirmed before you fly', 'text' => 'Guide, porters, permits, teahouse stays and trail meals are quoted together, so what’s on this page is what lands on your invoice.' ),
);

$departure_cards = array(
	array( 'pill' => '3 left', 'date' => '4 Oct to 17 Oct 2026', 'title' => '14-Day Everest Base Camp Trek', 'duration' => '14 days', 'price' => '$1,200' ),
	array( 'pill' => '6 left', 'date' => '10 Oct to 16 Oct 2026', 'title' => '7-Day Annapurna Base Camp Trek', 'duration' => '7 days', 'price' => '$800' ),
	array( 'pill' => '5 left', 'date' => '1 Nov to 14 Nov 2026', 'title' => '14-Day Manaslu Circuit Trek', 'duration' => '14 days', 'price' => '$1,100' ),
	array( 'pill' => '4 left', 'date' => '6 Mar to 17 Mar 2027', 'title' => '12-Day Gokyo Lake Trek', 'duration' => '12 days', 'price' => '$1,400' ),
	array( 'pill' => '2 left', 'date' => '3 Apr to 20 Apr 2027', 'title' => '18-Day Makalu Base Camp Trek', 'duration' => '18 days', 'price' => '$1,575' ),
);

$compare_table = array(
	'columns' => array(
		array( 'label' => 'Factor', 'note' => '' ),
		array( 'label' => 'iFly Nepal', 'note' => '' ),
		array( 'label' => 'Typical large Nepal trekking chain', 'note' => 'high-volume trek operators' ),
		array( 'label' => 'Typical Alps or Andes trek operator', 'note' => 'hut-to-hut and lodge treks' ),
	),
	'rows'    => array(
		array( 'Route choice', 'Classic, remote and restricted routes, from 3 to 24 days', 'Mostly Everest and Annapurna, remote routes subcontracted', 'Well-marked hut routes, few multi-week options' ),
		array( 'Typical group size', '12 to 16 on group departures, private from one', 'Often 15 to 25 in peak season', '8 to 16' ),
		array( 'Altitude and pace', 'Up to 5,545m, acclimatisation days built into the schedule', 'Similar terrain, rest days sometimes cut to shorten the trip', 'Mostly under 4,000m, little acclimatisation needed' ),
		array( 'Permits and guides', 'Park and restricted permits arranged before arrival, licensed guide included', 'Usually included, confirm in writing', 'Rarely needed, often self-guided' ),
		array( 'Porter welfare', 'One porter for two trekkers, fair loads and insurance', 'Varies, ask about load limits', 'Luggage transfer by vehicle between huts' ),
		array( 'Booking transparency', 'Fixed all-in price shown before you book', 'Varies, some quote trek-only', 'Varies, huts often paid locally' ),
	),
);

$faq_items = array(
	array( 'q' => 'How much does a trek in Nepal cost?', 'a' => 'Treks with iFly Nepal range from $350 for the 3-day Ama Yangri trek to $2,800 for the 21-day Ama Lapcha Pass. Most classic treks, including Everest Base Camp, fall between $800 and $1,500, with guide, porters, permits, trail meals and teahouse stays included.' ),
	array( 'q' => 'Do I need a guide to trek in Nepal?', 'a' => 'Yes, on most routes. Since 2023, foreign trekkers must walk with a licensed guide in Nepal’s national parks and conservation areas, and restricted areas such as Manaslu, Tsum and Dolpo have always required one.' ),
	array( 'q' => 'Do I need trekking experience for Everest Base Camp?', 'a' => 'No prior trekking experience is required, but a reasonable level of fitness helps. Most guests train with regular walking or hiking for 6 to 8 weeks beforehand, and the itinerary is paced with rest days either way.' ),
	array( 'q' => 'What permits do I need?', 'a' => 'Most treks need a national park or conservation area permit plus a local permit, such as the Sagarmatha National Park and Khumbu Pasang Lhamu permits for Everest. Restricted areas need a special permit on top. All of it is arranged before you arrive.' ),
	array( 'q' => 'When is the best time to trek in Nepal?', 'a' => 'March to May and September to November give the clearest mountain views and the most stable trail conditions. Lower treks like Chisapani or Poon Hill also work in winter, and rain-shadow routes such as Lower Mustang and Dolpo can be walked in the monsoon.' ),
	array( 'q' => 'How do you handle altitude sickness?', 'a' => 'Every trek above 3,000m has acclimatisation days built in, and our guides are trained to recognise the early signs. If anyone needs to descend, they do, and the evacuation plan is set before the trek begins.' ),
	array( 'q' => 'Teahouse or camping: what’s the difference?', 'a' => 'Teahouses are simple lodges with a bed, a shared dining room and hot meals, and they line the classic routes. Camping treks bring tents, a cook and a camp crew, and are needed on remote routes like Kanchenjunga or Dolpo.' ),
	array( 'q' => 'What is not included in the trek price?', 'a' => 'International flights, the Nepal visa fee, travel insurance that covers high-altitude rescue, personal gear, drinks and tips are not included unless stated on the specific trek.' ),
);

/* -------------------------------------------------------------------- drop */

if ( $drop ) {
	$keys = array_merge(
		array_keys( $fields ),
		array( 'benefit_cards', 'departure_cards', 'compare_table', 'faq_items', 'hero_image', 'final_image' )
	);

	foreach ( $keys as $key ) {
		delete_term_meta( $term_id, iflynepal_archive_meta_key( $key ) );
	}

	foreach ( $packages as $package ) {
		$existing = get_posts(
			array(
				'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
				'post_status' => 'any',
				'numberposts' => 1,
				'title'       => $package['title'],
				'fields'      => 'ids',
			)
		);

		if ( $existing ) {
			wp_delete_post( (int) $existing[0], true );
			echo "deleted package: {$package['title']}\n";
		}
	}

	foreach ( $categories as $slug ) {
		$child = get_term_by( 'slug', $slug, IFLYNEPAL_PACKAGE_TAXONOMY );

		if ( $child ) {
			wp_delete_term( $child->term_id, IFLYNEPAL_PACKAGE_TAXONOMY );
			echo "deleted category: $slug\n";
		}
	}

	echo "Dropped.\n";
	exit;
}

/* ------------------------------------------------------------------- write */

$images = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'numberposts'    => 12,
		'fields'         => 'ids',
		'orderby'        => 'ID',
	)
);

if ( ! $images ) {
	exit( "No images in the media library to stand in with.\n" );
}

printf(
	"Trek term #%d, slug '%s'%s\n",
	$term_id,
	$term->slug,
	'trekking-nepal' === $term->slug ? '' : " -> 'trekking-nepal'"
);
printf(
	"%d fields, %d reason cards, %d departures, %d compare rows, %d questions\n",
	count( $fields ),
	count( $benefit_cards ),
	count( $departure_cards ),
	count( $compare_table['rows'] ),
	count( $faq_items )
);
printf( "%d categories, %d packages, %d images to stand in\n", count( $categories ), count( $packages ), count( $images ) );

if ( ! $apply ) {
	echo "\nDry run — pass --apply to write.\n";
	exit;
}

if ( 'trekking-nepal' !== $term->slug ) {
	wp_update_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY, array( 'slug' => 'trekking-nepal' ) );
}

foreach ( $fields as $key => $value ) {
	update_term_meta( $term_id, iflynepal_archive_meta_key( $key ), $value );
}

$next = 0;

foreach ( $benefit_cards as $index => $card ) {
	$benefit_cards[ $index ]['image'] = (int) $images[ $next++ % count( $images ) ];
}

foreach ( $departure_cards as $index => $card ) {
	$departure_cards[ $index ]['link']  = $whatsapp;
	$departure_cards[ $index ]['image'] = (int) $images[ $next++ % count( $images ) ];
}

update_term_meta( $term_id, iflynepal_archive_meta_key( 'benefit_cards' ), $benefit_cards );
update_term_meta( $term_id, iflynepal_archive_meta_key( 'departure_cards' ), $departure_cards );
update_term_meta( $term_id, iflynepal_archive_meta_key( 'compare_table' ), $compare_table );
update_term_meta( $term_id, iflynepal_archive_meta_key( 'faq_items' ), $faq_items );
update_term_meta( $term_id, iflynepal_archive_meta_key( 'hero_image' ), (int) $images[0] );
update_term_meta( $term_id, iflynepal_archive_meta_key( 'final_image' ), (int) $images[1] );

foreach ( $categories as $name => $slug ) {
	if ( ! get_term_by( 'slug', $slug, IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		wp_insert_term(
			$name,
			IFLYNEPAL_PACKAGE_TAXONOMY,
			array(
				'slug'   => $slug,
				'parent' => $term_id,
			)
		);

		echo "created category: $name\n";
	}
}

foreach ( $packages as $package ) {
	$existing = get_posts(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status' => 'any',
			'numberposts' => 1,
			'title'       => $package['title'],
			'fields'      => 'ids',
		)
	);

	$id = $existing ? (int) $existing[0] : (int) wp_insert_post(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'post_status' => 'publish',
			'post_title'  => $package['title'],
		)
	);

	$child = get_term_by( 'slug', $package['category'], IFLYNEPAL_PACKAGE_TAXONOMY );

	wp_set_object_terms( $id, array( $term_id, (int) $child->term_id ), IFLYNEPAL_PACKAGE_TAXONOMY );

	foreach ( array( 'pill', 'duration', 'suitability', 'price', 'peek' ) as $key ) {
		update_post_meta( $id, iflynepal_package_meta_key( $key ), $package[ $key ] );
	}

	set_post_thumbnail( $id, (int) $images[ $next++ % count( $images ) ] );

	echo "package #$id {$package['title']}\n";
}

flush_rewrite_rules( false );

echo "\nWritten.\n";
