# WP OAuth Login

OAuth Login for Wordpress

This is a fork of [login with google](https://github.com/rtCamp/login-with-google) written by [rtCamp](https://rtcamp.com/), I have modified and extended the functionality for my own purposes to generically support any OAuth 2.0 provider. I created it to allow my site's users to login with any OAuth provider, not just Google.

## Features

- **Generic OAuth 2.0 Support**: Configure any OAuth 2.0 provider with custom endpoints
- **Multiple Provider Support**: Add and manage multiple OAuth providers simultaneously
- **WordPress Integration**: Seamless integration with WordPress user system
- **Shortcode Support**: Use `[oauth_login]` shortcode to add login buttons anywhere
- **Gutenberg Block**: Add OAuth login buttons using the block editor
- **One Tap Login**: Provider-aware one-tap login support (currently Google, extensible)
- **User Registration**: Automatically create WordPress users from OAuth accounts
- **Domain Whitelisting**: Restrict registration to specific email domains
- **Customizable Scopes**: Configure OAuth scopes per provider
- **JWT Token Verification**: Secure ID token verification with provider-specific certificates
- **Provider Management UI**: Easy-to-use admin interface for managing providers
- **Migration Support**: Automatic migration from old Google-specific settings

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/wp-oauth-login/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > WP OAuth Login to configure your providers

## Configuration

### Adding a Generic OAuth Provider

1. Go to **Settings > WP OAuth Login**
2. In the "OAuth Providers" section, fill out the provider form:
   - **Provider Name**: A unique identifier (e.g., "mycompany", "github")
   - **Display Name**: The name shown to users (e.g., "My Company", "GitHub")
   - **Authorization URL**: The OAuth 2.0 authorization endpoint
   - **Token URL**: The OAuth 2.0 token endpoint
   - **User Info URL**: The user info endpoint (e.g., `https://api.provider.com/user`)
   - **Client ID**: Your OAuth client ID
   - **Client Secret**: Your OAuth client secret
   - **Redirect URI**: The redirect URI for your OAuth application (defaults to your WordPress login page)
   - **Default Scopes**: Space-separated list of OAuth scopes
   - **Supports One-Tap Login**: Check if provider supports one-tap login
   - **Certificates URL**: URL for JWT certificate verification (optional)
   - **Valid Issuers**: Comma-separated list of valid JWT issuers (optional)

3. Click "Add Provider"

### Example Provider Configurations

#### GitHub
```
Provider Name: github
Display Name: GitHub
Authorization URL: https://github.com/login/oauth/authorize
Token URL: https://github.com/login/oauth/access_token
User Info URL: https://api.github.com/user
Client ID: [Your GitHub OAuth App Client ID]
Client Secret: [Your GitHub OAuth App Client Secret]
Redirect URI: https://yourdomain.com/wp-login.php
Default Scopes: user:email read:user
Supports One-Tap Login: [Leave unchecked]
```

#### Microsoft Azure AD
```
Provider Name: azure
Display Name: Microsoft
Authorization URL: https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/authorize
Token URL: https://login.microsoftonline.com/{tenant-id}/oauth2/v2.0/token
User Info URL: https://graph.microsoft.com/v1.0/me
Client ID: [Your Azure App Registration Client ID]
Client Secret: [Your Azure App Registration Client Secret]
Redirect URI: https://yourdomain.com/wp-login.php
Default Scopes: openid profile email
Supports One-Tap Login: [Leave unchecked]
Certificates URL: https://login.microsoftonline.com/{tenant-id}/discovery/v2.0/keys
Valid Issuers: https://login.microsoftonline.com/{tenant-id}/v2.0
```

#### Custom OAuth Server
```
Provider Name: mycompany
Display Name: My Company
Authorization URL: https://auth.mycompany.com/oauth/authorize
Token URL: https://auth.mycompany.com/oauth/token
User Info URL: https://api.mycompany.com/user
Client ID: [Your Client ID]
Client Secret: [Your Client Secret]
Redirect URI: https://yourdomain.com/wp-login.php
Default Scopes: email profile
Supports One-Tap Login: [Leave unchecked]
Certificates URL: https://auth.mycompany.com/.well-known/jwks.json
Valid Issuers: https://auth.mycompany.com
```

## Usage

### Shortcode

Add the OAuth login button anywhere using the shortcode:

```php
[oauth_login]
```

With custom options:

```php
[oauth_login provider="github" button_text="Login with GitHub" redirect_to="/dashboard"]
```

### Gutenberg Block

1. Add the "OAuth Login Button" block in the block editor
2. Configure the button text and display options
3. The block will automatically use the first configured provider

### Programmatic Usage

```php
// Get the provider manager
$provider_manager = new \DaxHurley\OAuthLogin\Utils\ProviderManager();

// Get a specific provider
$provider = $provider_manager->get_provider('github');

// Get the authorization URL
$auth_url = $provider->get_authorization_url_with_params();

// Check if provider is configured
if ($provider->is_configured()) {
    // Provider is ready to use
}

// Get all configured providers
$configured_providers = $provider_manager->get_configured_providers();
```

## Creating Custom Providers

You can create custom provider classes by extending `AbstractProvider`:

```php
<?php
namespace MyPlugin\Providers;

use DaxHurley\OAuthLogin\Providers\AbstractProvider;

class MyCustomProvider extends AbstractProvider {
    
    public function __construct(array $config = []) {
        parent::__construct($config);
        
        $this->name = 'mycustom';
        $this->display_name = 'My Custom Provider';
        $this->authorization_url = 'https://auth.mycustom.com/oauth/authorize';
        $this->token_url = 'https://auth.mycustom.com/oauth/token';
        $this->user_info_url = 'https://api.mycustom.com/user';
        $this->default_scopes = ['email', 'profile'];
        
        // Configure one-tap login support
        $this->config['supports_one_tap'] = false;
        $this->config['certs_url'] = 'https://auth.mycustom.com/.well-known/jwks.json';
        $this->config['valid_issuers'] = ['https://auth.mycustom.com'];
    }
    
    protected function get_documentation_url(): string {
        return 'https://docs.mycustom.com/oauth';
    }
}
```

Then register your provider:

```php
add_action('daxhurley.oauth_register_providers', function($manager) {
    $manager->register_provider('mycustom', new MyCustomProvider());
});
```

## OAuth 2.0 Compliance

This plugin implements the OAuth 2.0 Authorization Code flow as specified in [RFC 6749](https://tools.ietf.org/html/rfc6749):

- **Authorization Request**: Redirects users to the provider's authorization endpoint
- **Authorization Response**: Handles the authorization code from the provider
- **Token Request**: Exchanges the authorization code for an access token
- **User Info Request**: Uses the access token to fetch user information
- **State Parameter**: Includes state parameter for CSRF protection
- **PKCE Support**: Can be extended to support PKCE for additional security
- **JWT Verification**: Supports ID token verification with provider-specific certificates

## Redirect URI Configuration

The plugin allows you to configure a custom redirect URI for each OAuth provider. By default, it uses your WordPress login page (`wp-login.php`), but you can specify a different URL if needed.

**Default Redirect URI**: `https://yourdomain.com/wp-login.php`

**Custom Redirect URI**: You can set a custom redirect URI in the provider configuration form. This is useful if you need to use a different callback URL for specific OAuth providers.

**Important**: When configuring your OAuth application with your provider, make sure to set the redirect URI to match what you've configured in the plugin settings.

The plugin handles the OAuth callback at the configured URL and processes the authorization code to complete the login flow.

## Security Features

- **State Parameter**: CSRF protection using state parameter
- **Nonce Verification**: WordPress nonce verification
- **Input Sanitization**: All inputs are properly sanitized
- **URL Validation**: OAuth endpoints are validated as URLs
- **Secure Storage**: Sensitive data is stored securely in WordPress options
- **JWT Verification**: Secure token verification with provider-specific certificates
- **Provider Isolation**: Each provider's configuration is isolated and secure

## Hooks and Filters

### Actions

- `daxhurley.oauth_register_providers`: Register custom providers
- `daxhurley.oauth_user_authenticated`: Fired when user is authenticated
- `daxhurley.oauth_user_created`: Fired when a new user is created
- `daxhurley.oauth_register_user`: Fired before user registration
- `daxhurley.oauth_migration_completed`: Fired when migration is completed

### Filters

- `daxhurley.oauth_scope`: Modify OAuth scopes
- `daxhurley.oauth_client_args`: Modify OAuth client arguments
- `daxhurley.oauth_redirect_url`: Modify redirect URL
- `daxhurley.oauth_default_redirect`: Set default redirect URL
- `daxhurley.oauth_login_state`: Modify state parameter data
- `daxhurley.default_algorithm`: Set default JWT verification algorithm

## Requirements

- WordPress 5.5+
- PHP 7.4+
- OAuth 2.0 provider with standard endpoints

## Troubleshooting

### "The 'redirect_uri' parameter is required" Error

If you encounter this error when trying to authenticate with your OAuth provider:

1. **Check Provider Configuration**: Ensure your OAuth provider is properly configured in the WordPress admin
2. **Verify Redirect URI**: Make sure your OAuth application's redirect URI is set to `https://yourdomain.com/wp-login.php`
3. **Clear Cache**: Clear any caching plugins or server-side cache
4. **Check Plugin Version**: Ensure you're using version 1.4.0 or later

### Common OAuth Configuration Issues

- **Invalid Client ID/Secret**: Double-check your OAuth application credentials
- **Wrong Authorization URL**: Verify the authorization endpoint URL is correct
- **Missing Scopes**: Ensure required scopes (like `email`, `profile`) are configured
- **HTTPS Required**: Most OAuth providers require HTTPS for production use

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For support, please create an issue on the GitHub repository.

## License

GPL v2 or later

## Changelog

### 1.4.0
- **Major**: Complete rewrite to support generic OAuth 2.0 providers
- **New**: Provider management system with multiple provider support
- **New**: Generic provider configuration interface
- **New**: Provider interface and abstract base class
- **New**: Provider manager for handling multiple providers
- **Updated**: All modules updated to work with provider system
- **Removed**: Hardcoded Google/OAuth references
- **Added**: Support for custom OAuth endpoints and scopes
- **Added**: Provider-specific settings and validation
- **Added**: Example Google provider implementation
- **Added**: JWT token verification with provider-specific certificates
- **Added**: One-tap login support with provider awareness
- **Added**: Migration system for old Google settings
- **Added**: Admin UI for managing multiple providers
- **Added**: Support for custom JWT issuers and certificate URLs
