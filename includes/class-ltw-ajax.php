<?php
/**
 * LTW AJAX handlers
 * Handles all background requests for the game logic, configuration, and data submission.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Ajax {

    /**
     * Constructor: Register all AJAX hooks.
     */
    public function __construct() {
        // Retrieve general configuration for the wheel
        add_action( 'wp_ajax_getdataConfig', array( $this, 'ajax_getdataConfig' ) );
        add_action( 'wp_ajax_nopriv_getdataConfig', array( $this, 'ajax_getdataConfig' ) );

        // Handle customer data submission after a win
        add_action( 'wp_ajax_InsCustomer', array( $this, 'ajax_insertCustomer' ) );
        add_action( 'wp_ajax_nopriv_InsCustomer', array( $this, 'ajax_insertCustomer' ) );

        // Render the HTML for the prize claim popup
        add_action( 'wp_ajax_popup', array( $this, 'ajax_popup' ) );
        add_action( 'wp_ajax_nopriv_popup', array( $this, 'ajax_popup' ) );

        // Update wheel settings (Admin only)
        add_action( 'wp_ajax_UpdateConfig', array( $this, 'ajax_updateConfig' ) );

        // FIX: Register the result calculation action (Weighted/Random logic)
        add_action( 'wp_ajax_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
        add_action( 'wp_ajax_nopriv_get_spin_result', array( $this, 'ajax_get_spin_result' ) );
    }

    /**
     * Retrieves the latest configuration data stored in the database.
     */
    public function ajax_getdataConfig() {
        global $wpdb;

        $table = $wpdb->prefix . 'configgames';
        $license = 0;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE license = %d ORDER BY idconfiggame DESC LIMIT 1", $license),
            ARRAY_A
        );

        if (!$row) {
            // Fallback to default settings if no record exists
            $row = [
                'dataconfig' => LTW_Model_ConfigGame::default_json()
            ];
        }

        // Send JSON data formatted for the frontend engine
        wp_send_json( [ $row ] );
    }

    /**
     * Securely calculates the winning segment on the server.
     * Prevents client-side manipulation of results.
     */
    /**
     * Securely calculates the winner and deducts a spin from the database.
     */
    public function ajax_get_spin_result() {
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );

        $device_id = isset($_POST['device_id']) ? sanitize_text_field($_POST['device_id']) : '';
        global $wpdb;
        $table_customers = $wpdb->prefix . 'customers';

        // 1. Calculate the result based on probability (Server-side)
        $model_config = new LTW_Model_ConfigGame();
        $config       = json_decode( $model_config->get_latest_config(), true );
        $winner       = LTW_Core::get_winning_result( $config );

        if ( ! $winner ) {
            wp_send_json_error( array( 'message' => 'Reward calculation failed.' ) );
        }

        // 2. Deduct spin count for the identified device
        $updated = $wpdb->query( $wpdb->prepare(
            "UPDATE $table_customers SET spin_limit = spin_limit - 1 WHERE device_id = %s AND spin_limit > 0",
            $device_id
        ));

        if ( $updated === false ) {
            wp_send_json_error( array( 'message' => 'Failed to process spin.' ) );
        }

        // 3. Get updated spin count to inform UI
        $new_spins_left = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT spin_limit FROM $table_customers WHERE device_id = %s",
            $device_id
        ));

        wp_send_json_success( array(
            'segment_id' => $winner['original_index'],
            'label'      => $winner['label'],
            'spins_left' => $new_spins_left
        ));
    }

    // Hàm bổ trợ để lấy số lượt còn lại (để hiển thị lên màn hình sau khi quay)
    private function get_remaining_spins($device_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT spin_limit FROM {$wpdb->prefix}customers WHERE device_id = %s",
            $device_id
        ));
    }

    /**
     * Stores customer contact info into the database.
     */
    public function ajax_insertCustomer() {
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );
        
        $input = json_decode( file_get_contents( 'php://input' ), true );
        if ( empty( $input ) ) {
            wp_send_json_error( 'invalid_input' );
        }
        
        $model = new LTW_Model_Customer();
        $id = $model->insert_customer( $input );
        
        if ( is_wp_error( $id ) ) {
            wp_send_json_error( $id->get_error_message() );
        }
        
        wp_send_json_success( array( 'idcustomer' => $id ) );
    }

    /**
     * Renders the Congratulations popup template.
     */
    public function ajax_popup() {
        // Safety check: Don't show the form to admins
        if ( current_user_can( 'manage_options' ) ) {
            wp_die( 'Access denied for administrators.' );
        }
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );
        
        $input = json_decode( file_get_contents( 'php://input' ), true );
        $gif = isset( $input['name_gif'] ) ? sanitize_text_field( $input['name_gif'] ) : '';
        
        ob_start();
        ?>
        <div class="ltw-popup-form">
            <div class="ltw-form">
                <form class="form-input">
                    <button type="button" class="btn-close" onclick="close_popup(this)">×</button>
                    <div class="form-group head-congrate">
                        <h4><?php echo esc_html__( 'Congratulations', 'lucky-the-wheel' ); ?></h4>
                        <p class="namegif"><?php echo esc_html__( 'You got a gift:', 'lucky-the-wheel' ) . ' ' . esc_html( $gif ); ?></p>
                        <p><?php echo esc_html__( 'Leave your information, please!', 'lucky-the-wheel' ); ?></p>
                    </div>
                    <div class="form-group">
                        <input class="fullname" name="fullname" placeholder="<?php echo esc_attr__( 'Fullname', 'lucky-the-wheel' ); ?>">
                    </div>
                    <div class="form-group">
                        <input class="phone" name="phone" placeholder="<?php echo esc_attr__( 'Phone', 'lucky-the-wheel' ); ?>">
                    </div>
                    <div class="form-group">
                        <input class="email" name="email" placeholder="<?php echo esc_attr__( 'Email', 'lucky-the-wheel' ); ?>">
                    </div>
                    <div class="form-group but">
                        <button type="button" onclick="submit_customer(this)" class="btn btn-primary"><?php echo esc_html__( 'Submit', 'lucky-the-wheel' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        echo ob_get_clean();
        wp_die();
    }

    /**
     * Updates the main configuration JSON string from the admin settings.
     */
    public function ajax_updateConfig() {
        // Verify security nonce
        check_ajax_referer( 'lucky-wheel-nonce', 'security' );

        // Ensure current user has admin capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'You do not have permission to do this.' );
        }

        $input = json_decode( file_get_contents( 'php://input' ), true );
        if ( empty( $input ) || ! isset( $input['dataconfig'] ) ) {
            wp_send_json_error( 'invalid_input' );
        }

        $model = new LTW_Model_ConfigGame();
        $res = $model->save_config( $input['dataconfig'], isset( $input['license'] ) ? intval( $input['license'] ) : 0 );
        
        if ( is_wp_error( $res ) ) {
            wp_send_json_error( $res->get_error_message() );
        }

        wp_send_json_success( 'ok' );
    }
}