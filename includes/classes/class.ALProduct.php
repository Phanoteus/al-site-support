<?php
/**
 * Class module for Altec Lansing Product content type.
 *
 * @package  Altec Lansing Site Support
 * @since  1.0.0
 */

// Abort if this file is called directly.
if ( ! defined( 'WPINC' ) ) {
    die();
}

class ALProduct {

    protected static $instance = NULL;

    /**
     * Used to access class instance.
     *
     * @return  Object of this class (ALProduct).
     */
    public static function get_instance() {
        ( NULL === self::$instance ) and ( self::$instance = new self );

        return self::$instance;
    }

    /**
     * Constructor. Intentionally empty and public.
     */
    public function __construct() {}

    public function activate() {
        // Setting up custom types on activation (i.e., for the first time) so that types will be available
        // without having to reload site. (WordPress has already loaded, so hooking to init here won't do the job.)
        $this->initialize();
        flush_rewrite_rules(); // Flushing rewrite rules ONLY on activation, not on init, or rules will be flushed on every page load.

        $xml_file = ALS_PATH . '/includes/classes/data/acfg-technical-details.xml';
        $this->create_acf_field_group( $xml_file, false, true );

        $xml_file = ALS_PATH . '/includes/classes/data/acfg-color-views.xml';
        $this->create_acf_field_group( $xml_file, false, true );

        $xml_file = ALS_PATH . '/includes/classes/data/acfg-orientation-views.xml';
        $this->create_acf_field_group( $xml_file, false, true );
    }

    public function initialize() {

        $this->create_product_content_type();
        $this->create_product_taxonomies();
        $this->add_product_type_values();
        $this->add_product_quality_values();


        // Removing standard excerpt editor for 'product' content type.
        add_action( 'add_meta_boxes_product', array( $this, 'remove_excerpt_editor' ), 10, 1 );
        add_action( 'add_meta_boxes_product', array( $this, 'add_product_metadata_editor' ), 10, 1 );
        // Replacing standard excerpt editor with 'product summary' editor (which is really just a modified version of the excerpt editor).
        add_action( 'edit_form_after_title', array( $this, 'replace_excerpt_editor'), 10, 1);
        add_action( 'save_post', array( $this, 'save_product_metadata' ), 10, 2 );
        add_filter( 'manage_edit-product_columns', array( $this, 'set_product_edit_columns' ), 10, 1 );
        add_action( 'manage_posts_custom_column', array( $this, 'product_custom_columns' ), 10, 2 );
    }

    private function create_product_content_type() {
        register_post_type( 'product',
            array(
                'labels' => array(
                    'name' => _x( 'Products', 'post type general name', 'altec-lansing-support' ),
                    'singular_name' => _x( 'Product', 'post type singular name', 'altec-lansing-support' ),
                    'add_new' => _x( 'Add New Product', 'product', 'altec-lansing-support'),
                    'all_items' => __( 'All Products', 'altec-lansing-support' ),
                    'add_new_item' => __( 'Add New Product', 'altec-lansing-support' ), /* Add New Display Title */
                    'edit' => __( 'Edit', 'altec-lansing-support' ), /* Edit Dialog Title */
                    'edit_item' => __( 'Edit Product', 'altec-lansing-support' ), /* Edit Display Title */
                    'new_item' => __( 'New Product', 'altec-lansing-support' ), /* New Display Title */
                    'view_item' => __( 'View Product', 'altec-lansing-support' ), /* View Display Title */
                    'search_items' => __( 'Search Products', 'altec-lansing-support' ), /* Search Custom Type Title */
                    'not_found' => __( 'No products found.', 'altec-lansing-support' ),
                    'not_found_in_trash' => __( 'No products found in Trash.', 'altec-lansing-support' ),
                    'parent_item_colon' => '',
                    'menu_name' => _x( 'Products', 'Admin menu name', 'altec-lansing-support' ),
                ), // End of labels array.
                'description' => __( 'Content type for adding information for individual products.', 'altec-lansing-support' ), /* Custom Type Description */
                'public' => true,
                'publicly_queryable' => true,
                'exclude_from_search' => false,
                'show_ui' => true,
                'query_var' => true,
                'show_in_menu' => true,
                'menu_position' => (SEP_POSITION + 1),
                'menu_icon' => 'dashicons-microphone',
                'rewrite'   => array( 'slug' => 'al-products', 'with_front' => false ), // May change to 'product'.
                'has_archive' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt')
            )
        );

        // Add filter for update messages related to the Product type.
        add_filter( 'post_updated_messages', array( $this, 'product_messages' ) );
    }

