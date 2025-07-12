<?php
/**
 * Test login module class.
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Modules;

use DaxHurley\OAuthLogin\Container;
use DaxHurley\OAuthLogin\Plugin;
use WP_Mock;
use Mockery;
use DaxHurley\OAuthLogin\Utils\Helper;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Utils\ProviderManager;
use DaxHurley\OAuthLogin\Modules\Login as Testee;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\Authenticator;

/**
 * Class LoginTest
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */
class LoginTest extends TestCase {
	/**
	 * @var OAuthClient
	 */
	private $ghClientMock;

	/**
	 * @var Authenticator
	 */
	private $authenticatorMock;

	/**
	 * @var ProviderManager
	 */
	private $providerManagerMock;

	/**
	 * @var Testee
	 */
	private $testee;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		// Mock the global plugin function
		$pluginMock = Mockery::mock( Plugin::class );
		$containerMock = Mockery::mock( Container::class );
		$pluginMock->shouldReceive( 'container' )->andReturn( $containerMock );

		\WP_Mock::userFunction(
			'DaxHurley\OAuthLogin\plugin',
			[
				'return' => $pluginMock,
			]
		);

		// Mock additional WordPress functions
		\WP_Mock::userFunction(
			'admin_url',
			[
				'return' => 'https://example.com/wp-admin/',
			]
		);

		\WP_Mock::userFunction(
			'trailingslashit',
			[
				'return_arg' => 0,
			]
		);

		\WP_Mock::userFunction(
			'is_user_logged_in',
			[
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_kses_post',
			[
				'return' => 'sanitized_html',
			]
		);

		\WP_Mock::userFunction(
			'wp_parse_args',
			[
				'return_arg' => 0,
			]
		);

		\WP_Mock::userFunction(
			'remove_query_arg',
			[
				'return_arg' => 1,
			]
		);

		\WP_Mock::userFunction(
			'add_user_meta',
			[
				'return' => true,
			]
		);

		// Mock Helper class
		$helperMock = \Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'get_redirect_url' )->andReturn( 'https://example.com' );
		$helperMock->shouldReceive( 'set_redirect_state_filter' )->andReturn( true );
		$helperMock->shouldReceive( 'remove_redirect_state_filter' )->andReturn( true );
		$helperMock->shouldReceive( 'render_template' )->andReturn( '' );
		$helperMock->shouldReceive( 'filter_input' )->andReturn( null );

		$this->ghClientMock      = Mockery::mock( OAuthClient::class );
		$this->authenticatorMock = Mockery::mock( Authenticator::class );
		$this->providerManagerMock = Mockery::mock( ProviderManager::class );

		// Mock the container to return the provider manager
		$containerMock->shouldReceive( 'get' )
		              ->with( 'provider_manager' )
		              ->andReturn( $this->providerManagerMock );

		$this->testee = new Testee( $this->ghClientMock, $this->authenticatorMock );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'login_flow', $this->testee->name() );
	}

	/**
	 * @covers ::__construct
	 */
	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded( 'login_form', [ $this->testee, 'login_button' ] );
		WP_Mock::expectActionAdded( 'authenticate', [ $this->testee, 'authenticate' ], 20 );
		WP_Mock::expectActionAdded( 'daxhurley.oauth_register_user', [ $this->authenticatorMock, 'register' ] );
		WP_Mock::expectActionAdded( 'daxhurley.oauth_redirect_url', [ $this->testee, 'redirect_url' ] );
		WP_Mock::expectActionAdded( 'daxhurley.oauth_user_created', [ $this->testee, 'user_meta' ] );
		WP_Mock::expectFilterAdded( 'daxhurley.oauth_login_state', [ $this->testee, 'state_redirect' ] );
		WP_Mock::expectActionAdded( 'wp_login', [ $this->testee, 'login_redirect' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::redirect_url
	 */
	public function testRedirectURLRetuensWithQueryParam() {
		$url = $this->testee->redirect_url( 'https://example.com' );
		$this->assertSame( 'https://example.com', $url );
	}
}
