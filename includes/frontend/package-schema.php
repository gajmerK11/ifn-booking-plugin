<?php
/**
 * Structured data for the package pages, added to Yoast SEO's graph.
 *
 * Yoast describes a package as a plain WebPage. This file adds what the page
 * actually sells — a TouristTrip with its price, itinerary, destination and
 * photos, plus a FAQPage — read from the same stored fields the page renders, so
 * the markup cannot disagree with what a visitor sees. It also makes the
 * BreadcrumbList follow the package's own type path, the trail the page and the
 * URL already use.
 *
 * The output is part of the server-rendered JSON-LD block, so it is safe behind
 * the Cloudflare page cache. It runs only while Yoast's schema filters fire.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The @id of the first graph piece of a type.
 *
 * @since 1.0.0
 *
 * @param array  $graph Graph pieces.
 * @param string $type  schema.org type.
 * @return string The @id, or an empty string.
 */
function iflynepal_package_schema_piece_id( $graph, $type ) {
	foreach ( $graph as $piece ) {
		if ( isset( $piece['@type'], $piece['@id'] ) && in_array( $type, (array) $piece['@type'], true ) ) {
			return $piece['@id'];
		}
	}

	return '';
}

/**
 * A stored price as a plain number string.
 *
 * Prices are free text ("USD 1,250.50"), so this keeps digits and the decimal
 * point only.
 *
 * @since 1.0.0
 *
 * @param string $value Stored price.
 * @return float Zero when there is no usable number.
 */
function iflynepal_package_schema_number( $value ) {
	return (float) preg_replace( '/[^0-9.]/', '', (string) $value );
}

/**
 * The price a package is quoted from.
 *
 * The page's own price first; with none, the cheapest group-size tier, which is
 * the "from" price a visitor sees when the group is large.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return float Zero when the package has no price.
 */
function iflynepal_package_schema_price( $post_id ) {
	$price = iflynepal_package_schema_number( iflynepal_package_field( $post_id, 'price_amount' ) );

	if ( $price > 0 ) {
		return $price;
	}

	$lowest = 0.0;

	foreach ( iflynepal_package_price_tiers( $post_id ) as $tier ) {
		if ( 0.0 === $lowest || $tier['price'] < $lowest ) {
			$lowest = $tier['price'];
		}
	}

	return $lowest;
}

/**
 * The package's photos as ImageObject nodes: the featured image, then the gallery.
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @param int $limit   Most images to return.
 * @return array[] ImageObject nodes.
 */
function iflynepal_package_schema_images( $post_id, $limit = 6 ) {
	$ids = array_merge( array( (int) get_post_thumbnail_id( $post_id ) ), iflynepal_package_gallery( $post_id ) );
	$out = array();

	foreach ( array_unique( array_filter( $ids ) ) as $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );

		if ( ! $src ) {
			continue;
		}

		$node = array(
			'@type'      => 'ImageObject',
			'url'        => $src[0],
			'contentUrl' => $src[0],
			'width'      => (int) $src[1],
			'height'     => (int) $src[2],
		);

		$caption = trim( wp_strip_all_tags( wp_get_attachment_caption( $id ) ) );

		if ( '' !== $caption ) {
			$node['caption'] = $caption;
		}

		$out[] = $node;

		if ( count( $out ) >= $limit ) {
			break;
		}
	}

	return $out;
}

/**
 * The itinerary as an ItemList, one entry per day (or per week).
 *
 * @since 1.0.0
 *
 * @param int $post_id Package.
 * @return array ItemList node, or an empty array when there is no itinerary.
 */
function iflynepal_package_schema_itinerary( $post_id ) {
	$cards = iflynepal_package_cards( $post_id, 'itinerary_days' );

	if ( ! $cards ) {
		$cards = iflynepal_package_cards( $post_id, 'itinerary_weeks' );
	}

	$items    = array();
	$position = 0;

	foreach ( $cards as $card ) {
		$title = trim( wp_strip_all_tags( (string) $card['title'] ) );

		if ( '' === $title ) {
			continue;
		}

		$item = array(
			'@type'    => 'ListItem',
			'position' => ++$position,
			'name'     => $title,
		);

		$summary = trim( wp_strip_all_tags( (string) $card['summary'] ) );

		if ( '' !== $summary ) {
			$item['description'] = $summary;
		}

		$items[] = $item;
	}

	if ( ! $items ) {
		return array();
	}

	return array(
		'@type'           => 'ItemList',
		'itemListElement' => $items,
	);
}

/**
 * Adds the package's TouristTrip, destination and FAQPage to the graph.
 *
 * @since 1.0.0
 *
 * @param array $graph Graph pieces.
 * @return array
 */
