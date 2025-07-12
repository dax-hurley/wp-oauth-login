<?php
/**
 * Migration Utility.
 *
 * Handles migration from old Google-specific configuration to new provider system.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.4.0
 */

declare(strict_types=1);

namespace DaxHurley\OAuthLogin\Utils;

/**
 * Class Migration
 *
 * @package DaxHurley\OAuthLogin\Utils
 */
class Migration {

	/**
	 * Check if migration is needed.
	 *
	 * @return bool
	 */
	public static function is_migration_needed(): bool {
		$old_settings = get_option( 'wp_oauth_login_settings', [] );
		$new_providers = get_option( 'wp_oauth_login_providers', [] );
		
		// Check if old settings exist and new providers don't
		return ! empty( $old_settings['client_id'] ) && empty( $new_providers );
	}

	/**
	 * Migrate old settings to new provider system.
	 *
	 * @return bool
	 */
	public static function migrate(): bool {
		$old_settings = get_option( 'wp_oauth_login_settings', [] );
		
		if ( empty( $old_settings['client_id'] ) ) {
			return false;
		}

		// Create Google provider configuration
		$google_config = [
			'name' => 'google',
			'display_name' => 'Google',
			'authorization_url' => 'https://accounts.google.com/o/oauth2/auth',
			'token_url' => 'https://oauth2.googleapis.com/token',
			'user_info_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
			'client_id' => $old_settings['client_id'],
			'client_secret' => $old_settings['client_secret'] ?? '',
			'default_scopes' => 'email profile openid',
		];

		// Save the new provider configuration
		$providers = [ 'google' => $google_config ];
		$success = update_option( 'wp_oauth_login_providers', $providers );

		if ( $success ) {
			// Mark migration as complete
			update_option( 'wp_oauth_login_migration_complete', true );
			
			/**
			 * Fires when migration is completed successfully.
			 *
			 * @param array $old_settings Old settings that were migrated.
			 * @param array $providers New provider configurations.
			 */
			do_action( 'daxhurley.oauth_migration_completed', $old_settings, $providers );
		}

		return $success;
	}

	/**
	 * Get migration notice HTML.
	 *
	 * @return string
	 */
	public static function get_migration_notice(): string {
		$notice = '<div class="notice notice-warning is-dismissible">';
		$notice .= '<p><strong>' . __( 'WP OAuth Login Update Required', 'wp-oauth-login' ) . '</strong></p>';
		$notice .= '<p>' . __( 'Your existing Google OAuth settings have been preserved.', 'wp-oauth-login' ) . '</p>';
		$notice .= '<p><a href="' . admin_url( 'options-general.php?page=wp-oauth-login&migrate=1' ) . '" class="button button-primary">' . __( 'Migrate Now', 'wp-oauth-login' ) . '</a></p>';
		$notice .= '</div>';
		
		return $notice;
	}

	/**
	 * Check if migration has been completed.
	 *
	 * @return bool
	 */
	public static function is_migration_complete(): bool {
		return (bool) get_option( 'wp_oauth_login_migration_complete', false );
	}

	/**
	 * Clean up old settings after migration.
	 *
	 * @return bool
	 */
	public static function cleanup_old_settings(): bool {
		// Only cleanup if migration is complete
		if ( ! self::is_migration_complete() ) {
			return false;
		}

		// Remove old settings
		delete_option( 'wp_oauth_login_settings' );
		
		// Mark cleanup as complete
		update_option( 'wp_oauth_login_cleanup_complete', true );
		
		return true;
	}

	/**
	 * Get migration status.
	 *
	 * @return array
	 */
	public static function get_migration_status(): array {
		$old_settings = get_option( 'wp_oauth_login_settings', [] );
		$new_providers = get_option( 'wp_oauth_login_providers', [] );
		$migration_complete = self::is_migration_complete();
		$cleanup_complete = (bool) get_option( 'wp_oauth_login_cleanup_complete', false );

		return [
			'needs_migration' => self::is_migration_needed(),
			'has_old_settings' => ! empty( $old_settings['client_id'] ),
			'has_new_providers' => ! empty( $new_providers ),
			'migration_complete' => $migration_complete,
			'cleanup_complete' => $cleanup_complete,
			'old_client_id' => $old_settings['client_id'] ?? '',
			'provider_count' => count( $new_providers ),
		];
	}
} 