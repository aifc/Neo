<?php
/**
 * WC_Appointments Customizer Class
 *
 * @since    3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Appointments_Customizer' ) ) :

	/**
	 * The WC_Appointments Customizer class
	 */
	class WC_Appointments_Customizer {

		/**
		 * Setup class.
		 *
		 * @since 1.0
		 */
		public function __construct() {
			add_action( 'customize_register',   array( $this, 'customize_register' ), 10 );
			add_action( 'wp_enqueue_scripts',   array( $this, 'add_customizer_css' ), 130 );
			add_action( 'customize_register',   array( $this, 'edit_default_customizer_settings' ), 99 );
			add_action( 'init',                 array( $this, 'default_theme_mod_values' ), 10 );

			add_action( 'after_switch_theme',   array( $this, 'set_wc_appointments_style_theme_mods' ) );
			add_action( 'customize_save_after', array( $this, 'set_wc_appointments_style_theme_mods' ) );
		}

		/**
		 * Returns an array of the desired default WC_Appointments Options
		 *
		 * @return array
		 */
		public static function get_wc_appointments_default_setting_values() {
			return apply_filters( 'wc_appointments_setting_default_values', $args = array(
				'wc_appointments_selection_color' => '#96588a',
			) );
		}

		/**
		 * Adds a value to each WC_Appointments setting if one isn't already present.
		 *
		 * @uses get_wc_appointments_default_setting_values()
		 */
		public function default_theme_mod_values() {
			foreach ( self::get_wc_appointments_default_setting_values() as $mod => $val ) {
				add_filter( 'theme_mod_' . $mod, array(
                    $this, 'get_theme_mod_value',
                ), 10 );
			}
		}

		/**
		 * Get theme mod value.
		 *
		 * @param string $value
		 * @return string
		 */
		public function get_theme_mod_value( $value ) {
			$key = substr( current_filter(), 10 );
			$set_theme_mods = get_theme_mods();

			if ( isset( $set_theme_mods[ $key ] ) ) {
				return $value;
			}

			$values = $this->get_wc_appointments_default_setting_values();

			return isset( $values[ $key ] ) ? $values[ $key ] : $value;
		}

		/**
		 * Set Customizer setting defaults.
		 * These defaults need to be applied separately as child themes can filter wc_appointments_setting_default_values
		 *
		 * @param  array $wp_customize the Customizer object.
		 * @uses   get_wc_appointments_default_setting_values()
		 */
		public function edit_default_customizer_settings( $wp_customize ) {
			foreach ( self::get_wc_appointments_default_setting_values() as $mod => $val ) {
				$wp_customize->get_setting( $mod )->default = $val;
			}
		}

		/**
		 * Add postMessage support for site title and description for the Theme Customizer along with several other settings.
		 *
		 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
		 * @since  1.0.0
		 */
		public function customize_register( $wp_customize ) {
			/**
			 * Add the section section
			 */
			$wp_customize->add_section( 'wc_appointments_section' , array(
				'title'             => __( 'Appointments', 'woocommerce-appointments' ),
				'priority'          => 45,
			) );

			/**
			 * Main color
			 */
			$wp_customize->add_setting( 'wc_appointments_selection_color', array(
				'default'           => apply_filters( 'wc_appointments_default_selection_color', '#96588a' ),
				'sanitize_callback' => 'sanitize_hex_color',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wc_appointments_selection_color', array(
				'label'             => __( 'Selection color', 'wc_appointments' ),
				'section'           => 'wc_appointments_section',
				'settings'          => 'wc_appointments_selection_color',
				'priority'          => 20,
			) ) );

		}

		/**
		 * Get all of the WC_Appointments theme mods.
		 *
		 * @return array $wc_appointments_theme_mods The WC_Appointments Theme Mods.
		 */
		public function get_wc_appointments_theme_mods() {
			$wc_appointments_theme_mods = array(
				'selection_color' => get_theme_mod( 'wc_appointments_selection_color' ),
			);

			return apply_filters( 'wc_appointments_theme_mods', $wc_appointments_theme_mods );
		}

		/**
		 * Get Customizer css.
		 *
		 * @see get_wc_appointments_theme_mods()
		 * @return array $styles the css
		 */
		public function get_css() {
			$wc_appointments_theme_mods = $this->get_wc_appointments_theme_mods();

			$styles = '
			.wc-appointments-date-picker .ui-datepicker td.ui-datepicker-current-day a,
			.wc-appointments-date-picker .ui-datepicker td.ui-datepicker-current-day a:hover {
				background-color: ' . $wc_appointments_theme_mods['selection_color'] . ';
			}

			.wc-appointments-appointment-form .slot-picker li.selected a,
			.wc-appointments-appointment-form .slot-picker li.selected:hover a {
			    background-color: ' . $wc_appointments_theme_mods['selection_color'] . ';
			}

			.wc-appointments-date-picker .ui-datepicker td.appointable-range .ui-state-default {
				background-color: ' . $wc_appointments_theme_mods['selection_color'] . ';
			}';

			return apply_filters( 'wc_appointments_customizer_css', $styles );
		}

		/**
		 * Assign WC_Appointments styles to individual theme mods.
		 *
		 * @return void
		 */
		public function set_wc_appointments_style_theme_mods() {
			set_theme_mod( 'wc_appointments_styles', $this->get_css() );
		}

		/**
		 * Add CSS in <head> for styles handled by the theme customizer
		 * If the Customizer is active pull in the raw css. Otherwise pull in the prepared theme_mods if they exist.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function add_customizer_css() {
			$wc_appointments_styles = get_theme_mod( 'wc_appointments_styles' );

			#if ( is_customize_preview() || ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) || ( false === $wc_appointments_styles ) ) {
			if ( is_customize_preview() ) {
				wp_add_inline_style( 'wc-appointments-styles', $this->get_css() );
			} elseif ( $wc_appointments_styles ) {
				wp_add_inline_style( 'wc-appointments-styles', get_theme_mod( 'wc_appointments_styles' ) );
			}
		}
	}

endif;

$GLOBALS['wc_appointments_customizer'] = new WC_Appointments_Customizer();
