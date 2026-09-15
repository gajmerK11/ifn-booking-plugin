<?php
/**
 * The Connect Requests post type — one post per message sent from the
 * "Connect With Us" tab on the landing page.
 *
 * A second post type rather than a second source flag on the enquiry type, and
 * that is a deliberate choice. An enquiry is about a package: it carries a
 * package ID, it is sent from a catalogue template, and the office answers it
 * with a quotation. A connect request is sent from the landing page by somebody
 * who has not reached the catalogue at all — it carries a WhatsApp number and,
 * at most, the kind of trip they are curious about. The two answer different
 * questions, they are worked through by different people at different speeds,
 * and folding them into one list would mean every screen and every filter in
 * both workflows carrying an "is this the other kind?" branch.
 *
 * What the two genuinely share is the follow-up state — New / Contacted /
 * Closed, the office's own note about a conversation — so this type reuses the
 * enquiry's status list, its pill renderer and its admin CSS rather than
 * carrying a second copy of them. It registers itself on the pill-screens
 * filter; see iflynepal_enquiry_pill_screens() in enquiry-cpt.php.
 *
 * It lives in the plugin rather than the theme for the reason the catalogue and
 * the enquiries do: these are the business's own records and must survive a
 * theme switch.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The post type's key.
 *
 * WordPress caps a post type key at 20 characters, of which this uses 17. The
 * key never appears in a URL — connect requests are not public.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_CONNECT_POST_TYPE = 'iflynepal_connect';

/**
 * Meta key prefix for a connect request's stored fields.
 *
 * Underscore-prefixed, so the keys are protected: they never appear in the
 * generic custom-fields UI, and nothing but this plugin writes them.
 *
 * @since 1.0.0
 */
const IFLYNEPAL_CONNECT_META_PREFIX = '_iflynepal_connect_';

/**
 * Registers the Connect Requests post type.
 *
 * Not public, no Add New, nothing supported — the same three decisions the
 * enquiry type makes, for the same reasons. A connect request is a record of
 * what somebody sent: giving it a URL would publish a stranger's name, phone
 * number and email at a guessable address, and one typed by hand in the admin
 * would read in the list exactly like a real one.
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_register_connect_cpt() {
	register_post_type(
		IFLYNEPAL_CONNECT_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'Connect Requests', 'iflynepal' ),
				'singular_name'      => __( 'Connect Request', 'iflynepal' ),
				'edit_item'          => __( 'Connect Request', 'iflynepal' ),
				'view_item'          => __( 'View Connect Request', 'iflynepal' ),
				'search_items'       => __( 'Search Connect Requests', 'iflynepal' ),
				'not_found'          => __( 'No connect requests found.', 'iflynepal' ),
				'not_found_in_trash' => __( 'No connect requests found in trash.', 'iflynepal' ),
				'all_items'          => __( 'Connect Requests', 'iflynepal' ),
				'menu_name'          => __( 'Connect Requests', 'iflynepal' ),
			),
			'public'              => false,
			'show_ui'             => true,

			/*
			 * Inside the Packages menu, beside Enquiries. Both are inbound
			 * messages about the catalogue; they belong on the same shelf even
			 * though they are separate lists.
			 */
			'show_in_menu'        => 'edit.php?post_type=' . IFLYNEPAL_PACKAGE_POST_TYPE,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-format-chat',
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
			),
			'supports'            => false,
		)
	);
}
add_action( 'init', 'iflynepal_register_connect_cpt' );

/**
 * Adds this post type to the screens the enquiry status CSS is printed on.
 *
 * The pills, their colours and the radio control are generated from
 * iflynepal_enquiry_statuses() and are identical on both screens, so the one
 * builder serves both rather than a second copy being kept in step with it.
 *
 * @since 1.0.0
 *
 * @param string[] $post_types Post types whose screens draw status pills.
 * @return string[] Filtered post types.
 */
function iflynepal_connect_pill_screen( $post_types ) {
	$post_types[] = IFLYNEPAL_CONNECT_POST_TYPE;

	return $post_types;
}
add_filter( 'iflynepal_enquiry_pill_screens', 'iflynepal_connect_pill_screen' );

/**
 * The stored follow-up status of one connect request.
 *
 * Falls back to the first status in the list rather than to a hardcoded 'new',
 * so a site that filters the list to different words does not read every
 * request as having a status that is no longer on offer.
 *
 * @since 1.0.0
 *
 * @param int $post_id Connect request post ID.
 * @return string Status key.
 */
