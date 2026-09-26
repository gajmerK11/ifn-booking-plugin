<?php
/**
 * Nested package permalinks.
 *
 * Target URL shape, confirmed 10 September 2026:
 *
 *     /retreat-nepal/                                 top-level type archive
 *     /retreat-nepal/monastery-retreats/              child type archive
 *     /retreat-nepal/monastery-retreats/3-days-...     single package
 *
 * The first segment is not a fixed base owned by this plugin — it is the slug
 * of the package's top-level Package Type term. So the root of every catalogue
 * URL is editable from Package Types in the admin, and adding a new kind of
 * package adds a new root without a code change.
 *
 * Why this file owns every rule rather than letting WordPress generate them:
 * the URLs start at the site root, so the only base WordPress could be given is
 * an empty one, and an empty base produces a rule that swallows any single path
 * segment — /about, /team, /contact-us would all be tried as package type
 * archives before they were tried as pages. Building the rules from the term
 * slugs that actually exist means a rule only ever matches a real catalogue
 * path, and everything else falls through to core untouched.
 *
 * The rules are built on the `rewrite_rules_array` filter, which runs when the
 * rule set is generated and saved — on a flush — and not on every request. The
 * get_terms() call is therefore paid once per flush, not once per page load.
 * Anything that changes a term slug or the term tree has to flush, which is
 * what the term hooks at the bottom of this file are for.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post meta key holding the package's designated primary type.
 *
 * A package can sit in more than one type, but it has exactly one URL, so one
 * of its terms has to be the one the URL is built from. This key stores that
 * choice as a term ID. The editor UI for setting it ships with the package meta
 * boxes; until then, and for any package where it is unset or stale,
 * iflynepal_package_primary_type() falls back to a deterministic pick so that a
 * permalink never depends on the order get_the_terms() happened to return.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_PRIMARY_TYPE_META = '_iflynepal_primary_package_type';

/**
 * The full slug path of a package type term, ancestors first.
 *
 * "Ayurvedic Retreats" nested under "Retreat" returns
 * `retreat-nepal/ayurvedic-retreats`. A top-level term returns its own slug.
 *
 * @since 1.0.0
 *
 * @param int|WP_Term $term Term or term ID.
 * @return string Slug path without leading or trailing slashes, empty if the term is gone.
 */
function iflynepal_package_type_path( $term ) {
	$term = get_term( $term, IFLYNEPAL_PACKAGE_TAXONOMY );

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	$slugs = array( $term->slug );

	foreach ( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) as $ancestor_id ) {
		$ancestor = get_term( $ancestor_id, IFLYNEPAL_PACKAGE_TAXONOMY );

		if ( $ancestor && ! is_wp_error( $ancestor ) ) {
			array_unshift( $slugs, $ancestor->slug );
		}
	}

	return implode( '/', $slugs );
}

/**
 * The package type a package's URL is built from.
 *
 * The stored primary term wins, but only if the package is still in it — a term
 * that was removed from the package, or deleted outright, would otherwise leave
 * the permalink pointing at a path that no longer resolves.
 *
 * The fallback is the deepest term the package holds, because a package filed
 * under both "Retreat" and "Retreat > Monastery Retreats" belongs at the more
 * specific of the two, with the lowest term ID breaking a tie. Deterministic is
 * the point: get_the_terms() order is not guaranteed and a permalink that moves
 * on its own breaks every link anyone has saved.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package post ID.
 * @return WP_Term|null The term, or null when the package has no type at all.
 */
function iflynepal_package_primary_type( $post_id ) {
	$terms = get_the_terms( $post_id, IFLYNEPAL_PACKAGE_TAXONOMY );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	$designated = (int) get_post_meta( $post_id, IFLYNEPAL_PACKAGE_PRIMARY_TYPE_META, true );

	if ( $designated ) {
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === $designated ) {
				return $term;
			}
		}
	}

	$depths = array();

	foreach ( $terms as $term ) {
		$depths[ $term->term_id ] = count( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) );
	}

	usort(
		$terms,
		static function ( $a, $b ) use ( $depths ) {
			if ( $depths[ $a->term_id ] !== $depths[ $b->term_id ] ) {
				return $depths[ $b->term_id ] <=> $depths[ $a->term_id ];
			}

			return $a->term_id <=> $b->term_id;
		}
	);

	return $terms[0];
}

