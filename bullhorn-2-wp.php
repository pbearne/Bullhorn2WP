<?php
/*
Plugin Name: Bullhorn Staffing and Recruitment Job Listing and CV/Resume uploader.
Plugin URI: https://github.com/pbearne/Bullhorn2WP
Description: This plugin adds Bullhorn jobs to a custom post (Job Listings) for front-end display. New and deleted jobs are synchronized every hour with Bullhorn. This is a 'pull' process only - any job created locally is not pushed to Bullhorn! Any theme developed on top of this plugin should have archive-job-listing.php and single-job-listing.php template files if a special layout is needed. Pull requests are accepted at https://github.com/pbearne/Bullhorn2WP
Version: 2.5.0
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
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once $path . 'bullhorn-connection.php';
require_once $path . 'settings.php';
require_once $path . 'custom-post-type.php';
require_once $path . 'cron.php';
require_once $path . 'shortcodes.php';
require_once $path . 'bullhorn-cv.php';
require_once $path . 'appication-email.php';
require_once $path . 'wp-job-manager-addon.php';

class Bullhorn_2_WP {

	public function __construct() {

		add_action( 'plugins_loaded', array( __CLASS__, 'bullhorn_load_plugin_textdomain' ) );
		register_activation_hook( __FILE__, array( __CLASS__, 'bullhorn_activation_hook' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'bullhorn_deactivation_hook' ) );
	}

	public static function bullhorn_load_plugin_textdomain() {
		load_plugin_textdomain( 'bh-staffing-job-listing-and-cv-upload-for-wp', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * flush rewrtie on activation
	 */
	function bullhorn_activation_hook() {
		flush_rewrite_rules();
	}

	/**
	 * remove cron on deactivation
	 * flush rewrtie on deactivation
	 */
	function bullhorn_deactivation_hook() {
		wp_clear_scheduled_hook( 'bullhorn_event' );
		wp_clear_scheduled_hook( 'bullhorn_appication_sync' );
		flush_rewrite_rules();
	}

}

new Bullhorn_2_WP;