<?php
/**
 * Token Verifier.
 *
 * Useful in verifying JWT Auth token.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Utils;

use Requests_Utility_CaseInsensitiveDictionary;
use Exception;
use DaxHurley\OAuthLogin\Modules\Settings;
use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;
use stdClass;

/**
 * Class TokenVerifier
 *
 * @package DaxHurley\OAuthLogin\Utils
 */
class TokenVerifier {
	/**
	 * Default certificates URL for Google (fallback).
	 */
	const DEFAULT_CERTS_URL = 'https://www.googleapis.com/oauth2/v1/certs';

	/**
	 * List of supported algorithms.
	 */
	const SUPPORTED_ALGORITHMS = [
		'RS256' => OPENSSL_ALGO_SHA256,
		'RS384' => OPENSSL_ALGO_SHA384,
		'RS512' => OPENSSL_ALGO_SHA512,
		'ES384' => OPENSSL_ALGO_SHA384,
		'ES256' => OPENSSL_ALGO_SHA512,
	];

	/**
	 * ID Token Sent via OAuth.
	 *
	 * @var string
	 */
	private $token = '';

	/**
	 * User who needs to be authenticated.
	 *
	 * @var stdClass
	 */
	private $current_user;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Provider manager instance.
	 *
	 * @var ProviderManager
	 */
	private $provider_manager;

	/**
	 * Current provider for token verification.
	 *
	 * @var ProviderInterface|null
	 */
	private $current_provider;

	/**
	 * TokenVerifier constructor.
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->provider_manager = new ProviderManager();
	}

	/**
	 * Get supported algorithms value.
	 *
	 * @param string $algo Algorithm.
	 */
	public static function get_supported_algorithm( string $algo = '' ) {
		$find_algo = array_key_exists( $algo, self::SUPPORTED_ALGORITHMS );

		if ( ! $find_algo ) {
			return apply_filters( 'daxhurley.default_algorithm', OPENSSL_ALGO_SHA256, $algo );
		}

		return self::SUPPORTED_ALGORITHMS[ $algo ];
	}

	/**
	 * Verify if a token is valid or not.
	 *
	 * @param string $token Received ID token from OAuth.
	 * @param string|null $provider_name Provider name for token verification.
	 *
	 * @return bool
	 * @throws Exception Token verification failure exception.
	 */
	public function verify_token( string $token, ?string $provider_name = null ): bool {
		$this->token = $token;

		// Get the provider for verification
		if ( $provider_name ) {
			$this->current_provider = $this->provider_manager->get_provider( $provider_name );
		} else {
			$this->current_provider = $this->provider_manager->get_first_configured_provider();
		}

		try {
			$this->is_valid_jwt();
			$this->is_valid_signature();
			$this->valid_data();

			return true;
		} catch ( Exception $e ) {

			do_action( 'daxhurley.login_with_oauth_exception', $e );

			throw $e;
		}
	}

	/**
	 * Base64 URL Encode a string.
	 *
	 * @param string $str Input string to encode.
	 *
	 * @return array|string|string[]
	 */
	public function base64_encode_url( $str ) {
		return str_replace( [ '+', '/', '=' ], [ '-', '_', '' ], base64_encode( $str ) );
	}

	/**
	 * Base64 URL Encode a string.
	 *
	 * @param string $str Input string to decode.
	 *
	 * @return false|string
	 */
	public function base64_decode_url( string $str ) {
		return base64_decode( str_replace( [ '-', '_' ], [ '+', '/' ], $str ) );
	}

	/**
	 * Retrieve current user's data.
	 *
	 * Current user is OAuth user, not WP user.
	 *
	 * @return stdClass|null
	 */
	public function current_user(): ?stdClass {

		return $this->current_user;
	}

	/**
	 * Get the certificates URL for the current provider.
	 *
	 * @return string
	 */
	private function get_certs_url(): string {
		if ( $this->current_provider ) {
			// Allow providers to specify their own certificates URL
			$config = $this->current_provider->get_config();
			if ( ! empty( $config['certs_url'] ) ) {
				return $config['certs_url'];
			}

			// For Google provider, use the default URL
			if ( $this->current_provider->get_name() === 'google' ) {
				return self::DEFAULT_CERTS_URL;
			}
		}

		// Fallback to default
		return self::DEFAULT_CERTS_URL;
	}

	/**
	 * Get public key based on key ID.
	 *
	 * @param string|null $key_id Key ID.
	 *
	 * @return string|null
	 */
	public function get_public_key( $key_id = null ): ?string {
		if ( ! $key_id ) {
			return null;
		}

		$transient_key = 'lwg_pk_' . $key_id;
		$cached_pk     = $this->get_transient( $transient_key );

		if ( ! empty( $cached_pk ) ) {
			return (string) $cached_pk;
		}

		//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$certs = wp_remote_get( $this->get_certs_url() );

		if ( 200 !== wp_remote_retrieve_response_code( $certs ) ) {
			return null;
		}

		$headers = wp_remote_retrieve_headers( $certs );
		$keys    = wp_remote_retrieve_body( $certs );
		$keys    = json_decode( $keys );

		if ( property_exists( $keys, $key_id ) ) {
			$max_age = is_object( $headers ) && is_a( $headers, Requests_Utility_CaseInsensitiveDictionary::class ) ? $this->get_max_age( $headers ) : 0;

			/**
			 * Cache public key in transient.
			 *
			 * We will cache it for 5 mins less than the actual expiration time,
			 * so that it should be cleared on time.
			 */
			if ( $max_age ) {
				$max_age = $max_age - 300;
				$this->set_transient( $transient_key, $keys->{$key_id}, max( 5, $max_age ) );
			}

			return $keys->{$key_id};
		}

		return null;
	}