/**
 * The URL path segment Polylang puts in front of a language's links.
 *
 * "fr/" for French, "" for the default language when Polylang is set to hide
 * it from the URL — the same two rules PLL_Links_Directory::add_language_to_link()
 * applies to every link it builds. Read straight from Polylang's own options
 * rather than asking it to build a URL and parsing the prefix back out of it,
 * which would need a home_url() round trip for every term on every flush.
 *
 * @since 1.0.0
 *
 * @param string $lang Language slug, empty when Polylang is inactive or the
 *                      term has none.
 * @return string Prefix, with a trailing slash, or '' for none.
 */
function iflynepal_package_language_url_prefix( $lang ) {
	if ( '' === $lang || ! function_exists( 'PLL' ) || ! PLL() ) {
		return '';
	}

	$options = PLL()->options;

	if ( $lang === $options['default_lang'] && $options['hide_default'] ) {
		return '';
	}

	$base = $options['rewrite'] ? '' : 'language/';

	return $base . $lang . '/';
}

/**
 * The URL slug for the flat /packages/ fallback archive, per language.
 *
 * A package with no type yet falls back to this path rather than a nested
 * one, so it needs its own translated slug the same way a package type term
 * does — a French visitor should never land on an English word in the URL.
 *
 * IFLYNEPAL_PACKAGE_ARCHIVE_SLUG stays the slug for the default language and
 * for a site running without Polylang; every other language is looked up
 * here, filterable rather than hardcoded so a translated slug is a config
 * change, not a code change.
 *
 * @since 1.0.0
 *
 * @param string $lang Language slug, empty for the current front-end language
 *                      or when Polylang is inactive.
 * @return string Slug, without leading or trailing slashes.
 */
function iflynepal_package_archive_slug( $lang = '' ) {
	if ( '' === $lang && function_exists( 'pll_current_language' ) ) {
		$lang = (string) pll_current_language();
	}

	if ( '' === $lang || ! function_exists( 'PLL' ) || ! PLL() || $lang === PLL()->options['default_lang'] ) {
		return IFLYNEPAL_PACKAGE_ARCHIVE_SLUG;
	}

	/**
	 * Filters the per-language slugs for the flat /packages/ fallback archive.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $slugs Language slug => URL slug.
	 */
	$slugs = (array) apply_filters(
		'iflynepal_package_archive_slug_translations',
		array( 'fr' => 'forfaits' )
	);

	return isset( $slugs[ $lang ] ) ? $slugs[ $lang ] : IFLYNEPAL_PACKAGE_ARCHIVE_SLUG;
}

/**
 * The plugin's rewrite rules, in match order.
 *
 * Order is the whole design. Every type archive path is emitted before any
 * single-package path, because a package rule is a wildcard over one segment
 * and a child term archive is a literal segment in the same position:
 * `retreat-nepal/([^/]+)/?$` would otherwise swallow
 * /retreat-nepal/monastery-retreats/ and serve it as a missing package.
 * WordPress matches rules top to bottom and takes the first hit, with no check
 * that what it matched exists, so precedence has to be arranged here.
 *
 * The flat /packages/... rules at the end are the fallback for a package that
 * has been given no type yet. Without them such a package would have no
 * resolvable URL at all, since this plugin registers the post type with
 * `rewrite => false` and core generates nothing.
 *
 * @since 1.0.0
 *
 * @return string[] Rewrite rules, regex keyed, query strings as values.
 */
