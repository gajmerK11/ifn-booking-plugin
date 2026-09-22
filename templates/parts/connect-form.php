<?php
/**
 * The "Connect With Us" tab and its slide-in panel.
 *
 * Two pieces that behave differently without JavaScript, and that difference is
 * the design:
 *
 *  - **The tab** is a plain anchor pointing at the panel's own id. It is
 *    positioned by CSS alone, so it is on the edge of the screen and it works
 *    whether or not a script ever runs: pressed without one, it jumps to the
 *    panel at the foot of the page.
 *  - **The panel** is an ordinary section of the page until the inline head
 *    script adds `iflynepal-connect-js` to the document element. Under that
 *    class the stylesheet turns it into a right-hand drawer and connect.js
 *    opens it. The class goes in the head rather than on load so the panel is
 *    never painted at the bottom of the page and then snatched away — the same
 *    reasoning as the enquiry form's gate and the catalogue's animation gate.
 *
 * Fields, their types and which are required all come from
 * iflynepal_connect_fields(), the same list the handler validates against, so a
 * field cannot exist on one side and not the other.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_notice   = iflynepal_connect_notice();
$iflynepal_fields   = iflynepal_connect_fields();
$iflynepal_choices  = iflynepal_connect_interest_choices();
$iflynepal_chat_url = iflynepal_whatsapp_url();
?>

<div class="iflynepal-connect" data-iflynepal-connect<?php echo $iflynepal_notice ? ' data-iflynepal-connect-open-now' : ''; ?>>

	<?php
	/*
	 * The tab. Collapsed to its icon and expanded on hover — and on
	 * :focus-within, because a control that only answers a mouse is a control a
	 * keyboard cannot read. aria-expanded is written by the script rather than
	 * rendered here: without the script this is a link to a section, not a
	 * control that opens anything, and claiming otherwise is worse than
	 * claiming nothing.
	 */
	?>
	<a class="iflynepal-connect__tab" href="#iflynepal-connect-panel" data-iflynepal-connect-open>
		<?php
		/*
		 * A speech bubble, not the reference site's handshake: this tab now
		 * opens on every page, not just the one the handshake was chosen to
		 * match, so the icon reads as "message us" rather than as a callback
		 * to a single hero. Filled rather than stroked, same reasoning as
		 * before — at 26px a stroked bubble's tail and dots blur together.
		 */
		?>
		<span class="iflynepal-connect__tab-icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="currentColor" focusable="false">
				<path d="M4.5 4A2.5 2.5 0 0 0 2 6.5v8A2.5 2.5 0 0 0 4.5 17H7v3.3a.7.7 0 0 0 1.15.54L12.6 17h6.9A2.5 2.5 0 0 0 22 14.5v-8A2.5 2.5 0 0 0 19.5 4h-15z"/>
			</svg>
		</span>

		<?php
		/*
		 * The label is two elements, not one, because of how it is revealed. The
		 * outer element is a one-track grid animating between 0fr and 1fr; the
		 * inner one is what is clipped. See the note in connect.css — a single
		 * element cannot do it, because there is nothing for the track to size
		 * against.
		 */
		?>
		<span class="iflynepal-connect__tab-label">
			<span class="iflynepal-connect__tab-text"><?php esc_html_e( 'Connect With Us', 'iflynepal' ); ?></span>
		</span>
	</a>

	<div class="iflynepal-connect__backdrop" data-iflynepal-connect-close hidden></div>

	<?php
	/*
	 * tabindex="-1" so the panel itself can take focus when the drawer opens. It
	 * is the panel that is focused rather than the first field, which would pop
	 * a keyboard open on a phone before the visitor has read what they are
	 * looking at, or the close button, which announces "Close" as the first
	 * thing a screen reader hears. Focusing the labelled dialog announces its
	 * title instead.
	 *
	 * 🔴 `role="dialog"` and `aria-modal` are written by the script, not
	 * rendered here. Without the script this is an ordinary section of the page
	 * that nothing can close — announcing it as a modal dialog would tell a
	 * screen reader the rest of the page is inert when it is not, which is worse
	 * than announcing nothing. `aria-labelledby` is safe either way: a section
	 * is allowed a name.
	 */
	?>
	<section class="iflynepal-connect__panel" id="iflynepal-connect-panel" aria-labelledby="iflynepal-connect-title" tabindex="-1">
		<header class="iflynepal-connect__head">
			<h2 class="iflynepal-connect__title" id="iflynepal-connect-title"><?php esc_html_e( 'Connect With Us', 'iflynepal' ); ?></h2>

			<button class="iflynepal-connect__close" type="button" data-iflynepal-connect-close hidden>
				<span class="screen-reader-text"><?php esc_html_e( 'Close', 'iflynepal' ); ?></span>
				<svg class="iflynepal-connect__x" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M6.5 6.5l11 11M17.5 6.5l-11 11" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
				</svg>
			</button>
		</header>

		<?php if ( $iflynepal_notice ) : ?>
			<?php
			/*
			 * tabindex="-1" so the script can move focus here when the drawer is
			 * reopened after a redirect: a visitor who has just submitted needs
			 * to be told what happened, and a notice nobody is focused on is a
			 * notice a screen reader never reads.
			 */
			?>
			<p class="iflynepal-connect__notice iflynepal-connect__notice--<?php echo esc_attr( $iflynepal_notice['type'] ); ?>"
				id="iflynepal-connect-notice"
				role="<?php echo 'error' === $iflynepal_notice['type'] ? 'alert' : 'status'; ?>"
				tabindex="-1">
				<?php echo esc_html( $iflynepal_notice['message'] ); ?>
			</p>
		<?php endif; ?>

		<form class="iflynepal-connect__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( IFLYNEPAL_CONNECT_ACTION ); ?>">
			<?php wp_nonce_field( IFLYNEPAL_CONNECT_ACTION, 'iflynepal_connect_nonce' ); ?>

			<?php
			/*
			 * The honeypot. Hidden from sight by the stylesheet and from the
			 * accessibility tree and the tab order by the attributes, so nobody
			 * using the form ever meets it and a script filling every input
			 * does. Same field name as the theme's contact form and the
			 * enquiry's.
			 */
			?>
			<p class="iflynepal-connect__hp" aria-hidden="true">
				<label for="iflynepal-connect-website"><?php esc_html_e( 'Leave this field empty', 'iflynepal' ); ?></label>
				<input type="text" id="iflynepal-connect-website" name="website" tabindex="-1" autocomplete="off" value="">
			</p>

			<?php foreach ( $iflynepal_fields as $iflynepal_key => $iflynepal_field ) : ?>
				<?php
				$iflynepal_type     = isset( $iflynepal_field['type'] ) ? $iflynepal_field['type'] : 'text';
				$iflynepal_id       = 'iflynepal-connect-' . sanitize_key( $iflynepal_key );
				$iflynepal_required = ! empty( $iflynepal_field['required'] );
				$iflynepal_help     = isset( $iflynepal_field['help'] ) ? $iflynepal_field['help'] : '';
				$iflynepal_auto     = isset( $iflynepal_field['autocomplete'] ) ? $iflynepal_field['autocomplete'] : '';
				$iflynepal_hint     = isset( $iflynepal_field['placeholder'] ) ? $iflynepal_field['placeholder'] : '';

				/*
				 * A package_type field with no terms to offer is dropped rather
				 * than drawn empty: a select holding only "not sure yet" asks a
				 * question it cannot take an answer to. It cannot happen on this
				 * site — the five types are seeded on activation — but a
				 * taxonomy an editor has emptied should not publish a dead
				 * control.
				 */
				if ( 'package_type' === $iflynepal_type && ! $iflynepal_choices ) {
					continue;
				}
				?>
				<p class="iflynepal-connect__field iflynepal-connect__field--<?php echo esc_attr( $iflynepal_type ); ?>">
					<label for="<?php echo esc_attr( $iflynepal_id ); ?>">
						<?php echo esc_html( $iflynepal_field['label'] ); ?>
						<?php if ( $iflynepal_required ) : ?>
							<span class="iflynepal-connect__req" aria-hidden="true">*</span>
						<?php endif; ?>
					</label>

					<?php if ( 'textarea' === $iflynepal_type ) : ?>
						<textarea
							id="<?php echo esc_attr( $iflynepal_id ); ?>"
							name="<?php echo esc_attr( $iflynepal_key ); ?>"
							rows="4"
							<?php echo '' !== $iflynepal_hint ? 'placeholder="' . esc_attr( $iflynepal_hint ) . '"' : ''; ?>
							<?php echo $iflynepal_required ? 'required' : ''; ?>
							<?php echo '' !== $iflynepal_help ? 'aria-describedby="' . esc_attr( $iflynepal_id ) . '-help"' : ''; ?>
						></textarea>

					<?php elseif ( 'package_type' === $iflynepal_type ) : ?>
						<?php
						/*
						 * The empty option carries a real label rather than a
						 * blank: "not sure yet" is an answer a visitor can mean,
						 * and it is the one an unanswered select would store
						 * anyway.
						 */
						?>
						<select
							id="<?php echo esc_attr( $iflynepal_id ); ?>"
							name="<?php echo esc_attr( $iflynepal_key ); ?>"
							<?php echo $iflynepal_required ? 'required' : ''; ?>
							<?php echo '' !== $iflynepal_help ? 'aria-describedby="' . esc_attr( $iflynepal_id ) . '-help"' : ''; ?>
						>
							<option value=""><?php echo esc_html( isset( $iflynepal_field['empty'] ) ? $iflynepal_field['empty'] : __( 'Choose one', 'iflynepal' ) ); ?></option>
							<?php foreach ( $iflynepal_choices as $iflynepal_choice ) : ?>
								<option value="<?php echo esc_attr( (string) $iflynepal_choice['id'] ); ?>">
									<?php
									/*
									 * A non-breaking space per level of depth,
									 * which is how core's own term dropdowns
									 * indent: an ordinary space is collapsed
									 * inside an <option> and the hierarchy
									 * disappears.
									 */
									echo esc_html( str_repeat( "\xc2\xa0\xc2\xa0\xc2\xa0", (int) $iflynepal_choice['depth'] ) . $iflynepal_choice['label'] );
									?>
								</option>
							<?php endforeach; ?>
						</select>

					<?php else : ?>
						<input
							type="<?php echo esc_attr( iflynepal_connect_input_type( $iflynepal_type ) ); ?>"
							id="<?php echo esc_attr( $iflynepal_id ); ?>"
							name="<?php echo esc_attr( $iflynepal_key ); ?>"
							<?php echo '' !== $iflynepal_hint ? 'placeholder="' . esc_attr( $iflynepal_hint ) . '"' : ''; ?>
							<?php echo '' !== $iflynepal_auto ? 'autocomplete="' . esc_attr( $iflynepal_auto ) . '"' : ''; ?>
							<?php echo $iflynepal_required ? 'required' : ''; ?>
							<?php echo '' !== $iflynepal_help ? 'aria-describedby="' . esc_attr( $iflynepal_id ) . '-help"' : ''; ?>
							value="">
					<?php endif; ?>

					<?php if ( '' !== $iflynepal_help ) : ?>
						<span class="iflynepal-connect__help" id="<?php echo esc_attr( $iflynepal_id ); ?>-help"><?php echo esc_html( $iflynepal_help ); ?></span>
					<?php endif; ?>
				</p>
			<?php endforeach; ?>

			<p class="iflynepal-connect__actions">
				<button class="iflynepal-connect__submit" type="submit">
					<?php esc_html_e( 'Send', 'iflynepal' ); ?>
					<?php echo iflynepal_booking_arrow_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns finished, escaped markup. ?>
				</button>
			</p>

			<?php
			/*
			 * The office's own WhatsApp, offered as the other way of reaching
			 * them rather than making a visitor close the drawer and go looking.
			 * Nothing is shown when no number is configured at Packages >
			 * Settings — see P9.
			 */
			?>
			<?php if ( '' !== $iflynepal_chat_url ) : ?>
				<p class="iflynepal-connect__alt">
					<?php esc_html_e( 'Prefer to chat?', 'iflynepal' ); ?>
				</p>
				<a class="iflynepal-connect__whatsapp" <?php echo iflynepal_booking_anchor_attr( $iflynepal_chat_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?> target="_blank" rel="noopener noreferrer">
					<svg class="iflynepal-connect__whatsapp-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8a9.1 9.1 0 0 0-7.9 13.7L2.8 21.2l4.8-1.3A9.1 9.1 0 1 0 12 2.8zm0 16.6c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A7.5 7.5 0 1 1 12 19.4zm4.1-5.6c-.2-.1-1.3-.7-1.6-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1-.2-.1-1-.4-1.8-1.1-.7-.6-1.1-1.3-1.3-1.5-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.4c.1.2 1.6 2.5 4 3.5 2 .8 2.4.6 2.8.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.5-.3z"/></svg>
					<?php esc_html_e( 'Message us on WhatsApp', 'iflynepal' ); ?>
				</a>
			<?php endif; ?>

			<p class="iflynepal-connect__small">
				<?php esc_html_e( 'We use your details to answer you and nothing else.', 'iflynepal' ); ?>
			</p>
		</form>
	</section>
</div>
