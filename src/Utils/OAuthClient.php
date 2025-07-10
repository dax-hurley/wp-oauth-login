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

use Exception;

/**
 * Class OAuthClient
 *
 * @package DaxHurley\OAuthLogin\Utils
 */
class OAuthClient {
	/**
	 * Authorization URL.
	 *
	 * @var string
	 */
	const AUTHORIZE_URL = 'https://accounts.oauth.com/o/oauth2/auth';

	/**
	 * Access Token URL.
	 *
	 * @var string
	 */
	const TOKEN_URL = 'https://oauth2.oauthapis.com/token';

	/**
	 * API base for oauth.
	 *
	 * @var string
	 */
	const API_BASE = 'https://www.oauthapis.com';

	/**
	 * Client ID.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Client secret.
	 *
	 * @var string
	 */
	public $client_secret;

	/**
	 * Redirect URI.
	 *
	 * @var string
	 */
	public $redirect_uri;

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $access_token;

	/**
	 * OAuthClient constructor.
	 *
	 * @param array $config Configuration for client.
	 */
	public function __construct( array $config ) {
		$this->client_id     = $config['client_id'] ?? '';
		$this->client_secret = $config['client_secret'] ?? '';
		$this->redirect_uri  = $config['redirect_uri'] ?? '';
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
			throw new Exception( esc_html__( 'Access token must be set to make this API call', 'login-with-oauth' ) );
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
			$this->access_token = $this->access_token( $code )->access_token;

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
		return apply_filters( 'daxhurley.oauth_redirect_url', $this->redirect_uri );
	}

	/**
	 * Get the authorize URL
	 *
	 * @return string
	 */
	public function authorization_url(): string {
		$plugin_scope = [
			'email',
			'profile',
			'openid',
		];

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
			'client_id'     => $this->client_id,
			'redirect_uri'  => $this->gt_redirect_url(),
			'state'         => $this->state(),
			'scope'         => implode( ' ', $scope ),
			'access_type'   => 'online',
			'response_type' => 'code',
		];

		/**
		 * Filter the arguments for sending in query.
		 *
		 * This is useful in cases for example: choosing the correct prompt.
		 *
		 * @param array $client_args List of query arguments to send to OAuth OAuth.
		 */
		$client_args = apply_filters( 'daxhurley.oauth_client_args', $client_args );

		return self::AUTHORIZE_URL . '?' . http_build_query( $client_args );
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
		$response = wp_remote_post(
			self::TOKEN_URL,
			[
				'headers' => [
					'Accept' => 'application/json',
				],
				'body'    => [
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'redirect_uri'  => $this->gt_redirect_url(),
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
	 * Make an API request.
	 *
	 * @return \stdClass
	 * @throws \Throwable Exception during API.
	 * @throws Exception API Exception.
	 */
	public function user(): \stdClass {
		try {
			//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
			$user = wp_remote_get(
				trailingslashit( self::API_BASE ) . 'oauth2/v2/userinfo?access_token=' . $this->access_token,
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

		} catch ( \Throwable $e ) {

			throw $e;
		}
	}

	/**
	 * State to pass in GH API.
	 *
	 * @return string
	 */
	public function state(): string {
		$state_data['nonce']    = wp_create_nonce( 'login_with_oauth' );
		$state_data             = apply_filters( 'daxhurley.oauth_login_state', $state_data );
		$state_data['provider'] = 'oauth';

		return base64_encode( wp_json_encode( $state_data ) );
	}
}
