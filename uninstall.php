<?php
/**
 * Altec Lansing Support Plugin Uninstall
 *
 */

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

// The code here removes options and any custom tables from database.

// WordPress will remove the files and plugin directory after this script runs.

global $wpdb;

$uninstall_data = get_option( 'als_remove_data_on_uninstall', 0 );

if ( $uninstall_data ) {
    $table_name = $wpdb->prefix . "subscribers";
    $wpdb->query($wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );
}

// Delete options
delete_option( 'als_products_per_page' );
delete_option( 'als_remove_data_on_uninstall' );
