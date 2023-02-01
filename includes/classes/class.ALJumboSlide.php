<?php
/**
 * Class module for Altec Lansing Jumbo Slide content type.
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALJumboSlide {

    protected static $instance = NULL;

    /**
     * Used to access class instance.
     *
     * @return  Object of this class (ALJumboSlide).
     */
    public static function get_instance() {
        ( NULL === self::$instance ) and ( self::$instance = new self );
        return self::$instance;
    }

    /**
     * Constructor. Intentionally empty and public.
     */
    public function __construct() {}

    public function initialize() {
        $this->create_jumboslide_content_type();
    }

    private function create_jumboslide_content_type() {
        register_post_type( 'jumboslide',
            array(
                'labels' => array(
                    'name' => _x( 'Jumbo Slides', 'post type general name', 'altec-lansing-support' ),
                    'singular_name' => _x( 'Jumbo Slide', 'post type singular name', 'altec-lansing-support' ),
                    'add_new' => _x( 'Add New Jumbo Slide', 'jumboslide', 'altec-lansing-support'),
                    'all_items' => __( 'All Jumbo Slides', 'altec-lansing-support' ),
                    'add_new_item' => __( 'Add New Jumbo Slide', 'altec-lansing-support' ),
                    'edit' => __( 'Edit', 'altec-lansing-support' ),
                    'edit_item' => __( 'Edit Jumbo Slide', 'altec-lansing-support' ),
                    'new_item' => __( 'New Jumbo Slide', 'altec-lansing-support' ),
                    'view_item' => __( 'View Jumbo Slide', 'altec-lansing-support' ),
                    'search_items' => __( 'Search Jumbo Slides', 'altec-lansing-support' ),
                    'not_found' => __( 'No slides found.', 'altec-lansing-support' ),
                    'not_found_in_trash' => __( 'No slides found in Trash.', 'altec-lansing-support' ),
                    'parent_item_colon' => '',
                    'menu_name' => _x( 'Jumbo Slides', 'Admin menu name', 'altec-lansing-support' ),
                ),
                'description' => __( 'Content type for adding slides to front page Jumbo Slider.', 'altec-lansing-support' ),
                'public' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => true,
                'show_ui' => true,
                'query_var' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'menu_position' => (SEP_POSITION + 3),
                'menu_icon' => 'dashicons-slides',
                'rewrite'   => array( 'slug' => 'jumboslides', 'with_front' => false ),
                'has_archive' => false,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array( 'title', 'editor', 'thumbnail' )
            )
        );

        // Add filter for update messages related to the JumboSlide type.
        add_filter( 'post_updated_messages', array( $this, 'jumboslide_messages' ) );
    }


    /****************************************************************
    * Public Callback Functions Added to Hooks
    *****************************************************************/

    public function jumboslide_messages( $messages ) {
        global $post, $post_ID;
        $messages['jumboslide'] = array(
            0 => '',
            1 => sprintf( __('Jumbo Slide updated. <a href="%s">View Jumbo Slides</a>'), esc_url( get_permalink($post_ID) ) ),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => __('Jumbo Slide updated.'),
            5 => isset($_GET['revision']) ? sprintf( __('Jumbo Slide restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __('Jumbo Slide published. <a href="%s">View Jumbo Slide</a>'), esc_url( get_permalink($post_ID) ) ),
            7 => __('Jumbo Slide saved.'),
            8 => sprintf( __('Jumbo Slide submitted. <a target="_blank" href="%s">Preview Jumbo Slide</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Jumbo Slide scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Jumbo Slide</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Jumbo Slide draft updated. <a target="_blank" href="%s">Preview Jumbo Slide</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );
        return $messages;
    }

}

/**
 * Returns instance of ALJumboSlide class.
 *
 * @since  1.0
 * @return The ALJumboSlide instance
 */
function ALS_JumboSlide() {
    return ALJumboSlide::get_instance();
}