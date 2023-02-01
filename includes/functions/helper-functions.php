<?php
/**
 * Helper functions.
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

/**
 * Programmatically adds terms to taxonomies. Taxonomies are created if they don't already exist.
 * @param array $taxes Takes an array of taxonomy value, taxonomy terms, and taxonomy descriptions.
 */
function als_add_custom_taxonomies($taxes) {
    if ( ! empty($taxes) ) {

        foreach ( $taxes as $tax ) {
            wp_insert_term(
                $tax[0], // Taxonomy Term
                $tax[1], // Taxonomy
                array( // Taxonomy Description
                    'description' => $tax[2],
                    'slug' => sanitize_title_with_dashes( $tax[0], $context = 'save' ),
                )
            );
        }

    }
}

/**
 * Sets a cookie. (Wrapper for setcookie using WP constants; adapted from WooCommerce.)
 *
 * @param  string  $name   Name of the cookie being set
 * @param  string  $value  Value of the cookie
 * @param  integer $expire Expiry of the cookie
 * @param  string  $secure Whether the cookie should be served only over https
 */
function als_setcookie( $name, $value, $expire = 0, $secure = false ) {
    if ( ! headers_sent() ) {
        setcookie( $name, $value, $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );
    } elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        headers_sent( $file, $line );
        trigger_error( "{$name} cookie cannot be set. Headers already sent by {$file} on line {$line}.", E_USER_NOTICE );
    }
}

/**
 * Validate YouTube ID using API.
 * @param  string $id Identifier to validate.
 * @return boolean
 */
function als_validate_yt_id( $id ) {
    $id = trim($id);
    $file = @file_get_contents('http://gdata.youtube.com/feeds/api/videos/' . $id);
    return !!$file;
}