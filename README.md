# Login with Google - WordPress Plugin

A simple email-based login system for WordPress that allows users to login with just their email address.

## Features

- **Passwordless Login**: Users can login using only their email address
- **Auto User Creation**: Automatically creates a new WordPress user if email doesn't exist
- **Clean UI**: Modern and responsive design matching the reference
- **AJAX-powered**: Smooth login experience without page reload
- **Shortcode Support**: Easy integration with `[login_with_google]`

## Installation

1. Upload the `login-with-google` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcode `[login_with_google]` on any page or post

## Usage

### Basic Shortcode

```
[login_with_google]
```

Simply add this shortcode to any page, post, or widget where you want to display the login form.

### How It Works

1. **For Logged Out Users**: Shows a login form with email input field
2. **For Logged In Users**: Displays "Welcome, [Username]"
3. **New Users**: Automatically creates an account when a new email is used
4. **Existing Users**: Logs them in directly

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
