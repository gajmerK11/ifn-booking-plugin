<?php
/**
 * The comparison table.
 *
 * Columns 2 to 4 are drawn only if they have a heading, so the table can be a
 * two- or three-column comparison without a second template. A row whose first
 * cell is empty is left out.
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

// Which columns exist at all is decided by their headings.
$iflynepal_columns = array();

for ( $iflynepal_c = 1; $iflynepal_c <= 4; $iflynepal_c++ ) {
	$iflynepal_heading = iflynepal_archive_field( $iflynepal_id, 'compare_col_' . $iflynepal_c );

	if ( '' === $iflynepal_heading ) {
		continue;
	}

	$iflynepal_columns[] = array(
		'index' => $iflynepal_c,
		'label' => $iflynepal_heading,
		'note'  => $iflynepal_c > 1 ? iflynepal_archive_field( $iflynepal_id, 'compare_col_' . $iflynepal_c . '_note' ) : '',
	);
}

if ( count( $iflynepal_columns ) < 2 ) {
	return;
}

$iflynepal_rows = array();

for ( $iflynepal_r = 1; $iflynepal_r <= IFLYNEPAL_ARCHIVE_COMPARE_SLOTS; $iflynepal_r++ ) {
	if ( '' === iflynepal_archive_field( $iflynepal_id, 'compare_row_' . $iflynepal_r . '_col_1' ) ) {
		continue;
	}

	$iflynepal_cells = array();

	foreach ( $iflynepal_columns as $iflynepal_column ) {
		$iflynepal_cells[] = iflynepal_archive_field( $iflynepal_id, 'compare_row_' . $iflynepal_r . '_col_' . $iflynepal_column['index'] );
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
							<th scope="col"<?php echo 2 === $iflynepal_column['index'] ? ' class="is-us"' : ''; ?>>
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
