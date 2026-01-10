<?php
/**
 * LTW Admin Display Settings
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Admin_Display {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_settings() {
        register_setting( 'ltw_display_options_group', 'ltw_popup_delay' );
        register_setting( 'ltw_display_options_group', 'ltw_max_spins' );
        // ADDED: Register Reset Days setting
        register_setting( 'ltw_display_options_group', 'ltw_reset_days' );
        
        register_setting( 'ltw_display_options_group', 'ltw_recaptcha_site_key' );
        register_setting( 'ltw_display_options_group', 'ltw_recaptcha_secret_key' );
    }

    public function render() {
        if ( isset( $_POST['ltw_save_display'] ) && check_admin_referer( 'ltw_save_display_nonce' ) ) {
            
            $delay = isset( $_POST['ltw_popup_delay'] ) ? intval( $_POST['ltw_popup_delay'] ) : 0;
            update_option( 'ltw_popup_delay', $delay );

            $max_spins = isset( $_POST['ltw_max_spins'] ) ? intval( $_POST['ltw_max_spins'] ) : 1;
            update_option( 'ltw_max_spins', $max_spins );

            // ADDED: Save Reset Days
            $reset_days = isset( $_POST['ltw_reset_days'] ) ? intval( $_POST['ltw_reset_days'] ) : 1;
            if ($reset_days < 1) $reset_days = 1; // Prevent 0 or negative
            update_option( 'ltw_reset_days', $reset_days );

            if ( isset( $_POST['ltw_recaptcha_site_key'] ) ) {
                update_option( 'ltw_recaptcha_site_key', sanitize_text_field( $_POST['ltw_recaptcha_site_key'] ) );
            }
            if ( isset( $_POST['ltw_recaptcha_secret_key'] ) ) {
                update_option( 'ltw_recaptcha_secret_key', sanitize_text_field( $_POST['ltw_recaptcha_secret_key'] ) );
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'lucky-the-wheel' ) . '</p></div>';
        }

        $current_delay = get_option( 'ltw_popup_delay', 0 );
        $current_max_spins = get_option( 'ltw_max_spins', 1 );
        // ADDED: Get Reset Days (Default 1 day)
        $current_reset_days = get_option( 'ltw_reset_days', 1 );

        $site_key = get_option( 'ltw_recaptcha_site_key', '' );
        $secret_key = get_option( 'ltw_recaptcha_secret_key', '' );
        ?>
        <div class="ltw-admin-display-wrap" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; margin-top: 20px; max-width: 600px;">
            <h3><?php echo esc_html__( 'Popup Display Settings', 'lucky-the-wheel' ); ?></h3>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'ltw_save_display_nonce' ); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="ltw_popup_delay"><?php echo esc_html__( 'Popup Delay (seconds)', 'lucky-the-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="ltw_popup_delay" name="ltw_popup_delay" value="<?php echo esc_attr( $current_delay ); ?>" class="regular-text" min="0" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="ltw_max_spins"><?php echo esc_html__( 'Max Spins per Device', 'lucky-the-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="ltw_max_spins" name="ltw_max_spins" value="<?php echo esc_attr( $current_max_spins ); ?>" class="regular-text" min="1" />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="ltw_reset_days"><?php echo esc_html__( 'Reset Limit After (Days)', 'lucky-the-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="ltw_reset_days" name="ltw_reset_days" value="<?php echo esc_attr( $current_reset_days ); ?>" class="regular-text" min="1" />
                            <p class="description"><?php echo esc_html__( 'How many days before the user can spin again? Default is 1 day.', 'lucky-the-wheel' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row" colspan="2"><hr><strong>Google reCAPTCHA v3 Protection</strong></th>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="ltw_recaptcha_site_key">Site Key</label></th>
                        <td>
                            <input type="text" id="ltw_recaptcha_site_key" name="ltw_recaptcha_site_key" value="<?php echo esc_attr( $site_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="ltw_recaptcha_secret_key">Secret Key</label></th>
                        <td>
                            <input type="password" id="ltw_recaptcha_secret_key" name="ltw_recaptcha_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" />
                            <p class="description"><a href="https://www.google.com/recaptcha/admin/create" target="_blank">Get Keys Here (Select v3)</a></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="ltw_save_display" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save Changes', 'lucky-the-wheel' ); ?>">
                </p>
            </form>
        </div>
        <?php
    }
}