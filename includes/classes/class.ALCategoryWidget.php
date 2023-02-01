<?php
/**
 * Class module for Altec Lansing Product Category Links Widget
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALCategoryWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'al-category-widget', // Base ID
            __('AL Product Category Widget', 'altec-lansing-support'), // Name
            array(
                'description'   => __( 'Widget to display links for Altec Lansing product categories.', 'altec-lansing-support' ),
                'classname'     => 'al-category-widget') // Args
        );

        // Hook category edit action to flush transit if categories are updated.
        add_action( 'edit_category', 'als_cw_flush_transient' );
    }

    public function als_cw_flush_transient() {
        delete_transient( 'als_cw_' . md5( 'al-category-widget' ) );
    }

    public function widget( $args, $instance ) {
        if ( isset( $instance['title'] ) ) {
            $title = apply_filters( 'widget_title', $instance['title'] );
        }
        $title = ( ! empty( $title ) ) ? $title : __( 'Products', 'altec-lansing-support' );

        // See if widget data is stored as transient.
        $transient_data = get_transient( 'als_cw_' . md5( 'al-category-widget' ) );

        if ($transient_data === false ) {

            ob_start( NULL, 0, PHP_OUTPUT_HANDLER_STDFLAGS );
            echo $args['before_widget'];

            echo '<div class="widget-module">';

            echo $args['before_title'] . $title . $args['after_title'];

            /**
             * Create a list (in a ul) of al-product-type category terms (if al-product-type is available).
             */
            $taxonomies = array( 'al_product_type' );

            $term_args = array(
                'parent' => '',
                'orderby'   => 'id',
                'order' => 'ASC',
                'hide_empty' => false,
                'hierarchical' => 1,
                'exclude' => '',
                'include' => '',
                'number' => '',
                'fields' => 'all'
            );

            $type_list = get_terms( $taxonomies, $term_args );

            if ( ! is_wp_error( $type_list ) ) {
                if ( ! empty( $type_list ) ) {

                    $types = '';
                    $link_display_name = '';
                    foreach ( $type_list as $type ) {
                        $link_display_name = trim( $type->name );

                        $types .= '<li class="al-product-type-item"><a title="' . sprintf(__( 'See all products in the %s category.', 'altec-lansing-support' ), $type->name ) . '" href="' . get_term_link($type) . '">' . $link_display_name . '</a></li>';
                    }

                    echo '<ul class="al-product-type-list">' . $types . '</ul>';
                }
                else {
                    echo '<p>Product Type category</p>';
                }
            }
            else {
                // Specified taxonomy doesn't exist.
                $error_string = $type_list->get_error_message();
                echo '<div class="widget-error"><p>' . $error_string . '</p></div>';
            }

            echo '</div><!-- .widget-module -->';
            echo $args['after_widget'];

            $buffer_content = ob_get_clean();

            // Storing data for a day.
            set_transient( 'als_cw_' . md5( 'al-category-widget' ), $buffer_content, DAY_IN_SECONDS );
            echo $buffer_content;
        }
        else
        {
            echo $transient_data;
        }
    }

    public function form( $instance ) {
        // Creates the options form for the widget on the Admin screen.
        $defaults = array( 'title' => __( 'Products', 'altec-lansing-support' ) );
        $instance = wp_parse_args( (array) $instance, $defaults ); // Merge default values with incoming arguments (if any).

        $title = $instance['title'];


        $title_label = __( 'Title:', 'altec-lansing-support');
        $field_id = $this->get_field_id( 'title' );
        $title = esc_attr( $title );
        $field_name = $this->get_field_name( 'title' );

        echo <<<_END
        <p><label for="$field_id">$title_label</label>
        <input class="widefat" id="$field_id" name="$field_name" type="text" value="$title" /></p>
_END;

    }

    public function update( $new_instance, $old_instance ) {
        // Processes and saves widget options.
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        // Flush transient data if changed.
        delete_transient( 'als_cw_' . md5( 'al-category-widget' ) );

        return $instance;
    }
}