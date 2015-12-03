<?php

class Bullhorn_Custom_Post_Type {

	/**
	 * Bullhorn_Custom_Post_Type constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		add_action( 'contextual_help', array( $this, 'contextual_help' ), 10, 2 );

		add_action( 'the_content', array( $this, 'the_content' ) );

		add_filter( 'comments_open', array( $this, 'comments_open' ), 10, 2 );
	}

	/**
	 * @return bool
	 */
	public function init() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( empty( $settings ) or ! isset( $settings['listings_page'] ) ) {
			return false;
		}

		$labels = array(
			'name'               => 'Job Listings',
			'singular_name'      => 'Job Listing',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Job Listing',
			'edit_item'          => 'Edit Job Listing',
			'new_item'           => 'New Job Listing',
			'all_items'          => 'All Job Listings',
			'view_item'          => 'View Job Listing',
			'search_items'       => 'Search Job Listings',
			'not_found'          => 'No job listings found',
			'not_found_in_trash' => 'No job listings found in Trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Job Listings',
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
			'name'                       => _x( 'Categories', 'Taxonomy General Name', 'bullhorn' ),
			'singular_name'              => _x( 'Category', 'Taxonomy Singular Name', 'bullhorn' ),
			'menu_name'                  => __( 'Categories', 'bullhorn' ),
			'all_items'                  => __( 'All Categories', 'bullhorn' ),
			'parent_item'                => __( 'Parent Category', 'bullhorn' ),
			'parent_item_colon'          => __( 'Parent Category:', 'bullhorn' ),
			'new_item_name'              => __( 'New Category', 'bullhorn' ),
			'add_new_item'               => __( 'Add New Category', 'bullhorn' ),
			'edit_item'                  => __( 'Edit Category', 'bullhorn' ),
			'update_item'                => __( 'Update Category', 'bullhorn' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'bullhorn' ),
			'search_items'               => __( 'Search categories', 'bullhorn' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'bullhorn' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'bullhorn' ),
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
			'name'                       => _x( 'States', 'Taxonomy General Name', 'bullhorn' ),
			'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'bullhorn' ),
			'menu_name'                  => __( 'States', 'bullhorn' ),
			'all_items'                  => __( 'All States', 'bullhorn' ),
			'parent_item'                => __( 'Parent State', 'bullhorn' ),
			'parent_item_colon'          => __( 'Parent State:', 'bullhorn' ),
			'new_item_name'              => __( 'New State', 'bullhorn' ),
			'add_new_item'               => __( 'Add New State', 'bullhorn' ),
			'edit_item'                  => __( 'Edit State', 'bullhorn' ),
			'update_item'                => __( 'Update State', 'bullhorn' ),
			'separate_items_with_commas' => __( 'Separate states with commas', 'bullhorn' ),
			'search_items'               => __( 'Search states', 'bullhorn' ),
			'add_or_remove_items'        => __( 'Add or remove states', 'bullhorn' ),
			'choose_from_most_used'      => __( 'Choose from the most used states', 'bullhorn' ),
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

	 * @param $messages
	 *
	 * @return mixed
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['bullhornjoblisting'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Job listing updated. <a href="%s">View job listing</a>', 'your_text_domain' ), esc_url( get_permalink( $post_ID ) ) ),
			2  => __( 'Custom field updated.', 'your_text_domain' ),
			3  => __( 'Custom field deleted.', 'your_text_domain' ),
			4  => __( 'Job listing updated.', 'your_text_domain' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Job listing restored to revision from %s', 'your_text_domain' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Job listing published. <a href="%s">View job listing</a>', 'your_text_domain' ), esc_url( get_permalink( $post_ID ) ) ),
			7  => __( 'Job listing saved.', 'your_text_domain' ),
			8  => sprintf( __( 'Job listing submitted. <a target="_blank" href="%s">Preview job listing</a>', 'your_text_domain' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf( __( 'Job listing scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview job listing</a>', 'your_text_domain' ),
				// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Job listing draft updated. <a target="_blank" href="%s">Preview job listing</a>', 'your_text_domain' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
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
	public function contextual_help( $contextual_help, $screen_id ) {
		if ( 'bullhornjoblisting' === $screen_id ) {
			$contextual_help =
				'<p>' . __( 'Things to remember when adding or editing a job listing:', 'bullhorn_text_domain' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Specify the correct genre such as Mystery, or Historic.', 'bullhorn_text_domain' ) . '</li>' .
				'<li>' . __( 'Specify the correct writer of the job listing. Remember that the Author module refers to you, the author of this job listing review.', 'bullhorn_text_domain' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'If you want to schedule the job listing to be published in the future:', 'bullhorn_text_domain' ) . '</p>' .
				'<ul>' .
				'<li>' . __( 'Under the Publish module, click on the Edit link next to Publish.', 'bullhorn_text_domain' ) . '</li>' .
				'<li>' . __( 'Change the date to the date to actual publish this article, then click on Ok.', 'bullhorn_text_domain' ) . '</li>' .
				'</ul>' .
				'<p><strong>' . __( 'For more information:', 'bullhorn_text_domain' ) . '</strong></p>' .
				'<p>' . __( '<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>', 'bullhorn_text_domain' ) . '</p>' .
				'<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>', 'bullhorn_text_domain' ) . '</p>';
		} elseif ( 'edit-job-listing' == $screen_id ) {
			$contextual_help =
				'<p>' . __( 'This is the help screen displaying the table of job listings.', 'bullhorn_text_domain' ) . '</p>';
		}

		return $contextual_help;
	}


	/**
	 * Filters the content for single job posts to insert a customizable link
	 * to the form where the user can submit their resume.

	 * @param null $content
	 *
	 * @return null|string
	 */
	public function the_content( $content = null ) {
		$settings = (array) get_option( 'bullhorn_settings' );

		if ( isset( $settings['form_page'] ) and 'bullhornjoblisting' === get_post_type() ) {
			if ( is_single() ) {
				$content .= '<a class="button" href="' . get_permalink( $settings['form_page'] ) . '?position=' . urlencode( get_the_title() ) . '">Submit Resume</a>';
			} else {
				$content .= '<a class="button" href="' . get_permalink( $settings['form_page'] ) . '?position=' . urlencode( get_the_title() ) . '">Apply Now</a>';
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
	public function comments_open( $open, $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'bullhornjoblisting' === $post_type ) {
			return false;
		}

		return $open;
	}
}

$bullhorn_custom_post_type = new Bullhorn_Custom_Post_Type;
