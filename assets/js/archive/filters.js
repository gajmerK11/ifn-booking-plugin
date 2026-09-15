/**
 * Card filtering on a package type archive.
 *
 * Every card is already in the page; this only hides the ones that do not match
 * the chosen category. That is why the grid is complete and correct with
 * JavaScript off, and why the filter row is only drawn when there is a child
 * category worth choosing between.
 *
 * Cards are hidden with a class that sets display:none, which takes them out of
 * the accessibility tree as well as out of the layout. The switch is instant —
 * no animation of any kind, on the cards or on the grid around them.
 *
 * 🔴 This used to reflow with GSAP's Flip: record every card's position, switch
 * the classes, animate the survivors from where they used to sit to their new
 * slot, with the grid's own height separately tweened so the page beneath it
 * held still while that happened. Client-directed removal after several rounds
 * of chasing motion it kept producing regardless of what was tuned — cards
 * visibly lifting from below on entry, the grid itself dipping and springing
 * back on a filter pair that changes no cards at all. Both were genuine
 * mechanisms of `absolute: true`, which pulls every tracked card out of normal
 * flow for the length of its own animation whether or not that card actually
 * moves, and each fix for one symptom left another. A plain, instant class
 * toggle has none of that machinery and therefore nothing left to misfire: the
 * grid reflows once, natively, the moment the classes change.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var row = document.querySelector( '.iflynepal-filter-row' );
	var grid = document.querySelector( '.iflynepal-cards' );

	if ( ! row || ! grid ) {
		return;
	}

	var buttons = Array.prototype.slice.call( row.querySelectorAll( '.iflynepal-filter-btn' ) );
	var cards = Array.prototype.slice.call( grid.querySelectorAll( '.iflynepal-card' ) );

	/*
	 * The "view all <category>" links under the grid. One per filter button,
	 * matched on the same slug, and there is none for "all" — so pressing All
	 * matches none of them and the block closes, which is the state the page
	 * ships in.
	 */
	var foot = document.querySelector( '.iflynepal-listing__foot' );
	var links = foot ? Array.prototype.slice.call( foot.querySelectorAll( '.iflynepal-listing__all' ) ) : [];

	function matches( card, slug ) {
		var categories = ( card.dataset.categories || '' ).split( ' ' );

		return 'all' === slug || categories.indexOf( slug ) !== -1;
	}

	function hideUnmatched( slug ) {
		cards.forEach( function ( card ) {
			card.classList.toggle( 'is-hidden', ! matches( card, slug ) );
		} );
	}

	function press( slug ) {
		buttons.forEach( function ( button ) {
			var active = button.dataset.filter === slug;

			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );

		var current = null;

		links.forEach( function ( link ) {
			var isCurrent = link.dataset.filter === slug;

			link.classList.toggle( 'is-current', isCurrent );

			if ( isCurrent ) {
				current = link;
			}
		} );

		if ( foot ) {
			foot.classList.toggle( 'is-open', null !== current );
		}
	}

	function apply( slug ) {
		press( slug );
		hideUnmatched( slug );
	}

	row.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.iflynepal-filter-btn' );

		if ( button ) {
			apply( button.dataset.filter || 'all' );
		}
	} );
}() );
