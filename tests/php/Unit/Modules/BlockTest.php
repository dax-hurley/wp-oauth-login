<?php
/**
 * Test Block module class.
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use DaxHurley\OAuthLogin\Modules\Block as Testee;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Modules\Assets;

/**
 * Class BlockTest
 *
 * @coversDefaultClass \DaxHurley\OAuthLogin\Modules\Block
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */
class BlockTest extends TestCase {
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
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->ghClientMock = $this->createMock( OAuthClient::class );
		$this->assetMock       = $this->createMock( Assets::class );
		$this->testee           = new Testee( $this->assetMock, $this->ghClientMock );
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->ghClientMock = null;
		$this->assetMock       = null;
		unset( $this->testee );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'oauth_login_block', $this->testee->name() );
	}

	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded( 'wp_enqueue_scripts', [ $this->testee->assets, 'register_login_styles' ] );
		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $this->testee, 'enqueue_block_editor_assets' ] );
		WP_Mock::expectActionAdded( 'init', [ $this->testee, 'register' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::enqueue_block_editor_assets
	 */
	public function testEnqueueBlockEditorAssets() {
		$path = dirname( __DIR__, 4 ) . '/assets/';

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);

		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => $path,
				];
			}
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[ 'oauth-login-block' ],
			1,
			true
		);

		$this->assetMock->expects( $this->once() )->method( 'register_login_styles' );
		$this->assetMock->expects( $this->once() )->method( 'register_script' )
		                 ->with(
			                 'oauth-login-block',
			                 'build/js/block-button.js',
			                 [
				                 'wp-blocks',
				                 'wp-element',
				                 'wp-editor',
				                 'wp-components',
			                 ],
			                 filemtime( $path . 'build/js/block-button.js' ),
			                 false
		                 );

		$this->testee->enqueue_block_editor_assets();
	}

	/**
	 * @covers ::register
	 */
	public function testRegister() {
		$this->wpMockFunction(
			'register_block_type',
			[
				'oauth-login/login-button',
				[
					'editor_style'    => 'login-with-oauth',
					'style'           => 'login-with-oauth',
					'render_callback' => [ $this->testee, 'render_login_button' ],
					'attributes'      => [
						'buttonText'   => [
							'type' => 'string',
						],
						'forceDisplay' => [
							'type'    => 'boolean',
							'default' => false,
						],
					],
				],
			],
			1,
			true
		);

		$this->testee->register();

		$this->assertConditionsMet();
	}


	/**
	 * @covers ::render_login_button, ::markup
	 */
	public function testRenderLoginButton() {
		$mockAttributes = [
			'login_url'       => '#',
			'custom_btn_text' => 'test',
			'force_display'   => false,
		];

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_parse_args',
			[],
			1,
			$mockAttributes
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[],
			1,
			''
		);

		$path = dirname( __DIR__, 4 ) . '/templates/';

		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'template_dir' => $path,
				];
			}
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);


		$helperMock = \Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'render_template' )->once()->withArgs(
			[
				$path . 'oauth-login-button.php',
				$mockAttributes,
				false,
			]
		)->andReturn( '' );

		$markup = $this->testee->render_login_button(
			[
				$path . '/oauth-login-button.php',
				$mockAttributes,
				false,
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::render_login_button, ::markup
	 */
	public function testRenderLogoutButton() {
		$mockAttributes = [
			'login_url'       => '#',
			'custom_btn_text' => 'test',
			'force_display'   => true,
		];

		$this->wpMockFunction(
			'is_user_logged_in',
			[],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_parse_args',
			[],
			1,
			$mockAttributes
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[],
			1,
			''
		);

		$path = dirname( __DIR__, 4 ) . '/templates/';

		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			1,
			function () use ( $path ) {
				return (object) [
					'template_dir' => $path,
				];
			}
		);

		WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'      => 1,
				'args'       => [ $path ],
				'return_arg' => 0,
			]
		);


		$helperMock = \Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'render_template' )->once()->withArgs(
			[
				$path . 'oauth-login-button.php',
				$mockAttributes,
				false,
			]
		)->andReturn( '' );

		$markup = $this->testee->render_login_button(
			[
				$path . '/oauth-login-button.php',
				$mockAttributes,
				false,
			]
		);

		$this->assertConditionsMet();
	}
}

