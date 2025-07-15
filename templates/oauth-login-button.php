<?php
/**
 * Template for oauth login button.
 *
 * @package DaxHurley\OAuthLogin
 * @since 1.0.0
 */

use DaxHurley\OAuthLogin\Utils\Helper;

/**
 * Determine if a color is dark based on its luminance.
 *
 * @param string $color Hex color value (with or without #).
 * @return bool True if the color is dark, false otherwise.
 */
function is_dark_color( $color ) {
	// Remove # if present
	$color = ltrim( $color, '#' );
	
	// If not a valid hex color, assume it's light
	if ( ! preg_match( '/^[0-9a-f]{6}$/i', $color ) ) {
		return false;
	}
	
	// Convert hex to RGB
	$r = hexdec( substr( $color, 0, 2 ) );
	$g = hexdec( substr( $color, 2, 2 ) );
	$b = hexdec( substr( $color, 4, 2 ) );
	
	// Calculate luminance using the formula: 0.299*R + 0.587*G + 0.114*B
	$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
	
	// Return true if luminance is less than 0.5 (dark color)
	return $luminance < 0.5;
}

if ( isset( $custom_btn_text ) && $custom_btn_text ) {
	$button_text = esc_html( $custom_btn_text );
} else {
	$button_text = ( ! empty( $button_text ) ) ? $button_text : __( 'Login with OAuth', 'wp-oauth-login' );
}

if ( empty( $login_url ) ) {
	return;
}

$button_url = $login_url;

if ( is_user_logged_in() ) {
	$button_text  = __( 'Log out', 'wp-oauth-login' );
	$redirect_url = Helper::get_redirect_url();
	$button_url   = wp_logout_url( $redirect_url );
}

// Get provider name for display
$provider_name = isset( $provider_name ) ? $provider_name : __( 'OAuth Provider', 'wp-oauth-login' );

// Determine if this is a shortcode or block (not the default WP login page)
$is_shortcode_or_block = isset( $css_classes ) || isset( $disable_styles ) || isset( $force_display_block );

// Get provider configuration for customization
$provider_config = isset( $provider_config ) ? $provider_config : [];
$button_icon = $provider_config['button_icon'] ?? '';
$button_color = $provider_config['button_color'] ?? '';

// Build CSS classes
$container_classes = 'wp_oauth_login';
if ( $is_shortcode_or_block ) {
	$container_classes .= ' wp_oauth_login--shortcode';
}
if ( ! empty( $css_classes ) ) {
	$container_classes .= ' ' . esc_attr( $css_classes );
}

// Build button styles
$button_styles = '';
if ( ! empty( $button_color ) && ( ! isset( $disable_styles ) || $disable_styles !== 'yes' ) ) {
	$button_styles .= 'background-color: ' . esc_attr( $button_color ) . ';';
	
	// Add white text color if background is dark
	if ( is_dark_color( $button_color ) ) {
		$button_styles .= 'color: #ffffff;';
	}
}

// Build icon styles
$icon_styles = '';
if ( ! empty( $button_icon ) ) {
	$icon_styles .= 'background-image: url("' . esc_url( $button_icon ) . '");';
}

?>
<div class="<?php echo esc_attr( $container_classes ); ?>">
	<div
		<?php
		if ( ! isset( $disable_styles ) || $disable_styles !== 'yes' ) {
			echo 'class="wp_oauth_login__button-container"';
		}
		?>
	>
		<a
			<?php
			if ( ! isset( $disable_styles ) || $disable_styles !== 'yes' ) {
				echo 'class="wp_oauth_login__button"';
			}
			printf( ' href="%s"', esc_url( $button_url ) );
			if ( ! empty( $button_styles ) ) {
				printf( ' style="%s"', esc_attr( $button_styles ) );
			}
			?>
		>
			<?php if ( ! empty( $button_icon ) ): ?>
				<span class="wp_oauth_login__oauth-icon" style="<?php echo esc_attr( $icon_styles ); ?>"></span>
			<?php endif; ?>
			<?php echo esc_html( $button_text ); ?>
			<?php if ( ! is_user_logged_in() && ! isset( $custom_btn_text ) && ! empty( $provider_name ) ): ?>
				<span class="wp_oauth_login__provider-name">(<?php echo esc_html( $provider_name ); ?>)</span>
			<?php endif; ?>
		</a>
	</div>
</div>
<?php
