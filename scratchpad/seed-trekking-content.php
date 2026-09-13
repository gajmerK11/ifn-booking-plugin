<?php
/**
 * Brings Trekking up to the same state as Retreat and Tour: the three
 * Trekking categories get the copy their two sections need, and the
 * catalogue gains one fully written package — the Everest Base Camp Trek —
 * so the single-package template, altitude profile included, can be read
 * with real content in it.
 *
 * The words are trekking/trekking-package-single-design.html's own, typed in
 * directly rather than run through an extraction script: unlike the Tour
 * single (prose paragraphs with an Overnight/Altitude/Travel foot, folded
 * into the Retreat model because the two were to be identical), the
 * trekking design's day-foot carries a clean "Altitude: 5,364m" figure per
 * day, which is exactly the number the new Elevation field wants — there is
 * nothing to reconcile.
 *
 * Sample content on a dev site, not client content — the same standing as
 * P24. Dry run by default; --apply writes; --drop removes everything it made.
 */

define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = 'iflynepal.local';
require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply = in_array( '--apply', $argv, true );
$drop  = in_array( '--drop', $argv, true );
$tag   = $apply || $drop ? '' : '[dry] ';

function ifn_trek_say( $line ) {
	global $tag;
	echo $tag . $line . "\n";
}

/** The package this seeder owns, found by its slug so a re-run updates rather than duplicates. */
const IFN_TREK_PACKAGE_SLUG = 'everest-base-camp-trek';

/* ------------------------------------------------------------ the categories */

