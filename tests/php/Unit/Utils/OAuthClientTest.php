<?php
/**
 * Test OAuth client class.
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Utils;

use WP_Mock;
use Exception;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Utils\OAuthClient as Testee;
use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;

/**
 * Class OAuthClientTest
 *
 * @coversDefaultClass \DaxHurley\OAuthLogin\Utils\OAuthClient
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Utils
 */
class OAuthClientTest extends TestCase {

	/**
	 * Object under test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * Mock provider.
	 *
	 * @var ProviderInterface
	 */
	private $providerMock;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->providerMock = $this->createMock( ProviderInterface::class );
		$this->testee = new Testee( $this->providerMock );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$provider = $this->createMock( ProviderInterface::class );
		$client = new Testee( $provider );
		
		$this->assertSame( $provider, $this->getTesteeProperty( 'provider', $client ) );
	}

	/**
	 * @covers ::__call
	 */
	public function testCallWithUser() {
		$this->expectException( Exception::class );
		$this->testee->__call( 'user', null );
	}

	/**
	 * @covers ::__call
	 */
	public function testCallWithEmails() {
		$this->expectException( Exception::class );
		$this->testee->__call( 'emails', null );
	}

	/**
	 * @covers ::__call
	 * @covers ::gt_redirect_url
	 */
	public function testCallWithOtherMethods() {
		WP_Mock::expectFilterNotAdded( 'daxhurley.oauth_redirect_url', '' );
		$this->testee->__call( 'some_other_method', null );

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::set_access_token
	 */
	public function testSetAccessToken() {
		$token_response = (object) [
			'access_token' => 'AccessToken'
		];

		$this->providerMock->expects( $this->once() )
			->method( 'exchange_code_for_token' )
			->with( 'abc' )
			->willReturn( $token_response );

		$obj = $this->testee->set_access_token( 'abc' );
		$token = $this->getTesteeProperty( 'access_token', $this->testee );

		$this->assertSame( $this->testee, $obj );
		$this->assertSame( 'AccessToken', $token );
	}

	/**
	 * @covers ::set_access_token
	 */
	public function testSetAccessTokenThrowsException() {
		$this->providerMock->expects( $this->once() )
			->method( 'exchange_code_for_token' )
			->with( 'abc' )
			->willThrowException( new Exception( 'Token exchange failed' ) );

		$this->expectException( Exception::class );
		$this->testee->set_access_token( 'abc' );
	}

	/**
	 * @covers ::access_token
	 */
	public function testAccessToken() {
		$token_response = (object) [
			'access_token' => 'AccessToken'
		];

		$this->providerMock->expects( $this->once() )
			->method( 'exchange_code_for_token' )
			->with( 'abc' )
			->willReturn( $token_response );

		$result = $this->testee->access_token( 'abc' );
		$this->assertSame( $token_response, $result );
	}

	/**
	 * @covers ::access_token
	 */
	public function testAccessTokenThrowsException() {
		$this->providerMock->expects( $this->once() )
			->method( 'exchange_code_for_token' )
			->with( 'abc' )
			->willThrowException( new Exception( 'Token exchange failed' ) );

		$this->expectException( Exception::class );
		$this->testee->access_token( 'abc' );
	}

	/**
	 * @covers ::user
	 */
	public function testUserReturnsObject() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$user_data = (object) [
			'email' => 'user@domain.com',
			'login' => 'login',
		];

		$this->providerMock->expects( $this->once() )
			->method( 'get_user_info' )
			->with( 'someToken' )
			->willReturn( $user_data );

		$user = $this->testee->user();
		$this->assertInstanceOf( \stdClass::class, $user );
		$this->assertSame( $user->email, 'user@domain.com' );
		$this->assertSame( $user->login, 'login' );
	}

	/**
	 * @covers ::user
	 */
	public function testUserThrowsException() {
		$this->setTesteeProperty( $this->testee, 'access_token', 'someToken' );

		$this->providerMock->expects( $this->once() )
			->method( 'get_user_info' )
			->with( 'someToken' )
			->willThrowException( new Exception( 'User info failed' ) );

		$this->expectException( Exception::class );
		$this->testee->user();
	}

	/**
	 * @covers ::authorization_url
	 */
	public function testAuthorizationURL() {
		$expected_url = 'https://accounts.oauth.com/o/oauth2/auth?client_id=cid&redirect_uri=&state=abcd&scope=email+profile+openid&access_type=online&response_type=code';

		$this->providerMock->expects( $this->once() )
			->method( 'get_authorization_url_with_params' )
			->willReturn( $expected_url );

		$this->assertSame( $expected_url, $this->testee->authorization_url() );
	}

	/**
	 * @covers ::gt_redirect_url
	 */
	public function testGtRedirectUrl() {
		WP_Mock::expectFilter( 'daxhurley.oauth_redirect_url', 'https://example.com/callback' );

		$this->providerMock->expects( $this->once() )
			->method( 'get_redirect_uri' )
			->willReturn( 'https://example.com/callback' );

		$this->testee->gt_redirect_url();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::state
	 */
	public function testState() {
		$expected_state = 'base64_encoded_state';

		$this->providerMock->expects( $this->once() )
			->method( 'get_state' )
			->willReturn( $expected_state );

		$this->assertSame( $expected_state, $this->testee->state() );
	}

	/**
	 * @covers ::get_provider
	 */
	public function testGetProvider() {
		$this->assertSame( $this->providerMock, $this->testee->get_provider() );
	}

	/**
	 * Test that the provider's redirect URI is set and returned correctly.
	 */
	public function testProviderRedirectUriIsSetAndReturned() {
		$redirect_uri = 'https://example.com/wp-login.php';
		$this->providerMock->expects($this->once())
			->method('get_redirect_uri')
			->willReturn($redirect_uri);

		// The gt_redirect_url method should return the provider's redirect URI
		\WP_Mock::onFilter('daxhurley.oauth_redirect_url')->with($redirect_uri)->reply($redirect_uri);
		$this->assertSame($redirect_uri, $this->testee->gt_redirect_url());
	}

	/**
	 * Test that the authorization URL includes the correct redirect_uri parameter.
	 */
	public function testAuthorizationUrlIncludesRedirectUri() {
		$redirect_uri = 'https://example.com/wp-login.php';
		$expected_url = 'https://accounts.oauth.com/o/oauth2/auth?client_id=cid&redirect_uri=' . urlencode($redirect_uri) . '&state=abcd&scope=email+profile+openid&access_type=online&response_type=code';

		$this->providerMock->expects($this->once())
			->method('get_authorization_url_with_params')
			->willReturn($expected_url);

		$url = $this->testee->authorization_url();
		$this->assertStringContainsString('redirect_uri=' . urlencode($redirect_uri), $url);
	}
}
