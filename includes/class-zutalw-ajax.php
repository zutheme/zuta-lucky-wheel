<?php
/**
 * zutalw AJAX handlers
 * Handles all background requests for the game logic, configuration, and data submission.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Ajax {

    /**
     * Constructor: Register all AJAX hooks.
     */
    public function __construct() {
        // FIX: Added 'zutalw_' prefix to all AJAX actions
        add_action( 'wp_ajax_zutalw_getdataConfig', array( $this, 'ajax_getdataConfig' ) );
        add_action( 'wp_ajax_nopriv_zutalw_getdataConfig', array( $this, 'ajax_getdataConfig' ) );

        add_action( 'wp_ajax_zutalw_InsCustomer', array( $this, 'ajax_insertCustomer' ) );
        add_action( 'wp_ajax_nopriv_zutalw_InsCustomer', array( $this, 'ajax_insertCustomer' ) );

        add_action( 'wp_ajax_zutalw_popup', array( $this, 'ajax_popup' ) );
        add_action( 'wp_ajax_nopriv_zutalw_popup', array( $this, 'ajax_popup' ) );

        add_action( 'wp_ajax_zutalw_UpdateConfig', array( $this, 'ajax_updateConfig' ) );

        add_action( 'wp_ajax_zutalw_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
        add_action( 'wp_ajax_nopriv_zutalw_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
    }

    /**
     * Retrieves the latest configuration data with Object Caching.
     */
    public function ajax_getdataConfig() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'configgames';
        $license = 0;
        
        // Use caching to prevent DirectQuery/NoCaching warnings
        $cache_group = 'zutalw_config_ajax';
        $cache_key   = 'config_row_' . intval( $license );
        $row         = wp_cache_get( $cache_key, $cache_group );

        if ( false === $row ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE license = %d ORDER BY idconfiggame DESC LIMIT 1",
                    $table_name,
                    $license
                ),
                ARRAY_A
            );
            
            // Cache for 1 hour to reduce DB load
            wp_cache_set( $cache_key, $row, $cache_group, 3600 );
        }

        if ( ! $row ) {
            $row = [ 'dataconfig' => zutalw_Model_ConfigGame::default_json() ];
        }

        wp_send_json( [ $row ] );
    }

    /**
     * Securely calculates the winner and deducts a spin.
     * Updates DB and invalidates user cache.
     */
    public function ajax_get_spin_result() {
        check_ajax_referer( 'zutalw-nonce', 'security' );

        $device_id = isset($_POST['device_id']) ? sanitize_text_field( wp_unslash( $_POST['device_id'] ) ) : '';
        
        global $wpdb;
        $table_customers = $wpdb->prefix . 'customers';

        $model_config = new ZUTALW_Model_ConfigGame();
        $config       = json_decode( $model_config->get_latest_config(), true );
        $winner       = ZUTALW_Core::get_winning_result( $config );

        if ( ! $winner ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Reward calculation failed.', 'zuta-lucky-wheel' ) ) );
        }

        /**
         * Update Spin Limit.
         * Suppress DirectDatabaseQuery because UPDATE operations cannot be cached.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare(
            "UPDATE %i SET spin_limit = spin_limit - 1 WHERE device_id = %s AND spin_limit > 0",
            $table_customers,
            $device_id
        ));

        // --- CACHE INVALIDATION ---
        // Clear the cache set in zutalw_Frontend so the next check gets the new limit.
        $cache_key = 'spin_limit_' . md5( $device_id );
        wp_cache_delete( $cache_key, 'zutalw_spin_limits' );

        /**
         * Fetch new balance.
         * Direct query allowed here to ensure we return the immediate fresh value after update.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $new_spins_left = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT spin_limit FROM %i WHERE device_id = %s",
            $table_customers,
            $device_id
        ));

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
        
        $input = json_decode( file_get_contents( 'php://input' ), true );
        if ( empty( $input ) ) {
            wp_send_json_error( 'invalid_input' );
        }
        
        $model = new zutalw_Model_Customer();
        $id = $model->insert_customer( $input );
        
        if ( is_wp_error( $id ) ) {
            wp_send_json_error( $id->get_error_message() );
        }
        
        wp_send_json_success( array( 'idcustomer' => $id ) );
    }

    /**
     * Renders the Congratulations popup template with input fields.
     */
    public function ajax_popup() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied for administrators.', 'zuta-lucky-wheel' ) );
        }
        check_ajax_referer( 'zutalw-nonce', 'security' );
        
        $input = json_decode( file_get_contents( 'php://input' ), true );
        $gif = isset( $input['name_gif'] ) ? sanitize_text_field( $input['name_gif'] ) : '';
        
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
                    <div class="form-group">
                        <input class="fullname" name="fullname" placeholder="<?php echo esc_attr__( 'Fullname', 'zuta-lucky-wheel' ); ?>">
                    </div>
                    <div class="form-group">
                        <input class="phone" name="phone" placeholder="<?php echo esc_attr__( 'Phone', 'zuta-lucky-wheel' ); ?>">
                    </div>
                    <div class="form-group">
                        <input class="email" name="email" placeholder="<?php echo esc_attr__( 'Email', 'zuta-lucky-wheel' ); ?>">
                    </div>
                    <div class="form-group but">
                        <button type="button" onclick="submit_customer(this)" class="btn btn-primary"><?php echo esc_html__( 'Submit', 'zuta-lucky-wheel' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        // Allowed HTML tags for wp_kses to preserve form inputs
        $allowed_html = array(
            'div'    => array( 'class' => array(), 'id' => array(), 'style' => array() ),
            'form'   => array( 'class' => array(), 'id' => array() ),
            'h4'     => array( 'class' => array(), 'style' => array() ),
            'p'      => array( 'class' => array(), 'style' => array() ),
            'input'  => array(
                'class'       => array(),
                'name'        => array(),
                'type'        => array(),
                'value'       => array(),
                'placeholder' => array(),
            ),
            'button' => array(
                'class'   => array(),
                'type'    => array(),
                'onclick' => array(),
            ),
        );

        echo wp_kses( $output, $allowed_html );
        wp_die();
    }

    /**
     * Updates the wheel settings.
     */
    public function ajax_updateConfig() {
        check_ajax_referer( 'zutalw-nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( esc_html__( 'You do not have permission to do this.', 'zuta-lucky-wheel' ) );
        }

        $input = json_decode( file_get_contents( 'php://input' ), true );
        if ( empty( $input ) || ! isset( $input['dataconfig'] ) ) {
            wp_send_json_error( 'invalid_input' );
        }

        $model = new zutalw_Model_ConfigGame();
        $res = $model->save_config( $input['dataconfig'], isset( $input['license'] ) ? intval( $input['license'] ) : 0 );
        
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( $res->get_error_message() );
        }

        wp_send_json_success( 'ok' );
    }
}