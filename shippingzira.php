<?php
/**
 * Plugin Name: Zira Shipping
 * Description: Método de envío nacional con tarifas por zona basadas en Servientrega. Origen: Cuenca.
 * Version: 2.0.2
 * Author: José Urgilés
 * Text Domain: zira-shipping
 * Domain Path: /languages
 *
 * @package Zira_Shipping
 * @since   1.0.0
 *
 * Changelog:
 * 2.0.0 — Refactor completo: 5 zonas tarifarias verificadas con API Servientrega,
 *         selector de ciudad en checkout, reglas de redondeo a $0.25, peso ceil.
 * 1.1.0 — Logo de Servientrega en label, opción Urbano envíos.
 * 1.0.0 — Versión inicial con brackets fijos por peso.
 */

defined( 'ABSPATH' ) || exit;

$zira_shipping_plugin_loaded = true;

// ─── Debug: activar solo para diagnosticar ───────────────────
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( '[Zira Shipping] Plugin cargado. PHP ' . PHP_VERSION . ' | WC ' . ( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/D' ) );
}

// ─── Constantes ────────────────────────────────────────────────
define( 'ZIRA_SHIPPING_VERSION', '2.0.2' );
define( 'ZIRA_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ZIRA_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Redondea hacia arriba al múltiplo de 0.25 más cercano.
 *
 * @since  2.0.0
 * @param  float $amount
 * @return float
 */
function zira_shipping_round_up_025( float $amount ): float {
	return ceil( $amount * 4 ) / 4;
}

/**
 * Mapa de CIUDAD-PROVINCIA → zona tarifaria.
 *
 * Zonas (5):
 *   - local           → solo Cuenca
 *   - local_cercano   → Azuay cercano (Paute, Girón, Sígsig...)
 *   - nacional        → ciudades principales y capitales
 *   - oriente         → rural, costa remota, amazonía
 *   - galapagos       → provincia de Galápagos
 *
 * Datos verificados contra la API de Servientrega (ago 2026).
 *
 * @since  2.0.0
 * @return array
 */
function zira_shipping_get_city_map(): array {
	return array(
		// ── Zona Local (solo Cuenca) ─────────────────────
		'CUENCA-AZUAY'                                 => 'local',

		// ── Zona Azuay cercano (Paute, Giron, Sigsig...) ─
		'CHORDELEG-AZUAY'                              => 'local_cercano',
		'GIRON-AZUAY'                                  => 'local_cercano',
		'PAUTE-AZUAY'                                  => 'local_cercano',
		'PONCE ENRIQUEZ-AZUAY'                         => 'local_cercano',
		'SAN FERNANDO-AZUAY'                           => 'local_cercano',
		'SANTA ISABEL-AZUAY'                           => 'local_cercano',
		'SIGSIG-AZUAY'                                 => 'local_cercano',

		// ── Zona Nacional (ciudades principales) ─────────
		'ALAUSI-CHIMBORAZO'                            => 'nacional',
		'AMAGUANA-PICHINCHA'                           => 'nacional',
		'AMBATO-TUNGURAHUA'                            => 'nacional',
		'ATUNTAQUI-IMBABURA'                           => 'nacional',
		'AZOGUES-CANAR'                                => 'nacional',
		'BABAHOYO-LOS RIOS'                            => 'nacional',
		'BAHIA DE CARAQUEZ-MANABI'                     => 'nacional',
		'BALSAS-EL ORO'                                => 'nacional',
		'BANOS-AZUAY'                                  => 'nacional',
		'BANOS-TUNGURAHUA'                             => 'nacional',
		'BUCAY-GUAYAS'                                 => 'nacional',
		'CALDERON-PICHINCHA'                           => 'nacional',
		'CANAR-CANAR'                                  => 'nacional',
		'CAYAMBE-PICHINCHA'                            => 'nacional',
		'CHONE-MANABI'                                 => 'nacional',
		'CHUNCHI-CHIMBORAZO'                           => 'nacional',
		'CONOCOTO-PICHINCHA'                           => 'nacional',
		'CUMANDA-CHIMBORAZO'                           => 'nacional',
		'CUMBAYA-PICHINCHA'                            => 'nacional',
		'DURAN-GUAYAS'                                 => 'nacional',
		'EL CARMEN-MANABI'                             => 'nacional',
		'EL CHACO-NAPO'                                => 'nacional',
		'EL CORAZON-COTOPAXI'                          => 'nacional',
		'EL TRIUNFO-GUAYAS'                            => 'nacional',
		'ESMERALDAS-ESMERALDAS'                        => 'nacional',
		'GUACHAPALA-AZUAY'                             => 'nacional',
		'GUALACEO-AZUAY'                               => 'nacional',
		'GUARANDA-BOLIVAR'                             => 'nacional',
		'GUAYAQUIL-GUAYAS'                             => 'nacional',
		'HUAQUILLAS-EL ORO'                            => 'nacional',
		'IBARRA-IMBABURA'                              => 'nacional',
		'JIPIJAPA-MANABI'                              => 'nacional',
		'JIVINO VERDE-SUCUMBIOS'                       => 'nacional',
		'LA CONCORDIA-ESMERALDAS'                      => 'nacional',
		'LA LIBERTAD-SANTA ELENA'                      => 'nacional',
		'LATACUNGA-COTOPAXI'                           => 'nacional',
		'LOJA-LOJA'                                    => 'nacional',
		'MACHALA-EL ORO'                               => 'nacional',
		'MANTA-MANABI'                                 => 'nacional',
		'MILAGRO-GUAYAS'                               => 'nacional',
		'OTAVALO-IMBABURA'                             => 'nacional',
		'PALLATANGA-CHIMBORAZO'                        => 'nacional',
		'PANGUA-COTOPAXI'                              => 'nacional',
		'PASAJE-EL ORO'                                => 'nacional',
		'PELILEO-TUNGURAHUA'                           => 'nacional',
		'PIFO-PICHINCHA'                               => 'nacional',
		'PORTOVIEJO-MANABI'                            => 'nacional',
		'PUEMBO-PICHINCHA'                             => 'nacional',
		'PUERTO NAPO-NAPO'                             => 'nacional',
		'QUEVEDO-LOS RIOS'                             => 'nacional',
		'QUINCHE-PICHINCHA'                            => 'nacional',
		'QUININDE-ESMERALDAS'                          => 'nacional',
		'QUITO-PICHINCHA'                              => 'nacional',
		'RIOBAMBA-CHIMBORAZO'                          => 'nacional',
		'SALINAS (SANTA ELENA)-SANTA ELENA'            => 'nacional',
		'SAN GABRIEL-CARCHI'                           => 'nacional',
		'SAN JOSE DE CHIMBO-BOLIVAR'                   => 'nacional',
		'SAN LORENZO-ESMERALDAS'                       => 'nacional',
		'SAN RAFAEL-PICHINCHA'                         => 'nacional',
		'SANGOLQUI-PICHINCHA'                          => 'nacional',
		'SANTO DOMINGO-SANTO DOMINGO'                  => 'nacional',
		'TULCAN-CARCHI'                                => 'nacional',
		'VENTANAS-LOS RIOS'                            => 'nacional',
		'YACHAY-IMBABURA'                              => 'nacional',

		// ── Zona Oriente y rural (costo adicional) ────────
		'7 DE JULIO-SUCUMBIOS'                         => 'oriente',
		'ALAMOR-LOJA'                                  => 'oriente',
		'ALLURIQUIN-SANTO DOMINGO'                     => 'oriente',
		'AMALUZA-LOJA'                                 => 'oriente',
		'ARCHIDONA-NAPO'                               => 'oriente',
		'ARENILLAS-EL ORO'                             => 'oriente',
		'AROSEMENA TOLA-NAPO'                          => 'oriente',
		'ATACAMES-ESMERALDAS'                          => 'oriente',
		'BABA-LOS RIOS'                                => 'oriente',
		'BAEZA-NAPO'                                   => 'oriente',
		'BALZAR-GUAYAS'                                => 'oriente',
		'BALLENITA-SANTA ELENA'                        => 'oriente',
		'BIBLIAN-CANAR'                                => 'oriente',
		'BOLIVAR-CARCHI'                               => 'oriente',
		'BORBON-ESMERALDAS'                            => 'oriente',
		'BORJA-NAPO'                                   => 'oriente',
		'BUENA FE-LOS RIOS'                            => 'oriente',
		'CALCETA-MANABI'                               => 'oriente',
		'CALUMA-BOLIVAR'                               => 'oriente',
		'CARIAMANGA-LOJA'                              => 'oriente',
		'CASCALES-SUCUMBIOS'                           => 'oriente',
		'CATAMAYO-LOJA'                                => 'oriente',
		'CELICA-LOJA'                                  => 'oriente',
		'CEVALLOS-TUNGURAHUA'                          => 'oriente',
		'CHAMBO-CHIMBORAZO'                            => 'oriente',
		'CHILLANES-BOLIVAR'                            => 'oriente',
		'CHIMBO-BOLIVAR'                               => 'oriente',
		'COJITAMBO-CANAR'                              => 'oriente',
		'COLIMES-GUAYAS'                               => 'oriente',
		'COLTA-CHIMBORAZO'                             => 'oriente',
		'COTACACHI-IMBABURA'                           => 'oriente',
		'CUMBE-AZUAY'                                  => 'oriente',
		'DAULE-GUAYAS'                                 => 'oriente',
		'DELEG-CANAR'                                  => 'oriente',
		'ECHEANDIA-BOLIVAR'                            => 'oriente',
		'EL ANGEL-CARCHI'                              => 'oriente',
		'EL COCA-ORELLANA'                             => 'oriente',
		'EL EMPALME-GUAYAS'                            => 'oriente',
		'EL GUABO-EL ORO'                              => 'oriente',
		'EL GUANO-CHIMBORAZO'                          => 'oriente',
		'EL PAN-AZUAY'                                 => 'oriente',
		'EL PANGUI-ZAMORA'                             => 'oriente',
		'FLAVIO ALFARO-MANABI'                         => 'oriente',
		'GONZALO PIZARRO-NAPO'                         => 'oriente',
		'GONZANAMA-LOJA'                               => 'oriente',
		'GUADALUPE-ZAMORA'                             => 'oriente',
		'GUALAQUIZA-MORONA SANTIAGO'                   => 'oriente',
		'GUAMOTE-CHIMBORAZO'                           => 'oriente',
		'GUAYLLABAMBA-PICHINCHA'                       => 'oriente',
		'HUACA-CARCHI'                                 => 'oriente',
		'ISIDRO AYORA-GUAYAS'                          => 'oriente',
		'JARAMIJO-MANABI'                              => 'oriente',
		'JOYA DE LOS SACHAS-ORELLANA'                  => 'oriente',
		'JUJAN-GUAYAS'                                 => 'oriente',
		'JUNIN-MANABI'                                 => 'oriente',
		'LA INDEPENDENCIA-ESMERALDAS'                  => 'oriente',
		'LA MANA-COTOPAXI'                             => 'oriente',
		'LA PUNTILLA-CANAR'                            => 'oriente',
		'LA TRONCAL-CANAR'                             => 'oriente',
		'LAGO AGRIO-SUCUMBIOS'                         => 'oriente',
		'LAS DELICIAS-SANTO DOMINGO'                   => 'oriente',
		'LAS NAVES-BOLIVAR'                            => 'oriente',
		'LASSO-COTOPAXI'                               => 'oriente',
		'LOMAS DE SARGENTILLO-GUAYAS'                  => 'oriente',
		'LORETO-ORELLANA'                              => 'oriente',
		'LUMBAQUI-SUCUMBIOS'                           => 'oriente',
		'MACARA-LOJA'                                  => 'oriente',
		'MACAS-MORONA SANTIAGO'                        => 'oriente',
		'MANGLARALTO-SANTA ELENA'                      => 'oriente',
		'MARCABELI-EL ORO'                             => 'oriente',
		'MARCELINO MARIDUENA-GUAYAS'                   => 'oriente',
		'MARISCAL SUCRE-GUAYAS'                        => 'oriente',
		'MERA-PASTAZA'                                 => 'oriente',
		'MIRA-CARCHI'                                  => 'oriente',
		'MOCACHE-LOS RIOS'                             => 'oriente',
		'MOCHA-TUNGURAHUA'                             => 'oriente',
		'MONTALVO-LOS RIOS'                            => 'oriente',
		'MONTANITA-SANTA ELENA'                        => 'oriente',
		'MONTECRISTI-MANABI'                           => 'oriente',
		'MUISNE-ESMERALDAS'                            => 'oriente',
		'NABON-AZUAY'                                  => 'oriente',
		'NARANJAL-GUAYAS'                              => 'oriente',
		'NARANJITO-GUAYAS'                             => 'oriente',
		'NOBOL-GUAYAS'                                 => 'oriente',
		'OLON-SANTA ELENA'                             => 'oriente',
		'ONA-AZUAY'                                    => 'oriente',
		'PAJAN-MANABI'                                 => 'oriente',
		'PALANDA-ZAMORA'                               => 'oriente',
		'PALENQUE-LOS RIOS'                            => 'oriente',
		'PALESTINA-GUAYAS'                             => 'oriente',
		'PALORA-MORONA SANTIAGO'                       => 'oriente',
		'PATATE-TUNGURAHUA'                            => 'oriente',
		'PEDERNALES-MANABI'                            => 'oriente',
		'PEDRO CARBO-GUAYAS'                           => 'oriente',
		'PEDRO VICENTE MALDONADO-PICHINCHA'            => 'oriente',
		'PENIPE-CHIMBORAZO'                            => 'oriente',
		'PETRILLO-GUAYAS'                              => 'oriente',
		'PICHINCHA-MANABI'                             => 'oriente',
		'PILLARO-TUNGURAHUA'                           => 'oriente',
		'PIMAMPIRO-IMBABURA'                           => 'oriente',
		'PINDAL-LOJA'                                  => 'oriente',
		'PINAS-EL ORO'                                 => 'oriente',
		'PLAYAS-GUAYAS'                                => 'oriente',
		'PORTOVELO-EL ORO'                             => 'oriente',
		'PUCARA-AZUAY'                                 => 'oriente',
		'PUEBLO VIEJO-LOS RIOS'                        => 'oriente',
		'PUERTO LOPEZ-MANABI'                          => 'oriente',
		'PUERTO QUITO-PICHINCHA'                       => 'oriente',
		'PUJILI-COTOPAXI'                              => 'oriente',
		'PUYO-PASTAZA'                                 => 'oriente',
		'QUERO-TUNGURAHUA'                             => 'oriente',
		'QUINSALOMA-LOS RIOS'                          => 'oriente',
		'REVENTADOR-SUCUMBIOS'                         => 'oriente',
		'RIO VERDE-ESMERALDAS'                         => 'oriente',
		'ROCAFUERTE-MANABI'                            => 'oriente',
		'SALITRE-GUAYAS'                               => 'oriente',
		'SAMBORONDON-GUAYAS'                           => 'oriente',
		'SAN ANTONIO DE IBARRA-IMBABURA'               => 'oriente',
		'SAN MIGUEL DE BOLIVAR-BOLIVAR'                => 'oriente',
		'SAN MIGUEL DE LOS BANCOS-PICHINCHA'           => 'oriente',
		'SAN PABLO DEL LAGO-IMBABURA'                  => 'oriente',
		'SAN VICENTE-MANABI'                           => 'oriente',
		'SANTA ANA (MANABI)-MANABI'                    => 'oriente',
		'SANTA CECILIA-SUCUMBIOS'                      => 'oriente',
		'SANTA CLARA-PASTAZA'                          => 'oriente',
		'SANTA ELENA-SANTA ELENA'                      => 'oriente',
		'SANTA LUCIA-GUAYAS'                           => 'oriente',
		'SANTA ROSA (EL ORO)-EL ORO'                   => 'oriente',
		'SAQUISILI-COTOPAXI'                           => 'oriente',
		'SARAGURO-LOJA'                                => 'oriente',
		'SAYUASI-AZUAY'                                => 'oriente',
		'SEVILLA DE ORO-AZUAY'                         => 'oriente',
		'SHELL (EL PUYO)-PASTAZA'                      => 'oriente',
		'SHUSHUFINDI-SUCUMBIOS'                        => 'oriente',
		'SIGCHOS-COTOPAXI'                             => 'oriente',
		'SIMON BOLIVAR-GUAYAS'                         => 'oriente',
		'SOZORANGA-LOJA'                               => 'oriente',
		'SUSCAL-CANAR'                                 => 'oriente',
		'TABACUNDO-PICHINCHA'                          => 'oriente',
		'TAMBO-CANAR'                                  => 'oriente',
		'TARQUI (AZUAY)-AZUAY'                         => 'oriente',
		'TENA-NAPO'                                    => 'oriente',
		'TISALEO-TUNGURAHUA'                           => 'oriente',
		'TONSUPA-ESMERALDAS'                           => 'oriente',
		'TOSAGUA-MANABI'                               => 'oriente',
		'URCUQUI-IMBABURA'                             => 'oriente',
		'URDANETA-LOS RIOS'                            => 'oriente',
		'VALENCIA-LOS RIOS'                            => 'oriente',
		'VILCABAMBA-LOJA'                              => 'oriente',
		'VINCES-LOS RIOS'                              => 'oriente',
		'VOLUNTAD DE DIOS-CANAR'                       => 'oriente',
		'YAGUACHI-GUAYAS'                              => 'oriente',
		'YANTZAZA-ZAMORA'                              => 'oriente',
		'SAME-ESMERALDAS'                              => 'oriente',
		'SUA-ESMERALDAS'                               => 'oriente',
		'ZAMORA-ZAMORA'                                => 'oriente',
		'ZAPOTILLO-LOJA'                               => 'oriente',
		'ZARUMA-EL ORO'                                => 'oriente',
		'ZUMBA-ZAMORA'                                 => 'oriente',
		'ZUMBI-ZAMORA'                                 => 'oriente',

		// ── Zona Galapagos ───────────────────────────────
		'SAN CRISTOBAL-GALAPAGOS'                      => 'galapagos',
		'SANTA CRUZ-GALAPAGOS'                         => 'galapagos',
	);
}

/**
 * Tabla de precios por zona tarifaria.
 *
 * Precios redondeados ↑ a 0.25 (regla del cliente).
 * Primer bracket (2kg) fijado a $6.00 para zona nacional.
 * Pesos superiores al último bracket: fórmula dinámica.
 *
 * @since  2.0.0
 * @return array
 */
function zira_shipping_get_pricing_table(): array {
	return array(
		'local'          => array(
			'brackets'  => array(
				2  => 3.00,
				3  => 3.25,
				4  => 3.50,
				5  => 3.75,
				6  => 3.75,
				7  => 3.75,
				8  => 5.00,
				9  => 5.00,
				10 => 5.00,
				11 => 6.25,
				12 => 6.25,
				13 => 6.25,
				14 => 7.75,
				15 => 7.75,
				16 => 7.75,
			),
			'base'      => 2.25,
			'adicional' => 0.39,
			'doc_1kg'   => 2.50,
		),
		'local_cercano'  => array(
			'brackets'  => array(
				2  => 5.50,
				3  => 6.00,
				4  => 6.75,
				5  => 7.00,
				6  => 7.00,
				7  => 7.00,
				8  => 8.25,
				9  => 8.25,
				10 => 8.25,
				11 => 10.50,
				12 => 10.50,
				13 => 10.50,
				14 => 12.50,
				15 => 12.50,
				16 => 12.50,
			),
			'base'      => 4.60,
			'adicional' => 0.60,
			'doc_1kg'   => 4.25,
		),
		'nacional'       => array(
			'brackets'  => array(
				2  => 6.00,
				3  => 7.00,
				4  => 8.00,
				5  => 8.00,
				6  => 8.00,
				7  => 8.00,
				8  => 10.25,
				9  => 10.25,
				10 => 10.25,
				11 => 12.75,
				12 => 12.75,
				13 => 12.75,
				14 => 15.50,
				15 => 15.50,
				16 => 15.50,
			),
			'base'      => 5.15,
			'adicional' => 0.80,
			'doc_1kg'   => 4.75,
		),
		'oriente'        => array(
			'brackets'  => array(
				2  => 7.25,
				3  => 8.25,
				4  => 9.50,
				5  => 10.25,
				6  => 10.25,
				7  => 10.25,
				8  => 12.50,
				9  => 12.50,
				10 => 12.50,
				11 => 16.00,
				12 => 16.00,
				13 => 16.00,
				14 => 19.25,
				15 => 19.25,
				16 => 19.25,
			),
			'base'      => 6.10,
			'adicional' => 0.99,
			'doc_1kg'   => 5.50,
		),
		'galapagos'      => array(
			'brackets'  => array(
				2  => 13.00,
				3  => 17.25,
				4  => 21.50,
				5  => 25.75,
				6  => 30.00,
				7  => 34.25,
				8  => 38.50,
				9  => 42.75,
				10 => 47.00,
				11 => 51.25,
				12 => 55.50,
				13 => 59.50,
				14 => 63.75,
				15 => 68.00,
				16 => 72.25,
			),
			'base'      => 11.25,
			'adicional' => 3.68,
			'doc_1kg'   => 7.50,
		),
	);
}

/**
 * Retorna las ciudades agrupadas por zona para usar en el checkout.
 *
 * @since  2.0.0
 * @return array
 */
function zira_shipping_get_cities_by_zone(): array {
	static $cities = null;

	if ( null !== $cities ) {
		return $cities;
	}

	$map    = zira_shipping_get_city_map();
	$cities = array(
		'local'         => array(),
		'local_cercano' => array(),
		'nacional'      => array(),
		'oriente'       => array(),
		'galapagos'     => array(),
	);

	foreach ( $map as $city_full => $zone ) {
		$parts     = explode( '-', $city_full );
		$city_name = trim( $parts[0] );
		$cities[ $zone ][ $city_full ] = $city_name;
	}

	foreach ( $cities as $zone => &$list ) {
		asort( $list );
	}
	unset( $list );

	return $cities;
}

/**
 * Mapea la ciudad a una zona tarifaria.
 *
 * Primero busca en el mapa exacto de ciudades Servientrega.
 * Luego intenta match por nombre de ciudad.
 * Finalmente fallback por provincia WooCommerce.
 *
 * @param string $state Código de provincia de WooCommerce
 * @param string $city  Ciudad (formato 'CIUDAD' o 'CIUDAD-PROVINCIA')
 * @return string
 */
function zira_shipping_get_zone( string $state, string $city ): string {
	$city_map = zira_shipping_get_city_map();

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Zira Shipping] get_zone called: state=[' . $state . '] city=[' . $city . ']' );
	}

	// 1. Match exacto (formato dropdown: CIUDAD-PROVINCIA)
	if ( isset( $city_map[ $city ] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Zira Shipping] get_zone: EXACT match "' . $city . '" → ' . $city_map[ $city ] );
		}
		return $city_map[ $city ];
	}

	// 2. Match por nombre de ciudad (sin provincia)
	$city_normalized = strtoupper( trim( $city ) );
	foreach ( $city_map as $key => $zone ) {
		$parts     = explode( '-', $key );
		$city_name = strtoupper( trim( $parts[0] ) );
		if ( $city_name === $city_normalized ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Zira Shipping] get_zone: NAME match "' . $city . '" → ' . $zone );
			}
			return $zone;
		}
	}

	// 3. Fallback por provincia
	if ( empty( $state ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Zira Shipping] get_zone: NO match + no state → nacional' );
		}
		return 'nacional';
	}

	$state = strtoupper( trim( $state ) );

	// Normalizar: quitar prefijo 'EC-' si existe
	// WooCommerce Ecuador usa EC-A, EC-W, etc.; la lógica espera A, W, etc.
	if ( str_starts_with( $state, 'EC-' ) ) {
		$state = substr( $state, 3 );
	}

	if ( 'W' === $state ) {
		return 'galapagos';
	}

	if ( 'A' === $state ) {
		return 'local';
	}

	$oriente_states = array(
		'D'  => true, 'K' => true, 'N' => true,
		'S'  => true, 'Y' => true, 'Z' => true,
		'SE' => true, 'L' => true,
	);

	if ( isset( $oriente_states[ $state ] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Zira Shipping] get_zone: STATE fallback state=' . $state . ' → oriente' );
		}
		return 'oriente';
	}

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Zira Shipping] get_zone: STATE fallback state=' . $state . ' → nacional' );
	}
	return 'nacional';
}

