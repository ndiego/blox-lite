<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Prints the content blocks to the frontend
 *
 * @since 1.0.0
 *
 * @package Blox
 * @author  Nicholas Diego
 */
class Blox_Frontend {

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
     * Holds an array of our active block content types
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $active_content_types = array();


    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

    	// Load the base class object.
        $this->base = Blox_Lite_Main::get_instance();

    	add_action( 'wp', array( $this, 'display_content_block' ) );
    }


	/**
     * Prints our content blocks on the frontend is a series of tests are passed
     *
     * @since 1.0.0
     */
	public function display_content_block() {

		global $post;
		
		// Get all of the Global Content Blocks
		$global_blocks = get_posts( array(
			'post_type'    => 'blox',
			'post_status'  => 'publish',
			'numberposts'  => -1  // We want all global blocks
		) );
		
		// echo print_r( $global_blocks );
		
		if ( ! empty( $global_blocks ) ) {
			foreach ( $global_blocks as $block ) {
				$block_data = get_post_meta( $block->ID, '_blox_content_blocks_data', true );
				$this->content_block_visibility( $block->ID, $block_data, true );
			}
		}

		// Check if local blocks are enabled
		$local_enable = blox_get_option( 'local_enable', true );

		// Local blocks only run on singular pages, so make sure it is a singular page before proceding and also that local blocks are enabled
		if ( $local_enable && is_singular() ) {

			// Get the post type of the current page, and our array of enabled post types
			$post_type     = get_post_type( get_the_ID() );
			$enabled_pages = blox_get_option( 'local_enabled_pages', array( 'post', 'page' ) );

			// Make sure local blocks are allowed on this post type
			if ( ! empty( $enabled_pages ) && in_array( $post_type, $enabled_pages ) ) {

				// Get all of the Local Content Blocks
				$local_blocks = get_post_meta( $post->ID, '_blox_content_blocks_data', true );

				if ( ! empty( $local_blocks ) ) {
					foreach ( $local_blocks as $id => $block ) {
						$this->content_block_visibility( $id, $block, false );
					}
				}
			}
		}
		
		// Now that our blocks have been added (maybe), check to see if we should run wp_enqueue_scripts
		if ( ! empty( $this->active_content_types ) ) {
		
			// We have active content blocks so enqueue the needed stypes and scripts
   			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts_styles' ) );
		}
	}


	/**
	 * Run the visibility test
	 *
     * @since 1.0.0
	 *
	 * @param int $id       The block id, if global, id = $post->ID otherwise it is a random local id
	 * @param array $block  Contains all of our block settings data
	 * @param bool $global  Tells whether our block is global or local
	 */
	public function content_block_visibility( $id, $block, $global ) {

		// Get the visibility data
		$visibility_data = isset( $block['visibility'] ) ? $block['visibility'] : '';
		
		// If we have visibility data, run the visibility test...
		if ( ! empty( $visibility_data ) ) {
		
			// Need to make this "true" to continue
			$visibility_test = false;

			// If the block is globally disabled, bail. Otherwise continue the visibility test
			if ( $visibility_data['global_disable'] == 1 ) {
				return;
			} else {

				if ( $visibility_data['role']['role_type'] == 'all' ) {
					$visibility_test = true;
				} else if ( $visibility_data['role']['role_type'] == 'public' && ! is_user_logged_in() ) {

					// The content block is public only display to those not logged in
					$visibility_test = true;

				} else if ( $visibility_data['role']['role_type'] == 'private' && is_user_logged_in() ) {

					// The content block is private and the user is logged in so display
					$visibility_test = true;

				} else if ( $visibility_data['role']['role_type'] == 'restrict' && is_user_logged_in() ) {

					// The user is logged in, so now we check what restrictions there are and if the user
					// has the permissions to view the content. Note: if no restrictions are set, don't show at all
					if ( ! empty( $visibility_data['role']['restrictions'] ) ) {

						// Create an array to hold our restrictions
						$restrictions = array();

						// Fill our restrictions array with the block's restrictions
						foreach ( $visibility_data['role']['restrictions'] as $restriction => $val ) {
							$restrictions[] = $restriction;
						}

						// Get info about the current user and bail if it's not an instance of WP_User
						$current_user = wp_get_current_user();
						if ( ! ( $current_user instanceof WP_User ) ) {
						   return;
						}

						// Get the user's role
						$user_roles = $current_user->roles;

						// See if user's role is one of the restricted ones. If so it will return an array
						// of matched roles. Count to make sure array length > 0. If so, show the block
						if ( count( array_intersect( $restrictions, $user_roles ) ) != 0 ) {
							$visibility_test = true;
						}
					}
				} else {
				
					// The role type does not seem to be set, so assume all and move on
					$visibility_test = true;
				}

			}

			// Action hook for modifying/adding visibility settings
			do_action( 'blox_content_block_visibility', $id, $block, $global );

			// If the block passes the visibility test, continue on to location
			if ( $visibility_test == true ) {
				$this->content_block_location( $id, $block, $global );
			}
			
		} else {
			
			// The visibility data does not exist, so move on
			$this->content_block_location( $id, $block, $global );
		}
	}
	
	
	/**
	 * If we are on a global block, run the location test, otherwise proceed
	 *
     * @since 1.0.0
	 *
	 * @param int $id       The block id, if global, id = $post->ID otherwise it is a random local id
	 * @param array $block  Contains all of our block settings data
	 * @param bool $global  Tells whether our block is global or local
	 */
	public function content_block_location( $id, $block, $global ) {

		if ( ! $global ) {

			// This is a local block so no location testing is required, proceed to block positioning
			$this->content_block_position( $id, $block, $global );

		} else {

			// Get our location data
			$location_data = $block['location'];

			if ( ! empty( $location_data['location_type'] ) ) {

				if ( $location_data['location_type'] == 'show_selected' ) {

					// Run our show on selected test
					$this->content_block_location_test( $location_data, $id, $block, $global, 'show' );

				} else if ( $location_data['location_type'] == 'hide_selected' ) {

					// Run our hide on selected test
					$this->content_block_location_test( $location_data, $id, $block, $global, 'hide' );

				} else {

					// If no test is selected, proceed to block positioning
					$this->content_block_position( $id, $block, $global );
				}
			}
		}

	}


	/**
	 * If "Show on Selected" location test, run the show test
	 *
	 * @since 1.0.0
	 *
	 * @param array $location_data   An array of all the location data/settings
	 * @param int $id       		 The block id, if global, id = $post->ID otherwise it is a random local id
	 * @param array $block  		 Contains all of our block settings data
	 * @param bool $global  		 Tells whether our block is global or local
	 * @param string $show_hide_test Either "show" or "hide"
	 */
	public function content_block_location_test( $location_data, $id, $block, $global, $show_hide_test ) {

		// Need to try and make this true in order for the block to display on the page
		$location_test = false;

		if ( ! empty( $location_data['selection'] ) ) {

			if ( in_array( 'front', $location_data['selection'] ) && is_front_page() == true ) {

				// For the actual front page of the website
				$location_test = true;

			} else if ( in_array( 'home', $location_data['selection'] ) && is_home() == true ) {

				// For the blog index page (doesn't necessarily need to be the "homepage")
				$location_test = true;

			} else if ( in_array( 'search', $location_data['selection'] ) && is_search() == true ) {

				// For any search archive
				$location_test = true;

				// POSSIBLY ADD MORE SEARCH OPTIONS IN THE FUTURE

			} else if ( in_array( '404', $location_data['selection'] ) && is_404() == true ) {

				// For the 404 page
				$location_test = true;

			} else if ( in_array( 'archive', $location_data['selection'] ) && is_archive() == true ) {

				if ( $location_data['archive']['select_type'] == 'all' ) {

					// Show the block on any archive page
					$location_test = true;

				} else if ( $location_data['archive']['select_type'] == 'selected' ) {
				
					// If our archive selection set is not empty, proceed...
					if ( ! empty( $location_data['archive']['selection'] ) ) {
                	
                		if ( in_array( 'datetime', $location_data['archive']['selection'] ) && is_date() ) {
                		
                			// We are on a Date/Time archive, so proceed...
							$location_test = true;
                		
                		} else if ( in_array( 'posttypes', $location_data['archive']['selection'] ) && is_post_type_archive() ) {
                		
                		    if ( $location_data['archive']['posttypes']['select_type'] == 'all' ) {

								// Show the block on any post type archive page
								$location_test = true;

							} else if ( $location_data['archive']['posttypes']['select_type'] == 'selected' ) {
							
								if ( ! empty( $location_data['archive']['posttypes']['selection'] ) ) {
									
									$posttypes = $location_data['archive']['posttypes']['selection'];
									
									foreach ( $posttypes as $posttype ) {
										$location_test = is_post_type_archive( $posttype ) ? true : false;
									}
								}
							}
                		
                		} else if ( in_array( 'authors', $location_data['archive']['selection'] ) && is_author() ) {

                			if ( $location_data['archive']['authors']['select_type'] == 'all' ) {

								// Show the block on any author archive page
								$location_test = true;

							} else if ( $location_data['archive']['authors']['select_type'] == 'selected' ) {
								
								if ( ! empty( $location_data['archive']['authors']['selection'] ) ) {
									
									// Get author and sort through selection to check
									$author = get_userdata( get_query_var('author') );
									
									if ( in_array( $author->id, $location_data['archive']['authors']['selection'] ) ) {
										
										// This author archive is part of the selection, so proceed...
										$location_test = true;
									
									}
								}
							}
                		
                		} else if ( in_array( 'category', $location_data['archive']['selection'] ) && is_category() ) {
							
							// Post categories need to be treated differently than normal taxonomies
                			if ( $location_data['archive']['category']['select_type'] == 'all' ) {

								// Show the block on any Post category archive page
								$location_test = true;

							} else if ( $location_data['archive']['category']['select_type'] == 'selected' ) {
								
								if ( ! empty( $location_data['archive']['category']['selection'] ) ) {
									
									// Get selected categories and loop through to see which category page we are on, if any
									$categories = $location_data['archive']['category']['selection'];
									
									foreach ( $categories as $category ) {
										$term_test[] = is_category( $category ) ? true : false;
									}
									
									$location_test = in_array( true, $term_test ) ? true : false;
								}
							}
                		
                		} else if ( in_array( 'post_tag', $location_data['archive']['selection'] ) && is_tag() ) {
							
							// Post tags need to be treated differently than normal taxonomies
                			if ( $location_data['archive']['post_tag']['select_type'] == 'all' ) {

								// Show the block on any Post tag archive page
								$location_test = true;

							} else if ( $location_data['archive']['post_tag']['select_type'] == 'selected' ) {
								
								if ( ! empty( $location_data['archive']['post_tag']['selection'] ) ) {
									
									// Get selected tags and loop through to see which tag page we are on, if any
									$tags = $location_data['archive']['post_tag']['selection'];
									
									foreach ( $tags as $tag ) {
										$term_test[] = is_tag( $tag ) ? true : false;
									}
									
									$location_test = in_array( true, $term_test ) ? true : false;
								}
							}
                		
                		} else {
                			
                			// Remove Date/Time, Authors, Post Types, Post Tags, and Post Categories from the selection (if they are there)
							$taxonomy_archives = array_diff( $location_data['archive']['selection'],  array( 'datetime', 'authors', 'posttypes', 'category', 'post_tag' ) );
							
							if ( ! empty( $taxonomy_archives ) ) {
								foreach ( $taxonomy_archives as $taxonomy_archive ) {
							
									if ( $location_data['archive'][$taxonomy_archive]['select_type'] == 'all' ) {

										// Show the block on any taxonomy's archive pages
										$location_test = true;

									} else if ( $location_data['archive'][$taxonomy_archive]['select_type'] == 'selected' ) {
							
										if ( ! empty( $location_data['archive'][$taxonomy_archive]['selection'] ) ) {
								
											// Get selected tags and loop through to see which tag page we are on, if any
											$terms = $location_data['archive'][$taxonomy_archive]['selection'];
											
											foreach ( $terms as $term ) {
												$term_object = get_term( $term, $taxonomy_archive );
												$term_test[] = is_tax( $taxonomy_archive, $term_object->slug ) ? true : false;
											}

											$location_test = in_array( true, $term_test ) ? true : false;
										}
									}
								}
							}
							
                		} // end archive test
                	
                	}
                	
				}

			} else if ( in_array( 'singles', $location_data['selection'] ) && is_singular() == true && is_front_page() == false ) {

				if ( $location_data['singles']['select_type'] == 'all' ) {

					// Show the block on any singles page
					$location_test = true;

				} else if ( $location_data['singles']['select_type'] == 'selected' ) {

					// If our singles selection set is not empty, proceed...
					if ( ! empty( $location_data['singles']['selection'] ) ) {

						// Our singles selection is not empty so now get the current page's id and post type
						$current_post_id   = get_the_ID();
						$current_post_type = get_post_type( $current_post_id );

						// If the current page's post type is in our selection, proceed...
						if ( in_array( $current_post_type, $location_data['singles']['selection'] ) ) {

							// Get our singles display type
							$display_type = ! empty( $location_data['singles'][$current_post_type]['select_type'] ) ? $location_data['singles'][$current_post_type]['select_type'] : false;

							if ( $display_type == 'all' ) {

								// Show all posts so proceed...
								$location_test = true;

							} else if ( $display_type == 'selected_posts' ) {

								// If our current post's id is one of the selected posts, proceed...
								if ( ! empty( $location_data['singles'][$current_post_type]['selection'] ) && in_array( $current_post_id, $location_data['singles'][$current_post_type]['selection'] ) ) {
									$location_test = true;
								}

							} else if ( $display_type == 'selected_taxonomies' ) {

								// Get all the taxonomies objects of the current post types
								$taxonomy_objects = get_object_taxonomies( $current_post_type, 'object' );

								// If the current post type actually has taxonomies, proceed...
								if ( ! empty( $taxonomy_objects ) ) {

									// Determine what taxonomy test we are running
									$taxonomy_test_type = ! empty( $location_data['singles'][$current_post_type]['taxonomies']['taxonomy_test'] ) ? $location_data['singles'][$current_post_type]['taxonomies']['taxonomy_test'] : false;

									// Setup our taxonomy test array
									$taxonomy_test = array();

									// Loop through all taxonomies and run the taxonomy test
									foreach ( $taxonomy_objects as $taxonomy_object ) {

										// Get the taxonomy type from the taxonomy object
										$taxonomy_type = $taxonomy_object->name;

										if ( ! empty( $location_data['singles'][$current_post_type]['taxonomies'][$taxonomy_type]['select_type'] ) ) {

											if ( $location_data['singles'][$current_post_type]['taxonomies'][$taxonomy_type]['select_type'] == 'selected_taxonomies' ) {

												// Get the taxonomy terms associated with the current post and the number of terms the current post has
												$current_post_term_list = wp_get_post_terms( $current_post_id, $taxonomy_type, array( "fields" => "ids" ) );
												$num_current_post_terms = count( $current_post_term_list );

												// Determine what taxonomy test we are running
												$taxonomy_test_type     = ! empty( $location_data['singles'][$current_post_type]['taxonomies']['taxonomy_test'] ) ? $location_data['singles'][$current_post_type]['taxonomies']['taxonomy_test'] : false;

												if ( ! empty( $location_data['singles'][$current_post_type]['taxonomies'][$taxonomy_type]['selection'] ) ) {

													// Get the number of selected terms
													$num_selected           = count( $location_data['singles'][$current_post_type]['taxonomies'][$taxonomy_type]['selection'] );

													// See how many of the custom post type's terms are part of the taxonomy selection
													$intersect_results      = array_intersect( $current_post_term_list, $location_data['singles'][$current_post_type]['taxonomies'][$taxonomy_type]['selection'] );
													$num_intersect_results  = ! empty( $intersect_results ) ? count( $intersect_results ) : null;

													if ( $taxonomy_test_type == 'loose' ) {

														if ( ! empty( $intersect_results ) ) {

															// If our current post's terms are part of the selected set (only one needs to match), proceed...
															$taxonomy_test[$taxonomy_type] = true;
														} else {

															// If our current post's terms are part NOT of the selected set (not even one matches), end...
															$taxonomy_test[$taxonomy_type] = false;
														}
													} else if ( $taxonomy_test_type == 'strict' ) {

														// All terms of the current post have to be in the selected set, but the post can have more terms than are selected
														if ( $num_selected <= $num_current_post_terms && $num_selected == $num_intersect_results ) {

															// If our current post's terms are part of the selected set (only one needs to match), proceed...
															$taxonomy_test[$taxonomy_type] = true;
														} else {

															// If our current post's terms are part NOT of the selected set (not even one matches), end...
															$taxonomy_test[$taxonomy_type] = false;
														}
													} else if ( $taxonomy_test_type == 'binding' ) {

														// Total number of terms the current post has, has to equal the number selected and the intersection of the current post's
														// terms and the selected terms has to equal the number of selected (i.e. all terms of the current post match those selected, no more, no less)
														if ( $num_selected == $num_current_post_terms && $num_selected == $num_intersect_results ) {

															// If our current post's terms are all part of the selected set (all need to match), proceed...
															$taxonomy_test[$taxonomy_type] = true;
														} else {

															// If our current post's terms are part NOT ALL of the selected set, end...
															$taxonomy_test[$taxonomy_type] = false;
														}
													}

												} else {

													// Determine what will happen if the user enabled "Selected by Taxonomy Terms" but didn't select any terms
													if ( $num_current_post_terms > 0 ) {

														if ( $taxonomy_test_type == 'binding' ) {
															// The webpage has terms but none were selected...so end
															$taxonomy_test[$taxonomy_type] = false;
														}

														// For "Loose" and "Strict" tests, a block can still be displayed if no terms are selected. They just need to have selected terms in other taxonomies...

													} else {

														// User enabled "Selected by Taxonomy Terms" but didn't select any terms, and the webpage has no terms...so end
														$taxonomy_test[$taxonomy_type] = false;
													}
												}
											}

											// If select type equals "ignore", we do not include the taxonomy as part of the taxonomy show/hide test

										}
									} 

									// Determine the outcome of the taxonomy test
									if ( $taxonomy_test_type == 'loose' ) {

										// For the loose test, we only need to have one 'true'. If we passed the taxonomy test, proceed...
										if ( ! empty( $taxonomy_test ) && in_array( true, $taxonomy_test ) ) {
											$location_test = true;
										}
									} else if ( $taxonomy_test_type == 'strict' || $taxonomy_test_type == 'binding' ) {

										// For the strict and binding tests, we can have no 'false'. If we passed the taxonomy test, proceed...
										if ( ! empty( $taxonomy_test ) && ! in_array( false, $taxonomy_test ) ) {
											$location_test = true;
										}
									} // end taxonomy test


								}
								
							} else if ( $display_type == 'selected_authors' ) {
									
								// Get author of page and sort through selection to check
								$author = get_queried_object()->post_author;

								// If our current post's id is one of the selected posts, proceed...
								if ( ! empty( $location_data['singles'][$current_post_type]['authors']['selection'] ) && in_array( $author, $location_data['singles'][$current_post_type]['authors']['selection'] ) ) {
									$location_test = true;
								}
																
							}
						}
					}
				}
			}

			// Determine whether to show or hide the block
			if ( $show_hide_test == 'show' ) {

				// Since we are running a show test, we only show the block if the location_test is true
				if ( $location_test == true ) {
					$this->content_block_position( $id, $block, $global );
				}
			} else if ( $show_hide_test == 'hide' ) {

				// Since we are running a hide test, we only show the block if the location_test is false
				if ( $location_test == false ) {
					$this->content_block_position( $id, $block, $global );
				}
			}
		}
	}


	/**
	 * Run the position test
	 *
     * @since 1.0.0
	 *
	 * @param int $id       The block id, if global, id = $post->ID otherwise it is a random local id
	 * @param array $block  Contains all of our block settings data
	 * @param bool $global  Tells whether our block is global or local
	 */
	public function content_block_position( $id, $block, $global ) {
		
		// Since this block passed all previous tests, it is considered active to pass it's content type to $active_content_types
		array_push( $this->active_content_types, $block['content']['content_type'] );

		// Get block position meta data
		$position_data = $block['position'];

		// Determine if we are using the default position or a custom position, and then set position and priority
		if ( empty( $position_data['position_type'] ) || $position_data['position_type'] == 'default' ) {
			$position = $global ? blox_get_option( 'global_default_position', 'genesis_after_header' ) : blox_get_option( 'local_default_position', 'genesis_after_header' );
			$priority = $global ? blox_get_option( 'global_default_priority', 15 ) : blox_get_option( 'local_default_priority', 15 );
		} else {
			$position = ! empty( $position_data['custom']['position'] ) ? $position_data['custom']['position'] : 'genesis_after_header';
			$priority = ! empty( $position_data['custom']['priority'] ) ? $position_data['custom']['priority'] : 1;
		}
		
		// Action hook for modifying/adding position settings
		do_action( 'blox_content_block_position', $id, $block, $global );
		
		// Load the final "printing" function
		add_action( $position, array( new Blox_Action_Storage( array( $id, $block, $global ) ), 'blox_frontend_content' ), $priority, 1 );
	}


	/**
     * Loads styles and scripts for our content blocks
     *
     * @since 1.0.0
     */
    public function frontend_scripts_styles() {

 		// Check to see if default css is globally disabled
        $global_disable_default_css = blox_get_option( 'disable_default_css', '' );

    	if ( empty( $global_disable_default_css ) ) {

        	// Load the Blox default frontend styles.
        	wp_register_style( $this->base->plugin_slug . '-default-styles', plugins_url( 'assets/css/default.css', $this->base->file ), array(), $this->base->version );
        	wp_enqueue_style( $this->base->plugin_slug . '-default-styles' );
		}
		
		// Fire a hook to load in custom metabox scripts and styles.
        do_action( 'blox_frontend_main_scripts_styles' );
		
		// Get all active content types, strip out any duplicates
		$active_content_types = array_unique( $this->active_content_types );
		
		// Now that critical scripts and styles have been enqueued, conditionally load content specific scripts and styles
		foreach ( $active_content_types as $type ) {
			do_action( 'blox_frontend_' . $type . '_scripts_styles' );
		}
    }


    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The class object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Blox_Frontend ) ) {
            self::$instance = new Blox_Frontend();
        }

        return self::$instance;
    }
}

