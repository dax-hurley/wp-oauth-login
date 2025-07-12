<?php
/**
 * One Tap Login.
 *
 * This will handle the one tap login functionality.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\Helper;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Utils\Authenticator;
use DaxHurley\OAuthLogin\Utils\ProviderManager;
use DaxHurley\OAuthLogin\Utils\TokenVerifier;
use Exception;

/**
 * Class OneTapLogin.
 *
 * @package DaxHurley\OAuthLogin\Modules
 */
class OneTapLogin implements ModuleInterface {
	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Token verifier instance.
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
	 * Authenticator instance.
	 *
	 * @var Authenticator
	 */
	private $authenticator;

	/**
	 * Provider manager instance.
	 *
	 * @var ProviderManager
	 */
	private $provider_manager;

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
		$this->provider_manager = plugin()->container()->get( 'provider_manager' );
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
			add_action( 'daxhurley.id_token_verified', [ $this, 'authenticate' ] );
		}
	}

	/**
	 * Show one tap prompt markup.
	 *
	 * @return void
	 */
	public function one_tap_prompt(): void {
		$provider = $this->provider_manager->get_first_configured_provider();
		if ( ! $provider ) {
			return;
		}

		// Check if this provider supports one-tap login
		if ( ! $this->provider_supports_one_tap( $provider ) ) {
			return;
		}

		$one_tap_config = $this->get_one_tap_config( $provider );
		?>
		<div id="g_id_onload" 
			data-use_fedcm_for_prompt="true" 
			data-client_id="<?php echo esc_attr( $provider->get_client_id() ); ?>" 
			data-login_uri="<?php echo esc_attr( wp_login_url() ); ?>" 
			data-callback="LoginWithOAuthDataCallBack"
			<?php if ( ! empty( $one_tap_config['additional_attributes'] ) ): ?>
				<?php foreach ( $one_tap_config['additional_attributes'] as $key => $value ): ?>
					data-<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $value ); ?>"
				<?php endforeach; ?>
			<?php endif; ?>
		></div>
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

		$provider = $this->provider_manager->get_first_configured_provider();
		if ( ! $provider ) {
			return;
		}

		// Check if this provider supports one-tap login
		if ( ! $this->provider_supports_one_tap( $provider ) ) {
			return;
		}

		$filename     = ( defined( 'WP_SCRIPT_DEBUG' ) && true === WP_SCRIPT_DEBUG ) ? 'onetap.min.js' : 'onetap.js';
		$redirects_to = Helper::get_redirect_url();

		Helper::set_redirect_state_filter( $redirects_to );

		// Get the one-tap script URL for this provider
		$one_tap_config = $this->get_one_tap_config( $provider );
		$script_url = $one_tap_config['script_url'] ?? 'https://accounts.google.com/gsi/client';

		wp_enqueue_script(
			'login-with-oauth-one-tap',
			$script_url,
			[],
			filemtime( trailingslashit( plugin()->path ) . 'assets/build/js/onetap.js' ),
			true
		);

		$data = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'state'   => $provider->get_state(),
			'homeurl' => get_option( 'home', '' ),
			'provider' => $provider->get_name(),
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
	 * Check if a provider supports one-tap login.
	 *
	 * @param \DaxHurley\OAuthLogin\Interfaces\Provider $provider Provider instance.
	 * @return bool
	 */
	private function provider_supports_one_tap( $provider ): bool {
		$config = $provider->get_config();
		
		// Check if provider explicitly supports one-tap
		if ( isset( $config['supports_one_tap'] ) ) {
			return (bool) $config['supports_one_tap'];
		}

		// Default: Only Google supports one-tap login
		return $provider->get_name() === 'google';
	}

	/**
	 * Get one-tap configuration for a provider.
	 *
	 * @param \DaxHurley\OAuthLogin\Interfaces\Provider $provider Provider instance.
	 * @return array
	 */
	private function get_one_tap_config( $provider ): array {
		$config = $provider->get_config();
		
		// Default configuration
		$default_config = [
			'script_url' => 'https://accounts.google.com/gsi/client',
			'additional_attributes' => [],
		];

		// Allow providers to override one-tap configuration
		if ( ! empty( $config['one_tap_config'] ) ) {
			return array_merge( $default_config, $config['one_tap_config'] );
		}

		// Provider-specific configurations
		switch ( $provider->get_name() ) {
			case 'google':
				return $default_config;
			
			default:
				// For other providers, return empty config (no one-tap support)
				return [
					'script_url' => '',
					'additional_attributes' => [],
				];
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
			$provider = Helper::filter_input( INPUT_POST, 'provider', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			
			$verified = $this->token_verifier->verify_token( $token, $provider );

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
			do_action( 'daxhurley.id_token_verified' );

			$redirect_to   = apply_filters( 'daxhurley.oauth_default_redirect', admin_url() );
			$state         = Helper::filter_input( INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			if ( is_array( $decoded_state ) && ! empty( $decoded_state['provider'] ) ) {
				$redirect_to = $decoded_state['redirect_to'] ?? $redirect_to;
			}

			wp_send_json_success(
				[
					'redirect_to' => $redirect_to,
				]
			);

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Authenticate user after token verification.
	 *
	 * @return void
	 */
	public function authenticate(): void {
		$current_user = $this->token_verifier->current_user();

		if ( ! $current_user ) {
			return;
		}

		$user = $this->authenticator->authenticate( $current_user );

		if ( $user instanceof \WP_User ) {
			/**
			 * Fires once the user has been authenticated via OAuth one tap login.
			 *
			 * @since 1.0.16
			 *
			 * @param WP_User $user WP User object.
			 */
			do_action( 'daxhurley.oauth_user_authenticated', $user );
		}
	}
}
