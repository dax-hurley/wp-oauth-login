<?php
/**
 * Google OAuth Provider.
 *
 * Google OAuth 2.0 provider implementation.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Providers;

/**
 * Class GoogleProvider
 *
 * @package DaxHurley\OAuthLogin\Providers
 */
class GoogleProvider extends AbstractProvider {

	/**
	 * GoogleProvider constructor.
	 *
	 * @param array $config Provider configuration.
	 */
	public function __construct( array $config = [] ) {
		parent::__construct( $config );
		
		$this->name         = $config['name'] ?? 'google';
		$this->display_name = $config['display_name'] ?? __( 'Google', 'wp-oauth-login' );
		$this->authorization_url = 'https://accounts.google.com/o/oauth2/auth';
		$this->token_url    = 'https://oauth2.googleapis.com/token';
		$this->user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
		$this->default_scopes = [ 'email', 'profile', 'openid' ];

		// Set up Google-specific configuration
		$this->config['supports_one_tap'] = true;
		$this->config['certs_url'] = 'https://www.googleapis.com/oauth2/v1/certs';
		$this->config['valid_issuers'] = [ 'accounts.google.com', 'https://accounts.google.com' ];
		$this->config['one_tap_config'] = [
			'script_url' => 'https://accounts.google.com/gsi/client',
			'additional_attributes' => [
				'auto_select' => 'false',
				'cancel_on_tap_outside' => 'false',
			],
		];
	}

	/**
	 * Get the provider's settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields(): array {
		return [
			'name' => [
				'label'       => __( 'Provider Name', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'A unique name for this provider (e.g., "google")', 'wp-oauth-login' ),
				'required'    => true,
				'default'     => 'google',
			],
			'display_name' => [
				'label'       => __( 'Display Name', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'The name shown to users', 'wp-oauth-login' ),
				'required'    => true,
				'default'     => 'Google',
			],
			'client_id' => [
				'label'       => __( 'Client ID', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => sprintf(
					/* translators: %1$s: Provider name, %2$s: Documentation URL */
					__( 'Create OAuth Client ID for %1$s at %2$s', 'wp-oauth-login' ),
					$this->get_display_name(),
					$this->get_documentation_url()
				),
				'required'    => true,
			],
			'client_secret' => [
				'label'       => __( 'Client Secret', 'wp-oauth-login' ),
				'type'        => 'password',
				'description' => __( 'The OAuth 2.0 client secret from Google', 'wp-oauth-login' ),
				'required'    => true,
			],
			'default_scopes' => [
				'label'       => __( 'Default Scopes', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'Space-separated list of OAuth scopes (e.g., "email profile openid")', 'wp-oauth-login' ),
				'default'     => 'email profile openid',
			],
		];
	}

	/**
	 * Validate the provider's configuration.
	 *
	 * @return bool
	 */
	public function validate_config(): bool {
		$required_fields = [ 'name', 'display_name', 'client_id', 'client_secret' ];
		
		foreach ( $required_fields as $field ) {
			if ( empty( $this->config[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the provider's documentation URL.
	 *
	 * @return string
	 */
	protected function get_documentation_url(): string {
		return 'https://console.developers.google.com/apis/credentials';
	}

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->config['name'] ?? $this->name;
	}

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_display_name(): string {
		return $this->config['display_name'] ?? $this->display_name;
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
		if ( isset( $this->config['default_scopes'] ) ) {
			if ( is_string( $this->config['default_scopes'] ) ) {
				return array_filter( explode( ' ', $this->config['default_scopes'] ) );
			}
			return $this->config['default_scopes'];
		}
		return $this->default_scopes;
	}
} 