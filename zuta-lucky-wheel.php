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
if ( ! defined( 'ZUTALW_VERSION' ) ) {
    // If WP_DEBUG is enabled, use time() for cache busting during development
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        define( 'ZUTALW_VERSION', time() ); 
    } else {
        // Use static version for production releases
        define( 'ZUTALW_VERSION', '1.0.0' );
    }
}

if ( ! defined( 'ZUTALW_DIR' ) ) {
    define( 'ZUTALW_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ZUTALW_URL' ) ) {
    define( 'ZUTALW_URL', plugin_dir_url( __FILE__ ) );
}

// FIX: Added 'S' to ASSETS to match usage in other files
if ( ! defined( 'ZUTALW_ASSETS_URL' ) ) {
    define( 'ZUTALW_ASSETS_URL', ZUTALW_URL . 'assets/' );
}

/* -------------------------------------------------------------------------
 * ACTIVATION HOOK
 * ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'zutalw_run_activation_hook' );

/**
 * Executes plugin activation logic, such as creating database tables.
 */
function zutalw_run_activation_hook() {
    // Ensure the database class file is loaded for table creation
    if ( ! class_exists( 'ZUTALW_Database' ) ) {
        require_once ZUTALW_DIR . 'includes/class-zutalw-database.php';
    }

    $db = new ZUTALW_Database();
    
    // Create necessary database tables
    // Check method existence to avoid fatal errors during updates
    if ( method_exists( $db, 'create_config' ) ) {
        $db->create_config();   // Table: wp_configgames
    }
    
    if ( method_exists( $db, 'create_customer' ) ) {
        $db->create_customer(); // Table: wp_customers
    }
}

/* -------------------------------------------------------------------------
 * REQUIRE INCLUDES
 * ------------------------------------------------------------------------- */
require_once ZUTALW_DIR . 'includes/class-zutalw-core.php';
require_once ZUTALW_DIR . 'includes/class-zutalw-admin.php';
require_once ZUTALW_DIR . 'includes/class-zutalw-frontend.php';
require_once ZUTALW_DIR . 'includes/class-zutalw-ajax.php';
require_once ZUTALW_DIR . 'includes/class-zutalw-database.php';
require_once ZUTALW_DIR . 'includes/class-zutalw-shortcode.php';
require_once ZUTALW_DIR . 'includes/models/model-zutalw-customer.php';
require_once ZUTALW_DIR . 'includes/models/model-zutalw-configgame.php'; 
require_once ZUTALW_DIR . 'includes/admin/class-zutalw-admin-customers.php';

/* -------------------------------------------------------------------------
 * INITIALIZE ADMIN FEATURES
 * ------------------------------------------------------------------------- */
/**
 * Initializes administrative features such as customer management.
 */
function zutalw_init_admin_features() {
    if ( is_admin() ) {
        if ( class_exists( 'ZUTALW_Admin_Customers' ) ) {
            new ZUTALW_Admin_Customers(); 
        }
    }
}
add_action( 'plugins_loaded', 'zutalw_init_admin_features' );

/* -------------------------------------------------------------------------
 * INITIALIZE CORE INSTANCE
 * ------------------------------------------------------------------------- */
/**
 * Initializes the main plugin core instance.
 */
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'ZUTALW_Core' ) ) {
        ZUTALW_Core::get_instance();
    }
} );