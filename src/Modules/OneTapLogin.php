<?php
/**
 * One Tap Login Class.
 *
 * This class will be responsible for handling
 * OAuth's one tap login for web functioning.
 *
 * @package RtCamp\OAuthLogin\Modules
 * @since 1.0.16
 */

declare(strict_types=1);

namespace RtCamp\OAuthLogin\Modules;

use Exception;
use RtCamp\OAuthLogin\Utils\Authenticator;
use RtCamp\OAuthLogin\Utils\OAuthClient;
use RtCamp\OAuthLogin\Utils\Helper;
use RtCamp\OAuthLogin\Interfaces\Module;
use RtCamp\OAuthLogin\Utils\TokenVerifier;
use function RtCamp\OAuthLogin\plugin;

/**
 * Class OneTapLogin
 *
 * @package RtCamp\OAuthLogin\Modules
 */
class OneTapLogin implements Module {
	/**
	 * Settings Module.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Token verifier.
	 *
	 * @var TokenVerifier
	 */
	private $token_verifier;

	/**
	 * OAuth client instance.
	 *
	 * @var OAuthClient
	 */
	private $oauth_client;

	/**
	 * Authenticator service.
	 *
	 * @var Authenticator
	 */
	private $authenticator;

	/**
	 * OneTapLogin constructor.
	 *
	 * @param Settings      $settings Settings object.
	 * @param TokenVerifier $verifier Token verifier object.
	 * @param OAuthClient  $client   OAuth client instance.
	 * @param Authenticator $authenticator Authenticator service instance.
	 */
	public function __construct( Settings $settings, TokenVerifier $verifier, OAuthClient $client, Authenticator $authenticator ) {
		$this->settings       = $settings;
		$this->token_verifier = $verifier;
		$this->oauth_client  = $client;
		$this->authenticator  = $authenticator;
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'one_tap_login';
	}

	/**
	 * Module Initialization activity.
	 *
	 * Everything will happen if and only if one tap is active in settings.
	 */
	public function init(): void {
		if ( $this->settings->one_tap_login ) {
			// If oneTap login is enabled sitewide, we need to enqueue it using both wp_enqueue_scripts and login_enqueue_scripts. If it is not sitewide, we only need to enqueue it using login_enqueue_scripts.
			if ( 'sitewide' === $this->settings->one_tap_login_screen ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'one_tap_scripts' ] );
				add_action( 'wp_footer', [ $this, 'one_tap_prompt' ], 10000 );
			}
			add_action( 'login_enqueue_scripts', [ $this, 'one_tap_scripts' ] );
			add_action( 'login_footer', [ $this, 'one_tap_prompt' ] );
			add_action( 'wp_ajax_nopriv_validate_id_token', [ $this, 'validate_token' ] );
			add_action( 'rtcamp.id_token_verified', [ $this, 'authenticate' ] );
		}
	}

	/**
	 * Show one tap prompt markup.
	 *
	 * @return void
	 */
	public function one_tap_prompt(): void {
		?>
		<div id="g_id_onload" data-use_fedcm_for_prompt="true" data-client_id="<?php echo esc_attr( $this->settings->client_id ); ?>" data-login_uri="<?php echo esc_attr( wp_login_url() ); ?>" data-callback="LoginWithOAuthDataCallBack"></div>
		<?php
	}

	/**
	 * Enqueue one-tap related scripts.
	 *
	 * @return void
	 */
	public function one_tap_scripts(): void {
		if ( is_user_logged_in() ) {
			return;
		}

		$filename     = ( defined( 'WP_SCRIPT_DEBUG' ) && true === WP_SCRIPT_DEBUG ) ? 'onetap.min.js' : 'onetap.js';
		$redirects_to = Helper::get_redirect_url();

		Helper::set_redirect_state_filter( $redirects_to );

		wp_enqueue_script(
			'login-with-oauth-one-tap',
			'https://accounts.oauth.com/gsi/client',
			[],
			filemtime( trailingslashit( plugin()->path ) . 'assets/build/js/onetap.js' ),
			true
		);

		$data = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'state'   => $this->oauth_client->state(),
			'homeurl' => get_option( 'home', '' ),
		];

		Helper::remove_redirect_state_filter();

		wp_register_script(
			'login-with-oauth-one-tap-js',
			trailingslashit( plugin()->url ) . 'assets/build/js/' . $filename,
			[
				'wp-i18n',
			],
			filemtime( trailingslashit( plugin()->path ) . 'assets/build/js/onetap.js' ),
			true
		);

		wp_add_inline_script(
			'login-with-oauth-one-tap-js',
			'var TempAccessOneTap=' . json_encode( $data ), //phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
			'before'
		);

		wp_enqueue_script( 'login-with-oauth-one-tap-js' );

		// @see https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/
		// @see https://developer.wordpress.org/reference/functions/wp_set_script_translations/
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'login-with-oauth-one-tap-js', 'login-with-oauth' );
		}
	}

	/**
	 * Validate the ID token.
	 *
	 * @return void
	 * @throws Exception Credential verification failure exception.
	 */
	public function validate_token(): void {
		try {
			$token    = Helper::filter_input( INPUT_POST, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$verified = $this->token_verifier->verify_token( $token );

			if ( ! $verified ) {
				throw new Exception( __( 'Cannot verify the credentials', 'login-with-oauth' ) );
			}

			/**
			 * Do something when token has been verified successfully.
			 *
			 * If we are here that means ID token has been verified.
			 *
			 * @since 1.0.16
			 */
			do_action( 'rtcamp.id_token_verified' );

			$redirect_to   = apply_filters( 'rtcamp.oauth_default_redirect', admin_url() );
			$state         = Helper::filter_input( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( is_array( $decoded_state ) && ! empty( $decoded_state['provider'] ) && 'oauth' === $decoded_state['provider'] ) {
				$redirect_to = $decoded_state['redirect_to'] ?? $redirect_to;
			}

			wp_send_json_success(
				[
					'redirect' => $redirect_to,
				]
			);
			die;

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Authenticate the user in WordPress.
	 *
	 * @return void
	 * @throws Exception Authentication exception.
	 */
	public function authenticate(): void {
		$user = $this->token_verifier->current_user();

		if ( is_null( $user ) ) {
			throw new Exception( esc_html__( 'User not found to authenticate', 'login-with-oauth' ) );
		}

		$wp_user = $this->authenticator->authenticate( $user );
		$this->authenticator->set_auth_cookies( $wp_user );
	}
}
