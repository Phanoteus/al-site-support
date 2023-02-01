<?php
/**
 * Class module for Altec Lansing Enhanced Product Menu Widget
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALProductMenuWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'al-product-menu-widget', // Base ID
            __('AL Product Menu Widget', 'altec-lansing-support'), // Name
            array(
                'description'   => __( 'Widget to display enhanced product menu.', 'altec-lansing-support' ),
                'classname'     => 'al-product-menu-widget') // Args
        );

    }

    public function widget( $args, $instance ) {
        extract( $args );

        if ( $instance ) {
            $highlights = array_chunk($instance, 5);
        }

        $first_id = $highlights[0][0];

        // See if widget data is stored as transient.
        $transient_data = get_transient( 'als_pmw_' . md5( __CLASS__ ) );

        if ($transient_data === false ) {

            ob_start( NULL, 0, PHP_OUTPUT_HANDLER_STDFLAGS );
            echo $before_widget;

            echo '<div id="highlight-widget-module" class="highlight-widget-module">';

            echo '<div id="highlight-nav" class="highlight-nav"><ul>';

            foreach ( $highlights as $highlight ) {
                if ( $highlight[0] == $first_id ) {
                    $selected = 'class="highlight-selected"';
                }
                else {
                    $selected = 'class="highlight-unselected"';
                }
                echo '<li id="hl_' . $highlight[0] . '" ' . $selected . '>' . $highlight[1] . '</li>';
            }

            echo '</ul></div>';

            $query_args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 3,
                    'post_status' => 'publish',
                    'order_by' => 'title'
                );

            foreach ( $highlights as $highlight ) {
                $term_id = array_values($highlight)[0];
                $term_name = array_values($highlight)[1];
                $pid1 = array_values($highlight)[2];
                $pid2 = array_values($highlight)[3];
                $pid3 = array_values($highlight)[4];

                $query_args['tax_query'] = array(
                                    array(
                                        'taxonomy' => 'al_product_type',
                                        'field' => 'term_id',
                                        'terms' => $term_id
                                    )
                                );
                $query_args['post__in'] = array( $pid1, $pid2, $pid3 );

                if ( $term_id == $first_id ) {
                    $hide = 'class="highlight-show"';
                }
                else {
                    $hide = 'class="highlight-hide"';
                }

                echo '<div id="hw_' . $term_id . '" ' . $hide . '>';

                $products = get_posts( $query_args );

                if ( $products ) {
                    // Only 3 highlight slots available. Shouldn't have more than 3 products because the get_posts query
                    // is limited to 3 posts per page.
                    $highlight_limit = 3;
                    $counter = 0;
                    $image_cells = '';
                    $detail_cells = '';

                    echo '<div class="highlight-module">';
                    echo '<table class="highlight-table">';

                    foreach ( $products as $product ) {

                        $image_cells .= '<td>';

                        if ( has_post_thumbnail( $product->ID ) ) :
                            $image_cells .= '<div class="highlight-image-container">';
                            $image_src = wp_get_attachment_image_src( get_post_thumbnail_id( $product->ID ),'featured-widget' );
                            $image_cells .= '<div class="highlight-image"><a href="' . esc_url( get_permalink( $product->ID ) ) . '"><img src="' . $image_src[0] . '" width="100%" /></a></div>';
                            $image_cells .= '</div>';
                        endif;

                        $image_cells .= '</td>';

                        $detail_cells .= '<td><div class="highlight-details">';
                        $detail_cells .= '<div class="highlight-title"><a href="' . esc_url( get_permalink( $product->ID ) ) . '">' . $product->post_title . '</a></div>';
                        $detail_cells .= '<div class="highlight-category"><a title="' . sprintf(__( 'See all products in the %s category.', 'altec-lansing-support' ), $term_name ) . '" href="' . get_term_link( (int)$term_id, 'al_product_type' ) . '">' . $term_name . '</a></div>';
                        $detail_cells .= '</div></td>';

                        $counter++;
                        if ( $counter == 3 ) { break; }

                    } // End for

                    $limit = $highlight_limit - $counter;

                    // Always create table rows with three cells,
                    // even when there are fewer than three highlighted products in a category.
                    for ( $i=0; $i<$limit; $i++ ) {
                        $image_cells .= '<td></td>';
                        $detail_cells .= '<td></td>';
                    }

                    echo '<tr>' . $image_cells . '</tr>';
                    echo '<tr>' . $detail_cells . '</tr>';
                    echo '</table>';

                    echo '</div><!-- .highlight-module -->';
                }

                echo '</div><!-- #hw_ -->';
            }

            echo '</div><!-- .highlight-widget-module -->';
            echo $after_widget;

            $buffer_content = ob_get_clean();

            // Storing data for an hour.
            set_transient( 'als_pmw_' . md5( __CLASS__ ), $buffer_content, HOUR_IN_SECONDS );
            echo $buffer_content;
        }
        else {
            echo $transient_data;
        }
    }

    public function form( $instance ) {

        $highlights = array();

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

        if ( !empty( $type_list ) && !is_wp_error( $type_list ) ) {
            foreach ( $type_list as $type ) {
                $type_term_id = $type->term_id;
                $type_term_name = $type->name;
                $highlights[] = array (
                        $type_term_id,
                        $type_term_name,
                        0,
                        0,
                        0
                    );
            }
        }

        $saved_highlights = array_chunk($instance, 5);

        // Merge default values with incoming arguments (if any).
        $new_highlights = array_replace_recursive( $highlights, $saved_highlights );

        ?>
        <br>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e( 'Type', 'altec-lansing-support' ) ?></th>
                    <th><?php _e( 'Highlight 1', 'altec-lansing-support' ) ?></th>
                    <th><?php _e( 'Highlight 2', 'altec-lansing-support' ) ?></th>
                    <th><?php _e( 'Highlight 3', 'altec-lansing-support' ) ?></th>
                </tr>
            </thead>
            <tbody>
        <?php

            foreach ( $new_highlights as $highlight ) {

                $term_id = $highlight[0];
                $term_name = $highlight[1];

                $post_args = array(
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'al_product_type',
                                'field' => 'term_id',
                                'terms' => $term_id
                            )),
                        'post_status' => 'publish',
                        'ordery_by' => 'title'
                    );


                $products = get_posts( $post_args );
                $has_products = ( $products ) ? true : false;

                // Field Names and IDs
                $hidden1_name = $term_id . '_term_id';
                $hidden2_name = $term_id . '_term_name';
                $select1_name = $term_id . '_select1';
                $select2_name = $term_id . '_select2';
                $select3_name = $term_id . '_select3';


                echo "<tr><td>";
                echo $term_name;
                echo '<input type="hidden" name="' . $this->get_field_name( $hidden1_name ) . '" value="' . $term_id . '">';
                echo '<input type="hidden" name="' . $this->get_field_name( $hidden2_name ) . '" value="' . $term_name . '">';
                echo "</td>";


                if ( $has_products ) {
                    // Note: The inline styles suck here, but a very long product name can throw off the display in the Admin panel.
                    $select1 = '<td><select name="' . $this->get_field_name( $select1_name ) . '" style="max-width:200px"><option value="0">' . __( 'None', 'altec-lansing-support' ) . '</option>';
                    $select2 = '<td><select name="' . $this->get_field_name( $select2_name ) . '" style="max-width:200px"><option value="0">' . __( 'None', 'altec-lansing-support' ) . '</option>';
                    $select3 = '<td><select name="' . $this->get_field_name( $select3_name ) . '" style="max-width:200px"><option value="0">' . __( 'None', 'altec-lansing-support' ) . '</option>';

                    foreach ( $products as $product ) {
                        $selected1 = ( $highlight[2] == $product->ID ) ? 'selected="selected"' : '';
                        $selected2 = ( $highlight[3] == $product->ID ) ? 'selected="selected"' : '';
                        $selected3 = ( $highlight[4] == $product->ID ) ? 'selected="selected"' : '';

                        $select1 .= '<option value="' . $product->ID . '" ' . $selected1 . '>' . $product->post_title . '</option>';
                        $select2 .= '<option value="' . $product->ID . '" ' . $selected2 . '>' . $product->post_title . '</option>';
                        $select3 .= '<option value="' . $product->ID . '" ' . $selected3 . '>' . $product->post_title . '</option>';

                    }

                    $select1 .= '</select></td>';
                    $select2 .= '</select></td>';
                    $select3 .= '</select></td>';

                    echo $select1;
                    echo $select2;
                    echo $select3;

                }
                else {
                    echo '<td><select name="' . $this->get_field_name( $select1_name ) . '"><option value="0" selected="selected">' . __( 'None', 'altec-lansing-support' ) . '</option>';
                    echo '</select></td>';

                    echo '<td><select name="' . $this->get_field_name( $select2_name ) . '"><option value="0" selected="selected">' . __( 'None', 'altec-lansing-support' ) . '</option>';
                    echo '</select></td>';

                    echo '<td><select name="' . $this->get_field_name( $select3_name ) . '"><option value="0" selected="selected">' . __( 'None', 'altec-lansing-support' ) . '</option>';
                    echo '</select></td>';
                }

                echo'</tr>';

            }

        echo '</tbody></table><br>';

    }

    public function update( $new_instance, $old_instance ) {
        $instance = array_replace( $old_instance, $new_instance );
        // Flush transient so widget is recreated with any changes.
        delete_transient( 'als_pmw_' . md5( __CLASS__ ) );
        return $instance;
    }
}