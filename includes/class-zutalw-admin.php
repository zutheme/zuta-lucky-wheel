<?php
/**
 * zutalw Admin Class
 * Registers the administration menu and manages admin-side modules and assets.
 * FIXED: Localize script attached to 'zutalw-sketch' to ensure Assets URL is available early.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Admin {

    private $db;

    public function __construct( $database ) {
        $this->db = $database;
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_menu() {
        add_menu_page(
            esc_html__( 'Lucky Wheel Settings', 'zuta-lucky-wheel' ),
            esc_html__( 'Lucky Wheel', 'zuta-lucky-wheel' ),
            'manage_options',
            'lucky-wheel-settings',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-generic',
            56
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page', 'zuta-lucky-wheel' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'setup';

        echo '<div class="wrap"><h1>' . esc_html__( 'Lucky Wheel Settings', 'zuta-lucky-wheel' ) . '</h1>';

        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=lucky-wheel-settings&tab=setup" class="nav-tab ' . ( $tab === 'setup' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Design Setup', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=display" class="nav-tab ' . ( $tab === 'display' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Display Rules', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=trigger" class="nav-tab ' . ( $tab === 'trigger' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Manual Trigger', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=customer" class="nav-tab ' . ( $tab === 'customer' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Customers', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=upgrade" class="nav-tab ' . ( $tab === 'upgrade' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Upgrade', 'zuta-lucky-wheel' ) . '</a>';
        echo '</h2>';

        $admin_base = ZUTALW_DIR . 'includes/admin/';

        switch ( $tab ) {
            case 'setup':
                require_once $admin_base . 'admin-setup.php';
                $mod = new ZUTALW_Admin_Setup();
                $mod->render();
                break;
            case 'display':
                if ( file_exists( $admin_base . 'class-zutalw-admin-display.php' ) ) {
                    require_once $admin_base . 'class-zutalw-admin-display.php';
                    $mod = new zutalw_Admin_Display();
                    $mod->render();
                } else { echo '<p>' . esc_html__( 'Display rules module not found.', 'zuta-lucky-wheel' ) . '</p>'; }
                break;
            case 'trigger':
                $this->render_trigger_guide();
                break;
            case 'customer':
                if ( class_exists( 'zutalw_Admin_Customers' ) ) {
                    $mod = new zutalw_Admin_Customers();
                    $mod->render();
                }
                break;
            case 'upgrade':
                if ( file_exists( $admin_base . 'admin-upgrade.php' ) ) {
                    require_once $admin_base . 'admin-upgrade.php';
                    $mod = new zutalw_Admin_Upgrade();
                    $mod->render();
                }
                break;
            default:
                echo '<p>' . esc_html__( 'Unknown tab', 'zuta-lucky-wheel' ) . '</p>';
        }
        echo '</div>';
    }

    public function enqueue_admin_assets( $hook ) {
        if ( false === strpos( $hook, 'lucky-wheel-settings' ) ) { return; }

        // Styles
        wp_enqueue_style( 'zutalw-admin-css', ZUTALW_ASSETS_URL . 'css/style_rotate_admin.css', array(), ZUTALW_VERSION );
        wp_enqueue_style( 'zutalw-admin-the-wheel_css', ZUTALW_ASSETS_URL . 'css/rotate_style.css', array(), ZUTALW_VERSION );
        wp_enqueue_style( 'wp-color-picker' );
        
        // Scripts
        wp_enqueue_media();
        wp_enqueue_script( 'wp-color-picker' );

        // Engine Scripts
        wp_enqueue_script( 'p5-js', ZUTALW_ASSETS_URL . 'js/p5.min.js', array(), ZUTALW_VERSION, true );
        wp_enqueue_script( 'p5-dom-js', ZUTALW_ASSETS_URL . 'js/p5.dom.min.js', array('p5-js'), ZUTALW_VERSION, true );
        wp_enqueue_script( 'p5-sound-js', ZUTALW_ASSETS_URL . 'js/p5.sound.min.js', array('p5-js'), ZUTALW_VERSION, true );

        wp_enqueue_script( 'matter-js', ZUTALW_ASSETS_URL . 'js/matter.js', array(), ZUTALW_VERSION, true );
        wp_enqueue_script( 'decomp-js', ZUTALW_ASSETS_URL . 'js/decomp.js', array('matter-js'), ZUTALW_VERSION, true );
        wp_enqueue_script( 'pathseg-js', ZUTALW_ASSETS_URL . 'js/pathseg.js', array('matter-js'), ZUTALW_VERSION, true );
        wp_enqueue_script( 'arcshape-js', ZUTALW_ASSETS_URL . 'js/arcshape.js', array('matter-js'), ZUTALW_VERSION, true );
        wp_enqueue_script( 'particle-js', ZUTALW_ASSETS_URL . 'js/particle.js', array('matter-js'), ZUTALW_VERSION, true );
        wp_enqueue_script( 'boundary-js', ZUTALW_ASSETS_URL . 'js/boundary.js', array('matter-js'), ZUTALW_VERSION, true );

        // Main Sketch (This needs the Config variable FIRST)
        wp_enqueue_script( 'zutalw-sketch', ZUTALW_ASSETS_URL . 'js/sketch.js', array( 'matter-js', 'decomp-js' ), ZUTALW_VERSION, true );

        // Helper Scripts
        wp_enqueue_script( 'zutalw-custom', ZUTALW_ASSETS_URL . 'js/custom_rotate.js', array( 'zutalw-sketch' ), ZUTALW_VERSION, true );
        wp_enqueue_script( 'zutalw-admin-js', ZUTALW_ASSETS_URL . 'js/setupgame.js', array( 'zutalw-sketch', 'wp-color-picker' ), ZUTALW_VERSION, true );
        wp_enqueue_script( 'zutalw-admin-setup-js', ZUTALW_ASSETS_URL . 'js/admin-setup.js', array( 'jquery' ), ZUTALW_VERSION, true );

        // --- PREPARE & LOCALIZE DATA (UNIFIED) ---
        $config_json = class_exists('ZUTALW_Model_ConfigGame') ? ZUTALW_Model_ConfigGame::get_instance_latest() : '';
        
        $localize_data = array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'home_url'    => home_url(),
            'assets_url'  => ZUTALW_ASSETS_URL, // Path for Audio
            'plugin_url'  => ZUTALW_URL,
            'mode_admin'  => 'true',
            'getConfig'   => $config_json,
            'nonce'       => wp_create_nonce( 'zutalw-nonce' ),
            'recaptcha_site_key' => '',
            'device_id'   => 'admin_preview'
        );

        // [CRITICAL FIX] Attach to 'zutalw-sketch' instead of 'zutalw-admin-js'.
        // sketch.js loads BEFORE setupgame.js, so it needs the config variable available immediately.
        wp_localize_script( 'zutalw-sketch', 'ZutalwConfig', $localize_data );
    }

    private function render_trigger_guide() {
        ?>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2><?php esc_html_e( 'Manual Trigger Guide', 'zuta-lucky-wheel' ); ?></h2>
            <div style="background:#f0f0f1; padding:20px; border-radius:4px; border:1px dashed #ccc; margin: 20px 0;">
                <code style="font-size: 1.5em; color: #d63638; user-select:all;">#zutalw_lucky_spin=0</code>
            </div>
            <h3><?php esc_html_e( 'Implementation Examples:', 'zuta-lucky-wheel' ); ?></h3>
            <table class="widefat fixed" cellspacing="0" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Element Type', 'zuta-lucky-wheel' ); ?></th>
                        <th><?php esc_html_e( 'How to Implement', 'zuta-lucky-wheel' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e( 'Button or Link', 'zuta-lucky-wheel' ); ?></strong></td>
                        <td><code>&lt;a href="#zutalw_lucky_spin=0" class="my-button"&gt; Spin Now &lt;/a&gt;</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Image Click', 'zuta-lucky-wheel' ); ?></strong></td>
                        <td><code>&lt;a href="#zutalw_lucky_spin=0"&gt; &lt;img src="banner.jpg" /&gt; &lt;/a&gt;</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Navigation Menu', 'zuta-lucky-wheel' ); ?></strong></td>
                        <td><?php esc_html_e( 'Create a "Custom Link" in Menus and set the URL to #zutalw_lucky_spin=0', 'zuta-lucky-wheel' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}