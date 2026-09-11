<?php
/**
 * The FAQ.
 *
 * <details>/<summary>, not a scripted accordion: the open/closed behaviour, the
 * keyboard handling and the announcement are all native, and the answers are in
 * the page for search engines whether or not they are open.
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

// A question with no answer is half a thought; both have to be there.
$iflynepal_entries = array();

foreach ( iflynepal_archive_cards( $iflynepal_id, 'faq_items' ) as $iflynepal_item ) {
	$iflynepal_question = isset( $iflynepal_item['q'] ) ? (string) $iflynepal_item['q'] : '';
	$iflynepal_answer   = isset( $iflynepal_item['a'] ) ? (string) $iflynepal_item['a'] : '';

	if ( '' === $iflynepal_question || '' === $iflynepal_answer ) {
		continue;
	}

	$iflynepal_entries[] = array(
		'question' => $iflynepal_question,
		'answer'   => $iflynepal_answer,
	);
}

if ( empty( $iflynepal_entries ) ) {
	return;
}

$iflynepal_eyebrow = iflynepal_archive_field( $iflynepal_id, 'faq_eyebrow' );
$iflynepal_heading = iflynepal_archive_field( $iflynepal_id, 'faq_heading' );
?>

<section class="iflynepal-section iflynepal-faq" id="iflynepal-faq">
	<div class="iflynepal-container iflynepal-faq__layout">
		<div class="iflynepal-faq__head">
			<?php if ( '' !== $iflynepal_eyebrow ) : ?>
				<span class="iflynepal-eyebrow"><?php echo esc_html( $iflynepal_eyebrow ); ?></span>
			<?php endif; ?>

			<?php if ( '' !== $iflynepal_heading ) : ?>
				<h2><?php iflynepal_archive_the_heading( $iflynepal_id, 'faq_heading' ); ?></h2>
			<?php endif; ?>
		</div>

		<div class="iflynepal-faq__list">
			<?php foreach ( $iflynepal_entries as $iflynepal_index => $iflynepal_entry ) : ?>
				<details<?php echo 0 === $iflynepal_index ? ' open' : ''; ?>>
					<summary><?php echo esc_html( $iflynepal_entry['question'] ); ?></summary>
					<p><?php echo esc_html( $iflynepal_entry['answer'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
