<?php
/*
 * Plugin Name: Altec Lansing Site Support
 * Description: Adds support for components of Altec Lansing site.
 * Version: 1.0.0
 * Author: Joseph Tisa
 * Text Domain: altec-lansing-support
 * License: GPLv2 or later
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die();
}

DEFINE( 'SEP_POSITION', 26 ); /* Below Comments. */
DEFINE( 'ALS_URL', plugins_url( '/', __FILE__ ) );
DEFINE( 'ALS_PATH', plugin_dir_path( __FILE__ ) );
DEFINE( 'ALS_VERSION', '1.0' );

require_once( ALS_PATH . '/includes/functions/helper-functions.php' );

// Load ACF plugin.
include_once( ALS_PATH . '/includes/plugins/advanced-custom-fields/acf.php' );

require_once( ALS_PATH . '/includes/classes/class.ALProduct.php' );
require_once( ALS_PATH . '/includes/classes/class.ALFieldGroups.php' );
require_once( ALS_PATH . '/includes/classes/class.ALJumboSlide.php' );
require_once( ALS_PATH . '/includes/classes/class.ALCategoryWidget.php' );
require_once( ALS_PATH . '/includes/classes/class.ALProductMenuWidget.php' );
require_once( ALS_PATH . '/includes/classes/class.ALNewsWidget.php' );
require_once( ALS_PATH . '/includes/classes/class.ALRecentProductsWidget.php' );
require_once( ALS_PATH . '/includes/classes/class.ALAdminWidgetManager.php' );

/**
 * Widget Registration
 *
 * Intended to be added to 'widgets_init' hook.
 */
function als_register_widgets() {
    register_widget( 'ALCategoryWidget' );
    register_widget( 'ALProductMenuWidget' );
    register_widget( 'ALNewsWidget' );
    register_widget( 'ALRecentProductsWidget' );
}

/**
 * Adds additional categories.
 *
 * Called during plugin initialization.
 */
function als_add_plugin_categories() {
    $tax_values = array(
        array( __('News', 'altec-lansing-support'), 'category', __('The latest Altec Lansing news.', 'altec-lansing-support') ),
    );

    als_add_custom_taxonomies( $tax_values );
}

/**
 * Creates database tables to store information specific to plugin.
 *
 * Called on activation of plugin.
 */
function als_create_db_tables() {
    global $wpdb;
    $charset_collate = '';

    if ( ! empty($wpdb->charset) )
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    if ( ! empty($wpdb->collate) )
        $charset_collate .= " COLLATE {$wpdb->collate}";

    // Maximum length of email address is (arguably) 254.
    $table_name = $wpdb->prefix . "subscribers";

    // Make sure to retain the two spaces after 'PRIMARY KEY' and keep each
    // field on its own line. These peculiarities are required by dbDelta.
    $ddl = "CREATE TABLE $table_name (
        ID int(10) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $ddl );
}

function als_initialize_options() {
    add_option( 'als_products_per_page', 25 );
    add_option( 'als_remove_data_on_uninstall', 0 );
}

// Plugin Activation (i.e., Installation)
function als_activation() {
    // Some security checking.
    if ( !current_user_can( 'activate_plugins' ) )
        return;
    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';

    // Ensure that request to activate is coming from an Administration Screen.
    check_admin_referer( "activate-plugin_{$plugin}" );

    // Activate ALProduct custom type.
    ALS_Product()->activate();

    // Create necessary database tables.
    als_create_db_tables();

    als_initialize_options();
}

// Plugin Initialization
function als_initialization() {
    // Initialize Product custom type.
    ALS_Product()->initialize();

    ALS_JumboSlide()->initialize();

    // Add News Category.
    als_add_plugin_categories();
}

function als_add_dashboard_widgets() {
    ALS_AdminWidgets()->add_dashboard_widgets();
}

// Hook activation.
register_activation_hook( __FILE__, 'als_activation' );

// Hook initialization and loading actions.
add_action( 'init', 'als_initialization' );
add_action( 'widgets_init', 'als_register_widgets' );
add_action( 'wp_dashboard_setup', 'als_add_dashboard_widgets' );
add_action( 'pre_get_posts', 'configure_main_query' );


