<?php
/**
 * Login class.
 *
 * This will manage the login flow, which includes adding the
 * oauth login button on wp-login page, authorizing the user,
 * authenticating user and redirecting him to admin.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Modules;

use WP_User;
use WP_Error;
use stdClass;
use Throwable;
use Exception;
use DaxHurley\OAuthLogin\Utils\Helper;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Utils\Authenticator;
use DaxHurley\OAuthLogin\Utils\ProviderManager;
use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use function DaxHurley\OAuthLogin\plugin;

/**
 * Class Login.
 *
 * @package DaxHurley\OAuthLogin\Modules
 */
class Login implements ModuleInterface {
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
	 * Flag for determining whether the user has been authenticated
	 * from plugin.
	 *
	 * @var bool
	 */
	private $authenticated = false;

	/**
	 * Login constructor.
	 *
	 * @param OAuthClient  $client OAuth Client object.
	 * @param Authenticator $authenticator Settings object.
	 */
	public function __construct( OAuthClient $client, Authenticator $authenticator ) {
		$this->oauth_client     = $client;
		$this->authenticator = $authenticator;
		$this->provider_manager = plugin()->container()->get( 'provider_manager' );
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'login_flow';
	}

	/**
	 * Initialize login flow.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'login_form', [ $this, 'login_button' ] );
		// Priority is 20 because of issue: https://core.trac.wordpress.org/ticket/46748.
		add_action( 'authenticate', [ $this, 'authenticate' ], 20 );
		add_action( 'daxhurley.oauth_register_user', [ $this->authenticator, 'register' ] );
		add_action( 'daxhurley.oauth_redirect_url', [ $this, 'redirect_url' ] );
		add_action( 'daxhurley.oauth_user_created', [ $this, 'user_meta' ] );
		add_filter( 'daxhurley.oauth_login_state', [ $this, 'state_redirect' ] );
		add_action( 'wp_login', [ $this, 'login_redirect' ] );
	}

	/**
	 * Add the login button to login form.
	 *
	 * @return void
	 */
	public function login_button(): void {
		$configured_providers = $this->provider_manager->get_configured_providers();
		
		if ( empty( $configured_providers ) ) {
			return;
		}

		$template = trailingslashit( plugin()->template_dir ) . 'oauth-login-button.php';
		
		// For now, use the first configured provider
		// In the future, we could show multiple buttons for different providers
		$first_provider = reset( $configured_providers );
		$login_url = $first_provider->get_authorization_url_with_params();

		Helper::render_template(
			$template,
			[
				'login_url' => $login_url,
				'provider_name' => $first_provider->get_display_name(),
				'provider_config' => $first_provider->get_config(),
			]
		);
	}

	/**
	 * Authenticate the user.
	 *
	 * @param WP_User|null $user User object. Default is null.
	 *
	 * @return WP_User|WP_Error
	 * @throws Exception During authentication.
	 */
	public function authenticate( $user = null ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$code = Helper::filter_input( INPUT_GET, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $code ) {
			return $user;
		}

		$state         = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! is_array( $decoded_state ) || empty( $decoded_state['provider'] ) ) {
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'login_with_oauth' ) ) {
			return $user;
		}

		// Get the provider from the state
		$provider = $this->provider_manager->get_provider( $decoded_state['provider'] );
		if ( ! $provider ) {
			return $user;
		}

		try {
			// Create a new OAuth client for this specific provider
			$oauth_client = new OAuthClient( $provider );
			$oauth_client->set_access_token( $code );
			$user = $oauth_client->user();
			$user = $this->authenticator->authenticate( $user );

			if ( $user instanceof WP_User ) {
				$this->authenticated = true;

				/**
				 * Fires once the user has been authenticated via OAuth.
				 *
				 * @since 1.3.0
				 *
				 * @param WP_User $user WP User object.
				 */
				do_action( 'daxhurley.oauth_user_authenticated', $user );

				return $user;
			}

			throw new Exception( __( 'Could not authenticate the user, please try again.', 'wp-oauth-login' ) );

		} catch ( Throwable $e ) {
			return new WP_Error( 'oauth_login_failed', $e->getMessage() );
		}
	}

	/**
	 * Add extra meta information about user.
	 *
	 * @param int $uid  User ID.
	 *
	 * @return void
	 */
	public function user_meta( int $uid ) {
		add_user_meta( $uid, 'oauth_user', 1, true );
		add_user_meta( $uid, 'oauth_provider', 'oauth', true );
	}

	/**
	 * Redirect URL.
	 *
	 * This is useful when redirect URL is present when
	 * trying to login to wp-admin.
	 *
	 * @param string $url Redirect URL address.
	 *
	 * @return string
	 */
	public function redirect_url( string $url ): string {

		return remove_query_arg( 'redirect_to', $url );
	}

	/**
	 * Add redirect_to location in state.
	 *
	 * @param array $state State data.
	 *
	 * @return array
	 */
	public function state_redirect( array $state ): array {
		$redirect_to = Helper::filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		/**
		 * Filter the default redirect URL in case redirect_to param is not available.
		 * Default to admin URL.
		 *
		 * @param string $admin_url Admin URL address.
		 */
		$state['redirect_to'] = $redirect_to ?? apply_filters( 'daxhurley.oauth_default_redirect', admin_url() );

		return $state;
	}

	/**
	 * Add a redirect once user has been authenticated successfully.
	 *
	 * @return void
	 */
	public function login_redirect(): void {
		$state = Helper::filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $state || ! $this->authenticated ) {
			return;
		}

		$state = base64_decode( $state );
		$state = $state ? json_decode( $state ) : null;

		if ( ( $state instanceof stdClass ) && ! empty( $state->provider ) && ! empty( $state->redirect_to ) ) {
			wp_safe_redirect( $state->redirect_to, 302, 'WP OAuth Login' );
			exit;
		}
	}
}
