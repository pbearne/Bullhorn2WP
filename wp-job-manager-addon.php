<?php

class Bullhorn_WP_Job_Manager_Addon {

	public static function wp_job_manager_menu( $sections ) {

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_client_id',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Client ID', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => '',
			'type'      => 'input',
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_client_secret',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Client Secret', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'Note: You will have to ask Bullhorn support to add this URL "http://localhost:8080/wp-admin/options-general.php?page=bullhorn" to your API white list for this to work. (see the plugin install notes for more info)', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'      => 'input',
		);

		$settings[] = array(
			'name' 		  => '',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Synchronize', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => '',
			'type'      => 'bullhorn_sync',
		);

		add_action( 'wp_job_manager_admin_field_bullhorn_sync', array( __CLASS__, 'sync_button_handler' ) );

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_client_corporation',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Client Corporation', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'This field is optional, but will filter the jobs retreived from Bullhorn to only those listed under a specific Client Corporation. This must be the ID of the corporation. Leave blank to sync all job listings.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'      => 'input',
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_send_email',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Email address for applications', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'This field is optional, but if set, you will get a copy of the application sent to the email provided.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'      => 'input',
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_run_cron',
			'std' 		  => 'false',
			'placeholder' => '',
			'label' 	  => __( 'Auto-sync', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'Fetch Jobs from Bullhorn every hour or using the manual sync button below ( shows once you have connected to Bullhorn ).', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'        => 'select',
			'options'     => array(
				'true'       => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'false' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			),
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_cron_error_email',
			'std' 		  => 'true',
			'placeholder' => '',
			'label' 	  => __( 'Auto-sync Error Email', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'Send an Email to the WP admin email address if the synic errors.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'        => 'select',
			'options'     => array(
				'on'       => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'off' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			),
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_cron_is_public',
			'std' 		  => 'true',
			'placeholder' => '',
			'label' 	  => __( 'Filter isPublic', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'By Default the isPublic field is hidden in Vacancy by default if no job as syniced try set to this to false. To show the field the steps are : Fields Mapping Vacancy isPublic, uncheck "hidden" .', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'        => 'select',
			'options'     => array(
				'true'       => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'false' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			),
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_cron_mark_submitted',
			'std' 		  => 'true',
			'placeholder' => '',
			'label' 	  => __( 'Mark Submitted', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'Choose if to mark a submission to a job as "Submitted" or as "New Lead".', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'        => 'select',
			'options'     => array(
				'true'       => __( 'Submitted', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'false' => __( 'New Lead', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			),
		);

		$sections['bullhorn'] = array( __( 'Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), $settings );

		return $sections;
	}

	public static function sync_button_handler( $option, $attributes, $value, $placeholder ) {

		$settings = (array) get_option( 'bullhorn_settings' );

		$state_string = __( 'not ready', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
		if ( Bullhorn_Settings::authorized() ) {
			$state_string = __( 'Re-connect to Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
		} elseif ( Bullhorn_Settings::connected() ) {
			$state_string = __( 'Connect to Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
		}
		$url = add_query_arg(
			array(
				'client_id'     => $settings['client_id'],
				'response_type' => 'code',
				'redirect_uri'  => admin_url( 'options-general.php?page=bullhorn' ),
			),
			'auth.bullhornstaffing.com/oauth/authorize'
		);

		printf( '<a class="button" href="https://%s">%s</a>', $url, esc_html( $state_string ) );
	}
}