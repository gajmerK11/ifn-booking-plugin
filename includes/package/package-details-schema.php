<?php
/**
 * The single-package content model.
 *
 * Everything the package page in retreats-nepal-package-single-design.html is
 * built from, declared once: the at-a-glance table, the overview, the itinerary,
 * the dates block, the booking aside, the packing list, the map and the FAQs.
 *
 * These fields are merged into iflynepal_package_detail_fields(), so there is
 * still one schema, one sanitizer and one save routine for everything stored
 * against a package — the Package Card box and the Package Details box are two
 * views of the same list, told apart by each field's `box`.
 *
 * Two schema keys matter here beyond the ones the card fields use:
 *
 *   box     — which meta box draws the field. A field whose box no screen draws
 *             is stored and rendered but not editable, which is a holding state,
 *             not an oversight.
 *   section — which panel of the Package Details box it belongs to. Sections are
 *             declared in iflynepal_package_detail_sections() and drawn in that
 *             order; a field naming no section would never be drawn, so the
 *             renderer walks the sections rather than the fields.
 *
 * Nothing in here is enforced, checked or converted. A price is text that reads
 * as a price, a date is text that reads as a date, and the calendar on the front
 * end picks any day the visitor likes: there is no availability, capacity or
 * inventory logic anywhere in this plugin, by the client's explicit and repeated
 * instruction.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The most itinerary days one package can hold.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_ITINERARY_MAX = 30;

/**
 * The most timeline stops one itinerary day can hold.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_TIMELINE_MAX = 20;

/**
 * The most FAQ entries one package can hold.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_FAQ_MAX = 15;

/**
 * The most gallery images one package can hold.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_GALLERY_MAX = 24;

/**
 * The panels of the Package Details box, in the order they are drawn.
 *
 * @since 1.0.0
 *
 * @return array[] Section definitions keyed by section key.
 */
function iflynepal_package_detail_sections() {
	$sections = array(
		'title'     => array(
			'label'       => __( 'Package title', 'iflynepal' ),
			'description' => __( 'The heading at the top of the package page. Left empty, the post title is used as it stands.', 'iflynepal' ),
		),
		'glance'    => array(
			'label'       => __( 'At a glance', 'iflynepal' ),
			'description' => __( 'The summary table under the title. A row with nothing in it is left out.', 'iflynepal' ),
		),
		'overview'  => array(
			'label'       => __( 'Overview', 'iflynepal' ),
			'description' => __( 'The opening paragraphs and the highlights list.', 'iflynepal' ),
		),
		'itinerary' => array(
			'label'       => __( 'Itinerary', 'iflynepal' ),
			'description' => __( 'One card per day. Each day carries both its full timeline and the one-line summary the Short itinerary tab shows.', 'iflynepal' ),
		),
		'dates'     => array(
			'label'       => __( 'Dates and price', 'iflynepal' ),
			'description' => __( 'What the calendar and the totals are built from. The calendar lets a visitor pick any date — nothing is held, checked or reserved.', 'iflynepal' ),
		),
		'booking'   => array(
			'label'       => __( 'Booking aside', 'iflynepal' ),
			'description' => __( 'The price card beside the itinerary, and the expert card under it.', 'iflynepal' ),
		),
		'packing'   => array(
			'label'       => __( 'Packing list', 'iflynepal' ),
			'description' => '',
		),
		'map'       => array(
			'label'       => __( 'Map', 'iflynepal' ),
			'description' => __( 'An embedded map of where the package is based.', 'iflynepal' ),
		),
		'faqs'      => array(
			'label'       => __( 'FAQs', 'iflynepal' ),
			'description' => __( 'Shown as an accordion; the first is open.', 'iflynepal' ),
		),
	);

	/**
	 * Filters the panels of the Package Details box.
	 *
	 * @since 1.0.0
	 *
	 * @param array[] $sections Section definitions keyed by section key.
	 */
	return apply_filters( 'iflynepal_package_detail_sections', $sections );
}

/**
 * The single-package fields, merged into the package schema.
 *
 * @since 1.0.0
 *
 * @return array[] Field definitions keyed by schema key.
 */
