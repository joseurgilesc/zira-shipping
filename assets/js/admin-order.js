/**
 * Zira Shipping — Admin order: refresco AJAX del visor.
 *
 * @package Zira_Shipping
 * @since   2.0.0
 */

/* global jQuery, ajaxurl */
(function ($) {
	'use strict';

	function refreshMetabox() {
		var $box    = $('#zira-shipping-metabox-content');
		var orderId = $('#post_ID').val();
		var nonce   = $('#zira_shipping_metabox_nonce').val();

		if (!orderId || !nonce || !$box.length) return;

		$box.addClass('zira-loading');
		$.post(ajaxurl, {
			action: 'zira_shipping_refresh_metabox',
			order_id: orderId,
			nonce: nonce,
		}, function (res) {
			$box.removeClass('zira-loading');
			if (res.success && res.data.html) $box.html(res.data.html);
		}).fail(function () { $box.removeClass('zira-loading'); });
	}

	$(document).ready(function () {

		$(document).on('click', '.calculate-action', function () {
			setTimeout(refreshMetabox, 1000);
		});

		$(document.body).on('wc_order_items_reloaded wc_order_items_reload', function () {
			setTimeout(refreshMetabox, 600);
		});

		function initAdminCitySelect() {
			$('#_billing_city, #_shipping_city').each(function () {
				var $el = $(this);
				if ($el.length && $el.is('select') && !$el.data('select2')) {
					$el.select2({ width: '100%', allowClear: false });
				}
			});
		}

		$(document.body).on('wc_order_items_reload wc_order_items_reloaded', function () {
			setTimeout(initAdminCitySelect, 500);
		});
		initAdminCitySelect();

		$('<style>.zira-loading{opacity:.4;pointer-events:none}</style>').appendTo('head');
	});

})(jQuery);
