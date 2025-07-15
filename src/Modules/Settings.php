<?php
/**
 * Register the settings under settings page and also
 * provide the interface to retrieve the settings.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 * @author Dax Hurley <dax.hurley@gmail.com>
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\ProviderManager;

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
 * @package DaxHurley\OAuthLogin\Modules
 */
class Settings implements ModuleInterface {

	/**
	 * Settings values.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Provider manager instance.
	 *
	 * @var ProviderManager
	 */
	private $provider_manager;

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
	 * Settings constructor.
	 */
	public function __construct() {
		$this->provider_manager = new ProviderManager();
	}

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
		add_action( 'wp_ajax_save_provider', [ $this, 'save_provider_ajax' ] );
		add_action( 'wp_ajax_delete_provider', [ $this, 'delete_provider_ajax' ] );
		add_action( 'wp_ajax_get_provider', [ $this, 'get_provider_ajax' ] );
		add_action( 'admin_notices', [ $this, 'show_migration_notice' ] );
		add_action( 'admin_init', [ $this, 'handle_migration' ] );
	}

	/**
	 * Show migration notice if needed.
	 *
	 * @return void
	 */
	public function show_migration_notice(): void {
		if ( \DaxHurley\OAuthLogin\Utils\Migration::is_migration_needed() ) {
			echo \DaxHurley\OAuthLogin\Utils\Migration::get_migration_notice();
		}
	}

	/**
	 * Handle migration process.
	 *
	 * @return void
	 */
	public function handle_migration(): void {
		if ( isset( $_GET['page'] ) && 'wp-oauth-login' === $_GET['page'] && isset( $_GET['migrate'] ) && '1' === $_GET['migrate'] ) {
			if ( \DaxHurley\OAuthLogin\Utils\Migration::migrate() ) {
				wp_redirect( admin_url( 'options-general.php?page=wp-oauth-login&migration=success' ) );
				exit;
			} else {
				wp_redirect( admin_url( 'options-general.php?page=wp-oauth-login&migration=failed' ) );
				exit;
			}
		}
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
			__( 'OAuth Providers', 'wp-oauth-login' ),
			function () {
			},
			'wp-oauth-login'
		);

		add_settings_field(
			'wp_oauth_allow_registration',
			__( 'Create New User', 'wp-oauth-login' ),
			[ $this, 'user_registration' ],
			'wp-oauth-login',
			'wp_oauth_login_section',
			[ 'label_for' => 'user-registration' ]
		);

		add_settings_field(
			'wp_oauth_one_tap_login',
			__( 'Enable One Tap Login', 'wp-oauth-login' ),
			[ $this, 'one_tap_login' ],
			'wp-oauth-login',
			'wp_oauth_login_section',
			[ 'label_for' => 'one-tap-login' ]
		);

		add_settings_field(
			'wp_oauth_one_tap_login_screen',
			__( 'One Tap Login Locations', 'wp-oauth-login' ),
			[ $this, 'one_tap_login_screens' ],
			'wp-oauth-login',
			'wp_oauth_login_section',
			[ 'label_for' => 'one-tap-login-screen' ]
		);

		add_settings_field(
			'wp_oauth_whitelisted_domain',
			__( 'Whitelisted Domains', 'wp-oauth-login' ),
			[ $this, 'whitelisted_domains' ],
			'wp-oauth-login',
			'wp_oauth_login_section',
			[ 'label_for' => 'whitelisted-domains' ]
		);
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
			<?php esc_html_e( 'Create a new user account if it does not exist already', 'wp-oauth-login' ); ?>
		</label>
		<p class="description">
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1s will be replaced by page link */
					__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'wp-oauth-login' ),
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
			<?php esc_html_e( 'One Tap Login', 'wp-oauth-login' ); ?>
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
			<?php esc_html_e( 'Enable One Tap Login Only on Login Screen', 'wp-oauth-login' ); ?>
		</label>
		<label style='display:block;margin-top:6px;'><input <?php $this->disabled( 'one_tap_login' ); ?>
					type='radio'
					name='wp_oauth_login_settings[one_tap_login_screen]'
					id="one-tap-login-screen-sitewide" <?php echo esc_attr( checked( $this->one_tap_login_screen, 'sitewide' ) ); ?>
					value='sitewide'>
			<?php esc_html_e( 'Enable One Tap Login Site-wide', 'wp-oauth-login' ); ?>
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
			<?php echo esc_html( __( 'Add each domain comma separated', 'wp-oauth-login' ) ); ?>
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
			__( 'WP OAuth Login settings', 'wp-oauth-login' ),
			__( 'WP OAuth Login', 'wp-oauth-login' ),
			'manage_options',
			'wp-oauth-login',
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
			<h1><?php esc_html_e( 'WP OAuth Login Settings', 'wp-oauth-login' ); ?></h1>
			
			<?php
			// Show migration status
			if ( isset( $_GET['migration'] ) ) {
				if ( 'success' === $_GET['migration'] ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Migration completed successfully! Your Google OAuth settings have been preserved.', 'wp-oauth-login' ) . '</p></div>';
				} elseif ( 'failed' === $_GET['migration'] ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Migration failed. Please check your settings and try again.', 'wp-oauth-login' ) . '</p></div>';
				}
			}

			// Show migration status info
			$migration_status = \DaxHurley\OAuthLogin\Utils\Migration::get_migration_status();
			if ( $migration_status['has_old_settings'] && ! $migration_status['migration_complete'] ) {
				echo '<div class="notice notice-info is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Migration Status', 'wp-oauth-login' ) . '</strong></p>';
				echo '<p>' . esc_html__( 'You have old OAuth settings that need to be migrated to the new provider system.', 'wp-oauth-login' ) . '</p>';
				echo '<p><a href="' . admin_url( 'options-general.php?page=wp-oauth-login&migrate=1' ) . '" class="button button-primary">' . esc_html__( 'Migrate Now', 'wp-oauth-login' ) . '</a></p>';
				echo '</div>';
			}
			?>
			
			<!-- Provider Management Section -->
			<div class="oauth-providers-section">
				<h2><?php esc_html_e( 'OAuth Providers', 'wp-oauth-login' ); ?></h2>
				<p><?php esc_html_e( 'Configure your OAuth 2.0 providers below. You can add multiple providers to allow users to login with different services.', 'wp-oauth-login' ); ?></p>
				
				<div class="oauth-providers-list">
					<?php $this->render_providers_list(); ?>
				</div>
				
				<div class="oauth-provider-form">
					<h3><?php esc_html_e( 'Add New Provider', 'wp-oauth-login' ); ?></h3>
					<?php $this->render_provider_form(); ?>
				</div>
			</div>

			<!-- General Settings Section -->
			<div class="oauth-general-settings">
				<h2><?php esc_html_e( 'General Settings', 'wp-oauth-login' ); ?></h2>
				<form action='options.php' method='post'>
					<?php
					settings_fields( 'wp_oauth_login' );
					do_settings_sections( 'wp-oauth-login' );
					submit_button();
					?>
				</form>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			// Handle provider form submission
			$('#oauth-provider-form').on('submit', function(e) {
				e.preventDefault();
				
				var formData = $(this).serialize();
				formData += '&action=save_provider&nonce=<?php echo wp_create_nonce( 'save_provider' ); ?>';
				
				$.post(ajaxurl, formData, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data || 'Error saving provider');
					}
				});
			});

			// Handle provider editing
			$('.edit-provider').on('click', function(e) {
				e.preventDefault();
				
				var providerName = $(this).data('provider');
				var formData = {
					action: 'get_provider',
					provider: providerName,
					nonce: '<?php echo wp_create_nonce( 'get_provider' ); ?>'
				};
				
				$.post(ajaxurl, formData, function(response) {
					if (response.success) {
						var config = response.data;
						
						// Populate the form with provider data
						$('#provider_name').val(config.name);
						$('#display_name').val(config.display_name);
						$('#authorization_url').val(config.authorization_url);
						$('#token_url').val(config.token_url);
						$('#user_info_url').val(config.user_info_url);
						$('#client_id').val(config.client_id);
						$('#client_secret').val(config.client_secret);
						$('#redirect_uri').val(config.redirect_uri || '<?php echo esc_js( wp_login_url() ); ?>');
						$('#default_scopes').val(config.default_scopes || 'email profile');
						$('#supports_one_tap').prop('checked', config.supports_one_tap || false);
						$('#certs_url').val(config.certs_url || '');
						$('#valid_issuers').val(config.valid_issuers || '');
						$('#button_icon').val(config.button_icon || '');
						$('#button_color').val(config.button_color || '');
						
						// Update icon preview if icon exists
						if (config.button_icon) {
							$('#button_icon_preview img').attr('src', config.button_icon);
							$('#button_icon_preview').show();
							$('.upload-icon').hide();
						} else {
							$('#button_icon_preview').hide();
							$('.upload-icon').show();
						}
						
						// Change form title and button text
						$('.oauth-provider-form h3').text('<?php esc_html_e( 'Edit Provider', 'wp-oauth-login' ); ?>');
						$('#oauth-provider-form input[type="submit"]').val('<?php esc_html_e( 'Update Provider', 'wp-oauth-login' ); ?>');
						
						// Add edit mode styling
						$('.oauth-provider-form').addClass('edit-mode');
						
						// Scroll to form
						$('html, body').animate({
							scrollTop: $('.oauth-provider-form').offset().top - 50
						}, 500);
					} else {
						alert(response.data || 'Error loading provider data');
					}
				});
			});

			// Handle provider deletion
			$('.delete-provider').on('click', function(e) {
				e.preventDefault();
				
				if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this provider?', 'wp-oauth-login' ); ?>')) {
					return;
				}
				
				var providerName = $(this).data('provider');
				var formData = {
					action: 'delete_provider',
					provider: providerName,
					nonce: '<?php echo wp_create_nonce( 'delete_provider' ); ?>'
				};
				
				$.post(ajaxurl, formData, function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert(response.data || 'Error deleting provider');
					}
				});
			});

			// Handle form reset
			$('#reset-form').on('click', function(e) {
				e.preventDefault();
				
				// Clear all form fields
				$('#oauth-provider-form')[0].reset();
				
				// Reset form title and button text
				$('.oauth-provider-form h3').text('<?php esc_html_e( 'Add New Provider', 'wp-oauth-login' ); ?>');
				$('#oauth-provider-form input[type="submit"]').val('<?php esc_html_e( 'Add Provider', 'wp-oauth-login' ); ?>');
				
				// Remove edit mode styling
				$('.oauth-provider-form').removeClass('edit-mode');
				
				// Set default values
				$('#redirect_uri').val('<?php echo esc_js( wp_login_url() ); ?>');
				$('#default_scopes').val('email profile');
				$('#supports_one_tap').prop('checked', false);
				
				// Reset icon preview
				$('#button_icon').val('');
				$('#button_icon_preview').hide();
				$('.upload-icon').show();
			});

			// Handle icon upload
			$('.upload-icon').on('click', function(e) {
				e.preventDefault();
				
				var frame = wp.media({
					title: '<?php esc_html_e( 'Select Icon', 'wp-oauth-login' ); ?>',
					button: {
						text: '<?php esc_html_e( 'Use this icon', 'wp-oauth-login' ); ?>'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					$('#button_icon').val(attachment.url);
					$('#button_icon_preview img').attr('src', attachment.url);
					$('#button_icon_preview').show();
					$('.upload-icon').hide();
				});

				frame.open();
			});

			// Handle icon removal
			$('.remove-icon').on('click', function(e) {
				e.preventDefault();
				$('#button_icon').val('');
				$('#button_icon_preview').hide();
				$('.upload-icon').show();
			});
		});
		</script>
		<?php
	}

	/**
	 * Render the providers list.
	 *
	 * @return void
	 */
	private function render_providers_list(): void {
		$providers = $this->provider_manager->get_providers();
		
		if ( empty( $providers ) ) {
			echo '<p>' . esc_html__( 'No providers configured yet.', 'wp-oauth-login' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Provider', 'wp-oauth-login' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'wp-oauth-login' ) . '</th>';
		echo '<th>' . esc_html__( 'Redirect URI', 'wp-oauth-login' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'wp-oauth-login' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( $providers as $name => $provider ) {
			$status = $provider->is_configured() ? 
				'<span class="status-ok">' . esc_html__( 'Configured', 'wp-oauth-login' ) . '</span>' : 
				'<span class="status-error">' . esc_html__( 'Not Configured', 'wp-oauth-login' ) . '</span>';
			
			$redirect_uri = $provider->get_redirect_uri();
			$redirect_uri_display = ! empty( $redirect_uri ) ? esc_html( $redirect_uri ) : esc_html__( 'Default (wp-login.php)', 'wp-oauth-login' );
			
			echo '<tr>';
			echo '<td><strong>' . esc_html( $provider->get_display_name() ) . '</strong><br><small>' . esc_html( $name ) . '</small></td>';
			echo '<td>' . $status . '</td>';
			echo '<td><code>' . $redirect_uri_display . '</code></td>';
			echo '<td>';
			echo '<button class="button edit-provider" data-provider="' . esc_attr( $name ) . '">' . esc_html__( 'Edit', 'wp-oauth-login' ) . '</button> ';
			echo '<button class="button delete-provider" data-provider="' . esc_attr( $name ) . '">' . esc_html__( 'Delete', 'wp-oauth-login' ) . '</button>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the provider form.
	 *
	 * @return void
	 */
	private function render_provider_form(): void {
		?>
		<form id="oauth-provider-form" method="post">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="provider_name"><?php esc_html_e( 'Provider Name', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="text" id="provider_name" name="provider_name" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'A unique name for this provider (e.g., "mycompany", "github")', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="display_name"><?php esc_html_e( 'Display Name', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="text" id="display_name" name="display_name" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'The name shown to users (e.g., "My Company", "GitHub")', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="authorization_url"><?php esc_html_e( 'Authorization URL', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="url" id="authorization_url" name="authorization_url" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'The OAuth 2.0 authorization endpoint URL', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="token_url"><?php esc_html_e( 'Token URL', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="url" id="token_url" name="token_url" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'The OAuth 2.0 token endpoint URL', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="user_info_url"><?php esc_html_e( 'User Info URL', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="url" id="user_info_url" name="user_info_url" class="regular-text" />
						<p class="description"><?php esc_html_e( 'The user info endpoint URL (e.g., https://api.provider.com/user). Leave empty if user info is included in the ID token.', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="client_id"><?php esc_html_e( 'Client ID', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="text" id="client_id" name="client_id" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'The OAuth 2.0 client ID from your provider', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="client_secret"><?php esc_html_e( 'Client Secret', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="password" id="client_secret" name="client_secret" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'The OAuth 2.0 client secret from your provider', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="redirect_uri"><?php esc_html_e( 'Redirect URI', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="url" id="redirect_uri" name="redirect_uri" class="regular-text" value="<?php echo esc_attr( wp_login_url() ); ?>" />
						<p class="description"><?php esc_html_e( 'The redirect URI for your OAuth application. Defaults to your WordPress login page.', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="default_scopes"><?php esc_html_e( 'Default Scopes', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="text" id="default_scopes" name="default_scopes" class="regular-text" value="email profile" />
						<p class="description"><?php esc_html_e( 'Space-separated list of OAuth scopes (e.g., "email profile openid")', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="supports_one_tap"><?php esc_html_e( 'Supports One-Tap Login', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="supports_one_tap" name="supports_one_tap" value="1" />
						<p class="description"><?php esc_html_e( 'Check if this provider supports one-tap login (currently only Google supports this)', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="certs_url"><?php esc_html_e( 'Certificates URL', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="url" id="certs_url" name="certs_url" class="regular-text" />
						<p class="description"><?php esc_html_e( 'URL for JWT certificate verification (optional, only needed for ID token verification)', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="valid_issuers"><?php esc_html_e( 'Valid Issuers', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="text" id="valid_issuers" name="valid_issuers" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Comma-separated list of valid JWT issuers (optional, for ID token verification)', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="button_icon"><?php esc_html_e( 'Button Icon', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<div class="button-icon-upload">
							<input type="hidden" id="button_icon" name="button_icon" class="regular-text" />
							<div id="button_icon_preview" class="icon-preview" style="display: none;">
								<img src="" alt="" style="max-width: 25px; max-height: 25px; margin-right: 10px;" />
								<button type="button" class="button remove-icon"><?php esc_html_e( 'Remove', 'wp-oauth-login' ); ?></button>
							</div>
							<button type="button" class="button upload-icon"><?php esc_html_e( 'Upload Icon', 'wp-oauth-login' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Upload a custom icon for the login button (optional). Recommended size: 25x25px.', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="button_color"><?php esc_html_e( 'Button Color', 'wp-oauth-login' ); ?></label>
					</th>
					<td>
						<input type="color" id="button_color" name="button_color" class="color-picker" />
						<p class="description"><?php esc_html_e( 'Custom background color for the login button (optional).', 'wp-oauth-login' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Add Provider', 'wp-oauth-login' ) ); ?>
			<button type="button" id="reset-form" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e( 'Reset Form', 'wp-oauth-login' ); ?></button>
		</form>
		<?php
	}

	/**
	 * Save provider via AJAX.
	 *
	 * @return void
	 */
	public function save_provider_ajax(): void {
		check_ajax_referer( 'save_provider', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-oauth-login' ) );
		}

		$provider_name = sanitize_text_field( $_POST['provider_name'] ?? '' );
		$config = [
			'name' => $provider_name,
			'display_name' => sanitize_text_field( $_POST['display_name'] ?? '' ),
			'authorization_url' => esc_url_raw( $_POST['authorization_url'] ?? '' ),
			'token_url' => esc_url_raw( $_POST['token_url'] ?? '' ),
			'user_info_url' => esc_url_raw( $_POST['user_info_url'] ?? '' ),
			'client_id' => sanitize_text_field( $_POST['client_id'] ?? '' ),
			'client_secret' => sanitize_text_field( $_POST['client_secret'] ?? '' ),
			'redirect_uri' => esc_url_raw( $_POST['redirect_uri'] ?? wp_login_url() ),
			'default_scopes' => sanitize_text_field( $_POST['default_scopes'] ?? 'email profile' ),
			'supports_one_tap' => isset( $_POST['supports_one_tap'] ) ? true : false,
			'certs_url' => esc_url_raw( $_POST['certs_url'] ?? '' ),
			'valid_issuers' => sanitize_text_field( $_POST['valid_issuers'] ?? '' ),
			'button_icon' => esc_url_raw( $_POST['button_icon'] ?? '' ),
			'button_color' => sanitize_hex_color( $_POST['button_color'] ?? '' ),
		];

		// Process valid issuers if provided
		if ( ! empty( $config['valid_issuers'] ) ) {
			$issuers = array_map( 'trim', explode( ',', $config['valid_issuers'] ) );
			$config['valid_issuers'] = array_filter( $issuers );
		}

		if ( $this->provider_manager->validate_provider_config( $provider_name, $config ) ) {
			$success = $this->provider_manager->save_provider_config( $provider_name, $config );
			if ( $success ) {
				wp_send_json_success( __( 'Provider saved successfully.', 'wp-oauth-login' ) );
			} else {
				wp_send_json_error( __( 'Failed to save provider.', 'wp-oauth-login' ) );
			}
		} else {
			wp_send_json_error( __( 'Invalid provider configuration.', 'wp-oauth-login' ) );
		}
	}

	/**
	 * Delete provider via AJAX.
	 *
	 * @return void
	 */
	public function delete_provider_ajax(): void {
		check_ajax_referer( 'delete_provider', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-oauth-login' ) );
		}

		$provider_name = sanitize_text_field( $_POST['provider'] ?? '' );
		
		if ( $this->provider_manager->delete_provider_config( $provider_name ) ) {
			wp_send_json_success( __( 'Provider deleted successfully.', 'wp-oauth-login' ) );
		} else {
			wp_send_json_error( __( 'Failed to delete provider.', 'wp-oauth-login' ) );
		}
	}

	/**
	 * Get provider data via AJAX.
	 *
	 * @return void
	 */
	public function get_provider_ajax(): void {
		check_ajax_referer( 'get_provider', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-oauth-login' ) );
		}

		$provider_name = sanitize_text_field( $_POST['provider'] ?? '' );
		
		if ( empty( $provider_name ) ) {
			wp_send_json_error( __( 'Provider name is required.', 'wp-oauth-login' ) );
		}

		$config = $this->provider_manager->get_provider_config( $provider_name );
		
		if ( empty( $config ) ) {
			wp_send_json_error( __( 'Provider not found.', 'wp-oauth-login' ) );
		}

		// Process valid issuers for display
		if ( ! empty( $config['valid_issuers'] ) && is_array( $config['valid_issuers'] ) ) {
			$config['valid_issuers'] = implode( ', ', $config['valid_issuers'] );
		}

		wp_send_json_success( $config );
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
