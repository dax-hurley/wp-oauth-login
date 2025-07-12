<?php
/**
 * OAuth API Client.
 *
 * Useful for authenticating the user and other API related operations.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Utils;

use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;
use Exception;

/**
 * Class OAuthClient
 *
 * @package DaxHurley\OAuthLogin\Utils
 */
class OAuthClient {
	/**
	 * Provider instance.
	 *
	 * @var ProviderInterface
	 */
	private $provider;

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * OAuthClient constructor.
	 *
	 * @param ProviderInterface $provider Provider instance.
	 */
	public function __construct( ProviderInterface $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Check if access token is set before calling API methods.
	 *
	 * @param string $name Name of method called.
	 * @param mixed  $args Arguments for method.
	 *
	 * @throws Exception Empty access token.
	 */
	public function __call( string $name, $args ) {
		$methods = [
			'user',
			'emails',
		];

		if ( in_array( $name, $methods, true ) && empty( $this->access_token ) ) {
			throw new Exception( esc_html__( 'Access token must be set to make this API call', 'wp-oauth-login' ) );
		}
	}

	/**
	 * Set access token.
	 *
	 * @param string $code Token.
	 *
	 * @return self
	 * @throws \Throwable Exception for fetching access token.
	 */
	public function set_access_token( string $code ): self {
		try {
			$token_response = $this->provider->exchange_code_for_token( $code );
			$this->access_token = $token_response->access_token;

			return $this;
		} catch ( \Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Return redirect url.
	 *
	 * @return string
	 */
	public function gt_redirect_url(): string {
		return apply_filters( 'daxhurley.oauth_redirect_url', $this->provider->get_redirect_uri() );
	}

	/**
	 * Get the authorize URL
	 *
	 * @return string
	 */
	public function authorization_url(): string {
		return $this->provider->get_authorization_url_with_params();
	}

	/**
	 * Get the access token.
	 *
	 * @param string $code Response code received during authorization.
	 *
	 * @return \stdClass
	 * @throws Exception For access token errors.
	 */
	public function access_token( string $code ): \stdClass {
		return $this->provider->exchange_code_for_token( $code );
	}

	/**
	 * Make an API request.
	 *
	 * @return \stdClass
	 * @throws \Throwable Exception during API.
	 * @throws Exception API Exception.
	 */
	public function user(): \stdClass {
		try {
			return $this->provider->get_user_info( $this->access_token );
		} catch ( \Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * State to pass in OAuth API.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->provider->get_state();
	}

	/**
	 * Get the provider instance.
	 *
	 * @return ProviderInterface
	 */
	public function get_provider(): ProviderInterface {
		return $this->provider;
	}
}
