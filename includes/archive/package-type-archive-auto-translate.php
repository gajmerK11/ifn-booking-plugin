<?php
/**
 * Drafts a package type archive's copy in the new language the moment a term
 * translation is created, using DeepL.
 *
 * A Package Type is a taxonomy term, not a post, so it rides a different hook
 * than includes/package/package-auto-translate.php: Polylang's own
 * `create_term`, which it uses itself (at priority 900) to set the new term's
 * language and translation group. This runs after it, at 950, so the new
 * term's language is already in place by the time it reads it.
 *
 * What is copied, and what is not — the client's own instruction:
 *
 *  - Every image (the hero and closing backgrounds, a reason card's picture,
 *    a departure card's picture) is carried over unchanged. A photograph is
 *    the same photograph regardless of language.
 *  - A checkbox ("Highlight this plan") is carried over unchanged. It is a
 *    flag, not a sentence — there is nothing in it for DeepL to translate.
 *  - Every prose field — headings, leads, card titles and text, the plan
 *    names and feature lists, the comparison table's headings and cells, the
 *    FAQ — is translated with DeepL, the same client this theme already uses
 *    for Posts, Articles, News and Testimonials.
 *  - Every link field (a button's URL, a departure card's Reserve link) is
 *    left empty on the new term. A French URL is not an English URL
 *    translated; it is a different, real destination, and the client asked
 *    for these left for an editor to fill in rather than guessed at or
 *    copied wrong.
 *
 * Silent on any failure, the same rule package-auto-translate.php follows: no
 * configured key, an expired or over-quota key, a timeout, a malformed
 * response — every one of them leaves the new term exactly as blank on the
 * affected fields as it was before this file existed.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Copies and translates one package type's archive content into another,
 * already-created term.
 *
 * Collects every string worth sending to DeepL first, translates all of them
 * in as few requests as it takes (50 strings a request, DeepL's own
 * recommended ceiling), and only then writes anything — a term is never left
 * half in one language and half in another because a request midway through
 * failed. Images and checkboxes are copied immediately since there is
 * nothing to wait on a translation for.
 *
 * @since 1.0.0
 *
 * @param int    $from_id     Source term.
 * @param int    $to_id       The new translation, already created.
 * @param string $source_lang Polylang language slug of the source.
 * @param string $target_lang Polylang language slug of the target.
 * @return void
 */
