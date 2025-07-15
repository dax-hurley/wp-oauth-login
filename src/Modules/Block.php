<?php
/**
 * Block class.
 *
 * This is useful for registering custom gutenberg block to
 * add `WP OAuth Login` button in desired place.
 *
 * Particularly useful in FSE.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.2.3
 */

declare( strict_types=1 );

namespace DaxHurley\OAuthLogin\Modules;

use DaxHurley\OAuthLogin\Utils\Helper;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Utils\ProviderManager;
use DaxHurley\OAuthLogin\Interfaces\Module;
use function DaxHurley\OAuthLogin\plugin;

/**
 * Class Block.
 *
 * @package DaxHurley\OAuthLogin\Modules
 */
class Block implements Module {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'oauth-login-block';

	/**
	 * Assets object.
	 *
	 * @var Assets
	 */
	public $assets;

	/**
	 * OAuth client.
	 *
	 * @var OAuthClient
	 */
	public $client;

	/**
	 * Provider manager instance.
	 *
	 * @var ProviderManager
	 */
	private $provider_manager;

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'oauth_login_block';
	}

	/**
	 * Block constructor.
	 *
	 * @param Assets       $assets Assets object.
	 * @param OAuthClient $client OAuth client object.
	 */
	public function __construct( Assets $assets, OAuthClient $client ) {
		$this->assets = $assets;
		$this->client = $client;
		$this->provider_manager = plugin()->container()->get( 'provider_manager' );
	}

	/**
	 * Initialization.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register' ] );
	}


	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection(
				trailingslashit( plugin()->assets_dir ) . 'build/blocks',
				trailingslashit( plugin()->assets_dir ) . 'build/blocks/blocks-manifest.php'
			);
		}

		register_block_type(
			trailingslashit( plugin()->assets_dir ) . 'build/blocks/login-button',
			[
				'render_callback' => [ $this, 'render_login_button' ],
			]
		);
	}

	/**
	 * Render callback for block.
	 *
	 * This will output the WP OAuth Login
	 * button if user is not logged in currently.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_login_button( array $attributes ): string {
		/**
		 * This filter is useful where we want to forcefully display login button,
		 * even when user is already logged-in in system.
		 *
		 * @param bool $display flag to display button. Default false.
		 *
		 * @since 1.2.3
		 */
		$force_display = $attributes['forceDisplay'] ?? false;

		// Setting up dynamic redirect URL based on the current page.
		$redirects_to = Helper::get_redirect_url();

		Helper::set_redirect_state_filter( $redirects_to );

		if (
			$force_display ||
			! is_user_logged_in() ||
			apply_filters( 'daxhurley.oauth_login_button_display', false )
		) {
			// Get the provider to use
			$provider = $this->provider_manager->get_first_configured_provider();
			$login_url = $provider ? $provider->get_authorization_url_with_params() : '#';
			$provider_name = $provider ? $provider->get_display_name() : '';
			$provider_config = $provider ? $provider->get_config() : [];

			$markup = $this->markup(
				[
					'login_url'           => $login_url,
					'provider_name'       => $provider_name,
					'provider_config'     => $provider_config,
					'custom_btn_text'     => $attributes['buttonText'] ?? false,
					'force_display_block' => $attributes['forceDisplay'] ?? false,
				]
			);

			ob_start();
			?>
			<div class="wp_oauth_login">
				<?php echo wp_kses_post( $markup ); ?>
			</div>
			<?php

			return ob_get_clean();
		}

		Helper::remove_redirect_state_filter();

		return '';
	}

	/**
	 * Return markup for login button.
	 *
	 * @param array $args Arguments passed to template.
	 *
	 * @return string
	 */
	private function markup( array $args = [] ): string {
		$args = wp_parse_args(
			$args,
			[
				'login_url'       => '#',
				'provider_name'   => '',
				'custom_btn_text' => '',
				'forceDisplay'    => false,
			]
		);

		$template = trailingslashit( plugin()->template_dir ) . 'oauth-login-button.php';
		return Helper::render_template( $template, $args, false );
	}
}
