<?php
/**
 * zutalw Admin Display Settings
 * Handles the display and saving of general display options for the lucky wheel.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Admin_Display {

    /**
     * Constructor: Hooks into admin initialization.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Registers plugin settings in the WordPress options table.
     * FIX: Added sanitize_callback to all register_setting calls to satisfy Plugin Check.
     */
    public function register_settings() {
        // --- 1. REGISTER SETTINGS WITH SANITIZATION ---
        
        // Game Title
        register_setting( 'zutalw_display_options_group', 'zutalw_game_title', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Zuta Lucky Wheel'
        ));

        // Popup Delay (Integer)
        register_setting( 'zutalw_display_options_group', 'zutalw_popup_delay', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0
        ));

        // Max Spins (Integer)
        register_setting( 'zutalw_display_options_group', 'zutalw_max_spins', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1
        ));

        // Reset Days (Integer)
        register_setting( 'zutalw_display_options_group', 'zutalw_reset_days', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1
        ));
        
        // Recaptcha Keys (Strings)
        register_setting( 'zutalw_display_options_group', 'zutalw_recaptcha_site_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => ''
        ));

        register_setting( 'zutalw_display_options_group', 'zutalw_recaptcha_secret_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => ''
        ));
    }

    /**
     * Renders the Display Settings page content.
     */
    public function render() {
        // Handle form submission securely
        if ( isset( $_POST['zutalw_save_display'] ) && check_admin_referer( 'zutalw_save_display_nonce' ) ) {
            
            // --- 2. SAVE SETTINGS MANUALLY (SECURITY DOUBLE CHECK) ---
            
            if ( isset( $_POST['zutalw_game_title'] ) ) {
                update_option( 'zutalw_game_title', sanitize_text_field( wp_unslash( $_POST['zutalw_game_title'] ) ) );
            }

            // Numeric values
            $delay = isset( $_POST['zutalw_popup_delay'] ) ? intval( $_POST['zutalw_popup_delay'] ) : 0;
            update_option( 'zutalw_popup_delay', $delay );

            $max_spins = isset( $_POST['zutalw_max_spins'] ) ? intval( $_POST['zutalw_max_spins'] ) : 1;
            update_option( 'zutalw_max_spins', $max_spins );

            $reset_days = isset( $_POST['zutalw_reset_days'] ) ? intval( $_POST['zutalw_reset_days'] ) : 1;
            if ( $reset_days < 1 ) { $reset_days = 1; }
            update_option( 'zutalw_reset_days', $reset_days );

            // API Keys
            if ( isset( $_POST['zutalw_recaptcha_site_key'] ) ) {
                update_option( 'zutalw_recaptcha_site_key', sanitize_text_field( wp_unslash( $_POST['zutalw_recaptcha_site_key'] ) ) );
            }
            if ( isset( $_POST['zutalw_recaptcha_secret_key'] ) ) {
                update_option( 'zutalw_recaptcha_secret_key', sanitize_text_field( wp_unslash( $_POST['zutalw_recaptcha_secret_key'] ) ) );
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'zuta-lucky-wheel' ) . '</p></div>';
        }

        // --- 3. RETRIEVE CURRENT VALUES ---
        $current_title = get_option( 'zutalw_game_title', 'Zuta Lucky Wheel' );

        $current_delay = get_option( 'zutalw_popup_delay', 0 );
        $current_max_spins = get_option( 'zutalw_max_spins', 1 );
        $current_reset_days = get_option( 'zutalw_reset_days', 1 );

        $site_key = get_option( 'zutalw_recaptcha_site_key', '' );
        $secret_key = get_option( 'zutalw_recaptcha_secret_key', '' );
        ?>
        <div class="zutalw-admin-display-wrap" style="padding: 20px; background: #fff; border: 1px solid #ccd0d4; margin-top: 20px; max-width: 600px;">
            <h3><?php echo esc_html__( 'Popup Display Settings', 'zuta-lucky-wheel' ); ?></h3>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'zutalw_save_display_nonce' ); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="zutalw_game_title"><?php echo esc_html__( 'Game Popup Title', 'zuta-lucky-wheel' ); ?></label></th>
                        <td>
                            <input type="text" id="zutalw_game_title" name="zutalw_game_title" value="<?php echo esc_attr( $current_title ); ?>" class="regular-text" />
                            <p class="description"><?php echo esc_html__( 'This title appears at the top of the spinning wheel popup.', 'zuta-lucky-wheel' ); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="zutalw_popup_delay"><?php echo esc_html__( 'Popup Delay (seconds)', 'zuta-lucky-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="zutalw_popup_delay" name="zutalw_popup_delay" value="<?php echo esc_attr( $current_delay ); ?>" class="regular-text" min="0" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="zutalw_max_spins"><?php echo esc_html__( 'Max Spins per Device', 'zuta-lucky-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="zutalw_max_spins" name="zutalw_max_spins" value="<?php echo esc_attr( $current_max_spins ); ?>" class="regular-text" min="1" />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="zutalw_reset_days"><?php echo esc_html__( 'Reset Limit After (Days)', 'zuta-lucky-wheel' ); ?></label></th>
                        <td>
                            <input type="number" id="zutalw_reset_days" name="zutalw_reset_days" value="<?php echo esc_attr( $current_reset_days ); ?>" class="regular-text" min="1" />
                            <p class="description"><?php echo esc_html__( 'How many days before the user can spin again? Default is 1 day.', 'zuta-lucky-wheel' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row" colspan="2"><hr><strong>Google reCAPTCHA v3 Protection</strong></th>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="zutalw_recaptcha_site_key">Site Key</label></th>
                        <td>
                            <input type="text" id="zutalw_recaptcha_site_key" name="zutalw_recaptcha_site_key" value="<?php echo esc_attr( $site_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="zutalw_recaptcha_secret_key">Secret Key</label></th>
                        <td>
                            <input type="password" id="zutalw_recaptcha_secret_key" name="zutalw_recaptcha_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" />
                            <p class="description"><a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php echo esc_html__( 'Get Keys Here (Select v3)', 'zuta-lucky-wheel' ); ?></a></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="zutalw_save_display" id="submit" class="button button-primary" value="<?php echo esc_attr__( 'Save Changes', 'zuta-lucky-wheel' ); ?>">
                </p>
            </form>
        </div>
        <?php
    }
}