// ─── Clase Principal del Shipping Method ───────────────────────

if ( ! class_exists( 'Zira_Shipping_Method' ) ) :

	class Zira_Shipping_Method extends WC_Shipping_Method {

		private static ?self $instance = null;

		public function __construct( $instance_id = 0 ) {
			try {
				$this->id                 = 'zira_shipping';
				$this->instance_id        = absint( $instance_id );
				$this->method_title       = __( 'Zira Envío Nacional', 'zira-shipping' );
				$this->method_description = __( 'Calcula el costo de envío desde Cuenca según zona tarifaria y peso del carrito.', 'zira-shipping' );
				$this->title              = __( 'Envío Nacional', 'zira-shipping' );

				$this->supports = array(
					'shipping-zones',
					'instance-settings',
				);

				$this->init();

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Zira Shipping] Constructor OK. instance_id=' . $this->instance_id );
				}
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Zira Shipping] ERROR en constructor: ' . $e->getMessage() );
				}
			}
		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Clonación no permitida.', 'zira-shipping' ), ZIRA_SHIPPING_VERSION );
		}
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Deserialización no permitida.', 'zira-shipping' ), ZIRA_SHIPPING_VERSION );
		}

		public static function get_instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function init(): void {
			$this->init_form_fields();
			$this->init_settings();

			add_action(
				'woocommerce_update_options_shipping_' . $this->id,
				array( $this, 'process_admin_options' )
			);
		}

		public function init_form_fields(): void {
			$this->form_fields = array(
				'enabled'              => array(
					'title'   => __( 'Habilitar', 'zira-shipping' ),
					'type'    => 'checkbox',
					'label'   => __( 'Activar Zira Envío Nacional', 'zira-shipping' ),
					'default' => 'yes',
				),
				'title'                => array(
					'title'       => __( 'Título', 'zira-shipping' ),
					'type'        => 'text',
					'description' => __( 'Título que ve el cliente durante el checkout.', 'zira-shipping' ),
					'default'     => __( 'Envío Nacional', 'zira-shipping' ),
					'desc_tip'    => true,
				),
				'default_weight'       => array(
					'title'       => __( 'Peso por defecto (kg)', 'zira-shipping' ),
					'type'        => 'number',
					'description' => __( 'Peso usado cuando un producto no tiene peso definido.', 'zira-shipping' ),
					'default'     => 2,
					'desc_tip'    => true,
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 0.5,
					),
				),
				'fallback_zone'        => array(
					'title'       => __( 'Zona por defecto', 'zira-shipping' ),
					'type'        => 'select',
					'description' => __( 'Zona usada cuando no se puede determinar la ciudad del cliente.', 'zira-shipping' ),
					'default'     => 'nacional',
					'options'     => array(
						'local'         => __( 'Local (Cuenca)', 'zira-shipping' ),
						'local_cercano' => __( 'Azuay cercano', 'zira-shipping' ),
						'nacional'      => __( 'Nacional', 'zira-shipping' ),
						'oriente'       => __( 'Oriente y rural', 'zira-shipping' ),
						'galapagos'     => __( 'Galápagos', 'zira-shipping' ),
					),
				),
			);
		}

		public function calculate_shipping( $package = array() ): void {
			if ( 'yes' !== $this->enabled ) {
				return;
			}

			$weight = $this->calculate_cart_weight( $package );
			$zone   = $this->determine_zone( $package );
			$cost   = $this->calculate_cost( $weight, $zone );

		$zone_names = array(
			'local'         => __( 'Local', 'zira-shipping' ),
			'local_cercano' => __( 'Azuay', 'zira-shipping' ),
			'nacional'      => __( 'Nacional', 'zira-shipping' ),
			'oriente'       => __( 'Nacional', 'zira-shipping' ),
			'galapagos'     => __( 'Galápagos', 'zira-shipping' ),
		);

		$zone_label = $zone_names[ $zone ] ?? __( 'Nacional', 'zira-shipping' );
		$label = sprintf(
			'Servientrega (%s)',
			$zone_label
		);

			$this->add_rate( array(
				'id'       => $this->id . '_servientrega',
				'label'    => $label,
				'cost'     => $cost,
				'meta_data'=> array(
					'zira_zone'  => $zone,
					'zira_weight'=> (int) $weight,
				),
			) );
		}

		public function calculate_cart_weight( array $package ): float {
			$weight         = 0.0;
			$default_weight = (float) $this->get_option( 'default_weight', 2 );

			foreach ( $package['contents'] as $values ) {
				$_product = $values['data'];
				if ( ! is_object( $_product ) ) {
					continue;
				}

				$product_weight = method_exists( $_product, 'get_weight' )
					? $_product->get_weight()
					: '';

				if ( '' === $product_weight || false === $product_weight || null === $product_weight ) {
					$product_weight = $default_weight;
				}

				$product_weight = (float) $product_weight;
				if ( $product_weight <= 0 ) {
					$product_weight = $default_weight;
				}

				$weight += $product_weight * (float) $values['quantity'];
			}

			// Mínimo 2 kg: Servientrega no acepta menos para MERCANCIA PREMIER
			return max( 2.0, ceil( $weight ) );
		}

		private function determine_zone( array $package ): string {
			$destination = $package['destination'] ?? array();
			$state       = strtoupper( $destination['state'] ?? '' );
			$city        = $destination['city'] ?? '';

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Zira Shipping] determine_zone: state=' . $state . ' city=' . $city );
			}

			if ( empty( $state ) && empty( $city ) ) {
				$fallback = $this->get_option( 'fallback_zone', 'nacional' );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Zira Shipping] determine_zone: empty dest → fallback=' . $fallback );
				}
				return $fallback;
			}

			$zone = zira_shipping_get_zone( $state, $city );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Zira Shipping] determine_zone: resolved zone=' . $zone );
			}

			return $zone;
		}

		private function calculate_cost( float $weight, string $zone ): float {
			$pricing = zira_shipping_get_pricing_table();

			if ( ! isset( $pricing[ $zone ] ) ) {
				$zone = 'nacional';
			}

			$zone_data = $pricing[ $zone ];
			$weight_kg = (int) $weight;

			if ( 1 === $weight_kg && isset( $zone_data['doc_1kg'] ) ) {
				return $zone_data['doc_1kg'];
			}

			if ( isset( $zone_data['brackets'][ $weight_kg ] ) ) {
				return $zone_data['brackets'][ $weight_kg ];
			}

			$brackets    = $zone_data['brackets'];
			$max_bracket = max( array_keys( $brackets ) );

			if ( $weight_kg > $max_bracket ) {
				$base      = $zone_data['base'];
				$adicional = $zone_data['adicional'];
				$subtotal  = $base + ( $weight_kg - 2 ) * $adicional;
				$raw_total = $subtotal * 1.15;

				return zira_shipping_round_up_025( $raw_total );
			}

			return $brackets[ $max_bracket ];
		}

		/**
		 * Calcular costo sin redondeo (referencia, no usado directamente).
		 */
		private function calculate_raw_cost( float $weight, string $zone ): float {
			$pricing   = zira_shipping_get_pricing_table();
			$zone_data = $pricing[ $zone ] ?? $pricing['nacional'];
			$weight_kg = (int) $weight;

			$brackets    = $zone_data['brackets'];
			$max_bracket = max( array_keys( $brackets ) );

			if ( $weight_kg > $max_bracket ) {
				$base      = $zone_data['base'];
				$adicional = $zone_data['adicional'];
				$subtotal  = $base + ( $weight_kg - 2 ) * $adicional;
				return round( $subtotal * 1.15, 2 );
			}

			if ( isset( $zone_data['brackets'][ $weight_kg ] ) ) {
				return $zone_data['brackets'][ $weight_kg ];
			}
			if ( 1 === $weight_kg ) {
				return $zone_data['doc_1kg'] ?? $brackets[ $max_bracket ];
			}

			return $brackets[ $max_bracket ];
		}
	}

