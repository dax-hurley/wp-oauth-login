# WP OAuth Login

This is is a fork of [login with google](https://github.com/rtCamp/login-with-google) written by [rtCamp](https://rtcamp.com/), I have modified and extended the functionality for my own purposes to generically support any OAuth 2.0 provider. I created it to allow my site's users to authenticate with Clerk, which we are using for our existing web app.

Licensed under the same GPL license as the original plugin, feel free to use it for whatever you want!

> WordPress plugin to login/register with OAuth

- [WP OAuth Login](#login-with-oauth)
  - [Overview](#overview)
  - [Installation](#installation)
  - [Browser support](#browser-support)
  - [Usage Instructions](#usage-instructions)
    - [Plugin Constants](#plugin-constants)
    - [Hooks](#hooks)
      - [Filters](#filters)
      - [Actions](#actions)
  - [Shortcode](#shortcode)
  - [Contribute](#contribute)
  - [Unit testing](#unit-testing)
  - [Code Snippets](#code-snippets)
  - [Minimum Requirements](#minimum-requirements)
  - [License](#license)
  - [BTW, We're Hiring!](#btw-were-hiring)

## Overview

WP OAuth Login provides seamless experience for users to login in to WordPress
sites using their OAuth provider. No need to manually create accounts, no need to remember quirky
passwords. Just one click and land into the site!

## Installation

1. Clone this repository.
2. Run `composer install --no-dev` inside the cloned directory.
3. Navigate to the `assets` directory by using `cd assets`.
4. Use `nvm` to install the recommended node version.
5. Run `npm run production` inside the `assets` directory.
6. Upload the directory to the `wp-content/plugins` directory.
7. Activate the plugin from the WordPress dashboard.

## Usage Instructions

1. You will need to create an application with your OAuth provider.
2. `Authorization callback URL` should be like `https://yourdomain.com/wp-login.php`, where
`https://yourdomain.com` will be replaced by your site URL.

3. Once you create the app, you will receive the `Client ID` and `Client Secret`, add these credentials
in `Settings > WP OAuth Login` settings page in their respective fields.

4. `Create new user` enables new user registration irrespective of `Membership` settings in
   `Settings > General`; as sometimes enabling user registration can lead to lots of spam users.
   Plugin will take this setting as first priority and membership setting as second priority, so if
   any one of them is enabled, new users will be registered by this plugin after successful authorization.

5. `Whitelisted Domains` allows users from specific domains (domain in email) to get registered on site.
This will prevent unwanted registration on website.
**For Example:** If you want users only from your organization (`myorg.com`) to get registered on the
website, you enter `myorg.com` in whitelisted domains. Users with OAuth
email like `abc@myorg.com` will be able to register on website. Contrary to this, users with emails like
`something@gmail.com` would not be able to register here.

### Plugin Constants

Above mentioned settings can also be configured via PHP constants by defining them in wp-config.php
file.

Refer following list of constants.

|                                   | Type    | Description                                                                                                                                                                 |
|-----------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WP_OAUTH_LOGIN_CLIENT_ID         | String  | OAuth client ID of your application.                                                                                                                                       |
| WP_OAUTH_LOGIN_SECRET            | String  | Secret key of your application                                                                                                                                              |
| WP_OAUTH_LOGIN_USER_REGISTRATION | Boolean | (Optional) Set True If you want to enable new user registration. By default, user registration defers to `Settings > General Settings > Membership` if constant is not set. |
| WP_OAUTH_LOGIN_WHITELIST_DOMAINS | String  | (Optional) Domain name, if you want to restrict login with your custom domain. By default, It will allow all domains. You can whitelist multiple domains.                   |

These constants can also be configured
via [wp-cli](https://developer.wordpress.org/cli/commands/config/).

**Note:** If you have defined the constant in wp-config.php file, corresponding settings field will be disable
(locked for editing) on the settings page.

### Hooks
#### Filters

| Filter | Description | Parameters |
| --- | ----------- | --- |
| `rtcamp.oauth_scope` | This filter can be used to filter existing scope used in OAuth Sign in. <br />You can ask for additional permission while user logs in. | <ul><li>`scope` - contains array of scopes.</li></ul>
| `rtcamp.oauth_login_modules` | Filter out active modules before modules are initialized. | <ul><li>`active_modules` - contains array of active modules.</li></ul>
| `rtcamp.oauth_login_button_display` | This filter is useful where we want to forcefully display login button, even when user is already logged-in in system. | <ul><li>`display` - contains a boolean value of whether to display the button or not.</li></ul>
| `rtcamp.oauth_default_redirect` | Filter the default redirect URL in case redirect_to param is not available. <br />Default to admin URL. | <ul><li>`admin_url` - contains the admin URL address which is used as redirect URL by default.</li></ul>
| `rtcamp.oauth_register_user` | Check if we need to register the user. | <ul><li>`user` - contains the user object from OAuth.</li></ul>
| `rtcamp.oauth_client_args` | Filter the arguments for sending in query. <br />This is useful in cases for example: choosing the correct prompt. | <ul><li>`client_args` - contains the list of query arguments to send to OAuth OAuth.</li></ul>
| `rtcamp.oauth_login_state` | Filters the state to pass to the OAuth API. | <ul><li>`state_data` - contains the default state data.</li></ul>
| `rtcamp.default_algorithm` | Filters default algorithm for openssl signature verification | <ul><li>`default_algo` - Default algorithm.</li><li>`algo` - Algorithm from JWT header.</li></ul>

#### Actions

| Action | Description | Parameters |
| --- | ----------- | --- |
| `rtcamp.oauth_login_services` | Define any additional services. | <ul><li>`container` - Container object.</li></ul>
| `rtcamp.oauth_user_authenticated` | Fires once the user has been authenticated via OAuth OAuth. | <ul><li>`user` - User object.</li></ul>
| `rtcamp.id_token_verified` | Do something when token has been verified successfully.<br />If we are here that means ID token has been verified.
| `rtcamp.oauth_user_logged_in` | Fires once the user has been authenticated. | <ul><li>`user_wp` - WP User data object.</li><li>`user` - User data object returned by OAuth.</li></ul>
| `rtcamp.oauth_user_created` | Fires once the user has been registered successfully. | <ul><li>`uid` - User ID</li><li>`user` - WP user object.</li></ul>
| `rtcamp.login_with_oauth_exception` | Fires when an exception is raised during token verification. | <ul><li>`exception` - The exception which is being raised.</li></ul>

## Shortcode

You can add the OAuth login button to any page/post using shortcode: `oauth_login`

**Example:**
```php
[oauth_login button_text="OAuth Login" force_display="yes" /]
```

**Supported attributes for shortcode**

| Parameter      | Description                                                   | Values | Default            |
| -------------- | --------------------------------------------------------------| -------| ------------------ |
| button_text    | Text to show for login button                                 | string | WP OAuth Login  |
| force_display  | Whether to display button when user is already logged in      | yes/no | no                 |
| redirect_to    | URL where user should be redirected post login                | URL    | `wp-admin`         |

## Contribute
- For contributing to this plugin, please refer to [CONTRIBUTING.md](docs/CONTRIBUTING.md) for more details.

## Unit testing

Unit tests can be run with simple command `composer tests:unit`.
Please note that you'll need to do `composer install` (need to install dev dependencies) for running
unit tests.

You should have PHP CLI > 7.1 installed. If you have Xdebug enabled with php, code coverage report will be
generated at `/tmp/report/html`

## Code Snippets
Code snippets to extend and customize the plugin can be found [here](docs/CODE_SNIPPETS.md).

## Minimum Requirements

WordPress >= 5.5.0

PHP >= 7.4

## License

This library is released under
["GPL 2.0 or later" License](LICENSE).
