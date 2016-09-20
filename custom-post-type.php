<?php

class Bullhorn_Custom_Post_Type {

	/**
	 * Bullhorn_Custom_Post_Type constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ) );

		add_filter( 'post_updated_messages', array( __CLASS__, 'post_updated_messages' ) );

		add_action( 'contextual_help', array( __CLASS__, 'contextual_help' ), 10, 2 );

		add_action( 'the_content', array( __CLASS__, 'the_content' ) );
		add_action( 'the_content', array( __CLASS__, 'add_json_ld_to_content' ) );

		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
	}

	/**
	 * @return bool
	 */
	public static function init() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( empty( $settings ) or ! isset( $settings['listings_page'] ) ) {
			return false;
		}

		$labels = array(
			'name'               => _x( 'Job Listings', 'Taxonomy General Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'singular_name'      => _x( 'Job Listing', 'Taxonomy Singular Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_new'            => __( 'Add New' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_new_item'       => __( 'Add New Job Listing' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'edit_item'          => __( 'Edit Job Listing' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'new_item'           => __( 'New Job Listing' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'all_items'          => __( 'All Job Listings' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'view_item'          => __( 'View Job Listing' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'search_items'       => __( 'Search Job Listings' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'not_found'          => __( 'No job listings found' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'not_found_in_trash' => __( 'No job listings found in Trash' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Job Listings' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => trim( $settings['listings_page'], '/' ) ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		);
		register_post_type( 'bullhornjoblisting', $args );

		$labels = array(
			'name'                       => _x( 'Categories', 'Taxonomy General Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'singular_name'              => _x( 'Category', 'Taxonomy Singular Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'menu_name'                  => __( 'Categories' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'all_items'                  => __( 'All Categories' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'parent_item'                => __( 'Parent Category' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'parent_item_colon'          => __( 'Parent Category:' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'new_item_name'              => __( 'New Category' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_new_item'               => __( 'Add New Category' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'edit_item'                  => __( 'Edit Category' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'update_item'                => __( 'Update Category' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'separate_items_with_commas' => __( 'Separate categories with commas' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'search_items'               => __( 'Search categories' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_or_remove_items'        => __( 'Add or remove categories' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'bullhorn_category', 'bullhornjoblisting', $args );

		$labels = array(
			'name'                       => _x( 'States', 'Taxonomy General Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'singular_name'              => _x( 'State', 'Taxonomy Singular Name' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'menu_name'                  => __( 'States' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'all_items'                  => __( 'All States' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'parent_item'                => __( 'Parent State' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'parent_item_colon'          => __( 'Parent State:' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'new_item_name'              => __( 'New State' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_new_item'               => __( 'Add New State' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'edit_item'                  => __( 'Edit State' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'update_item'                => __( 'Update State' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'separate_items_with_commas' => __( 'Separate states with commas' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'search_items'               => __( 'Search states' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'add_or_remove_items'        => __( 'Add or remove states' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'choose_from_most_used'      => __( 'Choose from the most used states' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);
		$args   = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'bullhorn_state', 'bullhornjoblisting', $args );

		return true;
	}


	/**
	 * Add filter to ensure the text Job Listing, or job listing, is displayed
	 * when user updates a job listing.
	 *
	 * @param $messages
	 *
	 * @return mixed
	 */
	public static function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['bullhornjoblisting'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Job listing updated. <a href="%s">View job listing</a>', 'your_text_domain' ), esc_url( get_permalink( $post_ID ) ) ),
			2  => __( 'Custom field updated.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			3  => __( 'Custom field deleted.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			4  => __( 'Job listing updated.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Job listing restored to revision from %s' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Job listing published. <a href="%s">View job listing</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ), esc_url( get_permalink( $post_ID ) ) ),
			7  => __( 'Job listing saved.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			8  => sprintf( __( 'Job listing submitted. <a target="_blank" href="%s">Preview job listing</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf( __( 'Job listing scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview job listing</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Job listing draft updated. <a target="_blank" href="%s">Preview job listing</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}


	/**
	 * Display contextual help for Job Listings.
	 *
	 * @param $contextual_help
	 * @param $screen_id
	 *
	 * @return string
	 */
	public static function contextual_help( $contextual_help, $screen_id ) {
		if ( 'bullhornjoblisting' === $screen_id ) {
			$contextual_help =
				'<p>' . __( 'Things to remember when adding or editing a job listing:' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Specify the correct genre such as Mystery, or Historic.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</li>' .
				'<li>' . __( 'Specify the correct writer of the job listing. Remember that the Author module refers to you, the author of this job listing review.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'If you want to schedule the job listing to be published in the future:' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Under the Publish module, click on the Edit link next to Publish.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</li>' .
				'<li>' . __( 'Change the date to the date to actual publish this article, then click on Ok.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</li>' .
				'</ul>' .
				'<p><strong>' . __( 'For more information:' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p>' .
				'<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p>';
		} elseif ( 'edit-job-listing' == $screen_id ) {
			$contextual_help =
				'<p>' . __( 'This is the help screen displaying the table of job listings.' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p>';
		}

		return $contextual_help;
	}


	/**
	 * Filters the content for single job posts to insert a customizable link
	 * to the form where the user can submit their resume.
	 *
	 * @param null $content
	 *
	 * @return null|string
	 */
	public static function the_content( $content = null ) {

		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['form_page'] ) and 'bullhornjoblisting' === get_post_type() ) {
			$bullhorn_job_id = get_post_meta( get_the_ID(), 'bullhorn_job_id', true );
			if ( is_single() ) {
				if ( apply_filters( 'bullhorn_show_form_on_job_page', true ) ) {
					$content .= sprintf( '<h4>%s</h4><br />' , apply_filters( 'wp-bullhorn_apply_now_text', __( 'Apply for this Now', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ) );

					$settings = (array) get_option( 'bullhorn_settings' );
					$inputs = ( isset( $settings['default_shortcode'] ) ) ? $settings['default_shortcode'] : array( 'name', 'email', 'phone' );
					$content .= \bullhorn_2_wp\Shortcodes::render_cv( $inputs );
				} else {
					$content .= '<a class="button" href="' . get_permalink( $settings['form_page'] ) . '?position=' . absint( $bullhorn_job_id ) . '">' . __( 'Submit Resume' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</a>';
				}
			} else {
				$content .= '<a class="button" href="' . get_permalink( $settings['form_page'] ) . '?position=' . absint( $bullhorn_job_id ) . '">' . __( 'Apply Now' , 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</a>';
			}
		}
		if ( isset( $_GET['bh_applied'] ) ) {
			$content = sprintf( ' <h3 style="text-align: center">%s</h3>', __( 'Thank you for uploading you resume.' ) ) . $content;
		}

		return apply_filters( 'wp_bullhorn_the_content_filter', $content );
	}

	public static function add_json_ld_to_content( $content = null ) {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['form_page'] ) and 'bullhornjoblisting' === get_post_type() ) {
			if ( is_single() ) {
				$bullhorn_json_ld = get_post_meta( get_the_ID(), 'bullhorn_json_ld', true );

				$depth   = apply_filters( 'bullhorn_json_ld_depth', 1024 );
				$options = apply_filters( 'bullhorn_json_ld_options', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {
					$options = $options | JSON_PRETTY_PRINT;
				}
				$bullhorn_json_ld = apply_filters( 'bullhorn_json_ld_full_array', $bullhorn_json_ld );

				$content = PHP_EOL . sprintf( '<script type="application/ld+json">%s</script>', wp_json_encode( $bullhorn_json_ld, $options, $depth ) ) . PHP_EOL . $content;
			}
		}

		return $content;
	}


	/**
	 * @param $open
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function comments_open( $open, $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'bullhornjoblisting' === $post_type ) {
			return false;
		}

		return $open;
	}
}

$bullhorn_custom_post_type = new Bullhorn_Custom_Post_Type;
