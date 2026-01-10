<?php
/**
 * Plugin Name:       Zuta Lucky Wheel – Spin to Win & Lead Generation
 * Plugin URI:        https://github.com/zutheme/zuta-lucky-wheel
 * Description:       Boost marketing engagement with a physics-based Lucky Wheel. Create interactive spin-to-win campaigns to collect leads, offer rewards, and increase conversions.
 * Version:           1.5.1.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            zutatheme
 * Author URI:        https://github.com/zutheme
 * Text Domain:       zuta-lucky-wheel
 * Domain Path:       /languages
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * CONSTANTS
 * ------------------------------------------------------------------------- */
/* Safe constants */
if ( ! defined( 'LTW_PLUGIN_VERSION' ) ) {
    // Nếu đang bật chế độ Debug (Dev), dùng time() để cache busting
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        define( 'LTW_PLUGIN_VERSION', time() ); 
    } else {
        // Nếu là bản chạy thật (Production), dùng version cứng
        define( 'LTW_PLUGIN_VERSION', '1.5.1.2' );
    }
}

if ( ! defined( 'LTW_PLUGIN_DIR' ) ) {
    define( 'LTW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'LTW_PLUGIN_URL' ) ) {
    define( 'LTW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'LTW_ASSETS_URL' ) ) {
    define( 'LTW_ASSETS_URL', LTW_PLUGIN_URL . 'assets/' );
}

/* -------------------------------------------------------------------------
 * LOAD TEXT DOMAIN
 * ------------------------------------------------------------------------- */
function ltw_load_textdomain() {
    load_plugin_textdomain( 'lucky-the-wheel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ltw_load_textdomain' );

/* -------------------------------------------------------------------------
 * ACTIVATION HOOK
 * Runs when the user clicks "Activate" plugin
 * ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'ltw_run_activation_hook' );

function ltw_run_activation_hook() {
    // Ensure the database class file is loaded
    if ( ! class_exists( 'LTW_Database' ) ) {
        require_once LTW_PLUGIN_DIR . 'includes/class-ltw-database.php';
    }

    // Initialize the class and run table creation methods
    $db = new LTW_Database();
    
    // Create config table (wp_configgames)
    $db->create_config();
    
    // Create customer table (wp_customers)
    $db->create_customer();
}

/* -------------------------------------------------------------------------
 * REQUIRE INCLUDES
 * ------------------------------------------------------------------------- */
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-core.php';
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-admin.php';
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-frontend.php';
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-ajax.php';
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-database.php';
require_once LTW_PLUGIN_DIR . 'includes/class-ltw-shortcode.php';
require_once LTW_PLUGIN_DIR . 'includes/models/model-customer.php';
require_once LTW_PLUGIN_DIR . 'includes/admin/admin-customers.php';

// KHỞI TẠO CLASS SỚM ĐỂ BẮT HOOK EXPORT
function ltw_init_admin_features() {
    // Chỉ khởi tạo trong trang admin để tối ưu
    if ( is_admin() ) {
        // Gọi class này ngay để nó đăng ký hook 'admin_init' -> 'handle_csv_export'
        new LTW_Admin_Customers(); 
    }
}
// Dùng hook plugins_loaded để đảm bảo mọi file đã được include
add_action( 'plugins_loaded', 'ltw_init_admin_features' );
/* -------------------------------------------------------------------------
 * INITIALIZE
 * ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'LTW_Core' ) ) {
        LTW_Core::get_instance();
    }
} );