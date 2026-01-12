<?php
/**
 * LTW Admin: register menu and load admin modules
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Admin {

    private $db;

    public function __construct( $database ) {
        $this->db = $database;

        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function add_menu() {

        add_menu_page(
            __( 'Lucky Wheel Settings', 'zuta-lucky-wheel' ),
            __( 'Lucky Wheel', 'zuta-lucky-wheel' ),
            'manage_options',
            'lucky-wheel-settings',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-generic',
            56
        );
    }

    public function render_admin_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to access this page', 'zuta-lucky-wheel' ) );
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'setup';

        echo '<div class="wrap"><h1>' . esc_html__( 'Lucky Wheel Settings', 'zuta-lucky-wheel' ) . '</h1>';

        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=lucky-wheel-settings&tab=setup" class="nav-tab ' . ( $tab === 'setup' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Design Setup', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=display" class="nav-tab ' . ( $tab === 'display' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Display Rules', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=trigger" class="nav-tab ' . ( $tab === 'trigger' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Manual Trigger', 'zuta-lucky-wheel' ) . '</a>';

        echo '<a href="?page=lucky-wheel-settings&tab=customer" class="nav-tab ' . ( $tab === 'customer' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Customers', 'zuta-lucky-wheel' ) . '</a>';
        echo '<a href="?page=lucky-wheel-settings&tab=upgrade" class="nav-tab ' . ( $tab === 'upgrade' ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Upgrade', 'zuta-lucky-wheel' ) . '</a>';
        echo '</h2>';

        $admin_base = LTW_PLUGIN_DIR . 'includes/admin/';

        switch ( $tab ) {

            case 'setup':
                require_once $admin_base . 'admin-setup.php';
                $mod = new LTW_Admin_Setup();
                $mod->render();
                break;

            // --- ADDED: DISPLAY LOGIC ---
            case 'display':
                // Check if class file exists
                if ( file_exists( $admin_base . 'class-ltw-admin-display.php' ) ) {
                    require_once $admin_base . 'class-ltw-admin-display.php';
                    $mod = new LTW_Admin_Display();
                    $mod->render();
                } else {
                    echo '<p>' . esc_html__( 'File class-ltw-admin-display.php not found.', 'zuta-lucky-wheel' ) . '</p>';
                }
                break;
            case 'trigger':
                $this->render_trigger_guide();
                break;
            case 'customer':
                //require_once $admin_base . 'admin-customers.php';
                $mod = new LTW_Admin_Customers();
                $mod->render();
                break;

            case 'upgrade':
                require_once $admin_base . 'admin-upgrade.php';
                $mod = new LTW_Admin_Upgrade();
                $mod->render();
                break;

            default:
                echo '<p>' . esc_html__( 'Unknown tab', 'zuta-lucky-wheel' ) . '</p>';
        }

        echo '</div>';
    }


    /** LOAD ADMIN ASSETS */
    public function enqueue_admin_assets( $hook ) {

        if ( false === strpos( $hook, 'lucky-wheel-settings' ) ) {
            return;
        }

        /* ------- CSS + WP COLOR PICKER ------- */
        wp_enqueue_style( 'ltw-admin-css', LTW_ASSETS_URL . 'css/style_rotate_admin.css', array(), LTW_PLUGIN_VERSION );
         wp_enqueue_style( 'ltw-admin-the-wheel_css', LTW_ASSETS_URL . 'css/rotate_style.css', array(), LTW_PLUGIN_VERSION );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_media();

        /* ------- ENGINE FOR PREVIEW (same as frontend) ------- */
        wp_enqueue_script( 'p5-js', LTW_ASSETS_URL . 'js/p5.min.js', array(), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'p5-dom-js', LTW_ASSETS_URL . 'js/p5.dom.min.js', array('p5-js'), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'p5-sound-js', LTW_ASSETS_URL . 'js/p5.sound.min.js', array('p5-js'), LTW_PLUGIN_VERSION, true );

        wp_enqueue_script( 'matter-js', LTW_PLUGIN_URL . 'build/matter.js', array(), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'decomp-js', LTW_ASSETS_URL . 'js/decomp.js', array('matter-js'), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'pathseg-js', LTW_ASSETS_URL . 'js/pathseg.js', array('matter-js'), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'arcshape-js', LTW_ASSETS_URL . 'js/arcshape.js', array('matter-js'), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'particle-js', LTW_ASSETS_URL . 'js/particle.js', array('matter-js'), LTW_PLUGIN_VERSION, true );
        wp_enqueue_script( 'boundary-js', LTW_ASSETS_URL . 'js/boundary.js', array('matter-js'), LTW_PLUGIN_VERSION, true );

        /* ------- PREVIEW ENGINE ------- */
        wp_enqueue_script( 'ltw-sketch', LTW_ASSETS_URL . 'js/sketch.js', array( 'matter-js', 'decomp-js' ), LTW_PLUGIN_VERSION, true );

        /* ------- CUSTOM ROTATE MUST LOAD AFTER sketch.js ------- */
      

        /* ------- LOCALIZE CONFIG -------- */
        $config_json = LTW_Model_ConfigGame::get_instance_latest();

        wp_localize_script( 'ltw-sketch', 'LuckyWheelFront', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'home_url'    => home_url(),
            'assets_url'  => LTW_ASSETS_URL,
            'plugin_url'  => LTW_PLUGIN_URL,
            'mode_admin'  => 'true',
            'getConfig'   => $config_json,
            'nonce'       => wp_create_nonce( 'lucky-wheel-nonce' ),
        ));

        wp_enqueue_script( 
            'ltw-custom', 
            LTW_ASSETS_URL . 'js/custom_rotate.js', 
            array( 'ltw-sketch' ), 
            LTW_PLUGIN_VERSION, 
            true 
        );

        /* ------- ADMIN CONFIG EDITOR -------- */
        wp_enqueue_script(
            'ltw-admin-js',
            LTW_ASSETS_URL . 'js/setupgame.js',
            array( 'ltw-sketch', 'wp-color-picker' ),
            LTW_PLUGIN_VERSION,
            true
        );
        // Enqueue the script handling the UI toggle for Weighted/Random modes
        wp_enqueue_script( 
            'ltw-admin-setup-js', // Unique Handle
            LTW_ASSETS_URL . 'js/admin-setup.js', 
            array( 'jquery' ), 
            LTW_PLUGIN_VERSION, 
            true 
        );
    }
    /**
     * Renders the manual trigger instructions tab.
     */
    private function render_trigger_guide() {
        ?>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2><?php esc_html_e( 'Manual Trigger Guide', 'zuta-lucky-wheel' ); ?></h2>
            <p><?php esc_html_e( 'By default, the wheel can be managed via the "Display Rules" tab. However, if you want to trigger the wheel popup manually from a specific button, image, or menu item, use the link hash below:', 'zuta-lucky-wheel' ); ?></p>
            
            <div style="background:#f0f0f1; padding:20px; border-radius:4px; border:1px dashed #ccc; margin: 20px 0;">
                <code style="font-size: 1.5em; color: #d63638; user-select:all;">#lucky_spin_license=0</code>
            </div>

            <h3><?php esc_html_e( 'Usage Examples:', 'zuta-lucky-wheel' ); ?></h3>
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
                        <td><code>&lt;a href="#lucky_spin_license=0" class="my-button"&gt; Spin Now &lt;/a&gt;</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Image Click', 'zuta-lucky-wheel' ); ?></strong></td>
                        <td><code>&lt;a href="#lucky_spin_license=0"&gt; &lt;img src="banner.jpg" /&gt; &lt;/a&gt;</code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e( 'Navigation Menu', 'zuta-lucky-wheel' ); ?></strong></td>
                        <td><?php esc_html_e( 'Go to Appearance > Menus. Create a "Custom Link" and set the URL to #lucky_spin_license=0', 'zuta-lucky-wheel' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}
