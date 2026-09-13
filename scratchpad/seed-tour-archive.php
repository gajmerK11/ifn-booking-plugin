<?php
/**
 * Fills the Tour archive from nepal-tour-archive-design.html.
 *
 * Content, not code: the Tour archive uses the same nine sections as Retreat
 * (P14 — checked against the design file, they match section for section), so
 * nothing had to be built for it beyond the departures footnote. What was
 * missing was the copy, the three categories the filter row is derived from,
 * and packages for the grid.
 *
 * The copy is the SEO team's, transcribed from the design file. The packages
 * and the departure cards are SAMPLE CONTENT for design review — same standing
 * as P24 — and `--drop` takes all of it away again.
 *
 * Images are existing attachments from the media library, reused rather than
 * sideloaded: the design's own photographs are Unsplash stand-ins and the real
 * photography is still owed (P12).
 *
 * Usage:
 *   php seed-tour-archive.php            dry run
 *   php seed-tour-archive.php --apply    write
 *   php seed-tour-archive.php --drop     remove everything it wrote
 *
 * @package IFly_Nepal
 */

define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );

$_SERVER['HTTP_HOST'] = 'iflynepal.local';

require dirname( __DIR__, 4 ) . '/wp-load.php';

$apply = in_array( '--apply', $argv, true );
$drop  = in_array( '--drop', $argv, true );

$term = get_term_by( 'name', 'Tour', IFLYNEPAL_PACKAGE_TAXONOMY );

if ( ! $term ) {
	exit( "No Tour term.\n" );
}

$term_id = (int) $term->term_id;

/* ------------------------------------------------------------- categories */

$categories = array(
	'Trekking & Adventure' => 'adventure',
	'Culture & Festivals'  => 'culture',
	'Nature & Community'   => 'nature',
);

/* ---------------------------------------------------------------- packages */

$packages = array(
	array(
		'title'       => 'Himalayan Trekking',
		'category'    => 'adventure',
		'pill'        => 'Trekking',
		'duration'    => '7 to 21 days',
		'suitability' => 'Teahouse or camping',
		'price'       => 'From $1,200',
		'peek'        => 'Everest, Annapurna, Manaslu and further-off routes like Makalu and Rolwaling, with teahouse or camping options.',
	),
	array(
		'title'       => 'Cultural & Heritage Tours',
		'category'    => 'culture',
		'pill'        => 'Culture',
		'duration'    => '1 to 8 days',
		'suitability' => 'Kathmandu Valley',
		'price'       => 'From $150',
		'peek'        => 'Kathmandu, Bhaktapur and Patan Durbar Squares, Pashupatinath, Boudhanath and Swayambhunath, with a historian-trained local guide.',
	),
	array(
		'title'       => 'Wildlife & Nature Safari',
		'category'    => 'nature',
		'pill'        => 'Wildlife',
		'duration'    => '2 to 4 days',
		'suitability' => 'Chitwan',
		'price'       => 'From $280',
		'peek'        => 'Chitwan National Park by jeep, canoe and guided walk, with a naturalist who tracks the same animals daily.',
	),
	array(
		'title'       => 'Festival & Pilgrimage Tours',
		'category'    => 'culture',
		'pill'        => 'Festivals',
		'duration'    => '5 to 15 days',
		'suitability' => 'Seasonal dates',
		'price'       => 'From $800',
		'peek'        => 'Tihar, Dashain and Holi city tours, plus pilgrimage treks such as Badimalika and Muktinath for guests seeking the religious side of Nepal.',
	),
	array(
		'title'       => 'Volunteer & Community Programs',
		'category'    => 'nature',
		'pill'        => 'Community',
		'duration'    => '2 to 4 weeks',
		'suitability' => 'Homestay',
		'price'       => 'From $500',
		'peek'        => 'Youth mentorship and elderly care programs based in local communities, homestay included, structured but genuinely useful work.',
	),
	array(
		'title'       => 'Adventure Activities',
		'category'    => 'adventure',
		'pill'        => 'Adventure',
		'duration'    => '1 to 3 days',
		'suitability' => 'Kathmandu & Pokhara',
		'price'       => 'From $350',
		'peek'        => 'Half and full-day activity trips around Kathmandu and Pokhara, bookable alone or stacked onto a longer itinerary.',
	),
);

