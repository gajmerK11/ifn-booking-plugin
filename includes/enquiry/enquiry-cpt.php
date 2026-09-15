<?php
/**
 * The Enquiries post type — one post per enquiry a visitor sends.
 *
 * Modelled on the shape WP Travel Engine gives its own Enquiries screen, which
 * is what the client has seen and asked for: a list of Title, Date, Email and a
 * preview of the message, sitting inside the travel menu rather than beside
 * Posts.
 *
 * A post type rather than a custom table, for the reason the hand-off document
 * gives: the admin list table, search, sorting, trash, capabilities, pagination
 * and the row actions all come free and behave the way every other screen on
 * this site behaves. A table would need every one of them written, and the
 * volume here is sales enquiries, not analytics events.
 *
 * It lives in the plugin rather than the theme for the same reason as the
 * catalogue: enquiries are the business's own records and must survive a theme
 * switch.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The post type's key.
 *
 * Prefixed rather than the bare `enquiry` WP Travel Engine uses, which is
 * generic enough for another plugin to claim; a collision means one of the two
 * loses its posts to invisibility. WordPress caps a post type key at 20
 * characters, of which this uses 17. The key never appears in a URL — enquiries
 * are not public.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ENQUIRY_POST_TYPE = 'iflynepal_enquiry';

/**
 * Meta key prefix for an enquiry's stored fields.
 *
 * Underscore-prefixed, so the keys are protected: they never appear in the
 * generic custom-fields UI, and nothing but this plugin writes them.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_ENQUIRY_META_PREFIX = '_iflynepal_enquiry_';

/**
 * Registers the Enquiries post type.
 *
 * Not public: an enquiry is a private record of a conversation, and giving it a
 * URL would publish a stranger's name, email address and message at a guessable
 * address. `show_ui` still gives it the full admin screen.
 *
 * `create_posts` is mapped to `do_not_allow`, so there is no Add New button and
 * no Add New screen. An enquiry is something a visitor sends; one typed by hand
 * in the admin would carry no submission, no date a visitor chose and no
 * package, and would read in the list as a real one.
 *
 * Capabilities are otherwise the page ones, consistent with the packages type
 * and the theme's testimonials — see the note in includes/cpt/package-cpt.php
 * about the `*_posts` capabilities that were stripped from the roles on this
 * install.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_enquiry_cpt() {
	register_post_type(
		IFLYNEPAL_ENQUIRY_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'Enquiries', 'iflynepal' ),
				'singular_name'      => __( 'Enquiry', 'iflynepal' ),
				'edit_item'          => __( 'Enquiry', 'iflynepal' ),
				'view_item'          => __( 'View Enquiry', 'iflynepal' ),
				'search_items'       => __( 'Search Enquiries', 'iflynepal' ),
				'not_found'          => __( 'No Enquiries found.', 'iflynepal' ),
				'not_found_in_trash' => __( 'No Enquiries found in trash.', 'iflynepal' ),
				'all_items'          => __( 'Enquiries', 'iflynepal' ),
				'menu_name'          => __( 'Enquiries', 'iflynepal' ),
			),
			'public'              => false,
			'show_ui'             => true,

			/*
			 * Inside the Packages menu rather than a top-level item of its own.
			 * An enquiry is always about the catalogue, and the client's
			 * reference screen has it in the travel menu beside Bookings.
			 */
			'show_in_menu'        => 'edit.php?post_type=' . IFLYNEPAL_PACKAGE_POST_TYPE,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-email-alt',
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),

			/*
			 * Nothing is supported, so the edit screen carries no title input and
			 * no editor — only the plugin's own boxes and Publish. The title is
			 * generated from the sender and the package, and the message is the
			 * post content (stored there so the admin's own search finds an
			 * enquiry by what it says); both are still written and still read,
			 * they simply cannot be rewritten on a record of what somebody sent.
			 * The one decision on that screen is the follow-up status.
			 */
			'supports'            => false,
		)
	);
}
add_action( 'init', 'iflynepal_register_enquiry_cpt' );

/* ------------------------------------------------------------------ status */

