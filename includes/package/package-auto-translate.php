<?php
/**
 * Drafts a package's free text in the new language the moment a translation
 * is created, using DeepL.
 *
 * Runs on the same "+ Add" flow the media, package-type and pre-filled
 * boilerplate already ride (the `use_block_editor_for_post` filter Polylang's
 * own sync uses) — this is the last piece of it, covering the prose no fixed
 * lookup or bilingual default can stand in for: the title, the overview, the
 * itinerary, the FAQs, and so on.
 *
 * Deliberately narrow in two ways:
 *
 *  - Only prose fields are sent to DeepL. A price, a duration in days, a
 *    check-in time, a phone number or a currency code is a fact, not a
 *    sentence — translating "320" or "USD" risks nothing today but buys
 *    nothing either, so those fields are left exactly as they were before
 *    this file existed: created blank, for an editor to fill in. The three
 *    fixed-vocabulary fields (meals, accommodation, experience level) already
 *    have their own correct translation — a lookup table read at render time
 *    — and are left to it rather than run through an API that would just
 *    guess the same three answers less reliably.
 *  - Silence on any failure. No configured key, an expired or over-quota key,
 *    a timeout, a malformed response: every one of them leaves the new
 *    package exactly as blank on the affected fields as it was before this
 *    file existed, never a fatal error and never a half-written field. This
 *    is a draft an editor reviews before publishing, not a silent export.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The package fields sent to DeepL as-is, keyed by their schema key.
 *
 * Everything else the schema declares — every price, date, time, ID, URL,
 * phone number and fixed-choice dropdown — is left out on purpose; see the
 * file docblock.
 *
 * @since 1.0.0
 *
 * @return string[]
 */
function iflynepal_package_translatable_fields() {
	return array(
		'pill',
		'duration',
		'suitability',
		'price',
		'peek',
		'buffer_notice',
		'heading',
		'glance_destination',
		'glance_duration',
		'glance_activities',
		'glance_altitude',
		'glance_group',
		'glance_best_time',
		'overview_intro',
		'overview_body',
		'highlights',
		'itinerary_heading',
		'dates_heading',
		'dates_lead',
		'included',
		'excluded',
		'price_eyebrow',
		'price_points',
		'price_foot',
		'packing_heading',
		'packing_items',
		'map_heading',
		'map_note',
		'map_place',
		'faq_heading',
		'faq_lead',
	);
}

/**
 * The repeater fields sent to DeepL, and which of each row's parts.
 *
 * `timeline` names a day's own stop-by-stop repeater rather than a literal
 * part — see iflynepal_package_translate_fields() below, which is the only
 * part key handled that way.
 *
 * @since 1.0.0
 *
 * @return array<string,string[]>
 */
function iflynepal_package_translatable_card_fields() {
	return array(
		'itinerary_days'  => array( 'title', 'meta', 'description', 'summary', 'timeline' ),
		'itinerary_weeks' => array( 'title', 'meta', 'summary' ),
		'faq_items'       => array( 'q', 'a' ),
	);
}

/**
 * A Polylang language slug as DeepL's own language code.
 *
 * DeepL takes a plain code for every language it supports except a small
 * handful needing a regional variant — English and Portuguese as a *target*
 * (it always accepts the plain code as a *source*). Only English is
 * special-cased here because it is the only one this site currently
 * translates from; the filter lets a future language be added without
 * editing this function.
 *
 * @since 1.0.0
 *
 * @param string $slug      Polylang language slug, e.g. 'fr'.
 * @param bool   $is_target Whether this code is for `target_lang` (true) or `source_lang` (false).
 * @return string DeepL language code.
 */
function iflynepal_deepl_lang_code( $slug, $is_target ) {
	$code = strtoupper( (string) $slug );

	if ( $is_target && 'EN' === $code ) {
		$code = 'EN-GB';
	}

	/**
	 * Filters the DeepL language code used for a Polylang language.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code      DeepL language code.
	 * @param string $slug      Polylang language slug.
	 * @param bool   $is_target Whether this is the target language.
	 */
	return (string) apply_filters( 'iflynepal_deepl_lang_code', $code, $slug, $is_target );
}

/**
 * Marks the literal facts inside a string that DeepL must carry through
 * unchanged, rather than translate.
 *
 * Left to itself, DeepL treats a clock time or a currency amount as content
 * to localize rather than a fact to preserve — "4:00 PM" becomes "16h00" and
 * "USD 3,360" becomes "3 360 dollars américains", both grammatically correct
 * French and both wrong for a field where the number is the whole point.
 * Wrapping the literal in `<span translate="no">` — which DeepL's HTML
 * handling honours by passing the wrapped text through byte for byte — stops
 * this without stopping translation of the words around it; "From US$425"
 * still becomes "À partir de US$425", not just "US$425" on its own.
 *
 * @since 1.0.0
 *
 * @param string $text Source text.
 * @return string The same text with each literal wrapped.
 */
