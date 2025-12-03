<?php
/**
 * Plugin Name: Login with Google
 * Plugin URI: https://yourwebsite.com
 * Description: Simple email-based login system for WordPress. Users can login with just their email address.
 * Version: 1.0.0
 * Author: Mushfikur Rahman
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: login-with-google
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LWG_VERSION', '1.0.0');
define('LWG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LWG_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Activation Hook
 */
function lwg_activate() {
    // Add any activation tasks here
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'lwg_activate');

/**
 * Deactivation Hook
 */
function lwg_deactivate() {
    // Add any deactivation tasks here
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'lwg_deactivate');

/**
 * Enqueue styles and scripts
 */
function lwg_enqueue_scripts() {
    wp_enqueue_style('lwg-styles', LWG_PLUGIN_URL . 'assets/css/style.css', array(), LWG_VERSION);
    wp_enqueue_script('lwg-script', LWG_PLUGIN_URL . 'assets/js/script.js', array('jquery'), LWG_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('lwg-script', 'lwg_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lwg_login_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'lwg_enqueue_scripts');

/**
 * Handle AJAX Login Request
 */
function lwg_handle_login() {
    // Verify nonce
    check_ajax_referer('lwg_login_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
    }
    
    // Check if user exists
    $user = get_user_by('email', $email);
    
    // If user doesn't exist, create new user
    if (!$user) {
        $username = sanitize_user(current(explode('@', $email)));
        $random_password = wp_generate_password(12, true, true);
        
        // Make username unique if it already exists
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, $random_password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Failed to create user account.'));
        }
        
        // Set display name as email username part
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => ucfirst($base_username)
        ));
        
        $user = get_user_by('id', $user_id);
    }
    
    // Log the user in
    wp_clear_auth_cookie();
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    
    wp_send_json_success(array(
        'message' => 'Login successful!',
        'user_name' => $user->display_name
    ));
}
add_action('wp_ajax_nopriv_lwg_login', 'lwg_handle_login');
add_action('wp_ajax_lwg_login', 'lwg_handle_login');

/**
 * Shortcode to display login form or user name
 */
function lwg_login_shortcode() {
    ob_start();
    
    if (is_user_logged_in()) {
        // User is logged in - show their name
        $current_user = wp_get_current_user();
        ?>
        <div class="lwg-user-info">
            <span class="lwg-welcome-message">Welcome, <?php echo esc_html($current_user->display_name); ?></span>
        </div>
        <?php
    } else {
        // User is not logged in - show login form
        ?>
        <div class="lwg-login-container">
            <div class="lwg-login-form">
                <h2 class="lwg-title">I ALREADY HAVE AN ACCOUNT</h2>
                <form id="lwg-email-login-form">
                    <div class="lwg-form-group">
                        <label for="lwg-email">Please enter your phone number or email</label>
                        <input type="email" id="lwg-email" name="email" required placeholder="">
                    </div>
                    <div class="lwg-forgot-password">
                        <a href="<?php echo wp_lostpassword_url(); ?>">Forgot password?</a>
                    </div>
                    <div class="lwg-button-group">
                        <button type="button" class="lwg-btn lwg-btn-secondary">Create Account</button>
                        <button type="submit" class="lwg-btn lwg-btn-primary">Next</button>
                    </div>
                    <div class="lwg-divider">
                        <span>OR SIGN IN WITH</span>
                    </div>
                    <button type="button" class="lwg-btn lwg-btn-google">
                        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                            <path d="M3.964 10.71c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.958H.957C.347 6.173 0 7.548 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.426 0 9.003 0 5.482 0 2.438 2.017.957 4.958L3.964 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                        </svg>
                        GOOGLE
                    </button>
                </form>
                <div class="lwg-message"></div>
            </div>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('login_with_google', 'lwg_login_shortcode');
