<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Model_Customer {

    protected $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'customers';
    }

    /**
     * Get all customers (Supports date filtering)
     * * @param string $start_date (Y-m-d)
     * @param string $end_date (Y-m-d)
     * @return array
     */
    public function get_all_customers( $start_date = '', $end_date = '' ) {
        global $wpdb;
        
        // 1. Initialize basic query (Always true with WHERE 1=1 to easily append conditions)
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = array();

        // 2. Add Start Date filter condition
        // The date column in DB is usually 'created_at'. 
        // Add 00:00:00 to select from the beginning of the day.
        if ( ! empty( $start_date ) ) {
            $sql .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        // 3. Add End Date filter condition
        // Add 23:59:59 to select until the end of that day.
        if ( ! empty( $end_date ) ) {
            $sql .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        // 4. Sort by ID descending (Newest first)
        $sql .= " ORDER BY idcustomer DESC";

        // 5. Prepare the query to prevent SQL Injection
        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    public function insert_customer( $data = array() ) {
        global $wpdb;
        
        // Default to current time if not provided
        $current_time = current_time( 'mysql' );

        $inserted = $wpdb->insert( $this->table, array(
            'fullname'   => sanitize_text_field( $data['fullname'] ?? '' ),
            'phone'      => sanitize_text_field( $data['phone'] ?? '' ),
            'email'      => sanitize_email( $data['email'] ?? '' ),
            'getgift'    => sanitize_text_field( $data['getgift'] ?? '' ),
            'license'    => intval( $data['license'] ?? 0 ),
            'created_at' => $current_time // Ensure creation date exists
        ), array( '%s', '%s', '%s', '%s', '%d', '%s' ) );

        if ( $inserted === false ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }
        return $wpdb->insert_id;
    }
}