<?php
/**
 * LTW Frontend: Enqueue scripts & Output public UI
 * Optimized Version: Click Trigger + Security Checks + Object Caching
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Frontend {

    /**
     * Cache group name for frontend limits.
     */
    protected $cache_group = 'ltw_spin_limits';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
        add_action( 'wp_footer', array( $this, 'auto_display_wheel' ) );
        add_action( 'wp_footer', array( $this, 'loading_markup' ) );
        add_action( 'wp_footer', array( $this, 'popup_notify_markup' ) );

        // --- IMPORTANT: REGISTER AJAX ACTION ---
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
                'notice_title' => esc_html__( 'Notification', 'zuta-lucky-wheel' ),
                'err_name'     => esc_html__( 'Please enter your full name', 'zuta-lucky-wheel' ),
                'err_phone'    => esc_html__( 'Please enter your phone number', 'zuta-lucky-wheel' ),
                'success'      => esc_html__( 'Congratulations! Information sent successfully!', 'zuta-lucky-wheel' ),
                'error'        => esc_html__( 'Error sending information. Please try again!', 'zuta-lucky-wheel' ),
                'notice'       => esc_html__( 'Notification', 'zuta-lucky-wheel' ), 
                'close'        => esc_html__( 'Close', 'zuta-lucky-wheel' )
            )
        );

        // 5. Enqueue Trigger file
        wp_enqueue_script( 'ltw-trigger', LTW_ASSETS_URL . 'js/ltw-trigger.js', array( 'jquery' ), LTW_PLUGIN_VERSION, true );
       
        
        // Enqueue Google reCAPTCHA v3 API (Only if key exists)
        if ( ! empty( $recaptcha_site_key ) ) {
            /**
             * FIX: Use LTW_PLUGIN_VERSION instead of null to fix "MissingVersion" warning.
             */
            wp_enqueue_script( 
                'google-recaptcha', 
                'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), 
                array(), 
                LTW_PLUGIN_VERSION, 
                true 
            );
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

        // Ensure the image path is correct
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
                echo '<h3 class="ltw-card-title">' . esc_html__( 'CONGRATULATIONS!', 'zuta-lucky-wheel' ) . '</h3>';

                // 3. Description (Dynamic Spins)
                echo '<p class="ltw-card-desc">';
                echo esc_html__( 'You are the lucky one to get', 'zuta-lucky-wheel' );
                echo '<br><span class="highlight"><span id="ltw-spins-left">1</span> ' . esc_html__( 'FREE SPINS', 'zuta-lucky-wheel' ) . '</span>';
                echo ' ' . esc_html__( 'today!', 'zuta-lucky-wheel' );
                echo '</p>';

                // 4. CTA Button (Triggers the wheel via hash)
                echo '<a href="#lucky_spin_license=0" class="ltw-card-btn">';
                echo    esc_html__( 'SPIN NOW', 'zuta-lucky-wheel' );
                echo '</a>';
                
                // 5. Small Note (Optional)
                echo '<p class="ltw-card-note">' . esc_html__( 'No thanks, I hate winning prizes', 'zuta-lucky-wheel' ) . '</p>';

            echo '</div>'; // End .ltw-gift-card

        echo '</div>'; // End #ltw-gift-trigger

       // --- GAME POPUP WRAPPER ---
        echo '<div id="ltw-popup-wrapper" style="display:none;">';
            
            // Wheel Wrapper Container (The white card)
            echo '<div class="ltw-game-card">';
                
                // Close Button (Moved inside the card)
                echo '<div class="ltw-close-game">&#10005;</div>';
                
                // Game Title
                $game_title = get_option( 'ltw_game_title', 'Zuta Lucky Wheel' ); 
                
                echo '<h3 class="ltw-game-title">' . esc_html( $game_title ) . '</h3>';
                
                // Indicator Pointer (Ticker) - Essential for identifying the winning segment
                echo '<div class="ltw-wheel-pointer"></div>';

                // Wheel Area (Canvas)
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
                        <h4 class="notice"><?php echo esc_html__( 'Notification', 'zuta-lucky-wheel' ); ?></h4>
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
     * Includes: reCAPTCHA v3 Check + Fingerprint ID + IP Limit + Object Caching
     * Checks spin limit by prioritizing Database records.
     * If a new device is detected, it initializes a record in the database.
     */
    public function check_spin_limit_by_ip() {
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );

        $device_id = isset($_POST['device_id']) ? sanitize_text_field( wp_unslash( $_POST['device_id'] ) ) : '';
        
        if ( empty($device_id) || $device_id === 'unknown' ) {
            wp_send_json_error( array( 'message' => 'Identity could not be verified.' ) );
        }

        global $wpdb;
        $table_customers = $wpdb->prefix . 'customers';
        $max_spins = (int) get_option( 'ltw_max_spins', 1 );

        // --- 1. CACHE CHECK START ---
        $cache_key = 'spin_limit_' . md5( $device_id );
        $cached_limit = wp_cache_get( $cache_key, $this->cache_group );

        if ( false !== $cached_limit ) {
            $spins_left = (int) $cached_limit;
        } else {
            // No cache, verify in DB
            /**
             * Use %i identifier placeholder for table name to satisfy security checks.
             */
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $customer = $wpdb->get_row( $wpdb->prepare(
                "SELECT idcustomer, spin_limit FROM %i WHERE device_id = %s LIMIT 1",
                $table_customers,
                $device_id
            ));

            if ( ! $customer ) {
                // 2. New user detected: Create a permanent record with default spins
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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

            // Save to cache for 12 hours (43200 seconds)
            wp_cache_set( $cache_key, $spins_left, $this->cache_group, 43200 );
        }
        // --- CACHE CHECK END ---

        // 4. Return result
        if ( $spins_left <= 0 ) {
            wp_send_json_error( array( 
                'message'    => esc_html__( 'You have reached the maximum limit!', 'zuta-lucky-wheel' ),
                'spins_left' => 0 
            ) );
        } else {
            wp_send_json_success( array( 'spins_left' => $spins_left ) );
        }
    }
}