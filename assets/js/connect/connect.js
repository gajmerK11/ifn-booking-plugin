/**
 * The "Connect With Us" drawer.
 *
 * The panel is a real section of the page in the markup and the tab is a plain
 * anchor pointing at it — see the note at the top of
 * templates/parts/connect-form.php. Everything here is the enhancement on top of
 * that: the section becomes a dialog, the tab opens it instead of jumping to it,
 * and focus is kept inside it while it is open.
 *
 * Vanilla, deferred, no dependency on anything the theme loads.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-iflynepal-connect]' );

	if ( ! root ) {
		return;
	}

	var panel = root.querySelector( '.iflynepal-connect__panel' );
	var tab = root.querySelector( '[data-iflynepal-connect-open]' );

	if ( ! panel || ! tab ) {
		return;
	}

	var body = document.body;
	var isOpen = false;

	/*
	 * The panel only becomes a modal dialog once there is something able to
	 * close it. Announcing the role in the markup would tell a screen reader the
	 * rest of the page is inert on a page where the script never ran and it is
	 * not.
	 */
	panel.setAttribute( 'role', 'dialog' );
	panel.setAttribute( 'aria-modal', 'true' );

	/*
	 * The tab stops being a link to a section and starts being a control that
	 * opens one, so it says so. aria-controls names the panel it acts on;
	 * aria-expanded is kept in step by open() and close().
	 */
	tab.setAttribute( 'role', 'button' );
	tab.setAttribute( 'aria-controls', 'iflynepal-connect-panel' );
	tab.setAttribute( 'aria-expanded', 'false' );

	/*
	 * The close controls and the backdrop are rendered `hidden`, because without
	 * this script the panel is an ordinary section and a close button on a
	 * section that cannot be closed is a dead control. They are unhidden the
	 * moment the script that gives them meaning runs.
	 */
	var closers = root.querySelectorAll( '[data-iflynepal-connect-close]' );

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
	 * Opens the drawer.
	 *
	 * The class goes on one frame after the panel is shown, the way the gallery
	 * lightbox and the enquiry modal do it: setting both in one frame gives the
	 * browser a single style resolution, leaves no earlier transform to animate
	 * from, and the slide never plays.
	 *
	 * @return {void}
	 */
	function open() {
		if ( isOpen ) {
			return;
		}

		isOpen = true;
		root.classList.add( 'is-shown' );
		body.classList.add( 'iflynepal-connect-lock' );
		tab.setAttribute( 'aria-expanded', 'true' );

		window.requestAnimationFrame( function () {
			root.classList.add( 'is-open' );

			/*
			 * After a submission the visitor is sent back here and needs to be
			 * told what happened, so the notice takes focus rather than the
			 * panel — a notice nobody is focused on is one a screen reader never
			 * announces. Otherwise the panel takes it: it carries the dialog
			 * role and the label, so focusing it announces the title, where the
			 * first field would open a phone keyboard before the visitor has
			 * read anything.
			 */
			var notice = root.querySelector( '.iflynepal-connect__notice' );

			( notice || panel ).focus();
		} );
	}

	/**
	 * Closes the drawer and hands focus back to the tab.
	 *
	 * Focus always goes to the tab rather than to whatever was last focused:
	 * the tab is the only thing that opens this, so it is where a keyboard
	 * visitor needs to be standing to open it again.
	 *
	 * @return {void}
	 */
	function close() {
		if ( ! isOpen ) {
			return;
		}

		isOpen = false;
		root.classList.remove( 'is-open' );
		body.classList.remove( 'iflynepal-connect-lock' );
		tab.setAttribute( 'aria-expanded', 'false' );

		/*
		 * Display is taken away only once the slide out has played. The guard
		 * matters: a visitor who reopens inside those 340ms would otherwise have
		 * the drawer hidden out from under them by a timer belonging to the
		 * close before it.
		 */
		window.setTimeout( function () {
			if ( ! isOpen ) {
				root.classList.remove( 'is-shown' );
			}
		}, 340 );

		tab.focus();
	}

	tab.addEventListener( 'click', function ( event ) {
		event.preventDefault();
		open();
	} );

	/*
	 * A link given role="button" is expected to answer Space as well as Enter,
	 * which an anchor does not do on its own. Enter already works, and the
	 * default for Space on a focused anchor is to scroll the page.
	 */
	tab.addEventListener( 'keydown', function ( event ) {
		if ( ' ' === event.key || 'Spacebar' === event.key ) {
			event.preventDefault();
			open();
		}
	} );

	/*
	 * Anything else on the page can open the drawer by carrying the attribute —
	 * a "Talk to us" button in the footer, say — without this script having to
	 * know it exists.
	 */
	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-iflynepal-connect-open]' );

		if ( ! trigger || trigger === tab ) {
			return;
		}

		event.preventDefault();
		open();
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
	 * visitor filled in is gone with the page that held it. Reopening the drawer
	 * is what puts the result in front of them; the attribute is only rendered
	 * when there is a notice to show.
	 */
	if ( root.hasAttribute( 'data-iflynepal-connect-open-now' ) ) {
		open();
	}
} )();
