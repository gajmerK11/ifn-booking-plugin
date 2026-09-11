<?php
/**
 * The comparison table.
 *
 * Columns 2 to 4 are drawn only if they have a heading, so the table can be a
 * two- or three-column comparison without a second template. A row whose first
 * cell is empty is left out — an unlabelled row is a stray, not a blank line
 * somebody meant to publish.
 *
 * A real <table> with <th scope> on both axes, not a grid of divs: this is
 * tabular data and a screen reader has to be able to say which column a cell
 * belongs to. It sits in its own horizontally scrollable wrapper so the page
 * body never scrolls sideways on a phone.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'term'.
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_term = isset( $args['term'] ) ? $args['term'] : null;

if ( ! $iflynepal_term instanceof WP_Term ) {
	return;
}

$iflynepal_id = $iflynepal_term->term_id;

$iflynepal_table = iflynepal_archive_table( $iflynepal_id, 'compare_table' );

/*
 * Which columns exist at all is decided by their headings.
 *
 * The stored columns are a fixed run of four, blanks included, so that a column
 * an editor has not named keeps its position rather than shifting the cells of
 * the ones after it. Dropping the unnamed ones here is what lets the same model
 * publish a two-, three- or four-column comparison with no second template.
 */
$iflynepal_columns = array();

foreach ( $iflynepal_table['columns'] as $iflynepal_index => $iflynepal_column ) {
	$iflynepal_label = isset( $iflynepal_column['label'] ) ? (string) $iflynepal_column['label'] : '';

	if ( '' === $iflynepal_label ) {
		continue;
	}

	$iflynepal_columns[] = array(
		'index' => (int) $iflynepal_index,
		'label' => $iflynepal_label,
		'note'  => isset( $iflynepal_column['note'] ) ? (string) $iflynepal_column['note'] : '',
	);
}

if ( count( $iflynepal_columns ) < 2 ) {
	return;
}

$iflynepal_rows = array();

foreach ( $iflynepal_table['rows'] as $iflynepal_row ) {
	if ( ! is_array( $iflynepal_row ) || ! isset( $iflynepal_row[0] ) || '' === $iflynepal_row[0] ) {
		continue;
	}

	$iflynepal_cells = array();

	foreach ( $iflynepal_columns as $iflynepal_column ) {
		$iflynepal_cells[] = isset( $iflynepal_row[ $iflynepal_column['index'] ] ) ? (string) $iflynepal_row[ $iflynepal_column['index'] ] : '';
	}

	$iflynepal_rows[] = $iflynepal_cells;
}

if ( empty( $iflynepal_rows ) ) {
	return;
}

$iflynepal_footnote = iflynepal_archive_field( $iflynepal_id, 'compare_footnote' );
?>

<section class="iflynepal-section iflynepal-compare" id="iflynepal-compare">
	<div class="iflynepal-container">
		<?php iflynepal_archive_the_head( $iflynepal_id, 'compare' ); ?>

		<div class="iflynepal-compare__scroll">
			<table class="iflynepal-compare__table">
				<thead>
					<tr>
						<?php foreach ( $iflynepal_columns as $iflynepal_column ) : ?>
							<th scope="col"<?php echo 1 === $iflynepal_column['index'] ? ' class="is-us"' : ''; ?>>
								<?php echo esc_html( $iflynepal_column['label'] ); ?>
								<?php if ( '' !== $iflynepal_column['note'] ) : ?>
									<span class="iflynepal-compare__note"><?php echo esc_html( $iflynepal_column['note'] ); ?></span>
								<?php endif; ?>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $iflynepal_rows as $iflynepal_row ) : ?>
						<tr>
							<?php foreach ( $iflynepal_row as $iflynepal_index => $iflynepal_cell ) : ?>
								<?php if ( 0 === $iflynepal_index ) : ?>
									<th scope="row"><?php echo esc_html( $iflynepal_cell ); ?></th>
								<?php else : ?>
									<td<?php echo 1 === $iflynepal_index ? ' class="is-us"' : ''; ?>><?php echo esc_html( $iflynepal_cell ); ?></td>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( '' !== $iflynepal_footnote ) : ?>
			<p class="iflynepal-compare__foot"><?php echo esc_html( $iflynepal_footnote ); ?></p>
		<?php endif; ?>
	</div>
</section>