function iflynepal_deepl_protect_literals( $text ) {
	$pattern = '/'
		. '(?:USD|US\s?\$|NPR|Rs\.?|€|\$)\s?\d[\d,.]*'                                          // Currency before the amount: "USD 3,360", "US $425", "$425".
		. '|\d[\d,.]*\s?(?:USD|US\s?\$|NPR|Rs\.?|€|\$|%)'                                       // Currency or percent after: "3,360 USD", "20%".
		. '|\d{1,2}:\d{2}\s?(?:[AaPp]\.?[Mm]\.?)?(?:\s?[-–]\s?\d{1,2}:\d{2}\s?(?:[AaPp]\.?[Mm]\.?)?)?' // A clock time, or a "4:00 PM - 5:00 PM" range.
		. '|\d[\d,.]*'                                                                          // Any other bare number — an altitude, a group size, a day count.
		. '/u';

	return (string) preg_replace_callback(
		$pattern,
		function ( $match ) {
			return '<span translate="no">' . $match[0] . '</span>';
		},
		$text
	);
}

/**
 * Removes the wrapper iflynepal_deepl_protect_literals() added, once DeepL
 * has returned the translation.
 *
 * @since 1.0.0
 *
 * @param string $text Translated text, still carrying the wrapper spans.
 * @return string The same text with the wrapper removed and the literal left in place.
 */
function iflynepal_deepl_unprotect_literals( $text ) {
	return (string) preg_replace( '#<span translate="no">(.*?)</span>#su', '$1', $text );
}

/**
 * Removes French guillemets DeepL adds around a word it treats as a foreign
 * or coined term, when the source never had any quoting at all.
 *
 * DeepL's French output marks a term it has decided to leave untranslated —
 * often a brand-ish name like "Nightout" — by wrapping it in « » on its own
 * initiative. That is a genuine French convention for an unfamiliar loanword,
 * but it reads as an invented quotation mark on a package title or heading
 * that never had one, so it is removed whenever the source string carried no
 * quote mark of its own for it to be preserving.
 *
 * @since 1.0.0
 *
 * @param string $source     Original source text.
 * @param string $translated Translated text, already decoded and unprotected.
 * @return string Translated text, guillemets removed if the source had none.
 */
function iflynepal_deepl_strip_invented_quotes( $source, $translated ) {
	if ( false !== strpbrk( $source, '"\'«»“”' ) ) {
		return $translated;
	}

	if ( false === strpos( $translated, '«' ) && false === strpos( $translated, '»' ) ) {
		return $translated;
	}

	$translated = str_replace( array( '«', '»' ), '', $translated );
	// DeepL pads each guillemet with a non-breaking space on its inside;
	// removing just the mark would otherwise leave that as a doubled space.
	// Only plain spaces are touched — a 'lines' field's own newlines, which
	// this same string might carry, are left exactly as they were.
	$translated = str_replace( "\xC2\xA0", ' ', $translated );
	$translated = preg_replace( '/ {2,}/', ' ', $translated );

	return trim( $translated );
}

/**
 * Translates a batch of strings with DeepL in one request.
 *
 * One request for the whole package rather than one per field: a detailed
 * itinerary can easily carry 30-plus translatable strings (days, stops,
 * FAQs), and 30 round trips to translate one package is both slow and thirty
 * times the chance of one of them timing out.
 *
 * @since 1.0.0
 *
 * @param string[] $texts       Source strings, in order.
 * @param string   $target_lang DeepL target language code.
 * @param string   $source_lang DeepL source language code.
 * @return string[] Translated strings in the same order. An entry is '' where
 *                   its source was empty or the request failed — the caller
 *                   treats '' as "leave this field as it was".
 */