    private function create_product_taxonomies() {
        register_taxonomy( 'al_product_type',
            array('product'),
            array('hierarchical' => true, // Checkboxes will be displayed next to terms in Admin UI.
                'labels' => array(
                    'name' => __( 'Product Types', 'altec-lansing-support' ),
                    'singular_name' => __( 'Product Type', 'altec-lansing-support' ),
                    'search_items' =>  __( 'Search Product Types', 'altec-lansing-support' ),
                    'all_items' => __( 'All Product Types', 'altec-lansing-support' ),
                    'parent_item' => __( 'Parent Product Type', 'altec-lansing-support' ),
                    'parent_item_colon' => __( 'Parent Product Type:', 'altec-lansing-support' ),
                    'edit_item' => __( 'Edit Product Type', 'altec-lansing-support' ),
                    'update_item' => __( 'Update Product Type', 'altec-lansing-support' ),
                    'add_new_item' => __( 'Add New Product Type', 'altec-lansing-support' ),
                    'new_item_name' => __( 'New Product Type Value', 'altec-lansing-support' ),
                    'menu_name' => __( 'Product Types' )
                ),
                'show_admin_column' => true,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => array(
                    'slug' => 'al-product-types',
                    'with_front' => false
                    ),
            )
        );
        register_taxonomy_for_object_type( 'al_product_type', 'product' );

        register_taxonomy( 'al_product_quality',
            array('product'),
            array('hierarchical' => true,
                'labels' => array(
                    'name' => __( 'Product Qualities', 'altec-lansing-support' ),
                    'singular_name' => __( 'Product Quality', 'altec-lansing-support' ),
                    'search_items' =>  __( 'Search Product Qualities', 'altec-lansing-support' ),
                    'all_items' => __( 'All Product Qualities', 'altec-lansing-support' ),
                    'parent_item' => __( 'Parent Product Quality', 'altec-lansing-support' ),
                    'parent_item_colon' => __( 'Parent Product Quality:', 'altec-lansing-support' ),
                    'edit_item' => __( 'Edit Product Quality', 'altec-lansing-support' ),
                    'update_item' => __( 'Update Product Quality', 'altec-lansing-support' ),
                    'add_new_item' => __( 'Add New Product Quality', 'altec-lansing-support' ),
                    'new_item_name' => __( 'New Product Quality Value', 'altec-lansing-support' ),
                    'menu_name' => __( 'Product Qualities' )
                ),
                'show_admin_column' => false,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => array(
                    'slug' => 'al-product-qualities',
                    'with_front' => false
                    ),
            )
        );
        register_taxonomy_for_object_type( 'al_product_quality', 'product' );
    }