function als_ajax_output_subscribers() {
    $response_data = '';

    if ( !wp_verify_nonce($_POST['als_security'],'output_subscribers') ) {
        $response_data = __( 'Action not allowed.', 'altec-lansing-support' );
        echo $response_data;
    } else {

        if ( current_user_can( 'manage_options' ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . "subscribers";

            $wpdb->hide_errors();

            $addresses = $wpdb->get_results( "SELECT email FROM $table_name" );

            if ( $wpdb->num_rows > 0 ) {
                ob_start();
                $filename = 'subscribers-' . date('Y.m.d.His') .'.csv';

                header( 'Content-Description: File Transfer' );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
                header( 'Content-type: text/csv' );

                $fp = fopen('php://output', 'w');

                fputcsv( $fp, array('Email Addresses') );

                foreach ( $addresses as $address ) {
                    fputcsv( $fp, array( trim( $address->email ) ) );
                }
                fclose($fp);
                ob_end_flush();
            }
            else {
                $response_data = __( 'No email addresses have been added yet.', 'altec-lansing-support' );
                set_transient( 'output_subscribers_response', $response_data, MINUTE_IN_SECONDS );
                wp_safe_redirect( admin_url() );
            }
        }
    }
    die();
}
add_action( 'wp_ajax_output_subscribers', 'als_ajax_output_subscribers' );

function enqueue_product_edit_script() {
    if( 'product' === get_query_var('post_type') ) {
        wp_enqueue_script( 'als-edit-product', ALS_URL . 'includes/scripts/als-edit-product.js', array( 'jquery' ), ALS_VERSION, true );
    }
}

function enqueue_product_single_new_script() {
    global $post;
    if( 'product' === $post->post_type ) {
        wp_enqueue_script( 'als-edit-product', ALS_URL . 'includes/scripts/als-edit-product.js', array( 'jquery' ), ALS_VERSION, true );
    }
}

//Add a script for the posts edit screen.
add_action('admin_print_scripts-edit.php', 'enqueue_product_edit_script');

// Add script for single product edit screen.
add_action('admin_print_scripts-post.php', 'enqueue_product_single_new_script');

// And add script for new product product edit screen.
add_action('admin_print_scripts-post-new.php', 'enqueue_product_single_new_script');

function als_enqueue_functions_script() {
    if (!is_admin() ) {
        wp_register_script( 'als-functions', ALS_URL . 'includes/scripts/als-functions.js', array( 'jquery' ), THEME_VERSION, true );
        wp_enqueue_script( 'als-functions' );
    }
}
add_action( 'wp_enqueue_scripts', 'als_enqueue_functions_script' );

function als_enqueue_styles() {
    if ( !is_admin() ) {
        wp_register_style( 'als-styles', ALS_URL . 'includes/styles/als-style.css', array(), ALS_VERSION, 'all' );
        wp_enqueue_style( 'als-styles' );
    }
}
add_action( 'wp_enqueue_scripts', 'als_enqueue_styles' );

/**
 * Track recent product views. (Routine adapted from WooCommerce.)
 */
function als_track_product_views() {
    if ( ! is_singular( 'product' ) )
        return;

    global $post;

    if ( empty( $_COOKIE['als_recently_viewed'] ) )
        $viewed_products = array();
    else
        $viewed_products = (array) explode( '|', $_COOKIE['als_recently_viewed'] );

    if ( ! in_array( $post->ID, $viewed_products ) )
        $viewed_products[] = $post->ID;

    if ( sizeof( $viewed_products ) > 10 )
        array_shift( $viewed_products );

    // Store for session only.
    als_setcookie( 'als_recently_viewed', implode( '|', $viewed_products ) );
}
add_action( 'template_redirect', 'als_track_product_views', 20 );

function configure_main_query( $query ) {
    // Change how Loop queries work for Products (overide default query return limit).
    if ( !is_admin() && $query->is_main_query() ) {
        if ( ( isset( $query->query_vars['post_type'] ) ) && ( $query->query_vars['post_type'] == 'product' ) ) {
            $ppp = get_option( 'als_products_per_page', 25 );
            $query->set('order', 'ASC');
            $query->set('orderby', 'title');
            $query->set('posts_per_page', $ppp);
            return;
        }
        // Exclude News category from Blog home page (if there is one).
        if ( $query->is_home() ) {

            // You actually have to use the NAME, not the slug.
            $news_cat_id = get_cat_ID( 'News' );

            $query->set('category__not_in', array(
                    $news_cat_id
                )
            );
        }
    }
}