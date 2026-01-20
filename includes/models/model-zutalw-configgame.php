<?php
/**
 * zutalw Config Game Model
 * Handles database operations for game configuration settings with Object Caching.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Model_ConfigGame {

    protected $table;
    
    /**
     * Cache group name to prevent conflicts with other plugins.
     * @var string
     */
    protected $cache_group = 'zutalw_config_game';

    /**
     * Constructor: Initializes the table name and ensures a default configuration exists.
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'configgames';

        // Automatically create default config if it doesn't exist in the database
        $this->ensure_default_exists();
    }

    /**
     * Defines the default configuration for the game in JSON format.
     *
     * @return string Default configuration JSON string.
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

    /**
     * Checks for the existence of a configuration record and inserts the default if missing.
     * Implements caching to reduce database load on every page instantiation.
     */
    public function ensure_default_exists() {
        global $wpdb;

        $cache_key = 'exists_license_0';
        $exists    = wp_cache_get( $cache_key, $this->cache_group );

        if ( false === $exists ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $count = (int) $wpdb->get_var( $wpdb->prepare( 
                "SELECT COUNT(*) FROM %i WHERE license = %d", 
                $this->table, 
                0 
            ) );

            $exists = ( $count > 0 );
            wp_cache_set( $cache_key, $exists, $this->cache_group );
        }

        if ( ! $exists ) {
            $this->insert_default();
            // Update cache after insertion
            wp_cache_set( $cache_key, true, $this->cache_group );
        }
    }

    /**
     * Inserts the initial default configuration into the database.
     *
     * @return int|bool The ID of the inserted record or false on failure.
     */
    public function insert_default() {
        global $wpdb;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            $this->table,
            array(
                'nameconfig' => 'default config',
                'dataconfig' => $this->default_json(),
                'level'      => 1,
                'license'    => 0
            ),
            array( '%s', '%s', '%d', '%d' )
        );

        if ( $inserted ) {
            // Invalidate read cache
            wp_cache_delete( 'latest_config_0', $this->cache_group );
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Retrieves the latest configuration data stored in the database.
     * Implements Object Caching to fix "NoCaching" warnings.
     *
     * @param int $license License identifier (default is 0).
     * @return string JSON configuration data.
     */
    public function get_latest_config( $license = 0 ) {
        global $wpdb;

        $cache_key = 'latest_config_' . intval( $license );
        $raw       = wp_cache_get( $cache_key, $this->cache_group );

        if ( false === $raw ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $raw = $wpdb->get_var( $wpdb->prepare(
                "SELECT dataconfig FROM %i WHERE license = %d ORDER BY idconfiggame DESC LIMIT 1",
                $this->table,
                $license
            ) );
            
            // Cache the result (even if empty) to avoid repeated failed queries
            wp_cache_set( $cache_key, $raw, $this->cache_group );
        }

        if ( empty( $raw ) ) {
            return $this->default_json();
        }
        return $raw;
    }

    /**
     * Static helper to get the latest configuration instance.
     */
    public static function get_instance_latest( $license = 0 ) {
        $inst = new self();
        return $inst->get_latest_config($license);
    }

    /**
     * Saves new configuration data to the database.
     * Clears relevant cache keys to ensure data consistency.
     *
     * @param array $data Configuration data array.
     * @param int $license License identifier.
     * @return int|WP_Error The inserted record ID or WP_Error object.
     */
    public function save_config( $data, $license = 0 ) {
        global $wpdb;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $insert = $wpdb->insert(
            $this->table,
            array(
                'nameconfig' => isset($data['nameconfig']) ? sanitize_text_field($data['nameconfig']) : 'config',
                'dataconfig' => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
                'license'    => intval($license),
            ),
            array('%s','%s','%d')
        );

        if ($insert === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }

        // Fix NoCaching: Invalidate the cache for this license so the next read gets fresh data
        wp_cache_delete( 'latest_config_' . intval( $license ), $this->cache_group );

        return $wpdb->insert_id;
    }
}