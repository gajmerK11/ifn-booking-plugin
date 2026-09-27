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

		/*
		 * The notice belongs to the submission that just happened, not to the
		 * form itself. Left in the markup, reopening afterwards would show a
		 * visitor the outcome of a request they already saw — dismissing it is
		 * what "closed" means here, same as the notice going away is what
		 * fixing the reload was for.
		 */
		var notice = root.querySelector( '.iflynepal-enquiry__notice' );

		if ( notice ) {
			notice.remove();
		}

		window.setTimeout( function () {
			if ( ! isOpen ) {
				root.classList.remove( 'is-shown' );
			}
		}, 260 );

		if ( lastFocused && document.contains( lastFocused ) ) {
			lastFocused.focus();
		}
	}

	/*
	 * Progressive enhancement: the form posts to admin-post.php and works
	 * without this at all — the page redirects back with a status and the
	 * server-rendered notice above takes it from there. When the localized
	 * config and fetch are both available, a valid submit goes to
	 * admin-ajax.php instead, using the same fields admin-post.php would have
	 * received (the action name matches a wp_ajax_/wp_ajax_nopriv_ hook, so
	 * nothing in the form markup has to change), and the result is shown
	 * without closing the form or navigating anywhere.
	 */
	var form = panel.querySelector( '.iflynepal-enquiry__form' );
	var submit = form ? form.querySelector( '.iflynepal-enquiry__submit' ) : null;
	var canAjax = 'undefined' !== typeof iflynepalEnquiryForm && window.fetch && window.FormData;
	var fadeTimer = null;

	/**
	 * Fades a notice out and drops it, the same few seconds after showing it
	 * whether it came from a fresh AJAX submission or was already in the
	 * markup on load. Closing the form removes it immediately regardless —
	 * see close() — so this only ever fires while a visitor is still reading.
	 *
	 * @param {Element} notice The notice to fade.
	 * @return {void}
	 */
	function scheduleNoticeFade( notice ) {
		window.clearTimeout( fadeTimer );

		fadeTimer = window.setTimeout( function () {
			notice.style.transition = 'opacity .3s ease';
			notice.style.opacity = '0';

			window.setTimeout( function () {
				notice.remove();
			}, 300 );
		}, 6000 );
	}

	/**
	 * Shows one result in the notice's usual spot, between the header and the
	 * form, creating it if a submission has not already left one there.
	 *
	 * @param {string} type    'success' or 'error'.
	 * @param {string} message The text to show.
	 * @return {void}
	 */
	function showNotice( type, message ) {
		var notice = root.querySelector( '.iflynepal-enquiry__notice' );

		if ( ! notice ) {
			notice = document.createElement( 'p' );
			notice.id = 'iflynepal-enquiry-notice';
			notice.setAttribute( 'tabindex', '-1' );
			panel.insertBefore( notice, form );
		}

		notice.style.transition = '';
		notice.style.opacity = '';
		notice.className = 'iflynepal-enquiry__notice iflynepal-enquiry__notice--' + type;
		notice.setAttribute( 'role', 'error' === type ? 'alert' : 'status' );
		notice.textContent = message;
		notice.focus();
		scheduleNoticeFade( notice );
	}

	if ( form && submit ) {
		form.addEventListener( 'submit', function ( event ) {
			if ( ! canAjax ) {
				return;
			}

			event.preventDefault();

			var originalHtml = submit.innerHTML;

			submit.disabled = true;
			submit.textContent = iflynepalEnquiryForm.sendingLabel;

			fetch( iflynepalEnquiryForm.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams( new FormData( form ) )
			} )
				.then( function ( response ) { return response.json(); } )
				.then( function ( result ) {
					var data = result && result.data ? result.data : null;

					if ( ! data ) {
						showNotice( 'error', iflynepalEnquiryForm.networkMessage );
						return;
					}

					showNotice( data.type, data.message );

					if ( result.success ) {
						form.reset();
					}
				} )
				.catch( function () {
					showNotice( 'error', iflynepalEnquiryForm.networkMessage );
				} )
				.then( function () {
					submit.disabled = false;
					submit.innerHTML = originalHtml;
				} );
		} );
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

		var openNowNotice = root.querySelector( '.iflynepal-enquiry__notice' );

		if ( openNowNotice ) {
			scheduleNoticeFade( openNowNotice );
		}

		/*
		 * The status is in the URL itself, so a plain reload — no new
		 * submission, just the visitor pressing F5 — would ask the server for
		 * the same URL and get the same notice back, reopening the form
		 * forever. Clearing the query arg and the hash from the address bar
		 * without a navigation means the next reload asks for the page with
		 * nothing to report.
		 */
		if ( window.history && window.history.replaceState ) {
			var url = new URL( window.location.href );
			url.searchParams.delete( 'iflynepal_enquiry' );
			url.hash = '';
			window.history.replaceState( null, '', url.toString() );
		}
	}
} )();