function iflynepal_deepl_translate_batch( array $texts, $target_lang, $source_lang ) {
	$api_key = iflynepal_deepl_api_key();

	if ( '' === $api_key || empty( $texts ) ) {
		return array_fill( 0, count( $texts ), '' );
	}

	/*
	 * A free-tier key always ends in ":fx" and only works against the free
	 * endpoint; a paid key only works against the standard one. Neither
	 * endpoint accepts the other kind of key, so the key itself decides which
	 * one to call rather than a setting an editor would have to know to flip.
	 */
	$endpoint = ( ':fx' === substr( $api_key, -3 ) )
		? 'https://api-free.deepl.com/v2/translate'
		: 'https://api.deepl.com/v2/translate';

	$body = array();

	foreach ( $texts as $text ) {
		// Built by hand rather than passed as an array body: DeepL wants a
		// repeated "text=" parameter per string, not the indexed "text[0]="
		// form wp_remote_post() would build from a PHP array under one key.
		$body[] = 'text=' . rawurlencode( iflynepal_deepl_protect_literals( (string) $text ) );
	}

	$body[] = 'target_lang=' . rawurlencode( $target_lang );
	$body[] = 'source_lang=' . rawurlencode( $source_lang );
	// Recognizes the handful of HTML tags this plugin's rich fields allow
	// (<em>, <strong>, <br>, <ul>/<li>, …) and translates around them instead
	// of through them; a plain-text field with no tags is unaffected.
	$body[] = 'tag_handling=html';

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'DeepL-Auth-Key ' . $api_key,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => implode( '&', $body ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return array_fill( 0, count( $texts ), '' );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( empty( $data['translations'] ) || ! is_array( $data['translations'] ) ) {
		return array_fill( 0, count( $texts ), '' );
	}

	$out = array();

	foreach ( $texts as $index => $text ) {
		$translated = isset( $data['translations'][ $index ]['text'] ) ? (string) $data['translations'][ $index ]['text'] : '';

		/*
		 * With tag_handling=html, DeepL entity-encodes punctuation it treats
		 * as HTML-sensitive — an apostrophe comes back as "&#x27;" — on the
		 * assumption its output is dropped straight into a page's markup.
		 * This plugin stores field values as plain text (or, for the few rich
		 * fields, plain text plus a handful of literal tags), never
		 * pre-escaped, so left alone this would double-escape the moment a
		 * template ran esc_html() over it: "l&amp;#x27;itinéraire".
		 */
		if ( '' === $translated ) {
			$out[] = '';
			continue;
		}

		$translated = iflynepal_deepl_unprotect_literals( html_entity_decode( $translated, ENT_QUOTES, 'UTF-8' ) );
		$out[]      = iflynepal_deepl_strip_invented_quotes( (string) $text, $translated );
	}

	return $out;
}

/**
 * Translates one package's prose fields into another, already-created
 * package.
 *
 * Collects every non-empty translatable string from `$from_id` first,
 * translates all of them in as few DeepL requests as it takes (50 strings a
 * request, DeepL's own recommended ceiling), and only then writes anything —
 * a package is never left half in one language and half in another because a
 * request midway through failed.
 *
 * @since 1.0.0
 *
 * @param int    $from_id     Source package.
 * @param int    $to_id       The new translation, already created.
 * @param string $source_lang Polylang language slug of the source.
 * @param string $target_lang Polylang language slug of the target.
 * @return void
 */
function iflynepal_package_translate_fields( $from_id, $to_id, $source_lang, $target_lang ) {
	if ( '' === iflynepal_deepl_api_key() ) {
		return;
	}

	$all_fields = iflynepal_package_detail_fields();

	$texts = array();
	$jobs  = array();

	/*
	 * The post title itself, not a schema field: Polylang deliberately leaves
	 * it for a translator to write, the one thing it never copies from the
	 * source post. Translating it here means the new package opens with a
	 * real French title already in place instead of the "Add title"
	 * placeholder — still freely editable, same as everything else this file
	 * fills in.
	 */
	$from_post = get_post( $from_id );

	if ( $from_post instanceof WP_Post && '' !== $from_post->post_title ) {
		$texts[] = $from_post->post_title;
		$jobs[]  = array(
			'apply' => function ( $translated ) use ( $to_id ) {
				$clean = sanitize_text_field( $translated );

				wp_update_post(
					array(
						'ID'         => $to_id,
						'post_title' => $clean,
					)
				);

				/*
				 * wp_update_post() writes the database row, but this runs
				 * while post-new.php is still building the very same request
				 * that will render the "Add title" field from its own
				 * already-fetched $post object — a PHP object reference the
				 * write above never touches. Left alone, the title shows
				 * blank on first load and only appears after the page is
				 * reloaded. Setting it directly on that object is what makes
				 * the field show the translated title immediately.
				 */
				global $post;

				if ( $post instanceof WP_Post && (int) $post->ID === (int) $to_id ) {
					$post->post_title = $clean;
				}
			},
		);
	}

	foreach ( iflynepal_package_translatable_fields() as $key ) {
		$value = iflynepal_package_field( $from_id, $key );

		if ( '' === $value ) {
			continue;
		}

		$type = isset( $all_fields[ $key ]['type'] ) ? $all_fields[ $key ]['type'] : 'text';

		$texts[] = $value;
		$jobs[]  = array(
			'apply' => function ( $translated ) use ( $to_id, $key, $type ) {
				update_post_meta( $to_id, iflynepal_package_meta_key( $key ), iflynepal_package_sanitize_value( $translated, $type ) );
			},
		);
	}

	// Rewritten as whole rows once every string is translated, keyed by the
	// repeater's own schema key — see the note on the write-back loop below.
	$card_snapshots = array();

	foreach ( iflynepal_package_translatable_card_fields() as $card_key => $parts ) {
		$rows       = iflynepal_package_cards( $from_id, $card_key );
		$part_types = isset( $all_fields[ $card_key ]['parts'] ) ? $all_fields[ $card_key ]['parts'] : array();

		if ( empty( $rows ) ) {
			continue;
		}

		$card_snapshots[ $card_key ] = $rows;

		foreach ( $rows as $row_index => $row ) {
			foreach ( $parts as $part_key ) {
				if ( 'timeline' === $part_key ) {
					if ( empty( $row['timeline'] ) || ! is_array( $row['timeline'] ) ) {
						continue;
					}

					foreach ( $row['timeline'] as $stop_index => $stop ) {
						$text = isset( $stop['text'] ) ? (string) $stop['text'] : '';

						if ( '' === $text ) {
							continue;
						}

						$texts[] = $text;
						$jobs[]  = array(
							// The timeline's stop text is always a plain string — see
							// render_timeline_row() in the details box, which draws it
							// as a plain <input type="text">, never rich text.
							'apply' => function ( $translated ) use ( &$card_snapshots, $card_key, $row_index, $stop_index ) {
								$card_snapshots[ $card_key ][ $row_index ]['timeline'][ $stop_index ]['text'] = iflynepal_archive_sanitize_value( $translated, 'text' );
							},
						);
					}

					continue;
				}

				$text = isset( $row[ $part_key ] ) ? (string) $row[ $part_key ] : '';

				if ( '' === $text ) {
					continue;
				}

				$part_type = isset( $part_types[ $part_key ]['type'] ) ? $part_types[ $part_key ]['type'] : 'text';

				$texts[] = $text;
				$jobs[]  = array(
					'apply' => function ( $translated ) use ( &$card_snapshots, $card_key, $row_index, $part_key, $part_type ) {
						$card_snapshots[ $card_key ][ $row_index ][ $part_key ] = iflynepal_archive_sanitize_value( $translated, $part_type );
					},
				);
			}
		}
	}

	if ( empty( $texts ) ) {
		return;
	}

	$target_code = iflynepal_deepl_lang_code( $target_lang, true );
	$source_code = iflynepal_deepl_lang_code( $source_lang, false );

	foreach ( array_chunk( array_keys( $texts ), 50 ) as $chunk_indexes ) {
		$chunk_texts = array();

		foreach ( $chunk_indexes as $index ) {
			$chunk_texts[] = $texts[ $index ];
		}

		$translated_chunk = iflynepal_deepl_translate_batch( $chunk_texts, $target_code, $source_code );

		foreach ( $chunk_indexes as $offset => $index ) {
			$translated = isset( $translated_chunk[ $offset ] ) ? $translated_chunk[ $offset ] : '';

			if ( '' === $translated ) {
				continue; // Failed or empty: leave this one field as it was.
			}

			call_user_func( $jobs[ $index ]['apply'], $translated );
		}
	}

	// One update_post_meta() per repeater, not per row or per part: every
	// string a repeater carries was already translated above, so this is the
	// single point the whole, now-translated structure is written back.
	foreach ( $card_snapshots as $card_key => $rows ) {
		update_post_meta( $to_id, iflynepal_package_meta_key( $card_key ), $rows );
	}
}

/**
 * Hooks the translation step into the same "new translation" request the
 * media, package-type and boilerplate pre-fills already use.
 *
 * @since 1.0.0
 *
 * @param bool $is_block_editor Whether the post can be edited with the block editor.
 * @return bool Unmodified.
 */
function iflynepal_package_translate_content( $is_block_editor ) {
	global $post;
	static $done = array();

	if ( empty( $post ) || '' === iflynepal_deepl_api_key() || ! function_exists( 'PLL' ) || ! PLL() instanceof PLL_Admin_Base ) {
		return $is_block_editor;
	}

	$context_data = PLL()->links->get_data_from_new_post_translation_request();

	if ( empty( $context_data ) || ! empty( $done[ $context_data['from_post']->ID ] ) ) {
		return $is_block_editor;
	}

	if ( IFLYNEPAL_PACKAGE_POST_TYPE !== $context_data['from_post']->post_type ) {
		return $is_block_editor;
	}

	$done[ $context_data['from_post']->ID ] = true;

	$source_lang = PLL()->model->post->get_language( $context_data['from_post']->ID );

	if ( ! $source_lang || ! $context_data['new_lang'] instanceof PLL_Language ) {
		return $is_block_editor;
	}

	iflynepal_package_translate_fields(
		$context_data['from_post']->ID,
		$post->ID,
		$source_lang->slug,
		$context_data['new_lang']->slug
	);

	return $is_block_editor;
}
add_filter( 'use_block_editor_for_post', 'iflynepal_package_translate_content', 5002 );
