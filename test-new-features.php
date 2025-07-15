<?php
/**
 * Test file to demonstrate the new WP OAuth Login features
 * 
 * This file shows examples of the new shortcode attributes and styling options.
 */

// Example 1: Basic shortcode with provider
echo '<h3>Basic shortcode with GitHub provider:</h3>';
echo do_shortcode('[oauth_login provider="github" button_text="Login with GitHub"]');

// Example 2: Shortcode with custom CSS classes
echo '<h3>Shortcode with custom CSS classes:</h3>';
echo do_shortcode('[oauth_login provider="github" css_classes="my-custom-button custom-theme"]');

// Example 3: Shortcode with disabled styles
echo '<h3>Shortcode with disabled styles:</h3>';
echo do_shortcode('[oauth_login provider="github" disable_styles="yes"]');

// Example 4: Shortcode with all custom options
echo '<h3>Shortcode with all custom options:</h3>';
echo do_shortcode('[oauth_login provider="github" button_text="Custom Login" css_classes="my-button" disable_styles="yes" redirect_to="/dashboard"]');

// Example 5: Force display (shows logout when logged in)
echo '<h3>Force display (shows logout when logged in):</h3>';
echo do_shortcode('[oauth_login provider="github" force_display="yes"]');

// Example 6: Multiple providers (if configured)
echo '<h3>Different providers:</h3>';
echo '<p>GitHub: ' . do_shortcode('[oauth_login provider="github"]') . '</p>';
echo '<p>Azure: ' . do_shortcode('[oauth_login provider="azure"]') . '</p>';
echo '<p>Default: ' . do_shortcode('[oauth_login]') . '</p>';

// Example 7: Styling demonstration
echo '<h3>Styling demonstration:</h3>';
echo '<style>
.my-custom-button {
    border: 2px solid #007cba !important;
    border-radius: 8px !important;
}
.custom-theme {
    background: linear-gradient(45deg, #007cba, #005a87) !important;
    color: white !important;
}
.my-button {
    font-weight: bold !important;
    text-transform: uppercase !important;
}
</style>';

echo do_shortcode('[oauth_login provider="github" css_classes="my-custom-button custom-theme"]');
echo '<br><br>';
echo do_shortcode('[oauth_login provider="github" css_classes="my-button" disable_styles="yes"]');
?> 