/* --------------------------------------------------------------- the copy */

$whatsapp = 'https://wa.me/9841771010';

$fields = array(
	// Hero.
	'hero_heading'              => 'A Nepal tour that’s more than a mountain photo. <em>Yours</em>, built around what you actually want to do.',
	'hero_lead'                 => 'Trekking, heritage cities, wildlife safaris, festivals and real volunteer work, all run by one local team. Pick a category below or tell us the version of Nepal you’re after.',
	'hero_cta_label'            => 'Browse tour types',
	'hero_cta_url'              => '#iflynepal-packages',
	'hero_cta_alt_label'        => 'WhatsApp a trip planner',
	'hero_cta_alt_url'          => $whatsapp,

	// Package grid.
	'listing_heading'           => 'Six ways to see <span class="iflynepal-ink-mark">Nepal</span>, pick by what pulls you.',
	'listing_lead'              => 'Most guests come here wanting one thing and end up combining two. Every category below can be extended or shortened once you’re talking to a trip planner.',
	'listing_annotation_static' => 'Find your way',
	'listing_annotation_words'  => "up\nacross\nthrough",

	// Reasons to come.
	'benefits_eyebrow'          => 'Why book through one local team',
	'benefits_heading'          => 'What guests say made the trip work',
	'benefits_lead'             => 'Reasons past guests gave for choosing a single Nepal-based operator over booking each leg separately.',

	// Booking plans.
	'plans_eyebrow'             => 'Plans',
	'plans_heading'             => 'Three ways to book a <span class="iflynepal-ink-mark">Nepal tour</span>',
	'plans_lead'                => "Any tour type on this page can run under any of these three plans.\nPrices shown are per person for a representative 7-day trip.",

	'plan_1_name'               => 'Group Departure',
	'plan_1_subtitle'           => 'Join a scheduled trip with a fixed small group',
	'plan_1_price'              => '$800',
	'plan_1_price_note'         => '/ 7 days',
	'plan_1_features'           => "Shared teahouse, guesthouse or lodge accommodation\nFixed group, max 10 guests\nAll meals on trek, breakfast in cities\nGuide, porter support and permits included\nFixed departure dates, see below",
	'plan_1_cta_label'          => 'See group dates',
	'plan_1_cta_url'            => '#iflynepal-departures',

	'plan_2_name'               => 'Private & Tailored',
	'plan_2_subtitle'           => 'Your pace, your route, start any week',
	'plan_2_price'              => '$1,350',
	'plan_2_price_note'         => '/ 7 days',
	'plan_2_features'           => "Private guide and vehicle throughout\nRoute and daily pace adjusted to your fitness level\nUpgradeable accommodation in Kathmandu and Pokhara\nStart any week of the year\nFree 20-minute planning call before you book",
	'plan_2_cta_label'          => 'Ask about a private trip',
	'plan_2_cta_url'            => $whatsapp,
	'plan_2_featured'           => '1',

	'plan_3_name'               => 'Volunteer & Immersion',
	'plan_3_subtitle'           => 'Homestay-based, community-run programs',
	'plan_3_price'              => 'from $500',
	'plan_3_price_note'         => '/ 2 weeks',
	'plan_3_features'           => "Local homestay accommodation and meals\nStructured program with a community partner\nOrientation and language basics on arrival\nOptional extension into trekking or a cultural tour\nBackground-checked placement, not a drop-in visit",
	'plan_3_cta_label'          => 'Ask about volunteer programs',
	'plan_3_cta_url'            => $whatsapp,

	// Upcoming departures.
	'departures_eyebrow'        => 'Upcoming departures',
	'departures_heading'        => 'Next available group dates',
	'departures_lead'           => 'Private and Volunteer plans start any week. Group dates below are capped and do close.',
	'departures_foot'           => 'Sample dates for design review. Live dates and spot counts are pulled from the booking system in production.',

	// Comparison table.
	'compare_eyebrow'           => 'Doing the homework for you',
	'compare_heading'           => 'How a Nepal tour compares',
	'compare_lead'              => 'Written for the guest choosing a destination, not to declare a winner. Nepal and Southeast Asia both reward a trip, for different reasons.',
	'compare_footnote'          => 'Competitor figures are general market ranges observed across public tour listings in 2026, not quotes from any single operator. Ask us for a like-for-like comparison against a specific trip you’re considering.',

	// Testimonials.
	'testimonials_eyebrow'      => 'What guests say',

	// FAQ.
	'faq_eyebrow'               => 'Before you book',
	'faq_heading'               => 'Questions every guest asks <em>first</em>.',

	// Closing call to action.
	'final_eyebrow'             => 'Start with what pulls you',
	'final_heading'             => 'Tell us the version of Nepal you want. We’ll build the <em>route</em>.',
	'final_lead'                => 'A 20-minute call with a trip planner, no obligation, before you commit to any dates.',
	'final_cta_label'           => 'WhatsApp a trip planner',
	'final_cta_url'             => $whatsapp,
	'final_cta_alt_label'       => 'Schedule a call',
	'final_cta_alt_url'         => 'tel:+9779841771010',
);

