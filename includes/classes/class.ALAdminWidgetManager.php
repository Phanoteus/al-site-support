<?php
/**
 * Class module for managing Altec Lansing dashboard widgets
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

class ALAdminWidgetManager {

    protected static $instance = NULL;

    /**
     * Used to access class instance.
     *
     * @return  Object of this class (ALAdminWidgetManager).
     */
    public static function get_instance() {
        ( NULL === self::$instance ) and ( self::$instance = new self );

        return self::$instance;
    }

    public function __construct() {}

    public function add_dashboard_widgets() {
        $subscribers_title = __( 'Subscribers', 'altec-lansing-support' );
        $subscribers_id = 'al-subscribers-widget';

        wp_add_dashboard_widget( $subscribers_id, $subscribers_title, array( $this, 'subscriber_widget' ) );
    }

    public function subscriber_widget() {
        echo "<h4>Download Email Addresses of Subscribers</h4>";
        ?>
        <br>
        <form id="email-list-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="POST">
            <?php wp_nonce_field( 'output_subscribers','als_security' ); ?>
            <input type="hidden" name="action" value="output_subscribers">
            <input type="submit" value="Generate and Download List" class="button-primary">
        </form>
        <?php
        $response = get_transient( 'output_subscribers_response' );
        if ( $response === false ) {
            $response = '';
        }
        else {
            delete_transient( 'output_subscribers_response' );
        }
        echo '<br><div id="admin-feedback">' . $response . '</div>';
    }
}

/**
 * Returns instance of ALAdminWidgetManager.
 *
 * @since  1.0
 * @return The ALAdminWidgetManager instance
 */
function ALS_AdminWidgets() {
    return ALAdminWidgetManager::get_instance();
}