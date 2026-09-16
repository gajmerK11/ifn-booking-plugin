<?php
/**
 * One facet of the archive's filters.
 *
 * Drawn in two places and two shapes from one file: Activity as a row of pills
 * above the card grid, Duration and Budget as rows in the rail beside it. The
 * shape is the facet's own 'style', decided in iflynepal_archive_facets(), so
 * moving a facet between the two is one word there rather than a second copy
 * of this markup.
 *
 * The contract assets/js/archive/filters.js reads is all here and does not
 * change with the style: a .iflynepal-filter-group carrying data-facet, and
 * .iflynepal-filter-btn children carrying data-filter, is-active and
 * aria-pressed. The script collects every group on the page, so a group in the
 * rail and a group above the grid combine with AND exactly as two groups in
 * one panel would.
 *
 * A pill carries no count, and a row does. A pill is sized by its own label,
 * so a number inside it lands somewhere different on every pill and there is
 * no column for the eye to read down — which is the whole use of a count. The
 * number is still computed either way, because it is what decides whether the
 * option is offered at all.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 *
 * @var array $args Passed by iflynepal_booking_get_part(). Holds 'facet', one
 *                  entry from iflynepal_archive_facets().
 */

defined( 'ABSPATH' ) || exit;

$iflynepal_facet = isset( $args['facet'] ) ? $args['facet'] : null;

if ( ! is_array( $iflynepal_facet ) || empty( $iflynepal_facet['options'] ) ) {
	return;
}

$iflynepal_pills = 'pills' === $iflynepal_facet['style'];

/*
 * Sub-headings, when a facet's options come in more than one unit — Duration's
 * Days and Weeks. Empty on every other facet and on a Duration facet where only
 * one unit is on offer, in which case nothing below draws a heading and the
 * list is exactly what it always was.
 */
$iflynepal_unit_names = isset( $iflynepal_facet['unit_names'] ) ? $iflynepal_facet['unit_names'] : array();
$iflynepal_unit_drawn = '';

/*
 * The option this request arrives already asking for, from the query string a
 * type archive's "view all" button carried over. Pressed server-side rather
 * than by the script on load, so the markup is correct in the first frame and
 * the control never flickers from All to the real choice. filters.js reads the
 * active button as its starting state, so the grid follows on its own.
 */
$iflynepal_selected = isset( $iflynepal_facet['selected'] ) ? $iflynepal_facet['selected'] : '';
$iflynepal_all_on   = '' === $iflynepal_selected;
?>

<div class="iflynepal-filter-group" data-facet="<?php echo esc_attr( $iflynepal_facet['facet'] ); ?>">
	<span class="iflynepal-filter-group__label"><?php echo esc_html( $iflynepal_facet['label'] ); ?></span>
	<div class="iflynepal-filter-row<?php echo $iflynepal_pills ? ' iflynepal-filter-row--pills' : ''; ?>" role="group" aria-label="<?php echo esc_attr( $iflynepal_facet['aria'] ); ?>">
		<?php
		/*
		 * One "All" for the whole facet, above both blocks rather than one per
		 * block. Two of them would be two ways to say the same thing, and with
		 * one of the two blocks narrowed the other's "All" would sit there
		 * looking pressed while narrowing nothing.
		 */
		?>
		<button class="iflynepal-filter-btn<?php echo $iflynepal_all_on ? ' is-active' : ''; ?>" type="button" data-filter="all" aria-pressed="<?php echo $iflynepal_all_on ? 'true' : 'false'; ?>">
			<span class="iflynepal-filter-btn__label"><?php esc_html_e( 'All', 'iflynepal' ); ?></span>
			<?php if ( ! $iflynepal_pills ) : ?>
				<span class="iflynepal-filter-btn__count"><?php echo esc_html( number_format_i18n( $iflynepal_facet['total'] ) ); ?></span>
			<?php endif; ?>
		</button>
		<?php
		foreach ( $iflynepal_facet['options'] as $iflynepal_option ) :
			/*
			 * The run is cut on the unit *changing*, in the order the buckets
			 * come in, rather than by collecting every option of a unit
			 * together: the order is the editor's to set at Packages →
			 * Settings, and a unit appearing twice is a bucket list somebody
			 * has interleaved by mistake — worth seeing on the page rather
			 * than silently tidied away. The same rule §5.3g uses for the
			 * grouped fields on the term screen.
			 */
			$iflynepal_unit = isset( $iflynepal_option['unit'] ) ? $iflynepal_option['unit'] : '';

			if ( $iflynepal_unit_names && $iflynepal_unit !== $iflynepal_unit_drawn && isset( $iflynepal_unit_names[ $iflynepal_unit ] ) ) :
				$iflynepal_unit_drawn = $iflynepal_unit;
				?>
				<span class="iflynepal-filter-row__unit"><?php echo esc_html( $iflynepal_unit_names[ $iflynepal_unit ] ); ?></span>
				<?php
			endif;
			?>
			<?php $iflynepal_on = $iflynepal_option['key'] === $iflynepal_selected; ?>
			<button class="iflynepal-filter-btn<?php echo $iflynepal_on ? ' is-active' : ''; ?>" type="button" data-filter="<?php echo esc_attr( $iflynepal_option['key'] ); ?>" aria-pressed="<?php echo $iflynepal_on ? 'true' : 'false'; ?>">
				<span class="iflynepal-filter-btn__label"><?php echo esc_html( $iflynepal_option['label'] ); ?></span>
				<?php if ( ! $iflynepal_pills ) : ?>
					<span class="iflynepal-filter-btn__count"><?php echo esc_html( number_format_i18n( $iflynepal_option['count'] ) ); ?></span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>
</div>
