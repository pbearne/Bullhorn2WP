<?php
/*
Plugin Name: Bullhorn Staffing and Recruitment Job Listing and CV/Resume uploader.
Plugin URI: https://github.com/pbearne/Bullhorn2WP
Description: This plugin adds Bullhorn jobs to a custom post (Job Listings) for front-end display. New and deleted jobs are synchronized every hour with Bullhorn. This is a 'pull' process only - any job created locally is not pushed to Bullhorn! Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files if a special layout is needed. Pull requests are accepted at https://github.com/pbearne/Bullhorn2WP
Version: 2.4.0
Author: Paul Bearne
Author URI: https://github.com/pbearne/
License: GPL2
Text Domain: bh-staffing-job-listing-and-cv-upload-for-wp
Domain Path: /languages
*/

/*
WP CRON SYNC: A new WP Cron Schedule was added (every 10 minutes), which is used to run the job sync (Bullhorn->WP CPT). The scheduling of WP Cron requires user interaction on the WordPress installation, if a WP Cron job is scheduled to run every 10 minutes but no pages have been loaded for 15 minutes, the job will be run immediately during the next page load.
WP CPT: This plugin adds the custom post type bullhornjoblisting, with a URL slug of /open-positions/ with archive turned on at that address and single job listings displayed at /open-positions/{post-slug}.
SYNCHRONIZING: Jobs are pulled in to the custom post type, and additional information is stored as meta data (jobOrderID, createdDate, employmentType, and employmentType).
BULLHORN: The Bullhorn API has a limit of 20 posts per request, this plugin currently loops through 1 entry at a time to avoid this limit and make the code simpler (but perhaps slower than some alternatives).
THEME DISPLAY: This plugin should be accompanied by two template files in the active theme's root directory.
LIMITS: WP Cron sync schedule should not be shortened, elongate as necessary. Each run of the synchronizer will only handle 500 posts at a time. If there are more than 500 needing deletion this will span multiple synchronization runs. There is also currently a limit of 120 jobs set, this plugin may support over 1000 but could suffer from unexpected behavior at anything above the current limit. Unlike the post limit this will not be surpassed during immediately preceeding runs of the synchronization; 120 of the newest jobs are pulled from Bullhorn, and only as newer jobs are added will they be pulled into WordPress. Therefore: There will end up being more than 120 jobs listed if more than 120 have existed and have yet to be deleted over the span of time in which this plugin was running.
DISABLING: If you disable this plugin and want to restore the original /open-positions page, go to Settings > Permalinks and click Save Changes after disabling (this will refresh the WP Rewrite cache).
*/


$path = plugin_dir_path( __FILE__ );
require_once $path . 'bullhorn.php';
require_once $path . 'settings.php';
require_once $path . 'custom-post-type.php';
require_once $path . 'cron.php';
require_once $path . 'shortcodes.php';
require_once $path . 'bullhorn-cv.php';


function bullhorn_load_plugin_textdomain() {
	load_plugin_textdomain( 'bh-staffing-job-listing-and-cv-upload-for-wp', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'bullhorn_load_plugin_textdomain' );

/**
 * Allow job listings to be sorted by a specified setting by the admin.
 *
 * @param $query WP_QUERY
 */
function bullhorn_sort_results( $query ) {
	if ( $query->is_post_type_archive( 'bullhornjoblisting' ) ) {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['listings_sort'] ) and ! empty( $settings['listings_sort'] ) ) {
			// Use in_array() because this list might grow in the future
			if ( ! in_array( $settings['listings_sort'], array( 'name', 'date' ) ) ) {
				$query->set( 'meta_key', $settings['listings_sort'] );
				$query->set( 'orderby', 'meta_value' );
			} else {
				$query->set( 'orderby', $settings['listings_sort'] );
			}

			// All queries should default ascending except date sorts
			if ( strstr( $settings['listings_sort'], 'date' ) ) {
				$query->set( 'order', 'DESC' );
			} else {
				$query->set( 'order', 'ASC' );
			}
		}
	}

	$modify_query = false;
	$tax_queries  = array_filter( (array) $query->get( 'tax_query' ) );
	if ( count( $tax_queries ) > 0 ) {
		foreach ( $tax_queries as $tax_query ) {
			if ( isset( $tax_query['taxonomy'] ) ) {
				if ( false !== strstr( $tax_query['taxonomy'], 'bullhorn_' ) ) {
					$modify_query = true;
				}
			}
		}
	}

	if ( in_array( 'bullhornjoblisting', (array) $query->get( 'post_type' ) ) ) {
		$modify_query = true;
	}

	if ( true === $modify_query ) {
		if ( isset( $_GET['bullhorn_state'] ) ) {
			$tax_queries[] = array(
				'taxonomy' => 'bullhorn_state',
				'field'    => 'slug',
				'terms'    => sanitize_key( $_GET['bullhorn_state'] ),
			);
		}

		if ( isset( $_GET['bullhorn_category'] ) ) {
			$tax_queries[] = array(
				'taxonomy' => 'bullhorn_category',
				'field'    => 'slug',
				'terms'    => sanitize_key( $_GET['bullhorn_category'] ),
			);
		}

		$query->set( 'tax_query', $tax_queries );
	}
}

add_action( 'pre_get_posts', 'bullhorn_sort_results' );

/**
 * flush rewrtie on activation
 */
function bullhorn_activation_hook() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'bullhorn_activation_hook' );

/**
 * remove cron on deactivation
 * flush rewrtie on deactivation
 */
function bullhorn_deactivation_hook() {
	wp_clear_scheduled_hook( 'bullhorn_hourly_event' );
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'bullhorn_deactivation_hook' );
