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
		 * A handshake, to match the reference site the client pointed at.
		 *
		 * Filled rather than stroked, which is a departure from every other icon
		 * this plugin draws — and the reason is the size it is used at. A
		 * handshake is two overlapping hands, and at 26px the strokes of an
		 * outlined one collapse into each other and read as a scribble; it was
		 * drawn both ways and looked at before this was settled. The seam
		 * between the two hands is a *gap*, never a line painted in the
		 * background colour, so the icon stays correct on whatever ground it is
		 * ever put on.
		 */
		?>
		<span class="iflynepal-connect__tab-icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="currentColor" focusable="false">
				<rect x="0.7" y="9.2" width="4.1" height="7.2" rx="1.3"/>
				<rect x="19.2" y="7.8" width="4.1" height="7.2" rx="1.3"/>
				<path d="M5.4 10h3.2a2.3 2.3 0 0 1 1.5.58l2.1 1.83-2 1.72a1.4 1.4 0 0 1-1.85 0L5.4 11.5z"/>
				<path d="M18.6 8.6h-2.5a2.5 2.5 0 0 0-1.6.6l-5 4.25a1.6 1.6 0 0 0-.1 2.35l1.5 1.4a2.2 2.2 0 0 0 2.8.16l4.9-3.4z"/>
				<path d="M9.9 17.1l1.35 1.25a1.35 1.35 0 0 0 1.85-1.95l-1.3-1.2z"/>
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
					<a href="<?php echo esc_url( $iflynepal_chat_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Message us on WhatsApp', 'iflynepal' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<p class="iflynepal-connect__small">
				<?php esc_html_e( 'We use your details to answer you and nothing else.', 'iflynepal' ); ?>
			</p>
		</form>
	</section>
</div>
