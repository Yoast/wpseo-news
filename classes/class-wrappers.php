<?php

class WPSEO_News_Wrappers {

	/**
	 * Fallback for admin_header.
	 *
	 * @param bool   $form
	 * @param string $option_long_name
	 * @param string $option
	 * @param bool   $contains_files
	 *
	 * @return mixed
	 */
	public static function admin_header( $form = true, $option_long_name = 'yoast_wpseo_options', $option = 'wpseo', $contains_files = false ) {

		if ( method_exists( 'Yoast_Form', 'admin_header' ) ) {
			Yoast_Form::get_instance()->admin_header( $form, $option, $contains_files, $option_long_name );

			return;
		}

		return self::admin_pages()->admin_header( true, 'yoast_wpseo_news_options', 'wpseo_news' );
	}

	/**
	 * Fallback for admin_footer.
	 *
	 * @param bool $submit
	 * @param bool $show_sidebar
	 *
	 * @return mixed
	 */
	public static function admin_footer( $submit = true, $show_sidebar = true ) {

		if ( method_exists( 'Yoast_Form', 'admin_footer' ) ) {
			Yoast_Form::get_instance()->admin_footer( $submit, $show_sidebar );

			return;
		}

		return self::admin_pages()->admin_footer( $submit, $show_sidebar );
	}

	/**
	 * Fallback for the textinput method.
	 *
	 * @param string $var
	 * @param string $label
	 * @param string $option
	 *
	 * @return mixed
	 */
	public static function textinput( $var, $label, $option = '' ) {
		if ( method_exists( 'Yoast_Form', 'textinput' ) ) {
			if ( $option !== '' ) {
				Yoast_Form::get_instance()->set_option( $option );
			}

			Yoast_Form::get_instance()->textinput( $var, $label );

			return;
		}

		return self::admin_pages()->textinput( $var, $label, $option );
	}

	/**
	 * Wrapper for select method.
	 *
	 * @param string $var
	 * @param string $label
	 * @param array  $values
	 * @param string $option
	 *
	 * @return mixed
	 */
	public static function select( $var, $label, $values, $option = '' ) {
		if ( method_exists( 'Yoast_Form', 'select' ) ) {
			if ( $option !== '' ) {
				Yoast_Form::get_instance()->set_option( $option );
			}

			Yoast_Form::get_instance()->select( $var, $label, $values );

			return;
		}

		return self::admin_pages()->select( $var, $label, $option );
	}

	/**
	 * Wrapper for checkbox method.
	 *
	 * @param        $var
	 * @param        $label
	 * @param bool   $label_left
	 * @param string $option
	 *
	 * @return mixed
	 */
	public static function checkbox( $var, $label, $label_left = false, $option = '' ) {
		if ( method_exists( 'Yoast_Form', 'checkbox' ) ) {
			if ( $option !== '' ) {
				Yoast_Form::get_instance()->set_option( $option );
			}

			Yoast_Form::get_instance()->checkbox( $var, $label, $label_left );

			return;
		}

		return self::admin_pages()->checkbox( $var, $label, $label_left, $option );
	}

	/**
	 * Returns the wpseo_admin pages global variable.
	 *
	 * @return mixed
	 */
	private static function admin_pages() {
		global $wpseo_admin_pages;

		return $wpseo_admin_pages;
	}
}
