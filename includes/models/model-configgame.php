<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Model_ConfigGame {

    protected $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'configgames';

        // Automatically create config if it doesn't exist
        $this->ensure_default_exists();
    }

    /**
     * Defines the default configuration for the game.
     * Includes Game Mode and individual segment Probabilities.
     */
    public function default_json() {
        return '[
            {
                "game_mode":"weighted",
                "colorodd":"#81d742",
                "backgroundodd":"#ffffff",
                "coloreven":"#ffffff",
                "backgroundeven":"#81d742",
                "fontsize":"23",
                "colwin":"#eeee22",
                "coltextwin":"#ffffff",
                "colbut":"#33aa1b",
                "coltextbut":"#ffffff",
                "sizetextbut":"15",
                "textbut":"Click",
                "colbutpress":"#89d61d",
                "coltextpress":"#ffffff"
            },
            {"label":"Mobile", "probability":12},
            {"label":"Laptop", "probability":8},
            {"label":"Tablet", "probability":10},
            {"label":"Tourist", "probability":5},
            {"label":"Smart TV", "probability":15},
            {"label":"Apple Watch", "probability":10},
            {"label":"Ticket", "probability":20},
            {"label":"Fashion Bag", "probability":20}
        ]';
    }

    // ... (rest of the ensure_default_exists and insert_default methods remain the same)

    public function ensure_default_exists() {
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE license = %d", 0 );
        $count = (int) $wpdb->get_var($sql);

        if ($count === 0) {
            $this->insert_default();
        }
    }

    public function insert_default() {
        global $wpdb;
        $wpdb->insert(
            $this->table,
            array(
                'nameconfig' => 'default config',
                'dataconfig' => $this->default_json(),
                'level'      => 1,
                'license'    => 0
            ),
            array( '%s', '%s', '%d', '%d' )
        );
        return $wpdb->insert_id;
    }

    public function get_latest_config( $license = 0 ) {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT dataconfig FROM {$this->table} WHERE license = %d ORDER BY idconfiggame DESC LIMIT 1",
            $license
        );
        $raw = $wpdb->get_var($sql);

        if (empty($raw)) {
            return $this->default_json();
        }
        return $raw;
    }

    public static function get_instance_latest( $license = 0 ) {
        $inst = new self();
        return $inst->get_latest_config($license);
    }

    public function save_config( $data, $license = 0 ) {
        global $wpdb;
        $insert = $wpdb->insert(
            $this->table,
            array(
                'nameconfig' => isset($data['nameconfig']) ? sanitize_text_field($data['nameconfig']) : 'config',
                'dataconfig' => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
                'license' => intval($license),
            ),
            array('%s','%s','%d')
        );

        if ($insert === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        return $wpdb->insert_id;
    }
}