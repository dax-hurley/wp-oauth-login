<?php
/**
 * Block test.
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Modules;

use DaxHurley\OAuthLogin\Modules\Block;
use DaxHurley\OAuthLogin\Modules\Assets;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Interfaces\Module;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Utils\Helper;
use Mockery;
use WP_Mock;

/**
 * Class BlockTest.
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */
class BlockTest extends TestCase {

	/**
	 * Testee.
	 *
	 * @var Block
	 */
	private $testee;

	/**
	 * Assets mock.
	 *
	 * @var Assets
	 */
	private $assetMock;

	/**
	 * OAuth client mock.
	 *
	 * @var OAuthClient
	 */
	private $clientMock;

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->assetMock = Mockery::mock( Assets::class );
		$this->clientMock = Mockery::mock( OAuthClient::class );

		// Mock the plugin function to return a container with provider_manager
		$containerMock = Mockery::mock();
		$providerManagerMock = Mockery::mock();
		$containerMock->shouldReceive( 'get' )->with( 'provider_manager' )->andReturn( $providerManagerMock );

		$pluginMock = Mockery::mock();
		$pluginMock->shouldReceive( 'container' )->andReturn( $containerMock );
		$pluginMock->assets_dir = '/path/to/assets/';
		$pluginMock->template_dir = '/path/to/templates/';

		\WP_Mock::userFunction(
			'DaxHurley\OAuthLogin\plugin',
			[
				'return' => $pluginMock,
			]
		);

		$this->testee = new Block( $this->assetMock, $this->clientMock );
	}

	/**
	 * Teardown.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'oauth_login_block', $this->testee->name() );
	}

	/**
	 * @covers ::implements
	 */
	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof Module );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded( 'init', [ $this->testee, 'register' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}
}

