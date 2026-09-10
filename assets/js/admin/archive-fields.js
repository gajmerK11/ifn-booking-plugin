/**
 * Media picker for the archive content fields on the Edit Package Type screen.
 *
 * Vanilla, no jQuery, self-initialising, one file for one feature — the same
 * shape as the theme's own scripts. wp.media is core's and is already on the
 * page by the time this runs, because the screen enqueues it.
 *
 * @package IFly_Nepal
 * @since   1.0.0
 */

(function () {
	'use strict';

	var strings = window.iflynepalArchiveFields || {};

	/**
	 * Wires one picker.
	 *
	 * @param {HTMLElement} field The [data-iflynepal-media] wrapper.
	 */
	function initField(field) {
		var input = field.querySelector('[data-iflynepal-media-value]');
		var preview = field.querySelector('[data-iflynepal-media-preview]');
		var select = field.querySelector('[data-iflynepal-media-select]');
		var remove = field.querySelector('[data-iflynepal-media-remove]');
		var frame = null;

		if (!input || !preview || !select || !remove) {
			return;
		}

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

	document.querySelectorAll('[data-iflynepal-media]').forEach(initField);
})();
