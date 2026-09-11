<?php
/**
 * The archive content fields on the Edit Package Type screen.
 *
 * WordPress has no add_meta_box() on a term screen — the term editor is a form,
 * not a meta box canvas — so the fields are hooked into the form itself with
 * {$taxonomy}_edit_form_fields and saved on edited_{$taxonomy}. That pair is the
 * term equivalent of add_meta_box() plus save_post, and it is what every plugin
 * that puts fields on a category screen uses.
 *
 * Fields are drawn on the Edit screen only, not the Add screen. A new type is
 * created by typing a name; filling in ten sections of marketing copy for a term
 * that does not exist yet is not how anybody works, and it would put a very long
 * form above the term list.
 *
 * The sections are collapsed by default, because the whole model is well over a
 * hundred fields and an editor is normally coming here to change one of them.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders and stores the package type archive content model.
 *
 * @since 1.0.0
 */
class IFly_Nepal_Package_Type_Archive_Fields {

	/**
	 * Nonce action and field base.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const NONCE = 'iflynepal_archive_fields';

	/**
	 * Hooks the fields into the term editor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( IFLYNEPAL_PACKAGE_TAXONOMY . '_edit_form_fields', array( $this, 'render' ), 10, 1 );
		add_action( 'edited_' . IFLYNEPAL_PACKAGE_TAXONOMY, array( $this, 'save' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the media picker on the term editor screen only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( 'term.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || IFLYNEPAL_PACKAGE_TAXONOMY !== $screen->taxonomy ) {
			return;
		}

		wp_enqueue_media();

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
				/* translators: %d: the card's position in the list. */
				'cardLabel'   => __( 'Card %d', 'iflynepal' ),
			)
		);
	}

	/**
	 * Draws every section as a collapsed group of fields.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $term Term being edited.
	 * @return void
	 */
	public function render( $term ) {
		wp_nonce_field( self::NONCE . '_save', self::NONCE . '_nonce' );

		$this->render_styles();
		?>
		<tr class="form-field">
			<td colspan="2" class="iflynepal-archive">
				<h2><?php esc_html_e( 'Archive page content', 'iflynepal' ); ?></h2>
				<p class="description">
					<?php
					printf(
						/* translators: %s: the archive URL this content appears on. */
						esc_html__( 'The copy that wraps the package cards on %s. Every field is optional — a section with nothing in it is left off the page.', 'iflynepal' ),
						'<code>' . esc_html( get_term_link( $term ) ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inline.
					);
					?>
				</p>

				<?php
				/*
				 * Only the sections this term's archive can actually render. A
				 * category page is the hero and the grid, so showing an editor a
				 * Booking plans box on one would be an invitation to write copy
				 * that never appears anywhere.
				 */
				foreach ( iflynepal_package_type_archive_sections_for_term( $term ) as $section_key => $section ) :
					?>
					<details class="iflynepal-archive__section">
						<summary><?php echo esc_html( $section['label'] ); ?></summary>
						<?php if ( '' !== $section['description'] ) : ?>
							<p class="description"><?php echo esc_html( $section['description'] ); ?></p>
						<?php endif; ?>
						<table class="form-table" role="presentation">
							<?php
							/*
							 * One tbody per run of fields, which is what makes a
							 * group a card: a table may hold any number of
							 * tbodies, and each one is styled as its own panel.
							 *
							 * A div would have been the obvious wrapper and is
							 * the wrong one — the HTML parser hoists a div out
							 * of a table and drops it above it, so the card
							 * would render empty with its fields beside it.
							 */
							foreach ( $this->field_runs( $section ) as $run ) :
								$panel_class = 'iflynepal-archive__panel';

								if ( '' !== $run['title'] ) {
									$panel_class .= ' iflynepal-archive__panel--card';
								}
								?>
								<tbody class="<?php echo esc_attr( $panel_class ); ?>">
								<?php if ( '' !== $run['title'] ) : ?>
									<tr class="iflynepal-archive__panel-title">
										<th scope="col"><?php echo esc_html( $run['title'] ); ?></th>
									</tr>
								<?php endif; ?>
								<?php
								/*
								 * The type travels onto the row as a class so the
								 * stylesheet can lay the panel out: short controls
								 * sit side by side, and the ones that need room —
								 * paragraphs, lists, the card repeater — take the
								 * full width of the row.
								 */
								foreach ( $run['fields'] as $key => $field ) :
									$row_class = 'iflynepal-archive__field iflynepal-archive__field--' . $field['type'];

									/*
									 * A field may ask for a column, which is how the
									 * two halves of one button — its label and its
									 * link — are kept on a row together rather than
									 * flowing into whatever gap the row above left.
									 */
									if ( isset( $field['column'] ) ) {
										$row_class .= ' iflynepal-archive__field--col-' . (int) $field['column'];
									}
									?>
									<tr class="<?php echo esc_attr( $row_class ); ?>">
										<th scope="row">
											<label for="<?php echo esc_attr( $this->input_id( $key ) ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
										</th>
										<td><?php $this->render_control( $term->term_id, $key, $field ); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							<?php endforeach; ?>
						</table>
					</details>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * A section's fields split into the panels the screen draws them in.
	 *
	 * A section whose fields name no group is one untitled run, which is every
	 * section but the plans. Where fields do name a group — the three booking
	 * plans — each run is drawn as a titled card, so the eight fields of a plan
	 * are read and edited as one thing instead of as a twenty-four-field column
	 * told apart only by the number repeated in every label.
	 *
	 * Runs are cut on the group *changing*, in schema order, rather than
	 * collected by group key. Order is the schema's to decide, and a group
	 * appearing twice would be a mistake worth seeing rather than one to tidy
	 * away silently.
	 *
	 * @since 1.0.0
	 *
	 * @param array $section One section from the schema.
	 * @return array[] Runs, each with 'title' and 'fields'.
	 */
	private function field_runs( $section ) {
		$titles = isset( $section['groups'] ) ? $section['groups'] : array();
		$runs   = array();
		$open   = null;

		foreach ( $section['fields'] as $key => $field ) {
			$group = isset( $field['group'] ) ? (string) $field['group'] : '';

			if ( null === $open || $group !== $open ) {
				$runs[] = array(
					'title'  => isset( $titles[ $group ] ) ? $titles[ $group ] : '',
					'fields' => array(),
				);

				$open = $group;
			}

			$runs[ count( $runs ) - 1 ]['fields'][ $key ] = $field;
		}

		return $runs;
	}

	/**
	 * The DOM id for a field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Schema key.
	 * @return string DOM id.
	 */
	private function input_id( $key ) {
		return 'iflynepal-archive-' . str_replace( '_', '-', $key );
	}

	/**
	 * Draws one control, chosen by the field's type.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id Term being edited.
	 * @param string $key     Schema key.
	 * @param array  $field   Field definition.
	 * @return void
	 */
	private function render_control( $term_id, $key, $field ) {
		$id   = $this->input_id( $key );
		$name = iflynepal_archive_meta_key( $key );

		// `cards` and `table` store arrays; reading one as a string warns.
		$value = in_array( $field['type'], array( 'cards', 'table' ), true ) ? '' : iflynepal_archive_field( $term_id, $key );

		switch ( $field['type'] ) {
			case 'cards':
				$this->render_cards_control( $term_id, $key, $name, $field );
				break;

			case 'table':
				$this->render_table_control( $term_id, $key, $name );
				break;

			case 'image':
				$this->render_image_control( $id, $name, (int) $value );
				break;

			case 'textarea':
			case 'lines':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text">%4$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					'lines' === $field['type'] ? 5 : 3,
					esc_textarea( $value )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( '1', $value, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- checked() returns a fixed attribute string.
					esc_html__( 'Yes', 'iflynepal' )
				);
				break;

			case 'url':
				printf(
					'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="large-text" inputmode="url">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_url( $value )
				);
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="large-text">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;
		}

		if ( '' !== $field['help'] ) {
			echo '<p class="description">' . wp_kses(
				$field['help'],
				array(
					'em'     => array(),
					'strong' => array(),
					'code'   => array(),
				)
			) . '</p>';
		}
	}

	/**
	 * Draws a repeater.
	 *
	 * Rows are added and removed with a button rather than being a fixed run of
	 * numbered slots. The sections that use it — the reason cards and the FAQ —
	 * have no fixed length in the design, and numbering the slots means an
	 * editor with four entries works around the empty ones.
	 *
	 * What a row holds comes from the schema field's `parts`, so a second
	 * repeater is a schema entry rather than a second renderer and a second
	 * sanitizer. This is the control the CloudColleague industry FAQ and
	 * pay-rates tabs use, built once.
	 *
	 * The row template lives in a <template> element rather than in a JavaScript
	 * string, so the markup an editor sees on a saved row and the markup they
	 * get on a new one are the same markup — there is no second copy to drift.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id Term being edited.
	 * @param string $key     Schema key.
	 * @param string $name    Base field name.
	 * @param array  $field   Field definition, carrying 'parts', 'max', 'item'.
	 * @return void
	 */
	private function render_cards_control( $term_id, $key, $name, $field ) {
		$cards = iflynepal_archive_cards( $term_id, $key );
		$id    = $this->input_id( $key );
		$max   = iflynepal_archive_card_max( $field );
		$item  = isset( $field['item'] ) ? $field['item'] : __( 'Card', 'iflynepal' );
		?>
		<div class="iflynepal-archive__cards" data-iflynepal-cards data-max="<?php echo esc_attr( (string) $max ); ?>" data-name="<?php echo esc_attr( $name ); ?>" data-label="<?php echo esc_attr( $item . ' %d' ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<div data-iflynepal-cards-list>
				<?php foreach ( $cards as $index => $card ) : ?>
					<?php $this->render_card_row( $name, (int) $index, is_array( $card ) ? $card : array(), $field ); ?>
				<?php endforeach; ?>
			</div>

			<button type="button" class="button button-secondary iflynepal-archive__card-add" data-iflynepal-cards-add>
				<?php
				/* translators: %s: what one row is called, e.g. Card or Question. */
				printf( esc_html__( '+ Add %s', 'iflynepal' ), esc_html( $item ) );
				?>
			</button>

			<?php
			/*
			 * __INDEX__ is swapped for the real row number by the script. The
			 * template is inert markup until then — a <template> element is not
			 * rendered and its inputs are not submitted, so an unused one cannot
			 * post an empty row.
			 */
			?>
			<template data-iflynepal-cards-template>
				<?php $this->render_card_row( $name, '__INDEX__', array(), $field ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * One row of a repeater.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name  Base field name.
	 * @param int|string $index Row index, or the template placeholder.
	 * @param array      $card  Stored row, empty for a new one.
	 * @param array      $field Field definition.
	 * @return void
	 */
	private function render_card_row( $name, $index, $card, $field ) {
		$base = $name . '[' . $index . ']';
		$item = isset( $field['item'] ) ? $field['item'] : __( 'Card', 'iflynepal' );
		?>
		<div class="iflynepal-archive__card" data-iflynepal-card>
			<p class="iflynepal-archive__card-number" data-iflynepal-card-number></p>

			<?php foreach ( iflynepal_archive_card_parts( $field ) as $part_key => $part ) : ?>
				<?php $this->render_card_part( $base . '[' . $part_key . ']', $part, isset( $card[ $part_key ] ) ? $card[ $part_key ] : '' ); ?>
			<?php endforeach; ?>

			<button type="button" class="button iflynepal-archive__card-remove" data-iflynepal-card-remove>
				<?php
				/* translators: %s: what one row is called, e.g. Card or Question. */
				printf( esc_html__( 'Remove %s', 'iflynepal' ), esc_html( $item ) );
				?>
			</button>
		</div>
		<?php
	}

	/**
	 * One part of one repeater row.
	 *
	 * Each part carries its own label, because a row of unlabelled boxes is only
	 * readable while the placeholder is showing — the moment it is filled in,
	 * nothing on screen says which box is the question and which the answer.
	 * The image part is the exception: the picker names itself.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name  Full field name.
	 * @param array  $part  Part definition, with 'label' and 'type'.
	 * @param mixed  $value Stored value.
	 * @return void
	 */
	private function render_card_part( $name, $part, $value ) {
		if ( 'image' === $part['type'] ) {
			// A repeater row passes no id: its name is unique, a duplicated id is not.
			$this->render_image_control( '', $name, (int) $value );

			return;
		}
		?>
		<label class="iflynepal-archive__card-label"><?php echo esc_html( $part['label'] ); ?></label>
		<?php
		if ( 'textarea' === $part['type'] ) {
			printf(
				'<textarea name="%1$s" placeholder="%2$s">%3$s</textarea>',
				esc_attr( $name ),
				esc_attr( $part['label'] ),
				esc_textarea( (string) $value )
			);

			return;
		}

		printf(
			'<input type="text" name="%1$s" value="%2$s" placeholder="%3$s">',
			esc_attr( $name ),
			esc_attr( (string) $value ),
			esc_attr( $part['label'] )
		);
	}

	/**
	 * Draws the comparison table as a table.
	 *
	 * The control the CloudColleague industry pay-rates tab uses: the grid as it
	 * will be published, headings along the top, a row added with a button, a
	 * saved row read-only until its pencil is pressed. What it replaces is
	 * twenty-five separate text boxes labelled "Row 4, column 3", which say
	 * nothing about the shape of the thing being edited.
	 *
	 * The difference from the reference is that the **column headings are
	 * fields too**, sitting in the thead where they will read on the page. The
	 * pay-rates table hardcodes Role / Typical pay / Notes, which cannot work
	 * here: what a retreat archive compares itself against is not what a trek
	 * archive does.
	 *
	 * Columns are a fixed run of four and rows are a repeater, which is not an
	 * inconsistency — the design compares a row label against up to three
	 * things, and the front end drops any column whose heading is blank. An
	 * "Add Column" button would be a fourth way to say the same thing.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $term_id Term being edited.
	 * @param string $key     Schema key.
	 * @param string $name    Base field name.
	 * @return void
	 */
	private function render_table_control( $term_id, $key, $name ) {
		$table = iflynepal_archive_table( $term_id, $key );
		$id    = $this->input_id( $key );
		?>
		<div class="iflynepal-archive__table" data-iflynepal-table data-max="<?php echo esc_attr( (string) IFLYNEPAL_ARCHIVE_COMPARE_MAX_ROWS ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<div class="iflynepal-archive__grid-scroll">
			<table class="iflynepal-archive__grid">
				<thead>
					<tr>
						<?php
						for ( $column_index = 0; $column_index < IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS; $column_index++ ) :
							$column = isset( $table['columns'][ $column_index ] ) && is_array( $table['columns'][ $column_index ] ) ? $table['columns'][ $column_index ] : array();
							$base   = $name . '[columns][' . $column_index . ']';
							?>
							<th scope="col"<?php echo IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS - 1 === $column_index ? ' colspan="2"' : ''; ?>>
								<input type="text" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( isset( $column['label'] ) ? $column['label'] : '' ); ?>"
									<?php /* translators: %d: column number. */ ?>
									placeholder="<?php echo esc_attr( sprintf( __( 'Column %d', 'iflynepal' ), $column_index + 1 ) ); ?>">

								<?php
								/*
								 * No sub-note on the first column. It labels the
								 * rows rather than naming something compared, so
								 * there is nothing for a note to qualify.
								 */
								if ( $column_index > 0 ) :
									?>
									<input type="text" class="iflynepal-archive__grid-note" name="<?php echo esc_attr( $base . '[note]' ); ?>" value="<?php echo esc_attr( isset( $column['note'] ) ? $column['note'] : '' ); ?>" placeholder="<?php esc_attr_e( 'Sub-note', 'iflynepal' ); ?>">
								<?php endif; ?>
							</th>
						<?php endfor; ?>
					</tr>
				</thead>
				<tbody data-iflynepal-table-list>
					<?php foreach ( $table['rows'] as $row_index => $row ) : ?>
						<?php $this->render_table_row( $name, (int) $row_index, is_array( $row ) ? $row : array() ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>

			<button type="button" class="button button-secondary iflynepal-archive__row-add" data-iflynepal-table-add>
				<?php esc_html_e( '+ Add Row', 'iflynepal' ); ?>
			</button>

			<?php
			/*
			 * Outside the table on purpose. A <template> keeps its <tr> intact
			 * wherever it sits, but one placed inside a <table> is the sort of
			 * thing that trips a parser, and nothing needs it to be there.
			 */
			?>
			<template data-iflynepal-table-template>
				<?php $this->render_table_row( $name, '__INDEX__', array(), true ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * One row of the comparison table.
	 *
	 * A saved row's cells are read-only so the table reads as a table rather
	 * than as a wall of form controls; the pencil unlocks it. A new row arrives
	 * unlocked, because an editor who has just pressed Add Row wants to type.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $name    Base field name.
	 * @param int|string $index   Row index, or the template placeholder.
	 * @param array      $row     Stored cells, empty for a new row.
	 * @param bool       $editing Whether the row starts unlocked.
	 * @return void
	 */
	private function render_table_row( $name, $index, $row, $editing = false ) {
		$base = $name . '[rows][' . $index . ']';
		?>
		<tr class="iflynepal-archive__row<?php echo $editing ? ' is-editing' : ''; ?>" data-iflynepal-table-row>
			<?php for ( $column_index = 0; $column_index < IFLYNEPAL_ARCHIVE_COMPARE_COLUMNS; $column_index++ ) : ?>
				<td>
					<input type="text" name="<?php echo esc_attr( $base . '[' . $column_index . ']' ); ?>" value="<?php echo esc_attr( isset( $row[ $column_index ] ) ? $row[ $column_index ] : '' ); ?>"<?php echo $editing ? '' : ' readonly'; ?>>
				</td>
			<?php endfor; ?>

			<td class="iflynepal-archive__grid-actions">
				<button type="button" class="button button-small" data-iflynepal-table-edit aria-label="<?php esc_attr_e( 'Edit row', 'iflynepal' ); ?>">&#9998;</button>
				<button type="button" class="button button-small iflynepal-archive__row-remove" data-iflynepal-table-remove aria-label="<?php esc_attr_e( 'Remove row', 'iflynepal' ); ?>">&times;</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Draws the media picker.
	 *
	 * The attachment ID is what is stored, never a URL: a URL breaks the day the
	 * site moves domain, and it loses the alt text and the generated sizes that
	 * a responsive, dimensioned image needs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id            DOM id.
	 * @param string $name          Field name.
	 * @param int    $attachment_id Stored attachment.
	 * @return void
	 */
	private function render_image_control( $id, $name, $attachment_id ) {
		$image = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '';
		?>
		<span class="iflynepal-archive__media" data-iflynepal-media>
			<span class="iflynepal-archive__preview" data-iflynepal-media-preview>
				<?php
				// Built by wp_get_attachment_image(), which escapes its own output.
				echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</span>
			<?php // A repeater row passes no id: its name is unique, and a duplicated id is not. ?>
			<input type="hidden"<?php echo '' === $id ? '' : ' id="' . esc_attr( $id ) . '"'; ?> name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-iflynepal-media-value>
			<button type="button" class="button" data-iflynepal-media-select><?php esc_html_e( 'Choose image', 'iflynepal' ); ?></button>
			<button type="button" class="button-link" data-iflynepal-media-remove<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'iflynepal' ); ?></button>
		</span>
		<?php
	}

	/**
	 * The handful of rules the fields need.
	 *
	 * Printed inline rather than shipped as a stylesheet, the same way the
	 * theme's meta boxes do it: a few declarations that apply on one screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function render_styles() {
		?>
		<style>
			/*
			 * Core caps the term editor at #edittag { max-width: 800px }, which
			 * is right for a screen that is four fields long and tight for one
			 * carrying a whole page's content model.
			 *
			 * Widened rather than uncapped, and centred. Letting it run the full
			 * width of a 1900px monitor gives single-line inputs no one wants to
			 * read across and leaves the form pinned to the left edge; 1100px is
			 * room for two columns of fields and still a measure the eye can
			 * track.
			 */
			#edittag {
				max-width: 1100px;
				margin-left: auto;
				margin-right: auto;
			}

			/*
			 * The page title sits outside the form, so it needs the same measure
			 * or it hangs off the left edge while the form sits in the middle.
			 * Unscoped on purpose: this stylesheet is only ever printed on this
			 * taxonomy's edit screen.
			 */
			.wrap > h1,
			.wrap > #ajax-response {
				max-width: 1100px;
				margin-left: auto;
				margin-right: auto;
			}

			.iflynepal-archive__section {
				margin: 0 0 14px;
				border: 1px solid #dcdcde;
				border-radius: 6px;
				background: #fff;
			}

			.iflynepal-archive__section > summary {
				padding: 14px 18px;
				font-size: 14px;
				font-weight: 600;
				cursor: pointer;
			}

			.iflynepal-archive__section[open] > summary {
				border-bottom: 1px solid #f0f0f1;
			}

			.iflynepal-archive__section > .description {
				padding: 14px 20px 0;
			}

			.iflynepal-archive__section > .form-table {
				padding: 6px 20px 22px;
			}

			/*
			 * Field presentation inside a panel, matching the CloudColleague
			 * meta boxes: the label sits on its own line above a full-width
			 * control rather than in a narrow left-hand column. Long labels stop
			 * wrapping into three lines, and the inputs get the whole width of
			 * the screen instead of two thirds of it.
			 */
			/*
			 * The panel is a responsive grid rather than a single column.
			 *
			 * A full-width panel with one field per row turns a wide screen into
			 * a very long scroll of very wide single-line inputs — worse to work
			 * on than the narrow column it replaced. Short controls pair up
			 * instead, auto-fill decides how many fit, and the fields that
			 * genuinely need the width claim the whole row below.
			 */
			.iflynepal-archive__section > .form-table,
			.iflynepal-archive__section > .form-table > tbody,
			.iflynepal-archive__section > .form-table > tbody > tr,
			.iflynepal-archive__section > .form-table > tbody > tr > th,
			.iflynepal-archive__section > .form-table > tbody > tr > td {
				display: block;
				width: auto;
			}

			.iflynepal-archive__section > .form-table > tbody {
				display: grid;
				gap: 22px 32px;
				grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr));
			}

			/*
			 * Panels stack; a section with no groups has exactly one and the
			 * margin never shows.
			 */
			.iflynepal-archive__panel + .iflynepal-archive__panel {
				margin-top: 20px;
			}

			/*
			 * A grouped panel is a card, drawn like the reason-card repeater's
			 * rows so the two screens read as the same screen. The card is the
			 * grid as well as the box — a wrapper inside a table would be
			 * hoisted out of it by the parser.
			 */
			.iflynepal-archive__panel--card {
				padding: 18px 20px 20px;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				background: #fdfdfd;
			}

			.iflynepal-archive__panel-title {
				grid-column: 1 / -1;
			}

			.iflynepal-archive__section > .form-table .iflynepal-archive__panel-title > th {
				padding: 0 0 10px;
				border-bottom: 1px solid #e6e6e6;
				color: #1d2327;
				font-size: 14px;
				font-weight: 600;
			}

			/* Paragraphs, lists, the repeater and the table get the full row. */
			.iflynepal-archive__field--textarea,
			.iflynepal-archive__field--lines,
			.iflynepal-archive__field--cards,
			.iflynepal-archive__field--table {
				grid-column: 1 / -1;
			}

			/*
			 * A field that named a column is placed in it, which also pushes it
			 * down to the next row when that column is already taken — so the
			 * button label and the button link sit together and the secondary
			 * pair lands on the row below, whatever precedes them.
			 *
			 * Only above the width where the panel actually has two columns:
			 * asking for column 2 of a one-column grid would invent a second
			 * one and halve every control on a narrow screen. 1024px is the
			 * viewport at which the panel is wide enough for the 380px tracks
			 * to pair up, admin menu and page padding accounted for.
			 */
			@media screen and (min-width: 1024px) {
				.iflynepal-archive__field--col-1 {
					grid-column: 1;
				}

				.iflynepal-archive__field--col-2 {
					grid-column: 2;
				}
			}

			.iflynepal-archive__section > .form-table > tbody > tr {
				margin: 0;
			}

			.iflynepal-archive__section > .form-table > tbody > tr > th {
				padding: 0 0 8px;
				color: #1d2327;
				font-weight: 600;
				text-align: left;
			}

			.iflynepal-archive__section > .form-table > tbody > tr > td {
				padding: 0;
			}

			.iflynepal-archive__section .form-table .description {
				margin: 6px 0 0;
			}

			.iflynepal-archive__section .form-table input[type="text"],
			.iflynepal-archive__section .form-table input[type="url"],
			.iflynepal-archive__section .form-table textarea {
				width: 100%;
				padding: 10px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				box-sizing: border-box;
				line-height: 1.5;
			}

			.iflynepal-archive__section .form-table textarea {
				min-height: 110px;
			}

			/* ------------------------------------------- comparison table */

			/*
			 * The panel's layout rules are all direct-child selectors, so none of
			 * them reaches this table and it stays a table. That is why they were
			 * written that way — a descendant selector would block-ify the grid
			 * and leave the comparison as a single column of inputs.
			 */
			/*
			 * The outline is the design's: the scroller carries the border and
			 * the radius, the cells carry hairlines between them, and the head
			 * is separated by a heavier navy-tinted rule rather than by a fill.
			 * Same values as `.compare-scroll` / `.compare thead th` in
			 * retreats-nepal-archive-design.html, so the control looks like the
			 * thing it publishes.
			 */
			.iflynepal-archive__grid-scroll {
				overflow-x: auto;
				border: 1px solid rgba(5, 69, 167, 0.18);
				border-radius: 4px;
				background: #fff;
			}

			.iflynepal-archive__grid {
				width: 100%;
				border-collapse: collapse;
				font-size: 13px;
			}

			.iflynepal-archive__grid th {
				padding: 8px 6px;
				border-bottom: 2px solid rgba(5, 69, 167, 0.18);
				text-align: left;
			}

			/* The heading cells are fields, drawn like every other field. */
			.iflynepal-archive__grid th input {
				width: 100%;
				padding: 6px 8px;
				border: 1px solid #ddd;
				border-radius: 4px;
				background: #fff;
				box-sizing: border-box;
				font-weight: 600;
			}

			.iflynepal-archive__grid-note {
				margin-top: 6px;
				font-size: 12px;
				font-weight: 400 !important;
			}

			.iflynepal-archive__grid-actions {
				width: 84px;
				white-space: nowrap;
			}

			.iflynepal-archive__grid td {
				padding: 4px 6px;
				border-bottom: 1px solid #eee;
			}

			/* The wrapper draws the bottom edge; a last row rule doubles it. */
			.iflynepal-archive__grid tbody tr:last-child td {
				border-bottom: 0;
			}

			/*
			 * A saved row reads as a row of a table; the pencil turns it back
			 * into a row of inputs. Same control as the reference screen.
			 */
			.iflynepal-archive__grid td input {
				width: 100%;
				padding: 6px 8px;
				border: 1px solid transparent;
				border-radius: 4px;
				background: transparent;
				box-sizing: border-box;
			}

			.iflynepal-archive__row.is-editing td input {
				border-color: #bbb;
				background: #fff;
			}

			.iflynepal-archive__row-remove {
				color: #b32d2e;
			}

			/*
			 * Two classes, not one: core's `.wp-core-ui .button { margin: 0 }`
			 * outranks a single-class rule, so a plain .iflynepal-archive__row-add
			 * margin is silently dropped and the button sits on the table's edge.
			 */
			.iflynepal-archive .iflynepal-archive__row-add {
				margin-top: 20px;
			}

			.iflynepal-archive__media {
				display: flex;
				align-items: center;
				gap: 12px;
			}

			.iflynepal-archive__preview img {
				display: block;
				width: 64px;
				height: 64px;
				object-fit: cover;
				border-radius: 3px;
			}

			/* ---------------------------------------------------- card repeater */

			/*
			 * Cards side by side too. Nine of them stacked is a very long
			 * column for what is a three-across grid on the front end.
			 */
			.iflynepal-archive__cards [data-iflynepal-cards-list] {
				display: grid;
				gap: 18px;
				grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
			}

			.iflynepal-archive__card {
				display: flex;
				flex-direction: column;
				margin: 0;
				padding: 16px;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				background: #fdfdfd;
			}

			.iflynepal-archive__card-number {
				margin: 0 0 10px;
				color: #888;
				font-size: 12px;
				font-weight: 600;
			}

			.iflynepal-archive__card-label {
				display: block;
				margin-bottom: 4px;
				color: #1d2327;
				font-size: 12px;
				font-weight: 600;
			}

			.iflynepal-archive__card .iflynepal-archive__media {
				margin-bottom: 12px;
			}

			.iflynepal-archive__card input[type="text"],
			.iflynepal-archive__card textarea {
				width: 100%;
				margin-bottom: 10px;
				padding: 10px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				box-sizing: border-box;
				line-height: 1.5;
			}

			.iflynepal-archive__card textarea {
				min-height: 90px;
				/* Takes up the slack so every card in a row ends level. */
				flex: 1;
			}

			.iflynepal-archive .iflynepal-archive__card-remove {
				align-self: flex-start;
				color: #b32d2e;
			}

			.iflynepal-archive .iflynepal-archive__card-add {
				margin-top: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Stores every field.
	 *
	 * The nonce check is not only a permission check here — it is what stops the
	 * whole model being wiped by any other code that legitimately calls
	 * wp_update_term(). edited_{$taxonomy} fires for those too, and without a
	 * submitted form there are no field values to read, so every field would be
	 * saved empty.
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id Term being saved.
	 * @return void
	 */
	public function save( $term_id ) {
		$nonce_key = self::NONCE . '_nonce';

		if ( ! isset( $_POST[ $nonce_key ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce_key ] ) ), self::NONCE . '_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		/*
		 * Scoped to the sections this term renders, matching what was drawn.
		 * It matters for the checkbox branch below, which reads an absent field
		 * as unticked rather than skipping it: walking the whole schema here
		 * would clear the plan highlight flags of any term that has just been
		 * re-parented into a category, on the save that re-parented it.
		 */
		foreach ( iflynepal_package_type_archive_fields_for_term( $term_id ) as $key => $field ) {
			$meta_key = iflynepal_archive_meta_key( $key );

			if ( 'checkbox' === $field['type'] ) {
				$value = isset( $_POST[ $meta_key ] ) ? '1' : '';
			} elseif ( 'cards' === $field['type'] || 'table' === $field['type'] ) {
				/*
				 * A repeater emptied to nothing submits no field at all, so it
				 * cannot fall through to the `continue` below the way a cleared
				 * text field does — that would make the last card, or the last
				 * table row, impossible to delete. An absent repeater means
				 * zero of them.
				 */
				$raw   = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line, per field.
				$value = iflynepal_archive_sanitize_value( $raw, $field['type'], $field );
			} elseif ( isset( $_POST[ $meta_key ] ) ) {
				$raw   = wp_unslash( $_POST[ $meta_key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized on the next line, by field type.
				$value = iflynepal_archive_sanitize_value( $raw, $field['type'] );
			} else {
				continue;
			}

			if ( '' === $value || '0' === $value || array() === $value ) {
				delete_term_meta( $term_id, $meta_key );

				continue;
			}

			update_term_meta( $term_id, $meta_key, $value );
		}
	}
}

new IFly_Nepal_Package_Type_Archive_Fields();
