<?php
/**
 * Test settings module class.
 */

declare( strict_types=1 );

namespace RtCamp\OAuthLogin\Tests\Unit\Modules;

use WP_Mock;
use RtCamp\OAuthLogin\Interfaces\Module as ModuleInterface;
use RtCamp\OAuthLogin\Tests\TestCase;
use RtCamp\OAuthLogin\Modules\Settings as Testee;

/**
 * Class SettingsTest
 *
 * @coversDefaultClass \RtCamp\OAuthLogin\Modules\Settings
 *
 * @package RtCamp\OAuthLogin\Tests\Unit\Modules
 */
class SettingsTest extends TestCase {
	/**
	 * Object in test.
	 *
	 * @var Testee
	 */
	private $testee;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->testee = new Testee();
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'settings', $this->testee->name() );
	}

	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::__get
	 */
	public function testGetWithNull() {
		$value = $this->testee->__get( 'some_test_property' );
		$this->assertEquals( null, $value );
	}

	/**
	 * @covers ::__get
	 */
	public function testGetWithProper() {
		$this->wpMockFunction(
			'get_option',
			[
				'wp_oauth_login_settings',
				[]
			],
			1,
			[
				'client_id' => 'cid'
			]
		);

		$this->testee->init();
		$value = $this->testee->__get( 'client_id' );
		$this->assertEquals( 'cid', $value );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		$this->wpMockFunction(
			'get_option',
			[
				'wp_oauth_login_settings',
				[]
			],
			1,
			[]
		);

		WP_Mock::expectActionAdded( 'admin_init', [ $this->testee, 'register_settings' ] );
		WP_Mock::expectActionAdded( 'admin_menu', [ $this->testee, 'settings_page' ] );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::register_settings
	 */
	public function testRegisterSettings() {
		$this->wpMockFunction(
			'register_setting',
			[
				'wp_oauth_login',
				'wp_oauth_login_settings'
			],
			1,
			true
		);

		$this->wpMockFunction(
			'add_settings_section',
			[
				'wp_oauth_login_section',
				'Log in with OAuth Settings',
				\Closure::class,
				'login-with-oauth'
			],
			1
		);

		WP_Mock::userFunction(
			'add_settings_field',
			[
				'args'  => [
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'callable' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
				],
				'times' => 6
			]
		);

		$this->testee->register_settings();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::settings_page
	 */
	public function testSettingsPage() {
		$this->wpMockFunction(
			'add_options_page',
			[
				'WP OAuth Login settings',
				'WP OAuth Login',
				'manage_options',
				'login-with-oauth',
				[
					$this->testee,
					'output'
				],
			]
		);

		$this->testee->settings_page();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::output
	 */
	public function testOutput() {
		$this->wpMockFunction(
			'settings_fields',
			[
				'wp_oauth_login',
			],
			1
		);

		$this->wpMockFunction(
			'do_settings_sections',
			[
				'login-with-oauth',
			],
			1
		);

		$this->wpMockFunction(
			'submit_button',
			[],
			1,
			''
		);

		$this->setOutputCallback(function() {});
		$this->testee->output();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::client_id_field
	 */
	public function testClientIdField() {
		$this->wpMockFunction(
			'esc_html__',
			[
				'Create oAuth Client ID and Client Secret at',
				'login-with-oauth'
			],
			2,
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[
				sprintf(
					'<p>%1s <a target="_blank" href="%2s">%3s</a>.</p>',
					esc_html__( 'Create oAuth Client ID and Client Secret at', 'login-with-oauth' ),
					'https://console.developers.oauth.com/apis/dashboard',
					'console.developers.oauth.com'
				)
			],
			1,
		);

		$this->setOutputCallback(function() {});
		$this->testee->client_id_field();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::user_registration
	 */
	public function testUserRegistration() {
		$this->testee->registration_enabled = 'yes';

		$this->wpMockFunction(
			'checked',
			[
				'yes'
			],
			1,
		);

		$this->wpMockFunction(
			'esc_html_e',
			[
				'Create a new user account if it does not exist already',
				'login-with-oauth'
			],
			1,
		);

		$this->wpMockFunction(
			'is_multisite',
			[],
			1,
			'network/settings.php'
		);

		$this->wpMockFunction(
			'wp_kses_post',
			[
				/* translators: %1s will be replaced by page link */
				__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="network/settings.php">membership setting</a> is off.', 'login-with-oauth' ),
			],
			1,
		);

		$this->setOutputCallback(function() {});
		$this->testee->user_registration();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::whitelisted_domains
	 */
	public function testWhitelistedDomains() {
		$this->testee->whitelisted_domains = 'https://example1.com,https://example2.com';

		$this->wpMockFunction(
			'esc_attr',
			[
				'https://example1.com,https://example2.com',
			],
			1,
		);

		$this->wpMockFunction(
			'esc_html',
			[
				__( 'Add each domain comma separated', 'login-with-oauth' )
			],
			1,
		);

		$this->setOutputCallback(function() {});
		$this->testee->whitelisted_domains();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::client_secret_field
	 */
	public function testClientSecretField() {
		$this->testee->client_secret = 'cis';
		$this->wpMockFunction(
			'esc_attr',
			[
				'cis'
			],
			1
		);

		$this->setOutputCallback(function() {});
		$this->testee->client_secret_field();
		$this->assertConditionsMet();
	}

}
