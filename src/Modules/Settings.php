<?php
/**
 * Register the settings under settings page and also
 * provide the interface to retrieve the settings.
 *
 * @package RtCamp\OAuthLogin
 * @since 1.0.0
 * @author rtCamp <contact@rtcamp.com>
 */

declare(strict_types=1);

namespace RtCamp\OAuthLogin\Modules;

use RtCamp\OAuthLogin\Interfaces\Module as ModuleInterface;

/**
 * Class Settings.
 *
 * @property string|null whitelisted_domains
 * @property string|null client_id
 * @property string|null client_secret
 * @property bool|null registration_enabled
 * @property bool|null one_tap_login
 * @property string    one_tap_login_screen
 *
 * @package RtCamp\OAuthLogin\Modules
 */
class Settings implements ModuleInterface {

	/**
	 * Settings values.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Getters for settings values.
	 *
	 * @var string[]
	 */
	private $getters = [
		'WP_OAUTH_LOGIN_CLIENT_ID'         => 'client_id',
		'WP_OAUTH_LOGIN_SECRET'            => 'client_secret',
		'WP_OAUTH_LOGIN_USER_REGISTRATION' => 'registration_enabled',
		'WP_OAUTH_LOGIN_WHITELIST_DOMAINS' => 'whitelisted_domains',
		'WP_OAUTH_ONE_TAP_LOGIN'           => 'one_tap_login',
		'WP_OAUTH_ONE_TAP_LOGIN_SCREEN'    => 'one_tap_login_screen',
	];

	/**
	 * Getter method.
	 *
	 * @param string $name Name of option to fetch.
	 */
	public function __get( string $name ) {
		if ( in_array( $name, $this->getters, true ) ) {
			$constant_name = array_search( $name, $this->getters, true );

			return defined( $constant_name ) ? constant( $constant_name ) : ( $this->options[ $name ] ?? '' );
		}

		return null;
	}

	/**
	 * Return module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'settings';
	}

	/**
	 * Initialization of module.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->options = get_option( 'wp_oauth_login_settings', [] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'settings_page' ] );
	}

	/**
	 * Register the settings, section and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( 'wp_oauth_login', 'wp_oauth_login_settings' );

		add_settings_section(
			'wp_oauth_login_section',
			__( 'Log in with OAuth Settings', 'login-with-oauth' ),
			function () {
			},
			'login-with-oauth'
		);

		add_settings_field(
			'wp_oauth_login_client_id',
			__( 'Client ID', 'login-with-oauth' ),
			[ $this, 'client_id_field' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'client-id' ]
		);

		add_settings_field(
			'wp_oauth_login_client_secret',
			__( 'Client Secret', 'login-with-oauth' ),
			[ $this, 'client_secret_field' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'client-secret' ]
		);

		add_settings_field(
			'wp_oauth_allow_registration',
			__( 'Create New User', 'login-with-oauth' ),
			[ $this, 'user_registration' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'user-registration' ]
		);

		add_settings_field(
			'wp_oauth_one_tap_login',
			__( 'Enable One Tap Login', 'login-with-oauth' ),
			[ $this, 'one_tap_login' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'one-tap-login' ]
		);

		add_settings_field(
			'wp_oauth_one_tap_login_screen',
			__( 'One Tap Login Locations', 'login-with-oauth' ),
			[ $this, 'one_tap_login_screens' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'one-tap-login-screen' ]
		);

		add_settings_field(
			'wp_oauth_whitelisted_domain',
			__( 'Whitelisted Domains', 'login-with-oauth' ),
			[ $this, 'whitelisted_domains' ],
			'login-with-oauth',
			'wp_oauth_login_section',
			[ 'label_for' => 'whitelisted-domains' ]
		);
	}

	/**
	 * Render client ID field.
	 *
	 * @return void
	 */
	public function client_id_field(): void {
		?>
		<input type='text' name='wp_oauth_login_settings[client_id]' id="client-id" value='<?php echo esc_attr( $this->client_id ); ?>' autocomplete="off" <?php $this->disabled( 'client_id' ); ?> />
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
					'<p>%1s <a target="_blank" href="%2s">%3s</a>.</p>',
					esc_html__( 'Create oAuth Client ID and Client Secret at', 'login-with-oauth' ),
					'https://console.developers.oauth.com/apis/dashboard',
					'console.developers.oauth.com'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render client secret field.
	 *
	 * @return void
	 */
	public function client_secret_field(): void {
		?>
		<input type='password' name='wp_oauth_login_settings[client_secret]' id="client-secret" value='<?php echo esc_attr( $this->client_secret ); ?>' autocomplete="off" <?php $this->disabled( 'client_secret' ); ?> />
		<?php
	}

	/**
	 * User registration field.
	 *
	 * This will tell us whether or not to create the user
	 * if the user does not exist on WP application.
	 *
	 * This is irrespective of registration flag present in Settings > General
	 *
	 * @return void
	 */
	public function user_registration(): void {
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'registration_enabled' ); ?> type='checkbox'
															name='wp_oauth_login_settings[registration_enabled]'
															id="user-registration" <?php echo esc_attr( checked( $this->registration_enabled ) ); ?>
															value='1'>
			<?php esc_html_e( 'Create a new user account if it does not exist already', 'login-with-oauth' ); ?>
		</label>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1s will be replaced by page link */
					__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'login-with-oauth' ),
					is_multisite() ? 'network/settings.php' : 'options-general.php'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Toggle One Tap Login functionality.
	 *
	 * @return void
	 */
	public function one_tap_login(): void {
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='checkbox'
					name='wp_oauth_login_settings[one_tap_login]'
					id="one-tap-login" <?php echo esc_attr( checked( $this->one_tap_login ) ); ?>
					value='1'>
			<?php esc_html_e( 'One Tap Login', 'login-with-oauth' ); ?>
		</label>
		<?php
	}

