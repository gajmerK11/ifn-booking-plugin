/**
 * The trip-finder range controls (length and budget options) — add and remove
 * rows.
 *
 * The row markup lives in a <template> rather than in a string here, so a saved
 * row and a new one are the same markup, and a <template>'s inputs are never
 * submitted. Indices are re-derived from position on every add and remove, never
 * incremented, so deleting the second of four rows cannot leave a gap in the
 * posted array.
 *
 * Vanilla, no jQuery, loaded only on the settings screen.
 */
( function () {
	'use strict';

	Array.prototype.forEach.call( document.querySelectorAll( '[data-iflynepal-ranges]' ), function ( root ) {
		var list = root.querySelector( '[data-iflynepal-ranges-list]' );
		var add = root.querySelector( '[data-iflynepal-ranges-add]' );
		var template = root.querySelector( '[data-iflynepal-ranges-template]' );
		var max = parseInt( root.getAttribute( 'data-iflynepal-ranges-max' ), 10 ) || 8;

		if ( ! list || ! add || ! template ) {
			return;
		}

		/**
		 * Renumbers every row's input names and keeps the Add button honest.
		 *
		 * @return {void}
		 */
		function renumber() {
			var rows = list.querySelectorAll( '[data-iflynepal-ranges-row]' );

			Array.prototype.forEach.call( rows, function ( row, index ) {
				Array.prototype.forEach.call( row.querySelectorAll( '[name]' ), function ( field ) {
					field.name = field.name.replace( /\[\d+\]|\[__INDEX__\]/, '[' + index + ']' );
				} );

				var number = row.querySelector( '[data-iflynepal-ranges-number]' );

				if ( number ) {
					number.textContent = String( index + 1 );
				}
			} );

			add.hidden = rows.length >= max;
		}

		add.addEventListener( 'click', function () {
			if ( list.querySelectorAll( '[data-iflynepal-ranges-row]' ).length >= max ) {
				return;
			}

			list.appendChild( template.content.cloneNode( true ) );
			renumber();

			var rows = list.querySelectorAll( '[data-iflynepal-ranges-row]' );
			var last = rows[ rows.length - 1 ];
			var first = last ? last.querySelector( 'input' ) : null;

			if ( first ) {
				first.focus();
			}
		} );

		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-iflynepal-ranges-remove]' );

			if ( ! button ) {
				return;
			}

			var row = button.closest( '[data-iflynepal-ranges-row]' );

			if ( row ) {
				row.remove();
				renumber();
			}
		} );

		renumber();
	} );
} )();
