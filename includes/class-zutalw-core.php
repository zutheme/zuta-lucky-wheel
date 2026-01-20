<?php
/**
 * zutalw Core loader
 * Initializes admin, frontend, ajax, database and shortcode components.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Core {
    private static $instance = null;
    public $admin;
    public $frontend;
    public $ajax;
    public $database;
    public $shortcode;

    private function __construct() {
        $models_dir = ZUTALW_DIR . 'includes/models/';
        require_once $models_dir . 'model-zutalw-configgame.php';
        require_once $models_dir . 'model-zutalw-customer.php';

        $this->database = new zutalw_Database();

        if ( is_admin() ) {
            $this->admin = new zutalw_Admin( $this->database );
        }

        $this->frontend = new zutalw_Frontend();
        $this->ajax = new zutalw_Ajax();
        $this->shortcode = new zutalw_Shortcode();

        if ( function_exists( 'register_activation_hook' ) ) {
            $main_file = dirname( dirname( __FILE__ ) ) . '/zuta-lucky-wheel.php';
            register_activation_hook( $main_file, array( $this->database, 'create_config' ) );
            register_activation_hook( $main_file, array( $this->database, 'create_customer' ) );
        }
    }
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_winning_result( $config ) {
        $game_mode = isset($config[0]['game_mode']) ? $config[0]['game_mode'] : 'weighted';
        $items = [];
        foreach ($config as $key => $val) {
            if ($key == 0) continue;
            $val['original_index'] = $key - 1; 
            $items[] = $val;
        }

        if ($game_mode === 'random') {
            return $items[array_rand($items)];
        }

        $total_weight = 0;
        foreach ($items as $item) {
            $weight = isset($item['probability']) ? (int)$item['probability'] : 0;
            $total_weight += $weight;
        }

        if ($total_weight <= 0) {
            return $items[array_rand($items)];
        }

        // ĐÃ SỬA: Sử dụng wp_rand thay cho mt_rand
        $rand = wp_rand(1, $total_weight);
        foreach ($items as $item) {
            $weight = (int)$item['probability'];
            if ($weight <= 0) continue;

            $rand -= $weight;
            if ($rand <= 0) {
                return $item;
            }
        }
        return $items[0];
    }

    public static function get_config_label( $key ) {
        switch ( $key ) {
            case 'label':
                return esc_html__( 'Label', 'zuta-lucky-wheel' ); 
            case 'textbut':
                return esc_html__( 'Button Text', 'zuta-lucky-wheel' ); 
            case 'fontsize':
                return esc_html__( 'Font Size', 'zuta-lucky-wheel' ); 
            case 'sizetextbut':
                return esc_html__( 'Button Text Size', 'zuta-lucky-wheel' );
            case 'textleft':
                return esc_html__( 'Position Left', 'zuta-lucky-wheel' );
            case 'texttop':
                return esc_html__( 'Position Top', 'zuta-lucky-wheel' );
            case 'colorodd':
                return esc_html__( 'Odd Segment Color', 'zuta-lucky-wheel' ); 
            case 'coloreven':
                return esc_html__( 'Even Segment Color', 'zuta-lucky-wheel' ); 
            case 'backgroundodd':
                return esc_html__( 'Odd Background', 'zuta-lucky-wheel' );
            case 'backgroundeven':
                return esc_html__( 'Even Background', 'zuta-lucky-wheel' );
            case 'colwin':
                return esc_html__( 'Pointer Color', 'zuta-lucky-wheel' );
            case 'coltextwin':
                return esc_html__( 'Pointer Text Color', 'zuta-lucky-wheel' );
            case 'colbut':
                return esc_html__( 'Button Color', 'zuta-lucky-wheel' );
            case 'coltextbut':
                return esc_html__( 'Button Text Color', 'zuta-lucky-wheel' );
            case 'colbutpress':
                return esc_html__( 'Button Press Color', 'zuta-lucky-wheel' );
            case 'coltextpress':
                return esc_html__( 'Button Press Text Color', 'zuta-lucky-wheel' );
            case 'mobile':
                return esc_html__( 'Mobile', 'zuta-lucky-wheel' );
            case 'laptop':
                return esc_html__( 'Laptop', 'zuta-lucky-wheel' );
            case 'probability':
                return esc_html__( 'Win Rate (%)', 'zuta-lucky-wheel' ); 
            default:
                return esc_html( ucfirst( $key ) ); 
        }
    }
}