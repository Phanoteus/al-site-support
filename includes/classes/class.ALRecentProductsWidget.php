<?php
/**
 * Class module for Altec Lansing Recent Products Widget
 *
 * (Adapted from WooCommerce Plugin.)
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALRecentProductsWidget extends WP_Widget {

    public function __construct() {

        parent::__construct(
            'al-recent-products-widget', // Base ID
            __('AL Recent Products Widget', 'altec-lansing-support'), // Name
            array(
                'description'   => __( 'Widget to display list of recently viewed products.', 'altec-lansing-support' ),
                'classname'     => 'al-recent-products-widget') // Args
        );
    }

    public function widget($args, $instance) {

        $viewed_products = ! empty( $_COOKIE['als_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['als_recently_viewed'] ) : array();
        $viewed_products = array_filter( array_map( 'absint', $viewed_products ) );

        if ( empty( $viewed_products ) )
            return;

        ob_start( NULL, 0, PHP_OUTPUT_HANDLER_STDFLAGS );
        extract( $args );

        if ( !isset( $instance['title'] ) ) {
            $title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
        }
        $title = ( ! empty( $title ) ) ? $title : __( 'Recently Viewed Products', 'altec-lansing-support' );

        $number = empty( $instance['number'] ) ? 10 : absint( $instance['number'] );

        $query_args = array(
            'posts_per_page' => $number,
            'no_found_rows' => true,
            'post_status' => 'publish',
            'post_type' => 'product',
            'post__in' => $viewed_products,
            'orderby' => 'rand',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
        );

        $rvp = new WP_Query($query_args);

        if ( $rvp->have_posts() ) {

            echo $before_widget;

            echo $before_title . $title . $after_title;

            echo '<ul class="recent-products">';

            while ( $rvp->have_posts()) {
                $rvp->the_post();
                echo '<li>';
                echo '<a href="' . esc_url( get_permalink() ) . '" title="' . esc_attr( get_the_title() ) . '">';

                if ( has_post_thumbnail() ) :
                    $image_src = wp_get_attachment_image_src( get_post_thumbnail_id(),'thumbnail' );
                    echo '<img src="' . $image_src[0] . '" width="100%" />'; // Allows for image to be responsive.
                endif;

                echo '<h5>' . get_the_title() . '</h5>';
                echo '</a>';
                // Maybe echo 'price' or something here.
                echo '</li>';
            }

            echo '</ul>';

            echo $after_widget;
        }

        wp_reset_postdata();

        $buffer_content = ob_get_clean();
        echo $buffer_content;
    }

    public function form( $instance ) {
        // Creates the options form for the widget on the Admin screen.
        $defaults = array(
                    'title' => __( 'Recently Viewed Products', 'altec-lansing-support' ),
                    'number' => '10' );
        $instance = wp_parse_args( (array) $instance, $defaults ); // Merge default values with incoming arguments (if any).

        $title = $instance['title'];
        $number = $instance['number'];


        $title_label = __( 'Title:', 'altec-lansing-support');
        $title_field_id = $this->get_field_id( 'title' );
        $title = esc_attr( $title );
        $title_field_name = $this->get_field_name( 'title' );

        $items_label = __( 'Number of Products to Display:', 'altec-lansing-support' );
        $items_field_id = $this->get_field_id( 'number' );
        $items_field_name = $this->get_field_name( 'number' );
        ?>

        <p><label for="<?php echo $title_field_id; ?>"><?php echo $title_label; ?></label>
        <input class="widefat" id="<?php echo $title_field_id; ?>" name="<?php echo $title_field_name; ?>" type="text" value="<?php echo $title; ?>" /></p>
        <p><label for="<?php echo $items_field_id; ?>"><?php echo $items_label; ?></label>
        <select name="<?php echo $items_field_name; ?>" id="<?php echo $items_field_id; ?>">
            <option value="1" <?php echo selected( $number, 1 ); ?>>1</option>
            <option value="2" <?php echo selected( $number, 2 ); ?>>2</option>
            <option value="3" <?php echo selected( $number, 3 ); ?>>3</option>
            <option value="4" <?php echo selected( $number, 4 ); ?>>4</option>
            <option value="5" <?php echo selected( $number, 5 ); ?>>5</option>
            <option value="6" <?php echo selected( $number, 6 ); ?>>6</option>
            <option value="7" <?php echo selected( $number, 7 ); ?>>7</option>
            <option value="8" <?php echo selected( $number, 8 ); ?>>8</option>
            <option value="9" <?php echo selected( $number, 9 ); ?>>9</option>
            <option value="10" <?php echo selected( $number, 10 ); ?>>10</option>
        </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        // Processes and saves widget options.
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['number'] = strip_tags( $new_instance['number'] );
        return $instance;
    }
}
