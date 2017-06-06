<?php

class Bullhorn_WP_Job_Manager_Addon {

	public function __construct() {

		add_filter( 'job_manager_locate_template', array( __CLASS__, 'job_manager_locate_template' ), 10, 3 );
		add_filter( 'job_manager_application_details_bullhorn', array( __CLASS__, 'render_application_form' ) );
	}

	public static function render_application_form() {

		$option = get_option( 'job_manager_bullhorn_default_form_input' );

		if ( 'name_email_phone' === $option ) {
			echo \bullhorn_2_wp\Shortcodes::render_cv_form();
		} else if ( 'name_email_phone_address' === $option ) {
			echo \bullhorn_2_wp\Shortcodes::render_cv_appication();
		} else {
			echo \bullhorn_2_wp\Shortcodes::render_cv_form();
		}
	}

	public static function job_manager_locate_template( $template, $template_name, $template_path ) {

		if ( 'job-application.php' === $template_name && 'bullhorn2wp' === get_option( 'job_manager_allowed_application_method' ) ) {
			return dirname( __FILE__ ) . '/wp-job-manager-job-application-template.php';
		}

		return $template;
	}

	public static function wp_job_manager_menu( $sections ) {

		for ( $i = 0; $i < count( $sections['job_submission'][1] ); $i++ ) {

			if ( 'job_manager_allowed_application_method' === $sections['job_submission'][1][ $i ]['name'] ) {
				$sections['job_submission'][1][ $i ]['options']['bullhorn2wp'] = __( 'Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
			}
		}

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
			'desc'        => sprintf( __( 'Note: You will have to ask Bullhorn support to add this URL "%s" to your API white list for this to work. (see the plugin install notes for more info)',
				                         'bh-staffing-job-listing-and-cv-upload-for-wp'
			                         ), Bullhorn_Settings::get_api_redirect_uri() ),
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

		add_action( 'wp_job_manager_admin_field_bullhorn_sync', array( __CLASS__, 'form_sync_button_handler' ) );

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_client_corporation',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => __( 'Client Corporation', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => __( 'This field is optional, but will filter the jobs retreived from Bullhorn to only those listed under a specific Client Corporation. This must be the ID of the corporation. Leave blank to sync all job listings.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'type'      => 'input',
		);

		$settings[] = array(
			'name' 		  => 'job_manager_bullhorn_default_form_input',
			'std' 		  => 'false',
			'placeholder' => '',
			'label' 	  => __( 'Default Form Input', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'desc'        => '',
			'type'        => 'select',
			'options'     => array(
				'name_email_phone'       => __( 'Name + Email + Phone', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'name_email_phone_address' => __( 'Name + Email + Phone + Address', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			),
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

		if ( Bullhorn_Settings::authorized() ) {
			$settings[] = array(
				'name' => '',
				'std' => '',
				'placeholder' => '',
				'label' => __( 'Sync with Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
				'desc' => '',
				'type' => 'bullhorn_sync_now_button',
			);
			add_action( 'wp_job_manager_admin_field_bullhorn_sync_now_button', array(
				__CLASS__,
				'sync_now_button_handler',
			) );
		}

		//"invisible" field
		$settings[] = array(
			'name' 		  => '',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => '',
			'desc'        => '',
			'type'      => 'bullhorn_js',
		);
		add_action( 'wp_job_manager_admin_field_bullhorn_js', array( __CLASS__, 'form_js_handler' ) );

		//"invisible" field
		$settings[] = array(
			'name' 		  => '',
			'std' 		  => '',
			'placeholder' => '',
			'label' 	  => '',
			'desc'        => '',
			'type'      => 'bullhorn_code_authorization',
		);
		add_action( 'wp_job_manager_admin_field_bullhorn_code_authorization', array( 'Bullhorn_Settings', 'authorize' ) );

		$sections['bullhorn'] = array( __( 'Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), $settings );

		return $sections;
	}

	public static function sync_now_button_handler() {
		printf( '<a href="%s" class="button">%s</a>',
			admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings&sync=bullhorn#settings-bullhorn' ),
			__( 'Sync Now', 'bh-staffing-job-listing-and-cv-upload-for-wp' )
		);
	}

	public static function form_sync_button_handler() {

		$settings = apply_filters( 'wp_bullhorn_settings', (array) get_option( 'bullhorn_settings' ) );

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
				'redirect_uri'  => Bullhorn_Settings::get_api_redirect_uri(),
			),
			'auth.bullhornstaffing.com/oauth/authorize'
		);

		printf( '<a class="button" href="https://%s">%s</a>', $url, esc_html( $state_string ) );
	}

	public static function form_js_handler() {
	?>
		<script>
			jQuery(function(){

              var tabsIds = jQuery('.job-manager-settings-wrap div').toArray().map(function(value) { return '#' + value.id } );

              if((tabsIds.indexOf(window.location.hash) > -1)) {

                jQuery('.nav-tab-wrapper a[href="#settings-job_listings"]').removeClass('nav-tab-active');
                jQuery('.settings_panel').css('display', 'none');

                jQuery('.nav-tab-wrapper a[href="' + window.location.hash + '"]').addClass('nav-tab-active');
                jQuery('div' + window.location.hash).css('display', 'block');
              }
			});
		</script>
	<?php
	}
}