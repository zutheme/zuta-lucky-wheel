<?php
/**
 * zutalw Customer Model
 * Handles database interactions for customer data with Object Caching support.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Model_Customer {

    protected $table;
    protected $cache_group = 'zuta_lucky_wheel_customers';

    /**
     * Constructor: Initializes the table name with WordPress prefix.
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'zutalw_customers';
    }

    /**
     * Get all customers with support for date filtering and Object Caching.
     * * Uses wp_cache_get/set to fix "NoCaching" warnings.
     * Uses %i placeholders to fix "InterpolatedNotPrepared" warnings.
     *
     * @param string $start_date (Y-m-d)
     * @param string $end_date (Y-m-d)
     * @return array
     */
    public function get_all_customers( $start_date = '', $end_date = '' ) {
        global $wpdb;
        $table = $this->table;

        // 1. Generate Cache Key using a "Last Changed" versioning strategy
        $last_changed = wp_cache_get( 'last_changed', $this->cache_group );
        if ( ! $last_changed ) {
            $last_changed = microtime();
            wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
        }
        
        $cache_key = 'all_customers_' . md5( $start_date . $end_date . $last_changed );
        $results   = wp_cache_get( $cache_key, $this->cache_group );

        // 2. Return cached data if available
        if ( false !== $results ) {
            return $results;
        }

        // 3. If no cache, query the Database
        // Note: Direct DB calls are necessary for custom tables.
        
        // Case 1: Both Start and End dates provided
        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results( $wpdb->prepare( 
                "SELECT * FROM %i WHERE created_at >= %s AND created_at <= %s ORDER BY idcustomer DESC",
                $table, 
                $start_date . ' 00:00:00', 
                $end_date . ' 23:59:59' 
            ), ARRAY_A );
        
        // Case 2: Only Start date provided
        } elseif ( ! empty( $start_date ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results( $wpdb->prepare( 
                "SELECT * FROM %i WHERE created_at >= %s ORDER BY idcustomer DESC",
                $table, 
                $start_date . ' 00:00:00'
            ), ARRAY_A );

        // Case 3: Only End date provided
        } elseif ( ! empty( $end_date ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results( $wpdb->prepare( 
                "SELECT * FROM %i WHERE created_at <= %s ORDER BY idcustomer DESC",
                $table, 
                $end_date . ' 23:59:59'
            ), ARRAY_A );

        // Case 4: No date filter applied
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $results = $wpdb->get_results( $wpdb->prepare( 
                "SELECT * FROM %i ORDER BY idcustomer DESC", 
                $table 
            ), ARRAY_A );
        }

        // 4. Save result to Cache
        wp_cache_set( $cache_key, $results, $this->cache_group );

        return $results;
    }

    /**
     * Insert a new customer record into the database.
     * Clears the cache to ensure the list is up-to-date.
     *
     * @param array $data Input data from the spin form.
     * @return int|WP_Error ID of the inserted row or WP_Error on failure.
     */
    public function insert_customer( $data = array() ) {
        global $wpdb;
        
        $current_time = current_time( 'mysql' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert( 
            $this->table, 
            array(
                'fullname'   => sanitize_text_field( $data['fullname'] ?? '' ),
                'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
                'email'      => sanitize_email( $data['email'] ?? '' ),
                'getgift'    => sanitize_text_field( $data['getgift'] ?? '' ),
                'license'    => intval( $data['license'] ?? 0 ),
                'created_at' => $current_time
            ), 
            array( '%s', '%s', '%s', '%s', '%d', '%s' ) 
        );

        if ( $inserted === false ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }

        // 5. Invalidate Cache: Update the "last_changed" key
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
        
        return $wpdb->insert_id;
    }
}