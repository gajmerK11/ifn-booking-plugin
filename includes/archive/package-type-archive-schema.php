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
 * Repeating parts of the design — the six reason tiles, the three plans, the
 * comparison rows, the FAQ — are a fixed number of numbered slots rather than a
 * repeater widget, which is the same choice the theme made for the homepage
 * lists. An empty slot is skipped at render time; nothing stores how many slots
 * are in use, because a stored count is the thing that goes stale.
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
 * Number of reason tiles the design lays out.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_BENEFIT_SLOTS = 6;

/**
 * Number of booking plans the design lays out.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_PLAN_SLOTS = 3;

/**
 * Number of rows in the comparison table.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_COMPARE_SLOTS = 6;

/**
 * Number of questions in the FAQ.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ARCHIVE_FAQ_SLOTS = 6;

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
 *   textarea  several lines of plain text
 *   lines     a textarea read as one item per line, for short lists
 *   url       a link target
 *   image     an attachment ID chosen from the media library
 *
 * @since 1.0.0
 *
 * @return array[] Sections, each with 'label', 'description' and 'fields'.
 */
function iflynepal_package_type_archive_schema() {
	$head = static function ( $prefix, $heading_help = '' ) {
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
				'help'  => '',
			),
		);
	};

	$emphasis_help = __( 'Wrap a word or two in &lt;em&gt; to set it in the accent style, as the design does.', 'iflynepal' );

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
			'hero_cta_label'     => array(
				'label' => __( 'Primary button label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
			'hero_cta_url'       => array(
				'label' => __( 'Primary button link', 'iflynepal' ),
				'type'  => 'url',
				'help'  => '',
			),
			'hero_cta_alt_label' => array(
				'label' => __( 'Secondary button label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
			'hero_cta_alt_url'   => array(
				'label' => __( 'Secondary button link', 'iflynepal' ),
				'type'  => 'url',
				'help'  => '',
			),
		),
	);

	$sections['listing'] = array(
		'label'       => __( 'Package grid heading', 'iflynepal' ),
		'description' => __( 'Introduces the card grid. The cards themselves are the packages filed under this type — they are not fields.', 'iflynepal' ),
		'fields'      => $head( 'listing', $emphasis_help ),
	);

	$sections['feature'] = array(
		'label'       => __( 'Feature band', 'iflynepal' ),
		'description' => __( 'The single wide image band with a heading over it.', 'iflynepal' ),
		'fields'      => array(
			'feature_heading' => array(
				'label' => __( 'Heading', 'iflynepal' ),
				'type'  => 'rich',
				'help'  => $emphasis_help,
			),
			'feature_image'   => array(
				'label' => __( 'Image', 'iflynepal' ),
				'type'  => 'image',
				'help'  => '',
			),
			'feature_caption' => array(
				'label' => __( 'Caption', 'iflynepal' ),
				'type'  => 'textarea',
				'help'  => __( 'The small print under the band.', 'iflynepal' ),
			),
		),
	);

	$benefits = $head( 'benefits' );

	for ( $i = 1; $i <= IFLYNEPAL_ARCHIVE_BENEFIT_SLOTS; $i++ ) {
		$benefits[ 'benefit_' . $i . '_image' ] = array(
			/* translators: %d: tile number. */
			'label' => sprintf( __( 'Tile %d — image', 'iflynepal' ), $i ),
			'type'  => 'image',
			'help'  => '',
		);
		$benefits[ 'benefit_' . $i . '_title' ] = array(
			/* translators: %d: tile number. */
			'label' => sprintf( __( 'Tile %d — title', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => '',
		);
		$benefits[ 'benefit_' . $i . '_text' ]  = array(
			/* translators: %d: tile number. */
			'label' => sprintf( __( 'Tile %d — text', 'iflynepal' ), $i ),
			'type'  => 'textarea',
			'help'  => '',
		);
	}

	$sections['benefits'] = array(
		'label'       => __( 'Reasons to come', 'iflynepal' ),
		'description' => __( 'Leave a tile empty and it is left out of the grid. Nothing has to be renumbered.', 'iflynepal' ),
		'fields'      => $benefits,
	);

	$plans = $head( 'plans', $emphasis_help );

	for ( $i = 1; $i <= IFLYNEPAL_ARCHIVE_PLAN_SLOTS; $i++ ) {
		$plans[ 'plan_' . $i . '_name' ]       = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — name', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => '',
		);
		$plans[ 'plan_' . $i . '_subtitle' ]   = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — subtitle', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => '',
		);
		$plans[ 'plan_' . $i . '_price' ]      = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — price', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => __( 'Written exactly as it should read, e.g. "$680" or "from $1,800".', 'iflynepal' ),
		);
		$plans[ 'plan_' . $i . '_price_note' ] = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — price note', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => __( 'The small text beside the price, e.g. "/ 7 days".', 'iflynepal' ),
		);
		$plans[ 'plan_' . $i . '_features' ]   = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — what is included', 'iflynepal' ), $i ),
			'type'  => 'lines',
			'help'  => __( 'One item per line. Blank lines are ignored.', 'iflynepal' ),
		);
		$plans[ 'plan_' . $i . '_cta_label' ]  = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — button label', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => '',
		);
		$plans[ 'plan_' . $i . '_cta_url' ]    = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — button link', 'iflynepal' ), $i ),
			'type'  => 'url',
			'help'  => '',
		);
		$plans[ 'plan_' . $i . '_featured' ]   = array(
			/* translators: %d: plan number. */
			'label' => sprintf( __( 'Plan %d — highlight this plan', 'iflynepal' ), $i ),
			'type'  => 'checkbox',
			'help'  => __( 'Draws it in the raised, darker style. Only one plan should be highlighted.', 'iflynepal' ),
		);
	}

	$sections['plans'] = array(
		'label'       => __( 'Booking plans', 'iflynepal' ),
		'description' => __( 'The three-column price comparison.', 'iflynepal' ),
		'fields'      => $plans,
	);

	$sections['departures'] = array(
		'label'       => __( 'Departures heading', 'iflynepal' ),
		'description' => __( 'Introduces the departures rail. The dates themselves come from the fixed departure dates set on each package.', 'iflynepal' ),
		'fields'      => $head( 'departures' ),
	);

	$compare = $head( 'compare' );

	$compare['compare_col_1'] = array(
		'label' => __( 'Column 1 heading', 'iflynepal' ),
		'type'  => 'text',
		'help'  => __( 'The row-label column, e.g. "Factor".', 'iflynepal' ),
	);

	for ( $c = 2; $c <= 4; $c++ ) {
		$compare[ 'compare_col_' . $c ]           = array(
			/* translators: %d: column number. */
			'label' => sprintf( __( 'Column %d heading', 'iflynepal' ), $c ),
			'type'  => 'text',
			'help'  => '',
		);
		$compare[ 'compare_col_' . $c . '_note' ] = array(
			/* translators: %d: column number. */
			'label' => sprintf( __( 'Column %d sub-note', 'iflynepal' ), $c ),
			'type'  => 'text',
			'help'  => '',
		);
	}

	for ( $i = 1; $i <= IFLYNEPAL_ARCHIVE_COMPARE_SLOTS; $i++ ) {
		for ( $c = 1; $c <= 4; $c++ ) {
			$compare[ 'compare_row_' . $i . '_col_' . $c ] = array(
				/* translators: 1: row number, 2: column number. */
				'label' => sprintf( __( 'Row %1$d, column %2$d', 'iflynepal' ), $i, $c ),
				'type'  => 'text',
				'help'  => '',
			);
		}
	}

	$compare['compare_footnote'] = array(
		'label' => __( 'Footnote', 'iflynepal' ),
		'type'  => 'textarea',
		'help'  => __( 'Where the comparison figures come from. A comparison table without one invites a complaint.', 'iflynepal' ),
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

	for ( $i = 1; $i <= IFLYNEPAL_ARCHIVE_FAQ_SLOTS; $i++ ) {
		$faq[ 'faq_' . $i . '_question' ] = array(
			/* translators: %d: question number. */
			'label' => sprintf( __( 'Question %d', 'iflynepal' ), $i ),
			'type'  => 'text',
			'help'  => '',
		);
		$faq[ 'faq_' . $i . '_answer' ]   = array(
			/* translators: %d: question number. */
			'label' => sprintf( __( 'Answer %d', 'iflynepal' ), $i ),
			'type'  => 'textarea',
			'help'  => '',
		);
	}

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
				'help'  => '',
			),
			'final_image'         => array(
				'label' => __( 'Background image', 'iflynepal' ),
				'type'  => 'image',
				'help'  => '',
			),
			'final_cta_label'     => array(
				'label' => __( 'Primary button label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
			'final_cta_url'       => array(
				'label' => __( 'Primary button link', 'iflynepal' ),
				'type'  => 'url',
				'help'  => '',
			),
			'final_cta_alt_label' => array(
				'label' => __( 'Secondary button label', 'iflynepal' ),
				'type'  => 'text',
				'help'  => '',
			),
			'final_cta_alt_url'   => array(
				'label' => __( 'Secondary button link', 'iflynepal' ),
				'type'  => 'url',
				'help'  => '',
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
 * @return string Value as it should be stored.
 */
function iflynepal_archive_sanitize_value( $value, $type ) {
	switch ( $type ) {
		case 'image':
			return (string) absint( $value );

		case 'url':
			return esc_url_raw( trim( (string) $value ) );

		case 'rich':
			return iflynepal_booking_kses_text( $value );

		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( (string) $value );

		case 'checkbox':
			return $value ? '1' : '';

		default:
			return sanitize_text_field( (string) $value );
	}
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
