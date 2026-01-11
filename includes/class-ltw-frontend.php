<?php
/**
 * LTW Frontend: enqueue scripts & output public UI
 * Optimized Version: Click Trigger + Security Checks
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_footer', array( $this, 'auto_display_wheel' ) );
        add_action( 'wp_footer', array( $this, 'loading_markup' ) );
        add_action( 'wp_footer', array( $this, 'popup_notify_markup' ) );

        // --- QUAN TRỌNG: ĐĂNG KÝ AJAX ACTION (Bị thiếu trước đó) ---
        add_action( 'wp_ajax_ltw_check_limit', array( $this, 'check_spin_limit_by_ip' ) );
        add_action( 'wp_ajax_nopriv_ltw_check_limit', array( $this, 'check_spin_limit_by_ip' ) );
    }

    /**
     * 1. ENQUEUE ASSETS
     */
    public function enqueue_public_assets() {
        
        // 1. Always load CSS
        wp_enqueue_style( 'ltw-style', LTW_ASSETS_URL . 'css/rotate_style.css', array(), LTW_PLUGIN_VERSION );
        wp_enqueue_script( 'admin-setup', LTW_ASSETS_URL . 'js/admin-setup.js', array( 'jquery' ), LTW_PLUGIN_VERSION, true );

        // 2. Prepare configuration data
        $config_json = LTW_Model_ConfigGame::get_instance_latest();

        // Helper string for versioning
        $ver = '?ver=' . LTW_PLUGIN_VERSION;

        // 3. List of heavy library files
        $scripts = array(
            'p5'        => LTW_ASSETS_URL . 'js/p5.min.js' . $ver,
            'p5_dom'    => LTW_ASSETS_URL . 'js/p5.dom.min.js' . $ver,
            'p5_sound'  => LTW_ASSETS_URL . 'js/p5.sound.min.js' . $ver,
            'matter'    => LTW_PLUGIN_URL . 'build/matter.js' . $ver,
            'decomp'    => LTW_ASSETS_URL . 'js/decomp.js' . $ver,
            'pathseg'   => LTW_ASSETS_URL . 'js/pathseg.js' . $ver,
            'arcshape'  => LTW_ASSETS_URL . 'js/arcshape.js' . $ver,
            'particle'  => LTW_ASSETS_URL . 'js/particle.js' . $ver,
            'boundary'  => LTW_ASSETS_URL . 'js/boundary.js' . $ver,
            
            // FingerprintJS CDN
            'fingerprint' => LTW_ASSETS_URL . 'js/iife.min.js'. $ver,

            'sketch'    => LTW_ASSETS_URL . 'js/sketch.js' . $ver,
            'custom'    => LTW_ASSETS_URL . 'js/custom_rotate.js' . $ver,
        );

        // Get options
        $delay_seconds = get_option( 'ltw_popup_delay', 0 );
        $max_spins_opt = get_option( 'ltw_max_spins', 1 );
        $recaptcha_site_key = get_option( 'ltw_recaptcha_site_key', '' );
        
        // 4. Localize data to pass to JS
        $localize_data = array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'home_url'    => home_url(),
            'assets_url'  => LTW_ASSETS_URL,
            'plugin_url'  => LTW_PLUGIN_URL,
            'mode_admin'  => 'false',
            'getConfig'   => $config_json,
            'nonce'       => wp_create_nonce( 'lucky-wheel-nonce' ),
            'popup_delay' => $delay_seconds * 1000, 
            'max_spins'   => $max_spins_opt,
            'recaptcha_site_key' => $recaptcha_site_key,
            
            'i18n'        => array(
                'notice_title' => esc_html__( 'Notification', 'lucky-the-wheel' ),
                'err_name'     => esc_html__( 'Please enter your full name', 'lucky-the-wheel' ),
                'err_phone'    => esc_html__( 'Please enter your phone number', 'lucky-the-wheel' ),
                'success'      => esc_html__( 'Congratulations! Information sent successfully!', 'lucky-the-wheel' ),
                'error'        => esc_html__( 'Error sending information. Please try again!', 'lucky-the-wheel' ),
                'notice'       => esc_html__( 'Notification', 'lucky-the-wheel' ), 
                'close'        => esc_html__( 'Close', 'lucky-the-wheel' )
            )
        );

        // 5. Enqueue Trigger file
        wp_enqueue_script( 'ltw-trigger', LTW_ASSETS_URL . 'js/ltw-trigger.js', array( 'jquery' ), LTW_PLUGIN_VERSION, true );
       
        
        // Enqueue Google reCAPTCHA v3 API (Only if key exists)
        if ( ! empty( $recaptcha_site_key ) ) {
            wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), array(), null, true );
        }

        // 6. Pass data to JS
        wp_localize_script( 'ltw-trigger', 'LTW_Lazy_Assets', array(
            'scripts'      => $scripts,
            'config_data'  => $localize_data
        ));
    }

   /**
     * 2. AUTO DISPLAY (POPUP WRAPPER & GIFT TRIGGER)
     * Updated: Professional Card Style
     */
    public function auto_display_wheel() {
        // 1. Don't show in the WordPress Dashboard
        // 2. Hide the wheel entirely if the current user is an Administrator
        if ( is_admin() || current_user_can( 'manage_options' ) ) {
            return;
        }

        // Đảm bảo đường dẫn ảnh chính xác
        $gift_url = LTW_ASSETS_URL . 'images/gift1.png'; 
        
        // --- GIFT TRIGGER CONTAINER (OVERLAY) ---
        echo '<div id="ltw-gift-trigger" class="ltw-gift-overlay" style="display:none;">';
        
            // --- THE WHITE CARD ---
            echo '<div class="ltw-gift-card">';
            
                // Close Button (Inside the card)
                echo '<div class="ltw-card-close">&#10005;</div>'; 

                // 1. Floating Gift Image
                echo '<div class="ltw-card-img-wrapper">';
                echo '  <img src="' . esc_url($gift_url) . '" class="ltw-card-img" alt="Gift Box">';
                echo '</div>';

                // 2. Title
                echo '<h3 class="ltw-card-title">' . esc_html__( 'CONGRATULATIONS!', 'lucky-the-wheel' ) . '</h3>';

                // 3. Description (Dynamic Spins)
                echo '<p class="ltw-card-desc">';
                echo esc_html__( 'You are the lucky one to get', 'lucky-the-wheel' );
                echo '<br><span class="highlight"><span id="ltw-spins-left">1</span> ' . esc_html__( 'FREE SPINS', 'lucky-the-wheel' ) . '</span>';
                echo ' ' . esc_html__( 'today!', 'lucky-the-wheel' );
                echo '</p>';

                // 4. CTA Button (Triggers the wheel via hash)
                echo '<a href="#lucky_spin_license=0" class="ltw-card-btn">';
                echo    esc_html__( 'SPIN NOW', 'lucky-the-wheel' );
                echo '</a>';
                
                // 5. Small Note (Optional)
                echo '<p class="ltw-card-note">' . esc_html__( 'No thanks, I hate winning prizes', 'lucky-the-wheel' ) . '</p>';

            echo '</div>'; // End .ltw-gift-card

        echo '</div>'; // End #ltw-gift-trigger

       // --- GAME POPUP WRAPPER ---
        echo '<div id="ltw-popup-wrapper" style="display:none;">';
            
            // Container bao bọc vòng quay (Cái thẻ trắng)
            echo '<div class="ltw-game-card">';
                
                // Nút đóng (Di chuyển vào trong thẻ)
                echo '<div class="ltw-close-game">&#10005;</div>';
                
                // Tiêu đề game
                $game_title = get_option( 'ltw_game_title', 'Zuta Lucky Wheel' ); 
                
                echo '<h3 class="ltw-game-title">' . esc_html( $game_title ) . '</h3>';
                
                // Mũi tên chỉ định (Ticker) - Quan trọng để biết trúng ô nào
                echo '<div class="ltw-wheel-pointer"></div>';

                // Vòng quay (Canvas)
                echo '<div id="area-game" data-id="0" data-license="0"></div>';

            echo '</div>'; // End .ltw-game-card

        echo '</div>';
    }

    /**
     * 3. LOADING MARKUP
     */
    public function loading_markup() {
        ?>
        <div id="ltw-loading" class="ltw-popup-processing" style="display:none;position:fixed;z-index:9999999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);">
            <div class="processing" style="position:relative;background:#fff;border-radius:10px;width:200px;height:200px;margin:20% auto;text-align:center;box-shadow:0 0 20px rgba(0,0,0,0.3);">
                <img class="loading" src="<?php echo esc_url( LTW_ASSETS_URL . 'images/spinner.gif' ); ?>" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:80px;height:80px;">
                <p class="result" style="display:none;font-weight:bold;font-size:18px;margin-top:20px;"></p>
            </div>
        </div>
        <?php
    }

    /**
     * 4. NOTIFY POPUP MARKUP
     */
    public function popup_notify_markup() {
        ?>
        <div id="ltw-popup-notify" class="ltw-popup-notify" style="display:none;">
            <div class="ltw-form">
                <form class="form-input">
                    <button type="button" class="btn-close" onclick="close_notify(this)">x</button>
                    <div class="form-group head-congrate">
                        <h4 class="notice"><?php echo esc_html__( 'Notification', 'lucky-the-wheel' ); ?></h4>
                    </div>
                    <div class="form-content" style="padding:15px;text-align:center;">
                        </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 5. REGISTER AJAX FOR LIMIT CHECK
     * Includes: reCAPTCHA v3 Check + Fingerprint ID + IP Limit
     */
    /**
     * Checks spin limit by prioritizing Database records.
     * If a new device is detected, it initializes a record in the database.
     */
    public function check_spin_limit_by_ip() {
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );

        $device_id = isset($_POST['device_id']) ? sanitize_text_field($_POST['device_id']) : '';
        if ( empty($device_id) || $device_id === 'unknown' ) {
            wp_send_json_error( array( 'message' => 'Identity could not be verified.' ) );
        }

        global $wpdb;
        $table_customers = $wpdb->prefix . 'customers';
        $max_spins = (int) get_option( 'ltw_max_spins', 1 );

        // 1. Try to find the user in the Database
        $customer = $wpdb->get_row( $wpdb->prepare(
            "SELECT idcustomer, spin_limit FROM $table_customers WHERE device_id = %s LIMIT 1",
            $device_id
        ));

        if ( ! $customer ) {
            // 2. New user detected: Create a permanent record with default spins
            $wpdb->insert(
                $table_customers,
                array(
                    'device_id'  => $device_id,
                    'spin_limit' => $max_spins,
                    'fullname'   => 'Guest',
                ),
                array( '%s', '%d', '%s' )
            );
            $spins_left = $max_spins;
        } else {
            // 3. Existing user: Use the limit from the DB
            $spins_left = (int) $customer->spin_limit;
        }

        // 4. Return result
        if ( $spins_left <= 0 ) {
            wp_send_json_error( array( 
                'message'    => esc_html__( 'You have reached the maximum limit!', 'lucky-the-wheel' ),
                'spins_left' => 0 
            ) );
        } else {
            wp_send_json_success( array( 'spins_left' => $spins_left ) );
        }
    }
}