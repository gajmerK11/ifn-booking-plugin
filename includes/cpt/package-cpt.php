<?php
/**
 * Packages post type and the Package Type taxonomy.
 *
 * One package post per sellable itinerary — a retreat, a trek, a tour, a
 * volunteering placement or a workshop. The kind a package is, is a term in the
 * hierarchical package type taxonomy rather than a post type of its own, so a
 * single query, a single template set and a single admin screen cover the whole
 * catalogue, and a new kind of package is a term an editor adds rather than
 * code (developer-confirmed, 9 September 2026).
 *
 * Both live in the plugin, not the theme: the catalogue is the business's
 * content and has to survive a theme switch. This is the one place this plugin
 * deliberately diverges from the cloudcolleague-integrations reference, which
 * owns no post types at all.
 *
 * Neither type generates rewrite rules of its own — both are registered with
 * `rewrite => false`. The catalogue's URLs start at the site root and are built
 * from package type slugs, which is not a shape core's permastructs can express
 * safely; includes/rewrites/package-rewrites.php owns every rule and the
 * matching link filters.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The post type's key.
 *
 * Prefixed rather than a bare `package`, which is generic enough that another
 * plugin may well claim it; a collision would mean two plugins registering the
 * same type and one of them losing its posts to invisibility. WordPress caps a
 * post type key at 20 characters, of which this uses 17.
 *
 * The key is internal — it never appears in a URL. The public slug is
 * IFLYNEPAL_PACKAGE_ARCHIVE_SLUG below.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_POST_TYPE = 'iflynepal_package';

/**
 * The taxonomy's key. Prefixed for the same reason as the post type.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_TAXONOMY = 'iflynepal_package_type';

/**
 * URL base for the whole-catalogue archive, and the fallback base for a package
 * that has not been given a package type yet.
 *
 * It is NOT the base of a normal package URL. A package's URL is built from its
 * package type's slug path — /retreat-nepal/monastery-retreats/{package} — so
 * the roots of the catalogue are term slugs an editor controls, not a constant.
 * See includes/rewrites/package-rewrites.php.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_PACKAGE_ARCHIVE_SLUG = 'packages';

/**
 * The package types the catalogue ships with.
 *
 * Seeded once on activation (see iflynepal_booking_seed_package_types()), not
 * on every load: they are a starting point for editors, not a fixed list. An
 * editor is free to rename, re-slug, nest or delete any of them afterwards and
 * nothing here will put them back.
 *
 * @since 1.0.0
 *
 * @return string[] Term names, in the order they should be created.
 */
function iflynepal_default_package_types() {
	return array(
		__( 'Retreat', 'iflynepal' ),
		__( 'Trek', 'iflynepal' ),
		__( 'Tour', 'iflynepal' ),
		__( 'Volunteering', 'iflynepal' ),
		__( 'Workshop', 'iflynepal' ),
	);
}

/**
 * Registers the Package Type taxonomy.
 *
 * Hierarchical, so it behaves like categories rather than tags: an editor picks
 * from a checklist of terms someone defined, instead of typing free text that
 * quietly forks "Retreat" and "retreats" into two archives. Nesting is the
 * point — "Retreat" > "Ayurvedic Retreats" is what produces the category depth
 * the design mockups show.
 *
 * Registered before the post type so that naming it in the post type's
 * `taxonomies` argument resolves against a taxonomy that already exists.
 *
 * Not exposed to the REST API: this site is a classic theme with no blocks
 * anywhere, packages are edited in the classic editor, and an unused public
 * endpoint is surface area with nothing using it. The plugin's own reads go
 * through the iflynepal/v1 namespace when they need to.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_package_taxonomy() {
	register_taxonomy(
		IFLYNEPAL_PACKAGE_TAXONOMY,
		array( IFLYNEPAL_PACKAGE_POST_TYPE ),
		array(
			'labels'             => array(
				'name'              => __( 'Package Types', 'iflynepal' ),
				'singular_name'     => __( 'Package Type', 'iflynepal' ),
				'search_items'      => __( 'Search Package Types', 'iflynepal' ),
				'all_items'         => __( 'All Package Types', 'iflynepal' ),
				'parent_item'       => __( 'Parent Package Type', 'iflynepal' ),
				'parent_item_colon' => __( 'Parent Package Type:', 'iflynepal' ),
				'edit_item'         => __( 'Edit Package Type', 'iflynepal' ),
				'update_item'       => __( 'Update Package Type', 'iflynepal' ),
				'add_new_item'      => __( 'Add New Package Type', 'iflynepal' ),
				'new_item_name'     => __( 'New Package Type Name', 'iflynepal' ),
				'not_found'         => __( 'No package types found', 'iflynepal' ),
				'back_to_items'     => __( 'Back to Package Types', 'iflynepal' ),
				'menu_name'         => __( 'Package Types', 'iflynepal' ),
			),
			'public'             => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_admin_column'  => true,
			'show_in_rest'       => false,
			'publicly_queryable' => true,
			'query_var'          => true,

			/*
			 * Managed by whoever manages Pages. Not decoration: an older WP
			 * Travel Engine version stripped every `*_posts` capability from
			 * the administrator and editor roles on this install. The core post
			 * capabilities have since been restored, but the theme's
			 * Testimonials type is on page capabilities for the same reason and
			 * this stays consistent with it. "Can edit Pages" is the right bar
			 * for the catalogue either way.
			 */
			'capabilities'       => array(
				'manage_terms' => 'edit_pages',
				'edit_terms'   => 'edit_pages',
				'delete_terms' => 'edit_pages',
				'assign_terms' => 'edit_pages',
			),

			/*
			 * No generated rules. Term archives live at the site root — a
			 * top-level term's own slug IS the URL root, e.g. /retreat-nepal/ —
			 * and the only base core could be given for that is an empty one,
			 * which produces a rule that swallows every single-segment path on
			 * the site. includes/rewrites/package-rewrites.php builds the rules
			 * from the term slugs that actually exist instead, and filters
			 * term_link to match.
			 */
			'rewrite'            => false,
		)
	);
}

