<?php
/**
 * Generic OAuth Provider.
 *
 * A generic provider that can be configured for any OAuth 2.0 provider.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Providers;

/**
 * Class GenericProvider
 *
 * @package DaxHurley\OAuthLogin\Providers
 */
class GenericProvider extends AbstractProvider {

	/**
	 * GenericProvider constructor.
	 *
	 * @param array $config Provider configuration.
	 */
	public function __construct( array $config = [] ) {
		parent::__construct( $config );
		
		$this->name         = $config['name'] ?? 'generic';
		$this->display_name = $config['display_name'] ?? __( 'Generic OAuth Provider' );
		$this->authorization_url = $config['authorization_url'] ?? '';
		$this->token_url    = $config['token_url'] ?? '';
		$this->user_info_url = $config['user_info_url'] ?? '';
		$this->default_scopes = $config['default_scopes'] ?? [ 'email', 'profile' ];
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
				'description' => __( 'A unique name for this provider (e.g., "mycompany", "github")', 'wp-oauth-login' ),
				'required'    => true,
			],
			'display_name' => [
				'label'       => __( 'Display Name', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'The name shown to users (e.g., "My Company", "GitHub")', 'wp-oauth-login' ),
				'required'    => true,
			],
			'authorization_url' => [
				'label'       => __( 'Authorization URL', 'wp-oauth-login' ),
				'type'        => 'url',
				'description' => __( 'The OAuth 2.0 authorization endpoint URL', 'wp-oauth-login' ),
				'required'    => true,
			],
			'token_url' => [
				'label'       => __( 'Token URL', 'wp-oauth-login' ),
				'type'        => 'url',
				'description' => __( 'The OAuth 2.0 token endpoint URL', 'wp-oauth-login' ),
				'required'    => true,
			],
			'user_info_url' => [
				'label'       => __( 'User Info URL', 'wp-oauth-login' ),
				'type'        => 'url',
				'description' => __( 'The user info endpoint URL (e.g., https://api.provider.com/user)', 'wp-oauth-login' ),
				'required'    => true,
			],
			'client_id' => [
				'label'       => __( 'Client ID', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'The OAuth 2.0 client ID from your provider', 'wp-oauth-login' ),
				'required'    => true,
			],
			'client_secret' => [
				'label'       => __( 'Client Secret', 'wp-oauth-login' ),
				'type'        => 'password',
				'description' => __( 'The OAuth 2.0 client secret from your provider', 'wp-oauth-login' ),
				'required'    => true,
			],
			'default_scopes' => [
				'label'       => __( 'Default Scopes', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'Space-separated list of OAuth scopes (e.g., "email profile openid")', 'wp-oauth-login' ),
				'default'     => 'email profile',
			],
			'supports_one_tap' => [
				'label'       => __( 'Supports One-Tap Login', 'wp-oauth-login' ),
				'type'        => 'checkbox',
				'description' => __( 'Check if this provider supports one-tap login (currently only Google supports this)', 'wp-oauth-login' ),
				'default'     => false,
			],
			'certs_url' => [
				'label'       => __( 'Certificates URL', 'wp-oauth-login' ),
				'type'        => 'url',
				'description' => __( 'URL for JWT certificate verification (optional, only needed for ID token verification)', 'wp-oauth-login' ),
			],
			'valid_issuers' => [
				'label'       => __( 'Valid Issuers', 'wp-oauth-login' ),
				'type'        => 'text',
				'description' => __( 'Comma-separated list of valid JWT issuers (optional, for ID token verification)', 'wp-oauth-login' ),
			],
		];
	}

	/**
	 * Validate the provider's configuration.
	 *
	 * @return bool
	 */
	public function validate_config(): bool {
		$required_fields = [ 'name', 'display_name', 'authorization_url', 'token_url', 'user_info_url', 'client_id', 'client_secret' ];
		
		foreach ( $required_fields as $field ) {
			if ( empty( $this->config[ $field ] ) ) {
				return false;
			}
		}

		// Validate URLs
		if ( ! filter_var( $this->config['authorization_url'], FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( ! filter_var( $this->config['token_url'], FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( ! filter_var( $this->config['user_info_url'], FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the provider's documentation URL.
	 *
	 * @return string
	 */
	protected function get_documentation_url(): string {
		return 'https://oauth.net/2/';
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
		return $this->config['authorization_url'] ?? $this->authorization_url;
	}

	/**
	 * Get the token URL.
	 *
	 * @return string
	 */
	public function get_token_url(): string {
		return $this->config['token_url'] ?? $this->token_url;
	}

	/**
	 * Get the user info URL.
	 *
	 * @return string
	 */
	public function get_user_info_url(): string {
		return $this->config['user_info_url'] ?? $this->user_info_url;
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