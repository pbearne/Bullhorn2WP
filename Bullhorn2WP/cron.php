<?php

function bullhorn_sync() {
	$bullhorn_connection = new Bullhorn_Connection;
	$bullhorn_connection->sync();
}

// Schedule auto-seed/cull of Job Listings to/from WP CPT using WP Cron:
function bullhorn_event_activation() {
	if ( ! wp_next_scheduled( 'bullhorn_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'bullhorn_event' );
	}
}

add_action( 'wp', 'bullhorn_event_activation' );

function bullhorn_event_routine() {
	bullhorn_sync();
}

add_action( 'bullhorn_event', 'bullhorn_event_routine' );