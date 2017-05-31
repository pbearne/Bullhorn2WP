<?php


class Bullhorn_WP_Job_Manager_Custom_Post_Type {

	public function __construct() {

		add_action( 'init', array( __CLASS__, 'init' ) );

		add_action( 'the_content', array( __CLASS__, 'add_json_ld_to_content' ) );

		add_filter( 'manage_' . Bullhorn_2_WP::$post_type_application . '_posts_columns', array( 'Bullhorn_Custom_Post_Type', 'set_custom_edit_columns' ) );
		add_action( 'manage_' . Bullhorn_2_WP::$post_type_application . '_posts_custom_column', array( 'Bullhorn_Custom_Post_Type', 'custom_column' ), 10, 2 );
		add_filter( 'manage_edit-' . Bullhorn_2_WP::$post_type_application . '_sortable_columns', array( 'Bullhorn_Custom_Post_Type', 'sortable_column' ) );

		add_action( 'init' , array( 'Bullhorn_Custom_Post_Type', 'sniff_post' ) );

		add_action( 'pre_get_posts',  array( 'Bullhorn_Custom_Post_Type', 'orderby' ) );
	}

	public static function init() {

//		if ( ! taxonomy_exists( 'job_listing_region' ) ) {

			$labels = array(
				'name'               => _x( 'Applications', 'Taxonomy General Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'singular_name'      => _x( 'Application', 'Taxonomy Singular Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'add_new'            => __( 'Add New', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'add_new_item'       => __( 'Add New Application', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'edit_item'          => __( 'Edit Application', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'new_item'           => __( 'New Application', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'all_items'          => __( 'All Applications', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'view_item'          => __( 'View Application', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'search_items'       => __( 'Search Applications', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'not_found'          => __( 'No Applications found', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'not_found_in_trash' => __( 'No Applications found in Trash', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Application', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			);
			$args   = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=' . Bullhorn_2_WP::$post_type_job_listing,
				'query_var'          => true,
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'custom-fields' ),
			);
			register_post_type( Bullhorn_2_WP::$post_type_application, $args );

			$labels = array(
				'name'                       => _x( 'States', 'Taxonomy General Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'menu_name'                  => __( 'States', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'all_items'                  => __( 'All States', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'parent_item'                => __( 'Parent State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'parent_item_colon'          => __( 'Parent State:', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'new_item_name'              => __( 'New State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'add_new_item'               => __( 'Add New State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'edit_item'                  => __( 'Edit State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'update_item'                => __( 'Update State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'separate_items_with_commas' => __( 'Separate states with commas', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'search_items'               => __( 'Search states', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'add_or_remove_items'        => __( 'Add or remove states', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'choose_from_most_used'      => __( 'Choose from the most used states', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
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
			register_taxonomy( Bullhorn_2_WP::$taxonomy_listing_state, Bullhorn_2_WP::$post_type_job_listing, $args );
//		}
	}

	public static function add_json_ld_to_content( $content = null ) {

		if ( get_post_type() === Bullhorn_2_WP::$post_type_job_listing ) {
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

}