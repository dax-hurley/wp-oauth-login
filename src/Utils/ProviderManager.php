<?php
/**
 * Provider Manager.
 *
 * Manages multiple OAuth providers and their configurations.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Utils;

use DaxHurley\OAuthLogin\Interfaces\Provider as ProviderInterface;
use DaxHurley\OAuthLogin\Providers\GenericProvider;

/**
 * Class ProviderManager
 *
 * @package DaxHurley\OAuthLogin\Utils
 */
class ProviderManager {

	/**
	 * Registered providers.
	 *
	 * @var array
	 */
	private $providers = [];

	/**
	 * Provider configurations.
	 *
	 * @var array
	 */
	private $configurations = [];

	/**
	 * ProviderManager constructor.
	 */
	public function __construct() {
		$this->load_configurations();
		$this->register_default_providers();
	}

	/**
	 * Load provider configurations from WordPress options.
	 *
	 * @return void
	 */
	private function load_configurations(): void {
		$this->configurations = \get_option( 'wp_oauth_login_providers', [] );
	}

	/**
	 * Register default providers.
	 *
	 * @return void
	 */
	private function register_default_providers(): void {
		// Register configured providers
		foreach ( $this->configurations as $provider_name => $config ) {
			$this->register_provider( $provider_name, new GenericProvider( $config ) );
		}

		/**
		 * Allow other plugins to register their own providers.
		 *
		 * @param ProviderManager $manager Provider manager instance.
		 */
		do_action( 'daxhurley.oauth_register_providers', $this );
	}

	/**
	 * Register a provider.
	 *
	 * @param string                    $provider_name Provider name.
	 * @param ProviderInterface $provider     Provider instance.
	 * @return void
	 */
	public function register_provider( string $provider_name, ProviderInterface $provider ): void {
		$this->providers[ $provider_name ] = $provider;
	}

	/**
	 * Get a provider by name.
	 *
	 * @param string $provider_name Provider name.
	 * @return ProviderInterface|null
	 */
	public function get_provider( string $provider_name ): ?ProviderInterface {
		return $this->providers[ $provider_name ] ?? null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get all configured providers.
	 *
	 * @return array
	 */
	public function get_configured_providers(): array {
		$configured = [];
		foreach ( $this->providers as $name => $provider ) {
			if ( $provider->is_configured() ) {
				$configured[ $name ] = $provider;
			}
		}
		return $configured;
	}

	/**
	 * Get provider by state parameter.
	 *
	 * @param string $state State parameter.
	 * @return ProviderInterface|null
	 */
	public function get_provider_by_state( string $state ): ?ProviderInterface {
		$decoded_state = json_decode( base64_decode( $state ), true );
		
		if ( is_array( $decoded_state ) && isset( $decoded_state['provider'] ) ) {
			return $this->get_provider( $decoded_state['provider'] );
		}

		return null;
	}

	/**
	 * Save provider configuration.
	 *
	 * @param string $provider_name Provider name.
	 * @param array  $config        Configuration.
	 * @return bool
	 */
	public function save_provider_config( string $provider_name, array $config ): bool {
		$this->configurations[ $provider_name ] = $config;
		return \update_option( 'wp_oauth_login_providers', $this->configurations );
	}

	/**
	 * Delete provider configuration.
	 *
	 * @param string $provider_name Provider name.
	 * @return bool
	 */
	public function delete_provider_config( string $provider_name ): bool {
		unset( $this->configurations[ $provider_name ] );
		unset( $this->providers[ $provider_name ] );
		return \update_option( 'wp_oauth_login_providers', $this->configurations );
	}

	/**
	 * Get provider configuration.
	 *
	 * @param string $provider_name Provider name.
	 * @return array
	 */
	public function get_provider_config( string $provider_name ): array {
		return $this->configurations[ $provider_name ] ?? [];
	}

	/**
	 * Get all provider configurations.
	 *
	 * @return array
	 */
	public function get_all_configurations(): array {
		return $this->configurations;
	}

	/**
	 * Validate provider configuration.
	 *
	 * @param string $provider_name Provider name.
	 * @param array  $config        Configuration.
	 * @return bool
	 */
	public function validate_provider_config( string $provider_name, array $config ): bool {
		$provider = new GenericProvider( $config );
		return $provider->validate_config();
	}

	/**
	 * Get provider settings fields.
	 *
	 * @param string $provider_name Provider name.
	 * @return array
	 */
	public function get_provider_settings_fields( string $provider_name ): array {
		$provider = $this->get_provider( $provider_name );
		if ( $provider ) {
			return $provider->get_settings_fields();
		}
		return [];
	}

	/**
	 * Get the first configured provider.
	 *
	 * @return ProviderInterface|null
	 */
	public function get_first_configured_provider(): ?ProviderInterface {
		$configured = $this->get_configured_providers();
		return ! empty( $configured ) ? reset( $configured ) : null;
	}

	/**
	 * Check if any provider is configured.
	 *
	 * @return bool
	 */
	public function has_configured_providers(): bool {
		return ! empty( $this->get_configured_providers() );
	}
} 