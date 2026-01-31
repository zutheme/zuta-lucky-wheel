<?php
/**
 * zutalw AJAX handlers
 * Handles all background requests for the game logic, configuration, and data submission.
 * FIXED: Sanitization, Security compliance, and Class validation.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Ajax {

    /**
     * Constructor: Register all AJAX hooks.
     */
    public function __construct() {
        // All actions use 'zutalw_' prefix for consistency and to avoid conflicts
        add_action( 'wp_ajax_zutalw_getdataConfig', array( $this, 'ajax_getdataConfig' ) );
        add_action( 'wp_ajax_nopriv_zutalw_getdataConfig', array( $this, 'ajax_getdataConfig' ) );

        add_action( 'wp_ajax_zutalw_InsCustomer', array( $this, 'ajax_insertCustomer' ) );
        add_action( 'wp_ajax_nopriv_zutalw_InsCustomer', array( $this, 'ajax_insertCustomer' ) );

        add_action( 'wp_ajax_zutalw_popup', array( $this, 'ajax_popup' ) );
        add_action( 'wp_ajax_nopriv_zutalw_popup', array( $this, 'ajax_popup' ) );

        // Admin action to save configuration
        add_action( 'wp_ajax_zutalw_UpdateConfig', array( $this, 'ajax_updateConfig' ) );

        add_action( 'wp_ajax_zutalw_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
        add_action( 'wp_ajax_nopriv_zutalw_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
    }

    /**
     * Helper: Recursive Sanitization for Array/Object
     * Addresses: "Data Must be Sanitized, Escaped, and Validated"
     */
    private function sanitize_recursive( $data ) {
        if ( is_array( $data ) ) {
            foreach ( $data as $key => $value ) {
                $data[ $key ] = $this->sanitize_recursive( $value );
            }
            return $data;
        } elseif ( is_string( $data ) ) {
            return sanitize_text_field( $data ); 
        } elseif ( is_int( $data ) ) {
            return intval( $data );
        } elseif ( is_float( $data ) ) {
            return floatval( $data );
        } elseif ( is_bool( $data ) ) {
            return (bool) $data;
        }
        return $data;
    }

    /**
     * Retrieves the latest configuration data with Object Caching.
     */
    public function ajax_getdataConfig() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zutalw_configgame'; 
        $license = 0;
        
        $cache_group = 'zutalw_config_ajax';
        $cache_key   = 'config_row_' . intval( $license );
        $row         = wp_cache_get( $cache_key, $cache_group );

        if ( false === $row ) {
            // [FIX] Keep on ONE LINE to ensure phpcs:ignore applies to the interpolated variable
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE license = %d ORDER BY idconfiggame DESC LIMIT 1", $license ), ARRAY_A );
            
            if ( $row ) {
                wp_cache_set( $cache_key, $row, $cache_group, 3600 );
            }
        }

        if ( ! $row ) {
            $default = class_exists('ZUTALW_Model_ConfigGame') ? ZUTALW_Model_ConfigGame::default_json() : '';
            $row = [ 'dataconfig' => $default ];
        }

        wp_send_json( [ $row ] );
    }

    /**
     * Securely calculates the winner and deducts a spin.
     */
    public function ajax_get_spin_result() {
        check_ajax_referer( 'zutalw-nonce', 'security' );

        $device_id = isset($_POST['device_id']) ? sanitize_text_field( wp_unslash( $_POST['device_id'] ) ) : '';
        
        // Admin Preview bypass
        if ( $device_id === 'admin_preview' && current_user_can('manage_options') ) {
            wp_send_json_success( array(
                'segment_id' => wp_rand(0, 7),
                'label'      => 'Admin Test',
                'spins_left' => 9999
            ));
            return;
        }

        if ( empty( $device_id ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Identity missing.', 'zuta-lucky-wheel' ) ) );
        }

        global $wpdb;
        $table_customers = $wpdb->prefix . 'zutalw_customers';

        if ( ! class_exists( 'ZUTALW_Model_ConfigGame' ) ) {
            wp_send_json_error( 'Critical: Config Model missing.' );
        }

        $model_config = new ZUTALW_Model_ConfigGame();
        $config       = json_decode( $model_config->get_latest_config(), true );
        $winner       = ZUTALW_Core::get_winning_result( $config );

        if ( ! $winner ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Reward calculation failed.', 'zuta-lucky-wheel' ) ) );
        }

        // [FIX] Keep on ONE LINE for effective ignore
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query( $wpdb->prepare( "UPDATE $table_customers SET spin_limit = spin_limit - 1 WHERE device_id = %s AND spin_limit > 0", $device_id ) );

        wp_cache_delete( 'spin_limit_' . md5( $device_id ), 'zutalw_spin_limits' );

        // [FIX] Keep on ONE LINE for effective ignore
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $new_spins_left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT spin_limit FROM $table_customers WHERE device_id = %s", $device_id ) );

        wp_send_json_success( array(
            'segment_id' => $winner['original_index'],
            'label'      => $winner['label'],
            'spins_left' => $new_spins_left
        ));
    }

    /**
     * Stores customer contact info into the database.
     */
    public function ajax_insertCustomer() {
        check_ajax_referer( 'zutalw-nonce', 'security' );
        
        if ( current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Admin cannot participate.' );
        }

        $fullname = isset( $_POST['fullname'] ) ? sanitize_text_field( wp_unslash( $_POST['fullname'] ) ) : '';
        $phone    = isset( $_POST['phone'] )    ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $email    = isset( $_POST['email'] )    ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $license  = isset( $_POST['license'] )  ? intval( $_POST['license'] ) : 0;
        $getgift  = isset( $_POST['getgift'] )  ? sanitize_text_field( wp_unslash( $_POST['getgift'] ) ) : '';

        if ( empty( $fullname ) || empty( $phone ) ) {
            wp_send_json_error( 'invalid_input' );
        }

        $sanitized_input = array(
            'fullname' => $fullname,
            'phone'    => $phone,
            'email'    => $email,
            'license'  => $license,
            'getgift'  => $getgift
        );
        
        if ( class_exists( 'ZUTALW_Model_Customer' ) ) {
            $model = new ZUTALW_Model_Customer();
            $id = $model->insert_customer( $sanitized_input );
            
            if ( is_wp_error( $id ) ) {
                wp_send_json_error( $id->get_error_message() );
            }
            wp_send_json_success( array( 'idcustomer' => $id ) );
        }
        
        wp_send_json_error( 'Customer Model missing.' );
    }

    /**
     * Renders the Congratulations popup template.
     */
    public function ajax_popup() {
        if ( current_user_can( 'manage_options' ) ) {
            // Optional: Block admin logic if needed
        }
        check_ajax_referer( 'zutalw-nonce', 'security' );
        
        $gif = isset( $_POST['name_gif'] ) ? sanitize_text_field( wp_unslash( $_POST['name_gif'] ) ) : '';
        
        ob_start();
        ?>
        <div class="zutalw-popup-form">
            <div class="zutalw-form">
                <form class="form-input">
                    <button type="button" class="btn-close" onclick="close_popup(this)">×</button>
                    <div class="form-group head-congrate">
                        <h4><?php echo esc_html__( 'Congratulations', 'zuta-lucky-wheel' ); ?></h4>
                        <p class="namegif"><?php echo esc_html__( 'You got a gift:', 'zuta-lucky-wheel' ) . ' ' . esc_html( $gif ); ?></p>
                        <p><?php echo esc_html__( 'Leave your information, please!', 'zuta-lucky-wheel' ); ?></p>
                    </div>
                    <div class="form-group"><input class="fullname" name="fullname" placeholder="<?php echo esc_attr__( 'Fullname', 'zuta-lucky-wheel' ); ?>"></div>
                    <div class="form-group"><input class="phone" name="phone" placeholder="<?php echo esc_attr__( 'Phone', 'zuta-lucky-wheel' ); ?>"></div>
                    <div class="form-group"><input class="email" name="email" placeholder="<?php echo esc_attr__( 'Email', 'zuta-lucky-wheel' ); ?>"></div>
                    <div class="form-group but">
                        <button type="button" onclick="submit_customer(this)" class="btn btn-primary"><?php echo esc_html__( 'Submit', 'zuta-lucky-wheel' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        $allowed_html = array(
            'div'    => array( 'class' => array(), 'style' => array() ),
            'form'   => array( 'class' => array() ),
            'h4'     => array( 'style' => array() ),
            'p'      => array( 'class' => array(), 'style' => array() ),
            'input'  => array( 'class' => array(), 'name' => array(), 'placeholder' => array() ),
            'button' => array( 'class' => array(), 'type' => array(), 'onclick' => array() ),
        );

        echo wp_kses( $output, $allowed_html );
        wp_die();
    }

    /**
     * Updates the wheel settings (Admin).
     */
    public function ajax_updateConfig() {
        check_ajax_referer( 'zutalw-nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You do not have permission to do this.', 'zuta-lucky-wheel' ) );
        }

        // [FIX] wp_unslash before sanitize/decode
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $raw_input = isset($_POST['dataconfig']) ? wp_unslash( $_POST['dataconfig'] ) : '';
        
        $raw_config = [];
        // If it's a JSON string
        if ( is_string( $raw_input ) && !empty($raw_input) ) {
            $raw_config = json_decode( $raw_input, true );
        } elseif ( is_array( $raw_input ) ) {
            $raw_config = $raw_input;
        }

        if ( empty( $raw_config ) ) {
            wp_send_json_error( 'invalid_input' );
        }

        $sanitized_config = $this->sanitize_recursive( $raw_config );
        $license = isset( $_POST['license'] ) ? intval( $_POST['license'] ) : 0;

        if ( class_exists( 'ZUTALW_Model_ConfigGame' ) ) {
            $model = new ZUTALW_Model_ConfigGame();
            $res = $model->save_config( $sanitized_config, $license );
            
            if ( is_wp_error( $res ) ) {
                wp_send_json_error( $res->get_error_message() );
            }
            wp_send_json_success( 'ok' );
        }
        
        wp_send_json_error( 'Config Model missing.' );
    }
}