function iflynepal_connect_status( $post_id ) {
	$statuses = iflynepal_enquiry_statuses();
	$stored   = (string) get_post_meta( (int) $post_id, IFLYNEPAL_CONNECT_META_PREFIX . 'status', true );

	if ( isset( $statuses[ $stored ] ) ) {
		return $stored;
	}

	$keys = array_keys( $statuses );

	return isset( $keys[0] ) ? $keys[0] : 'new';
}

/* -------------------------------------------------------------- admin list */

/**
 * The Connect Requests list table's columns.
 *
 * The same shape as the Enquiries screen, plus the two facts this form asks for
 * and that one does not: the WhatsApp number — which is how most of these will
 * actually be answered — and the kind of trip the sender ticked.
 *
 * @since 1.0.0
 *
 * @param string[] $columns Column headings keyed by slug.
 * @return string[] Filtered columns.
 */
function iflynepal_connect_columns( $columns ) {
	return array(
		'cb'       => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'    => __( 'Name', 'iflynepal' ),
		'status'   => __( 'Follow-up', 'iflynepal' ),
		'date'     => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'iflynepal' ),
		'whatsapp' => __( 'WhatsApp', 'iflynepal' ),
		'email'    => __( 'Email', 'iflynepal' ),
		'interest' => __( 'Interested in', 'iflynepal' ),
		'preview'  => __( 'Enquiry', 'iflynepal' ),
	);
}
add_filter( 'manage_' . IFLYNEPAL_CONNECT_POST_TYPE . '_posts_columns', 'iflynepal_connect_columns' );

/**
 * Prints one cell of the Connect Requests list table.
 *
 * Both the number and the email are links, because answering the person is the
 * whole point of the screen: the number opens a WhatsApp chat with them, the
 * address opens a mail composer. The preview is a trimmed excerpt so a row says
 * what it is about without being opened.
 *
 * @since 1.0.0
 *
 * @param string $column  Column slug.
 * @param int    $post_id Connect request post ID.
 * @return void
 */
