<?php
/**
 * Test assets module class.
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Tests\Unit\Modules;

use WP_Mock;
use DaxHurley\OAuthLogin\Tests\TestCase;
use DaxHurley\OAuthLogin\Modules\Assets as Testee;

/**
 * Class AssetsTest
 *
 * @coversDefaultClass \DaxHurley\OAuthLogin\Modules\Assets
 *
 * @package DaxHurley\OAuthLogin\Tests\Unit\Modules
 */
class AssetsTest extends TestCase {
	/**
	 * Object in test.
	 *
	 * @var Testee
	 */
	private $testee;

	public function setUp(): void {
		\WP_Mock::userFunction(
			'wp_register_style',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_register_script',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			[
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_style_is',
			[
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_script_is',
			[
				'return' => false,
			]
		);

		// Mock plugin function
		\WP_Mock::userFunction(
			'DaxHurley\OAuthLogin\plugin',
			[
				'return' => (object) [
					'url' => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				],
			]
		);

		parent::setUp();
		
		$this->testee = new Testee();
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'assets', $this->testee->name() );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		WP_Mock::expectActionAdded(
			'login_enqueue_scripts',
			[
				$this->testee,
				'enqueue_login_styles'
			]
		);

		$this->testee->init();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_login_styles
	 * @covers ::register_style
	 * @covers ::get_file_version
	 */
	public function testRegisterLoginStyles() {
		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'wp-oauth-login',
				'https://example.com/assets/build/css/button/style.css',
				[],
				false,
				'all',
			],
			1,
			true
		);

		$this->testee->register_login_styles();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_script
	 */
	public function testRegisterLoginScript() {
		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			2,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_script',
			[
				'wp-oauth-login',
				'https://example.com/assets/js/login.js',
				[
					'some-other-script'
				],
				false,
				true,
			],
			1,
			true
		);

		$this->testee->register_script(
			'wp-oauth-login',
			'js/login.js',
			[
				'some-other-script'
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test enqueuing style when it is already registered.
	 *
	 * @covers ::enqueue_login_styles
	 */
	public function testEnqueueLoginStyleWithStyleRegistered() {
		$this->wpMockFunction(
			'wp_style_is',
			[
				'wp-oauth-login',
				'registered',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_script_is',
			[
				'wp-oauth-login-script',
				'registered',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'wp-oauth-login',
				'https://example.com/assets/build/css/login.css',
				[],
				false,
				true,
			],
			0,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				'wp-oauth-login',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				'wp-oauth-login-script',
			],
			1,
			true
		);

		$this->testee->enqueue_login_styles();
		$this->assertConditionsMet();
	}

	/**
	 * Test enqueuing style when it is already registered.
	 *
	 * @covers ::enqueue_login_styles
	 * @covers ::get_file_version
	 */
	public function testEnqueueLoginStyleWithStyleNotRegistered() {
		$this->wpMockFunction(
			'wp_style_is',
			[
				'wp-oauth-login',
				'registered',
			],
			1,
			false
		);

		$this->wpMockFunction(
			'wp_script_is',
			[
				'wp-oauth-login-script',
				'registered',
			],
			1,
			false
		);

		$this->wpMockFunction(
			'DaxHurley\OAuthLogin\plugin',
			[],
			4,
			function () {
				return (object) [
					'url'        => 'https://example.com/',
					'assets_dir' => 'https://example.com/assets',
				];
			}
		);

		$this->wpMockFunction(
			'wp_register_style',
			[
				'wp-oauth-login',
				'https://example.com/assets/build/css/button/style.css',
				[],
				false,
				'all',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_style',
			[
				'wp-oauth-login',
			],
			1,
			true
		);

		$this->wpMockFunction(
			'wp_enqueue_script',
			[
				'wp-oauth-login-script',
			],
			1,
			true
		);

		$this->testee->enqueue_login_styles();
		$this->assertConditionsMet();
	}
}
