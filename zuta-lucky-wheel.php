<?php
/**
 * Plugin Name:       Zuta Lucky Wheel
 * Plugin URI:        https://github.com/zutheme/zuta-lucky-wheel
 * Description:       Boost marketing engagement with a physics-based Lucky Wheel. Create interactive spin-to-win campaigns to collect leads, offer rewards, and increase conversions.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            zutatheme
 * Author URI:        https://github.com/zutheme
 * Text Domain:       zuta-lucky-wheel
 * Domain Path:       /languages
 * License:           GPLv2 or later
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------------------------------------
 * CONSTANTS
 * ------------------------------------------------------------------------- */
if ( ! defined( 'LTW_PLUGIN_VERSION' ) ) {
    // If WP_DEBUG is enabled, use time() for cache busting during development
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        define( 'LTW_PLUGIN_VERSION', time() ); 
    } else {
        // Use static version for production releases
        define( 'LTW_PLUGIN_VERSION', '1.0.0' );
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
 * ACTIVATION HOOK
 * ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'ltw_run_activation_hook' );

/**
 * Executes plugin activation logic, such as creating database tables.
 */
function ltw_run_activation_hook() {
    // Ensure the database class file is loaded for table creation
    if ( ! class_exists( 'LTW_Database' ) ) {
        require_once LTW_PLUGIN_DIR . 'includes/class-ltw-database.php';
    }

    $db = new LTW_Database();
    
    // Create necessary database tables
    $db->create_config();   // Table: wp_configgames
    $db->create_customer(); // Table: wp_customers
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
require_once LTW_PLUGIN_DIR . 'includes/models/model-configgame.php'; // Ensure config model is included
require_once LTW_PLUGIN_DIR . 'includes/admin/admin-customers.php';

/* -------------------------------------------------------------------------
 * INITIALIZE ADMIN FEATURES
 * ------------------------------------------------------------------------- */
/**
 * Initializes administrative features such as customer management.
 */
function ltw_init_admin_features() {
    if ( is_admin() ) {
        new LTW_Admin_Customers(); 
    }
}
add_action( 'plugins_loaded', 'ltw_init_admin_features' );

/* -------------------------------------------------------------------------
 * INITIALIZE CORE INSTANCE
 * ------------------------------------------------------------------------- */
/**
 * Initializes the main plugin core instance.
 */
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'LTW_Core' ) ) {
        LTW_Core::get_instance();
    }
} );