endif;

// ─── Registrar en WooCommerce ─────────────────────────────────

add_filter( 'woocommerce_shipping_methods', 'zira_add_shipping_method' );

function zira_add_shipping_method( array $methods ): array {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Zira Shipping] Filter woocommerce_shipping_methods called. Adding zira_shipping.' );
	}
	$methods['zira_shipping'] = 'Zira_Shipping_Method';
	return $methods;
}

// ─── Checkout: Selector de Ciudad (frontend + admin) ──────────

add_filter( 'woocommerce_checkout_fields', 'zira_shipping_city_select' );
add_filter( 'woocommerce_admin_billing_fields', 'zira_shipping_admin_city_select' );
add_filter( 'woocommerce_admin_shipping_fields', 'zira_shipping_admin_city_select' );

/**
 * Reemplaza el campo ciudad en el checkout del frontend.
 */
function zira_shipping_city_select( array $fields ): array {
	return zira_shipping_apply_city_select( $fields, 'billing', 'shipping' );
}

/**
 * Reemplaza el campo ciudad en el panel de admin (Añadir/Editar pedido).
 */
function zira_shipping_admin_city_select( array $fields ): array {
	return zira_shipping_apply_city_select( $fields );
}

/**
 * Aplica el selector de ciudades a los campos indicados.
 */
