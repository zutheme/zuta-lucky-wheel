<?php
/**
 * zutalw Database Helper
 * Handles the creation of required database tables upon plugin activation.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ZUTALW_Database {

    /**
     * Creates the configuration table (wp_configgames).
     */
    public function create_config() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'configgames';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            idconfiggame mediumint(9) NOT NULL AUTO_INCREMENT,
            nameconfig varchar(255) DEFAULT NULL,
            dataconfig LONGTEXT DEFAULT NULL,
            `level` mediumint(9) NULL,
            license mediumint(9) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (idconfiggame)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /**
         * FIX: Use prepare() with %s for table name to prevent SQL injection warnings.
         * Suppress DirectDatabaseQuery because checking table existence requires direct SQL.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );

        if ( $exists != $table_name ) {
            dbDelta( $sql );
        }
    }

    /**
     * Creates the customer table (wp_customers).
     */
    public function create_customer() {
        global $wpdb;
        $t_customer = $wpdb->prefix . 'customers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$t_customer} (
            idcustomer int(11) NOT NULL AUTO_INCREMENT,
            fullname varchar(255) DEFAULT NULL,
            phone varchar(20) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            device_id varchar(255) NOT NULL,
            spin_limit int(11) DEFAULT 0 NOT NULL,
            getgift varchar(250) DEFAULT NULL,
            license tinyint(1) DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (idcustomer)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        /**
         * FIX: Use prepare() and suppress DirectQuery/NoCaching warnings 
         * as this is a schema operation.
         */
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_customer ) );

        if ( $exists != $t_customer ) {
            dbDelta( $sql );
        }
    }
}