$benefit_cards = array(
	array( 'title' => 'Permits handled before you land', 'text' => 'TIMS cards, national park entry and restricted-area permits are arranged ahead of time, so you’re not queuing at an office on day one.' ),
	array( 'title' => 'One guide across the whole trip', 'text' => 'The same guide who takes you through Kathmandu can often stay with you into the mountains, so you’re not re-explaining your plans to a new face.' ),
	array( 'title' => 'Categories most operators split up', 'text' => 'Trek, culture, wildlife, volunteer and festival trips through one team means you can combine two in a single itinerary without two bookings.' ),
	array( 'title' => 'Altitude safety is built into the pace', 'text' => 'Rest and acclimatisation days are scheduled into every trek above 3,000m as standard, not offered as a paid add-on.' ),
	array( 'title' => 'Real access, not tourist theatre', 'text' => 'Festival and pilgrimage trips are timed to actual Nepali calendar dates, and volunteer programs run inside real community structures.' ),
	array( 'title' => 'One price, confirmed before you fly', 'text' => 'Accommodation, meals, guide and permits are quoted together, so what’s on this page is what lands on your invoice.' ),
);

$departure_cards = array(
	array( 'pill' => '3 left', 'date' => '28 Sep to 12 Oct 2026', 'title' => '15-Day Everest Base Camp Trek', 'duration' => '15 days', 'price' => '$1,450' ),
	array( 'pill' => '6 left', 'date' => '1 Nov to 5 Nov 2026', 'title' => '5-Day Tihar Festival Tour, Kathmandu', 'duration' => '5 days', 'price' => '$800' ),
	array( 'pill' => '7 left', 'date' => '10 Nov to 17 Nov 2026', 'title' => 'Kathmandu, Pokhara & Chitwan Cultural Tour', 'duration' => '8 days', 'price' => '$920' ),
	array( 'pill' => '2 left', 'date' => '1 Mar to 19 Mar 2027', 'title' => '18-Day Makalu Base Camp Trek', 'duration' => '18 days', 'price' => '$1,950' ),
	array( 'pill' => '5 left', 'date' => '4 Jan to 18 Jan 2027', 'title' => '2-Week Youth Empowerment Volunteer Program', 'duration' => '14 days', 'price' => '$500' ),
);

