<?php
/**
 * Interface Provider.
 *
 * Every OAuth provider should implement this interface.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

namespace DaxHurley\OAuthLogin\Interfaces;

/**
 * Interface Provider
 *
 * @package DaxHurley\OAuthLogin\Interfaces
 */
interface Provider {

	/**
	 * Get the provider name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get the provider display name.
	 *
	 * @return string
	 */
	public function get_display_name(): string;

	/**
	 * Get the authorization URL.
	 *
	 * @return string
	 */
	public function get_authorization_url(): string;

	/**
	 * Get the token URL.
	 *
	 * @return string
	 */
	public function get_token_url(): string;

	/**
	 * Get the user info URL.
	 *
	 * @return string
	 */
	public function get_user_info_url(): string;

	/**
	 * Get the default scopes for this provider.
	 *
	 * @return array
	 */
	public function get_default_scopes(): array;

	/**
	 * Get the provider configuration.
	 *
	 * @return array
	 */
	public function get_config(): array;

	/**
	 * Set the provider configuration.
	 *
	 * @param array $config Configuration array.
	 * @return void
	 */
	public function set_config( array $config ): void;

	/**
	 * Check if the provider is configured.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Get the provider's client ID.
	 *
	 * @return string
	 */
	public function get_client_id(): string;

	/**
	 * Get the provider's client secret.
	 *
	 * @return string
	 */
	public function get_client_secret(): string;

	/**
	 * Get the provider's redirect URI.
	 *
	 * @return string
	 */
	public function get_redirect_uri(): string;

	/**
	 * Set the redirect URI.
	 *
	 * @param string $redirect_uri Redirect URI.
	 * @return void
	 */
	public function set_redirect_uri( string $redirect_uri ): void;

	/**
	 * Get the provider's state parameter.
	 *
	 * @return string
	 */
	public function get_state(): string;

	/**
	 * Get the authorization URL with parameters.
	 *
	 * @return string
	 */
	public function get_authorization_url_with_params(): string;

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code Authorization code.
	 * @return \stdClass
	 */
	public function exchange_code_for_token( string $code ): \stdClass;

	/**
	 * Get user information using access token.
	 *
	 * @param string $access_token Access token.
	 * @return \stdClass
	 */
	public function get_user_info( string $access_token ): \stdClass;

	/**
	 * Get the provider's settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields(): array;

	/**
	 * Validate the provider's configuration.
	 *
	 * @return bool
	 */
	public function validate_config(): bool;
} 