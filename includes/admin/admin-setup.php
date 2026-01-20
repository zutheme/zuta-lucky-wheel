<?php
/**
 * zutalw Admin Setup Class
 * Handles the rendering of the Game Design and Settings UI in the Admin Dashboard.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Initialize the configuration model
require_once ZUTALW_DIR . 'includes/models/model-zutalw-configgame.php';

class ZUTALW_Admin_Setup {

    /**
     * Render the main setup page HTML.
     */
    public function render() {
        $model = new ZUTALW_Model_ConfigGame();
        $raw   = $model->get_latest_config();
        $json  = json_decode( $raw, true );

        // Validate configuration data integrity
        if ( ! is_array( $json ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid configuration JSON detected.', 'zuta-lucky-wheel' ) . '</p></div>';
            return;
        }
        ?>

        <div id="wrap-game" class="wrap-game">

            <div class="option-left">
                <div class="config">
                    <ul>
                        <li>
                            <p><?php esc_html_e('Game Mode', 'zuta-lucky-wheel'); ?></p>
                            <?php 
                                // Fetch current mode from global config (Row 0), default to 'weighted'
                                $current_mode = isset($json[0]['game_mode']) ? $json[0]['game_mode'] : 'weighted'; 
                            ?>
                            <select data-row="0" data-key="game_mode" onchange="change_content(this)" style="width:100%; margin-bottom:10px;">
                                <option value="weighted" <?php selected($current_mode, 'weighted'); ?>>
                                    <?php esc_html_e('Weighted Probability', 'zuta-lucky-wheel'); ?>
                                </option>
                                <option value="random" <?php selected($current_mode, 'random'); ?>>
                                    <?php esc_html_e('Pure Random (100%)', 'zuta-lucky-wheel'); ?>
                                </option>
                            </select>
                            <small style="color:#666;">
                                * <strong>Weighted:</strong> <?php esc_html_e('Based on the win rate weights entered below.', 'zuta-lucky-wheel'); ?><br>
                                * <strong>Random:</strong> <?php esc_html_e('Every segment has an equal chance regardless of probability.', 'zuta-lucky-wheel'); ?>
                            </small>
                        </li>

                    <?php
                    // Iterate through prize segments (Skip Row 0: Global Config)
                    foreach ( $json as $row => $item ) {
                        if ( $row == 0 ) continue;

                        echo '<li><ul>';
                        foreach ( $item as $key => $val ) {
                            echo '<li>';
                            
                            /**
                             * FIX FOR LINE 65: Added esc_html to the label output.
                             */
                            echo '<p>' . esc_html( ZUTALW_Core::get_config_label( $key ) ) . '</p>';

                            // --- FIELD RENDERING LOGIC ---
                            
                            // Case 1: Standard text inputs for labels and buttons
                            if ( in_array( $key, [ 'textbut', 'label' ] ) ) {
                                /**
                                 * FIX FOR LINE 71: Escaped data attributes and values.
                                 */
                                echo '<input type="text" data-row="' . esc_attr( $row ) . '" data-key="' . esc_attr( $key ) . '" onchange="change_content(this)" value="' . esc_attr( $val ) . '">';
                            } 
                            // Case 2: Numeric inputs for styling (fonts, positions)
                            elseif ( in_array( $key, [ 'fontsize', 'textleft', 'texttop', 'sizetextbut' ] ) ) {
                                /**
                                 * FIX FOR LINE 75: Escaped data attributes and values.
                                 */
                                echo '<input class="txt" type="text" data-row="' . esc_attr( $row ) . '" data-key="' . esc_attr( $key ) . '" onchange="change_content(this)" value="' . esc_attr( $val ) . '">';
                            }
                            // Case 3: Win weight input for the probability calculation
                            elseif ( $key === 'probability' ) {
                                ?>
                                <div class="probability-wrapper">
                                    <?php /** FIX FOR LINES 84-85: Escaped $row and $key variables. */ ?>
                                    <input type="number" min="0" max="1000" step="1" 
                                           class="small-text" 
                                           style="width: 80px;"
                                           data-row="<?php echo esc_attr( $row ); ?>" 
                                           data-key="<?php echo esc_attr( $key ); ?>" 
                                           onchange="change_content(this)" 
                                           value="<?php echo esc_attr( $val ); ?>">
                                </div>
                                <?php
                            } 
                            // Case 4: Color picker for all other aesthetic settings
                            else {
                                /**
                                 * FIX FOR LINE 93: Escaped attributes for color fields.
                                 */
                                echo '<input type="text" class="my-color-field" data-row="' . esc_attr( $row ) . '" data-key="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
                            }
                            
                            echo '</li>';
                        }
                        echo '</ul></li>';
                    }
                    ?>
                    </ul>
                </div>
            </div>

            <div class="option-right">
                <div class="config-bottom">
                    <ul>
                        <?php 
                        // Process Global Config (Row 0)
                        foreach ( $json[0] as $key => $val ) : 
                            // Filter out system/internal keys from the UI
                            if ( in_array( $key, [ 'display_mode', 'display_ids', 'game_mode' ] ) ) continue; 
                        ?>
                            <li>
                                <?php /** FIX FOR LINE 115: Escaped global config label. */ ?>
                                <p><?php echo esc_html( ZUTALW_Core::get_config_label( $key ) ); ?></p>

                                <?php if ( in_array( $key, [ 'textbut', 'fontsize', 'sizetextbut' ] ) ) : ?>
                                    <input type="text" data-row="0" data-key="<?php echo esc_attr( $key ); ?>" onchange="change_content(this)" value="<?php echo esc_attr( $val ); ?>">
                                <?php else : ?>
                                    <input class="my-color-field" type="text" data-row="0" data-key="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>">
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="preview-game">
                <div id="area-game"></div>
            </div>

        </div>

        <?php
    }
}