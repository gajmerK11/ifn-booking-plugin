/**
 * The trip-finder length options control — add and remove rows.
 *
 * The same contract the archive and package repeaters follow: the row markup
 * lives in a <template> rather than in a string here, so a saved row and a new
 * one are the same markup with no second copy to drift — and a <template>'s
 * inputs are not submitted, so an unused one cannot post an empty row.
 *
 * Indices are re-derived from position on every add and remove, never
 * incremented, so deleting the second of four rows cannot leave a gap in the
 * posted array.
 *
 * Vanilla, no jQuery, loaded only on the settings screen.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-iflynepal-durations]' );

	if ( ! root ) {
		return;
	}

	var list = root.querySelector( '[data-iflynepal-durations-list]' );
	var add = root.querySelector( '[data-iflynepal-durations-add]' );
	var template = root.querySelector( '[data-iflynepal-durations-template]' );
	var max = parseInt( root.getAttribute( 'data-iflynepal-durations-max' ), 10 ) || 8;

	if ( ! list || ! add || ! template ) {
		return;
	}

	/**
	 * Renumbers every row's input names, and keeps the buttons honest.
	 *
	 * @return {void}
	 */
	function renumber() {
		var rows = list.querySelectorAll( '[data-iflynepal-durations-row]' );

		Array.prototype.forEach.call( rows, function ( row, index ) {
			Array.prototype.forEach.call( row.querySelectorAll( '[name]' ), function ( field ) {
				field.name = field.name.replace( /\[\d+\]|\[__INDEX__\]/, '[' + index + ']' );
			} );

			var number = row.querySelector( '[data-iflynepal-durations-number]' );

			if ( number ) {
				number.textContent = String( index + 1 );
			}
		} );

		add.hidden = rows.length >= max;

		/*
		 * The last row cannot be removed. An empty list is a picker with no
		 * lengths in it at all, which renders as a dropdown holding only its
		 * placeholder — and the way back from that is not obvious from the
		 * screen. Clearing the field is done by emptying the row, not by
		 * deleting it.
		 */
		Array.prototype.forEach.call(
			list.querySelectorAll( '[data-iflynepal-durations-remove]' ),
			function ( button ) {
				button.disabled = rows.length < 2;
			}
		);
	}

	add.addEventListener( 'click', function () {
		if ( list.querySelectorAll( '[data-iflynepal-durations-row]' ).length >= max ) {
			return;
		}

		list.appendChild( template.content.cloneNode( true ) );
		renumber();

		/*
		 * Focus the new row's first control. Without this the page has silently
		 * grown a row somewhere below the button that was just pressed, and a
		 * keyboard visitor is still standing on Add.
		 */
		var rows = list.querySelectorAll( '[data-iflynepal-durations-row]' );
		var last = rows[ rows.length - 1 ];
		var first = last ? last.querySelector( 'select' ) : null;

		if ( first ) {
			first.focus();
		}
	} );

	list.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-iflynepal-durations-remove]' );

		if ( ! button || button.disabled ) {
			return;
		}

		var row = button.closest( '[data-iflynepal-durations-row]' );

		if ( row ) {
			row.remove();
			renumber();
		}
	} );

	/*
	 * The preview is the same sentence the picker will show, worked out in the
	 * browser as the selects change so the wording can be read before saving.
	 * It mirrors iflynepal_trip_finder_duration_label(), which is the authority
	 * — this is a convenience, and the server's answer is what publishes.
	 */
	list.addEventListener( 'change', function ( event ) {
		var row = event.target.closest( '[data-iflynepal-durations-row]' );

		if ( ! row ) {
			return;
		}

		var preview = row.querySelector( '[data-iflynepal-durations-preview]' );
		var selects = row.querySelectorAll( 'select' );

		if ( ! preview || selects.length < 2 ) {
			return;
		}

		var min = parseInt( selects[ 0 ].value, 10 );
		var rawMax = selects[ 1 ].value;
		var maxValue = '' === rawMax ? null : parseInt( rawMax, 10 );

		if ( ! min ) {
			preview.textContent = '';
			return;
		}

		if ( null === maxValue ) {
			preview.textContent = min + '+ days';
		} else if ( maxValue <= min ) {
			preview.textContent = min + ( 1 === min ? ' day' : ' days' );
		} else {
			preview.textContent = min + '–' + maxValue + ' days';
		}
	} );

	renumber();
} )();
