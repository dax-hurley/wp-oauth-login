<?php
/**
 * Tests for token verifier.
 *
 * @package DaxHurley\OAuthLogin
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Tests\Unit\Utils;

use DaxHurley\OAuthLogin\Utils\ProviderManager;
use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;
use DaxHurley\OAuthLogin\Plugin;
use DaxHurley\OAuthLogin\Container;
use DaxHurley\OAuthLogin\Modules\Settings;
use DaxHurley\OAuthLogin\Tests\PrivateAccess;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Utils\TokenVerifier as Testee;
use Mockery;

/**
 * Class TokenVerifierTest
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Utils
 */
class TokenVerifierTest extends TestCase {

	use PrivateAccess;

	/**
	 * Object under test.
	 *
	 * @var \DaxHurley\OAuthLogin\Utils\TokenVerifier
	 */
	private $testee;

	/**
	 * @var Settings
	 */
	private $settingsMock;

	/**
	 * @var ProviderManager
	 */
	private $providerManagerMock;

	/**
	 * @var ProviderInterface
	 */
	private $providerMock;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock WordPress functions
		\WP_Mock::userFunction(
			'get_transient',
			[
				'return' => null,
			]
		);

		\WP_Mock::userFunction(
			'get_option',
			[
				'return' => [
					'providers' => [
						'google' => [
							'client_id' => 'test_client_id',
							'client_secret' => 'test_client_secret',
						]
					]
				],
			]
		);

		\WP_Mock::userFunction(
			'set_transient',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_get',
			[
				'return' => 'response',
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code',
			[
				'return' => 200,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_body',
			[
				'return' => '{"test_key_id": "test_public_key"}',
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers',
			[
				'return' => 'headers',
			]
		);

		\WP_Mock::userFunction(
			'wp_login_url',
			[
				'return' => 'http://example.test/wp-login.php',
			]
		);

		// Mock the global plugin function
		$pluginMock = Mockery::mock( Plugin::class );
		$containerMock = Mockery::mock( Container::class );
		$providerManagerMock = Mockery::mock( ProviderManager::class );
		$providerMock = Mockery::mock( ProviderInterface::class );
		
		$containerMock->shouldReceive( 'get' )
		              ->with( 'provider_manager' )
		              ->andReturn( $providerManagerMock );
		
		$pluginMock->shouldReceive( 'container' )
		           ->andReturn( $containerMock );

		\WP_Mock::userFunction(
			'DaxHurley\OAuthLogin\plugin',
			[
				'return' => $pluginMock,
			]
		);

		$this->providerManagerMock = $providerManagerMock;
		$this->providerMock = $providerMock;

		// Create a mock Settings instance for the TokenVerifier constructor
		$settingsMock = Mockery::mock( Settings::class );

		$this->testee = new Testee( $settingsMock );
	}

	/**
	 * @covers ::__construct
	 */
	public function testInstance() {
		$this->assertInstanceOf( Testee::class, $this->testee );
	}

	public function testDefaultCertsURL() {
		$this->assertSame( 'https://www.googleapis.com/oauth2/v1/certs', $this->testee::DEFAULT_CERTS_URL );
	}

	/**
	 * @covers ::get_supported_algorithm
	 */
	public function testGetSupportedAlgorithmDefault() {
		\WP_Mock::expectFilter( 'daxhurley.default_algorithm', OPENSSL_ALGO_SHA256, '' );
		$expected = OPENSSL_ALGO_SHA256;
		$algo = $this->testee::get_supported_algorithm();

		$this->assertSame( $expected, $algo );
	}

	/**
	 * @covers ::get_supported_algorithm
	 */
	public function testGetSHA256Algo() {
		$expected = OPENSSL_ALGO_SHA256;
		$algo = $this->testee::get_supported_algorithm( 'RS256' );

		$this->assertSame( $expected, $algo );
	}

	/**
	 * @covers ::base64_encode_url
	 */
	public function testBase64EncodeURL() {
		$str    = 'some+random/string=';
		$result = $this->testee->base64_encode_url( $str );

		$this->assertSame( 'c29tZStyYW5kb20vc3RyaW5nPQ', $result );
	}

	/**
	 * @covers ::base64_decode_url
	 */
	public function testBase64DecodeURL() {
		$str    = 'c29tZStyYW5kb20vc3RyaW5nPQ';
		$result = $this->testee->base64_decode_url( $str );

		$this->assertSame( 'some+random/string=', $result );
	}

	/**
	 * @covers ::current_user
	 */
	public function testCurrentUser() {
		$user = (object) [
			'ID' => 123,
			'user_email' => 'test@example.com',
		];

		// Set the current_user property directly since the method just returns it
		$this->setTesteeProperty( $this->testee, 'current_user', $user );

		$result = $this->testee->current_user();
		$this->assertSame( $user, $result );
	}

	/**
	 * @covers ::get_public_key
	 */
	public function testPublicKeyIsNull() {
		$result = $this->testee->get_public_key();
		$this->assertNull( $result );
	}
}
