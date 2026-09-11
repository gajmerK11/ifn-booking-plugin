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
					title: strings.chooseTitle || 'Choose image',
					button: { text: strings.chooseUse || 'Use this image' },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var size =
						attachment.sizes && attachment.sizes.thumbnail
							? attachment.sizes.thumbnail
							: attachment;

					input.value = attachment.id;
					preview.textContent = '';

					var img = document.createElement('img');
					img.src = size.url;
					img.alt = attachment.alt || '';
					img.width = size.width || 64;
					img.height = size.height || 64;
					preview.appendChild(img);

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

		add.addEventListener('click', function () {
			if (max > 0 && rows().length >= max) {
				return;
			}

			var row = template.content.firstElementChild.cloneNode(true);

			list.appendChild(row);
			renumber();
			refresh();
			initField(row.querySelector('[data-iflynepal-media]'));
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
})();
