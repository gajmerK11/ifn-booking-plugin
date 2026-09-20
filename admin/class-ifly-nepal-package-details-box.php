<?php
/**
 * The Package Details meta box.
 *
 * Draws the `details` fields of the package schema, panelled by their declared
 * section: the title, the at-a-glance table, the overview, the itinerary, the
 * dates and price, the booking aside, the packing list, the map and the FAQs.
 *
 * Presentation follows the Edit Package Type screen and the Package Card box —
 * the CloudColleague pattern, with the label on its own line above a full-width
 * control and the panel a responsive grid. The three admin surfaces of this
 * plugin should read as the same screen.
 *
 * The render and the save walk the same list, which is not tidiness: the save
 * treats a field the form did not submit as emptied, so a box that drew one
 * section and saved nine would wipe the other eight on the first Update.
 *
 * Admin only: loaded from the main plugin file inside `if ( is_admin() )`.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Edits everything the single-package page is built from.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Details_Box {

	/**
	 * Meta box ID, the form field name, and the base for its nonce.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const ID = 'iflynepal_package_page';

	/**
	 * Which schema fields this box owns.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BOX = 'details';

	/**
	 * Hooks the box into the editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . IFLYNEPAL_PACKAGE_POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Registers the box under the Package Card box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register() {
		add_meta_box(
			self::ID,
			__( 'Package Details', 'iflynepal' ),
			array( $this, 'render' ),
			IFLYNEPAL_PACKAGE_POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Loads the media picker and the repeater behaviour.
	 *
	 * The same script the term screen uses: the repeater, the image picker and
	 * the row numbering are the plugin's one implementation of those controls,
	 * and a second copy for the post screen would be a second one to keep in
	 * step.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		if ( IFLYNEPAL_PACKAGE_POST_TYPE !== get_post_type() ) {
			return;
		}

		wp_enqueue_media();

		/*
		 * The `wysiwyg` fields' editor. Core only calls this itself when the post
		 * type supports 'editor' — which this one deliberately does not (§5.3l) —
		 * so without this call wp_editor() below prints a plain, unstyled
		 * textarea with no TinyMCE behind it.
		 */
		wp_enqueue_editor();

		wp_enqueue_script(
			'iflynepal-archive-fields',
			IFLYNEPAL_BOOKING_URL . 'assets/js/admin/archive-fields.js',
			array(),
			iflynepal_booking_asset_version( 'assets/js/admin/archive-fields.js' ),
			true
		);

		wp_localize_script(
			'iflynepal-archive-fields',
			'iflynepalArchiveFields',
			array(
				'chooseTitle' => __( 'Choose image', 'iflynepal' ),
				'chooseUse'   => __( 'Use this image', 'iflynepal' ),
				/* translators: %d: the row's position in the list. */
				'cardLabel'   => __( 'Card %d', 'iflynepal' ),
			)
		);
	}

	/**
	 * Draws the panels.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Package being edited.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( self::ID . '_save', self::ID . '_nonce' );

		echo '<div class="iflynepal-package-fields iflynepal-package-fields--panels">';

		foreach ( iflynepal_package_detail_sections() as $section_key => $section ) {
			$fields = iflynepal_package_fields_for_section( $section_key );

			if ( empty( $fields ) ) {
				continue;
			}

			/*
			 * Panels are collapsed <details>, as the term screen's are, and every
			 * one of them starts shut — the box itself now opens closed too, so a
			 * panel left open would be the one thing on the screen that unfolded
			 * itself. An editor opens what they came to write.
			 */
			printf(
				'<details class="iflynepal-archive__section"><summary>%s</summary>',
				esc_html( $section['label'] )
			);

			if ( '' !== $section['description'] ) {
				printf( '<p class="description">%s</p>', esc_html( $section['description'] ) );
			}

			echo '<table class="form-table" role="presentation"><tbody>';

			foreach ( $fields as $key => $field ) {
				$this->render_row( $post->ID, $key, $field );
			}

			echo '</tbody></table></details>';
		}

		echo '</div>';

		$this->render_styles();
	}

	/**
	 * One field, as a row of the panel grid.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Package.
	 * @param string $key     Schema key.
	 * @param array  $field   Field definition.
	 * @return void
	 */
	private function render_row( $post_id, $key, $field ) {
		$id   = self::ID . '_' . $key;
		$name = self::ID . '[' . $key . ']';

		printf(
			'<tr class="iflynepal-package-field iflynepal-package-field--%s">',
			esc_attr( $field['type'] )
		);
		printf(
			'<th scope="row"><label for="%1$s">%2$s</label></th>',
			esc_attr( $id ),
			esc_html( $field['label'] )
		);
		echo '<td>';

		$this->render_control( $post_id, $key, $id, $name, $field );

		if ( '' !== $field['help'] ) {
			/*
			 * Help text names the tags an editor may type, so it is escaped for
			 * display rather than allowed through — <em> in the instruction has
			 * to read as <em>, not italicise the instruction.
			 */
			printf( '<p class="description">%s</p>', esc_html( $field['help'] ) );
		}

		echo '</td></tr>';
	}

	/**
	 * The control a field's type asks for.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Package.
	 * @param string $key     Schema key.
	 * @param string $id      Input id.
	 * @param string $name    Input name.
	 * @param array  $field   Field definition.
	 * @return void
	 */
	private function render_control( $post_id, $key, $id, $name, $field ) {
		if ( 'cards' === $field['type'] ) {
			$this->render_cards( $post_id, $key, $name, $field );

			return;
		}

		if ( 'image' === $field['type'] ) {
			$this->render_image( $id, $name, (int) iflynepal_package_field( $post_id, $key ) );

			return;
		}

		if ( 'wysiwyg' === $field['type'] ) {
			$this->render_wysiwyg( $id, $name, iflynepal_package_field( $post_id, $key ), $field );

			return;
		}

		if ( 'select' === $field['type'] ) {
			$this->render_select( $id, $name, iflynepal_package_field( $post_id, $key ), $field );

			return;
		}

		$value = iflynepal_package_field( $post_id, $key );

		// A field with nothing saved yet may declare what to show instead —
		// the WhatsApp number pre-filled with the office's own, e.g. — which
		// stays exactly as editable as if an editor had typed it themselves.
		if ( '' === $value && isset( $field['default'] ) ) {
			$value = $field['default'];
		}

		if ( 'textarea' === $field['type'] || 'lines' === $field['type'] ) {
			printf(
				'<textarea rows="%1$d" id="%2$s" name="%3$s">%4$s</textarea>',
				'lines' === $field['type'] ? 6 : 4,
				esc_attr( $id ),
				esc_attr( $name ),
				esc_textarea( $value )
			);

			return;
		}

		$input_type = 'text';
		if ( 'url' === $field['type'] ) {
			$input_type = 'url';
		} elseif ( 'time' === $field['type'] ) {
			$input_type = 'time';
		}

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" />',
			esc_attr( $input_type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * A repeater, drawn as the term screen draws one.
	 *
	 * The markup contract is the script's, not this class's: a wrapper carrying
	 * the name, the cap and the row label, a list of rows, a <template> holding
	 * one empty row, and an Add button. Matching it is what lets one script drive
	 * every repeater in the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Package.
	 * @param string $key     Schema key.
	 * @param string $name    Base input name.
	 * @param array  $field   Field definition.
	 * @return void
	 */
	private function render_cards( $post_id, $key, $name, $field ) {
		$rows = iflynepal_package_cards( $post_id, $key );
		$max  = isset( $field['max'] ) ? (int) $field['max'] : 0;
		$item = isset( $field['item'] ) ? $field['item'] : __( 'Card', 'iflynepal' );
		?>
		<div class="iflynepal-archive__cards" data-iflynepal-cards data-max="<?php echo esc_attr( (string) $max ); ?>" data-name="<?php echo esc_attr( $name ); ?>" data-label="<?php echo esc_attr( $item . ' %d' ); ?>">
			<div data-iflynepal-cards-list>
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php $this->render_card_row( $name, (int) $index, $row, $field ); ?>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button button-secondary iflynepal-archive__card-add" data-iflynepal-cards-add>
				<?php
				/* translators: %s: what one row is called, e.g. Day or Question. */
				printf( esc_html__( '+ Add %s', 'iflynepal' ), esc_html( $item ) );
				?>
			</button>

			<?php
			/*
			 * __INDEX__ is swapped for the real row number by the script. A
			 * <template> is inert until then: it is not rendered and its inputs are
			 * not submitted, so an unused one cannot post an empty row.
			 */
			?>
			<template data-iflynepal-cards-template>
				<?php $this->render_card_row( $name, '__INDEX__', array(), $field ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * One repeater row.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name  Base input name.
	 * @param int|string $index Row position, or the template placeholder.
	 * @param array      $row   Stored row, empty for a new one.
	 * @param array      $field Field definition.
	 * @return void
	 */
	private function render_card_row( $name, $index, $row, $field ) {
		$base = $name . '[' . $index . ']';
		$item = isset( $field['item'] ) ? $field['item'] : __( 'Card', 'iflynepal' );
		?>
		<div class="iflynepal-archive__card" data-iflynepal-card>
			<p class="iflynepal-archive__card-number" data-iflynepal-card-number></p>

			<?php foreach ( $field['parts'] as $part_key => $part ) : ?>
				<?php $this->render_card_part( $base . '[' . $part_key . ']', $part, isset( $row[ $part_key ] ) ? $row[ $part_key ] : '' ); ?>
			<?php endforeach; ?>

			<button type="button" class="button iflynepal-archive__card-remove" data-iflynepal-card-remove>
				<?php
				/* translators: %s: what one row is called, e.g. Day or Question. */
				printf( esc_html__( 'Remove %s', 'iflynepal' ), esc_html( $item ) );
				?>
			</button>
		</div>
		<?php
	}

	/**
	 * One part of one repeater row.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Full input name.
	 * @param array  $part  Part definition.
	 * @param mixed  $value Stored value.
	 * @return void
	 */
	private function render_card_part( $name, $part, $value ) {
		if ( 'image' === $part['type'] ) {
			// A repeater row passes no id: its name is unique, a duplicated id is not.
			$this->render_image( '', $name, (int) $value );

			return;
		}
		?>
		<label class="iflynepal-archive__card-label"><?php echo esc_html( $part['label'] ); ?></label>
		<?php
		if ( 'timeline' === $part['type'] ) {
			$this->render_timeline( $name, $part, $value );

			return;
		}

		if ( 'textarea' === $part['type'] ) {
			printf(
				'<textarea name="%1$s" rows="4">%2$s</textarea>',
				esc_attr( $name ),
				esc_textarea( (string) $value )
			);

			return;
		}

		printf(
			'<input type="text" name="%1$s" value="%2$s" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * A day's timeline: a repeater of stops inside the day repeater.
	 *
	 * Two levels of repeater is machinery this plugin deliberately avoided once,
	 * with the timeline typed as `time | what happens` lines instead. The client
	 * asked for the rows themselves, movable, so they are rows — but the contract
	 * is the one the flat repeaters already use: a <template> holding an empty
	 * row, indices re-derived from position on every add, remove and move, and the
	 * cap enforced on save as well as here.
	 *
	 * Order is the whole point of a timeline, so a row can be moved three ways:
	 * dragged by its handle, or stepped with the arrow buttons, which are what a
	 * keyboard and a screen reader have. Drag alone would be a control some people
	 * cannot use.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Full input name for the part, e.g. …[0][timeline].
	 * @param array  $part  Part definition, carrying 'max' and 'item'.
	 * @param mixed  $value Stored rows, or the legacy line text.
	 * @return void
	 */
	private function render_timeline( $name, $part, $value ) {
		/*
		 * Legacy line text is parsed into rows here rather than migrated in the
		 * database: the control shows what was already stored, and the first
		 * Update writes it back in the new shape.
		 */
		$rows = iflynepal_package_timeline_rows( $value );
		$max  = isset( $part['max'] ) ? (int) $part['max'] : 0;
		$item = isset( $part['item'] ) ? $part['item'] : __( 'Stop', 'iflynepal' );
		?>
		<div class="iflynepal-timeline" data-iflynepal-timeline data-max="<?php echo esc_attr( (string) $max ); ?>">
			<div class="iflynepal-timeline__list" data-iflynepal-timeline-list>
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php $this->render_timeline_row( $name, (int) $index, $row ); ?>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button iflynepal-timeline__add" data-iflynepal-timeline-add>
				<?php
				/* translators: %s: what one row is called, e.g. Stop. */
				printf( esc_html__( '+ Add %s', 'iflynepal' ), esc_html( $item ) );
				?>
			</button>

			<?php if ( isset( $part['help'] ) && '' !== $part['help'] ) : ?>
				<p class="description"><?php echo esc_html( $part['help'] ); ?></p>
			<?php endif; ?>

			<template data-iflynepal-timeline-template>
				<?php
				$this->render_timeline_row(
					$name,
					'__INDEX__',
					array(
						'time' => '',
						'text' => '',
					)
				);
				?>
			</template>
		</div>
		<?php
	}

	/**
	 * One timeline stop.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name  Full input name for the part.
	 * @param int|string $index Row position, or the template placeholder.
	 * @param array      $row   Stored row of 'time' and 'text'.
	 * @return void
	 */
	private function render_timeline_row( $name, $index, $row ) {
		$base = $name . '[' . $index . ']';
		?>
		<div class="iflynepal-timeline__row" data-iflynepal-timeline-row draggable="true">
			<span class="iflynepal-timeline__handle" data-iflynepal-timeline-handle aria-hidden="true">
				<span class="dashicons dashicons-menu"></span>
			</span>

			<input type="text" class="iflynepal-timeline__time" name="<?php echo esc_attr( $base . '[time]' ); ?>"
				value="<?php echo esc_attr( isset( $row['time'] ) ? (string) $row['time'] : '' ); ?>"
				aria-label="<?php esc_attr_e( 'Time', 'iflynepal' ); ?>"
				placeholder="<?php esc_attr_e( 'Time', 'iflynepal' ); ?>" />

			<input type="text" class="iflynepal-timeline__text" name="<?php echo esc_attr( $base . '[text]' ); ?>"
				value="<?php echo esc_attr( isset( $row['text'] ) ? (string) $row['text'] : '' ); ?>"
				aria-label="<?php esc_attr_e( 'What happens', 'iflynepal' ); ?>"
				placeholder="<?php esc_attr_e( 'What happens', 'iflynepal' ); ?>" />

			<span class="iflynepal-timeline__actions">
				<button type="button" class="button-link iflynepal-timeline__move" data-iflynepal-timeline-up aria-label="<?php esc_attr_e( 'Move up', 'iflynepal' ); ?>">&uarr;</button>
				<button type="button" class="button-link iflynepal-timeline__move" data-iflynepal-timeline-down aria-label="<?php esc_attr_e( 'Move down', 'iflynepal' ); ?>">&darr;</button>
				<button type="button" class="button-link iflynepal-timeline__remove" data-iflynepal-timeline-remove aria-label="<?php esc_attr_e( 'Remove stop', 'iflynepal' ); ?>">&times;</button>
			</span>
		</div>
		<?php
	}

	/**
	 * The media picker.
	 *
	 * Stores the attachment ID, never a URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id            Input id, empty inside a repeater row.
	 * @param string $name          Input name.
	 * @param int    $attachment_id Stored attachment.
	 * @return void
	 */
	private function render_image( $id, $name, $attachment_id ) {
		$image = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';
		?>
		<span class="iflynepal-archive__media" data-iflynepal-media>
			<span class="iflynepal-archive__preview" data-iflynepal-media-preview>
				<?php
				// Built by wp_get_attachment_image(), which escapes its own output.
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</span>
			<input type="hidden"<?php echo '' === $id ? '' : ' id="' . esc_attr( $id ) . '"'; ?> name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-iflynepal-media-value>
			<button type="button" class="button" data-iflynepal-media-select><?php esc_html_e( 'Choose image', 'iflynepal' ); ?></button>
			<button type="button" class="button-link" data-iflynepal-media-remove<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'iflynepal' ); ?></button>
		</span>
		<?php
	}

	/**
	 * A reduced-toolbar wp_editor(), for the fields that hold prose rather
	 * than a single line — bold, italic, underline, a bulleted list and a
	 * link, which is what the schema's own help text for these fields now
	 * promises and nothing more. `teeny` is core's own name for this size of
	 * toolbar; it is narrowed further here because teeny's own default still
	 * carries alignment, block quotes and a fullscreen toggle this page has
	 * no design for.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Input id.
	 * @param string $name  Input name.
	 * @param string $value Stored HTML, or a legacy plain-text value.
	 * @param array  $field Field definition.
	 * @return void
	 */
	private function render_wysiwyg( $id, $name, $value, $field ) {
		wp_editor(
			$value,
			$id,
			array(
				'textarea_name' => $name,
				'textarea_rows' => isset( $field['rows'] ) ? (int) $field['rows'] : 14,
				'teeny'         => true,
				'media_buttons' => false,
				'quicktags'     => false,
				'tinymce'       => array(
					'toolbar1' => 'bold,italic,underline,bullist,link,unlink,undo,redo',
					'toolbar2' => '',
				),
			)
		);
	}

	/**
	 * A field with a fixed set of answers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    Input id.
	 * @param string $name  Input name.
	 * @param string $value Stored value.
	 * @param array  $field Field definition, carrying 'options' (value => label).
	 * @return void
	 */
	private function render_select( $id, $name, $value, $field ) {
		$options = isset( $field['options'] ) ? $field['options'] : array();
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<option value=""><?php esc_html_e( '— Not set —', 'iflynepal' ); ?></option>
			<?php foreach ( $options as $option_value => $option_label ) : ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * The box's own presentation.
	 *
	 * Only what the panels add on top of the Package Card box's rules, which are
	 * printed by that box on the same screen: the <details> shell, the repeater
	 * card and the image picker.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_styles() {
		?>
		<style>
			.iflynepal-package-fields--panels .iflynepal-archive__section {
				margin: 0 0 12px;
				border: 1px solid #dcdcde;
				border-radius: 6px;
				background: #fff;
			}

			.iflynepal-package-fields--panels .iflynepal-archive__section > summary {
				padding: 13px 16px;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
			}

			.iflynepal-package-fields--panels .iflynepal-archive__section[open] > summary {
				border-bottom: 1px solid #f0f0f1;
			}

			.iflynepal-package-fields--panels .iflynepal-archive__section > .description {
				margin: 0;
				padding: 14px 18px 0;
			}

			/*
			 * Direct-child selectors, so a table nested inside a field later is
			 * not block-ified into a single column of inputs — the bug the term
			 * screen's comparison grid already paid for once.
			 */
			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table,
			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table > tbody,
			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table > tbody > tr,
			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table > tbody > tr > th,
			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table > tbody > tr > td {
				display: block;
				width: auto;
			}

			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table {
				margin: 0;
				padding: 14px 18px 20px;
			}

			.iflynepal-package-fields--panels .iflynepal-archive__section > .form-table > tbody {
				display: grid;
				gap: 20px 28px;
				grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
			}

			/* Prose, lists and the repeaters take the whole row. */
			.iflynepal-package-fields--panels .iflynepal-package-field--textarea,
			.iflynepal-package-fields--panels .iflynepal-package-field--lines,
			.iflynepal-package-fields--panels .iflynepal-package-field--wysiwyg,
			.iflynepal-package-fields--panels .iflynepal-package-field--cards,
			.iflynepal-package-fields--panels .iflynepal-package-field--gallery {
				grid-column: 1 / -1;
			}

			.iflynepal-package-fields--panels .form-table input[type="text"],
			.iflynepal-package-fields--panels .form-table input[type="url"],
			.iflynepal-package-fields--panels .form-table select,
			.iflynepal-package-fields--panels .form-table textarea {
				width: 100%;
				padding: 10px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				box-sizing: border-box;
				line-height: 1.5;
			}

			.iflynepal-package-fields--panels .form-table select {
				max-width: 320px;
			}

			/* wp_editor() prints its own chrome; only the outer width is ours to set. */
			.iflynepal-package-fields--panels .wp-editor-wrap {
				max-width: 100%;
			}

			/* ------------------------------------------------------ repeater */

			.iflynepal-archive__card {
				position: relative;
				margin-bottom: 12px;
				padding: 16px 18px 18px;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				background: #fdfdfd;
			}

			.iflynepal-archive__card-number {
				margin: 0 0 10px;
				padding-bottom: 8px;
				border-bottom: 1px solid #ececec;
				color: #1d2327;
				font-size: 13px;
				font-weight: 600;
			}

			.iflynepal-archive__card-label {
				display: block;
				margin: 12px 0 5px;
				color: #50575e;
				font-size: 12px;
				font-weight: 600;
			}

			.iflynepal-archive__card-remove {
				margin-top: 14px;
			}

			/*
			 * Core's `.wp-core-ui .button { margin: 0 }` is two classes and beats
			 * a one-class rule, which reads as "the CSS did not apply". Prefixed
			 * so these margins actually land.
			 */
			.iflynepal-package-fields .iflynepal-archive__card-add,
			.iflynepal-package-fields .iflynepal-archive__card-remove {
				margin-top: 10px;
			}

			/* ----------------------------------------- timeline repeater */

			.iflynepal-timeline__row {
				display: grid;
				align-items: center;
				gap: 8px;
				grid-template-columns: 22px 140px 1fr auto;
				margin-bottom: 6px;
				padding: 6px;
				border: 1px solid #e0e0e0;
				border-radius: 4px;
				background: #fff;
			}

			.iflynepal-timeline__row.is-dragging {
				opacity: 0.4;
			}

			/* The row is dropped after the one it is dragged over. */
			.iflynepal-timeline__row.is-drop-target {
				border-color: #2271b1;
			}

			.iflynepal-timeline__handle {
				color: #8c8f94;
				cursor: grab;
				text-align: center;
			}

			.iflynepal-timeline__handle .dashicons {
				width: 18px;
				height: 18px;
				font-size: 18px;
			}

			.iflynepal-package-fields--panels .iflynepal-timeline__row input[type="text"] {
				margin: 0;
				padding: 6px 8px;
			}

			.iflynepal-timeline__actions {
				display: flex;
				gap: 6px;
				align-items: center;
			}

			.iflynepal-package-fields .iflynepal-timeline__move,
			.iflynepal-package-fields .iflynepal-timeline__remove {
				padding: 2px 4px;
				color: #50575e;
				font-size: 15px;
				line-height: 1;
				text-decoration: none;
			}

			.iflynepal-package-fields .iflynepal-timeline__remove {
				color: #b32d2e;
			}

			.iflynepal-package-fields .iflynepal-timeline__add {
				margin-top: 4px;
			}

			/* -------------------------------------------------- image picker */

			.iflynepal-archive__media {
				display: block;
			}

			.iflynepal-archive__preview img {
				display: block;
				max-width: 220px;
				height: auto;
				margin-bottom: 8px;
				border: 1px solid #dcdcde;
				border-radius: 4px;
			}

			.iflynepal-package-fields .iflynepal-archive__media .button {
				margin-right: 8px;
			}
		</style>
		<?php
	}

	/**
	 * Saves the submitted fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post being saved.
	 * @return void
	 */
	public function save( $post_id ) {
		$nonce_key = self::ID . '_nonce';

		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), self::ID . '_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitized per field below, by the type the schema declares.
		$submitted = isset( $_POST[ self::ID ] ) ? wp_unslash( $_POST[ self::ID ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $submitted ) ) {
			return;
		}

		foreach ( iflynepal_package_fields_for_box( self::BOX ) as $key => $field ) {
			$raw   = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';
			$value = iflynepal_package_sanitize_value( $raw, $field['type'], $field );

			/*
			 * A repeater emptied of its last row posts nothing at all, which has
			 * to mean "no rows" rather than "not submitted, leave alone" — or the
			 * final row could never be deleted.
			 */
			if ( '' === $value || array() === $value ) {
				delete_post_meta( $post_id, iflynepal_package_meta_key( $key ) );

				continue;
			}

			update_post_meta( $post_id, iflynepal_package_meta_key( $key ), $value );
		}
	}
}

new IFly_Nepal_Package_Details_Box();