function iflynepal_package_schema_graph( $graph ) {
	if ( ! is_singular( IFLYNEPAL_PACKAGE_POST_TYPE ) ) {
		return $graph;
	}

	$post_id   = (int) get_queried_object_id();
	$permalink = get_permalink( $post_id );
	$page_id   = iflynepal_package_schema_piece_id( $graph, 'WebPage' );
	$org_id    = iflynepal_package_schema_piece_id( $graph, 'Organization' );

	$name = trim( wp_strip_all_tags( iflynepal_package_field( $post_id, 'heading' ) ) );

	$trip = array(
		'@type' => 'TouristTrip',
		'@id'   => $permalink . '#trip',
		'name'  => '' !== $name ? $name : get_the_title( $post_id ),
		'url'   => $permalink,
	);

	$description = trim( wp_strip_all_tags( iflynepal_package_field( $post_id, 'overview_intro' ) ) );

	if ( '' === $description ) {
		$description = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
	}

	if ( '' !== $description ) {
		$trip['description'] = wp_trim_words( $description, 60, '…' );
	}

	if ( $page_id ) {
		$trip['mainEntityOfPage'] = array( '@id' => $page_id );
	}

	if ( $org_id ) {
		$trip['provider'] = array( '@id' => $org_id );
	}

	$images = iflynepal_package_schema_images( $post_id );

	if ( $images ) {
		$trip['image'] = $images;
	}

	$price = iflynepal_package_schema_price( $post_id );

	if ( $price > 0 ) {
		$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', iflynepal_package_field( $post_id, 'price_currency' ) ) );

		/*
		 * No availability is stated: the plugin deliberately holds no inventory
		 * (see package-details-schema.php), and marking a package InStock would
		 * claim a fact the site does not keep.
		 */
		$trip['offers'] = array(
			'@type'         => 'Offer',
			'price'         => (string) $price,
			'priceCurrency' => 3 === strlen( $currency ) ? $currency : 'USD',
			'url'           => $permalink,
		);
	}

	$itinerary = iflynepal_package_schema_itinerary( $post_id );

	if ( $itinerary ) {
		$trip['itinerary'] = $itinerary;
	}

	$place = trim( iflynepal_package_field( $post_id, 'map_place' ) );

	if ( '' !== $place ) {
		$destination = array(
			'@type' => 'TouristDestination',
			'@id'   => $permalink . '#destination',
			'name'  => $place,
		);

		$note = trim( iflynepal_package_field( $post_id, 'map_note' ) );
		$map  = trim( iflynepal_package_field( $post_id, 'map_link' ) );

		if ( '' !== $note ) {
			$destination['description'] = $note;
		}

		if ( '' !== $map ) {
			$destination['hasMap'] = esc_url_raw( $map );
		}

		$graph[] = $destination;

		$trip['itinerary'] = isset( $trip['itinerary'] )
			? array( $trip['itinerary'], array( '@id' => $destination['@id'] ) )
			: array( '@id' => $destination['@id'] );
	}

	$graph[] = $trip;

	$faq = array();

	foreach ( iflynepal_package_cards( $post_id, 'faq_items' ) as $card ) {
		$question = trim( wp_strip_all_tags( (string) $card['q'] ) );
		$answer   = trim( wp_strip_all_tags( (string) $card['a'] ) );

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$faq[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( $faq ) {
		$piece = array(
			'@type'      => 'FAQPage',
			'@id'        => $permalink . '#faq',
			'mainEntity' => $faq,
		);

		if ( $page_id ) {
			$piece['isPartOf'] = array( '@id' => $page_id );
		}

		$graph[] = $piece;
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'iflynepal_package_schema_graph' );

/**
 * Makes Yoast's breadcrumb follow the package type path.
 *
 * Without this a package reads Home > Packages > Title, while the page and the
 * URL read Home > Wilderness Adventure > Title. The trail is the one
 * iflynepal_package_the_breadcrumb() prints. A package type archive gets the
 * same walk up its own ancestors.
 *
 * @since 1.0.0
 *
 * @param array $crumbs Yoast breadcrumb links, each with 'url' and 'text'.
 * @return array
 */
function iflynepal_package_schema_breadcrumbs( $crumbs ) {
	$is_package = is_singular( IFLYNEPAL_PACKAGE_POST_TYPE );

	if ( ! $is_package && ! is_tax( IFLYNEPAL_PACKAGE_TAXONOMY ) ) {
		return $crumbs;
	}

	$object = get_queried_object();
	$term   = $is_package ? iflynepal_package_primary_type( $object->ID ) : $object;
	$trail  = array();

	if ( $term instanceof WP_Term ) {
		$trail = array_reverse( get_ancestors( $term->term_id, IFLYNEPAL_PACKAGE_TAXONOMY, 'taxonomy' ) );

		if ( $is_package ) {
			$trail[] = $term->term_id;
		}
	}

	$links = array( reset( $crumbs ) );

	foreach ( $trail as $term_id ) {
		$ancestor = get_term( $term_id, IFLYNEPAL_PACKAGE_TAXONOMY );
		$url      = $ancestor instanceof WP_Term ? get_term_link( $ancestor ) : '';

		if ( $ancestor instanceof WP_Term && ! is_wp_error( $url ) ) {
			$links[] = array(
				'url'  => $url,
				'text' => $ancestor->name,
			);
		}
	}

	$links[] = $is_package
		? array(
			'url'  => get_permalink( $object->ID ),
			'text' => get_the_title( $object->ID ),
		)
		: array(
			'url'  => get_term_link( $object ),
			'text' => $object->name,
		);

	return $links;
}
add_filter( 'wpseo_breadcrumb_links', 'iflynepal_package_schema_breadcrumbs' );