	/**
	 * One tap login screens.
	 *
	 * It can be enabled only for wp-login.php OR sitewide.
	 *
	 * @return void
	 */
	public function one_tap_login_screens(): void {
		$default = $this->one_tap_login_screen ?? '';
		?>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='radio'
					name='wp_oauth_login_settings[one_tap_login_screen]'
					id="one-tap-login-screen-login" <?php echo esc_attr( checked( $this->one_tap_login_screen, $default ) ); ?>
					value='login'>
			<?php esc_html_e( 'Enable One Tap Login Only on Login Screen', 'login-with-oauth' ); ?>
		</label>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='radio'
					name='wp_oauth_login_settings[one_tap_login_screen]'
					id="one-tap-login-screen-sitewide" <?php echo esc_attr( checked( $this->one_tap_login_screen, 'sitewide' ) ); ?>
					value='sitewide'>
			<?php esc_html_e( 'Enable One Tap Login Site-wide', 'login-with-oauth' ); ?>
		</label>
		<?php
		// phpcs:disable
		?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                var toggle = function () {
                    var enabled = jQuery("#one-tap-login").is(":checked");
                    var tr_elem = jQuery("#one-tap-login-screen-login").parents("tr");
                    if (enabled) {
                        tr_elem.show();
                        return;
                    }

                    tr_elem.hide();
                };
                jQuery("#one-tap-login").on('change', toggle);
                toggle();
            });
        </script>
		<?php
		// phpcs:enable
	}

	/**
	 * Whitelisted domains for registration.
	 *
	 * Only emails belonging to these domains would be preferred
	 * for registration.
	 *
	 * If left blank, all domains would be allowed.
	 *
	 * @return void
	 */
	public function whitelisted_domains(): void {
		?>
		<input <?php $this->disabled( 'whitelisted_domains' ); ?> type='text' name='wp_oauth_login_settings[whitelisted_domains]' id="whitelisted-domains" value='<?php echo esc_attr( $this->whitelisted_domains ); ?>' autocomplete="off" />
		<p class="description">
			<?php echo esc_html( __( 'Add each domain comma separated', 'login-with-oauth' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Add settings sub-menu page in admin menu.
	 *
	 * @return void
	 */
	public function settings_page(): void {
		add_options_page(
			__( 'WP OAuth Login settings', 'login-with-oauth' ),
			__( 'WP OAuth Login', 'login-with-oauth' ),
			'manage_options',
			'login-with-oauth',
			[ $this, 'output' ]
		);
	}

	/**
	 * Output the plugin settings.
	 *
	 * @return void
	 */
	public function output(): void {
		?>
		<div class="wrap">
		<form action='options.php' method='post'>
			<?php
			settings_fields( 'wp_oauth_login' );
			do_settings_sections( 'login-with-oauth' );
			submit_button();
			?>
		</form>
		</div>
		<?php
	}

	/**
	 * Outputs the disabled attribute if field needs to
	 * be disabled.
	 *
	 * @param string $id Input ID.
	 *
	 * @return void
	 */
	private function disabled( string $id ): void {
		if ( empty( $id ) ) {
			return;
		}

		$constant_name = array_search( $id, $this->getters, true );

		if ( false !== $constant_name ) {
			if ( defined( $constant_name ) ) {
				echo esc_attr( 'disabled="disabled"' );
			}
		}
	}
}
