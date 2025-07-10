<?php
/**
 * Template for oauth login button.
 *
 * @package DaxHurley\GithubLogin
 * @since 1.0.0
 */

use DaxHurley\OAuthLogin\Utils\Helper;

if ( isset( $custom_btn_text ) && $custom_btn_text ) {
	$button_text = esc_html( $custom_btn_text );
} else {
	$button_text = ( ! empty( $button_text ) ) ? $button_text : __( 'Login', 'login-with-oauth' );
}

if ( empty( $login_url ) ) {
	return;
}

$button_url = $login_url;

if ( is_user_logged_in() ) {
	$button_text  = __( 'Log out', 'login-with-oauth' );
	$redirect_url = Helper::get_redirect_url();
	$button_url   = wp_logout_url( $redirect_url );
}
?>
<div class="wp_oauth_login">
	<div class="wp_oauth_login__button-container">
		<a class="wp_oauth_login__button"
			<?php
			printf( ' href="%s"', esc_url( $button_url ) );
			?>
		>
			<span class="wp_oauth_login__oauth-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