function zira_shipping_apply_city_select( array $fields, string ...$sections ): array {
	$cities_by_zone = zira_shipping_get_cities_by_zone();

	$options = array(
		'' => __( '— Selecciona tu ciudad —', 'zira-shipping' ),
	);

	foreach ( $cities_by_zone as $cities ) {
		foreach ( $cities as $value => $label ) {
			$options[ $value ] = $label;
		}
	}

	$city_field = array(
		'label'    => __( 'Ciudad', 'zira-shipping' ),
		'required' => true,
		'type'     => 'select',
		'options'  => $options,
		'priority' => 70,
	);

	// Frontend usa array para class, admin usa string
	$is_admin = is_admin() || wp_doing_ajax();
	if ( $is_admin ) {
		$city_field['class'] = 'form-row-wide address-field';
	} else {
		$city_field['class'] = array( 'form-row-wide', 'address-field' );
	}

	// Si no se especifican secciones, aplicar a todas las que existan
	if ( empty( $sections ) ) {
		$sections = array( 'billing', 'shipping' );
	}

	foreach ( $sections as $section ) {
		$key = $section . '_city';
		// En admin, la key puede ser solo 'city'
		if ( isset( $fields[ $key ] ) ) {
			$fields[ $key ] = $city_field;
		} elseif ( isset( $fields['city'] ) ) {
			$fields['city'] = $city_field;
		}
	}

	return $fields;
}

