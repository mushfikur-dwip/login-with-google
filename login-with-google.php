<?php
/**
 * Plugin Name: Simple Email Login
 * Plugin URI: https://yourwebsite.com
 * Description: Email-based login system with Google OAuth integration. Users can login with their email or Google account.
 * Version: 1.0.0
 * Author: Mushfikur Rahman
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-email-login
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SEL_VERSION', '1.0.0');
define('SEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SEL_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Activation Hook
 */
function sel_activate() {
    // Add default options
    add_option('sel_google_client_id', '');
    add_option('sel_google_client_secret', '');
    add_option('sel_google_enabled', '0');
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sel_activate');

/**
 * Deactivation Hook
 */
function sel_deactivate() {
    // Add any deactivation tasks here
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sel_deactivate');

/**
 * Enqueue styles and scripts
 */
function sel_enqueue_scripts() {
    wp_enqueue_style('sel-styles', SEL_PLUGIN_URL . 'assets/css/style.css', array(), SEL_VERSION);
    wp_enqueue_script('sel-script', SEL_PLUGIN_URL . 'assets/js/script.js', array('jquery'), SEL_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('sel-script', 'sel_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sel_login_nonce'),
        'google_enabled' => get_option('sel_google_enabled', '0'),
        'google_client_id' => get_option('sel_google_client_id', '')
    ));
}
add_action('wp_enqueue_scripts', 'sel_enqueue_scripts');

/**
 * Handle AJAX Login Request
 */
function sel_handle_login() {
    // Verify nonce
    check_ajax_referer('sel_login_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }
    
    // Check if user exists
    $user = get_user_by('email', $email);
    
    if (!$user) {
        wp_send_json_error(array(
            'message' => 'No account found with this email. Please sign up first.',
            'needs_signup' => true
        ));
    }
    
    // If no password provided yet, ask for password
    if (empty($password)) {
        wp_send_json_error(array(
            'message' => 'Please enter your password.',
            'needs_password' => true
        ));
    }
    
    // Try to authenticate with password
    $credentials = array(
        'user_login'    => $user->user_login,
        'user_password' => $password,
        'remember'      => true
    );
    
    $login_user = wp_signon($credentials, false);
    
    if (is_wp_error($login_user)) {
        wp_send_json_error(array('message' => 'Incorrect password. Please try again.'));
    }
    
    wp_send_json_success(array(
        'message' => 'Login successful!',
        'user_name' => $login_user->display_name,
        'redirect' => 'https://rideonbd.com/my-account/'
    ));
}
add_action('wp_ajax_nopriv_sel_login', 'sel_handle_login');
add_action('wp_ajax_sel_login', 'sel_handle_login');

/**
 * Handle AJAX Signup Request
 */
function sel_handle_signup() {
    // Verify nonce
    check_ajax_referer('sel_login_nonce', 'nonce');
    
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        wp_send_json_error(array('message' => 'All fields are required.'));
    }
    
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }
    
    if (strlen($password) < 6) {
        wp_send_json_error(array('message' => 'Password must be at least 6 characters long.'));
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'This email is already registered. Please login instead.'));
    }
    
    // Create username from name or email
    $username = sanitize_user(strtolower(str_replace(' ', '', $name)));
    if (empty($username)) {
        $username = sanitize_user(current(explode('@', $email)));
    }
    
    // Make username unique if it already exists
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    // Create new user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => 'Failed to create account. Please try again.'));
    }
    
    // Update user display name
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => $name
    ));
    
    // Log the user in
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    wp_send_json_success(array(
        'message' => 'Account created successfully!',
        'user_name' => $name,
        'redirect' => 'https://rideonbd.com/my-account/'
    ));
}
add_action('wp_ajax_nopriv_sel_signup', 'sel_handle_signup');
add_action('wp_ajax_sel_signup', 'sel_handle_signup');

/**
 * Handle Google OAuth Callback
 */