/**
 * The follow-up states an enquiry can be in.
 *
 * Meta rather than a custom post status: a post status changes what queries and
 * the list table's own views do with a row, and these are sales notes about a
 * conversation, not publication states. Kept as a filterable list so the client
 * can be given different words — or different colours — without touching the
 * save routine or the stylesheet.
 *
 * Each status carries its own three colours rather than a class name the admin
 * CSS has to know about, so a status added through the filter is drawn correctly
 * by the pill and by the control with nothing else to edit. New is warm, because
 * it is the one that wants answering; Contacted is the site's navy, because it is
 * in hand; Closed is green, the only one that means nothing more is owed.
 *
 * @since 1.0.0
 *
 * @return array<string,array<string,string>> Status key => label, ink, bg, line.
 */
function iflynepal_enquiry_statuses() {
	$statuses = array(
		'new'       => array(
			'label' => __( 'New', 'iflynepal' ),
			'ink'   => '#8a4b00',
			'bg'    => '#fdf2dd',
			'line'  => '#e9c77f',
		),
		'contacted' => array(
			'label' => __( 'Contacted', 'iflynepal' ),
			'ink'   => '#04347d',
			'bg'    => '#e7effc',
			'line'  => '#9dbdf0',
		),
		'closed'    => array(
			'label' => __( 'Closed', 'iflynepal' ),
			'ink'   => '#1c6b35',
			'bg'    => '#e7f5ec',
			'line'  => '#9fd0b1',
		),
	);

	/**
	 * Filters the enquiry follow-up states.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,string>> $statuses Status key => label, ink, bg, line.
	 */
	return (array) apply_filters( 'iflynepal_enquiry_statuses', $statuses );
}

/**
 * One status's label.
 *
 * @since 1.0.0
 *
 * @param string $status Status key.
 * @return string Label, or the key itself when it is not a known status.
 */
function iflynepal_enquiry_status_label( $status ) {
	$statuses = iflynepal_enquiry_statuses();

	return isset( $statuses[ $status ]['label'] ) ? (string) $statuses[ $status ]['label'] : (string) $status;
}

/**
 * The coloured pill for one status.
 *
 * Returned finished and escaped so every screen showing a status shows the same
 * thing — the list table, the editor, and anything added later — rather than
 * three copies of one span drifting apart. Declared in phpcs.xml.dist as an
 * auto-escaped function, the way the theme declares its render callbacks.
 *
 * @since 1.0.0
 *
 * @param string $status Status key.
 * @return string Escaped HTML, or '' for a status that is not on offer.
 */
function iflynepal_enquiry_status_pill( $status ) {
	$statuses = iflynepal_enquiry_statuses();

	if ( ! isset( $statuses[ $status ] ) ) {
		return '';
	}

	return sprintf(
		'<span class="iflynepal-enq-pill iflynepal-enq-pill--%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( $statuses[ $status ]['label'] )
	);
}

/**
 * The post types whose admin screens draw a follow-up pill.
 *
 * A list rather than one constant, and taken by a filter rather than hardcoded,
 * because the pills, their colours and the radio control are generated from
 * iflynepal_enquiry_statuses() and are identical wherever a follow-up state is
 * shown. The connect requests screen adds itself here (see
 * includes/connect/connect-cpt.php), so the one CSS builder serves both rather
 * than a second copy of it being kept in step.
 *
 * The same shape as the answer §5.3p gave for testimonial display targets: one
 * hook that takes a list, rather than one hook per question.
 *
 * @since 1.0.0
 *
 * @return string[] Post type keys.
 */
function iflynepal_enquiry_pill_screens() {
	/**
	 * Filters the post types whose screens draw a follow-up pill.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $post_types Post type keys.
	 */
	return (array) apply_filters( 'iflynepal_enquiry_pill_screens', array( IFLYNEPAL_ENQUIRY_POST_TYPE ) );
}