function iflynepal_package_details_fields() {
	$fields = array();

	$fields['heading'] = array(
		'box'     => 'details',
		'section' => 'title',
		'label'   => __( 'Package title', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> to set it in the accent style, as the design does. Left empty, the post title is used.', 'iflynepal' ),
	);

	/*
	 * At a glance. Separate fields rather than one repeater: the design's rows
	 * are a fixed set with fixed icons, and a repeater would invite a row the
	 * page has no icon for.
	 *
	 * The design's table is two full rows of four, and its grid lines are drawn
	 * by rules that assume both rows are full. A short row still renders
	 * correctly (the template completes it with filler cells), but eight facts
	 * is the shape the design draws, so eight is what a package should carry.
	 *
	 * There are ten to choose from, because the eighth the design ships —
	 * Check-in — is a fact about arriving at a building, and a retreat is more
	 * often asked whether a beginner can come at all. Experience level answers
	 * that, and a package fills whichever eight of the ten it has an answer
	 * for. Max altitude is the tenth, added for the trekking design's row —
	 * retreat and tour packages simply leave it blank.
	 */
	$glance = array(
		'glance_destination' => __( 'Destination', 'iflynepal' ),
		'glance_duration'    => __( 'Duration', 'iflynepal' ),
		'glance_activities'  => __( 'Activities', 'iflynepal' ),
		'glance_meals'       => __( 'Meals', 'iflynepal' ),
		'glance_stay'        => __( 'Accommodation', 'iflynepal' ),
		'glance_altitude'    => __( 'Max altitude', 'iflynepal' ),
		'glance_group'       => __( 'Group size', 'iflynepal' ),
		'glance_level'       => __( 'Experience level', 'iflynepal' ),
		'glance_best_time'   => __( 'Best time', 'iflynepal' ),
		'glance_checkin'     => __( 'Check-in', 'iflynepal' ),
	);

	foreach ( $glance as $glance_key => $glance_label ) {
		$fields[ $glance_key ] = array(
			'box'     => 'details',
			'section' => 'glance',
			'label'   => $glance_label,
			'type'    => 'text',
			'help'    => '',
		);
	}

	$fields['overview_intro'] = array(
		'box'     => 'details',
		'section' => 'overview',
		'label'   => __( 'Opening paragraph', 'iflynepal' ),
		'type'    => 'textarea',
		'help'    => __( 'The larger paragraph directly under the at-a-glance table.', 'iflynepal' ),
	);

	$fields['overview_body'] = array(
		'box'     => 'details',
		'section' => 'overview',
		'label'   => __( 'Body', 'iflynepal' ),
		'type'    => 'textarea',
		'help'    => __( 'Leave a blank line between paragraphs. Each block becomes its own paragraph.', 'iflynepal' ),
	);

	/*
	 * Highlights is declared here, after the two paragraph fields, rather than
	 * with the card facts it was first written beside: the Package Details box
	 * draws a section in schema order, and an editor writes the prose before the
	 * ticked list under it. The stored meta key is unchanged, and the front end
	 * reads every overview field by name, so the page is untouched by the move.
	 */
	$fields['highlights'] = array(
		'box'     => 'details',
		'section' => 'overview',
		'label'   => __( 'Highlights', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line, shown as the ticked list under the overview.', 'iflynepal' ),
	);

	$fields['itinerary_heading'] = array(
		'box'     => 'details',
		'section' => 'itinerary',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "Two days, <em>gently</em> paced."', 'iflynepal' ),
	);

	/*
	 * The altitude profile is one paragraph plus whatever Elevation values the
	 * days below carry — there is no separate on/off switch, the same rule as
	 * every other opt-in section on this page. Fewer than two days with a
	 * number in Elevation and the whole chart, note included, is left off:
	 * one point has no line to draw.
	 */
	$fields['altitude_note'] = array(
		'box'     => 'details',
		'section' => 'itinerary',
		'label'   => __( 'Altitude profile note', 'iflynepal' ),
		'type'    => 'textarea',
		'help'    => __( 'The line above the chart, e.g. "Base camp at 5,364m on Day 9, with rest days at Namche and Dingboche on the way up." Needs at least two days below with an Elevation value, or the chart — and this note — is left off.', 'iflynepal' ),
	);

	$fields['itinerary_days'] = array(
		'box'     => 'details',
		'section' => 'itinerary',
		/* translators: %d: the most days allowed. */
		'label'   => sprintf( __( 'Days (max %d)', 'iflynepal' ), IFLYNEPAL_PACKAGE_ITINERARY_MAX ),
		'type'    => 'cards',
		'help'    => __( 'One card per day, numbered in the order listed. Remove one and the rest renumber themselves.', 'iflynepal' ),
		'item'    => __( 'Day', 'iflynepal' ),
		'max'     => IFLYNEPAL_PACKAGE_ITINERARY_MAX,
		'parts'   => array(
			'title'     => array(
				'label' => __( 'Day title', 'iflynepal' ),
				'type'  => 'text',
			),
			'elevation' => array(
				'label' => __( 'Elevation (m), for the altitude chart', 'iflynepal' ),
				'type'  => 'text',
			),
			'meta'      => array(
				'label' => __( 'Times and summary line', 'iflynepal' ),
				'type'  => 'text',
			),
			'timeline'  => array(
				'label' => __( 'Timeline', 'iflynepal' ),
				'type'  => 'timeline',
				'item'  => __( 'Stop', 'iflynepal' ),
				'max'   => IFLYNEPAL_PACKAGE_TIMELINE_MAX,
				'help'  => __( 'One row per stop, in the order they happen. Drag a row by its handle, or use the arrows, to move it.', 'iflynepal' ),
			),
			'summary'   => array(
				'label' => __( 'Short itinerary entry', 'iflynepal' ),
				'type'  => 'textarea',
			),

			/*
			 * The accommodation and meals notes a day used to carry were removed
			 * on the client's instruction, and the foot of the day card went with
			 * them: a repeater part has no `box` key to park it behind the way a
			 * top-level field does, so a part nothing draws is a part nothing can
			 * ever fill in. Stored values survive in the database until a day is
			 * next saved, at which point the sanitizer drops what the schema no
			 * longer declares. Putting either back is one entry here plus its
			 * block in templates/parts/package/itinerary.php.
			 */
		),
	);

	$fields['dates_heading'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word or two in <span class="iflynepal-ink-mark"> for the hand-drawn underline.', 'iflynepal' ),
	);

	$fields['dates_lead'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Lead paragraph', 'iflynepal' ),
		'type'    => 'textarea',
		'help'    => __( 'The sentence under the heading, above the calendar. Left empty, the calendar follows the heading directly.', 'iflynepal' ),
	);

	/*
	 * The price is two fields, not one formatted string, because the calendar
	 * multiplies it by the traveller count in the browser. It is the one number
	 * on this page that is arithmetic rather than copy.
	 */
	$fields['price_amount'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Price per person', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Digits only, e.g. 320 or 1250.50. This is the number the total is worked out from.', 'iflynepal' ),
	);

	$fields['price_currency'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Currency', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Shown in front of the price, e.g. USD.', 'iflynepal' ),
	);

	/*
	 * The trip's length in DAYS, and it is the calendar's whole contract: a
	 * visitor picks a start and the run of that many days is what they get. A
	 * five-day trip is typed as 5 and highlights five cells, which is how the
	 * length is written everywhere else on the page and everywhere the client
	 * sells it.
	 *
	 * It replaced a `nights` field, and the stored values were migrated rather
	 * than dropped (nights + 1). Two numbers meaning almost the same thing is an
	 * invitation to set 3 nights against 7 days and publish both.
	 */
	$fields['duration_days'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Trip duration (days)', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Whole days, e.g. 5 for a five-day trip. The calendar highlights this many days from whichever start date a visitor picks, and works the end date out from it. Left empty, the calendar picks a single day.', 'iflynepal' ),
	);

	$fields['included'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Included in the price', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line.', 'iflynepal' ),
	);

	$fields['excluded'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Not included', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line.', 'iflynepal' ),
	);

	$fields['price_eyebrow'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Price card eyebrow', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'The small line above the price, e.g. "All inclusive price".', 'iflynepal' ),
	);

	$fields['price_points'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Price card list', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line. A short version of what is included — the full list lives in the Dates and price panel.', 'iflynepal' ),
	);

	$fields['inquire_link'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Inquire link', 'iflynepal' ),
		'type'    => 'url',
		'help'    => __( 'Where the second button goes — a WhatsApp click-to-chat link, or a page. Left empty, the button is left off.', 'iflynepal' ),
	);

	$fields['whatsapp_number'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'WhatsApp number for this package', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Only if this package is answered on a different phone. Country code first, digits only. Left empty — which is the normal case — the site-wide number at Packages > Settings is used.', 'iflynepal' ),
	);

	$fields['price_foot'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Price card footnote', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'The reassurance under the buttons, e.g. "No payment needed to enquire".', 'iflynepal' ),
	);

	$fields['expert_image'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert photo', 'iflynepal' ),
		'type'    => 'image',
		'help'    => '',
	);

	$fields['expert_name'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert name', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'The whole expert card is left off unless this is filled in.', 'iflynepal' ),
	);

	$fields['expert_place'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert location', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Shown after the name, e.g. "Nepal".', 'iflynepal' ),
	);

	$fields['expert_label'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert button label', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'Usually the phone number.', 'iflynepal' ),
	);

	$fields['expert_link'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert button link', 'iflynepal' ),
		'type'    => 'url',
		'help'    => '',
	);

	$fields['packing_heading'] = array(
		'box'     => 'details',
		'section' => 'packing',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "What to <em>bring</em>."', 'iflynepal' ),
	);

	$fields['packing_items'] = array(
		'box'     => 'details',
		'section' => 'packing',
		'label'   => __( 'Items', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line.', 'iflynepal' ),
	);

	$fields['map_heading'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "Where you\'ll <em>stay</em>."', 'iflynepal' ),
	);

	$fields['map_embed'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Map embed URL', 'iflynepal' ),
		'type'    => 'url',
		'help'    => __( 'The src of a Google Maps embed, e.g. https://maps.google.com/maps?q=Chandragiri&z=12&output=embed. The map is left off until this is filled in.', 'iflynepal' ),
	);

	$fields['map_place'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Place name', 'iflynepal' ),
		'type'    => 'text',
		'help'    => '',
	);

	$fields['map_note'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Place note', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'The line under the place name.', 'iflynepal' ),
	);

	$fields['map_link'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Open in Maps link', 'iflynepal' ),
		'type'    => 'url',
		'help'    => '',
	);

	$fields['faq_heading'] = array(
		'box'     => 'details',
		'section' => 'faqs',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style.', 'iflynepal' ),
	);

	$fields['faq_lead'] = array(
		'box'     => 'details',
		'section' => 'faqs',
		'label'   => __( 'Lead paragraph', 'iflynepal' ),
		'type'    => 'textarea',
		'help'    => '',
	);

	$fields['faq_items'] = array(
		'box'     => 'details',
		'section' => 'faqs',
		/* translators: %d: the most questions allowed. */
		'label'   => sprintf( __( 'Questions (max %d)', 'iflynepal' ), IFLYNEPAL_PACKAGE_FAQ_MAX ),
		'type'    => 'cards',
		'help'    => __( 'The first question is open when the page loads.', 'iflynepal' ),
		'item'    => __( 'Question', 'iflynepal' ),
		'max'     => IFLYNEPAL_PACKAGE_FAQ_MAX,
		'parts'   => array(
			'q' => array(
				'label' => __( 'Question', 'iflynepal' ),
				'type'  => 'text',
			),
			'a' => array(
				'label' => __( 'Answer', 'iflynepal' ),
				'type'  => 'textarea',
			),
		),
	);

	/*
	 * The featured video sits in its own sidebar box directly under Featured
	 * image, because it is the same decision made twice: what the top of the
	 * package page opens on. A video is played there when one is chosen and the
	 * featured image is its poster; with no video, the image is what shows. The
	 * image is never optional — it is what the archive cards, the search results
	 * and every share card use, none of which can play a video.
	 */
	$fields['featured_video'] = array(
		'box'     => 'video',
		'section' => '',
		'label'   => __( 'Featured video', 'iflynepal' ),
		'type'    => 'video',
		'help'    => __( 'Played in place of the featured image at the top of the package page. The featured image is used as its poster frame and as the fallback when no video is chosen.', 'iflynepal' ),
	);

	/*
	 * The gallery is its own box, sitting under Featured image in the sidebar —
	 * it is a picture-picking job, and it belongs where the other picture-picking
	 * job already is.
	 */
	$fields['gallery'] = array(
		'box'     => 'gallery',
		'section' => '',
		/* translators: %d: the most images allowed. */
		'label'   => sprintf( __( 'Gallery (max %d)', 'iflynepal' ), IFLYNEPAL_PACKAGE_GALLERY_MAX ),
		'type'    => 'gallery',
		'help'    => __( 'The photo grid at the top of the package page. The featured image is used as the first, largest photo and does not need adding here.', 'iflynepal' ),
		'max'     => IFLYNEPAL_PACKAGE_GALLERY_MAX,
	);

	return $fields;
}

/**
 * The fields of one panel of the Package Details box.
 *
 * @since 1.0.0
 *
 * @param string $section Section key.
 * @return array[] Field definitions keyed by schema key, in schema order.
 */
function iflynepal_package_fields_for_section( $section ) {
	$fields = array();

	foreach ( iflynepal_package_fields_for_box( 'details' ) as $key => $field ) {
		if ( isset( $field['section'] ) && $section === $field['section'] ) {
			$fields[ $key ] = $field;
		}
	}

	return $fields;
}