function iflynepal_package_rewrite_rules() {
	$archives = array();
	$singles  = array();

	$terms = get_terms(
		array(
			'taxonomy'   => IFLYNEPAL_PACKAGE_TAXONOMY,
			'hide_empty' => false,
			/*
			 * Every language's terms, not just whichever one was active when
			 * the rules last flushed. Polylang silently filters get_terms() to
			 * the current language otherwise, so a French term slug — e.g.
			 * "randonnee" — would never get a matching rule and every French
			 * package URL under it would 404.
			 */
			'lang'       => '',
		)
	);

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$path = iflynepal_package_type_path( $term );

			if ( '' === $path ) {
				continue;
			}

			/*
			 * The language's own URL prefix ("fr/", or "" for the default
			 * language when it is hidden) plus a `lang=` query var, matching
			 * what Polylang stitches onto every rule it builds itself.
			 *
			 * Needed because these rules are added on the 'rewrite_rules_array'
			 * filter, which is the one place Polylang's own auto-prefixing
			 * (src/links-directory.php, PLL_Links_Directory::rewrite_rules())
			 * explicitly skips — it only prefixes rules whose query string
			 * already reads `post_type=…`, and this plugin's rules use the
			 * taxonomy/post type's own query var instead. Without this, a
			 * French request for /fr/randonnee/… never matches any rule at
			 * all and falls straight through to a 404.
			 */
			$lang       = function_exists( 'pll_get_term_language' ) ? pll_get_term_language( $term->term_id ) : '';
			$prefix     = iflynepal_package_language_url_prefix( $lang );
			$lang_query = '' !== $lang ? 'lang=' . $lang . '&' : '';

			$path = preg_quote( $prefix . $path, '#' );

			$archives[ $path . '/page/([0-9]{1,})/?$' ] = 'index.php?' . $lang_query . IFLYNEPAL_PACKAGE_TAXONOMY . '=' . $term->slug . '&paged=$matches[1]';
			$archives[ $path . '/?$' ]                  = 'index.php?' . $lang_query . IFLYNEPAL_PACKAGE_TAXONOMY . '=' . $term->slug;
			$singles[ $path . '/([^/]+)/?$' ]           = 'index.php?' . $lang_query . IFLYNEPAL_PACKAGE_POST_TYPE . '=$matches[1]';
		}
	}

	/*
	 * One bare, unprefixed rule per translated slug — the default language's
	 * own slug, plus every other language's translated one ("forfaits" for
	 * French). No language prefix or `lang=` query var is added by hand here:
	 * every one of these rules carries `post_type=…` in its query, which is
	 * exactly the pattern PLL_Links_Directory::rewrite_rules() (hooked on this
	 * same 'rewrite_rules_array' filter, after this one — see the class
	 * comment above) scans for and duplicates itself, prefixed with whichever
	 * language matches, `lang=$matches[1]` filled in. Adding a prefix here too
	 * would make it duplicate the prefix on top of Polylang's own.
	 */
	$fallback_slugs = array( IFLYNEPAL_PACKAGE_ARCHIVE_SLUG );

	if ( function_exists( 'pll_languages_list' ) ) {
		foreach ( (array) pll_languages_list() as $fallback_lang ) {
			$fallback_slugs[] = iflynepal_package_archive_slug( $fallback_lang );
		}
	}

	foreach ( array_unique( $fallback_slugs ) as $slug ) {
		$fallback = preg_quote( $slug, '#' );

		$singles[ $fallback . '/page/([0-9]{1,})/?$' ] = 'index.php?post_type=' . IFLYNEPAL_PACKAGE_POST_TYPE . '&paged=$matches[1]';
		$singles[ $fallback . '/?$' ]                  = 'index.php?post_type=' . IFLYNEPAL_PACKAGE_POST_TYPE;
		$singles[ $fallback . '/([^/]+)/?$' ]          = 'index.php?' . IFLYNEPAL_PACKAGE_POST_TYPE . '=$matches[1]';
	}

	return array_merge( $archives, $singles );
}

/**
 * Prepends the plugin's rules to the generated rule set.
 *
 * Prepended rather than appended so a catalogue path is matched before core's
 * catch-all page rule gets to it; core's own rules still handle everything the
 * plugin's rules do not match, in their original order.
 *
 * @since 1.0.0
 *
 * @param string[] $rules The generated rewrite rules.
 * @return string[] Filtered rules.
 */
function iflynepal_package_filter_rewrite_rules( $rules ) {
	return array_merge( iflynepal_package_rewrite_rules(), $rules );
}
add_filter( 'rewrite_rules_array', 'iflynepal_package_filter_rewrite_rules' );