add_action( 'wp_enqueue_scripts', 'zira_shipping_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'zira_shipping_admin_enqueue_assets' );

function zira_shipping_enqueue_assets(): void {
	if ( ! is_checkout() ) {
		return;
	}

	wp_enqueue_style(
		'zira-shipping-checkout',
		ZIRA_SHIPPING_PLUGIN_URL . 'assets/css/checkout.css',
		array(),
		ZIRA_SHIPPING_VERSION
	);

	wp_enqueue_script(
		'zira-shipping-checkout',
		ZIRA_SHIPPING_PLUGIN_URL . 'assets/js/checkout.js',
		array( 'jquery' ),
		ZIRA_SHIPPING_VERSION,
		true
	);

	wp_localize_script(
		'zira-shipping-checkout',
		'ziraShippingCheckout',
		array(
			'placeholder' => __( 'Selecciona tu ciudad', 'zira-shipping' ),
		)
	);
}

/**
 * Carga JS en el panel de Añadir/Editar pedido (admin).
 */
function zira_shipping_admin_enqueue_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) ) {
		return;
	}

	global $post;

	// post-new.php no tiene $post aún — verificar por query param
	$post_type = '';
	if ( $post && ! empty( $post->post_type ) ) {
		$post_type = $post->post_type;
	} elseif ( ! empty( $_GET['post_type'] ) ) {
		$post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		// Editar post existente vía post.php?post=123
		$p = get_post( absint( $_GET['post'] ) );
		$post_type = $p ? $p->post_type : '';
	}

	if ( 'shop_order' !== $post_type ) {
		return;
	}

	wp_enqueue_script(
		'zira-shipping-admin-order',
		ZIRA_SHIPPING_PLUGIN_URL . 'assets/js/admin-order.js',
		array( 'jquery' ),
		ZIRA_SHIPPING_VERSION,
		true
	);
}

// ─── Admin: Visor de Peso y Zona ─────────────────────────────

add_action( 'add_meta_boxes', 'zira_shipping_add_weight_metabox' );
add_action( 'wp_ajax_zira_shipping_refresh_metabox', 'zira_shipping_ajax_refresh_metabox' );
add_action( 'wp_ajax_zira_shipping_update_cost', 'zira_shipping_ajax_update_cost' );

function zira_shipping_add_weight_metabox(): void {
	$logo_url = ZIRA_SHIPPING_PLUGIN_URL . 'images/logoservientrega.png';
	add_meta_box(
		'zira-shipping-weight',
		'<img src="' . esc_url( $logo_url ) . '" width="65" style="vertical-align:middle;margin-right:6px" alt="Servientrega" />' . __( 'Servientrega — Peso y Tarifa', 'zira-shipping' ),
		'zira_shipping_weight_metabox_callback',
		'shop_order',
		'side',
		'default'
	);
}