/**
 * A message trimmed short enough to sit in a list-table cell.
 *
 * 🔴 `wp_trim_words()` alone is not enough, and the way it fails is not obvious.
 * It counts words by splitting on whitespace, so a message with no spaces in it
 * — 500 characters of keyboard-mashing, a pasted URL, a language this site does
 * not put spaces between words in — is **one word**, and a cap of fourteen is a
 * ceiling it can never reach. The whole message is returned untouched and the
 * cell stretches the table past the edge of the screen. Measured: 506 characters
 * in, 506 characters out.
 *
 * So the words are trimmed first, which is what gives a clean cut on ordinary
 * prose, and then the result is cut to a length as well, which is what catches
 * the run that has no words to count. The ellipsis is added once, at the end,
 * only if something was actually removed — appended before the length cut it
 * would be the part that gets cut off.
 *
 * `mb_*` throughout, or a cut can land in the middle of a multi-byte character
 * and print a replacement glyph.
 *
 * This is a display cap only. What the visitor sent is stored whole and is
 * printed whole on the record's own screen.
 *
 * @since 1.0.0
 *
 * @param string $text  The message.
 * @param int    $words Word ceiling.
 * @param int    $chars Character ceiling.
 * @return string Plain text, ready to escape. '' when there was nothing to show.
 */
function iflynepal_enquiry_preview( $text, $words = 14, $chars = 90 ) {
	$text = trim( wp_strip_all_tags( (string) $text ) );

	if ( '' === $text ) {
		return '';
	}

	$short = wp_trim_words( $text, $words, '' );

	if ( mb_strlen( $short ) > $chars ) {
		$short = rtrim( mb_substr( $short, 0, $chars ) );
	}

	return $short === $text ? $text : $short . '…';
}

/**
 * The admin styles for the pills and the status control.
 *
 * Built from the status list rather than written out, so a status added through
 * the filter arrives with its colours already applied. Inline because it is a few
 * hundred bytes on two admin screens — a file would be a second request for less
 * than one packet — and registered against a handle rather than echoed, so it
 * goes through the ordinary enqueue pipeline.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_enquiry_admin_styles() {
	/*
	 * get_current_screen() lives in wp-admin/includes/screen.php and is not
	 * defined everywhere this hook can fire — a login screen, a customizer
	 * frame or an early admin-ajax request can reach admin_enqueue_scripts
	 * before it exists, and calling it there is a fatal rather than a false.
	 */
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || ! in_array( $screen->post_type, iflynepal_enquiry_pill_screens(), true ) ) {
		return;
	}

	$css = '.iflynepal-enq-pill{display:inline-block;padding:3px 10px;border-radius:999px;border:1px solid transparent;font-size:12px;font-weight:600;line-height:1.6;white-space:nowrap}';

	/*
	 * The control is the same pills with a radio beside each, so the colour an
	 * editor picks is the colour they will then read in the list. The chosen one
	 * is ringed rather than only tinted: colour alone is not a state anybody can
	 * be asked to rely on (WCAG 1.4.1), and the radio and its label say it too.
	 */
	$css .= '.iflynepal-enq-status{margin:0;display:grid;gap:6px}';
	$css .= '.iflynepal-enq-status label{display:flex;align-items:center;gap:8px;padding:5px 6px;border-radius:6px;cursor:pointer}';
	$css .= '.iflynepal-enq-status label:hover{background:#f6f7f7}';
	$css .= '.iflynepal-enq-status input:checked+.iflynepal-enq-pill{box-shadow:0 0 0 2px #fff,0 0 0 3px currentColor}';
	$css .= '.iflynepal-enq-status input:focus-visible+.iflynepal-enq-pill{outline:2px solid #2271b1;outline-offset:2px}';
	$css .= '.iflynepal-enq-status .iflynepal-enq-note{margin:8px 0 0;color:#646970}';

	foreach ( iflynepal_enquiry_statuses() as $key => $status ) {
		$css .= sprintf(
			'.iflynepal-enq-pill--%1$s{color:%2$s;background:%3$s;border-color:%4$s}',
			sanitize_html_class( $key ),
			sanitize_hex_color( $status['ink'] ),
			sanitize_hex_color( $status['bg'] ),
			sanitize_hex_color( $status['line'] )
		);
	}

	/*
	 * 🔴 `overflow-wrap: anywhere`, not `break-word`, and the difference is the
	 * entire fix. Both break a long run of characters onto the next line, but
	 * only `anywhere` counts toward the element's min-content width — which is
	 * the number a table with `table-layout: auto` sizes its columns from. Under
	 * `break-word` the cell still *demands* the full width of the unbroken run,
	 * so the table stretches past the edge of the screen exactly as before and
	 * the rule looks as though it did nothing.
	 *
	 * The cap in iflynepal_enquiry_preview() above is what stops a long message
	 * reaching the cell at all; this is the belt to its braces, and it also
	 * covers the columns that are not trimmed — a pasted address with no spaces
	 * in it, or a name somebody held a key down in.
	 */
	$css .= '.wp-list-table td.column-title,.wp-list-table td.column-preview,.wp-list-table td.column-email,.wp-list-table td.column-whatsapp,.wp-list-table td.column-interest{overflow-wrap:anywhere}';
	$css .= '.wp-list-table td.column-preview{max-width:22em}';

	wp_register_style( 'iflynepal-enquiry-admin', false, array(), IFLYNEPAL_BOOKING_VERSION );
	wp_enqueue_style( 'iflynepal-enquiry-admin' );
	wp_add_inline_style( 'iflynepal-enquiry-admin', $css );
}
add_action( 'admin_enqueue_scripts', 'iflynepal_enquiry_admin_styles' );

