/**
 * JS for Login and Register page.
 *
 * @package login-with-oauth
 */

const wpOAuthLogin = {

	/**
	 * Init method.
	 *
	 * @return void
	 */
	init() {
		document.addEventListener( 'DOMContentLoaded', this.onContentLoaded );
	},

	/**
	 * Callback function when content is load.
	 * To render the oauth login button at after login form.
	 *
	 * Set cookie if "WP OAuth Login" button displayed to bypass page cache
	 * Do not set on wp login or registration page.
	 *
	 * @return void
	 */
	onContentLoaded() {

		// Form either can be login or register form.
		this.form = document.getElementById( 'loginform' ) || document.getElementById( 'registerform' );

		// Set cookie if "WP OAuth Login" button displayed to bypass page cache
		// Do not set on wp login or registration page.
		if ( document.querySelector( '.wp_oauth_login' ) && null === this.form ) {
			document.cookie = 'vip-go-cb=1;wp-login-with-oauth=1;path=' + encodeURI(window.location.pathname) + ';';
		}

		if ( null === this.form ) {
			return;
		}

		this.oauthLoginButton = this.form.querySelector( '.wp_oauth_login' );
		this.oauthLoginButton.classList.remove( 'hidden' );
		// HTML is cloned from existing HTML node.
		this.form.append( this.oauthLoginButton );
	}

};

wpOAuthLogin.init();
