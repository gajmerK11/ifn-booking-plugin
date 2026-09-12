/**
 * The package gallery picker.
 *
 * A multi-select wp.media frame, a grid of thumbnails, and one hidden input
 * holding the whole list as comma-separated attachment IDs. The input is the
 * only thing that is submitted — the grid is a view of it, rewritten from the
 * list on every change, so what is on screen and what will be saved cannot
 * drift apart.
 *
 * Order is meaningful: it is the order the photographs appear in on the page, so
 * thumbnails can be dragged into place. Native HTML drag and drop, no library.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

( function () {
	'use strict';

	var strings = window.iflynepalPackageGallery || {};

	function init( wrap ) {
		var input = wrap.querySelector( '[data-iflynepal-gallery-value]' );
		var list = wrap.querySelector( '[data-iflynepal-gallery-list]' );
		var add = wrap.querySelector( '[data-iflynepal-gallery-add]' );
		var max = parseInt( wrap.dataset.max, 10 ) || 0;
		var frame = null;
		var dragging = null;

		if ( ! input || ! list || ! add ) {
			return;
		}

		function ids() {
			return input.value.split( ',' ).filter( function ( id ) {
				return '' !== id;
			} );
		}

		function write( next ) {
			input.value = next.join( ',' );
		}

		/*
		 * The list is the truth; the DOM follows it. Rows are read back off the
		 * grid after a drag and written into the input, never the other way
		 * round, so there is one direction of flow to reason about.
		 */
		function sync() {
			write( Array.prototype.slice.call( list.querySelectorAll( '[data-iflynepal-gallery-item]' ) ).map( function ( item ) {
				return item.dataset.id;
			} ) );
		}

		add.addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}

			// One frame, reopened rather than rebuilt.
			if ( ! frame ) {
				frame = window.wp.media( {
					title: strings.chooseTitle || 'Add images',
					button: { text: strings.chooseUse || 'Add to gallery' },
					library: { type: 'image' },
					multiple: 'add'
				} );

				frame.on( 'select', function () {
					var current = ids();

					frame.state().get( 'selection' ).each( function ( attachment ) {
						var id = String( attachment.id );

						/*
						 * Picking the same photograph twice is a mistake, not an
						 * instruction to show it twice.
						 */
						if ( current.indexOf( id ) !== -1 ) {
							return;
						}

						if ( max > 0 && current.length >= max ) {
							return;
						}

						current.push( id );
						list.appendChild( item( id, attachment ) );
					} );

					write( current );
				} );
			}

			frame.open();
		} );

		function item( id, attachment ) {
			var li = document.createElement( 'li' );
			var img = document.createElement( 'img' );
			var remove = document.createElement( 'button' );
			var sizes = attachment.attributes.sizes || {};

			li.className = 'iflynepal-gallery__item';
			li.setAttribute( 'data-iflynepal-gallery-item', '' );
			li.dataset.id = id;
			li.draggable = true;

			img.src = sizes.thumbnail ? sizes.thumbnail.url : attachment.attributes.url;
			img.alt = '';

			remove.type = 'button';
			remove.className = 'iflynepal-gallery__remove';
			remove.setAttribute( 'data-iflynepal-gallery-remove', '' );
			remove.setAttribute( 'aria-label', strings.remove || 'Remove image' );
			remove.innerHTML = '&times;';

			li.appendChild( img );
			li.appendChild( remove );

			return li;
		}

		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-iflynepal-gallery-remove]' );

			if ( ! button ) {
				return;
			}

			button.closest( '[data-iflynepal-gallery-item]' ).remove();
			sync();
		} );

		/* ------------------------------------------------------ reordering */

		Array.prototype.slice.call( list.querySelectorAll( '[data-iflynepal-gallery-item]' ) ).forEach( function ( li ) {
			li.draggable = true;
		} );

		list.addEventListener( 'dragstart', function ( event ) {
			dragging = event.target.closest( '[data-iflynepal-gallery-item]' );

			if ( ! dragging ) {
				return;
			}

			dragging.classList.add( 'is-dragging' );
			// Firefox will not start a drag unless something is written here.
			event.dataTransfer.setData( 'text/plain', dragging.dataset.id );
			event.dataTransfer.effectAllowed = 'move';
		} );

		list.addEventListener( 'dragover', function ( event ) {
			var over = event.target.closest( '[data-iflynepal-gallery-item]' );

			if ( ! dragging || ! over || over === dragging ) {
				return;
			}

			// Without this the drop is refused and nothing moves.
			event.preventDefault();

			/*
			 * Inserted before or after the thumbnail under the cursor depending
			 * on which half of it the cursor is in, so a photograph can be
			 * dropped at either end of the row it is over.
			 */
			var box = over.getBoundingClientRect();
			var after = event.clientX > box.left + ( box.width / 2 );

			list.insertBefore( dragging, after ? over.nextSibling : over );
		} );

		list.addEventListener( 'drop', function ( event ) {
			event.preventDefault();
		} );

		list.addEventListener( 'dragend', function () {
			if ( ! dragging ) {
				return;
			}

			dragging.classList.remove( 'is-dragging' );
			dragging = null;
			sync();
		} );
	}

	document.querySelectorAll( '[data-iflynepal-gallery]' ).forEach( init );
}() );