$categories = array(
	'classic-routes'        => array(
		'hero_heading'              => 'Classic <em>Routes</em>',
		'hero_lead'                 => 'Everest Base Camp and the Annapurna Circuit: the two routes most people picture when they picture trekking in Nepal, on well-stocked teahouse trails.',
		'hero_cta_label'            => 'See the treks',
		'hero_cta_url'              => '#treks',
		'hero_cta_alt_label'        => 'Talk to a trek planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the trek that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, altitude and price, then open any trek for its full itinerary and dates.',
		'listing_annotation_static' => 'Walk it',
		'listing_annotation_words'  => "teahouse to teahouse\nat your pace\nwith a guide",
	),
	'remote-restricted'     => array(
		'hero_heading'              => 'Remote & <em>Restricted</em>',
		'hero_lead'                 => 'Manaslu, Kanchenjunga, Makalu and Dolpo: permit-controlled trails with a fraction of the crowds, walked with a licensed guide and, where the lodges run out, a full camping crew.',
		'hero_cta_label'            => 'See the treks',
		'hero_cta_url'              => '#treks',
		'hero_cta_alt_label'        => 'Talk to a trek planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the trek that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, altitude and price, then open any trek for its full itinerary and dates.',
		'listing_annotation_static' => 'Go',
		'listing_annotation_words'  => "farther out\noff the grid\nrestricted-area",
	),
	'short-near-kathmandu'  => array(
		'hero_heading'              => 'Short & Near <em>Kathmandu</em>',
		'hero_lead'                 => 'Langtang, Helambu and ridge walks like Chisapani: real mountain trekking that starts and ends within a few hours of the capital.',
		'hero_cta_label'            => 'See the treks',
		'hero_cta_url'              => '#treks',
		'hero_cta_alt_label'        => 'Talk to a trek planner',
		'hero_cta_alt_url'          => '#iflynepal-enquiry',
		'listing_heading'           => 'Find the trek that <em>fits you.</em>',
		'listing_lead'              => 'Compare length, altitude and price, then open any trek for its full itinerary and dates.',
		'listing_annotation_static' => 'Go',
		'listing_annotation_words'  => "in a weekend\nwithout altitude\nclose to home",
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

		ifn_trek_say( sprintf( 'cleared the archive copy on %s', $term->name ) );
	}

	$existing = get_page_by_path( IFN_TREK_PACKAGE_SLUG, OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

	if ( $existing ) {
		wp_delete_post( $existing->ID, true );
		ifn_trek_say( sprintf( 'deleted package #%d %s', $existing->ID, $existing->post_title ) );
	}

	ifn_trek_say( 'done' );

	return;
}

/* ------------------------------------------------------------- 1. categories */

foreach ( $categories as $slug => $fields ) {
	$term = get_term_by( 'slug', $slug, IFLYNEPAL_PACKAGE_TAXONOMY );

	if ( ! $term ) {
		ifn_trek_say( sprintf( 'MISSING term %s — run seed-trekking-archive.php --apply first', $slug ) );
		continue;
	}

	// The hero photograph is borrowed from a package already filed under this
	// category, so a trek page opens on a trek picture rather than at random.
	$in_category = get_posts(
		array(
			'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
			'numberposts' => 4,
			'fields'      => 'ids',
			'tax_query'   => array( array( 'taxonomy' => IFLYNEPAL_PACKAGE_TAXONOMY, 'field' => 'term_id', 'terms' => $term->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
		ifn_trek_say( sprintf( 'fill %-22s (%s) with %d fields', $slug, $term->name, count( $fields ) ) );
		continue;
	}

	foreach ( $fields as $key => $value ) {
		update_term_meta( $term->term_id, iflynepal_archive_meta_key( $key ), $value );
	}

	ifn_trek_say( sprintf( 'filled %-22s (%s) — %d fields', $slug, $term->name, count( $fields ) ) );
}

/* ---------------------------------------------------------- 2. the package */

/*
 * Title, elevation (from the design's own "Altitude:" / "High point:" line —
 * digits only, feeding the new Elevation field the altitude profile chart is
 * plotted from), sub-line and the day's timeline rows, split from the design's
 * two prose paragraphs. Verified against the design's own SVG chart data
 * before typing this in: the fourteen elevation figures below are exactly its
 * fourteen points (1,400 / 2,610 / 3,440 / 3,440 / 3,860 / 4,410 / 4,410 /
 * 4,940 / 5,364 / 5,545 / 3,440 / 2,860 / 1,400 / 1,400).
 */
$days = array(
	array(
		'title'    => 'Arrival in Kathmandu',
		'meta'     => '1,400m · Airport pick-up and trek briefing',
		'elevation' => '1400',
		'rows'     => array(
			array( 'Arrival', 'Welcome to Nepal. When you land at Tribhuvan International Airport, someone will meet you with a sign bearing your name and take you to your hotel. Use the day to rest after your flight, or walk the lively streets of Thamel, where many shops sell any last-minute trekking gear you need.' ),
			array( 'Evening', 'In the evening your guide gives you a full talk about the trek: what to expect, and answers to all your questions. This first day helps you settle in and gets you ready for the adventure ahead.' ),
		),
	),
	array(
		'title'    => 'Fly to Lukla, Walk to Phakding',
		'meta'     => '2,860m → 2,610m · 35-minute flight, 3–4 hours walking',
		'elevation' => '2610',
		'rows'     => array(
			array( 'Morning', 'Start early for the thrilling 35-minute flight to Tenzing-Hillary Airport in Lukla, often called the world’s riskiest airport. The plane is small, the landing strip is short, and the views of huge peaks and deep valleys from the window are amazing.' ),
			array( 'Afternoon', 'After landing you meet your porters, who carry your main bags, and the trek begins. Today is an easy walk of about 3 to 4 hours, downhill along the Dudh Koshi river through small villages like Chheplung, to a teahouse in Phakding.' ),
		),
	),
	array(
		'title'    => 'Walk to Namche Bazaar',
		'meta'     => '3,440m · 6–7 hours walking',
		'elevation' => '3440',
		'rows'     => array(
			array( 'Morning', 'A hard but good day of about 6 to 7 hours. You cross the Dudh Koshi many times on swinging suspension bridges, including the famous Hillary Bridge, draped in prayer flags that wave in the wind.' ),
			array( 'Afternoon', 'The final 2 to 3 hours are a steep, steady climb up to Namche Bazaar, the Khumbu’s main trading town. It sits on a hillside, busy with trekkers, and the views get better the higher you go.' ),
		),
	),
	array(
		'title'    => 'Rest Day in Namche Bazaar',
		'meta'     => '3,440m · Acclimatisation hike to 3,880m',
		'elevation' => '3440',
		'rows'     => array(
			array( 'Morning', 'An important day to get used to the height. We follow the rule "walk high, sleep low": in the morning you walk up to Hotel Everest View at 3,880m for your first wide view of Mount Everest, Lhotse, Nuptse and the beautiful Ama Dablam.' ),
			array( 'Afternoon', 'The afternoon is free to explore Namche’s bakeries, cafes and gear shops, or visit the Sherpa Culture Museum. The day helps your body adjust and lets you enjoy local life.' ),
		),
	),
	array(
		'title'    => 'Walk to Tengboche',
		'meta'     => '3,860m · 5–6 hours walking',
		'elevation' => '3860',
		'rows'     => array(
			array( 'Morning', 'The first few hours are mostly flat through green woods, then the path drops steeply to the river at Phunki Thanga. After lunch comes a tough 2-hour climb through rhododendron forest, bright with flowers in spring.' ),
			array( 'Afternoon', 'The climb brings you to Tengboche, home to the biggest and most important monastery in the region. With monks chanting and the monastery standing against the mountains, it is a calm and special place.' ),
		),
	),
	array(
		'title'    => 'Walk to Dingboche',
		'meta'     => '4,410m · 5–6 hours walking',
		'elevation' => '4410',
		'rows'     => array(
			array( 'Morning', 'Today you go above the tree line. You drop to Deboche, cross the Imja Khola and pass through Pangboche, which has the oldest monastery in the Khumbu.' ),
			array( 'Afternoon', 'The land turns dry and rocky as you climb to Dingboche, a village of stone walls and fields at the foot of Ama Dablam. The sun feels strong, the wind can be cool, and the views are wide open.' ),
		),
	),
	array(
		'title'    => 'Rest Day in Dingboche',
		'meta'     => '4,410m · Acclimatisation hike to Nagarjun Hill, 5,100m',
		'elevation' => '4410',
		'rows'     => array(
			array( 'Morning', 'Another key acclimatisation day, which helps your body make more red blood cells. The best choice is a hard morning walk up Nagarjun Hill at 5,100m.' ),
			array( 'Afternoon', 'The climb is steep, but it is very good preparation for the higher days ahead, and the 360-degree view from the top takes in Ama Dablam, Makalu, Lhotse and the peaks all around.' ),
		),
	),
	array(
		'title'    => 'Walk to Lobuche',
		'meta'     => '4,940m · 5–6 hours walking',
		'elevation' => '4940',
		'rows'     => array(
			array( 'Morning', 'The path climbs gently through a wide valley to the Thukla Pass and the Everest Memorial, stone cairns and prayer flags honouring climbers who have died on the mountain. It is a serious and humbling place.' ),
			array( 'Afternoon', 'From there the trail levels out alongside the Khumbu Glacier to Lobuche. The land is rocky and cold, and you can feel the chill of the ice as the big mountains close in.' ),
		),
	),
	array(
		'title'    => 'Walk to Gorak Shep & Everest Base Camp',
		'meta'     => '5,164m, Base Camp 5,364m · The day you have waited for',
		'elevation' => '5364',
		'rows'     => array(
			array( 'Morning', 'The rocky path to Gorak Shep follows the Khumbu Glacier over loose stones. Gorak Shep is the last small settlement before base camp, with a few teahouses; after a quick lunch you leave your main pack for the 2 to 3-hour walk to Everest Base Camp.' ),
			array( 'Afternoon', 'Standing on the Khumbu Glacier, surrounded by tall peaks and the famous icefall, is a feeling you cannot describe. You will see the tents of climbing teams and hear the ice crack. After photos, you return to Gorak Shep for the night.' ),
		),
	),
	array(
		'title'    => 'Kala Patthar, then down to Pheriche',
		'meta'     => '5,545m → 4,371m · Sunrise climb to the highest point',
		'elevation' => '5545',
		'rows'     => array(
			array( 'Early morning', 'An optional but highly recommended climb. You start before dawn, in the cold and dark, up to Kala Patthar, the highest point of the trek, for the best sunrise view of Mount Everest as the peak turns gold and pink.' ),
			array( 'Afternoon', 'After the view you return to Gorak Shep for breakfast, then begin the long walk down to Pheriche. Going down is easier on your lungs; the air gets thicker and warmer as you drop.' ),
		),
	),
	array(
		'title'    => 'Walk to Namche Bazaar',
		'meta'     => '3,440m · 7–8 hours walking',
		'elevation' => '3440',
		'rows'     => array(
			array( 'Morning', 'The walk down keeps going, and gravity helps. You retrace your steps through Pangboche and Tengboche, enjoying the thicker air and warmer weather.' ),
			array( 'Afternoon', 'It is a long day of about 7 to 8 hours, but the green returns as you go lower, your body feels less tired from the height, and the feeling of what you have done is huge.' ),
		),
	),
	array(
		'title'    => 'Walk to Lukla',
		'meta'     => '2,860m · 6–7 hours · Last day on foot',
		'elevation' => '2860',
		'rows'     => array(
			array( 'Morning', 'The last day of walking, about 6 to 7 hours from Namche back to Lukla. The path goes up and down, and the final climb into Lukla is a good end to the trek.' ),
			array( 'Evening', 'This evening is a time to celebrate with your guide and porters, and to thank them. You will feel tired but happy, back where the trek began.' ),
		),
	),
	array(
		'title'    => 'Fly back to Kathmandu',
		'meta'     => '1,400m · Morning flight, farewell dinner',
		'elevation' => '1400',
		'rows'     => array(
			array( 'Morning', 'An early morning flight takes you back to Kathmandu, with one last look at the mountains from the air. The rest of the day is free to buy gifts, rest at your hotel or see more of the city.' ),
			array( 'Evening', 'A goodbye dinner celebrates your successful trek. After the quiet of the mountains, you will feel the buzz of the city all over again.' ),
		),
	),
	array(
		'title'    => 'Final Trip Home',
		'meta'     => 'Airport transfer',
		'elevation' => '1400',
		'rows'     => array(
			array( 'Departure', 'You are taken to the airport for your flight home, full of great memories of the Himalaya, and with stories to tell for years to come.' ),
		),
	),
);

$itinerary_days = array();

foreach ( $days as $day ) {
	$rows = array();

	foreach ( $day['rows'] as $row ) {
		$rows[] = array(
			'time' => $row[0],
			'text' => $row[1],
		);
	}

	$itinerary_days[] = array(
		'title'     => $day['title'],
		'elevation' => $day['elevation'],
		'meta'      => $day['meta'],
		'timeline'  => $rows,
		'summary'   => $rows[0]['text'],
	);
}

$faqs = array(
	array( 'q' => 'What is the Everest Base Camp Trek?', 'a' => 'It is a high-mountain walk in Nepal that takes you to the base camp of Mount Everest, the world’s highest mountain, on a journey through the Khumbu region.' ),
	array( 'q' => 'Do I need a guide for the EBC Trek?', 'a' => 'Yes. Since 2023, people from other countries must use a licensed guide in Nepal’s national parks. This is a key safety step, and your guide helps you stay safe and learn about the area.' ),
	array( 'q' => 'How much does the EBC Trek cost?', 'a' => 'Prices across the market range from about $1,500 to over $3,000 USD per person, depending on the level of service, whether you book with a local or an international company, and what is included. Our 14-day trek is USD 1,200 per person.' ),
	array( 'q' => 'What are conditions like in Nepal after COVID-19?', 'a' => 'Trekking in Nepal is back to normal. The trails are fully open and the teahouses are working. The main lasting change is the required guide rule. Full travel insurance that covers trip changes and health problems is important.' ),
	array( 'q' => 'What permits do you need for the EBC Trek?', 'a' => 'You need the Khumbu Pasang Lhamu Rural Municipality Permit and the Sagarmatha National Park Permit. We get these for you; they allow you to enter the protected areas.' ),
);

$fields = array(
	'pill'              => 'Everest',
	'duration'          => '14 days',
	'suitability'       => 'Khumbu, Everest Region',
	'price'             => 'From $1,200',
	'peek'              => 'Everest Base Camp, Gokyo Lakes and the high passes, through Sherpa villages and Sagarmatha National Park.',

	'heading'           => 'Everest <em>Base Camp</em> Trek',

	'glance_destination' => 'Khumbu, Nepal',
	'glance_duration'    => '14 days',
	'glance_activities'  => 'Trekking',
	'glance_meals'       => 'Included on trek',
	'glance_stay'        => 'Hotel & teahouses',
	'glance_altitude'    => '5,545m',
	'glance_group'       => '12–16',
	'glance_best_time'   => 'Mar–May, Sep–Nov',

	'overview_intro'    => 'Do you dream of tall mountains, and of standing near the world’s highest peak? The Everest Base Camp trek is more than a walk. It is the trip of a lifetime, one that tests your strength and takes you to the heart of the Himalaya, among huge peaks and deep valleys where the air is clean and crisp.',
	'overview_body'     => "The walk to Everest Base Camp is one of the best treks in the world. It takes you deep into Sagarmatha National Park, a place known around the globe that protects many kinds of plants and animals. The trail runs from green forests with bright flowers to dry, beautiful mountain land, winding through small towns, past clear rivers and tall waterfalls.\n\nAlong the way, the Sherpa people will welcome you. You will visit old monasteries and walk among some of the highest and most beautiful mountains on Earth. The air is thin but clean, and the quiet of the mountains is peaceful.\n\nTravel in the Everest region is increasingly focused on doing right by the people and the land: supporting local businesses, keeping the mountains clean and leaving no trace. Your journey helps both, which makes the trip even more special.",
	'highlights'        => "Journey to Everest Base Camp at 5,364m, on the Khumbu Glacier beneath the famous icefall\nAmazing flight views on the 35-minute flight into Tenzing-Hillary Airport at Lukla\nThe wilderness of Sagarmatha National Park, from rhododendron forest to high, dry mountain land\nNamche Bazaar, the Khumbu’s trading town, with its bakeries and Sherpa Culture Museum\nSherpa culture in the villages and teahouses that line the trail\nHotel Everest View at 3,880m, for your first wide view of Everest, Lhotse and Ama Dablam\nTengboche Monastery, the largest and most important in the region\nThe Khumbu Glacier and Icefall, up close at base camp\nSunrise from Kala Patthar at 5,545m, the highest point of the trek\nMountain views of Everest, Lhotse, Nuptse, Makalu and Ama Dablam",

	'itinerary_heading' => 'Fourteen days to the foot of <em>Everest</em>.',
	'altitude_note'     => 'Base camp at 5,364m on Day 9, then Kala Patthar, 5,545m, on Day 10, with rest days at Namche and Dingboche on the way up.',

	'dates_heading'     => 'Choose your <span class="iflynepal-ink-mark">start date</span>.',
	'dates_lead'        => 'Secure your spot now on our best-selling trip. Spaces fill up fast, so book early, and feel free to ask any questions later.',
	'price_amount'      => '1200',
	'price_currency'    => 'USD',
	'duration_days'     => '14',
	'included'          => "Airport transfers in Kathmandu\nTrekking permits (Khumbu Pasang Lhamu Rural Municipality Permit and Sagarmatha National Park Permit)\nAn experienced trekking guide\nPorters to carry your main bags (usually one porter for two trekkers)\nAll meals during the trek (breakfast, lunch and dinner)\nTeahouse stays during the trek\nFirst-aid kit for the group",
	'excluded'          => "International flights to and from Nepal\nNepali visa fees\nTravel insurance (must cover high-altitude trekking and emergency rescue)\nPersonal trekking gear and clothing\nDrinks (bottled water, soft drinks, alcohol), hot showers and charging fees at teahouses\nTips for guides and porters\nPersonal expenses or extra activities not listed in the plan\nMeals and stays in Kathmandu",

	'price_eyebrow'     => 'All inclusive price',
	'price_points'      => "Accommodation & meals\nTrek guide, porters & staff\nFlights & transfers\nTrek map & certificate\nPermit & entry fees",
	'inquire_link'      => 'https://wa.me/9841771010?text=Hi%2C%20I%27d%20like%20to%20ask%20about%20the%20Everest%20Base%20Camp%20Trek',
	'price_foot'        => 'No payment needed to enquire',

	'expert_name'       => 'Prem',
	'expert_place'      => 'Nepal',
	'expert_label'      => '+977-9841771010',
	'expert_link'       => 'https://wa.me/9779841771010',

	'packing_heading'   => 'What to <em>bring</em>.',
	'packing_items'     => "Clothing — 3 to 4 wicking base layers; 2 to 3 fleece or down mid layers; a waterproof, windproof outer jacket and pants (like Gore-Tex); a warm fleece or wool hat, a wide-brim sun hat, a buff or balaclava, and liner plus warm waterproof gloves.\nFootwear — Broken-in waterproof boots that cover your ankles, 5 to 6 pairs of wool or synthetic trekking socks, liner socks against blisters, and light camp shoes or sandals for the teahouse.\nSleep & gear — A four-season sleeping bag rated to at least −15°C (5°F) with a liner, trekking poles, a headlamp with spare batteries, and bottles or a bladder holding at least 2 litres.\nHealth & sun — High-UV sunglasses, SPF 50+ sunscreen and SPF lip balm, a first-aid kit with blister plasters and your own medicines (ask your doctor about Diamox), light toiletries and a quick-dry towel.\nDocuments & extras — Passport and Nepal visa, Nepali rupees for tips and snacks, a printed insurance policy that covers high-altitude rescue, a camera and power bank, and energy bars, nuts and chocolate for the trail.",

	'map_heading'       => 'Where you’ll <em>walk</em>.',
	'map_embed'         => 'https://maps.google.com/maps?q=Everest%20Base%20Camp%2C%20Nepal&z=10&output=embed',
	'map_place'         => 'Everest Base Camp, Khumbu',
	'map_note'          => '5,364m, reached from Lukla on Day 9',
	'map_link'          => 'https://www.google.com/maps/search/?api=1&query=Everest+Base+Camp+Nepal',

	'faq_heading'       => 'Questions, answered <em>simply</em>.',
	'faq_lead'          => 'Still unsure? Message Prem on WhatsApp and get an answer from someone who runs the trek.',
);

if ( ! $apply ) {
	ifn_trek_say(
		sprintf(
			'make package "%s" — %d fields, %d days (%d timeline rows), %d FAQs, %d gallery photos',
			wp_strip_all_tags( $fields['heading'] ),
			count( $fields ),
			count( $itinerary_days ),
			array_sum(
				array_map(
					function ( $d ) {
						return count( $d['timeline'] );
					},
					$itinerary_days
				)
			),
			count( $faqs ),
			min( 8, count( $image_ids ) )
		)
	);
	ifn_trek_say( 'done' );

	return;
}

$existing = get_page_by_path( IFN_TREK_PACKAGE_SLUG, OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

$post_data = array(
	'post_type'   => IFLYNEPAL_PACKAGE_POST_TYPE,
	'post_status' => 'publish',
	'post_title'  => wp_slash( wp_strip_all_tags( $fields['heading'] ) ),
	'post_name'   => IFN_TREK_PACKAGE_SLUG,
);

if ( $existing ) {
	$post_data['ID'] = $existing->ID;
	$package_id      = wp_update_post( $post_data, true );
} else {
	$package_id = wp_insert_post( $post_data, true );
}

if ( is_wp_error( $package_id ) ) {
	ifn_trek_say( 'FAILED to create the package: ' . $package_id->get_error_message() );

	return;
}

// Filed under Trek > Classic routes, which is what the Everest Base Camp trek is.
$classic = get_term_by( 'slug', 'classic-routes', IFLYNEPAL_PACKAGE_TAXONOMY );
$trek    = get_term_by( 'slug', 'trekking-nepal', IFLYNEPAL_PACKAGE_TAXONOMY );
$terms   = array_filter( array( $classic ? $classic->term_id : 0, $trek ? $trek->term_id : 0 ) );

wp_set_object_terms( $package_id, $terms, IFLYNEPAL_PACKAGE_TAXONOMY );

foreach ( $fields as $key => $value ) {
	update_post_meta( $package_id, iflynepal_package_meta_key( $key ), $value );
}

update_post_meta( $package_id, iflynepal_package_meta_key( 'itinerary_days' ), $itinerary_days );
update_post_meta( $package_id, iflynepal_package_meta_key( 'faq_items' ), $faqs );

/*
 * The lead photograph is the one the Everest Region card already uses, so the
 * trek page opens on a Himalayan photograph rather than on whatever was
 * uploaded last. The rest of the gallery is made up from the library; every
 * one of them is a stand-in (P12).
 */
$lead    = 0;
$sibling = get_page_by_path( 'everest-region', OBJECT, IFLYNEPAL_PACKAGE_POST_TYPE );

if ( $sibling ) {
	$lead = get_post_thumbnail_id( $sibling->ID );
}

$gallery = array_values( array_unique( array_merge( $lead ? array( $lead ) : array(), $image_ids ) ) );

if ( $gallery ) {
	set_post_thumbnail( $package_id, $gallery[0] );
	update_post_meta( $package_id, iflynepal_package_meta_key( 'gallery' ), implode( ',', array_slice( $gallery, 0, 8 ) ) );
}

ifn_trek_say(
	sprintf(
		'made package #%d "%s" — %d fields, %d days, %d FAQs, %d photos, terms: %s',
		$package_id,
		get_the_title( $package_id ),
		count( $fields ),
		count( $itinerary_days ),
		count( $faqs ),
		count( array_slice( $image_ids, 0, 8 ) ),
		implode( ', ', wp_get_post_terms( $package_id, IFLYNEPAL_PACKAGE_TAXONOMY, array( 'fields' => 'slugs' ) ) )
	)
);
ifn_trek_say( 'URL: ' . get_permalink( $package_id ) );
ifn_trek_say( 'done' );
