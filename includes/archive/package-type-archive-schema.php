<?php
/**
 * The content model for a package type archive page.
 *
 * /retreat-nepal/ is not a page anyone can open in the editor — it is a term
 * archive, generated from a Package Type term. So the marketing copy that wraps
 * the retreat cards on that page is stored as **term meta** on the term itself,
 * and edited on the Edit Package Type screen.
 *
 * Why term meta and not something else:
 *
 *  - A Page. The archive would then exist twice, at /retreat-nepal/ and at the
 *    page's own URL, which is a duplicate-content problem and a redirect to
 *    maintain. It also needs somebody to remember to create a page every time a
 *    new package type is added.
 *  - The Customizer. That is where the theme keeps homepage copy, and it works
 *    there because there is exactly one homepage. There are as many archives as
 *    there are types, and the Customizer has no per-term anything.
 *  - An options page. Same objection, plus the content would be orphaned from
 *    the term it describes and would not be deleted with it.
 *
 * Term meta is the mechanism WordPress provides for exactly this: content that
 * belongs to a term. Core uses it for the term description, and every SEO
 * plugin uses it for per-archive titles. It scales to a new package type with
 * no code, and it is deleted with the term.
 *
 * This file is the schema only — the single description of every field, its
 * label, its help text and its type. The admin screen renders from it, the save
 * routine sanitizes from it, and the templates read from it, so none of the
 * three can drift out of step with the other two.
 *
 * Most repeating parts of the design — the three plans, the comparison rows, the
 * FAQ — are a fixed number of numbered slots rather than a repeater widget,
 * which is the same choice the theme made for the homepage lists. An empty slot
 * is skipped at render time; nothing stores how many slots are in use, because a
 * stored count is the thing that goes stale.
 *
 * The reason cards are the exception: they are a `cards` repeater, added and
 * removed with a button up to a cap, because that section has no fixed length in
 * the design and numbering nine slots to leave six of them blank is a worse
 * screen to work on. The stored value is a list, so there is still no count to
 * go stale — the list *is* the count.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prefix every archive term meta key carries.
 *
 * Leading underscore so the keys stay out of the generic custom-fields UI.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_META_PREFIX = '_iflynepal_archive_';

/**
 * Most reason cards an editor may add.
 *
 * A cap rather than a slot count: the cards are a repeater, added and removed
 * with a button, so there is no such thing as an empty slot to skip. Nine is
 * three full rows of the three-column grid.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_BENEFIT_CARDS = 9;

/**
 * Number of booking plans the design lays out.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_PLAN_SLOTS = 3;

/**
 * Most rows the comparison table may hold.
 *
 * A cap rather than a slot count: the rows are added and removed with a button,
 * so there is no such thing as an empty row to skip. Six is what the design
 * lays out; raising it is this one number.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_COMPARE_MAX_ROWS = 6;

/**
 * Columns the comparison table offers.
 *
 * Fixed, unlike the rows: the design is a row label plus up to three things
 * being compared, and the front end drops any column left without a heading. So
 * a two- or three-column comparison is made by leaving headings blank rather
 * than by a second control.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS = 4;

/**
 * Most questions the FAQ may hold.
 *
 * A cap rather than a slot count: the questions are a repeater, added and
 * removed with a button. The design lays out six; the cap is ten because an FAQ
 * grows with the questions people actually ask, and a cap is a ceiling rather
 * than a target. Changing it is this one number.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_FAQ_MAX = 10;

/**
 * The most departure cards one archive can hold.
 *
 * Enforced in the browser and again on save: the form is not the only thing
 * that can post to the term screen.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_DEPARTURE_CARDS = 10;

/**
 * The full meta key for a schema field.
 *
 * @since 1.0.0
 *
 * @param string $key Schema key, e.g. 'hero_title'.
 * @return string Meta key.
 */
function iflynepal_archive_meta_key( $key ) {
	return IFLYNEPAL_ARCHIVE_META_PREFIX . $key;
}

/**
 * The archive content model, grouped into the sections of the design.
 *
 * Field types decide both the control drawn and the sanitizer applied, so the
 * two can never disagree:
 *
 *   text      one line, stored as plain text
 *   rich      one line that may carry <em>, <strong>, <span class> or <br>
 *   textarea  several lines that may carry <b>, <span class> or <br>
 *   lines     a textarea read as one item per line, for short lists — plain text
 *   url       a link target
 *   image     an attachment ID chosen from the media library
 *   cards     a repeater: a list of { title, text, image } added with a button
 *   table     a grid: editable column headings plus rows added with a button
 *
 * A field may also carry an optional 'column' (1 or 2), which pins it to that
 * column of the edit screen's two-column panel instead of letting it flow. Use
 * it only where two fields are one thing split in half — a button's label and
 * its link — so they stay on a row together. It is layout on a wide screen and
 * nothing at all on a narrow one, where the panel is a single column.
 *
 * A field may also carry a 'group', naming one of the section's 'groups' — a
 * map of group key to card title. The edit screen draws each group as its own
 * titled card. This is for a fixed run of numbered slots, where the alternative
 * is a flat column of fields distinguishable only by the number in every label.
 * The stored data is unaffected: the fields are flat, individually keyed term
 * meta either way, and the save routine never reads 'group'.
 *
 * @since 1.0.0
 *
 * @return array[] Sections, each with 'label', 'description' and 'fields'.
 */
