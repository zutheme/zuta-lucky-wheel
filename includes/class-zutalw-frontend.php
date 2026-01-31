<?php
/**
 * ZUTALW Frontend: Enqueue scripts & Output public UI
 * Optimized Version: Click Trigger + Security Checks + Object Caching
 * FIXED: 403 Forbidden by attaching Config to 'zutalw-trigger' handle
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Frontend {

    protected $cache_group = 'zutalw_spin_limits';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_footer', array( $this, 'auto_display_wheel' ) );
        add_action( 'wp_footer', array( $this, 'loading_markup' ) );
        add_action( 'wp_footer', array( $this, 'popup_notify_markup' ) );

        add_action( 'wp_ajax_zutalw_check_limit', array( $this, 'check_spin_limit_by_ip' ) );
        add_action( 'wp_ajax_nopriv_zutalw_check_limit', array( $this, 'check_spin_limit_by_ip' ) );
    }

    public function enqueue_public_assets() {
        
        // 1. Always load CSS
        wp_enqueue_style( 'zutalw-style', ZUTALW_ASSETS_URL . 'css/rotate_style.css', array(), ZUTALW_VERSION );
        
        // 2. Load Admin Setup (Optional UI helpers)
        wp_enqueue_script( 'zutalw-admin-setup', ZUTALW_ASSETS_URL . 'js/admin-setup.js', array( 'jquery' ), ZUTALW_VERSION, true );

        // 3. Prepare configuration data
        $config_json = class_exists('ZUTALW_Model_ConfigGame') ? ZUTALW_Model_ConfigGame::get_instance_latest() : '';

        // Helper string for versioning
        $ver = '?ver=' . ZUTALW_VERSION;

        // 4. List of heavy library files
        $scripts = array(
            'p5'        => ZUTALW_ASSETS_URL . 'js/p5.min.js' . $ver,
            'p5_dom'    => ZUTALW_ASSETS_URL . 'js/p5.dom.min.js' . $ver,
            'p5_sound'  => ZUTALW_ASSETS_URL . 'js/p5.sound.min.js' . $ver,
            'matter'    => ZUTALW_ASSETS_URL . 'js/matter.js' . $ver,
            'decomp'    => ZUTALW_ASSETS_URL . 'js/decomp.js' . $ver,
            'pathseg'   => ZUTALW_ASSETS_URL . 'js/pathseg.js' . $ver,
            'arcshape'  => ZUTALW_ASSETS_URL . 'js/arcshape.js' . $ver,
            'particle'  => ZUTALW_ASSETS_URL . 'js/particle.js' . $ver,
            'boundary'  => ZUTALW_ASSETS_URL . 'js/boundary.js' . $ver,
            'fingerprint' => ZUTALW_ASSETS_URL . 'js/fingerprint.min.js'. $ver,
            'sketch'    => ZUTALW_ASSETS_URL . 'js/sketch.js' . $ver,
            'custom'    => ZUTALW_ASSETS_URL . 'js/custom_rotate.js' . $ver,
        );

        // 5. Config Data (Nonce/Security Key is here)
        $localize_data = array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'home_url'    => home_url(),
            'assets_url'  => ZUTALW_ASSETS_URL, 
            'plugin_url'  => ZUTALW_URL,
            'mode_admin'  => 'false',
            'getConfig'   => $config_json,
            'nonce'       => wp_create_nonce( 'zutalw-nonce' ), 
            'popup_delay' => (int)get_option( 'zutalw_popup_delay', 0 ) * 1000, 
            'max_spins'   => get_option( 'zutalw_max_spins', 1 ),
            'recaptcha_site_key' => get_option( 'zutalw_recaptcha_site_key', '' ),
            'i18n'        => array(
                'notice_title' => esc_html__( 'Notification', 'zuta-lucky-wheel' ),
                'err_name'     => esc_html__( 'Please enter your full name', 'zuta-lucky-wheel' ),
                'err_phone'    => esc_html__( 'Please enter your phone number', 'zuta-lucky-wheel' ),
                'success'      => esc_html__( 'Congratulations! Information sent successfully!', 'zuta-lucky-wheel' ),
                'error'        => esc_html__( 'Error sending information. Please try again!', 'zuta-lucky-wheel' ),
                'notice'       => esc_html__( 'Notification', 'zuta-lucky-wheel' ), 
                'close'        => esc_html__( 'Close', 'zuta-lucky-wheel' )
            )
        );

        // 6. Enqueue Trigger file
        wp_enqueue_script( 'zutalw-trigger', ZUTALW_ASSETS_URL . 'js/zutalw-trigger.js', array( 'jquery' ), ZUTALW_VERSION, true );
        
        // 7. PASS DATA TO TRIGGER
        wp_localize_script( 'zutalw-trigger', 'ZutalwConfig', $localize_data );

        // 8. Pass script list separately
        wp_localize_script( 'zutalw-trigger', 'zutalw_Lazy_Assets', array(
            'scripts' => $scripts
        ));
       
        if ( ! empty( $localize_data['recaptcha_site_key'] ) ) {
            wp_enqueue_script( 
                'google-recaptcha', 
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $localize_data['recaptcha_site_key'] ), 
                array(), 
                ZUTALW_VERSION, 
                true 
            );
        }
    }

   
    public function auto_display_wheel() {
        if ( is_admin() || current_user_can( 'manage_options' ) ) return;
        $gift_url = ZUTALW_ASSETS_URL . 'images/gift1.png'; 
        echo '<div id="zutalw-gift-trigger" class="zutalw-gift-overlay" style="display:none;"><div class="zutalw-gift-card"><div class="zutalw-card-close">&#10005;</div><div class="zutalw-card-img-wrapper"><img src="' . esc_url($gift_url) . '" class="zutalw-card-img"></div><h3 class="zutalw-card-title">' . esc_html__( 'CONGRATULATIONS!', 'zuta-lucky-wheel' ) . '</h3><p class="zutalw-card-desc"><br><span class="highlight"><span id="zutalw-spins-left">1</span> ' . esc_html__( 'FREE SPINS', 'zuta-lucky-wheel' ) . '</span></p><a href="#zutalw_lucky_spin=0" class="zutalw-card-btn">' . esc_html__( 'SPIN NOW', 'zuta-lucky-wheel' ) . '</a></div></div>'; 
        echo '<div id="zutalw-popup-wrapper" style="display:none;"><div class="zutalw-game-card"><div class="zutalw-close-game">&#10005;</div><h3 class="zutalw-game-title">' . esc_html( get_option( 'zutalw_game_title', 'Zuta Lucky Wheel' ) ) . '</h3><div class="zutalw-wheel-pointer"></div><div id="area-game" data-id="0" data-license="0"></div></div></div>';
    }

    public function loading_markup() {
        echo '<div id="zutalw-loading" class="zutalw-popup-processing" style="display:none;position:fixed;z-index:9999999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);"><div class="processing" style="position:relative;background:#fff;border-radius:10px;width:200px;height:200px;margin:20% auto;text-align:center;"><img class="loading" src="' . esc_url( ZUTALW_ASSETS_URL . 'images/spinner.gif' ) . '" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:80px;height:80px;"></div></div>';
    }

    public function popup_notify_markup() {
        echo '<div id="zutalw-popup-notify" class="zutalw-popup-notify" style="display:none;"></div>';
    }

    public function check_spin_limit_by_ip() {
        check_ajax_referer( 'zutalw-nonce', 'security' );
        $device_id = isset($_POST['device_id']) ? sanitize_text_field( wp_unslash( $_POST['device_id'] ) ) : '';
        if ( empty($device_id) || $device_id === 'unknown' ) { wp_send_json_error( array( 'message' => 'Identity error.' ) ); }
        
        global $wpdb;
        $table = $wpdb->prefix . 'zutalw_customers'; 
        
        // [FIX] Combine everything into one line to ensure phpcs:ignore works for both interpolation and direct query.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT spin_limit FROM $table WHERE device_id = %s LIMIT 1", $device_id ) );
        
        $limit = $row ? (int)$row->spin_limit : (int)get_option( 'zutalw_max_spins', 1 );
        
        if (!$row) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->insert( $table, array( 'device_id' => $device_id, 'spin_limit' => $limit, 'fullname' => 'Guest' ), array( '%s', '%d', '%s' ) );
        }
        
        if ( $limit <= 0 ) { wp_send_json_error( array( 'message' => 'Max limit reached!', 'spins_left' => 0 ) ); }
        else { wp_send_json_success( array( 'spins_left' => $limit ) ); }
    }
}