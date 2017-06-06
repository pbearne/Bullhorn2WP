<?php
wp_enqueue_script( 'wp-job-manager-job-application' );
?>
<div class="job_application application">
	<?php do_action( 'job_application_start' ); ?>

	<input type="button" class="application_button button" value="<?php _e( 'Apply for job', 'wp-job-manager' ); ?>" />

	<div class="application_details">
		<?php
		do_action( 'job_manager_application_details_bullhorn' );
		?>
	</div>
	<?php do_action( 'job_application_end' ); ?>
</div>

