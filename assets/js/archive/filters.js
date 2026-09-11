/**
 * Card filtering on a package type archive.
 *
 * Every card is already in the page; this only hides the ones that do not match
 * the chosen category. That is why the grid is complete and correct with
 * JavaScript off, and why the filter row is only drawn when there is more than
 * one child category to choose between.
 *
 * Cards are hidden with the `hidden` attribute rather than a style, so they
 * leave the accessibility tree as well as the layout.
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

	function apply( slug ) {
		cards.forEach( function ( card ) {
			var categories = ( card.dataset.categories || '' ).split( ' ' );
			var matches = 'all' === slug || categories.indexOf( slug ) !== -1;

			card.hidden = ! matches;
		} );

		buttons.forEach( function ( button ) {
			var active = button.dataset.filter === slug;

			button.classList.toggle( 'is-active', active );
			button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
	}

	row.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.iflynepal-filter-btn' );

		if ( button ) {
			apply( button.dataset.filter || 'all' );
		}
	} );
}() );
