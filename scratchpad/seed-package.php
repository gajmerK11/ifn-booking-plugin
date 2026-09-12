<?php
/**
 * Fills the sample package with the design file's own copy, so the template can
 * be checked against the design with real content in it.
 *
 * Sample data on a dev site, not client content. --drop removes it again.
 */
define( 'DB_HOST', '127.0.0.1:10036' );
define( 'WP_USE_THEMES', false );
$_SERVER['HTTP_HOST'] = 'iflynepal.local';
require 'C:/Users/asus/Local Sites/iflynepal/app/public/wp-load.php';

$posts = get_posts( array( 'post_type' => 'iflynepal_package', 'numberposts' => 1, 'post_status' => 'any' ) );
$id    = $posts[0]->ID;

$fields = array(
	'heading'            => '2-Day Holistic Ayurvedic <em>Rejuvenation</em> Retreat',
	'glance_destination' => 'Chandragiri',
	'glance_duration'    => '2 days',
	'glance_activities'  => 'Retreat',
	'glance_meals'       => 'Included',
	'glance_stay'        => 'Wellness room',
	'glance_group'       => '2-25',
	'glance_best_time'   => 'All season',
	'overview_intro'     => 'Escape into a rejuvenating 1 Night, 2 Day wellness retreat nestled in the lush hills of Kathmandu Valley. Designed to restore balance to the body, mind, and spirit, this immersive experience combines authentic Ayurvedic therapies, guided yoga and pranayama, sound healing, forest bathing, digital detox, and wholesome sattvic cuisine.',
	'overview_body'      => "Reconnect with your inner self through this 2-Day Holistic Ayurvedic Rejuvenation Retreat, a carefully designed wellness experience that blends ancient Ayurvedic healing practices with yoga, meditation, nature immersion, and mindful relaxation.\n\nThrough nourishing meals, rejuvenating therapies, and guided wellness activities, guests can experience deep relaxation, renewed energy, and a greater sense of well-being in a peaceful and supportive environment.",
	'highlights'         => "Authentic Ayurvedic relaxation and rejuvenation therapies\nGuided Yoga, Pranayama, and Meditation sessions\nHimalayan Singing Bowl Sound Healing\nForest Bathing and Nature Immersion\nOptional Digital Detox Experience\nAyurvedic Satvic Meals and Herbal Wellness Drinks\nShirodhara and Abhyanga Treatments\nScenic Himalayan and Kathmandu Valley Views",
	'itinerary_heading'  => 'Two days, <em>gently</em> paced.',
	'dates_heading'      => 'Choose your start date.',
	'price_amount'       => '320',
	'price_currency'     => 'USD',
	'nights'             => '1',
	'included'           => "1 night accommodation in a comfortable wellness room\nWelcome herbal drink and healthy refreshments\nGuided yoga, meditation, and pranayama sessions\nHimalayan singing bowl sound healing experience\nAyurvedic vegetarian meals\nHerbal foot soak and relaxation rituals\nUse of retreat and wellness facilities\nExperienced wellness facilitators and instructors",
	'excluded'           => "Extra meals and snacks outside the program\nTips and gratuities\nTransportation to and from the retreat venue\nTravel insurance\nPersonal expenses and shopping\nAlcoholic beverages and soft drinks",
	'price_eyebrow'      => 'All inclusive price',
	'price_points'       => "Wellness room & Ayurvedic meals\nGuided yoga, meditation & pranayama\nAyurvedic therapy & sound healing\nForest bathing & nature immersion\nExperienced wellness facilitators",
	'inquire_link'       => 'https://wa.me/9841771010',
	'price_foot'         => 'No payment needed to enquire',
	'expert_name'        => 'Prem',
	'expert_place'       => 'Nepal',
	'expert_label'       => '+977-9841771010',
	'expert_link'        => 'https://wa.me/9779841771010',
	'packing_heading'    => 'What to <em>bring</em>.',
	'packing_items'      => "Comfortable yoga or activewear\nLight jacket or shawl for cool mornings and evenings\nWalking shoes or comfortable outdoor footwear\nPersonal toiletries and medications\nReusable water bottle\nSunscreen and sunglasses\nPersonal identification document",
	'map_heading'        => 'Where you will <em>stay</em>.',
	'map_embed'          => 'https://maps.google.com/maps?q=Chandragiri%20Hills%2C%20Kathmandu&z=12&output=embed',
	'map_place'          => 'Chandragiri, Kathmandu Valley',
	'map_note'           => 'Hill retreat on the valley\'s south-west rim',
	'map_link'           => 'https://www.google.com/maps/search/?api=1&query=Chandragiri+Hills+Kathmandu',
	'faq_heading'        => 'Questions, answered <em>simply</em>.',
	'faq_lead'           => 'Still unsure? Message Prem on WhatsApp and get an answer from someone who runs the retreat.',
);