/**
 * AJAX: devuelve HTML actualizado para el visor lateral.
 */
function zira_shipping_ajax_refresh_metabox(): void {
	check_ajax_referer( 'zira-shipping-metabox', 'nonce' );

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$order    = wc_get_order( $order_id );

	if ( ! $order ) {
		wp_send_json_error( array( 'html' => '<p>Pedido no encontrado.</p>' ) );
	}

	// Ciudad y provincia enviadas desde el formulario (JS)
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$form_city  = isset( $_POST['zira_city'] ) ? sanitize_text_field( wp_unslash( $_POST['zira_city'] ) ) : '';
	$form_state = isset( $_POST['zira_state'] ) ? sanitize_text_field( wp_unslash( $_POST['zira_state'] ) ) : '';
	// phpcs:enable

	// Guardar en la orden para que save/recalcular tengan los datos frescos
	// (sin disparar hooks de recálculo que causarían recursión en AJAX)
	if ( ! empty( $form_city ) && $order_id > 0 ) {
		remove_action( 'woocommerce_before_order_object_save', 'zira_shipping_admin_calculate_hpos', 99 );
		remove_action( 'woocommerce_process_shop_order_meta', 'zira_shipping_admin_calculate', 99 );

		$order->set_billing_city( $form_city );
		$order->set_billing_state( $form_state );
		$order->save();

		add_action( 'woocommerce_before_order_object_save', 'zira_shipping_admin_calculate_hpos', 99, 2 );
		add_action( 'woocommerce_process_shop_order_meta', 'zira_shipping_admin_calculate', 99, 2 );
	}

	ob_start();
	zira_shipping_render_weight_metabox_content( $order, $form_city, $form_state );
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}

/**
 * AJAX: calcula zona y costo sin guardar nada en la base de datos.
 * Solo devuelve los valores. El guardado real ocurre al crear/actualizar.
 */
function zira_shipping_ajax_update_cost(): void {
	check_ajax_referer( 'zira-shipping-metabox', 'nonce' );

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$city  = isset( $_POST['zira_city'] ) ? sanitize_text_field( wp_unslash( $_POST['zira_city'] ) ) : '';
	$state = isset( $_POST['zira_state'] ) ? sanitize_text_field( wp_unslash( $_POST['zira_state'] ) ) : '';
	// phpcs:enable

	if ( empty( $city ) ) {
		wp_send_json_error( array( 'message' => 'Ciudad no proporcionada.' ) );
	}

	$zone = zira_shipping_get_zone( $state, $city );

	// Calcular peso desde el pedido (si existe) o usar 2 kg por defecto
	$weight = 2.0;
	if ( $order_id > 0 ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof \WC_Order ) {
			$weight = zira_shipping_calc_raw_weight( $order );
		}
	}
	$weight_kg = max( 2, (int) ceil( $weight ) );

	$cost = zira_shipping_admin_cost( $weight_kg, $zone );

	wp_send_json_success( array(
		'zone'   => $zone,
		'weight' => $weight_kg,
		'cost'   => $cost,
	) );
}

function zira_shipping_weight_metabox_callback( \WP_Post $post ): void {
	$order = wc_get_order( $post->ID );
	if ( ! $order ) {
		echo '<p>' . esc_html__( 'Pedido no encontrado.', 'zira-shipping' ) . '</p>';
		return;
	}

	// Nonce para AJAX
	wp_nonce_field( 'zira-shipping-metabox', 'zira_shipping_metabox_nonce' );
	echo '<div id="zira-shipping-metabox-content">';
	zira_shipping_render_weight_metabox_content( $order );
	echo '</div>';
}

function zira_shipping_render_weight_metabox_content( \WC_Order $order, string $form_city = '', string $form_state = '' ): void {
	$raw_weight = zira_shipping_calc_raw_weight( $order );
	$weight_kg = max( 2, (int) ceil( $raw_weight ) );

	// Priorizar datos del formulario (JS auto-province), luego del pedido guardado
	if ( ! empty( $form_city ) ) {
		$city  = $form_city;
		$state = $form_state;
	} else {
		$city  = $order->get_shipping_city() ?: $order->get_billing_city();
		$state = strtoupper( ( $order->get_shipping_state() ?: $order->get_billing_state() ) ?? '' );
	}
	$zone = zira_shipping_get_zone( $state, $city ?? '' );
	$cost = zira_shipping_admin_cost( $weight_kg, $zone );

	$zone_names = array(
		'local'         => '📍 Local (Cuenca)',
		'local_cercano' => '📍 Azuay cercano',
		'nacional'      => '🇪🇨 Nacional',
		'oriente'       => '🌿 Oriente / Rural',
		'galapagos'     => '🏝️ Galápagos',
	);

	$zone_label = $zone_names[ $zone ] ?? $zone;
	$city_short = explode( '-', $city )[0] ?? $city;

	echo '<style>
		.zira-weight-box p { margin: 3px 0; font-size: 12px; }
		.zira-weight-box .zira-big { font-size: 17px; font-weight: 700; color: #008C45; }
		.zira-weight-box .zira-detail { font-size: 11px; color: #666; }
		.zira-weight-box .zira-round { font-size: 10px; color: #999; font-style: italic; }
	</style>';
	echo '<div class="zira-weight-box">';
	echo '<p class="zira-detail">' . esc_html__( 'Tarifa Servientrega desde Cuenca', 'zira-shipping' ) . '</p>';

	// Productos y peso
	if ( ! empty( $order->get_items() ) ) {
		echo '<hr style="margin:6px 0;border:none;border-top:1px solid #eee">';
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$name    = $product ? $product->get_name() : $item->get_name();
			$qty     = $item->get_quantity();
			$w       = $product && method_exists( $product, 'get_weight' ) ? (float) $product->get_weight() : 0;
			$w       = $w > 0 ? $w : 2; // peso default

			echo '<p class="zira-detail">';
			echo esc_html( mb_strimwidth( $name, 0, 28, '…' ) );
			echo ' ×' . esc_html( $qty );
			echo ' <span style="color:#888">(' . esc_html( $w ) . 'kg c/u)</span>';
			echo '</p>';
		}
		echo '<hr style="margin:6px 0;border:none;border-top:1px solid #eee">';
	}

	echo '<p><strong>' . esc_html__( 'Peso real:', 'zira-shipping' ) . '</strong> ' . esc_html( number_format( $raw_weight, 2 ) ) . ' kg</p>';
	echo '<p class="zira-round">' . esc_html__( 'Redondeo Servientrega:', 'zira-shipping' ) . ' ' . esc_html( number_format( $raw_weight, 2 ) ) . ' → <strong>' . esc_html( $weight_kg ) . ' kg</strong></p>';
	echo '<p><strong>' . esc_html__( 'Ciudad:', 'zira-shipping' ) . '</strong> ' . esc_html( $city_short ?: '—' ) . '</p>';
	echo '<p><strong>' . esc_html__( 'Zona:', 'zira-shipping' ) . '</strong> ' . esc_html( $zone_label ) . '</p>';
	echo '<p style="margin-top:6px"><strong>' . esc_html__( 'Servientrega:', 'zira-shipping' ) . '</strong> <span class="zira-big">$' . esc_html( number_format( $cost, 2 ) ) . '</span></p>';

	echo '</div>';
}

/**
 * Peso real sin redondeo (para mostrar en el visor).
 */
function zira_shipping_calc_raw_weight( \WC_Order $order ): float {
	$weight         = 0.0;
	$default_weight = 2.0;

	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			$weight += $default_weight * $item->get_quantity();
			continue;
		}

		$w = method_exists( $product, 'get_weight' ) ? (float) $product->get_weight() : 0;
		if ( $w <= 0 ) {
			$w = $default_weight;
		}
		$weight += $w * $item->get_quantity();
	}

	return $weight;
}