/**
 * Builds the nested permalink for a single package.
 *
 * Drafts and pending posts are left alone: WordPress has not assigned them a
 * final slug, and the admin's own "sample permalink" preview is built from a
 * placeholder that this must not rewrite into something that looks real.
 *
 * @since 1.0.0
 *
 * @param string  $post_link The post's permalink.
 * @param WP_Post $post      The post.
 * @return string Permalink.
 */
function iflynepal_package_post_type_link( $post_link, $post ) {
	if ( ! $post instanceof WP_Post || IFLYNEPAL_PACKAGE_POST_TYPE !== $post->post_type ) {
		return $post_link;
	}

	if ( ! get_option( 'permalink_structure' ) ) {
		return $post_link;
	}

	if ( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft', 'future' ), true ) ) {
		return $post_link;
	}

	$fallback_slug = iflynepal_package_archive_slug(
		function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : ''
	);

	$term = iflynepal_package_primary_type( $post->ID );
	$path = $term ? iflynepal_package_type_path( $term ) : $fallback_slug;

	if ( '' === $path ) {
		$path = $fallback_slug;
	}

	return home_url( user_trailingslashit( $path . '/' . $post->post_name ) );
}
add_filter( 'post_type_link', 'iflynepal_package_post_type_link', 10, 2 );

/**
 * Builds the nested permalink for a package type term archive.
 *
 * The taxonomy is registered with `rewrite => false`, so without this
 * get_term_link() would hand out an unpretty ?iflynepal_package_type=… URL that
 * does not match what the rules above actually serve.
 *
 * @since 1.0.0
 *
 * @param string  $termlink Term link.
 * @param WP_Term $term     Term.
 * @param string  $taxonomy Taxonomy slug.
 * @return string Term link.
 */
function iflynepal_package_type_term_link( $termlink, $term, $taxonomy ) {
	if ( IFLYNEPAL_PACKAGE_TAXONOMY !== $taxonomy || ! get_option( 'permalink_structure' ) ) {
		return $termlink;
	}

	$path = iflynepal_package_type_path( $term );

	return '' === $path ? $termlink : home_url( user_trailingslashit( $path ) );
}
add_filter( 'term_link', 'iflynepal_package_type_term_link', 10, 3 );

/**
 * Points the post type archive link at the fallback /packages/ path.
 *
 * Same reason as the term link: core builds this one from the rewrite base it
 * generated, and this plugin generated it instead.
 *
 * The slug itself is picked for the current front-end language — Polylang's
 * own 'post_type_archive_link' filter (priority 20, after this one) adds the
 * language prefix on top; it never changes the slug text, so a translated
 * slug like "forfaits" has to be chosen here or it never appears at all.
 *
 * @since 1.0.0
 *
 * @param string $link      Archive link.
 * @param string $post_type Post type slug.
 * @return string Archive link.
 */
function iflynepal_package_archive_link( $link, $post_type ) {
	if ( IFLYNEPAL_PACKAGE_POST_TYPE !== $post_type || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}

	return home_url( user_trailingslashit( iflynepal_package_archive_slug() ) );
}
add_filter( 'post_type_archive_link', 'iflynepal_package_archive_link', 10, 2 );

/**
 * Rebuilds the rule set whenever the package type tree changes.
 *
 * The rules are derived from term slugs and term parentage, so renaming a
 * slug, re-parenting a term, adding one or deleting one all invalidate them.
 * These are rare admin actions, which is the only reason flushing here is
 * acceptable — flushing is expensive and must never be hooked to a front-end
 * request.
 *
 * @since 1.0.0
 *
 * @param int    $term_id  Term ID. Unused.
 * @param int    $tt_id    Term taxonomy ID. Unused.
 * @param string $taxonomy Taxonomy slug.
 * @return void
 */
function iflynepal_package_flush_on_term_change( $term_id, $tt_id, $taxonomy ) {
	if ( IFLYNEPAL_PACKAGE_TAXONOMY === $taxonomy ) {
		flush_rewrite_rules();
	}
}
add_action( 'created_term', 'iflynepal_package_flush_on_term_change', 10, 3 );
add_action( 'edited_term', 'iflynepal_package_flush_on_term_change', 10, 3 );
add_action( 'delete_term', 'iflynepal_package_flush_on_term_change', 10, 3 );