	/**
	 * Checks whether received token is valid JWT token or not.
	 *
	 * @return array|null Decoded informational array with Header|Payload|Signature form.
	 * @throws Exception ID token invalid.
	 */
	private function is_valid_jwt(): ?array {
		$token_parts = explode( '.', $this->token );

		if ( 3 !== count( $token_parts ) ) {
			throw new Exception( esc_html__( 'ID token is not a valid JWT token', 'wp-oauth-login' ) );
		}

		$header  = json_decode( $this->base64_decode_url( $token_parts[0] ) );
		$payload = json_decode( $this->base64_decode_url( $token_parts[1] ) );

		if ( ! $header || ! $payload ) {
			throw new Exception( esc_html__( 'ID token is not a valid JWT token', 'wp-oauth-login' ) );
		}

		$this->current_user = $payload;

		return [
			'header'  => $header,
			'payload' => $payload,
			'signature' => $token_parts[2],
		];
	}

	/**
	 * Verify the signature of the token.
	 *
	 * @return void
	 * @throws Exception Signature verification failure.
	 */
	private function is_valid_signature(): void {
		$token_parts = explode( '.', $this->token );
		$header      = json_decode( $this->base64_decode_url( $token_parts[0] ) );

		if ( ! property_exists( $header, 'kid' ) ) {
			throw new Exception( esc_html__( 'Key ID not found in token header', 'wp-oauth-login' ) );
		}

		$public_key = $this->get_public_key( $header->kid );

		if ( ! $public_key ) {
			throw new Exception( esc_html__( 'Public key not found for the given key ID', 'wp-oauth-login' ) );
		}

		$signature = $this->base64_decode_url( $token_parts[2] );
		$data      = $token_parts[0] . '.' . $token_parts[1];
		$algo      = self::get_supported_algorithm( $header->alg );

		$verified = openssl_verify( $data, $signature, $public_key, $algo );

		if ( 1 !== $verified ) {
			throw new Exception( esc_html__( 'Token signature verification failed', 'wp-oauth-login' ) );
		}
	}

	/**
	 * Validate the token data.
	 *
	 * @return void
	 * @throws Exception Token data validation failure.
	 */
	private function valid_data(): void {
		if ( ! property_exists( $this->current_user, 'iss' ) ) {
			throw new Exception( esc_html__( 'Issuer not found in token', 'wp-oauth-login' ) );
		}

		if ( ! property_exists( $this->current_user, 'aud' ) ) {
			throw new Exception( esc_html__( 'Audience not found in token', 'wp-oauth-login' ) );
		}

		if ( ! property_exists( $this->current_user, 'exp' ) ) {
			throw new Exception( esc_html__( 'Expiration time not found in token', 'wp-oauth-login' ) );
		}

		// Check if token is expired
		if ( time() > $this->current_user->exp ) {
			throw new Exception( esc_html__( 'Token has expired', 'wp-oauth-login' ) );
		}

		// Check issuer based on provider configuration
		$valid_issuers = $this->get_valid_issuers();
		if ( ! in_array( $this->current_user->iss, $valid_issuers, true ) ) {
			throw new Exception( esc_html__( 'Invalid token issuer', 'wp-oauth-login' ) );
		}

		// Check audience (should match client ID)
		if ( $this->current_provider && $this->current_user->aud !== $this->current_provider->get_client_id() ) {
			throw new Exception( esc_html__( 'Token audience does not match client ID', 'wp-oauth-login' ) );
		}
	}

	/**
	 * Get valid issuers for the current provider.
	 *
	 * @return array
	 */
	private function get_valid_issuers(): array {
		if ( $this->current_provider ) {
			$config = $this->current_provider->get_config();
			
			// Allow providers to specify their own valid issuers
			if ( ! empty( $config['valid_issuers'] ) ) {
				return $config['valid_issuers'];
			}

			// For Google provider, use Google-specific issuers
			if ( $this->current_provider->get_name() === 'google' ) {
				return [ 'accounts.google.com', 'https://accounts.google.com' ];
			}
		}

		// Default fallback - allow any issuer (less secure but more flexible)
		return [ $this->current_user->iss ?? '' ];
	}

	/**
	 * Get max age from headers.
	 *
	 * @param Requests_Utility_CaseInsensitiveDictionary $headers Headers.
	 *
	 * @return int
	 */
	private function get_max_age( Requests_Utility_CaseInsensitiveDictionary $headers ): int {
		$cache_control = $headers->getValues( 'cache-control' );

		if ( ! $cache_control ) {
			return 0;
		}

		$cache_control = $cache_control[0];
		preg_match( '/max-age=(\d+)/', $cache_control, $matches );

		return isset( $matches[1] ) ? (int) $matches[1] : 0;
	}

	/**
	 * Set transient.
	 *
	 * @param string $key   Key.
	 * @param string $value Value.
	 * @param int    $expire Expire time.
	 *
	 * @return void
	 */
	private function set_transient( string $key, string $value, int $expire = 0 ): void {
		set_transient( $key, $value, $expire );
	}

	/**
	 * Get transient.
	 *
	 * @param string $key Key.
	 *
	 * @return mixed
	 */
	private function get_transient( string $key ) {
		return get_transient( $key );
	}
}