    private function add_product_type_values() {
        $tax_values = array(
            array( __('Bluetooth Speakers', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing Bluetooth Speakers', 'altec-lansing-support') ),
            array( __('Headphones', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing Headphones', 'altec-lansing-support') ),
            array( __('Speakers', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing Speakers', 'altec-lansing-support') ),
            array( __('Home Theatre', 'altec-lansing-support'), 'al_product_type', __('Products to Furnish a Home Theatre', 'altec-lansing-support') ),
            array( __('Mobile Solutions', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing Mobile Solutions', 'altec-lansing-support') ),
            array( __('PC Accessories', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing PC Accessories', 'altec-lansing-support') ),
            array( __('Accessories', 'altec-lansing-support'), 'al_product_type', __('Altec Lansing Accessories', 'altec-lansing-support') )
        );

        als_add_custom_taxonomies( $tax_values );
    }

    private function add_product_quality_values() {
        $tax_values = array(
            array( __('Dustproof', 'altec-lansing-support'), 'al_product_quality', __('Product resistant to dust.', 'altec-lansing-support') ),
            array( __('Sandproof', 'altec-lansing-support'), 'al_product_quality', __('Product resistant to sand.', 'altec-lansing-support') ),
            array( __('Shockproof', 'altec-lansing-support'), 'al_product_quality', __('Product can withstand shocks.', 'altec-lansing-support') ),
            array( __('Snowproof', 'altec-lansing-support'), 'al_product_quality', __('Product resistant to snow and cold.', 'altec-lansing-support') ),
            array( __('Waterproof', 'altec-lansing-support'), 'al_product_quality', __('Product resistant to water.', 'altec-lansing-support') )
        );

        als_add_custom_taxonomies( $tax_values );
    }

    private function create_acf_field_group( $xml_file, $allow_duplicates = false, $update_if_exists = false ) {

        $xml_string = file_get_contents($xml_file);
        global $wpdb;

        if ( $xml_string === false )
            return false;

        // Parse and load RSS XML into an object.
        $content = simplexml_load_string( $xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);

        // Get children nodes (based on 'wp' prefix).
        $field_group = $content->channel->item->children('wp', true);
        if ( count( $field_group ) == 0 )
            return false;

        $field_group_title = $content->channel->item->title;

        // Create field group content type (acf) post in posts table.
        $field_group_definition = array(
            'post_type'   => 'acf',
            'post_title'  => $field_group_title,
            'post_name'   => $field_group->post_name,
            'post_status' => 'publish',
            'comment_status' => $field_group->comment_status,
            'ping_status' => $field_group->ping_status,
            'post_author' => 1,
            'menu_order' => $field_group->menu_order
        );

        $current_field_group = get_page_by_title( $field_group_title, 'OBJECT', 'acf' );

        // Create a new acf field group if it doesn't exist.
        if ( !$current_field_group || $allow_duplicates == true ) {
            $post_id = wp_insert_post( $field_group_definition );
        }
        // If it exists, update the post ID.
        else {
            $post_id = $current_field_group->ID;
        }

        if( $update_if_exists === true ) {
            $wpdb->hide_errors();
            $table_name = $wpdb->prefix . 'postmeta';

            // Remove any field group rules that may have already been added.
            $wpdb->delete( $table_name, array( 'post_id' => $post_id, 'meta_key' => 'rule' ) );

            foreach ( $field_group as $row ) {

                if( count($row) > 0) {
                    $data_format = ( $row->meta_key == '_edit_last' ) ? '%d' : '%s';

                    if ( $row->meta_key == 'rule' ) {
                        $result = 0;
                    }
                    else {
                        $result = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND meta_key = %s",
                                $post_id,
                                $row->meta_key
                            )
                        );
                    }

                    if ( $result ) {
                        // Not inserting (or updating) metadata that already exists because additional fields may
                        // have been added to the field group by an administrator.
                        continue;
                    }
                    else {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'post_id' => $post_id,
                                'meta_key' => $row->meta_key,
                                'meta_value' => $row->meta_value
                            ),
                            array(
                                '%d',
                                '%s',
                                $data_format
                            )
                        );
                    }
                }
            }
        }
        return true;
    }


    /****************************************************************
    * Public Callback Functions Added to Hooks
    *****************************************************************/

    public function product_messages( $messages ) {
        global $post, $post_ID;
        $messages['product'] = array(
            0 => '',
            1 => sprintf( __('Product updated. <a href="%s">View Products</a>'), esc_url( get_permalink($post_ID) ) ),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => __('Product updated.'),
            5 => isset($_GET['revision']) ? sprintf( __('Product restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __('Product published. <a href="%s">View Product</a>'), esc_url( get_permalink($post_ID) ) ),
            7 => __('Product saved.'),
            8 => sprintf( __('Product submitted. <a target="_blank" href="%s">Preview Product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Product scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Product</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Product draft updated. <a target="_blank" href="%s">Preview Product</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );
        return $messages;
    }

    public function add_product_metadata_editor( $post ) {
        // Including these args for extensibility; not in use now.
        $args = array(
            'branding' => __('Altec Lansing', 'altec-lansing-support' ),
            'help_link' => ''
            );

        add_meta_box(
            'product-metadata-editor', // $id
            __( 'Product Options', 'altec-lansing-support' ), // $title
            array( $this, 'render_product_metadata_editor'), // $callback
            $post->post_type, // $page (i.e., associated post-type). Will be 'product' here.
            'normal', // $context
            'high', // $priority
            $args);
    }

    public function render_product_metadata_editor( $post, $args) {
        $values = get_post_custom( $post->ID );
        $branding = $args['args']['branding'];
        $help_link = esc_url( $args['args']['help_link'] );
        $media = '';

        wp_nonce_field(
            __CLASS__ . '_product_metadata_editor', // Nonce value.
            'product-meta-box-nonce' // Name of nonce field.
            );

        if ( isset( $values['_product_media_link'] ) ) {
            $media = esc_url( trim( $values['_product_media_link'][0] ) );
        }

        ?>
        <p>
            <input type="checkbox" name="featured-product" id="featured-product" value="yes" <?php if ( isset ( $values['_featured'] ) ) checked( $values['_featured'][0], 'yes' ); ?> >
            <?php _e( 'Featured Product', 'altec-lansing-support' )?>
            <br><span class="metadata-editor-tip">[ <em><?php _e( "Checking this option will add this product to the Featured Products carousel on the home page.", 'altec-lansing-support' ); ?></em> ]</span>
        </p>
        <p>
            <label for="product-video-link"><?php _e( "Product Video Link: ", 'altec-lansing-support' ); ?><input type="text" class="widefat" name="product-video-link" id="product-video-link" value="<?php echo $media; ?>" /></label>
            <br><span class="metadata-editor-tip">[ <em><?php printf( __( 'Specify an optional link to a video for the product on YouTube, Vimeo, Flickr, or another %ssupported provider%s.', 'altec-lansing-support' ), '<a href="http://codex.wordpress.org/Embeds" target="_blank">', '</a>'); ?></em> ]</span>
        </p>
        <?php
    }

    public function save_product_metadata( $post_id, $post ) {
        // Abort if operation is an auto-save.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Abort if current user can't edit this post.
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Abort if nonce isn't set or can't be verified.
        if ( ! isset( $_POST['product-meta-box-nonce'] ) ) return;

        $nonce = $_POST['product-meta-box-nonce'];

        // Verify that the nonce and context are valid.
        if ( wp_verify_nonce( $nonce, __CLASS__ . '_product_metadata_editor' ) ) {

            // Validate and save "featured" indicator.
            if ( isset( $_POST['featured-product'] ) && !empty( $_POST['featured-product'] ) ) {
                update_post_meta( $post_id, '_featured', 'yes' );
            } else {
                if ( '' !== get_post_meta( $post_id, '_featured', true ) ) {
                    delete_post_meta( $post_id, '_featured' );
                }
            }

            // Validate and save product video link.
            if ( isset( $_POST['product-video-link'] ) && !empty( $_POST['product-video-link'] ) ) {
                update_post_meta( $post_id, '_product_media_link', esc_url_raw( $_POST['product-video-link'] ) );
            }
            else {
                if ( '' !== get_post_meta( $post_id, '_product_media_link', true ) ) {
                    delete_post_meta( $post_id, '_product_media_link' );
                }
            }
        }
    }

    public function set_product_edit_columns( $defaults ) {
        if ( isset( $defaults['cb'] ) )
            $cb = $defaults['cb'];

        unset( $defaults['cb'] );
        unset( $defaults['date'] );

        $columns = array(
                'cb' => $cb
            );

        $columns += $defaults;
        $columns += array(
                'model' => __( 'Model' ),
                'price' => __( 'Price' ),
                'featured' => __( 'Featured' )
            );
        return $columns;
    }

    public function product_custom_columns( $column, $id ) {
        switch ( $column ) {
            case 'model':
                $model = get_post_meta( $id, 'model', true );
                if ( ! empty( $model ) ) {
                    echo esc_html( $model );
                }
                else {
                    echo '&mdash;';
                }
                break;
            case 'price':
                $price = get_post_meta( $id, 'price', true );
                if ( ! empty( $price ) ) {
                    $price = esc_html( $price );
                    if ( is_numeric($price) ) {
                        $price = number_format($price, 2, '.', '');
                    }
                    else {
                        $price = 0;
                    }
                    $price = '$' . number_format($price, 2, '.', '');
                }
                else {
                    $price = '&mdash;';
                }
                echo $price;
                break;
            case 'featured':
                $featured = get_post_meta( $id, '_featured', true );
                if ( $featured == 'yes' ) {
                    echo 'Yes';
                }
                else {
                    echo 'No';
                }
                break;
        }
    }

    public function remove_excerpt_editor() {
        remove_meta_box( 'postexcerpt', 'product', 'normal' );
    }

    public function replace_excerpt_editor( $post ) {

        if ($post->post_type == 'product') {
            echo '<br><div id="postexcerpt" class="postbox">';
            echo '<div class="handlediv" title="Click to toggle"><br></div>';
            echo '<h3 class="hndle"><span>Product Summary</span></h3><div class="inside">';

            // Similar to post_excerpt_meta_box() function in WP core (metaboxes.php).
            ?>
            <label class="screen-reader-text" for="excerpt"><?php _e('Excerpt') ?></label><textarea rows="1" cols="40" name="excerpt" id="excerpt"><?php echo $post->post_excerpt; // textarea_escaped ?></textarea>
            <p><?php _e( 'Specify a brief product summary (excerpt) here.' ); ?></p>
            <?php

            echo '</div></div>';
        }
    }

}

/**
 * Returns the main instance of ALProduct.
 *
 * @since  1.0
 * @return The ALProduct instance
 */
function ALS_Product() {
    return ALProduct::get_instance();
}