// ─── Admin: Auto-provincia desde la ciudad ───────────────────

/**
 * Mapa de nombre de provincia → código WC Ecuador.
 */
function zira_shipping_province_map(): array {
	return array(
		'AZUAY'              => 'EC-A',
		'BOLIVAR'            => 'EC-B',
		'CANAR'              => 'EC-F',
		'CARCHI'             => 'EC-C',
		'CHIMBORAZO'         => 'EC-H',
		'COTOPAXI'           => 'EC-X',
		'EL ORO'             => 'EC-O',
		'ESMERALDAS'         => 'EC-E',
		'GALAPAGOS'          => 'EC-W',
		'GUAYAS'             => 'EC-G',
		'IMBABURA'           => 'EC-I',
		'LOJA'               => 'EC-L',
		'LOS RIOS'           => 'EC-R',
		'MANABI'             => 'EC-M',
		'MORONA SANTIAGO'    => 'EC-S',
		'NAPO'               => 'EC-N',
		'ORELLANA'           => 'EC-D',
		'PASTAZA'            => 'EC-Y',
		'PICHINCHA'          => 'EC-P',
		'SANTA ELENA'        => 'EC-SE',
		'SANTO DOMINGO'      => 'EC-SD',
		'SUCUMBIOS'          => 'EC-U',
		'TUNGURAHUA'         => 'EC-T',
		'ZAMORA CHINCHIPE'   => 'EC-Z',
		'ZAMORA'             => 'EC-Z',
	);
}

add_action( 'woocommerce_process_shop_order_meta', 'zira_shipping_auto_fill_province', 45, 2 );

function zira_shipping_auto_fill_province( $order_id, $order = null ): void {
	$order = $order ?: wc_get_order( $order_id );
	if ( ! $order instanceof \WC_Order ) {
		return;
	}

	$province_map = zira_shipping_province_map();

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	foreach ( array( 'billing', 'shipping' ) as $type ) {
		$city_key  = "_{$type}_city";
		$state_key = "_{$type}_state";

		if ( empty( $_POST[ $city_key ] ) ) {
			continue;
		}

		$city_val  = sanitize_text_field( wp_unslash( $_POST[ $city_key ] ) );
		$parts     = explode( '-', $city_val );
		$province  = count( $parts ) > 1 ? strtoupper( trim( end( $parts ) ) ) : '';

		if ( ! empty( $province ) && isset( $province_map[ $province ] ) ) {
			$state_code = $province_map[ $province ];

			// Si el estado está vacío o es diferente, auto-llenarlo
			$current_state = isset( $_POST[ $state_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $state_key ] ) ) : '';
			if ( empty( $current_state ) || $current_state !== $state_code ) {
				$_POST[ $state_key ] = $state_code;

				$setter_billing = "set_{$type}_state";
				$setter_city    = "set_{$type}_city";
				if ( method_exists( $order, $setter_billing ) ) {
					$order->{$setter_billing}( $state_code );
				}
				if ( method_exists( $order, $setter_city ) ) {
					$order->{$setter_city}( $city_val );
				}
			}
		}
	}
	// phpcs:enable
}

// ─── Admin: Auto-añadir envío Zira al CREAR pedido ──────────
// Solo en la primera creación (woocommerce_new_order), NO en cada save.
// Así el usuario puede quitar el envío manualmente sin que se re-agregue.

add_action( 'woocommerce_new_order', 'zira_shipping_auto_add_on_create', 50, 2 );

function zira_shipping_auto_add_on_create( $order_id, $order ): void {
	if ( ! $order instanceof \WC_Order ) {
		$order = wc_get_order( $order_id );
	}
	if ( ! $order instanceof \WC_Order ) {
		return;
	}
	zira_shipping_ensure_shipping_method( $order );
}

/**
 * Marca el pedido cuando el usuario quita manualmente un envío Zira.
 * Así no se re-agrega en el próximo save.
 */
add_action( 'woocommerce_before_delete_order_item', 'zira_shipping_mark_removed' );

function zira_shipping_mark_removed( $item_id ): void {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$item = \WC_Order_Factory::get_order_item( $item_id );
	if ( ! $item || ! is_a( $item, 'WC_Order_Item_Shipping' ) ) {
		return;
	}

	if ( 'zira_shipping' !== $item->get_method_id() ) {
		return;
	}

	$order = $item->get_order();
	if ( $order instanceof \WC_Order ) {
		$order->update_meta_data( '_zira_shipping_removed', '1' );
		$order->save();
	}
}

/**
 * Limpia el flag cuando se agrega un envío Zira (manual o auto).
 */
add_action( 'woocommerce_new_order_item', 'zira_shipping_clear_removed', 10, 3 );

function zira_shipping_clear_removed( $item_id, $item, $order_id ): void {
	if ( ! is_a( $item, 'WC_Order_Item_Shipping' ) ) {
		return;
	}

	if ( 'zira_shipping' !== $item->get_method_id() ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( $order instanceof \WC_Order ) {
		$order->delete_meta_data( '_zira_shipping_removed' );
		$order->save();
	}
}

/**
 * Si el pedido tiene productos pero no tiene método de envío Zira,
 * lo añade automáticamente.
 *
 * Usa static flag para evitar doble ejecución cuando
 * woocommerce_process_shop_order_meta y woocommerce_before_order_object_save
 * se disparan ambos en HPOS.
 */
function zira_shipping_ensure_shipping_method( \WC_Order $order ): void {
	static $already_ran = array();

	$order_id = $order->get_id();
	if ( isset( $already_ran[ $order_id ] ) ) {
		return;
	}
	$already_ran[ $order_id ] = true;

	if ( empty( $order->get_items() ) ) {
		return;
	}

	if ( ! empty( $order->get_shipping_methods() ) ) {
		return;
	}

	// Si el usuario quitó el envío manualmente, no re-agregar
	if ( $order->get_meta( '_zira_shipping_removed' ) ) {
		return;
	}

	// Detectar ciudad: 1º POST, 2º order
	$city  = zira_shipping_get_post_city();
	if ( empty( $city ) ) {
		$city = $order->get_shipping_city() ?: $order->get_billing_city();
	}

	$state = zira_shipping_get_post_state();
	if ( empty( $state ) ) {
		$state = $order->get_shipping_state() ?: $order->get_billing_state();
	}

	$state = strtoupper( $state ?? '' );
	$city  = $city ?? '';
	$zone  = zira_shipping_get_zone( $state, $city );

	// Cuenca → sin envío automático
	if ( 'local' === $zone || 'local_cercano' === $zone ) {
		return;
	}

	// Calcular peso y costo real (no dejar en $0)
	$weight = zira_shipping_calc_raw_weight( $order );
	$weight_kg = max( 2, (int) ceil( $weight ) );
	$cost = zira_shipping_admin_cost( $weight_kg, $zone );

	$item = new \WC_Order_Item_Shipping();
	$item->set_method_title( __( 'Envío Nacional - Servientrega', 'zira-shipping' ) );
	$item->set_method_id( 'zira_shipping' );
	$item->set_total( (string) $cost );
	$item->update_meta_data( 'zira_zone', $zone );
	$item->update_meta_data( 'zira_weight', $weight_kg );
	$order->add_item( $item );
	$order->delete_meta_data( '_zira_shipping_removed' );
	$order->save();
}

// ─── Admin: Auto-calcular envío al guardar pedido ─────────────

/**
 * Obtener la ciudad del POST (shipping → billing).
 *
 * @return string
 */
function zira_shipping_get_post_city(): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['_shipping_city'] ) ) {
		return sanitize_text_field( wp_unslash( $_POST['_shipping_city'] ) );
	}
	if ( ! empty( $_POST['_billing_city'] ) ) {
		return sanitize_text_field( wp_unslash( $_POST['_billing_city'] ) );
	}
	// phpcs:enable
	return '';
}

