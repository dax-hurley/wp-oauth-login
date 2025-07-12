<?php
/**
 * Abstract Provider Class.
 *
 * Base class for all OAuth providers.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Providers;

use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;
use Exception;

/**
 * Abstract Class AbstractProvider
 *
 * @package DaxHurley\OAuthLogin\Providers
 */
abstract class AbstractProvider implements ProviderInterface {

	/**
	 * Provider configuration.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Provider name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Provider display name.
	 *
	 * @var string
	 */
	protected $display_name = '';

	/**
	 * Authorization URL.
	 *
	 * @var string
	 */
	protected $authorization_url = '';

	/**
	 * Token URL.
	 *
	 * @var string
	 */
	protected $token_url = '';

	/**
	 * User info URL.
	 *
	 * @var string
	 */
	protected $user_info_url = '';

	/**
	 * Default scopes.
	 *
	 * @var array
	 */
	protected $default_scopes = [];

	/**
	 * AbstractProvider constructor.
	 *
	 * @param array $config Provider configuration.
	 */
	public function __construct( array $config = [] ) {
		$this->config = $config;
	}

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->display_name;
	}

	/**
	 * Get the authorization URL.
	 *
	 * @return string
	 */
	public function get_authorization_url(): string {
		return $this->authorization_url;
	}

	/**
	 * Get the token URL.
	 *
	 * @return string
	 */
	public function get_token_url(): string {
		return $this->token_url;
	}

	/**
	 * Get the user info URL.
	 *
	 * @return string
	 */
	public function get_user_info_url(): string {
		return $this->user_info_url;
	}

	/**
	 * Get the default scopes for this provider.
	 *
	 * @return array
	 */
	public function get_default_scopes(): array {
		return $this->default_scopes;
	}

	/**
	 * Get the provider configuration.
	 *
	 * @return array
	 */
	public function get_config(): array {
		return $this->config;
	}

	/**
	 * Set the provider configuration.
	 *
	 * @param array $config Configuration array.
	 * @return void
	 */
	public function set_config( array $config ): void {
		$this->config = $config;
	}

	/**
	 * Check if the provider is configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_secret() );
	}

	/**
	 * Get the provider's client ID.
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		return $this->config['client_id'] ?? '';
	}

	/**
	 * Get the provider's client secret.
	 *
	 * @return string
	 */
	public function get_client_secret(): string {
		return $this->config['client_secret'] ?? '';
	}

	/**
	 * Get the provider's redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_uri(): string {
		return $this->config['redirect_uri'] ?? '';
	}

	/**
	 * Set the redirect URI.
	 *
	 * @param string $redirect_uri Redirect URI.
	 * @return void
	 */
	public function set_redirect_uri( string $redirect_uri ): void {
		$this->config['redirect_uri'] = $redirect_uri;
	}

	/**
	 * Get the provider's state parameter.
	 *
	 * @return string
	 */
	public function get_state(): string {
		$state_data['nonce']    = wp_create_nonce( 'login_with_oauth' );
		$state_data             = apply_filters( 'daxhurley.oauth_login_state', $state_data );
		$state_data['provider'] = $this->get_name();

		return base64_encode( wp_json_encode( $state_data ) );
	}

	/**
	 * Get the authorization URL with parameters.
	 *
	 * @return string
	 */
	public function get_authorization_url_with_params(): string {
		$plugin_scope = $this->get_default_scopes();

		$scope = apply_filters_deprecated(
			'wp_oauth_login_scopes',
			[
				$plugin_scope,
			],
			'1.0.15',
			'daxhurley.oauth_scope'
		);

		/**
		 * Filter the scopes.
		 *
		 * @param array $scope List of scopes.
		 */
		$scope = apply_filters( 'daxhurley.oauth_scope', $scope );

		$client_args = [
			'client_id'     => $this->get_client_id(),
			'redirect_uri'  => $this->get_redirect_uri(),
			'state'         => $this->get_state(),
			'scope'         => implode( ' ', $scope ),
			'access_type'   => 'online',
			'response_type' => 'code',
		];

		/**
		 * Filter the arguments for sending in query.
		 *
		 * This is useful in cases for example: choosing the correct prompt.
		 *
		 * @param array $client_args List of query arguments to send to OAuth provider.
		 */
		$client_args = apply_filters( 'daxhurley.oauth_client_args', $client_args );

		return $this->get_authorization_url() . '?' . http_build_query( $client_args );
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code Authorization code.
	 * @return \stdClass
	 * @throws Exception For access token errors.
	 */
	public function exchange_code_for_token( string $code ): \stdClass {
		$response = wp_remote_post(
			$this->get_token_url(),
			[
				'headers' => [
					'Accept' => 'application/json',
				],
				'body'    => [
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_redirect_uri(),
					'code'          => $code,
					'grant_type'    => 'authorization_code',
				],
			]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new Exception( esc_html__( 'Could not retrieve the access token, please try again.', 'login-with-oauth' ) );
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Get user information using access token.
	 *
	 * @param string $access_token Access token.
	 * @return \stdClass
	 * @throws Exception API Exception.
	 */
	public function get_user_info( string $access_token ): \stdClass {
		$user = wp_remote_get(
			$this->get_user_info_url() . '?access_token=' . $access_token,
			[
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $user ) ) {
			throw new Exception( esc_html__( 'Could not retrieve the user information, please try again.', 'login-with-oauth' ) );
		}

		return json_decode( wp_remote_retrieve_body( $user ) );
	}

	/**
	 * Get the provider's settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		return [
			'client_id'     => [
				'label'       => __( 'Client ID', 'login-with-oauth' ),
				'type'        => 'text',
				'description' => sprintf(
					/* translators: %1$s: Provider name, %2$s: Documentation URL */
					__( 'Create OAuth Client ID for %1$s at %2$s', 'login-with-oauth' ),
					$this->get_display_name(),
					$this->get_documentation_url()
				),
			],
			'client_secret' => [
				'label'       => __( 'Client Secret', 'login-with-oauth' ),
				'type'        => 'password',
				'description' => '',
			],
		];
	}

	/**
	 * Validate the provider's configuration.
	 *
	 * @return bool
	 */
	public function validate_config(): bool {
		return $this->is_configured();
	}

	/**
	 * Get the provider's documentation URL.
	 *
	 * @return string
	 */
	abstract protected function get_documentation_url(): string;
} 