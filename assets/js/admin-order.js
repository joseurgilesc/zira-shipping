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
		console.log('[Zira] bindCityToProvince: buscando campos de ciudad...');

		// Buscar tanto con guion bajo como sin él
		var $cityFields = $('#_billing_city, #_shipping_city, #billing_city, #shipping_city');
		console.log('[Zira] Campos de ciudad encontrados:', $cityFields.length);

		$cityFields.each(function () {
			console.log('[Zira]   -', this.id, '| tag:', this.tagName, '| type attr:', $(this).attr('type'));
		});

		var recalcTimeout;

		// Usar off/on para evitar duplicados
		$(document.body).off('.ziraAdminProv');
		$(document.body).on('change.ziraAdminProv select2:select.ziraAdminProv', '#_billing_city, #_shipping_city', function (e) {
			console.log('[Zira] Evento detectado:', e.type, '| id:', this.id);

			var val      = $(this).val() || '';
			console.log('[Zira] Valor de ciudad:', val);

			var parts    = val.split('-');
			var province = parts.length > 1 ? parts[parts.length - 1].trim() : '';
			console.log('[Zira] Provincia extraída:', province);

			var stateCode = PROVINCE_TO_STATE[province.toUpperCase()] || '';
			console.log('[Zira] State code:', stateCode);

			if (!stateCode) {
				console.log('[Zira] No se encontró state code para provincia:', province);
				return;
			}

			// Determinar qué campo de estado actualizar
			var fieldId  = $(this).attr('id') || '';
			var isBilling = fieldId.indexOf('billing') !== -1;

			// Buscar campo de estado: probar con y sin guion bajo
			var $state = isBilling
				? $('#_billing_state, #billing_state').first()
				: $('#_shipping_state, #shipping_state').first();

			console.log('[Zira] Campo state encontrado:', $state.length, '| id:', $state.attr('id'), '| valor actual:', $state.val());

			if ($state.length) {
				$state.val(stateCode).trigger('change');
				console.log('[Zira] State actualizado a:', stateCode);
			} else {
				console.log('[Zira] ERROR: No se encontró campo de estado para', isBilling ? 'billing' : 'shipping');
			}

			// Disparar recálculo automático (con debounce para evitar doble disparo)
			clearTimeout(recalcTimeout);
			recalcTimeout = setTimeout(function () {
				console.log('[Zira] Disparando recálculo automático...');
				$('.calculate-action').trigger('click');
			}, 400);
		});
	}

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

		$('<style>.zira-loading{opacity:.4;pointer-events:none}</style>').appendTo('head');
	});

})(jQuery);