/**
 * Obtener la provincia/estado del POST (shipping → billing).
 *
 * @return string
 */
function zira_shipping_get_post_state(): string {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['_shipping_state'] ) ) {
		return sanitize_text_field( wp_unslash( $_POST['_shipping_state'] ) );
	}
	if ( ! empty( $_POST['_billing_state'] ) ) {
		return sanitize_text_field( wp_unslash( $_POST['_billing_state'] ) );
	}
	// phpcs:enable
	return '';
}

add_action( 'woocommerce_process_shop_order_meta', 'zira_shipping_admin_calculate', 99, 2 );
add_action( 'woocommerce_before_order_object_save', 'zira_shipping_admin_calculate_hpos', 99, 2 );

/**
 * Calcula automáticamente el costo de envío al guardar/crear un pedido en el admin.
 *
 * WooCommerce admin no ejecuta calculate_shipping() para métodos custom.
 * Este hook detecta si Zira Shipping está seleccionado y recalcula el costo
 * basado en la dirección de envío y los productos del pedido.
 *
 * @since 2.0.0
 *
 * @param int      $order_id ID del pedido.
 * @param WC_Order $order    Objeto del pedido (HPOS) o post (legacy).
 */
function zira_shipping_admin_calculate( $order_id, $order = null ): void {
	$order = $order ?: wc_get_order( $order_id );
	if ( ! $order instanceof \WC_Order ) {
		return;
	}
	zira_shipping_maybe_update_shipping_cost( $order );
}

function zira_shipping_admin_calculate_hpos( \WC_Order $order ): void {
	zira_shipping_maybe_update_shipping_cost( $order );
}

/**
 * Si el pedido tiene Zira Shipping como método, recalcula y actualiza el costo.
 *
 * Usa static flag para evitar doble ejecución en HPOS.
 */
function zira_shipping_maybe_update_shipping_cost( \WC_Order $order ): void {
	static $already_ran = array();

	$order_id = $order->get_id();

	// Para pedidos nuevos (id=0), permitir siempre (el flag por ID no funciona)
	if ( $order_id > 0 && isset( $already_ran[ $order_id ] ) ) {
		return;
	}
	if ( $order_id > 0 ) {
		$already_ran[ $order_id ] = true;
	}

	$shipping_methods = $order->get_shipping_methods();

	$has_zira = false;
	foreach ( $shipping_methods as $item ) {
		if ( 'zira_shipping' === $item->get_method_id() ) {
			$has_zira = true;
			break;
		}
	}

	if ( ! $has_zira ) {
		// Si no hay envío Zira, intentar auto-añadirlo
		// (útil cuando el usuario cambia la ciudad en un pedido existente)
		if ( ! empty( $order->get_items() ) && empty( $order->get_shipping_methods() ) ) {
			zira_shipping_ensure_shipping_method( $order );
		}
		return;
	}

	// Leer del POST (más fresco durante "Recalcular")
	// 1º shipping, 2º billing como fallback
	$city  = '';
	$state = '';

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$city = zira_shipping_get_post_city();
	if ( empty( $city ) ) {
		$city = $order->get_shipping_city();
	}
	if ( empty( $city ) ) {
		$city = $order->get_billing_city();
	}

	$state = zira_shipping_get_post_state();
	if ( empty( $state ) ) {
		$state = $order->get_shipping_state();
	}
	if ( empty( $state ) ) {
		$state = $order->get_billing_state();
	}
	// phpcs:enable

	$state = strtoupper( $state ?? '' );
	$city  = $city ?? '';
	$zone  = zira_shipping_get_zone( $state, $city );

	// Si es zona local o cercana, quitar envío Zira (se ingresa manual)
	if ( 'local' === $zone || 'local_cercano' === $zone ) {
		$items_to_remove = array();
		foreach ( $shipping_methods as $item_id => $item ) {
			if ( 'zira_shipping' === $item->get_method_id() ) {
				$items_to_remove[] = $item_id;
			}
		}
		foreach ( $items_to_remove as $item_id ) {
			$order->remove_item( $item_id );
		}
		if ( ! empty( $items_to_remove ) ) {
			$order->save();
		}
		return;
	}

	// Construir contenidos
	$weight = zira_shipping_calc_raw_weight( $order );
	$weight_kg = max( 2, (int) ceil( $weight ) );
	$cost = zira_shipping_admin_cost( $weight_kg, $zone );

	// Actualizar cada línea de envío de Zira
	foreach ( $shipping_methods as $item_id => $item ) {
		if ( 'zira_shipping' === $item->get_method_id() ) {
			$item->set_total( (string) $cost );
			$item->update_meta_data( 'zira_zone', $zone );
			$item->update_meta_data( 'zira_weight', $weight_kg );
			$item->save();
		}
	}
}

/**
 * Construye un array de contenidos similar al carrito para calcular el peso.
 */
function zira_shipping_build_cart_contents( \WC_Order $order ): array {
	$contents = array();
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product ) {
			continue;
		}
		$contents[] = array(
			'data'     => $product,
			'quantity' => $item->get_quantity(),
		);
	}
	return $contents;
}

/**
 * Calcula el costo (misma lógica que el método principal).
 */
function zira_shipping_admin_cost( float $weight, string $zone ): float {
	$pricing   = zira_shipping_get_pricing_table();
	$zone_data = $pricing[ $zone ] ?? $pricing['nacional'];
	$weight_kg = (int) max( 2.0, ceil( $weight ) );

	if ( 1 === $weight_kg && isset( $zone_data['doc_1kg'] ) ) {
		return $zone_data['doc_1kg'];
	}

	if ( isset( $zone_data['brackets'][ $weight_kg ] ) ) {
		return $zone_data['brackets'][ $weight_kg ];
	}

	$brackets    = $zone_data['brackets'];
	$max_bracket = max( array_keys( $brackets ) );

	if ( $weight_kg > $max_bracket ) {
		$base      = $zone_data['base'];
		$adicional = $zone_data['adicional'];
		$subtotal  = $base + ( $weight_kg - 2 ) * $adicional;
		return zira_shipping_round_up_025( $subtotal * 1.15 );
	}

	return $brackets[ $max_bracket ];
}

// ─── Label con logo ───────────────────────────────────────────

add_filter( 'woocommerce_cart_shipping_method_full_label', 'zira_shipping_method_label', 10, 2 );

function zira_shipping_method_label( string $full_label, $method ): string {
	if ( $method->get_method_id() !== 'zira_shipping' ) {
		return $full_label;
	}

	$logo_url = ZIRA_SHIPPING_PLUGIN_URL . 'images/logoservientrega.png?v=' . ZIRA_SHIPPING_VERSION;

	return sprintf(
		'<div class="zira-shipping-row">'
			. '<img src="%s" width="40" height="20" class="zira-shipping-logo" alt="Servientrega" />'
			. '<span class="zira-shipping-label">%s</span>'
		. '</div>',
		esc_url( $logo_url ),
		$full_label
	);
}
