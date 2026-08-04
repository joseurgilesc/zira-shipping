/**
 * Zira Shipping — Admin order: refresco AJAX del visor + auto-provincia.
 *
 * @package Zira_Shipping
 * @since   2.0.0
 */

/* global jQuery, ajaxurl */
(function ($) {
	'use strict';

	/**
	 * Mapa de provincia → código de estado WC Ecuador.
	 */
	var PROVINCE_TO_STATE = {
		'AZUAY':              'EC-A',
		'BOLIVAR':            'EC-B',
		'CANAR':              'EC-F',
		'CARCHI':             'EC-C',
		'CHIMBORAZO':         'EC-H',
		'COTOPAXI':           'EC-X',
		'EL ORO':             'EC-O',
		'ESMERALDAS':         'EC-E',
		'GALAPAGOS':          'EC-W',
		'GUAYAS':             'EC-G',
		'IMBABURA':           'EC-I',
		'LOJA':               'EC-L',
		'LOS RIOS':           'EC-R',
		'MANABI':             'EC-M',
		'MORONA SANTIAGO':    'EC-S',
		'NAPO':               'EC-N',
		'ORELLANA':           'EC-D',
		'PASTAZA':            'EC-Y',
		'PICHINCHA':          'EC-P',
		'SANTA ELENA':        'EC-SE',
		'SANTO DOMINGO':      'EC-SD',
		'SUCUMBIOS':          'EC-U',
		'TUNGURAHUA':         'EC-T',
		'ZAMORA':             'EC-Z',
		'ZAMORA CHINCHIPE':   'EC-Z',
	};

	/**
	 * Auto-llenar provincia al seleccionar ciudad (admin order).
	 */
	function bindCityToProvince() {
		$(document.body).off('.ziraAdminProv');
		$(document.body).on('change.ziraAdminProv select2:select.ziraAdminProv', '#_billing_city, #_shipping_city', function (e) {
			var val      = $(this).val() || '';
			var parts    = val.split('-');
			var province = parts.length > 1 ? parts[parts.length - 1].trim() : '';
			var stateCode = PROVINCE_TO_STATE[province.toUpperCase()] || '';

			if (!stateCode) {
				return;
			}

			var fieldId  = $(this).attr('id') || '';
			var isBilling = fieldId.indexOf('billing') !== -1;
			var $state = isBilling
				? $('#_billing_state, #billing_state').first()
				: $('#_shipping_state, #shipping_state').first();

			if ($state.length) {
				$state.val(stateCode).trigger('change');
				clearTimeout(recalcTimeout);
				recalcTimeout = setTimeout(function () {
					refreshMetaboxWithData(val, stateCode);
				}, 400);
			}
		});
	}

	var recalcTimeout;

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

	/**
	 * Refresca el metabox pasando ciudad y provincia del formulario.
	 * Así no necesita que el pedido esté guardado.
	 */
	function refreshMetaboxWithData(city, state) {
		var $box    = $('#zira-shipping-metabox-content');
		var orderId = $('#post_ID').val();
		var nonce   = $('#zira_shipping_metabox_nonce').val();

		if (!nonce || !$box.length) return;

		// Refrescar el panel lateral
		$box.addClass('zira-loading');
		$.post(ajaxurl, {
			action:   'zira_shipping_refresh_metabox',
			order_id: orderId,
			nonce:    nonce,
			zira_city:  city,
			zira_state: state,
		}, function (res) {
			$box.removeClass('zira-loading');
			if (res.success && res.data.html) $box.html(res.data.html);
		}).fail(function () { $box.removeClass('zira-loading'); });
	}

	$(document).ready(function () {

		// Auto-provincia al seleccionar ciudad
		bindCityToProvince();

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
			setTimeout(bindCityToProvince, 500);
		});
		initAdminCitySelect();

		$('<style>\
			.zira-loading{opacity:.4;pointer-events:none;transition:opacity .2s}\
			@keyframes zira-pulse{0%,100%{background:transparent}50%{background:rgba(0,140,69,.08)}}\
			#zira-shipping-metabox-content.zira-loading{animation:zira-pulse 1s ease-in-out}\
		</style>').appendTo('head');
	});

})(jQuery);
