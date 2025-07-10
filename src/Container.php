<?php
/**
 * Class Container.
 *
 * This will be useful for creation of object.
 * We are using Pimple DI Container, which will be
 * useful for defining services and serves as service
 * locator.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin;

use DaxHurley\OAuthLogin\Interfaces\Container as ContainerInterface;
use Pimple\Container as PimpleContainer;
use InvalidArgumentException;
use DaxHurley\OAuthLogin\Modules\Assets;
use DaxHurley\OAuthLogin\Modules\Block;
use DaxHurley\OAuthLogin\Modules\Login;
use DaxHurley\OAuthLogin\Modules\OneTapLogin;
use DaxHurley\OAuthLogin\Modules\Settings;
use DaxHurley\OAuthLogin\Utils\Authenticator;
use DaxHurley\OAuthLogin\Utils\OAuthClient;
use DaxHurley\OAuthLogin\Modules\Shortcode;
use DaxHurley\OAuthLogin\Utils\TokenVerifier;

/**
 * Class Container
 *
 * @package DaxHurley\OAuthLogin
 */
class Container implements ContainerInterface {
	/**
	 * Pimple container.
	 *
	 * @var PimpleContainer
	 */
	public $container;

	/**
	 * Container constructor.
	 *
	 * @param PimpleContainer $container Pimple Container.
	 */
	public function __construct( PimpleContainer $container ) {
		$this->container = $container;
	}

	/**
	 * Get the service object.
	 *
	 * @param string $service Service object in need.
	 *
	 * @return object
	 *
	 * @throws InvalidArgumentException Exception for invalid service.
	 */
	public function get( string $service ) {
		if ( ! in_array( $service, $this->container->keys(), true ) ) {
			$error_message = sprintf(
				/* translators: %$s is replaced with requested service name. */
				__( 'Invalid Service %s Passed to the container', 'login-with-oauth' ),
				$service
			);

			throw new InvalidArgumentException( esc_html( $error_message ) );
		}

		return $this->container[ $service ];
	}

	/**
	 * Define common services in container.
	 *
	 * All the module specific services will be defined inside
	 * respective module's container.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function define_services(): void {
		/**
		 * Define Settings service to add settings page and retrieve setting values.
		 *
		 * @return Settings
		 */
		$this->container['settings'] = function () {
			return new Settings();
		};

		/**
		 * Define the login flow service.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return Login
		 */
		$this->container['login_flow'] = function ( PimpleContainer $c ) {
			return new Login( $c['gh_client'], $c['authenticator'] );
		};

		/**
		 * Define a service for OAuth OAuth client.
		 *
		 * @param PimpleContainer $c Pimple container instance.
		 *
		 * @return OAuthClient
		 */
		$this->container['gh_client'] = function ( PimpleContainer $c ) {
			$settings = $c['settings'];

			return new OAuthClient(
				[
					'client_id'     => $settings->client_id,
					'client_secret' => $settings->client_secret,
					'redirect_uri'  => wp_login_url(),
				]
			);
		};

		/**
		 * Define Assets service to add styles or script.
		 *
		 * @return Assets
		 */
		$this->container['assets'] = function () {
			return new Assets();
		};

		/**
		 * Define Shortcode service to register shortcode for oauth login.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return Shortcode
		 */
		$this->container['shortcode'] = function ( PimpleContainer $c ) {
			return new Shortcode( $c['gh_client'], $c['assets'] );
		};

		/**
		 * Define Token Verifier Service.
		 *
		 * Useful in verifying JWT Auth token.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return TokenVerifier
		 */
		$this->container['token_verifier'] = function ( PimpleContainer $c ) {
			return new TokenVerifier( $c['settings'] );
		};

		/**
		 * One Tap Login Service.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return OneTapLogin
		 */
		$this->container['one_tap_login'] = function ( PimpleContainer $c ) {
			return new OneTapLogin( $c['settings'], $c['token_verifier'], $c['gh_client'], $c['authenticator'] );
		};

		/**
		 * Authenticator utility.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return Authenticator
		 */
		$this->container['authenticator'] = function ( PimpleContainer $c ) {
			return new Authenticator( $c['settings'] );
		};

		/**
		 * Define Block service to add gutenberg block.
		 *
		 * @param PimpleContainer $c Pimple container object.
		 *
		 * @return Block
		 */
		$this->container['oauth_login_block'] = function ( PimpleContainer $c ) {
			return new Block( $c['assets'], $c['gh_client'] );
		};


		/**
		 * Define any additional services.
		 *
		 * @param ContainerInterface $container Container object.
		 *
		 * @since 1.0.0
		 */
		do_action( 'daxhurley.oauth_login_services', $this );
	}
}