// Load the frontend class.
$blox_frontend = Blox_Frontend::get_instance();



/**
 * Helper function that get the content from the content block
 * Needs to remain outside the Blox_Frontend class due to Blox_Action_Storage ---> Possibly find work around...
 *
 * @since 1.0.0
 *
 * @param array $args       These are any args associated to the action hook by default
 * @param array $parameters Additional args that we are passing to the action hook (whole point of using Block_Action_Storage)
 */
function blox_frontend_content( $args, $parameters ) {

	// Reassign the parameters
	$id 	= $parameters[0];
	$block	= $parameters[1];
	$global = $parameters[2];

	// Get the type of block we are working with
	$block_scope = $global ? 'global' : 'local';

	// Get block settings
	$content_data = $block['content'];
	$style_data   = $block['style'];

	//echo print_r( $content_data );

	// Get access to some of our helper functions
	$instance = Blox_Common::get_instance();

	// Check is default Blox CSS is globally disabled
    $global_disable_default_css = blox_get_option( 'disable_default_css', '' );

	// Should we include our default styles?
	if ( empty( $global_disable_default_css ) ) {
		if ( empty( $style_data['disable_default_css'] ) || ! $style_data['disable_default_css'] ) {
			$blox_theme = 'blox-theme-default';
		}
	}

	// If this block has its own custom css, add that before the block is displayed on the page
	if ( ! empty( $style_data['custom_css'] ) ) {
		echo '<style type="text/css">' . $instance->minify_string( html_entity_decode( $style_data['custom_css'] ) ) . '</style>';
	}

	// Make sure a content type is selected and then print our content block
	if ( ! empty( $content_data['content_type'] ) ) {
		
		if ( $content_data['content_type'] == 'raw' && $content_data['raw']['disable_markup'] == 1 ) {
			// Get the block content
			do_action( 'blox_print_content_' . $content_data['content_type'], $content_data, $id, $block, $global );
		} else {
			?>
			<div id="<?php echo 'blox_' . $block_scope . '_' . $id; ?>" class="blox-container <?php echo 'blox-content-' . $content_data['content_type']; ?> <?php echo $blox_theme; ?> <?php echo 'blox-scope-' . $block_scope; ?> <?php echo ! empty( $style_data['custom_classes'] ) ? esc_attr( $style_data['custom_classes'] ) : '';?>">
				<div class="blox-wrap <?php echo $style_data['enable_wrap'] == 1 ? 'wrap' : '';?>">
					<?php do_action( 'blox_print_content_' . $content_data['content_type'], $content_data, $id, $block, $global ); ?>
				</div>
			</div>
			<?php
		}
	}
}