function iflynepal_package_type_translate_archive( $from_id, $to_id, $source_lang, $target_lang ) {
	if ( '' === iflynepal_deepl_api_key() ) {
		return;
	}

	/*
	 * Scoped to what the new term can actually render — a category gets the
	 * hero and the grid, a root type gets everything. Reading the full schema
	 * here would translate copy the new term has nowhere to show, and would
	 * write term meta a re-parenting was supposed to make unreachable.
	 */
	$fields = iflynepal_package_type_archive_fields_for_term( $to_id );

	$texts = array();
	$jobs  = array();

	// Rewritten as whole rows or a whole table once every string in it is
	// translated, keyed by the field's own schema key — see the write-back
	// loop below.
	$snapshots = array();

	foreach ( $fields as $key => $field ) {
		$meta_key = iflynepal_archive_meta_key( $key );
		$type     = $field['type'];

		switch ( $type ) {
			// A link is a real destination, not a sentence: left for an
			// editor to fill in, per the client's own instruction.
			case 'url':
				break;

			case 'image':
				$value = (int) get_term_meta( $from_id, $meta_key, true );

				if ( $value ) {
					update_term_meta( $to_id, $meta_key, (string) $value );
				}

				break;

			case 'checkbox':
				if ( '1' === get_term_meta( $from_id, $meta_key, true ) ) {
					update_term_meta( $to_id, $meta_key, '1' );
				}

				break;

			case 'cards':
				$rows = iflynepal_archive_cards( $from_id, $key );

				if ( empty( $rows ) ) {
					break;
				}

				$snapshots[ $key ] = $rows;
				$parts             = iflynepal_archive_card_parts( $field );

				foreach ( $rows as $row_index => $row ) {
					foreach ( $parts as $part_key => $part ) {
						if ( 'image' === $part['type'] ) {
							// Already in the snapshot verbatim from
							// iflynepal_archive_cards() above; nothing to queue.
							continue;
						}

						if ( 'url' === $part['type'] ) {
							$snapshots[ $key ][ $row_index ][ $part_key ] = '';

							continue;
						}

						$text = isset( $row[ $part_key ] ) ? (string) $row[ $part_key ] : '';

						if ( '' === $text ) {
							continue;
						}

						$part_type = $part['type'];

						$texts[] = $text;
						$jobs[]  = array(
							'apply' => function ( $translated ) use ( &$snapshots, $key, $row_index, $part_key, $part_type ) {
								$snapshots[ $key ][ $row_index ][ $part_key ] = iflynepal_archive_sanitize_value( $translated, $part_type );
							},
						);
					}
				}

				break;

			case 'table':
				$table = iflynepal_archive_table( $from_id, $key );

				if ( empty( $table['columns'] ) && empty( $table['rows'] ) ) {
					break;
				}

				$snapshots[ $key ] = $table;

				foreach ( $table['columns'] as $column_index => $column ) {
					foreach ( array( 'label', 'note' ) as $part ) {
						$text = isset( $column[ $part ] ) ? (string) $column[ $part ] : '';

						if ( '' === $text ) {
							continue;
						}

						$texts[] = $text;
						$jobs[]  = array(
							'apply' => function ( $translated ) use ( &$snapshots, $key, $column_index, $part ) {
								$snapshots[ $key ]['columns'][ $column_index ][ $part ] = sanitize_text_field( $translated );
							},
						);
					}
				}

				foreach ( $table['rows'] as $row_index => $row ) {
					foreach ( (array) $row as $cell_index => $cell ) {
						$text = (string) $cell;

						if ( '' === $text ) {
							continue;
						}

						$texts[] = $text;
						$jobs[]  = array(
							'apply' => function ( $translated ) use ( &$snapshots, $key, $row_index, $cell_index ) {
								$snapshots[ $key ]['rows'][ $row_index ][ $cell_index ] = sanitize_text_field( $translated );
							},
						);
					}
				}

				break;

			// text, rich, textarea, lines: plain prose, sent to DeepL as-is.
			default:
				$text = (string) get_term_meta( $from_id, $meta_key, true );

				if ( '' === $text ) {
					break;
				}

				$texts[] = $text;
				$jobs[]  = array(
					'apply' => function ( $translated ) use ( $to_id, $meta_key, $type ) {
						update_term_meta( $to_id, $meta_key, iflynepal_archive_sanitize_value( $translated, $type ) );
					},
				);

				break;
		}
	}

	if ( ! empty( $texts ) ) {
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
	}

	// One update_term_meta() per repeater or table, not per row or per cell:
	// every string either one carries was already translated above (its
	// images and links were written into the snapshot directly, as soon as
	// it was taken), so this is the single point the whole, now-complete
	// structure is written back.
	foreach ( $snapshots as $key => $value ) {
		update_term_meta( $to_id, iflynepal_archive_meta_key( $key ), $value );
	}
}

/**
 * Hooks the translation step into the "+" translation flow.
 *
 * Polylang prints a hidden `from_tag` field on the Add form whenever it was
 * reached by clicking a language's "+" next to an existing term (see
 * PLL_Admin_Filters_Term::add_term_form()), and reads it back nowhere itself
 * — it exists for exactly this kind of listener. A term created any other
 * way (typed into the Add form directly, imported, created by another
 * plugin) posts no such field and is left alone, same as before this file
 * existed.
 *
 * Priority 950: Polylang's own `save_term()` runs at 900 on this same hook
 * and is what sets the new term's language and links it to its source as a
 * translation, so this has to run after it to read either one back.
 *
 * @since 1.0.0
 *
 * @param int    $term_id  The new term.
 * @param int    $tt_id    Term taxonomy ID. Unused.
 * @param string $taxonomy Taxonomy the term was created in.
 * @return void
 */
function iflynepal_package_type_translate_new_term( $term_id, $tt_id, $taxonomy ) {
	static $done = array();

	if ( IFLYNEPAL_PACKAGE_TAXONOMY !== $taxonomy || '' === iflynepal_deepl_api_key() ) {
		return;
	}

	if ( ! isset( $_POST['from_tag'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only branch guard; Polylang's own nonce already gates the request that reaches create_term.
		return;
	}

	$from_id = (int) $_POST['from_tag']; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Same as above.

	if ( ! $from_id || isset( $done[ $from_id ] ) || ! function_exists( 'pll_get_term_language' ) ) {
		return;
	}

	$source_lang = pll_get_term_language( $from_id );
	$target_lang = pll_get_term_language( $term_id );

	if ( ! $source_lang || ! $target_lang || $source_lang === $target_lang ) {
		return;
	}

	$done[ $from_id ] = true;

	iflynepal_package_type_translate_archive( $from_id, $term_id, $source_lang, $target_lang );
}
add_action( 'create_term', 'iflynepal_package_type_translate_new_term', 950, 3 );