function sel_handle_google_callback() {
    if (!isset($_GET['code'])) {
        wp_die('Invalid request');
    }
    
    $code = sanitize_text_field($_GET['code']);
    $client_id = get_option('sel_google_client_id');
    $client_secret = get_option('sel_google_client_secret');
    $redirect_uri = admin_url('admin-ajax.php?action=sel_google_callback');
    
    // Exchange code for access token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = array(
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    );
    
    $token_response = wp_remote_post($token_url, array(
        'body' => $token_data
    ));
    
    if (is_wp_error($token_response)) {
        wp_die('Failed to authenticate with Google');
    }
    
    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    
    if (!isset($token_body['access_token'])) {
        wp_die('Failed to get access token');
    }
    
    // Get user info from Google
    $userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $userinfo_response = wp_remote_get($userinfo_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token_body['access_token']
        )
    ));
    
    if (is_wp_error($userinfo_response)) {
        wp_die('Failed to get user info from Google');
    }
    
    $userinfo = json_decode(wp_remote_retrieve_body($userinfo_response), true);
    
    if (!isset($userinfo['email'])) {
        wp_die('Failed to get email from Google');
    }
    
    // Check if user exists
    $user = get_user_by('email', $userinfo['email']);
    
    if (!$user) {
        // Create new user
        $username = sanitize_user(current(explode('@', $userinfo['email'])));
        $random_password = wp_generate_password(20, true, true);
        
        // Make username unique
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, $random_password, $userinfo['email']);
        
        if (is_wp_error($user_id)) {
            wp_die('Failed to create user account');
        }
        
        // Update user with Google info
        $display_name = isset($userinfo['name']) ? $userinfo['name'] : $username;
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name,
            'first_name' => isset($userinfo['given_name']) ? $userinfo['given_name'] : '',
            'last_name' => isset($userinfo['family_name']) ? $userinfo['family_name'] : ''
        ));
        
        // Store Google ID
        update_user_meta($user_id, 'google_id', $userinfo['id']);
        
        if (isset($userinfo['picture'])) {
            update_user_meta($user_id, 'google_picture', $userinfo['picture']);
        }
        
        $user = get_user_by('id', $user_id);
    }
    
    // Log the user in
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);
    
    // Redirect to my-account page
    wp_redirect('https://rideonbd.com/my-account/');
    exit;
}
add_action('wp_ajax_nopriv_sel_google_callback', 'sel_handle_google_callback');
add_action('wp_ajax_sel_google_callback', 'sel_handle_google_callback');

/**
 * Shortcode to display login form or user name
 */
