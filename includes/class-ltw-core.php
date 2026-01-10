<?php
/**
 * LTW Core loader
 * Initializes admin, frontend, ajax, database and shortcode components.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Core {
    private static $instance = null;

    public $admin;
    public $frontend;
    public $ajax;
    public $database;
    public $shortcode;

    private function __construct() {
        // Load models first
        $models_dir = LTW_PLUGIN_DIR . 'includes/models/';
        require_once $models_dir . 'model-configgame.php';
        require_once $models_dir . 'model-customer.php';
        // Load models first (new location)
        $models_dir = LTW_PLUGIN_DIR . 'includes/models/';
        require_once $models_dir . 'model-configgame.php';
        require_once $models_dir . 'model-customer.php';

        // Initialize database helper
        $this->database = new LTW_Database();

        // Admin (only if admin)
        if ( is_admin() ) {
            $this->admin = new LTW_Admin( $this->database );
        }

        // Frontend (public)
        $this->frontend = new LTW_Frontend();

        // Ajax handlers
        $this->ajax = new LTW_Ajax();

        // Shortcode
        $this->shortcode = new LTW_Shortcode();

        // Activation hooks
        if ( function_exists( 'register_activation_hook' ) ) {
            $main_file = dirname( dirname( __FILE__ ) ) . '/lucky-the-wheel.php';
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

   // File: includes/class-ltw-core.php

    public static function get_winning_result( $config ) {
        $game_mode = isset($config[0]['game_mode']) ? $config[0]['game_mode'] : 'weighted';
        $items = [];
        foreach ($config as $key => $val) {
            if ($key == 0) continue;
            $val['original_index'] = $key - 1; // Khớp với JS
            $items[] = $val;
        }

        if ($game_mode === 'random') {
            return $items[array_rand($items)];
        }

        // --- WEIGHTED LOGIC ---
        $total_weight = 0;
        foreach ($items as $item) {
            // Đảm bảo lấy đúng giá trị số, mặc định là 0 nếu không tồn tại
            $weight = isset($item['probability']) ? (int)$item['probability'] : 0;
            $total_weight += $weight;
        }

        if ($total_weight <= 0) {
            return $items[array_rand($items)];
        }

        $rand = mt_rand(1, $total_weight);
        foreach ($items as $item) {
            $weight = (int)$item['probability'];
            if ($weight <= 0) continue; // Bỏ qua ô có xác suất bằng 0 hoàn toàn

            $rand -= $weight;
            if ($rand <= 0) {
                return $item;
            }
        }

        return $items[0];
    }
    /**
     * Hàm lấy nhãn dịch dựa trên key cấu hình
     */
    public static function get_config_label( $key ) {
        switch ( $key ) {
            // Nhóm Text
            case 'label':
                return esc_html__( 'Label', 'lucky-the-wheel' ); // Dịch: Nhãn
            case 'textbut':
                return esc_html__( 'Button Text', 'lucky-the-wheel' ); // Dịch: Chữ trên nút
            case 'fontsize':
                return esc_html__( 'Font Size', 'lucky-the-wheel' ); // Dịch: Cỡ chữ
            case 'sizetextbut':
                return esc_html__( 'Button Text Size', 'lucky-the-wheel' );
            case 'textleft':
                return esc_html__( 'Position Left', 'lucky-the-wheel' );
            case 'texttop':
                return esc_html__( 'Position Top', 'lucky-the-wheel' );

            // Nhóm Màu sắc & Giao diện
            case 'colorodd':
                return esc_html__( 'Odd Segment Color', 'lucky-the-wheel' ); // Dịch: Màu ô lẻ
            case 'coloreven':
                return esc_html__( 'Even Segment Color', 'lucky-the-wheel' ); // Dịch: Màu ô chẵn
            case 'backgroundodd':
                return esc_html__( 'Odd Background', 'lucky-the-wheel' );
            case 'backgroundeven':
                return esc_html__( 'Even Background', 'lucky-the-wheel' );
            case 'colwin':
                return esc_html__( 'Pointer Color', 'lucky-the-wheel' );
            case 'coltextwin':
                return esc_html__( 'Pointer Text Color', 'lucky-the-wheel' );
            case 'colbut':
                return esc_html__( 'Button Color', 'lucky-the-wheel' );
            case 'coltextbut':
                return esc_html__( 'Button Text Color', 'lucky-the-wheel' );
            case 'colbutpress':
                return esc_html__( 'Button Press Color', 'lucky-the-wheel' );
            case 'coltextpress':
                return esc_html__( 'Button Press Text Color', 'lucky-the-wheel' );
            // Nhóm Tên phần thưởng (Ví dụ)
            case 'mobile':
                return esc_html__( 'Mobile', 'lucky-the-wheel' );
            case 'laptop':
                return esc_html__( 'Laptop', 'lucky-the-wheel' );
            // NEW: Label for Probability
            case 'probability':
                return esc_html__( 'Win Rate (%)', 'lucky-the-wheel' ); //
            // Mặc định: Nếu key không nằm trong danh sách trên thì hiển thị chính nó
            default:
                return esc_html( ucfirst( $key ) ); 
        }
    }
}
