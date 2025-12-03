# Simple Email Login - WordPress Plugin

A simple email-based login system with Google OAuth integration for WordPress.

## Features

- **Passwordless Email Login**: Users can login using only their email address
- **Google OAuth Integration**: Optional Google Sign-In support
- **Auto User Creation**: Automatically creates a new WordPress user if email doesn't exist
- **Admin Settings Page**: Easy configuration for Google OAuth credentials
- **Clean UI**: Modern and responsive design
- **AJAX-powered**: Smooth login experience without page reload
- **Shortcode Support**: Easy integration with `[simple_email_login]`

## Installation

1. Upload the `login-with-google` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Simple Email Login to configure Google OAuth (optional)
4. Use the shortcode `[simple_email_login]` on any page or post

## Configuration

### Google OAuth Setup (Optional)

1. Go to WordPress Admin → Settings → Simple Email Login
2. Check "Enable Google OAuth Login" if you want Google sign-in
3. Enter your Google Client ID and Client Secret
4. Follow the instructions on the settings page to get credentials from Google Cloud Console

### Basic Shortcode

```
[simple_email_login]
```

Simply add this shortcode to any page, post, or widget where you want to display the login form.

## How It Works

1. **For Logged Out Users**: Shows a login form with email input field
2. **For Logged In Users**: Displays "Welcome, [Username]"
3. **New Users**: Automatically creates an account when a new email is used
4. **Existing Users**: Logs them in directly
5. **Google Login**: Optional Google OAuth authentication (when enabled)

## Files Structure

```
login-with-google/
├── login-with-google.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── style.css        # Styling
│   └── js/
│       └── script.js        # AJAX functionality
└── README.md
```

## Security

- Uses WordPress nonces for AJAX security
- Email validation on both client and server side
- Sanitized email inputs
- Built-in WordPress authentication functions

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Author

Mushfikur Rahman

## Version

1.0.0

## License

GPL v2 or later
