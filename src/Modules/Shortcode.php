<?php
/**
 * Shortcode Class.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Modules;

use DaxHurley\OAuthLogin\Interfaces\Module as ModuleInterface;
use DaxHurley\OAuthLogin\Utils\Helper;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Utils\ProviderManager;
use function DaxHurley\OAuthLogin\plugin;

/**
 * Class Shortcode
 *
 * @package DaxHurley\OAuthLogin\Modules
 */
class Shortcode implements ModuleInterface {

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	const TAG = 'oauth_login';

	/**
	 * Redirect URL.
	 *
	 * @var string
	 */
	public $redirect_uri;

	/**
	 * OAuth client instance.
	 *
	 * @var OAuthClient
	 */
	private $oauth_client;

	/**
	 * Assets object.
	 *
	 * @var Assets
	 */
	private $assets;

	/**
	 * Provider manager instance.
	 *
	 * @var ProviderManager
	 */
	private $provider_manager;

	/**
	 * Shortcode constructor.
	 *
	 * @param OAuthClient $client OAuth Client object.
	 * @param Assets       $assets Assets object.
	 */
	public function __construct( OAuthClient $client, Assets $assets ) {
		$this->oauth_client = $client;
		$this->assets    = $assets;
		$this->provider_manager = plugin()->container()->get( 'provider_manager' );
	}

	/**
	 * Module name.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'shortcode';
	}

	/**
	 * Initialization actions.
	 */
	public function init(): void {
		add_shortcode( self::TAG, [ $this, 'callback' ] );
		add_filter( 'do_shortcode_tag', [ $this, 'scan_shortcode' ], 10, 3 );
	}

	/**
	 * Callback function for shortcode rendering.
	 *
	 * @param array $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function callback( $attrs = [] ): string {
		$redirect_to = Helper::get_redirect_url();
		$attrs       = shortcode_atts(
			[
				'button_text'   => __( 'Login with OAuth', 'login-with-oauth' ),
				'force_display' => 'no',
				'redirect_to'   => $redirect_to,
				'provider'      => '',
			],
			$attrs,
			self::TAG
		);

		if ( ! $this->should_display( $attrs ) ) {
			return '';
		}

		$this->redirect_uri = $attrs['redirect_to'];

		add_filter( 'daxhurley.oauth_redirect_url', [ $this, 'redirect_url' ] );
		
		Helper::set_redirect_state_filter( $redirect_to );

		// Get the provider to use
		$provider = null;
		if ( ! empty( $attrs['provider'] ) ) {
			$provider = $this->provider_manager->get_provider( $attrs['provider'] );
		}
		
		if ( ! $provider ) {
			$provider = $this->provider_manager->get_first_configured_provider();
		}

		if ( $provider ) {
			$attrs['login_url'] = $provider->get_authorization_url_with_params();
			$attrs['provider_name'] = $provider->get_display_name();
		} else {
			$attrs['login_url'] = '';
			$attrs['provider_name'] = '';
		}

		Helper::remove_redirect_state_filter();
		
		remove_filter( 'daxhurley.oauth_redirect_url', [ $this, 'redirect_url' ] );
		$template = trailingslashit( plugin()->template_dir ) . 'oauth-login-button.php';

		return Helper::render_template( $template, $attrs, false );
	}

	/**
	 * Check if the current single post or page contains
	 * shortcode. If it does, enqueue the relevant style.
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag Shortcode tag being processed.
	 * @param array|string $attrs Shortcode attributes.
	 *
	 * @return string
	 */
	public function scan_shortcode( string $output, string $tag, $attrs ): string {
		if ( ( ! is_single() && ! is_page() ) || self::TAG !== $tag || ! $this->should_display( (array) $attrs ) ) {
			return $output;
		}

		$this->assets->enqueue_login_styles();

		return $output;
	}


	/**
	 * Filter redirect URL as per shortcode param.
	 *
	 * @param string $url Login URL.
	 *
	 * @return string
	 */
	public function redirect_url( string $url ): string {

		return remove_query_arg( 'redirect_to', $url );
	}

	/**
	 * Determines whether to process the shortcode.
	 *
	 * @param array $attrs Shortcode attributes.
	 *
	 * @return bool
	 */
	private function should_display( array $attrs ): bool {
		if ( ! is_user_logged_in() || ( ! empty( $attrs['force_display'] ) && 'yes' === (string) $attrs['force_display'] ) ) {
			return true;
		}

		return false;
	}
}
