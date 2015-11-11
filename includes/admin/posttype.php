<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Posttype admin class.
 *
 * @since 1.0.0
 *
 * @package Blox
 * @author  Nicholas Diego
 */
class Blox_Posttype_Admin {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;


    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;


    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;


    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Blox_Lite_Main::get_instance();

        // Remove quick editing from the global blocks post type row actions.
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );

        // Manage post type columns.
        add_filter( 'manage_edit-blox_columns', array( $this, 'admin_column_titles' ) );
        add_filter( 'manage_blox_posts_custom_column', array( $this, 'admin_column_data' ), 10, 2 );

        // Update post type messages.
        add_filter( 'post_updated_messages', array( $this, 'messages' ) );
        
        // Conditionally add the Local Blocks column to admin pages
        // Note: Need to fire immediately after admin_init, hence current_screen
        add_action( 'current_screen', array( $this, 'local_blocks_columns' ) );
	}
	
	
    /**
     * Customize the post columns for the blox post type.
     *
     * @since 1.0.0
     *
     * @param array $columns  The default columns.
     * @return array $columns Amended columns.
     */
    public function admin_column_titles( $columns ) {

        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'title'     	=> __( 'Title', 'blox' ),
            'content' 		=> __( 'Content', 'blox' ),
            'position'		=> __( 'Position', 'blox' ),
            'location'    	=> __( 'Location', 'blox' ),
            'modified'  	=> __( 'Last Modified', 'blox' ),
            'date'      	=> __( 'Date', 'blox' )
        );

        return apply_filters( 'blox_admin_column_titles', $columns );
    }


    /**
     * 
     *
     * @since 1.0.0
     *
     * @global object $post  The current post object.
     * @param string $column The name of the custom column.
     * @param int $post_id   The current post ID.
     */
    public function admin_column_data( $column, $post_id ) {

        global $post;
        $post_id = absint( $post_id );
        
        $block_data = get_post_meta( $post_id, '_blox_content_blocks_data', true );

        switch ( $column ) {
            case 'content' :
                if ( ! empty( $block_data['content']['content_type'] ) && array_key_exists( $block_data['content']['content_type'], $this->get_content_types() ) ) {
                	echo ucfirst( $block_data['content']['content_type'] );
                } else {
                	echo ucfirst( $block_data['content']['content_type'] ) . ' - <span style="color:#a00;font-style:italic;">' . __( 'Error', 'blox' ) . '</span>';
                }	
                break;
                
            case 'position' :
               	 if ( ! empty( $block_data['position']['position_type'] ) ) {
               	 	if ( $block_data['position']['position_type'] == 'default' ) {
            			echo blox_get_option( 'global_default_position', 'genesis_after_header' );
            		} else if ( $block_data['position']['custom'] ) {
            			echo ! empty( $block_data['position']['custom']['position'] ) ? $block_data['position']['custom']['position'] : '<span style="color:#a00;font-style:italic;">' . __( 'Error', 'blox' ) . '</span>';
            		}
            	}
                break;
                
            case 'location' :
               	$type = ! empty( $block_data['location']['location_type'] ) ? $block_data['location']['location_type'] : '';	
                
                // More location information to come...
                switch ( $type ) {
					case 'all' :
						$output = __( 'All', 'blox' );
						break;
					case 'show_selected' :
						$output = __( 'Selected', 'blox' );
						break;
					case 'hide_selected' :
						$output = __( 'Selected', 'blox' );
						break;
					default :
						$output = '<span style="color:#a00;font-style:italic;">' . __( 'Error', 'blox' ) . '</span>';
						break;
                }
                
                echo $output;
                
                break;

            case 'modified' :
                the_modified_date();
                break;
        }
        
        // Hook in additional column settings
        do_action( 'blox_admin_column_data', $column, $post_id, $block_data );

    }


    /**
     * Filter out unnecessary row actions from the global blocks post table.
     *
     * @since 1.0.0
     *
     * @param array $actions  Default row actions.
     * @param object $post    The current post object.
     * @return array $actions Amended row actions.
     */
    public function row_actions( $actions, $post ) {

        if ( isset( get_current_screen()->post_type ) && 'blox' == get_current_screen()->post_type ) {
            unset( $actions['inline hide-if-no-js'] );
        }

        return apply_filters( 'blox_row_actions', $actions, $post );
    }


    /**
     * Contextualizes the post updated messages.
     *
     * @since 1.0.0
     *
     * @global object $post    The current post object.
     * @param array $messages  Array of default post updated messages.
     * @return array $messages Amended array of post updated messages.
     */
    public function messages( $messages ) {

        global $post;

        // Contextualize the messages.
        $messages['blox'] = apply_filters( 'blox_block_messages',
            array(
                0  => '',
                1  => __( 'Global block updated.', 'blox' ),
                2  => __( 'Global block custom field updated.', 'blox' ),
                3  => __( 'Global block custom field deleted.', 'blox' ),
                4  => __( 'Global block updated.', 'blox' ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( 'Global block restored to revision from %s.', 'blox' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => __( 'Global block published.', 'blox' ),
                7  => __( 'Global block saved.', 'blox' ),
                8  => __( 'Global block submitted.', 'blox' ),
                9  => sprintf( __( 'Global block scheduled for: <strong>%1$s</strong>.', 'blox' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
                10 => __( 'Global block draft updated.', 'blox' )
            )
        );

        return apply_filters( 'blox_post_messages', $messages, $post );
    }


	/**
     * Conditionally add the Local Blocks column to admin pages
     *
     * @since 1.0.0
     *
     * @global string $typenow The current post type.
     */
	public function local_blocks_columns() {
		global $typenow;

		$enabled_pages = blox_get_option( 'local_enabled_pages', array( 'page', 'post' ) );
		
		// Note this does not work on some custom post types in other plugins, need to explore reason...
		if ( in_array( $typenow, $enabled_pages ) ) {
			add_filter( 'manage_' . $typenow . '_posts_columns', array( $this, 'local_blocks_column_title' ), 5 );
			add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'local_blocks_column_data' ), 10, 2);
        }
	}


	/**
     * Add content to the Local Blocks column
     *
     * @since 1.0.0
     *
     * @param array $columns  An array of all admin columns for give post type
     * @return array $columns The array of admin columns with our new one appended
     */
	public function local_blocks_column_title( $columns ) {
	  	$new_columns = array();
	  	
	  	// Specify where we want to put our column
  		foreach( $columns as $key => $title ) {
    		$new_columns[$key] = $title;
    		if ( $key == 'title' ) { // Put the Local Blocks column after the Title column
      			$new_columns['local_blocks'] = __( 'Local Blocks', 'blox' );
      		}
  		}
  		return $new_columns;
	}
	
	
	/**
     * Add content to the Local Blocks column
     *
     * @since 1.0.0
     *
     * @param string $column_name  The name of the column to be added
     * @param string $post_ID      The current post's ID
     */
	public function local_blocks_column_data( $column_name, $post_ID ) {
		if ( $column_name == 'local_blocks' ) {
			
			// Get all local blocks
			$local_blocks = get_post_meta( $post_ID, '_blox_content_blocks_data', true );

			if ( ! empty( $local_blocks ) ) {
				echo count( $local_blocks );
				// Possibly add more than just the number of block in the future...
			} else {
			    echo '<span aria-hidden="true">—</span>';
			}
		}
	}
	
	
	/**
     * Helper function for retrieving the available content types.
     *
     * @since 1.0.0
     *
     * @return array Array of image size data.
     */
    public function get_content_types() {

        $instance = Blox_Common::get_instance();
        return $instance->get_content_types();
    }


    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The class object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Posttype_Admin ) ) {
            self::$instance = new Blox_Posttype_Admin();
        }

        return self::$instance;
    }
}

// Load the posttype admin class.
$blox_posttype_admin = Blox_Posttype_Admin::get_instance();