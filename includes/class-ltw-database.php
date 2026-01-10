<?php
/**
 * LTW Database helper: create required tables
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LTW_Database {

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

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }

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

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$t_customer}'" ) != $t_customer ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }
}
