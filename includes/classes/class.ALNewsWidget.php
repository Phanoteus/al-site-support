<?php
/**
 * Class module for Altec Lansing News Widget
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALNewsWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'al-news-widget', // Base ID
            __('AL News Widget', 'altec-lansing-support'), // Name
            array(
                'description'   => __( 'Widget to display posts marked with the News category.', 'altec-lansing-support' ),
                'classname'     => 'al-news-widget') // Args
        );
    }

    public function widget( $args, $instance ) {
        if ( isset( $instance['title'] ) ) {
            $title = apply_filters( 'widget_title', $instance['title'] );
        }

        $title = ( ! empty( $title ) ) ? $title : __( 'News', 'altec-lansing-support' );

        $news_items = empty( $instance['news_items'] ) ? 2 : absint( $instance['news_items'] );

        extract( $args );

        ob_start( NULL, 0, PHP_OUTPUT_HANDLER_STDFLAGS );
        echo $before_widget;

        echo '<div class="widget-title-container">';
        echo $before_title . $title . $after_title;
        echo '</div>';

        printf( "<div class='widget-module-%s'>", $news_items );


        // Get posts in News category.
        $news_query = new WP_Query(array(
            'category_name' => 'news',
            'posts_per_page' => $news_items,
            'post_status' => 'publish',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false
            )
        );

        // Loop through if there are News posts.
        if ($news_query->have_posts()) :
            while ($news_query->have_posts()) : $news_query->the_post();
                echo '<div class="news-widget">';
                echo '<div class="news-header"><div class="news-title"><a href="' . get_permalink() . '" rel="bookmark">' . get_the_title() . '</a></div>';
                echo '<div class="news-excerpt">' . get_the_excerpt() . '</div></div><!-- .news-header -->';
                echo '<div class="news-body">';

                if ( has_post_thumbnail() ) :
                    $image_src = wp_get_attachment_image_src( get_post_thumbnail_id(),'news-widget' );
                    echo '<img src="' . $image_src[0] . '" width="100%" />'; // Allows for image to be responsive.
                endif;

                echo '</div><!-- .news-body -->';
                echo '<div class="news-footer"><div class="news-date">' . get_the_date() . '</div>';
                echo '<a class="news-more" href="' . get_permalink() . '">More.</a></div></div>';
            endwhile;

            echo '</div><!-- .widget-module -->';

            $news_cat_id = get_cat_ID( 'news' );
            $news_cat_link = get_category_link( $news_cat_id );
            echo '<div class="news-more-container">';
            echo '<a class="news-more-category" href="' . esc_url( $news_cat_link ) . '" title="News">More News.</a>';
            echo '</div>';

        else :
            echo '<p>' . _e('No news items.', 'altec-lansing-support' ) . '</p>';
            echo '</div><!-- .widget-module -->';
        endif;
        echo $args['after_widget'];

        wp_reset_postdata(); // Called the_post() above.

        $buffer_content = ob_get_clean();
        echo $buffer_content;
    }

    public function form( $instance ) {
        // Creates the options form for the widget on the Admin screen.
        $defaults = array(
                    'title' => __( 'News', 'altec-lansing-support' ),
                    'news_items' => '2' );
        $instance = wp_parse_args( (array) $instance, $defaults ); // Merge default values with incoming arguments (if any).

        $title = $instance['title'];
        $news_items = $instance['news_items'];


        $title_label = __( 'Title:', 'altec-lansing-support');
        $title_field_id = $this->get_field_id( 'title' );
        $title = esc_attr( $title );
        $title_field_name = $this->get_field_name( 'title' );

        $items_label = __( 'Number of News Items to Display:', 'altec-lansing-support' );
        $items_field_id = $this->get_field_id( 'news_items' );
        $items_field_name = $this->get_field_name( 'news_items' );
        ?>

        <p><label for="<?php echo $title_field_id; ?>"><?php echo $title_label; ?></label>
        <input class="widefat" id="<?php echo $title_field_id; ?>" name="<?php echo $title_field_name; ?>" type="text" value="<?php echo $title; ?>" /></p>
        <p><label for="<?php echo $items_field_id; ?>"><?php echo $items_label; ?></label>
        <select name="<?php echo $items_field_name; ?>" id="<?php echo $items_field_id; ?>">
            <option value="1" <?php echo selected( $news_items, 1 ); ?>>1</option>
            <option value="2" <?php echo selected( $news_items, 2 ); ?>>2</option>
            <option value="3" <?php echo selected( $news_items, 3 ); ?>>3</option>
            <option value="4" <?php echo selected( $news_items, 4 ); ?>>4</option>
        </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        // Processes and saves widget options.
        $instance = $old_instance;
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['news_items'] = strip_tags( $new_instance['news_items'] );
        return $instance;
    }
}