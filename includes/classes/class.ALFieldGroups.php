<?php
/**
 * Class for providing Field Group information.
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALFieldGroups {

    protected static $instance = NULL;

    /**
     * Used to access class instance.
     *
     * @return  Object of this class (ALFieldGroups).
     */
    public static function get_instance() {
        ( NULL === self::$instance ) and ( self::$instance = new self );

        return self::$instance;
    }

    /**
     * Constructor. Intentionally empty and public.
     */
    public function __construct() {}

    protected static $field_groups = array();

    public function field_group_array() {
        if( function_exists( 'get_fields' ) ) {

            if ( !self::$field_groups ) {

                global $wpdb;
                $table_name = $wpdb->prefix . "posts";
                $query = "SELECT post_name, ID FROM $table_name WHERE post_type = 'acf' AND post_status = 'publish' and post_name in
                    (
                        'acf_product-technical-details',
                        'acf_product-color-views',
                        'acf_product-orientation-views'
                    );";

                $wpdb->hide_errors();

                $results = $wpdb->get_results( $query, ARRAY_N );
                if ( $results ) {
                    foreach ($results as $result) {
                        $keys[] = $result[0];
                        $values[] = $result[1];
                    }
                    self::$field_groups = array_combine($keys, $values);
                }
            }
        }

        return self::$field_groups;
    }

}

/**
 * Returns the ALFieldGroups instance.
 *
 * @since  1.0
 * @return The ALFieldGroups instance
 */
function ALS_FieldGroups() {
    return ALFieldGroups::get_instance();
}