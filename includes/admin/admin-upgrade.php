<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Admin_Upgrade {

    public function render() {
        $legacy = LTW_PLUGIN_DIR . 'includes/modules/rotates/rotate-upgrade.php';
        if ( file_exists( $legacy ) ) {
            include $legacy;
            return;
        }

        ?>
        <div class="ltw-admin-section">
            <h2><?php esc_html_e( 'Upgrade', 'lucky-the-wheel' ); ?></h2>
            <p><?php esc_html_e( 'Upgrade options not configured.', 'lucky-the-wheel' ); ?></p>
        </div>
        <?php
    }
}
