/**
 * Zira Shipping — Checkout city selector + auto-provincia.
 *
 * @package Zira_Shipping
 * @since   2.0.2
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
	 * Auto-llenar provincia al seleccionar ciudad.
	 */
	function bindCityToProvince() {
		$(document.body).off('change.ziraProv select2:select.ziraProv', '#billing_city, #shipping_city');
		$(document.body).on('change.ziraProv select2:select.ziraProv', '#billing_city, #shipping_city', function () {
			var val      = $(this).val() || '';
			var parts    = val.split('-');
			var province = parts.length > 1 ? parts[parts.length - 1].trim() : '';
			var stateCode = PROVINCE_TO_STATE[province.toUpperCase()] || '';

			if (!stateCode) return;

			var isBilling = $(this).attr('id') === 'billing_city';
			var $state    = isBilling ? $('#billing_state') : $('#shipping_state');

			if ($state.length) {
				$state.val(stateCode).trigger('change');
				$(document.body).trigger('update_checkout');
			}
		});
	}

	/**
	 * Inicializar select2 en los campos de ciudad.
	 */
	function initCitySelect() {
		$('#billing_city, #shipping_city').each(function () {
			var $el = $(this);
			if ($el.length && $el.is('select') && !$el.data('select2')) {
				$el.select2({
					placeholder: getPlaceholder(),
					width: '100%',
					allowClear: false,
				});
			}
		});
	}

	/**
	 * Disparar update_checkout al cambiar ciudad.
	 */
	function bindCityChange() {
		$(document.body).off('change.ziraCity select2:select.ziraCity', '#billing_city, #shipping_city');
		$(document.body).on('change.ziraCity select2:select.ziraCity', '#billing_city, #shipping_city', function () {
			$(document.body).trigger('update_checkout');
		});
	}

	function getPlaceholder() {
		if (typeof ziraShippingCheckout !== 'undefined' && ziraShippingCheckout.placeholder) {
			return ziraShippingCheckout.placeholder;
		}
		return 'Selecciona tu ciudad';
	}

	/**
	 * Construye el HTML del <select> de ciudades.
	 * Se usa cuando el tema renderiza el campo como <input type="text">
	 * en vez de <select>, y no podemos cambiarlo vía PHP.
	 */
	function buildCitySelectHtml(fieldId, currentValue) {
		var cities = ziraShippingCheckout.cities || [];
		var html = '<select name="' + fieldId + '" id="' + fieldId + '" class="select">';
		html += '<option value="">' + getPlaceholder() + '</option>';

		for (var i = 0; i < cities.length; i++) {
			var selected = cities[i].value === currentValue ? ' selected' : '';
			html += '<option value="' + cities[i].value + '"' + selected + '>' + cities[i].label + '</option>';
		}

		html += '</select>';
		return html;
	}

	/**
	 * Reemplaza <input type="text"> por <select> si el tema no lo hizo.
	 */
	function replaceCityTextInputs() {
		$('#billing_city, #shipping_city').each(function () {
			var $el = $(this);

			// Si ya es <select>, no hacer nada
			if ($el.is('select')) return;

			// Si ya fue reemplazado, no hacer nada
			if ($el.data('zira-replaced')) return;

			// Solo reemplazar si tenemos datos de ciudades
			if (!ziraShippingCheckout.cities || !ziraShippingCheckout.cities.length) return;

			var fieldId = $el.attr('id');
			var currentValue = $el.val() || '';
			var $newSelect = $(buildCitySelectHtml(fieldId, currentValue));

			// Copiar clases del input original
			var originalClasses = $el.attr('class') || '';
			$newSelect.addClass(originalClasses);

			// Reemplazar y marcar
			$el.replaceWith($newSelect);
			$newSelect.data('zira-replaced', true);
		});
	}

	$(document).ready(function () {
		replaceCityTextInputs();
		initCitySelect();
		bindCityChange();
		bindCityToProvince();

		$(document.body).on('updated_checkout', function () {
			setTimeout(function () {
				replaceCityTextInputs();
				initCitySelect();
				bindCityChange();
				bindCityToProvince();
			}, 100);
		});
	});

	// MutationObserver: detectar si el input aparece después (page builders)
	if (window.MutationObserver) {
		var observer = new MutationObserver(function () {
			if ($('#billing_city').length && $('#billing_city').is('input[type="text"]')) {
				replaceCityTextInputs();
				initCitySelect();
				bindCityChange();
				bindCityToProvince();
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

})(jQuery);