/**
 * The stored status of one enquiry.
 *
 * Falls back to the first status in the list rather than to a hardcoded 'new',
 * so a site that filters the list to different words does not read every
 * enquiry as having a status that is no longer on offer.
 *
 * @since 1.0.0
 *
 * @param int $post_id Enquiry post ID.
 * @return string Status key.
 */
function iflynepal_enquiry_status( $post_id ) {
	$statuses = iflynepal_enquiry_statuses();
	$stored   = (string) get_post_meta( (int) $post_id, IFLYNEPAL_ENQUIRY_META_PREFIX . 'status', true );

	if ( isset( $statuses[ $stored ] ) ) {
		return $stored;
	}

	$keys = array_keys( $statuses );

	return isset( $keys[0] ) ? $keys[0] : 'new';
}

/*
 * There is deliberately no display_post_states badge on the title. The status has
 * a column of its own in the list table below, and a grey "— Contacted" beside
 * the title as well would be one fact printed twice, in two styles, two columns
 * apart.
 */

/* -------------------------------------------------------------- admin list */

/**
 * The Enquiries list table's columns.
 *
 * Title, Date, Email, Preview — the shape of the screen the client asked for.
 * Author is dropped (every enquiry is written by nobody: they arrive logged
 * out) and so are comments.
 *
 * @since 1.0.0
 *
 * @param string[] $columns Column headings keyed by slug.
 * @return string[] Filtered columns.
 */
function iflynepal_enquiry_columns( $columns ) {
	return array(
		'cb'      => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'   => __( 'Title', 'iflynepal' ),
		'status'  => __( 'Follow-up', 'iflynepal' ),
		'date'    => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'iflynepal' ),
		'email'   => __( 'Email', 'iflynepal' ),
		'preview' => __( 'Preview', 'iflynepal' ),
	);
}
add_filter( 'manage_' . IFLYNEPAL_ENQUIRY_POST_TYPE . '_posts_columns', 'iflynepal_enquiry_columns' );

/**
 * Prints one cell of the Enquiries list table.
 *
 * The email is a mailto link, because the whole point of this screen is to
 * answer the person, and the preview is a trimmed excerpt of the message so a
 * row says what it is about without being opened.
 *
 * @since 1.0.0
 *
 * @param string $column  Column slug.
 * @param int    $post_id Enquiry post ID.
 * @return void
 */