function sel_login_shortcode() {
    ob_start();
    
    if (is_user_logged_in()) {
        // User is logged in - show their name
        $current_user = wp_get_current_user();
        ?>
        <div class="sel-user-info">
            <span class="sel-welcome-message">Welcome, <?php echo esc_html($current_user->display_name); ?></span>
        </div>
        <?php
    } else {
        // User is not logged in - show login/signup forms
        $google_enabled = get_option('sel_google_enabled', '0');
        ?>
        <div class="sel-login-container">
            <!-- Login Form -->
            <div class="sel-login-form" id="sel-login-form-container">
                <h2 class="sel-title">I ALREADY HAVE AN ACCOUNT</h2>
                <form id="sel-email-login-form">
                    <div class="sel-form-group">
                        <label for="sel-login-email">Email Address</label>
                        <input type="email" id="sel-login-email" name="email" required placeholder="">
                    </div>
                    <div class="sel-form-group" id="sel-password-group" style="display:none;">
                        <label for="sel-login-password">Password</label>
                        <input type="password" id="sel-login-password" name="password" placeholder="">
                    </div>
                    <div class="sel-forgot-password">
                        <a href="<?php echo wp_lostpassword_url(); ?>">Forgot password?</a>
                    </div>
                    <div class="sel-button-group">
                        <button type="button" class="sel-btn sel-btn-secondary" id="sel-show-signup">Create Account</button>
                        <button type="submit" class="sel-btn sel-btn-primary">Next</button>
                    </div>
                    <?php if ($google_enabled === '1') : ?>
                    <div class="sel-divider">
                        <span>OR SIGN IN WITH</span>
                    </div>
                    <button type="button" class="sel-btn sel-btn-google" id="sel-google-login">
                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                            <path d="M3.964 10.71c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.426 0 9.003 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                        </svg>
                        GOOGLE
                    </button>
                    <?php endif; ?>
                </form>
                <div class="sel-message"></div>
            </div>

            <!-- Signup Form (Hidden by default) -->
            <div class="sel-login-form" id="sel-signup-form-container" style="display:none;">
                <h2 class="sel-title">CREATE NEW ACCOUNT</h2>
                <form id="sel-signup-form">
                    <div class="sel-form-group">
                        <label for="sel-signup-name">Full Name</label>
                        <input type="text" id="sel-signup-name" name="name" required placeholder="">
                    </div>
                    <div class="sel-form-group">
                        <label for="sel-signup-email">Email Address</label>
                        <input type="email" id="sel-signup-email" name="email" required placeholder="">
                    </div>
                    <div class="sel-form-group">
                        <label for="sel-signup-password">Password</label>
                        <input type="password" id="sel-signup-password" name="password" required placeholder="">
                    </div>
                    <div class="sel-button-group">
                        <button type="button" class="sel-btn sel-btn-secondary" id="sel-show-login">Back to Login</button>
                        <button type="submit" class="sel-btn sel-btn-primary">Sign Up</button>
                    </div>
                </form>
                <div class="sel-message"></div>
            </div>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('simple_email_login', 'sel_login_shortcode');

/**
 * Header shortcode - Shows Login button or Username
 */
function sel_header_shortcode() {
    ob_start();
    
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        ?>
        <div class="sel-header-user">
            <a href="https://rideonbd.com/my-account/" class="sel-header-link">
                <?php echo esc_html($current_user->display_name); ?>
            </a>
        </div>
        <?php
    } else {
        // Get current page URL or default to login page
        $login_page = home_url('/login/'); // Adjust this to your login page URL
        ?>
        <div class="sel-header-login">
            <a href="<?php echo esc_url($login_page); ?>" class="sel-header-link">Login</a>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('sel_header', 'sel_header_shortcode');

/**
 * Add admin menu
 */
function sel_add_admin_menu() {
    add_options_page(
        'Simple Email Login Settings',
        'Simple Email Login',
        'manage_options',
        'simple-email-login',
        'sel_settings_page'
    );
}
add_action('admin_menu', 'sel_add_admin_menu');

/**
 * Register settings
 */
function sel_register_settings() {
    register_setting('sel_settings_group', 'sel_google_client_id');
    register_setting('sel_settings_group', 'sel_google_client_secret');
    register_setting('sel_settings_group', 'sel_google_enabled');
}
add_action('admin_init', 'sel_register_settings');

/**
 * Settings page HTML
 */
function sel_settings_page() {
    ?>
    <div class="wrap">
        <h1>Simple Email Login Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('sel_settings_group'); ?>
            <?php do_settings_sections('sel_settings_group'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Google Login</th>
                    <td>
                        <label>
                            <input type="checkbox" name="sel_google_enabled" value="1" <?php checked(get_option('sel_google_enabled'), '1'); ?> />
                            Enable Google OAuth Login
                        </label>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Google Client ID</th>
                    <td>
                        <input type="text" name="sel_google_client_id" value="<?php echo esc_attr(get_option('sel_google_client_id')); ?>" class="regular-text" />
                        <p class="description">Enter your Google OAuth Client ID</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Google Client Secret</th>
                    <td>
                        <input type="text" name="sel_google_client_secret" value="<?php echo esc_attr(get_option('sel_google_client_secret')); ?>" class="regular-text" />
                        <p class="description">Enter your Google OAuth Client Secret</p>
                    </td>
                </tr>
            </table>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>How to Get Google OAuth Credentials</h2>
                <ol>
                    <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select an existing one</li>
                    <li>Enable the Google+ API</li>
                    <li>Go to Credentials → Create Credentials → OAuth Client ID</li>
                    <li>Select "Web application" as the application type</li>
                    <li>Add authorized redirect URI: <code><?php echo home_url('/wp-admin/admin-ajax.php?action=sel_google_callback'); ?></code></li>
                    <li>Copy the Client ID and Client Secret and paste them above</li>
                </ol>
            </div>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