function iflynepal_connect_column_content( $column, $post_id ) {
	if ( 'status' === $column ) {
		// Finished, escaped markup from the shared pill renderer.
		echo iflynepal_enquiry_status_pill( iflynepal_connect_status( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return;
	}

	if ( 'whatsapp' === $column ) {
		$number = iflynepal_connect_field( $post_id, 'whatsapp' );
		$chat   = iflynepal_connect_chat_url( $post_id );

		if ( '' === $number ) {
			echo '&mdash;';

			return;
		}

		if ( '' === $chat ) {
			echo esc_html( $number );

			return;
		}

		printf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $chat ),
			esc_html( $number )
		);

		return;
	}

	if ( 'email' === $column ) {
		$email = iflynepal_connect_field( $post_id, 'email' );

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

	if ( 'interest' === $column ) {
		$label = iflynepal_connect_interest_label( $post_id );

		echo '' === $label ? '&mdash;' : esc_html( $label );

		return;
	}

	if ( 'preview' === $column ) {
		/*
		 * Trimmed by iflynepal_enquiry_preview(), which caps the length as well
		 * as the word count — a message with no spaces in it is one word, and a
		 * word cap alone never fires on it. See the note on that function.
		 */
		$preview = iflynepal_enquiry_preview( get_post_field( 'post_content', $post_id ) );

		echo '' === $preview ? '&mdash;' : esc_html( $preview );
	}
}
add_action( 'manage_' . IFLYNEPAL_CONNECT_POST_TYPE . '_posts_custom_column', 'iflynepal_connect_column_content', 10, 2 );

/**
 * Makes the meta-backed columns sortable.
 *
 * @since 1.0.0
 *
 * @param string[] $columns Sortable columns.
 * @return string[] Filtered sortable columns.
 */
function iflynepal_connect_sortable_columns( $columns ) {
	$columns['email']  = 'iflynepal_connect_email';
	$columns['status'] = 'iflynepal_connect_status';

	return $columns;
}
add_filter( 'manage_edit-' . IFLYNEPAL_CONNECT_POST_TYPE . '_sortable_columns', 'iflynepal_connect_sortable_columns' );

/**
 * Orders the list by a meta field when one of those columns is pressed.
 *
 * `meta_key` rather than a `meta_query`, for the reason the enquiry screen
 * gives: WordPress left-joins on a bare `meta_key` sort, so a row missing the
 * key still appears in the list instead of dropping out of it.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function iflynepal_connect_sort_by_meta( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( IFLYNEPAL_CONNECT_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	$sortable = array(
		'iflynepal_connect_email'  => 'email',
		'iflynepal_connect_status' => 'status',
	);

	$orderby = (string) $query->get( 'orderby' );

	if ( ! isset( $sortable[ $orderby ] ) ) {
		return;
	}

	$query->set( 'meta_key', IFLYNEPAL_CONNECT_META_PREFIX . $sortable[ $orderby ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
	$query->set( 'orderby', 'meta_value' );
}
add_action( 'pre_get_posts', 'iflynepal_connect_sort_by_meta' );

/**
 * The filters above the Connect Requests table — follow-up state, and interest.
 *
 * Two dropdowns rather than the list table's own views links, which belong to
 * post statuses. The interest filter is the one the office asked the question
 * for: it is what turns this list into "everybody curious about Trekking".
 *
 * @since 1.0.0
 *
 * @return void
 */
function iflynepal_connect_admin_filters() {
	global $typenow;

	if ( IFLYNEPAL_CONNECT_POST_TYPE !== $typenow ) {
		return;
	}

	// Reading filter values to repopulate the controls; both are sanitized before use.
	$status   = isset( $_GET['iflynepal_connect_status'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_connect_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$interest = isset( $_GET['iflynepal_connect_interest'] ) ? absint( wp_unslash( $_GET['iflynepal_connect_interest'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<label class="screen-reader-text" for="iflynepal_connect_status"><?php esc_html_e( 'Filter by follow-up', 'iflynepal' ); ?></label>
	<select name="iflynepal_connect_status" id="iflynepal_connect_status">
		<option value=""><?php esc_html_e( 'All follow-ups', 'iflynepal' ); ?></option>
		<?php foreach ( iflynepal_enquiry_statuses() as $iflynepal_key => $iflynepal_status ) : ?>
			<option value="<?php echo esc_attr( $iflynepal_key ); ?>" <?php selected( $status, $iflynepal_key ); ?>>
				<?php echo esc_html( $iflynepal_status['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>

	<label class="screen-reader-text" for="iflynepal_connect_interest"><?php esc_html_e( 'Filter by interest', 'iflynepal' ); ?></label>
	<select name="iflynepal_connect_interest" id="iflynepal_connect_interest">
		<option value="0"><?php esc_html_e( 'All package types', 'iflynepal' ); ?></option>
		<?php foreach ( iflynepal_connect_interest_choices() as $iflynepal_choice ) : ?>
			<option value="<?php echo esc_attr( (string) $iflynepal_choice['id'] ); ?>" <?php selected( $interest, $iflynepal_choice['id'] ); ?>>
				<?php echo esc_html( str_repeat( '— ', (int) $iflynepal_choice['depth'] ) . $iflynepal_choice['label'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
add_action( 'restrict_manage_posts', 'iflynepal_connect_admin_filters' );

/**
 * Applies the two list filters to the query.
 *
 * `NOT EXISTS` is part of the first status's clause because a request stored
 * before this meta existed carries no row at all and is still a new one.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function iflynepal_connect_filter_list( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( IFLYNEPAL_CONNECT_POST_TYPE !== $query->get( 'post_type' ) ) {
		return;
	}

	// Reading filter values from the list table's own GET form; sanitized here and checked against what exists.
	$status   = isset( $_GET['iflynepal_connect_status'] ) ? sanitize_key( wp_unslash( $_GET['iflynepal_connect_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$interest = isset( $_GET['iflynepal_connect_interest'] ) ? absint( wp_unslash( $_GET['iflynepal_connect_interest'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$statuses = iflynepal_enquiry_statuses();
	$clauses  = array();

	if ( '' !== $status && isset( $statuses[ $status ] ) ) {
		$keys   = array_keys( $statuses );
		$clause = array(
			'key'     => IFLYNEPAL_CONNECT_META_PREFIX . 'status',
			'value'   => $status,
			'compare' => '=',
		);

		if ( isset( $keys[0] ) && $status === $keys[0] ) {
			$clause = array(
				'relation' => 'OR',
				$clause,
				array(
					'key'     => IFLYNEPAL_CONNECT_META_PREFIX . 'status',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		$clauses[] = $clause;
	}

	if ( $interest ) {
		$clauses[] = array(
			'key'     => IFLYNEPAL_CONNECT_META_PREFIX . 'package_type',
			'value'   => $interest,
			'compare' => '=',
		);
	}

	if ( ! $clauses ) {
		return;
	}

	$clauses['relation'] = 'AND';

	$query->set( 'meta_query', $clauses ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
}
add_action( 'pre_get_posts', 'iflynepal_connect_filter_list' );