$compare_table = array(
	'columns' => array(
		array( 'label' => 'Factor', 'note' => '' ),
		array( 'label' => 'iFly Nepal', 'note' => '' ),
		array( 'label' => 'Typical large Nepal trekking chain', 'note' => 'high-volume trek operators' ),
		array( 'label' => 'Typical Bali or Southeast Asia operator', 'note' => 'beach and temple itineraries' ),
	),
	'rows'    => array(
		array( 'Catalog breadth', 'Trek, culture, wildlife, festival and volunteer, one team', 'Mostly trekking, culture handled by a subcontracted partner', 'Beach, temple and wellness-tour focused, limited high-altitude trekking' ),
		array( 'Typical group size', 'Up to 10 on group departures', 'Often 15 to 25 in peak season', '10 to 20' ),
		array( 'Terrain and altitude', 'Sea level to 5,364m, acclimatisation built into the schedule', 'Similar terrain, pace varies by operator', 'Mostly under 1,500m, minimal altitude planning needed' ),
		array( 'Permit handling', 'TIMS and park permits arranged before arrival', 'Usually included, confirm in writing', 'Visa and park fees vary, less standardised' ),
		array( 'Cultural access', 'Festival dates tied to the real Nepali calendar, working monasteries', 'Similar where offered', 'Temple ceremonies commonly built into yoga or beach itineraries' ),
		array( 'Booking transparency', 'Fixed all-in price shown before you book', 'Varies, some quote trek-only', 'Varies widely by operator' ),
	),
);

$faq_items = array(
	array( 'q' => 'How much does a Nepal tour cost?', 'a' => 'Trips with iFly Nepal range from $250 for a 1-day cultural workshop to $3,000 for a month-long program. Most multi-day tours and treks fall between $500 and $2,000, with accommodation, meals, guide and permits included in the quoted price.' ),
	array( 'q' => 'Do I need trekking experience for Everest Base Camp or Annapurna?', 'a' => 'No prior trekking experience is required, but a reasonable level of fitness helps. Most guests train with regular walking or hiking for 6 to 8 weeks before a high-altitude trek, and the itinerary is paced with rest days either way.' ),
	array( 'q' => 'What permits do I need for trekking in Nepal?', 'a' => 'Most treks require a TIMS card and a national park or conservation area entry permit. Restricted areas such as Upper Mustang or Manaslu need an additional special permit. All of this is arranged for you before you arrive.' ),
	array( 'q' => 'What is the best time of year to visit Nepal?', 'a' => 'October to November and March to May give the clearest mountain views and the most stable trekking conditions. Cultural and wildlife tours based in the lower valleys work well through most of the year, including monsoon months.' ),
	array( 'q' => 'Is it safe to travel Nepal alone?', 'a' => 'Yes. Solo travellers, including solo women, make up a large share of our guests. Guided group departures and private guides both mean you’re never navigating logistics alone once you land.' ),
	array( 'q' => 'Can I combine trekking with a cultural or wildlife tour?', 'a' => 'Yes. This is one of the most common bookings we run, a trek followed by a few days in Pokhara and a Chitwan safari, arranged as a single itinerary with one guide handling the transitions.' ),
	array( 'q' => 'What is included in the tour price?', 'a' => 'Accommodation, listed meals, a guide, permits and airport or hotel transfers are included in every plan on this page. International flights, travel insurance and personal spending are not included unless stated on the specific trip.' ),
	array( 'q' => 'How is a Nepal tour different from a Southeast Asia tour?', 'a' => 'Nepal centres on high-altitude trekking, living Buddhist and Hindu tradition, and wildlife safari in the lowlands. A typical Southeast Asia tour, including Bali, centres on beach, temple and wellness travel at lower elevation with less structured trekking.' ),
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
	"Tour term #%d, slug '%s'%s\n",
	$term_id,
	$term->slug,
	'nepal-tour' === $term->slug ? '' : " -> 'nepal-tour'"
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

if ( 'nepal-tour' !== $term->slug ) {
	wp_update_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY, array( 'slug' => 'nepal-tour' ) );
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
