/**
 * Zira Shipping — Checkout city selector + auto-provincia.
 *
 * @package Zira_Shipping
 * @since   2.0.0
 */

/* global jQuery, ziraShippingCheckout */
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
	 * Obtener placeholder de forma segura.
	 */
	function getPlaceholder() {
		if (typeof ziraShippingCheckout !== 'undefined' && ziraShippingCheckout.placeholder) {
			return ziraShippingCheckout.placeholder;
		}
		return 'Selecciona tu ciudad';
	}

	/**
	 * Auto-llenar provincia al seleccionar ciudad (frontend checkout).
	 */
	function bindCityToProvince() {
		$(document.body).off('change.ziraProv', '#billing_city, #shipping_city');
		$(document.body).on('change.ziraProv', '#billing_city, #shipping_city', function () {
			var val      = $(this).val() || '';
			var parts    = val.split('-');
			var province = parts.length > 1 ? parts[parts.length - 1].trim() : '';
			var stateCode = PROVINCE_TO_STATE[province.toUpperCase()] || '';

			if (!stateCode) {
				return;
			}

			var isBilling = $(this).attr('id') === 'billing_city';
			var $state    = isBilling ? $('#billing_state') : $('#shipping_state');

			if ($state.length) {
				$state.val(stateCode).trigger('change');
				$(document.body).trigger('update_checkout');
			}
		});
	}

	$(document).ready(function () {

		function initCitySelect() {
			var $billingCity  = $('#billing_city');
			var $shippingCity = $('#shipping_city');

			if ($billingCity.length && $billingCity.is('select') && !$billingCity.data('select2')) {
				$billingCity.select2({
					placeholder: getPlaceholder(),
					width: '100%',
					allowClear: false,
				});
			}

			if ($shippingCity.length && $shippingCity.is('select') && !$shippingCity.data('select2')) {
				$shippingCity.select2({
					placeholder: getPlaceholder(),
					width: '100%',
					allowClear: false,
				});
			}
		}

		function bindCityChange() {
			$(document.body).off('change.zira', '#billing_city, #shipping_city');
			$(document.body).on('change.zira', '#billing_city, #shipping_city', function () {
				$(document.body).trigger('update_checkout');
			});

			$(document.body).off('select2:select.zira', '#billing_city, #shipping_city');
			$(document.body).on('select2:select.zira', '#billing_city, #shipping_city', function () {
				$(document.body).trigger('update_checkout');
			});
		}

		// Auto-provincia
		bindCityToProvince();

		$(document.body).on('updated_checkout', function () {
			initCitySelect();
			bindCityChange();
			bindCityToProvince();
		});

		initCitySelect();
		bindCityChange();
	});

})(jQuery);
