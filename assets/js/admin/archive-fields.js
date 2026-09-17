/**
 * The archive content fields on the Edit Package Type screen.
 *
 * Three features: the media picker used by every image field, the card repeater
 * used by the reason cards, and the comparison table.
 *
 * Vanilla, no jQuery, self-initialising — the same shape as the theme's own
 * scripts. wp.media is core's and is already on the page by the time this runs,
 * because the screen enqueues it.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

(function () {
	'use strict';

	var strings = window.iflynepalArchiveFields || {};

	/* ------------------------------------------------------- media picker */

	/**
	 * Wires one picker.
	 *
	 * Guarded against running twice: a repeater row is wired when it is added,
	 * and a second pass over the whole screen must not stack a second listener
	 * on it.
	 *
	 * @param {HTMLElement} field The [data-iflynepal-media] wrapper.
	 */
	function initField(field) {
		if (!field || field.dataset.iflynepalMediaReady === '1') {
			return;
		}

		var input = field.querySelector('[data-iflynepal-media-value]');
		var preview = field.querySelector('[data-iflynepal-media-preview]');
		var select = field.querySelector('[data-iflynepal-media-select]');
		var remove = field.querySelector('[data-iflynepal-media-remove]');
		// Every picker on this screen but one is an image picker.
		var type = field.dataset.iflynepalMediaType || 'image';
		var frame = null;

		if (!input || !preview || !select || !remove) {
			return;
		}

		field.dataset.iflynepalMediaReady = '1';

		select.addEventListener('click', function () {
			if (!window.wp || !window.wp.media) {
				return;
			}

			// One frame per field, reopened rather than rebuilt.
			if (!frame) {
				frame = window.wp.media({
					title: field.dataset.iflynepalMediaTitle || strings.chooseTitle || 'Choose image',
					button: {
						text: field.dataset.iflynepalMediaButton || strings.chooseUse || 'Use this image'
					},
					library: { type: type },
					multiple: false
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();

					input.value = attachment.id;
					preview.textContent = '';

					/*
					 * A video has no thumbnail sizes to pick from, so it previews as
					 * the file itself with its controls — which is also the only way
					 * to check the right clip was chosen without leaving the editor.
					 */
					if ('video' === type) {
						var video = document.createElement('video');

						video.src = attachment.url;
						video.controls = true;
						video.preload = 'metadata';
						preview.appendChild(video);
					} else {
						var size =
							attachment.sizes && attachment.sizes.thumbnail
								? attachment.sizes.thumbnail
								: attachment;
						var img = document.createElement('img');

						img.src = size.url;
						img.alt = attachment.alt || '';
						img.width = size.width || 64;
						img.height = size.height || 64;
						preview.appendChild(img);
					}

					remove.hidden = false;
				});
			}

			frame.open();
		});

		remove.addEventListener('click', function () {
			input.value = '';
			preview.textContent = '';
			remove.hidden = true;
		});
	}

	/* ------------------------------------------------------ card repeater */

	/**
	 * Wires one repeater.
	 *
	 * @param {HTMLElement} wrap The [data-iflynepal-cards] wrapper.
	 */
	function initCards(wrap) {
		var list = wrap.querySelector('[data-iflynepal-cards-list]');
		var add = wrap.querySelector('[data-iflynepal-cards-add]');
		var template = wrap.querySelector('[data-iflynepal-cards-template]');
		var max = parseInt(wrap.dataset.max, 10) || 0;

		if (!list || !add || !template) {
			return;
		}

		function rows() {
			return Array.prototype.slice.call(list.querySelectorAll('[data-iflynepal-card]'));
		}

		/**
		 * Puts the row numbering and the input names back in order.
		 *
		 * Both are derived from the row's position every time, never tracked, so
		 * removing the second of five cards cannot leave a gap in the numbering
		 * or in the array the form posts. Only the FIRST bracketed number in a
		 * name is the row index — the ones after it are field names.
		 */
		function renumber() {
			rows().forEach(function (row, i) {
				var label = row.querySelector('[data-iflynepal-card-number]');

				if (label) {
					label.textContent = (wrap.dataset.label || strings.cardLabel || 'Card %d').replace('%d', i + 1);
				}

				row.querySelectorAll('input, textarea').forEach(function (input) {
					var name = input.getAttribute('name');

					if (name) {
						input.setAttribute('name', name.replace(/\[(?:\d+|__INDEX__)\]/, '[' + i + ']'));
					}
				});
			});
		}

		// The button goes away at the cap rather than failing on click.
		function refresh() {
			add.hidden = max > 0 && rows().length >= max;
		}

		/*
		 * The nested timeline repeater has to be able to ask for this: a row it
		 * adds is named from the card's own <template>, which still carries the
		 * __INDEX__ placeholder for the card, and only this function knows what
		 * number that card now is.
		 */
		wrap.iflynepalRenumber = renumber;

		add.addEventListener('click', function () {
			if (max > 0 && rows().length >= max) {
				return;
			}

			var row = template.content.firstElementChild.cloneNode(true);

			list.appendChild(row);
			renumber();
			refresh();
			initField(row.querySelector('[data-iflynepal-media]'));
			row.querySelectorAll('[data-iflynepal-timeline]').forEach(initTimeline);
		});

		list.addEventListener('click', function (event) {
			var button = event.target.closest('[data-iflynepal-card-remove]');

			if (!button) {
				return;
			}

			button.closest('[data-iflynepal-card]').remove();
			renumber();
			refresh();
		});

		renumber();
		refresh();
	}

	/* ------------------------------------------------- timeline repeater */

	/**
	 * Wires one day's timeline: a repeater of stops inside a card repeater.
	 *
	 * Same contract as the flat repeaters — a <template> row, indices re-derived
	 * from position, the Add button hidden at the cap — with ordering on top,
	 * because the order of a timeline is its meaning. A row moves by drag, and by
	 * the arrow buttons, which are the half of that a keyboard can reach.
	 *
	 * @param {HTMLElement} wrap The [data-iflynepal-timeline] wrapper.
	 */
	function initTimeline(wrap) {
		if (!wrap || wrap.dataset.iflynepalTimelineReady === '1') {
			return;
		}

		var list = wrap.querySelector('[data-iflynepal-timeline-list]');
		var add = wrap.querySelector('[data-iflynepal-timeline-add]');
		var template = wrap.querySelector('[data-iflynepal-timeline-template]');
		var max = parseInt(wrap.dataset.max, 10) || 0;
		var dragging = null;

		if (!list || !add || !template) {
			return;
		}

		wrap.dataset.iflynepalTimelineReady = '1';

		function rows() {
			return Array.prototype.slice.call(list.querySelectorAll('[data-iflynepal-timeline-row]'));
		}

		/**
		 * Puts the posted row indices back in order.
		 *
		 * The index to rewrite is the LAST one — the numbers before it belong to
		 * the card this timeline sits inside, and rewriting one of those would
		 * move a stop onto another day.
		 */
		function renumber() {
			rows().forEach(function (row, i) {
				row.querySelectorAll('input').forEach(function (input) {
					var name = input.getAttribute('name');

					if (name) {
						input.setAttribute(
							'name',
							name.replace(/\[(?:\d+|__INDEX__)\]\[(time|text)\]$/, '[' + i + '][$1]')
						);
					}
				});
			});

			/*
			 * A row added to a card that was itself just added is still named with
			 * the card's __INDEX__ placeholder, and only the card repeater knows
			 * what to put there.
			 */
			var cards = wrap.closest('[data-iflynepal-cards]');

			if (cards && typeof cards.iflynepalRenumber === 'function') {
				cards.iflynepalRenumber();
			}
		}

		// The button goes away at the cap rather than failing on click.
		function refresh() {
			add.hidden = max > 0 && rows().length >= max;
		}

		function move(row, delta) {
			var all = rows();
			var from = all.indexOf(row);
			var to = from + delta;

			if (from < 0 || to < 0 || to >= all.length) {
				return;
			}

			if (delta < 0) {
				list.insertBefore(row, all[to]);
			} else {
				list.insertBefore(row, all[to].nextSibling);
			}

			renumber();
		}

		add.addEventListener('click', function () {
			if (max > 0 && rows().length >= max) {
				return;
			}

			var row = template.content.firstElementChild.cloneNode(true);

			list.appendChild(row);
			renumber();
			refresh();

			var first = row.querySelector('input');

			if (first) {
				first.focus();
			}
		});

		list.addEventListener('click', function (event) {
			var row = event.target.closest('[data-iflynepal-timeline-row]');

			if (!row) {
				return;
			}

			if (event.target.closest('[data-iflynepal-timeline-remove]')) {
				row.remove();
				renumber();
				refresh();

				return;
			}

			if (event.target.closest('[data-iflynepal-timeline-up]')) {
				move(row, -1);

				return;
			}

			if (event.target.closest('[data-iflynepal-timeline-down]')) {
				move(row, 1);
			}
		});

		/*
		 * Enter adds the next stop instead of submitting the post.
		 *
		 * The editor is one form, and a form submits on Enter from a text input.
		 * Typing a stop and pressing Enter would otherwise save and reload the
		 * whole screen mid-edit.
		 */
		list.addEventListener('keydown', function (event) {
			if ('Enter' !== event.key || 'INPUT' !== event.target.tagName) {
				return;
			}

			event.preventDefault();
			add.click();
		});

		/* ------------------------------------------------------------ drag */

		list.addEventListener('dragstart', function (event) {
			var row = event.target.closest('[data-iflynepal-timeline-row]');

			if (!row) {
				return;
			}

			dragging = row;
			row.classList.add('is-dragging');

			// Firefox starts no drag at all unless something is put on the transfer.
			if (event.dataTransfer) {
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData('text/plain', '');
			}
		});

		list.addEventListener('dragover', function (event) {
			var over = event.target.closest('[data-iflynepal-timeline-row]');

			if (!dragging || !over || over === dragging) {
				return;
			}

			// Without this the drop never happens: the default is to refuse it.
			event.preventDefault();

			var box = over.getBoundingClientRect();
			var after = event.clientY > box.top + box.height / 2;

			list.insertBefore(dragging, after ? over.nextSibling : over);
		});

		list.addEventListener('dragend', function () {
			if (!dragging) {
				return;
			}

			dragging.classList.remove('is-dragging');
			dragging = null;
			renumber();
		});

		renumber();
		refresh();
	}

	/* ---------------------------------------------------- comparison table */

	/**
	 * Wires the comparison table.
	 *
	 * The same repeater contract as the cards — a <template> row, indices
	 * re-derived from position, the Add button hidden at the cap — with one
	 * addition: a saved row is read-only until its pencil is pressed, so the
	 * grid reads as the table it will publish rather than as a block of inputs.
	 *
	 * @param {HTMLElement} wrap The [data-iflynepal-table] wrapper.
	 */
	function initTable(wrap) {
		var list = wrap.querySelector('[data-iflynepal-table-list]');
		var add = wrap.querySelector('[data-iflynepal-table-add]');
		var template = wrap.querySelector('[data-iflynepal-table-template]');
		var max = parseInt(wrap.dataset.max, 10) || 0;

		if (!list || !add || !template) {
			return;
		}

		function rows() {
			return Array.prototype.slice.call(list.querySelectorAll('[data-iflynepal-table-row]'));
		}

		/**
		 * Puts the posted row indices back in order.
		 *
		 * The row number is the one inside [rows][…]; the number after it is the
		 * column and must survive untouched, which is why this matches the
		 * literal [rows] rather than the first bracketed number the way the card
		 * repeater does.
		 */
		function renumber() {
			rows().forEach(function (row, i) {
				row.querySelectorAll('input').forEach(function (input) {
					var name = input.getAttribute('name');

					if (name) {
						input.setAttribute('name', name.replace(/\[rows\]\[(?:\d+|__INDEX__)\]/, '[rows][' + i + ']'));
					}
				});
			});
		}

		// The button goes away at the cap rather than failing on click.
		function refresh() {
			add.hidden = max > 0 && rows().length >= max;
		}

		function setEditing(row, on) {
			row.classList.toggle('is-editing', on);
			row.querySelectorAll('input').forEach(function (input) {
				input.readOnly = !on;
			});
		}

		function focusFirst(row) {
			var first = row.querySelector('input');

			if (first) {
				first.focus();
			}
		}

		add.addEventListener('click', function () {
			if (max > 0 && rows().length >= max) {
				return;
			}

			var row = template.content.firstElementChild.cloneNode(true);

			list.appendChild(row);
			renumber();
			refresh();
			setEditing(row, true);
			focusFirst(row);
		});

		list.addEventListener('click', function (event) {
			var remove = event.target.closest('[data-iflynepal-table-remove]');

			if (remove) {
				remove.closest('[data-iflynepal-table-row]').remove();
				renumber();
				refresh();

				return;
			}

			var edit = event.target.closest('[data-iflynepal-table-edit]');

			if (edit) {
				var row = edit.closest('[data-iflynepal-table-row]');

				setEditing(row, true);
				focusFirst(row);
			}
		});

		/*
		 * Enter finishes the row instead of submitting the term.
		 *
		 * The term editor is one form, and a form submits on Enter from a text
		 * input. Without this, typing the last cell of a row and pressing Enter
		 * saves and reloads the whole screen mid-edit.
		 */
		list.addEventListener('keydown', function (event) {
			if ('Enter' !== event.key || 'INPUT' !== event.target.tagName) {
				return;
			}

			event.preventDefault();

			var row = event.target.closest('[data-iflynepal-table-row]');

			if (row) {
				setEditing(row, false);
			}
		});

		renumber();
		refresh();
	}

	document.querySelectorAll('[data-iflynepal-media]').forEach(initField);
	document.querySelectorAll('[data-iflynepal-cards]').forEach(initCards);
	document.querySelectorAll('[data-iflynepal-table]').forEach(initTable);
	document.querySelectorAll('[data-iflynepal-timeline]').forEach(initTimeline);

	/* --------------------------------------------- wa.me link auto-fill */

	/**
	 * Keeps a link field in step with a number-or-label field beside it,
	 * writing a wa.me click-to-chat URL into the link for as long as the
	 * link is still the one this wrote.
	 *
	 * The moment an editor types into the link field themselves, it is
	 * theirs: this stops touching it, the same way it would if they had
	 * pasted in an unrelated URL. A no-op wherever either field does not
	 * exist, which is every admin screen this pairing was not built for.
	 *
	 * @param {string} sourceId Id of the field holding the phone number.
	 * @param {string} linkId   Id of the field the wa.me URL is written into.
	 */
	function initWaLinkPair(sourceId, linkId) {
		var source = document.getElementById(sourceId);
		var link = document.getElementById(linkId);

		if (!source || !link) {
			return;
		}

		function waLink(value) {
			var digits = value.replace(/\D/g, '');

			return digits.length >= 7 ? 'https://wa.me/' + digits : '';
		}

		// Already in step on load (freshly created, or last saved from the
		// source as it stands now) counts as auto — anything else is an
		// editor's own link, left alone from the start.
		var auto = '' === link.value || link.value === waLink(source.value);

		// A source field can start the page with a value already in it — the
		// WhatsApp number's own default, e.g. — with nothing yet written to
		// the link. Auto mode says that link is this pairing's to fill, so it
		// is filled immediately rather than waiting for the source to be
		// typed into.
		if (auto && '' === link.value) {
			link.value = waLink(source.value);
		}

		link.addEventListener('input', function () {
			auto = '' === link.value || link.value === waLink(source.value);
		});

		source.addEventListener('input', function () {
			if (auto) {
				link.value = waLink(source.value);
			}
		});
	}

	// The expert card's button (label "usually the phone number", per
	// expert_label in package-details-schema.php) and the price card's
	// Inquire link (fed from the package's own WhatsApp number field).
	initWaLinkPair('iflynepal_package_page_expert_label', 'iflynepal_package_page_expert_link');
	initWaLinkPair('iflynepal_package_page_whatsapp_number', 'iflynepal_package_page_inquire_link');
})();