/**
 * Registers the Packages post type.
 *
 * Public and archived: a package is a page a visitor lands on from search and
 * from the card grids, which is the whole point of the catalogue.
 *
 * Flat, not hierarchical. Packages do not nest inside each other — the
 * structure the design shows comes from the package type terms, and a post type
 * that was hierarchical as well would give an editor two contradictory ways to
 * express the same thing.
 *
 * `supports` covers what the card grids and the single template actually read:
 * a name, a body, a card image, a card summary, revisions on the copy, and
 * menu_order so an editor can set the running order of the cards by hand rather
 * than being stuck with publication date. Departure dates and every other
 * package field are meta boxes registered separately; `custom-fields` is
 * deliberately absent, because it only adds the raw meta UI, which would show
 * the plugin's private underscore-prefixed keys to editors alongside their
 * proper fields.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_package_cpt() {
	register_post_type(
		IFLYNEPAL_PACKAGE_POST_TYPE,
		array(
			'labels'             => array(
				'name'               => __( 'Packages', 'iflynepal' ),
				'singular_name'      => __( 'Package', 'iflynepal' ),
				'add_new'            => __( 'Add New', 'iflynepal' ),
				'add_new_item'       => __( 'Add New Package', 'iflynepal' ),
				'edit_item'          => __( 'Edit Package', 'iflynepal' ),
				'new_item'           => __( 'New Package', 'iflynepal' ),
				'view_item'          => __( 'View Package', 'iflynepal' ),
				'view_items'         => __( 'View Packages', 'iflynepal' ),
				'search_items'       => __( 'Search Packages', 'iflynepal' ),
				'not_found'          => __( 'No packages found', 'iflynepal' ),
				'not_found_in_trash' => __( 'No packages found in trash', 'iflynepal' ),
				'all_items'          => __( 'All Packages', 'iflynepal' ),
				'archives'           => __( 'Package Archives', 'iflynepal' ),
				'menu_name'          => __( 'Packages', 'iflynepal' ),
			),
			'public'             => true,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_rest'       => false,
			'publicly_queryable' => true,
			'has_archive'        => IFLYNEPAL_PACKAGE_ARCHIVE_SLUG,
			'query_var'          => true,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-palmtree',
			'capability_type'    => 'page',
			'map_meta_cap'       => true,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'taxonomies'         => array( IFLYNEPAL_PACKAGE_TAXONOMY ),

			/*
			 * No generated rules, for the same reason as the taxonomy: a
			 * package's URL is /{type-path}/{post-slug}, which has no fixed
			 * base for core to build a permastruct from. Every rule, including
			 * the flat /packages/ fallback and the archive, is built in
			 * includes/rewrites/package-rewrites.php.
			 *
			 * `has_archive` is kept because it is what makes
			 * is_post_type_archive(), the archive-{post_type}.php template and
			 * get_post_type_archive_link() work; with `rewrite => false` it
			 * contributes no rules of its own.
			 */
			'rewrite'            => false,
		)
	);
}

/**
 * Registers both, in dependency order.
 *
 * Wrapped in one function so activation can call exactly what `init` calls.
 * Registering a post type or a taxonomy anywhere other than `init` is
 * unreliable: too early and the roles and the rewrite system are not built yet,
 * too late and the main query has already been parsed.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_package_content_types() {
	iflynepal_register_package_taxonomy();
	iflynepal_register_package_cpt();
}
add_action( 'init', 'iflynepal_register_package_content_types' );

/**
 * Creates the default package type terms if they are not already there.
 *
 * Called from activation only. Idempotent by design: the term_exists() check
 * means deactivating and reactivating the plugin will not duplicate a term
 * and — as important — will not resurrect one an editor deliberately deleted,
 * because a term of that name is either present or was removed on purpose.
 *
 * The taxonomy must be registered before this runs, which is why the activation
 * callback registers first and seeds second.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_booking_seed_package_types() {
	foreach ( iflynepal_default_package_types() as $name ) {
		if ( term_exists( $name, IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
			continue;
		}

		wp_insert_term( $name, IFLYNEPAL_PACKAGE_TAXONOMY );
	}
}