function iflynepal_package_type_archive_schema() {
	$textarea_help = __( 'May carry &lt;br&gt; for a line break, &lt;b&gt; for bold, or &lt;span class="..."&gt; for a styled word.', 'iflynepal' );

	$head = static function ( $prefix, $heading_help = '' ) use ( $textarea_help ) {
		return array(
			$prefix . '_eyebrow' => array(
				'label' => __( 'Eyebrow', 'iflynepal' ),
				'type'  => 'text',
				'help'  => __( 'The small line set above the heading.', 'iflynepal' ),
			),
			$prefix . '_heading' => array(
				'label' => __( 'Heading', 'iflynepal' ),
				'type'  => 'rich',
				'help'  => $heading_help,
			),
			$prefix . '_lead'    => array(
				'label' => __( 'Lead paragraph', 'iflynepal' ),
				'type'  => 'textarea',
				'help'  => $textarea_help,
			),
		);
	};

	$emphasis_help = __( 'Wrap a word or two in &lt;em&gt; to set it in the accent style, as the design does.', 'iflynepal' );

	/*
	 * The grid heading carries the other emphasis in the designs: the hand-drawn
	 * underline that sweeps itself in under the last few words. It is a span with
	 * a class rather than its own tag because the stroke is drawn by CSS on the
	 * span, so there is nothing for an editor to write but the words.
	 */
	$underline_help = __( 'Wrap a word or two in &lt;span class="iflynepal-ink-mark"&gt; to give it the hand-drawn underline, or in &lt;em&gt; for the accent style.', 'iflynepal' );

	$sections = array();

	$sections['hero'] = array(
		'label'       => __( 'Hero', 'iflynepal' ),
		'description' => __( 'The full-width opening band.', 'iflynepal' ),
		'fields'      => array(
			'hero_heading'       => array(
				'label' => __( 'Heading', 'iflynepal' ),
				'type'  => 'rich',
				'help'  => $emphasis_help,
			),
			'hero_lead'          => array(
				'label' => __( 'Lead paragraph', 'iflynepal' ),
				'type'  => 'textarea',
				'help'  => '',
			),
			'hero_image'         => array(
				'label' => __( 'Background image', 'iflynepal' ),
				'type'  => 'image',
				'help'  => __( 'Landscape, and the largest thing on the page — keep it under 150KB, it is the image the page is scored on.', 'iflynepal' ),
			),

			/*
			 * A button is a label and a link, and reading one without the other
			 * tells an editor nothing. The pair is pinned to a row of its own so
			 * the primary button is one line of the form and the secondary is the
			 * next, instead of the four of them flowing wherever the auto-placed
			 * grid happens to leave a gap after the image.
			 */
			'hero_cta_label'     => array(
				'label'  => __( 'Primary button label', 'iflynepal' ),
				'type'   => 'text',
				'help'   => '',
				'column' => 1,
			),
			'hero_cta_url'       => array(
				'label'  => __( 'Primary button link', 'iflynepal' ),
				'type'   => 'url',
				'help'   => '',
				'column' => 2,
			),
			'hero_cta_alt_label' => array(
				'label'  => __( 'Secondary button label', 'iflynepal' ),
				'type'   => 'text',
				'help'   => '',
				'column' => 1,
			),
			'hero_cta_alt_url'   => array(
				'label'  => __( 'Secondary button link', 'iflynepal' ),
				'type'   => 'url',
				'help'   => '',
				'column' => 2,
			),
		),
	);

	$listing = $head( 'listing', $underline_help );

	/*
	 * No eyebrow on this one. The grid is the page's main event and sits directly
	 * under the hero, so a kicker above its heading is a second label for
	 * something the reader has already been told they are looking at.
	 */
	unset( $listing['listing_eyebrow'] );

	$listing['listing_annotation_static'] = array(
		'label' => __( 'Handwritten note — fixed part', 'iflynepal' ),
		'type'  => 'text',
		'help'  => __( 'The handwritten line beside the heading, e.g. "Find your way". This part never moves or re-types.', 'iflynepal' ),
	);

	$listing['listing_annotation_words'] = array(
		'label' => __( 'Handwritten note — cycling endings', 'iflynepal' ),
		'type'  => 'lines',
		'help'  => __( 'One ending per line, e.g. "inward", "within", "to yourself". They type and delete themselves in turn after the fixed part. A single line just sits there; leave empty and only the fixed part shows.', 'iflynepal' ),
	);

	$sections['listing'] = array(
		'label'       => __( 'Package Grid', 'iflynepal' ),
		'description' => __( 'Introduces the card grid. The cards themselves are the packages filed under this type — they are not fields.', 'iflynepal' ),
		'fields'      => $listing,
	);

	$benefits = $head( 'benefits' );

	$benefits['benefit_cards'] = array(
		/* translators: %d: the most cards allowed. */
		'label' => sprintf( __( 'Cards (max %d)', 'iflynepal' ), IFLYNEPAL_ARCHIVE_BENEFIT_CARDS ),
		'type'  => 'cards',
		'help'  => __( 'Add a card, fill it in, drag nothing — they render in the order they are listed. Remove one and the rest close up on their own.', 'iflynepal' ),
		'item'  => __( 'Card', 'iflynepal' ),
		'max'   => IFLYNEPAL_ARCHIVE_BENEFIT_CARDS,
		'parts' => array(
			'image' => array(
				'label' => __( 'Image', 'iflynepal' ),
				'type'  => 'image',
			),
			'title' => array(
				'label' => __( 'Card title', 'iflynepal' ),
				'type'  => 'text',
			),
			'text'  => array(
				'label' => __( 'Card description', 'iflynepal' ),
				'type'  => 'textarea',
			),
		),
	);

	$sections['benefits'] = array(
		'label'       => __( 'Reasons to come', 'iflynepal' ),
		'description' => __( 'Add as many cards as the section needs. A card with nothing in it is dropped when you save.', 'iflynepal' ),
		'fields'      => $benefits,
	);

	$plans       = $head( 'plans', $emphasis_help );
	$plan_groups = array();

	/*
	 * Each plan is a group, so the edit screen draws its eight fields inside a
	 * card of their own instead of running all twenty-four down one panel where
	 * only the number in the label tells you which plan you are editing. The
	 * fields are still flat, individually keyed term meta — the grouping is the
	 * edit screen's, and the save routine never sees it.
	 *
	 * Because the card is titled with the plan number, the labels inside it drop
	 * it: "Name", not "Plan 2 — name".
	 */
	for ( $i = 1; $i <= IFLYNEPAL_ARCHIVE_PLAN_SLOTS; $i++ ) {
		$group = 'plan_' . $i;

		/* translators: %d: plan number. */
		$plan_groups[ $group ] = sprintf( __( 'Plan %d', 'iflynepal' ), $i );

		$plans[ 'plan_' . $i . '_name' ]       = array(
			'label'  => __( 'Name', 'iflynepal' ),
			'type'   => 'text',
			'help'   => '',
			'group'  => $group,
			'column' => 1,
		);
		$plans[ 'plan_' . $i . '_subtitle' ]   = array(
			'label'  => __( 'Subtitle', 'iflynepal' ),
			'type'   => 'text',
			'help'   => '',
			'group'  => $group,
			'column' => 2,
		);
		$plans[ 'plan_' . $i . '_price' ]      = array(
			'label'  => __( 'Price', 'iflynepal' ),
			'type'   => 'text',
			'help'   => __( 'Written exactly as it should read, e.g. "$680" or "from $1,800".', 'iflynepal' ),
			'group'  => $group,
			'column' => 1,
		);
		$plans[ 'plan_' . $i . '_price_note' ] = array(
			'label'  => __( 'Price note', 'iflynepal' ),
			'type'   => 'text',
			'help'   => __( 'The small text beside the price, e.g. "/ 7 days".', 'iflynepal' ),
			'group'  => $group,
			'column' => 2,
		);
		$plans[ 'plan_' . $i . '_features' ]   = array(
			'label' => __( 'What is included', 'iflynepal' ),
			'type'  => 'lines',
			'help'  => __( 'One item per line. Blank lines are ignored.', 'iflynepal' ),
			'group' => $group,
		);
		$plans[ 'plan_' . $i . '_cta_label' ]  = array(
			'label'  => __( 'Button label', 'iflynepal' ),
			'type'   => 'text',
			'help'   => '',
			'group'  => $group,
			'column' => 1,
		);
		$plans[ 'plan_' . $i . '_cta_url' ]    = array(
			'label'  => __( 'Button link', 'iflynepal' ),
			'type'   => 'url',
			'help'   => '',
			'group'  => $group,
			'column' => 2,
		);
		$plans[ 'plan_' . $i . '_featured' ]   = array(
			'label' => __( 'Highlight this plan', 'iflynepal' ),
			'type'  => 'checkbox',
			'help'  => __( 'Draws it in the raised, darker style. Only one plan should be highlighted.', 'iflynepal' ),
			'group' => $group,
		);
	}

	$sections['plans'] = array(
		'label'       => __( 'Booking plans', 'iflynepal' ),
		'description' => __( 'The three-column price comparison. A plan with no name is left off the page.', 'iflynepal' ),
		'fields'      => $plans,
		'groups'      => $plan_groups,
	);

	/*
	 * Upcoming departures — the heading band and the rail of cards beneath it.
	 *
	 * The section was removed on 11 September and came back as a heading only,
	 * because the card design was still open with the client. It is settled now,
	 * and the cards are a repeater rather than a run of numbered slots for the
	 * same reason the reason-tiles are: the design has no fixed number of them,
	 * and an archive with three departures should be a list of three, not seven
	 * empty boxes.
	 *
	 * A departure card is *not* a package and is not derived from one. The dates
	 * on the package meta box are a package's own fixed departures; these are
	 * scheduled small-group dates the office is selling now, which may span
	 * several packages or none of them. Deriving one from the other was
	 * considered and does not hold — the design's cards carry their own price,
	 * their own duration and a remaining-places pill that lives nowhere else.
	 *
	 * And as everywhere else in this plugin: nothing here is enforced. The pill
	 * is a line of text an editor types. There is no seat count behind it, no
	 * capacity, and no check that anything sold matches it.
	 */
	$departures = $head( 'departures' );

	$departures['departure_cards'] = array(
		/* translators: %d: the most cards allowed. */
		'label' => sprintf( __( 'Cards (max %d)', 'iflynepal' ), IFLYNEPAL_ARCHIVE_DEPARTURE_CARDS ),
		'type'  => 'cards',
		'help'  => __( 'Each card is one scheduled departure. They render in the order they are listed; remove one and the rest close up on their own. A card with no Reserve link shows Reserve as plain text rather than as a dead button.', 'iflynepal' ),
		'item'  => __( 'Departure', 'iflynepal' ),
		'max'   => IFLYNEPAL_ARCHIVE_DEPARTURE_CARDS,
		'parts' => array(
			'pill'     => array(
				'label' => __( 'Pill text', 'iflynepal' ),
				'type'  => 'text',
			),
			'date'     => array(
				'label' => __( 'Date', 'iflynepal' ),
				'type'  => 'text',
			),
			'title'    => array(
				'label' => __( 'Title', 'iflynepal' ),
				'type'  => 'text',
			),
			'duration' => array(
				'label' => __( 'Duration', 'iflynepal' ),
				'type'  => 'text',
			),
			'price'    => array(
				'label' => __( 'Price', 'iflynepal' ),
				'type'  => 'text',
			),
			'link'     => array(
				'label' => __( 'Reserve link', 'iflynepal' ),
				'type'  => 'url',
			),
			'image'    => array(
				'label' => __( 'Card image', 'iflynepal' ),
				'type'  => 'image',
			),
		),
	);

	$departures['departures_foot'] = array(
		'label' => __( 'Footnote', 'iflynepal' ),
		'type'  => 'textarea',
		'help'  => __( 'The small line under the rail, for what the dates are and are not — e.g. that spot counts are indicative. Left empty, nothing is drawn.', 'iflynepal' ) . ' ' . $textarea_help,
	);

	$sections['departures'] = array(
		'label'       => __( 'Upcoming departures', 'iflynepal' ),
		'description' => __( 'The heading band, and the rail of departure cards under it. Dates, durations and prices are written exactly as they should read — nothing here is parsed, converted or checked against anything.', 'iflynepal' ),
		'fields'      => $departures,
	);

	$compare = $head( 'compare' );

	/*
	 * The table is one field, edited as a table.
	 *
	 * It was twenty-five: four column headings, three sub-notes and a cell per
	 * row per column, each its own text box in a flat column of the panel. The
	 * shape of the thing being edited was nowhere on the screen — you read
	 * "Row 4, column 3" and worked out what that meant. It is now a grid with
	 * the headings along the top and a button that adds a row, which is the
	 * same control the CloudColleague industry pay-rates tab uses.
	 */
	$compare['compare_table'] = array(
		/* translators: %d: the most rows allowed. */
		'label' => sprintf( __( 'Table (max %d rows)', 'iflynepal' ), IFLYNEPAL_ARCHIVE_COMPARE_MAX_ROWS ),
		'type'  => 'table',
		'help'  => __( 'Type over a column heading to rename it. A column left without a heading is dropped from the page, and the table needs two to render at all.', 'iflynepal' ),
	);

	$compare['compare_footnote'] = array(
		'label' => __( 'Footnote', 'iflynepal' ),
		'type'  => 'textarea',
		'help'  => $textarea_help,
	);

	$sections['compare'] = array(
		'label'       => __( 'Comparison table', 'iflynepal' ),
		'description' => __( 'A row with an empty first column is left out of the table.', 'iflynepal' ),
		'fields'      => $compare,
	);

	$sections['testimonials'] = array(
		'label'       => __( 'Testimonials heading', 'iflynepal' ),
		'description' => __( 'The reviews themselves are Testimonials, edited under their own menu.', 'iflynepal' ),
		'fields'      => array(
			'testimonials_eyebrow' => array(
				'label' => __( 'Eyebrow', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
		),
	);

	$faq = array(
		'faq_eyebrow' => array(
			'label' => __( 'Eyebrow', 'iflynepal' ),
			'type'  => 'text',
			'help'  => '',
		),
		'faq_heading' => array(
			'label' => __( 'Heading', 'iflynepal' ),
			'type'  => 'rich',
			'help'  => $emphasis_help,
		),
	);

	/*
	 * The questions are a repeater, not a run of numbered slots.
	 *
	 * Same reasoning as the reason cards, and the same control the
	 * CloudColleague industry FAQ tab uses: a list of questions has no fixed
	 * length, and numbering the slots means an editor with four questions works
	 * around eight empty boxes. The list is the count — nothing stores one.
	 */
	$faq['faq_items'] = array(
		/* translators: %d: the most questions allowed. */
		'label' => sprintf( __( 'Questions (max %d)', 'iflynepal' ), IFLYNEPAL_ARCHIVE_FAQ_MAX ),
		'type'  => 'cards',
		'help'  => __( 'Add a question, fill it in — they render in the order they are listed.', 'iflynepal' ),
		'item'  => __( 'Question', 'iflynepal' ),
		'max'   => IFLYNEPAL_ARCHIVE_FAQ_MAX,
		'parts' => array(
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

	$sections['faq'] = array(
		'label'       => __( 'Questions before booking', 'iflynepal' ),
		'description' => __( 'A question with no answer, or an answer with no question, is left out.', 'iflynepal' ),
		'fields'      => $faq,
	);

	$sections['final'] = array(
		'label'       => __( 'Closing call to action', 'iflynepal' ),
		'description' => __( 'The dark card that closes the page.', 'iflynepal' ),
		'fields'      => array(
			'final_eyebrow'       => array(
				'label' => __( 'Eyebrow', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
			'final_heading'       => array(
				'label' => __( 'Heading', 'iflynepal' ),
				'type'  => 'rich',
				'help'  => $emphasis_help,
			),
			'final_lead'          => array(
				'label' => __( 'Lead paragraph', 'iflynepal' ),
				'type'  => 'textarea',
				'help'  => $textarea_help,
			),
			'final_image'         => array(
				'label' => __( 'Background image', 'iflynepal' ),
				'type'  => 'image',
				'help'  => '',
			),
			// Paired onto a row each, the same way the hero's buttons are.
			'final_cta_label'     => array(
				'label'  => __( 'Primary button label', 'iflynepal' ),
				'type'   => 'text',
				'help'   => '',
				'column' => 1,
			),
			'final_cta_url'       => array(
				'label'  => __( 'Primary button link', 'iflynepal' ),
				'type'   => 'url',
				'help'   => '',
				'column' => 2,
			),
			'final_cta_alt_label' => array(
				'label'  => __( 'Secondary button label', 'iflynepal' ),
				'type'   => 'text',
				'help'   => '',
				'column' => 1,
			),
			'final_cta_alt_url'   => array(
				'label'  => __( 'Secondary button link', 'iflynepal' ),
				'type'   => 'url',
				'help'   => '',
				'column' => 2,
			),
		),
	);

	/**
	 * Filters the package type archive content model.
	 *
	 * @since 1.0.0
	 *
	 * @param array[] $sections Sections, each with 'label', 'description' and 'fields'.
	 */
	return apply_filters( 'iflynepal_package_type_archive_schema', $sections );
}

/**
 * The sections a top-level package type carries but a category does not.
 *
 * A top-level type archive — /retreat-nepal/ — is the landing page for a whole
 * kind of travel, and the design wraps its card grid in ten sections of
 * marketing copy. A category archive beneath it —
 * /retreat-nepal/ayurvedic-retreats/ — is a filtered view of the same catalogue,
 * and its design is two sections: the hero and the grid.
 *
 * The reason is not only visual. The plans, the comparison table, the FAQ and
 * the closing CTA all answer "why book this kind of trip with us", which is
 * asked once per type and not again per category — repeating them under every
 * category would duplicate the same copy across a dozen URLs, which is a real
 * SEO problem as well as a maintenance one.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_TOP_LEVEL_SECTIONS = array(
	'benefits',
	'plans',
	'departures',
	'compare',
	'testimonials',
	'faq',
	'final',
);

/**
 * The archive sections that apply to one term.
 *
 * Depth decides it: a term with a parent is a category and gets the hero and the
 * grid; a root term is a package type and gets everything.
 *
 * This is not the same question as "does this section have content in it". The
 * templates skip an empty section anyway. This decides which sections a term can
 * have *at all* — which is why it also governs the edit screen, so an editor is
 * never shown eight sections whose copy would never appear on the page.
 *
 * @since 1.0.0
 *
 * @param WP_Term|int|null $term Term or term ID. Null means every section.
 * @return array[] Sections, keyed as the schema keys them.
 */
function iflynepal_package_type_archive_sections_for_term( $term ) {
	$sections = iflynepal_package_type_archive_schema();

	if ( null === $term ) {
		return $sections;
	}

	if ( ! $term instanceof WP_Term ) {
		$term = get_term( (int) $term, IFLYNEPAL_PACKAGE_TAXONOMY );
	}

	$is_category = $term instanceof WP_Term && $term->parent > 0;

	if ( $is_category ) {
		foreach ( IFLYNEPAL_ARCHIVE_TOP_LEVEL_SECTIONS as $key ) {
			unset( $sections[ $key ] );
		}
	}

	/**
	 * Filters the archive sections that apply to a term.
	 *
	 * @since 1.0.0
	 *
	 * @param array[]      $sections    Applicable sections, keyed by section key.
	 * @param WP_Term|null $term        The term, when it resolved.
	 * @param bool         $is_category Whether the term has a parent.
	 */
	return apply_filters(
		'iflynepal_package_type_archive_sections_for_term',
		$sections,
		$term instanceof WP_Term ? $term : null,
		$is_category
	);
}

/**
 * The fields belonging to the sections that apply to one term.
 *
 * The save routine is scoped with this, not with the full field list. A field
 * that was never rendered was never submitted, and a save that walked every
 * field would read those as empty and delete them — so re-parenting a term under
 * another and pressing Update would silently wipe the copy it had as a root.
 *
 * @since 1.0.0
 *
 * @param WP_Term|int|null $term Term or term ID.
 * @return array[] Field definitions keyed by schema key.
 */
function iflynepal_package_type_archive_fields_for_term( $term ) {
	$fields = array();

	foreach ( iflynepal_package_type_archive_sections_for_term( $term ) as $section ) {
		$fields += $section['fields'];
	}

	return $fields;
}

/**
 * Every field in the schema, flattened, keyed by schema key.
 *
 * @since 1.0.0
 *
 * @return array[] Field definitions.
 */
function iflynepal_package_type_archive_fields() {
	$fields = array();

	foreach ( iflynepal_package_type_archive_schema() as $section ) {
		$fields += $section['fields'];
	}

	return $fields;
}

/**
 * Sanitizes one field's submitted value according to its type.
 *
 * @since 1.0.0
 *
 * @param mixed  $value Raw submitted value, already unslashed.
 * @param string $type  Field type from the schema.
 * @param array  $field The whole field definition, for the types that need more
 *                      than their name — a repeater's parts and its cap.
 * @return string Value as it should be stored.
 */
function iflynepal_archive_sanitize_value( $value, $type, $field = array() ) {
	switch ( $type ) {
		case 'cards':
			return iflynepal_archive_sanitize_cards( $value, $field );

		case 'table':
			return iflynepal_archive_sanitize_table( $value );

		case 'image':
			return (string) absint( $value );

		case 'url':
			return esc_url_raw( trim( (string) $value ) );

		case 'rich':
		case 'textarea':
			return iflynepal_booking_kses_text( $value );

		case 'lines':
			return sanitize_textarea_field( (string) $value );

		case 'prose':
			/*
			 * A prose part is a repeater's wp_editor(), so it is saved the way the
			 * top-level wysiwyg fields are: the sanitizer core uses for post
			 * content, which keeps the paragraphs, the lists and the emphasis the
			 * toolbar can produce and drops everything else.
			 *
			 * An editor emptied in TinyMCE posts back a paragraph holding a
			 * non-breaking space rather than nothing at all, so a value with no
			 * words left in it is stored as empty — otherwise a description an
			 * editor deleted would go on printing a blank paragraph on the page,
			 * and would keep its day alive as a "filled" row.
			 */
			$value = iflynepal_booking_strip_inline_style( wp_kses_post( (string) $value ) );

			return '' === trim( wp_strip_all_tags( str_replace( '&nbsp;', ' ', $value ) ) ) ? '' : $value;

		case 'checkbox':
			return $value ? '1' : '';

		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * The sub-fields one repeater holds, and what each of them is.
 *
 * Declared on the schema field rather than hardcoded here, so a second repeater
 * is a schema entry and not a second renderer, a second sanitizer and a second
 * row template. The reason cards keep the shape they have always had; the FAQ
 * is a question and an answer.
 *
 * @since 1.0.0
 *
 * @param array $field Field definition.
 * @return array[] Parts keyed by their name, each with 'label' and 'type'.
 */
function iflynepal_archive_card_parts( $field ) {
	return isset( $field['parts'] ) && is_array( $field['parts'] ) ? $field['parts'] : array();
}

/**
 * Most rows one repeater accepts.
 *
 * @since 1.0.0
 *
 * @param array $field Field definition.
 * @return int Cap, or 0 for no cap.
 */
function iflynepal_archive_card_max( $field ) {
	return isset( $field['max'] ) ? (int) $field['max'] : 0;
}

/**
 * Cleans a submitted repeater into the list that gets stored.
 *
 * Rows arrive as a numbered array from the form, but the numbering is a detail
 * of how HTML names inputs — it is discarded here and the survivors are
 * re-indexed from zero. Nothing downstream should ever depend on a row's
 * original position in the form.
 *
 * Each part is sanitized by its own declared type, so a repeater cannot store
 * something its schema did not describe. A row with nothing in any of its parts
 * is dropped rather than stored empty: an editor adds a row before filling it,
 * and leaving one on the page would otherwise be enough to publish a blank.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw submitted repeater, already unslashed.
 * @param array $field Field definition, carrying 'parts' and 'max'.
 * @return array[] Rows, each keyed by the field's part names.
 */
function iflynepal_archive_sanitize_cards( $value, $field = array() ) {
	$parts = iflynepal_archive_card_parts( $field );
	$max   = iflynepal_archive_card_max( $field );

	if ( ! is_array( $value ) || empty( $parts ) ) {
		return array();
	}

	$rows = array();

	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$clean  = array();
		$filled = false;

		foreach ( $parts as $part_key => $part ) {
			$raw_part = isset( $row[ $part_key ] ) ? $row[ $part_key ] : '';

			/*
			 * A timeline part is a repeater inside a repeater, so its value is a
			 * list rather than a string and it cannot go through the scalar
			 * sanitizer: sanitize_text_field() on an array returns an empty string,
			 * which would silently throw the whole timeline away on every save.
			 */
			if ( 'timeline' === $part['type'] ) {
				$part_value = iflynepal_package_sanitize_timeline( $raw_part, $part );

				if ( ! empty( $part_value ) ) {
					$filled = true;
				}

				$clean[ $part_key ] = $part_value;

				continue;
			}

			$part_value = '' === $raw_part ? '' : iflynepal_archive_sanitize_value( $raw_part, $part['type'] );

			if ( 'image' === $part['type'] ) {
				$part_value = (int) $part_value;

				if ( $part_value ) {
					$filled = true;
				}
			} elseif ( '' !== $part_value ) {
				$filled = true;
			}

			$clean[ $part_key ] = $part_value;
		}

		if ( ! $filled ) {
			continue;
		}

		$rows[] = $clean;

		// The cap is enforced on save as well as in the browser: the form is not
		// the only thing that can post to this screen.
		if ( $max > 0 && count( $rows ) >= $max ) {
			break;
		}
	}

	return $rows;
}

/**
 * Cleans a submitted comparison table into the array that gets stored.
 *
 * Columns are a fixed run, so they are read by position and an absent one is
 * stored empty — the front end decides what to draw from whether a heading was
 * written, and it cannot do that if a blank column simply vanishes from the
 * array and shifts the ones after it.
 *
 * Rows are the opposite: they are added and removed with a button, so the
 * numbering the form posts is a detail of how HTML names inputs. It is
 * discarded and the surviving rows are re-indexed from zero, which is what
 * makes deleting the second of five rows safe.
 *
 * A row with nothing in any cell is dropped rather than stored empty: an editor
 * adds a row before filling it, and leaving one on the screen would otherwise
 * be enough to put a blank line in the published table.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw submitted table, already unslashed.
 * @return array Table with 'columns' and 'rows', or an empty array when unset.
 */
function iflynepal_archive_sanitize_table( $value ) {
	$raw_columns = ( is_array( $value ) && isset( $value['columns'] ) && is_array( $value['columns'] ) ) ? $value['columns'] : array();
	$raw_rows    = ( is_array( $value ) && isset( $value['rows'] ) && is_array( $value['rows'] ) ) ? $value['rows'] : array();

	$columns    = array();
	$rows       = array();
	$has_column = false;

	for ( $index = 0; $index < IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS; $index++ ) {
		$column = isset( $raw_columns[ $index ] ) && is_array( $raw_columns[ $index ] ) ? $raw_columns[ $index ] : array();

		$label = isset( $column['label'] ) ? sanitize_text_field( $column['label'] ) : '';
		$note  = isset( $column['note'] ) ? sanitize_text_field( $column['note'] ) : '';

		if ( '' !== $label ) {
			$has_column = true;
		}

		$columns[] = array(
			'label' => $label,
			'note'  => $note,
		);
	}

	foreach ( $raw_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$cells  = array();
		$filled = false;

		for ( $index = 0; $index < IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS; $index++ ) {
			$cell = isset( $row[ $index ] ) ? sanitize_text_field( $row[ $index ] ) : '';

			if ( '' !== $cell ) {
				$filled = true;
			}

			$cells[] = $cell;
		}

		if ( ! $filled ) {
			continue;
		}

		$rows[] = $cells;

		// The cap is enforced on save as well as in the browser: the form is not
		// the only thing that can post to this screen.
		if ( count( $rows ) >= IFLYNEPAL_ARCHIVE_COMPARE_MAX_ROWS ) {
			break;
		}
	}

	// Nothing written in either half is nothing to store.
	if ( ! $has_column && empty( $rows ) ) {
		return array();
	}

	return array(
		'columns' => $columns,
		'rows'    => $rows,
	);
}

/**
 * A stored table, in the shape a template and the edit screen both expect.
 *
 * Both halves are guaranteed present and arrays, so neither caller has to test
 * what came back out of the database before looping it.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $key     Schema key, e.g. 'compare_table'.
 * @return array Table with 'columns' and 'rows'.
 */
function iflynepal_archive_table( $term_id, $key ) {
	$table = get_term_meta( (int) $term_id, iflynepal_archive_meta_key( $key ), true );

	if ( ! is_array( $table ) ) {
		$table = array();
	}

	return array(
		'columns' => isset( $table['columns'] ) && is_array( $table['columns'] ) ? $table['columns'] : array(),
		'rows'    => isset( $table['rows'] ) && is_array( $table['rows'] ) ? $table['rows'] : array(),
	);
}

/**
 * A stored card repeater, ready to loop over in a template.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $key     Schema key, e.g. 'benefit_cards'.
 * @return array[] Cards, each with 'title', 'text' and 'image'. Empty when unset.
 */
function iflynepal_archive_cards( $term_id, $key ) {
	$cards = get_term_meta( (int) $term_id, iflynepal_archive_meta_key( $key ), true );

	return is_array( $cards ) ? $cards : array();
}

/**
 * One stored archive field.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $key     Schema key, e.g. 'hero_heading'.
 * @return string Stored value, empty string when unset.
 */
function iflynepal_archive_field( $term_id, $key ) {
	return (string) get_term_meta( (int) $term_id, iflynepal_archive_meta_key( $key ), true );
}

/**
 * A `lines` field as a list.
 *
 * @since 1.0.0
 *
 * @param int    $term_id Package type term.
 * @param string $key     Schema key.
 * @return string[] Non-empty lines, in order.
 */
function iflynepal_archive_field_lines( $term_id, $key ) {
	$lines = preg_split( '/\R/', iflynepal_archive_field( $term_id, $key ) );

	return array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
}

/**
 * The filled slots of a numbered group, ready to loop over in a template.
 *
 * Which slots are in use is worked out here, from the stored values, every time
 * it is asked for. Nothing records a count: a stored count is a second source of
 * truth that goes stale the moment somebody empties a slot.
 *
 * @since 1.0.0
 *
 * @param int      $term_id  Package type term.
 * @param string   $prefix   Slot prefix, e.g. 'benefit' for benefit_1_title.
 * @param int      $slots    How many slots the schema defines.
 * @param string[] $keys     Field names within a slot, e.g. array( 'image', 'title', 'text' ).
 * @param string   $required Field name that must be filled for the slot to count. Defaults to the first.
 * @return array[] One array per filled slot, keyed by field name, plus 'index'.
 */
function iflynepal_archive_group( $term_id, $prefix, $slots, $keys, $required = '' ) {
	$required = '' === $required ? (string) reset( $keys ) : $required;
	$rows     = array();

	for ( $i = 1; $i <= (int) $slots; $i++ ) {
		$row = array( 'index' => $i );

		foreach ( $keys as $key ) {
			$row[ $key ] = iflynepal_archive_field( $term_id, $prefix . '_' . $i . '_' . $key );
		}

		if ( '' === trim( (string) $row[ $required ] ) ) {
			continue;
		}

		$rows[] = $row;
	}

	return $rows;
}
