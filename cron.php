<?php

require_once 'bullhorn-2-wp.php';

/**
 * @throws Exception
 */
function bullhorn_sync() {
	$bullhorn_connection = new Bullhorn_Connection;

	$settings = (array) get_option( 'bullhorn_settings' );

	if ( isset( $settings['run_cron'] ) ) {
		$run_cron = $settings['run_cron'];
		if ( 'false' !== $run_cron ) {
			$sync = $bullhorn_connection->sync( false );
			error_log( 'bullhorn sync ran and returned ' . serialize( $sync ) );

			if ( true !== $sync ) {
				$admin_email = get_bloginfo( 'admin_email' );
				if ( $admin_email ) {
					$subject = __( 'Bullhorn cron sync failed with this error', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
					wp_mail( $admin_email, $subject, serialize( $sync ) );
				}
			}
		}
	}
}


function bullhorn_sync_now() {
	$bullhorn_connection = new Bullhorn_Connection;

	return $bullhorn_connection->sync();
}


/**
 *Schedule auto-seed/cull of Job Listings to/from WP CPT using WP Cron:
 */
function bullhorn_event_activation() {
	if ( ! wp_next_scheduled( 'bullhorn_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'bullhorn_event' );
	}

	if ( ! wp_next_scheduled( 'bullhorn_application_sync' ) ) {
		wp_schedule_event( time(), 'hourly', 'bullhorn_application_sync' );
	}
}

add_action( 'init', 'bullhorn_event_activation' );

/**
 *
 */
function bullhorn_event_routine() {
	bullhorn_sync();
}

add_action( 'bullhorn_event', 'bullhorn_event_routine' );

//bullhorn_application_sync();
/**
 *
 */
function bullhorn_application_sync( $local_post_id = null ) {

	if ( null === $local_post_id ) {
		$args             = array(
			'post_type'  => Bullhorn_2_WP::$post_type_application,
			'number'     => 1,
			'meta_query' => array(
				array(
					'key'     => 'bullhorn_synced',
					'compare' => '=',
					'value'   => 'false',
				),
			),
		);

		$application_post = get_posts( $args );
		// return if none found
		if ( false === $application_post ) {

			return;
		}
		$local_post_id = $application_post[0]->ID;
	}


	$application_post_data = (array) get_post_meta( $local_post_id, 'bh_candidate_data', true );

	if( isset( $application_post_data['cv_name'] ) && isset( $application_post_data['cv_dir'] ) ) {
		$file_data['resume']['name']                  = $application_post_data['cv_name'];
		$file_data['resume']['tmp_name']              = $application_post_data['cv_dir'];
		$application_post_data['application_post_id'] = $local_post_id;

		Bullhorn_Extended_Connection::add_bullhorn_candidate( $application_post_data, $file_data );

	}
}

add_action( 'bullhorn_application_sync', 'bullhorn_application_sync' );

add_action( 'bullhorn_application_sync_now', 'bullhorn_application_sync' );
