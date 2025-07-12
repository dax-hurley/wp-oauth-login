<?php
/**
 * Test shortcode module class.
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use DaxHurley\OAuthLogin\Modules\Shortcode as Testee;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Modules\Assets;
use DaxHurley\OAuthLogin\Plugin;
use DaxHurley\OAuthLogin\Container;
use DaxHurley\OAuthLogin\Utils\ProviderManager;

/**
 * Class ShortCodeTest
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */
class ShortCodeTest extends TestCase {
	/**
	 * @var OAuthClient
	 */
	private $ghClientMock;

	/**
	 * @var Assets
	 */
	private $assetMock;

	/**
	 * @var Testee
	 */
	private $testee;

	/**
	 * @var ProviderManager
	 */
	private $providerManagerMock;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock the global plugin function
		$pluginMock = Mockery::mock( Plugin::class );
		$containerMock = Mockery::mock( Container::class );
		$providerManagerMock = Mockery::mock( ProviderManager::class );
		
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

		// Mock WordPress functions
		\WP_Mock::userFunction(
			'get_permalink',
			[
				'return' => 'https://example.com/',
			]
		);

		\WP_Mock::userFunction(
			'shortcode_atts',
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
			'trailingslashit',
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
			'is_single',
			[
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'is_page',
			[
				'return' => false,
			]
		);

		// Mock Helper class
		$helperMock = \Mockery::mock( 'alias:' . Helper::class );
		$helperMock->shouldReceive( 'get_redirect_url' )->andReturn( 'https://example.com' );
		$helperMock->shouldReceive( 'set_redirect_state_filter' )->andReturn( true );
		$helperMock->shouldReceive( 'remove_redirect_state_filter' )->andReturn( true );
		$helperMock->shouldReceive( 'render_template' )->andReturn( '' );

		$this->ghClientMock = Mockery::mock( OAuthClient::class );
		$this->assetMock    = Mockery::mock( Assets::class );
		$this->providerManagerMock = $providerManagerMock;

		$this->testee = new Testee( $this->ghClientMock, $this->assetMock );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'shortcode', $this->testee->name() );
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
		\WP_Mock::userFunction(
			'add_shortcode',
			[
				'times' => 1,
				'args'  => [
					'oauth_login',
					[ $this->testee, 'callback' ],
				],
				'return' => true,
			]
		);

		WP_Mock::expectFilterAdded( 'do_shortcode_tag', [ $this->testee, 'scan_shortcode' ], 10, 3 );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::scan_shortcode
	 */
	public function testScanShortcodeForDifferentTag() {
		$result = $this->testee->scan_shortcode( 'output', 'different_tag', [] );
		$this->assertSame( 'output', $result );
	}
}
