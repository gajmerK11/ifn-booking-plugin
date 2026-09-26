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
 * The most itinerary weeks one package can hold.
 *
 * For a week-paced package (volunteering, homestays and the like) instead of
 * a day-paced one — see itinerary_weeks below.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_ITINERARY_WEEKS_MAX = 12;

/**
 * The most timeline stops one itinerary day can hold.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_TIMELINE_MAX = 20;

/**
 * The most group-size price tiers one package can hold.
 *
 * A tier is a discount for travelling in a group — "1–12 pax, was 540, now
 * 502 each". Six is well past what any package the client sells uses, and the
 * cap exists for the same reason every other repeater has one: a repeater with
 * no ceiling is a page nobody has laid out.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_PRICE_TIERS_MAX = 6;

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

	/*
	 * Accommodation is a fixed either/or rather than free text: the design's
	 * fact here answers "is accommodation part of the price", not what kind of
	 * bed it is (that belongs in the Included/Not included lists below). A
	 * dropdown makes the two possible answers the only two an editor can type.
	 */
	$fields['glance_stay']['type']    = 'select';
	$fields['glance_stay']['options'] = array(
		'Included'     => __( 'Included', 'iflynepal' ),
		'Not included' => __( 'Not included', 'iflynepal' ),
	);
	$fields['glance_stay']['help']    = __( 'Whether accommodation is included in the package price.', 'iflynepal' );

	/*
	 * Experience level is likewise one of a fixed set, not free text —
	 * dropdown keeps editors from typing near-duplicate labels (e.g.
	 * "Moderate" vs "Medium") that would render as separate untranslated
	 * strings on the front end.
	 */
	$fields['glance_level']['type']    = 'select';
	$fields['glance_level']['options'] = array(
		'Relaxed'  => __( 'Relaxed', 'iflynepal' ),
		'Easy'     => __( 'Easy', 'iflynepal' ),
		'Moderate' => __( 'Moderate', 'iflynepal' ),
		'Hard'     => __( 'Hard', 'iflynepal' ),
	);
	$fields['glance_level']['help']    = __( 'How physically demanding the package is.', 'iflynepal' );

	/*
	 * Check-in is a clock time, not a sentence — a native time picker keeps
	 * editors from typing "2 PM" one week and "14:00" the next.
	 */
	$fields['glance_checkin']['type'] = 'time';
	$fields['glance_checkin']['help'] = __( 'The time guests can check in.', 'iflynepal' );

	/*
	 * Meals can be more than one of the same three — a package feeding
	 * breakfast and lunch is not the same fact as one feeding just breakfast —
	 * so it is checkboxes, not a single-choice dropdown. Stored and displayed
	 * as one comma-separated string, the same shape free text already used.
	 */
	$fields['glance_meals']['type']    = 'checkbox_group';
	$fields['glance_meals']['options'] = array(
		'Breakfast' => __( 'Breakfast', 'iflynepal' ),
		'Lunch'     => __( 'Lunch', 'iflynepal' ),
		'Dinner'    => __( 'Dinner', 'iflynepal' ),
	);
	$fields['glance_meals']['help']    = __( 'Which meals are included.', 'iflynepal' );

	$fields['overview_intro'] = array(
		'box'     => 'details',
		'section' => 'overview',
		'label'   => __( 'Opening paragraph', 'iflynepal' ),
		'type'    => 'wysiwyg',
		'help'    => __( 'The larger paragraph directly under the at-a-glance table.', 'iflynepal' ),
	);

	$fields['overview_body'] = array(
		'box'     => 'details',
		'section' => 'overview',
		'label'   => __( 'Body', 'iflynepal' ),
		'type'    => 'wysiwyg',
		'help'    => __( 'Press Enter between paragraphs. Each one becomes its own paragraph.', 'iflynepal' ),
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
		'type'    => 'wysiwyg',
		'help'    => __( 'One per line (press Enter, or use the bulleted-list button), shown as the ticked list under the overview.', 'iflynepal' ),
	);

	$fields['itinerary_heading'] = array(
		'box'     => 'details',
		'section' => 'itinerary',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "Two days, <em>gently</em> paced."', 'iflynepal' ),
		'default' => array(
			'en' => 'What will you do',
			'fr' => 'Que ferez-vous',
		),
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

			/*
			 * What the badge reads, when counting is not the whole truth. A card
			 * standing for two days of the same thing is "3-4", not "3", and a
			 * package that opens with an arrival evening may want "0". Left
			 * empty — which is every card until someone says otherwise — the
			 * badge counts the cards, so numbering still looks after itself.
			 */
			'number'      => array(
				'label' => __( 'Day label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => __( 'Leave empty to number this card by its position. Fill it in for a card that covers more than one day, e.g. 3-4.', 'iflynepal' ),
				// Typed into the card's own header rather than drawn as a field.
				'badge' => true,
			),
			'title'       => array(
				'label'  => __( 'Day title', 'iflynepal' ),
				'type'   => 'text',
				// What a shut card in the editor is named by.
				'header' => true,
			),

			/*
			 * Elevation — the altitude chart's only data source — was removed
			 * on the client's instruction, same rule as the accommodation and
			 * meals parts below: iflynepal_package_altitude_profile() reads
			 * $day['elevation'], finds it on no day ever again, and the chart
			 * (and the note above it, already removed) stays permanently
			 * opted out rather than being torn out itself. Stored values
			 * survive until a day is next saved, at which point the sanitizer
			 * drops what the schema no longer declares.
			 */
			'meta'        => array(
				'label' => __( 'Times and summary line', 'iflynepal' ),
				'type'  => 'text',
			),
			'timeline'    => array(
				'label' => __( 'Timeline', 'iflynepal' ),
				'type'  => 'timeline',
				'item'  => __( 'Stop', 'iflynepal' ),
				'max'   => IFLYNEPAL_PACKAGE_TIMELINE_MAX,
				'help'  => __( 'One row per stop, in the order they happen. Drag a row by its handle, or use the arrows, to move it.', 'iflynepal' ),
			),

			/*
			 * The day told in prose, for a day whose shape is a story rather than
			 * a clock: it is offered by a button beside Add Stop rather than as a
			 * box that is always open, because most days are one or the other and
			 * an empty textarea under every timeline reads as work left undone.
			 * Both render, in that order, for the day that wants both.
			 */
			'description' => array(
				'label'  => __( 'Descriptive itinerary', 'iflynepal' ),
				'type'   => 'prose',
				'button' => __( 'Add Descriptive Itinerary', 'iflynepal' ),
				'hide'   => __( 'Hide descriptive itinerary', 'iflynepal' ),
				'help'   => __( 'Shown under the timeline on the Detailed Itinerary tab, laid out exactly as it is written here — press Enter for a new paragraph, and use the toolbar for bold, underline and bulleted lists.', 'iflynepal' ),
			),
			'summary'     => array(
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

	/*
	 * A week-paced package uses this instead of Days above, never both: the
	 * template shows whichever one has cards and leaves the other's section
	 * off. There is no altitude chart or hour-by-hour timeline at this
	 * cadence — a week's shape is its title and a couple of lines, not a
	 * schedule of stops — which is the whole difference from a day card.
	 */
	$fields['itinerary_weeks'] = array(
		'box'     => 'details',
		'section' => 'itinerary',
		/* translators: %d: the most weeks allowed. */
		'label'   => sprintf( __( 'Weeks (max %d) — for week-paced packages instead of Days above', 'iflynepal' ), IFLYNEPAL_PACKAGE_ITINERARY_WEEKS_MAX ),
		'type'    => 'cards',
		'help'    => __( 'One card per week, numbered in the order listed. Use this instead of Days above for a package paced in weeks rather than days — fill in one or the other, not both.', 'iflynepal' ),
		'item'    => __( 'Week', 'iflynepal' ),
		'max'     => IFLYNEPAL_PACKAGE_ITINERARY_WEEKS_MAX,
		'parts'   => array(
			'number'  => array(
				'label' => __( 'Week label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => __( 'Leave empty to number this card by its position. Fill it in for a card that covers more than one week, e.g. 3-4.', 'iflynepal' ),
				'badge' => true,
			),
			'title'   => array(
				'label'  => __( 'Week title', 'iflynepal' ),
				'type'   => 'text',
				'header' => true,
			),
			'meta'    => array(
				'label' => __( 'Summary line', 'iflynepal' ),
				'type'  => 'text',
			),
			'summary' => array(
				'label' => __( 'Short itinerary entry', 'iflynepal' ),
				'type'  => 'textarea',
			),
		),
	);

	$fields['dates_heading'] = array(
		'box'     => 'details',
		'section' => 'dates',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word or two in <span class="iflynepal-ink-mark"> for the hand-drawn underline.', 'iflynepal' ),
		'default' => array(
			'en' => 'Plan Your Visit',
			'fr' => 'Planifiez votre visite',
		),
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
		'default' => 'USD',
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
		'default' => array(
			'en' => 'All Inclusive Price',
			'fr' => 'Prix tout compris',
		),
	);

	/*
	 * Group-size pricing: "1–12 pax, from 540, now 502 each". A repeater rather
	 * than a pair of fields because the discount is a ladder — the price per
	 * head falls as the group grows, and how many rungs that ladder has is the
	 * package's business, not the schema's.
	 *
	 * It sits beside price_amount rather than replacing it. price_amount is
	 * still the package's price with nothing else said, and a package with no
	 * tiers at all is exactly the page it was before this field existed. Where
	 * tiers are filled in they take over both the price card and the
	 * calculator's arithmetic, so the two can never quote different numbers at
	 * the same traveller count.
	 *
	 * Every part is plain text and nothing is enforced, per the note at the top
	 * of this file: what is typed is read as a number where a number is needed
	 * (iflynepal_package_price_tiers()) and shown as typed everywhere else.
	 */
	$fields['price_tiers'] = array(
		'box'     => 'details',
		'section' => 'booking',
		/* translators: %d: the most tiers allowed. */
		'label'   => sprintf( __( 'Group-size prices (Pax prices, max %d)', 'iflynepal' ), IFLYNEPAL_PACKAGE_PRICE_TIERS_MAX ),
		'type'    => 'cards',
		'help'    => __( 'One row per group size, smallest group first. The price card shows the row matching how many travellers are picked, and the total is worked out from it. Leave this empty to price the package at one rate for everybody.', 'iflynepal' ),
		'item'    => __( 'Tier', 'iflynepal' ),
		'max'     => IFLYNEPAL_PACKAGE_PRICE_TIERS_MAX,
		'parts'   => array(
			'pax_from' => array(
				'label' => __( 'From how many travellers', 'iflynepal' ),
				'type'  => 'text',
			),
			'pax_to'   => array(
				'label' => __( 'Up to how many travellers (leave empty for no upper limit)', 'iflynepal' ),
				'type'  => 'text',
			),
			'was'      => array(
				'label' => __( 'Price before the discount (optional, shown struck through)', 'iflynepal' ),
				'type'  => 'text',
			),
			'price'    => array(
				'label' => __( 'Price per person at this group size', 'iflynepal' ),
				'type'  => 'text',
			),
		),
	);

	$fields['price_points'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Price card list', 'iflynepal' ),
		'type'    => 'lines',
		'help'    => __( 'One per line. A short version of what is included — the full list lives in the Dates and price panel.', 'iflynepal' ),
	);

	$fields['whatsapp_number'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'WhatsApp number for this package', 'iflynepal' ),
		'type'    => 'text',
		'default' => '9841771010',
		'help'    => __( 'Country code first, digits only. Pre-filled with the office number; change it only if this package is answered on a different phone.', 'iflynepal' ),
	);

	$fields['inquire_link'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Inquire link', 'iflynepal' ),
		'type'    => 'url',
		'help'    => __( 'Where the second button goes. Filled in automatically as a WhatsApp click-to-chat link for the number above — edit it directly to point somewhere else instead, e.g. a page.', 'iflynepal' ),
	);

	$fields['price_foot'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Price card footnote', 'iflynepal' ),
		'type'    => 'text',
		'help'    => __( 'The reassurance under the buttons, e.g. "No payment needed to enquire".', 'iflynepal' ),
		'default' => array(
			'en' => 'No payment needed to enquire',
			'fr' => 'Aucun paiement requis pour se renseigner',
		),
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
		'default' => 'Nepal',
		'help'    => __( 'Shown after the name, e.g. "Nepal".', 'iflynepal' ),
	);

	$fields['expert_label'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert button label', 'iflynepal' ),
		'type'    => 'text',
		'default' => '9841771010',
		'help'    => __( 'Usually the phone number. Pre-filled with the office number, same as the WhatsApp number above; change it only if this package is answered by someone else.', 'iflynepal' ),
	);

	$fields['expert_link'] = array(
		'box'     => 'details',
		'section' => 'booking',
		'label'   => __( 'Expert button link', 'iflynepal' ),
		'type'    => 'url',
		'help'    => __( 'Filled in automatically as a WhatsApp click-to-chat link for the label above — edit it directly to point somewhere else instead.', 'iflynepal' ),
	);

	$fields['packing_heading'] = array(
		'box'     => 'details',
		'section' => 'packing',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "What to <em>bring</em>."', 'iflynepal' ),
		'default' => array(
			'en' => 'What to <em>bring</em>',
			'fr' => 'Quoi <em>apporter</em>',
		),
	);

	$fields['packing_items'] = array(
		'box'     => 'details',
		'section' => 'packing',
		'label'   => __( 'Items', 'iflynepal' ),
		'type'    => 'wysiwyg',
		'help'    => __( 'One per line (press Enter, or use the bulleted-list button). Bold and underline carry through to the list on the page.', 'iflynepal' ),
	);

	$fields['map_heading'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Heading', 'iflynepal' ),
		'type'    => 'rich',
		'help'    => __( 'Wrap a word in <em> for the accent style, e.g. "Where you\'ll <em>stay</em>."', 'iflynepal' ),
		'default' => array(
			'en' => 'Where you\'ll <em>stay</em>',
			'fr' => 'Où vous <em>séjournerez</em>',
		),
	);

	$fields['map_image'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Map image', 'iflynepal' ),
		'type'    => 'image',
		'help'    => __( 'A drawn route or illustrated map, e.g. an itinerary map graphic. Shown instead of the embed below when both are filled in.', 'iflynepal' ),
	);

	$fields['map_embed'] = array(
		'box'     => 'details',
		'section' => 'map',
		'label'   => __( 'Map embed URL', 'iflynepal' ),
		'type'    => 'url',
		'help'    => __( 'The src of a Google Maps embed, e.g. https://maps.google.com/maps?q=Chandragiri&z=12&output=embed. Ignored while a map image above is set. The map is left off until one of the two is filled in.', 'iflynepal' ),
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
		'default' => array(
			'en' => 'Questions, answered simply.',
			'fr' => 'Des questions, des réponses simples.',
		),
	);

	$fields['faq_lead'] = array(
		'box'     => 'details',
		'section' => 'faqs',
		'label'   => __( 'Lead paragraph', 'iflynepal' ),
		'type'    => 'rich_textarea',
		'help'    => __( '<b>, <strong> and <br> are allowed.', 'iflynepal' ),
		'default' => array(
			'en' => 'Still unsure? Message <em>Prem</em> on WhatsApp and get an answer from someone who runs the retreat.',
			'fr' => 'Encore des doutes ? Écrivez à <em>Prem</em> sur WhatsApp et obtenez une réponse de la personne qui dirige la retraite.',
		),
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