$days = array(
	array(
		'title'    => 'Arrival, Relaxation & Mindful Unwinding',
		'meta'     => '4:00 PM - 8:00 PM - Welcome, wellness rituals, dinner, sound healing',
		'timeline' => "4:00 - 5:00 PM | Arrive at the retreat and receive a traditional welcome with refreshing herbal drinks and homemade organic snacks.\n5:00 - 6:00 PM | Join an introductory wellness session, a soothing herbal foot bath, gentle head relaxation therapy and an eye-cleansing ritual.\n6:00 - 7:00 PM | Savour a wholesome Ayurvedic vegetarian dinner prepared using fresh seasonal ingredients.\n7:00 - 8:00 PM | Guided candle meditation followed by a Himalayan singing bowl healing session.",
		'summary'  => 'Arrive at the retreat, enjoy a warm welcome, relax with Ayurvedic wellness rituals, savour a healthy dinner, and unwind through meditation and sound healing.',
		'stay'     => 'Wellness room',
		'meals'    => 'Welcome snacks, dinner',
	),
	array(
		'title'    => 'Yoga, Nature & Rejuvenation',
		'meta'     => '5:30 AM - 12:00 PM - Yoga, breakfast, forest walk, Ayurvedic therapy',
		'timeline' => "5:30 - 6:00 AM | Optional early-morning awakening to experience the tranquility of the day's most peaceful hours.\n6:00 - 7:00 AM | Guided yoga, breathing exercises and meditation amidst the surrounding hills.\n7:00 - 8:00 AM | A nourishing Ayurvedic breakfast designed to energise and balance the body.\n8:00 - 10:00 AM | Guided nature immersion with mindful walking and forest connection.\n10:00 - 11:30 AM | A rejuvenating Ayurvedic wellness therapy.\n11:30 AM - 12:00 PM | Closing reflection session, wellness guidance and departure.",
		'summary'  => 'Begin the day with yoga and meditation, enjoy a nourishing breakfast, connect with nature, experience Ayurvedic therapy, and depart feeling refreshed.',
		'stay'     => '',
		'meals'    => 'Breakfast',
	),
);

$faqs = array(
	array( 'q' => 'Is this retreat suitable for beginners?', 'a' => 'Yes, the retreat is designed for all experience levels, including beginners with no prior yoga or meditation experience.' ),
	array( 'q' => 'What type of accommodation is provided?', 'a' => 'Guests stay in comfortable wellness accommodations designed to promote relaxation and restful sleep.' ),
	array( 'q' => 'Are meals included in the retreat package?', 'a' => 'Yes, Ayurvedic vegetarian meals and herbal refreshments included in the itinerary are provided.' ),
	array( 'q' => 'Do I need to bring my own yoga mat?', 'a' => 'Yoga mats and basic wellness equipment are provided, but you may bring your own if you prefer.' ),
	array( 'q' => 'Can dietary requirements be accommodated?', 'a' => 'Yes, please tell the retreat team in advance about any dietary restrictions or allergies.' ),
);

if ( in_array( '--drop', $argv, true ) ) {
	foreach ( array_merge( array_keys( $fields ), array( 'itinerary_days', 'faq_items', 'gallery' ) ) as $key ) {
		delete_post_meta( $id, iflynepal_package_meta_key( $key ) );
	}

	echo "dropped\n";
	exit;
}

foreach ( $fields as $key => $value ) {
	update_post_meta( $id, iflynepal_package_meta_key( $key ), $value );
}

update_post_meta( $id, iflynepal_package_meta_key( 'itinerary_days' ), $days );
update_post_meta( $id, iflynepal_package_meta_key( 'faq_items' ), $faqs );

// A gallery from whatever is already in the media library.
$images = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 8, 'fields' => 'ids' ) );
update_post_meta( $id, iflynepal_package_meta_key( 'gallery' ), implode( ',', $images ) );

printf( "seeded %s\n%s\n", get_the_title( $id ), get_permalink( $id ) );
