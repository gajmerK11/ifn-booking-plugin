<?php
/**
 * The Connect Request meta box — what the visitor sent, as a plain form view.
 *
 * Every value is printed read-only and deliberately not as a control, the same
 * decision the enquiry screen records: a bordered grey box reads as a disabled
 * input, invites a click and fills the screen with furniture for a record
 * nobody can change. A label beside its value in core's own form table says the
 * same thing in half the space and never pretends to be a field.
 *
 * The one decision on the screen is the follow-up status, which is the office's
 * own note rather than the visitor's, and it has a box of its own in the
 * sidebar: see class-ifly-nepal-connect-status-box.php.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shows one connect request, read-only.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Connect_Details_Box {

	/**
	 * Meta box ID.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_connect_details';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	/**
	 * Registers the box as the main column of the connect request screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Connect Request', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_CONNECT_POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Prints the request.
	 *
	 * `form-table` is core's own two-column layout — the one the Settings
	 * screens use — so the rows line up with the rest of the admin and inherit
	 * its responsive collapse at narrow widths with nothing to maintain here.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The request being viewed.
	 * @return void
	 */
	public function render( $post ) {
		$whatsapp = iflynepal_connect_field( $post->ID, 'whatsapp' );
		$chat     = iflynepal_connect_chat_url( $post->ID );
		$email    = iflynepal_connect_field( $post->ID, 'email' );
		$interest = iflynepal_connect_interest_label( $post->ID );
		$term_id  = iflynepal_connect_interest_id( $post->ID );
		$message  = iflynepal_connect_field( $post->ID, 'message' );
		$source   = iflynepal_connect_field( $post->ID, 'source' );
		?>
		<style>
			/*
			 * Only what core's form table does not already give: the enquiry
			 * keeps the line breaks the visitor typed, and the rows are ruled so
			 * a long message and the row under it do not run together.
			 */
			.iflynepal-connect-view th { width: 150px; padding: 14px 10px 14px 0; }
			.iflynepal-connect-view td { padding: 14px 0; }
			.iflynepal-connect-view tr + tr th,
			.iflynepal-connect-view tr + tr td { border-top: 1px solid #f0f0f1; }
			.iflynepal-connect-view td { overflow-wrap: anywhere; }
			.iflynepal-connect-view .iflynepal-connect-message { margin: 0; white-space: pre-wrap; line-height: 1.7; }
			.iflynepal-connect-view .iflynepal-connect-muted { color: #646970; }
		</style>

		<table class="form-table iflynepal-connect-view" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Name', 'iflynepal' ); ?></th>
					<td><?php echo esc_html( iflynepal_connect_field( $post->ID, 'name' ) ); ?></td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'WhatsApp', 'iflynepal' ); ?></th>
					<td>
						<?php if ( '' === $whatsapp ) : ?>
							<span class="iflynepal-connect-muted">&mdash;</span>
						<?php elseif ( '' === $chat ) : ?>
							<?php echo esc_html( $whatsapp ); ?>
						<?php else : ?>
							<?php
							/*
							 * The number is printed as the visitor wrote it and
							 * linked by its digits — the two are not the same
							 * string, and showing the stripped form would have
							 * the screen quietly disagreeing with what they
							 * typed.
							 */
							?>
							<a href="<?php echo esc_url( $chat ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $whatsapp ); ?></a>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Email', 'iflynepal' ); ?></th>
					<td>
						<?php if ( is_email( $email ) ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
						<?php else : ?>
							<span class="iflynepal-connect-muted">&mdash;</span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Interested in', 'iflynepal' ); ?></th>
					<td>
						<?php if ( '' !== $interest ) : ?>
							<?php
							$link = get_edit_term_link( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY );
							?>
							<?php if ( $link ) : ?>
								<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $interest ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $interest ); ?>
							<?php endif; ?>
						<?php elseif ( $term_id ) : ?>
							<?php
							/*
							 * A stored term that no longer resolves has been
							 * deleted since. Saying so is more use than an
							 * em dash, which reads as "they did not answer".
							 */
							?>
							<span class="iflynepal-connect-muted"><?php esc_html_e( 'A package type that has since been deleted', 'iflynepal' ); ?></span>
						<?php else : ?>
							<span class="iflynepal-connect-muted"><?php esc_html_e( 'Not specified', 'iflynepal' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Enquiry', 'iflynepal' ); ?></th>
					<td>
						<?php if ( '' !== trim( $message ) ) : ?>
							<p class="iflynepal-connect-message"><?php echo esc_html( $message ); ?></p>
						<?php else : ?>
							<span class="iflynepal-connect-muted"><?php esc_html_e( 'None given', 'iflynepal' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Received', 'iflynepal' ); ?></th>
					<td>
						<?php
						echo esc_html(
							get_post_time(
								get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
								false,
								$post,
								true
							)
						);
						?>
					</td>
				</tr>

				<?php if ( '' !== $source ) : ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Sent from', 'iflynepal' ); ?></th>
						<td><a href="<?php echo esc_url( $source ); ?>"><?php echo esc_html( $source ); ?></a></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}

new IFly_Nepal_Connect_Details_Box();
