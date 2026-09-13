/**
 * The enquiry form's modal behaviour.
 *
 * The form is a real section of the page in the markup — see the note at the top
 * of templates/parts/enquiry-form.php. Everything here is the enhancement on top
 * of that: the section becomes a dialog, the triggers open it instead of jumping
 * to it, and focus is kept inside it while it is open.
 *
 * Vanilla, deferred, no dependency on anything the theme loads.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-iflynepal-enquiry]' );

	if ( ! root ) {
		return;
	}

	var panel = root.querySelector( '.iflynepal-enquiry__panel' );
	var body = document.body;
	var lastFocused = null;
	var isOpen = false;

	/*
	 * The close controls and the backdrop are rendered `hidden`, because without
	 * this script the form is an ordinary section and a close button on a
	 * section that cannot be closed is a dead control. They are unhidden the
	 * moment the script that gives them meaning runs.
	 */
	var closers = root.querySelectorAll( '[data-iflynepal-enquiry-close]' );

	Array.prototype.forEach.call( closers, function ( el ) {
		el.hidden = false;
		el.addEventListener( 'click', close );
	} );

	/**
	 * Every element inside the panel that can take focus.
	 *
	 * Re-read on each Tab rather than cached: the notice is only in the markup
	 * after a submission, and a cached list taken at load would not hold it.
	 *
	 * @return {Element[]} Focusable elements, in document order.
	 */
	function focusable() {
		var nodes = panel.querySelectorAll(
			'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);

		return Array.prototype.filter.call( nodes, function ( el ) {
			return ! el.hidden && null !== el.offsetParent;
		} );
	}

	/**
	 * Opens the form.
	 *
	 * The class goes on one frame after the element is shown, the way the
	 * gallery lightbox does it: setting both in one frame gives the browser a
	 * single style resolution, leaves no earlier value to animate from, and the
	 * fade never plays.
	 *
	 * @param {Element|null} trigger The control that asked for it.
	 * @return {void}
	 */
	function open( trigger ) {
		if ( isOpen ) {
			return;
		}

		lastFocused = trigger || document.activeElement;
		isOpen = true;
		root.classList.add( 'is-shown' );
		body.classList.add( 'iflynepal-enquiry-lock' );

		window.requestAnimationFrame( function () {
			root.classList.add( 'is-open' );

			/*
			 * After a submission the visitor is sent back here and needs to be
			 * told what happened, so the notice takes focus rather than the
			 * first field — a notice nobody is focused on is one a screen
			 * reader never announces. Otherwise the panel takes it: it carries
			 * the dialog role and the label, so focusing it announces the
			 * title, where the first field would open a phone keyboard before
			 * the visitor has read anything.
			 */
			var notice = root.querySelector( '.iflynepal-enquiry__notice' );
			var target = notice || panel;

			if ( target ) {
				target.focus();
			}
		} );
	}

	/**
	 * Closes the form and hands focus back to whatever opened it.
	 *
	 * @return {void}
	 */
	function close() {
		if ( ! isOpen ) {
			return;
		}

		isOpen = false;
		root.classList.remove( 'is-open' );
		body.classList.remove( 'iflynepal-enquiry-lock' );

		window.setTimeout( function () {
			if ( ! isOpen ) {
				root.classList.remove( 'is-shown' );
			}
		}, 260 );

		if ( lastFocused && document.contains( lastFocused ) ) {
			lastFocused.focus();
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-iflynepal-enquiry-open], a[href="#iflynepal-enquiry"]' );

		if ( ! trigger ) {
			return;
		}

		event.preventDefault();
		open( trigger );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( ! isOpen ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			close();
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		var items = focusable();

		if ( ! items.length ) {
			return;
		}

		var first = items[ 0 ];
		var last = items[ items.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	/*
	 * A submission redirects back to the page it came from, and the form the
	 * visitor filled in is gone with the page that held it. Reopening it is what
	 * puts the result in front of them; the attribute is only rendered when
	 * there is a notice to show.
	 */
	if ( root.hasAttribute( 'data-iflynepal-enquiry-open-now' ) ) {
		open( null );
	}
} )();
