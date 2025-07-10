<?php
/**
 * Test main plugin class.
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Tests\Unit;

use WP_Mock;
use DaxHurley\OAuthLogin\Plugin;
use DaxHurley\OAuthLogin\Container;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Plugin as Testee;
use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Interfaces\Container as ContainerInterface;

/**
 * Class PluginTest
 *
 * @coversDefaultClass \DaxHurley\OAuthLogin\Plugin
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit
 */
class PluginTest extends TestCase {

	/**
	 * Container mocked object.
	 *
	 * @var ContainerInterface
	 */
	private $containerMock;

	/**
	 * Mock for module.
	 *
	 * @var ModuleInterface
	 */
	private $moduleMock;

	/**
	 * Object in test.
	 *
	 * @var Plugin
	 */
	private $testee;

	/**
	 * Runs before any test in class is run.
	 */
	public function setUp(): void {
		$this->moduleMock    = $this->createMock( ModuleInterface::class );
		$this->containerMock = $this->createMock( Container::class );
		$this->testee        = new Testee( $this->containerMock );
	}

	/**
	 * Test run method of Plugin class.
	 *
	 * @covers ::run
	 * @covers ::activate_modules
	 */
	public function testRun() {
		$this->moduleMock->expects( $this->exactly( 6 ) )
		                 ->method( 'init' );

		$this->containerMock->expects( $this->once() )
		                    ->method( 'define_services' );


		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		$this->wpMockFunction(
			'plugin_dir_url',
			[
				'slashedstring/login-with-oauth.php'
			]
		);

		$this->wpMockFunction(
			'plugin_basename',
			[],
			1,
			'login-with-oauth'
		);

		$this->testee->run();

		$this->assertConditionsMet();
	}

	/**
	 * Test the path for the plugin.
	 *
	 * @covers ::run
	 */
	public function testPath() {
		$this->moduleMock->expects( $this->exactly( 6 ) )
		                 ->method( 'init' );

		$this->containerMock->expects( $this->once() )
		                    ->method( 'define_services' );

		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		$this->wpMockFunction(
			'plugin_dir_url',
			[
				'slashedstring/login-with-oauth.php'
			]
		);

		$this->testee->run();

		$this->assertSame( GH_PLUGIN_DIR, $this->testee->path );
	}

	/**
	 * Test the path template directory in plugin.
	 *
	 * @covers ::run
	 */
	public function testTemplateDirPath() {
		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		$this->wpMockFunction(
			'plugin_dir_url',
			[
				'slashedstring/login-with-oauth.php'
			]
		);

		$this->wpMockFunction(
			'plugin_basename',
			[],
			1,
			'login-with-oauth'
		);

		$this->testee->run();

		$this->assertSame( 'slashedstring/templates/', $this->testee->template_dir );
	}

	/**
	 * Test the plugin url.
	 *
	 * @covers ::run
	 */
	public function testPluginURL() {
		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		WP_Mock::userFunction(
			'plugin_dir_url',
			[
				'args'       => [
					'slashedstring/login-with-oauth.php'
				],
				'return_arg' => 0
			]
		);

		$this->wpMockFunction(
			'plugin_basename',
			[],
			1,
			'login-with-oauth'
		);

		$this->testee->run();

		$this->assertSame( 'slashedstring/login-with-oauth.php', $this->testee->url );
	}

	/**
	 * Test assets directory path.
	 *
	 * @covers ::run
	 */
	public function testAssetsDirPath() {
		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		$this->wpMockFunction(
			'plugin_dir_url',
			[
				'slashedstring/login-with-oauth.php'
			]
		);

		$this->testee->run();

		$this->assertSame( 'slashedstring/assets/', $this->testee->assets_dir );
	}

	/**
	 * Test that hooks are added on plugin run.
	 *
	 * @covers ::run
	 */
	public function testHooksAddedOnRun() {
		$this->containerMock->expects( $this->exactly( 6 ) )
		                    ->method( 'get' )
		                    ->withAnyParameters()
		                    ->willReturn( $this->moduleMock );

		$this->wpMockFunction(
			'trailingslashit',
			[
				WP_Mock\Functions::type( 'string' )
			],
			3,
			'slashedstring/'
		);

		$this->wpMockFunction(
			'plugin_dir_url',
			[
				'slashedstring/login-with-oauth.php'
			]
		);

		$this->wpMockFunction(
			'plugin_basename',
			[],
			2,
			'login-with-oauth'
		);

		WP_Mock::expectActionAdded( 'init', [ $this->testee, 'load_translations' ] );
		WP_Mock::expectActionAdded( 'plugin_action_links_' . plugin_basename( $this->testee->path ) . '/login-with-oauth.php', [ $this->testee, 'add_plugin_action_links' ] );
		WP_Mock::expectFilter( 'daxhurley.oauth_login_modules', $this->testee->active_modules );

		$this->testee->run();
		$this->assertConditionsMet();
	}

	/**
	 * Test load_translations method.
	 *
	 * @covers ::load_translations
	 */
	public function testLoadTranslation() {

		$this->moduleMock->expects( $this->never() )
		                 ->method( 'init' );

		$this->containerMock->expects( $this->never() )
		                    ->method( 'define_services' );

		$this->wpMockFunction(
			'get_locale',
			[],
			1,
			'en_US'
		);

		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			1,
			function () {
				return (object) [
					'path' => '/some/utterly/fake/path-to-test/',
				];
			}
		);

		$this->wpMockFunction(
			'load_plugin_textdomain',
			[
				'login-with-oauth',
				false,
				'path-to-test/languages/en_US'
			]
		);

		$this->testee->load_translations();

		$this->assertConditionsMet();
	}

	/**
	 * Test plugin action callback.
	 *
	 * @covers ::add_plugin_action_links
	 */
	public function testSettingPluginActionIsAdded() {
		$this->wpMockFunction(
			'admin_url',
			[
				'options-general.php?page=login-with-oauth',
			],
			1,
			'http://example.test/wp-admin/options-general.php?page=login-with-oauth'
		);

		$actions = $this->testee->add_plugin_action_links( [] );

		print_r($actions);

		$this->assertIsArray( $actions, 'Plugin actions should be an array.');
		$this->assertArrayHasKey( 'settings', $actions, 'Setting plugin action should exists.' );
		$this->assertEquals(
			'<a href="http://example.test/wp-admin/options-general.php?page=login-with-oauth">Settings</a>',
			$actions['settings'],
			'Setting plugin action link is created.'
		);
	}

	/**
	 * @covers ::container
	 */
	public function testContainer() {
		$container = $this->testee->container();

		$this->assertSame( $container, $this->containerMock );
	}
}
