<?php
/**
 * Assets class.
 *
 * This will manage the assets file (css/js)
 * for adding style and JS functionality.
 *
 * @package DaxHurley\OAuthLogin
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use function DaxHurley\OAuthLogin\plugin;

/**
 * Class Assets
 *
 * @package DaxHurley\OAuthLogin\Modules
 */
class Assets implements ModuleInterface {

	/**
	 * Handle for login button style.
	 *
	 * @var string
	 */
	const LOGIN_BUTTON_STYLE_HANDLE = 'wp-oauth-login';

	/**
	 * Handle for admin style.
	 *
	 * @var string
	 */
	const ADMIN_STYLE_HANDLE = 'wp-oauth-login-admin';

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'assets';
	}

	/**
	 * Initialize the assets file
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'login_enqueue_scripts', [ $this, 'enqueue_login_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
	}

	/**
	 * Register style/script for Login Page.
	 *
	 * @action login_enqueue_scripts
	 *
	 * @return void
	 */
	public function register_login_styles(): void {
		$this->register_style( self::LOGIN_BUTTON_STYLE_HANDLE, 'build/css/button/style.css' );
	}

	/**
	 * Register style/script for Admin Page.
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @return void
	 */
	public function register_admin_styles(): void {
		$this->register_style( self::ADMIN_STYLE_HANDLE, 'css/admin.css' );
	}

	/**
	 * Enqueue the login style.
	 *
	 * @return void
	 */
	public function enqueue_login_styles(): void {
		/**
		 * If style is not registered, register it.
		 */
		if ( ! wp_style_is( self::LOGIN_BUTTON_STYLE_HANDLE, 'registered' ) ) {
			$this->register_login_styles();
		}

		if ( ! wp_script_is( 'wp-oauth-login-script', 'registered' ) ) {
			$this->register_script( 'wp-oauth-login-script', 'build/js/login.js' );
		}

		wp_enqueue_script( 'wp-oauth-login-script' );
		wp_enqueue_style( self::LOGIN_BUTTON_STYLE_HANDLE );
	}

	/**
	 * Enqueue the admin style.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_styles( string $hook_suffix ): void {
		// Only enqueue on our settings page
		if ( 'settings_page_login-with-oauth' !== $hook_suffix ) {
			return;
		}

		/**
		 * If style is not registered, register it.
		 */
		if ( ! wp_style_is( self::ADMIN_STYLE_HANDLE, 'registered' ) ) {
			$this->register_admin_styles();
		}

		wp_enqueue_style( self::ADMIN_STYLE_HANDLE );
	}

	/**
	 * Register a new script.
	 *
	 * @param  string           $handle    Name of the script. Should be unique.
	 * @param  string|bool      $file      script file, path of the script relative to the assets/build/ directory.
	 * @param  array            $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param  string|bool|null $ver       Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param  bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                     Default 'false'.
	 * @return bool Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( $handle, $file, $deps = [], $ver = false, $in_footer = true ) {
		$src     = sprintf( '%1$sassets/%2$s', plugin()->url, $file );
		$version = $this->get_file_version( $file, $ver );

		return wp_register_script( $handle, $src, $deps, $version, $in_footer );
	}

	/**
	 * Register a CSS stylesheet.
	 *
	 * @param string           $handle Name of the stylesheet. Should be unique.
	 * @param string|bool      $file   style file, path of the script relative to the assets/build/ directory.
	 * @param array            $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying script version number, if not set, filetime will be used as version number.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 *
	 * @return bool Whether the style has been registered. True on success, false on failure.
	 */
	public function register_style( $handle, $file, $deps = [], $ver = false, $media = 'all' ) {
		$src     = sprintf( '%1$sassets/%2$s', plugin()->url, $file );
		$version = $this->get_file_version( $file, $ver );

		return wp_register_style( $handle, $src, $deps, $version, $media );
	}

	/**
	 * Get file version.
	 *
	 * @param string             $file File path.
	 * @param int|string|boolean $ver  File version.
	 *
	 * @return bool|false|int
	 */
	private function get_file_version( $file, $ver = false ) {
		if ( ! empty( $ver ) ) {
			return $ver;
		}

		$file_path = sprintf( '%s/%s', plugin()->assets_dir, $file );

		return file_exists( $file_path ) ? filemtime( $file_path ) : false;
	}
}