function iflynepal_enquiry_column_content( $column, $post_id ) {
	if ( 'status' === $column ) {
		// Finished, escaped markup from the shared pill renderer.
		echo iflynepal_enquiry_status_pill( iflynepal_enquiry_status( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return;
	}

	if ( 'email' === $column ) {
		$email = iflynepal_enquiry_field( $post_id, 'email' );

		if ( is_email( $email ) ) {
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( 'mailto:' . $email ),
				esc_html( $email )
			);
		} else {
			echo '&mdash;';
		}

		return;
	}

	if ( 'preview' === $column ) {
		$preview = iflynepal_enquiry_preview( get_post_field( 'post_content', $post_id ) );

		echo '' === $preview ? '&mdash;' : esc_html( $preview );
	}
}
add_action( 'manage_' . IFLYNEPAL_ENQUIRY_POST_TYPE . '_posts_custom_column', 'iflynepal_enquiry_column_content', 10, 2 );

/**
 * Makes the Email column sortable.
 *
 * @since 1.0.0
 *
 * @param string[] $columns Sortable columns.
 * @return string[] Filtered sortable columns.
 */
function iflynepal_enquiry_sortable_columns( $columns ) {
	$columns['email']  = 'iflynepal_enquiry_email';
	$columns['status'] = 'iflynepal_enquiry_status';

	return $columns;
}
add_filter( 'manage_edit-' . IFLYNEPAL_ENQUIRY_POST_TYPE . '_sortable_columns', 'iflynepal_enquiry_sortable_columns' );

/**
 * Orders the list by a meta field when one of those columns is pressed.
 *
 * `meta_key` rather than a `meta_query` with a relation, because an enquiry
 * without the key — there should be none, but a hand-edited row is possible —
 * would otherwise drop out of the list entirely when the column is sorted.
 * WordPress left-joins on a bare `meta_key` sort and keeps the row.
 *
 * Sorting by follow-up groups the list alphabetically by status key rather than
 * by how far along a conversation is. That is a limitation worth knowing about
 * and not worth a stored sort order: the dropdown above the table is what
 * answers "show me the new ones".
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function iflynepal_enquiry_sort_by_meta( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( IFLYNEPAL_ENQUIRY_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	$sortable = array(
		'iflynepal_enquiry_email'  => 'email',
		'iflynepal_enquiry_status' => 'status',
	);

	$orderby = (string) $query->get( 'orderby' );

	if ( ! isset( $sortable[ $orderby ] ) ) {
		return;
	}

	$query->set( 'meta_key', IFLYNEPAL_ENQUIRY_META_PREFIX . $sortable[ $orderby ] );
	$query->set( 'orderby', 'meta_value' );
}
add_action( 'pre_get_posts', 'iflynepal_enquiry_sort_by_meta' );

/**
 * Filters the list by follow-up status.
 *
 * A dropdown above the table rather than the list table's own views links,
 * which belong to post statuses. `NOT EXISTS` is part of the New clause because
 * an enquiry stored before this meta existed carries no row at all and is still
 * a new one.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_enquiry_status_filter() {
	global $typenow;

	if ( IFLYNEPAL_ENQUIRY_POST_TYPE !== $typenow ) {
		return;
	}

	// Reading a filter value to repopulate the control; the value is sanitized before use.
	$current = isset( $_GET['iflynepal_enquiry_status'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_enquiry_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<label class="screen-reader-text" for="iflynepal_enquiry_status"><?php esc_html_e( 'Filter by status', 'iflynepal' ); ?></label>
	<select name="iflynepal_enquiry_status" id="iflynepal_enquiry_status">
		<option value=""><?php esc_html_e( 'All statuses', 'iflynepal' ); ?></option>
		<?php foreach ( iflynepal_enquiry_statuses() as $iflynepal_key => $iflynepal_status ) : ?>
			<option value="<?php echo esc_attr( $iflynepal_key ); ?>" <?php selected( $current, $iflynepal_key ); ?>>
				<?php echo esc_html( $iflynepal_status['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'iflynepal_enquiry_status_filter' );

/**
 * Applies the status filter to the list query.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function iflynepal_enquiry_filter_by_status( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( IFLYNEPAL_ENQUIRY_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	// Reading a filter value from the list table's own GET form; sanitized here and checked against the known list.
	$status = isset( $_GET['iflynepal_enquiry_status'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_enquiry_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $status || ! isset( iflynepal_enquiry_statuses()[ $status ] ) ) {
		return;
	}

	$statuses = array_keys( iflynepal_enquiry_statuses() );
	$clause   = array(
		'key'     => IFLYNEPAL_ENQUIRY_META_PREFIX . 'status',
		'value'   => $status,
		'compare' => '=',
	);

	if ( isset( $statuses[0] ) && $status === $statuses[0] ) {
		$clause = array(
			'relation' => 'OR',
			$clause,
			array(
				'key'     => IFLYNEPAL_ENQUIRY_META_PREFIX . 'status',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$query->set( 'meta_query', array( $clause ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
}
add_action( 'pre_get_posts', 'iflynepal_enquiry_filter_by_status' );
