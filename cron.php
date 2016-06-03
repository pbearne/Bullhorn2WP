<?php

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
					$subject = __( 'Bullhorn cron synic failed with this error', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
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
}

add_action( 'wp', 'bullhorn_event_activation' );

/**
 *
 */
function bullhorn_event_routine() {
	bullhorn_sync();
}

add_action( 'bullhorn_event', 'bullhorn_event_routine' );
