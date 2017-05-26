<?php
/**
 * Examples of how hook into the plugin
 * User: Paul
 * Date: 2016-05-28
 *
 */


function send_me_form( $candidate, $resume, $local_post_id, $local_post_data ) {

	// get the info from the form
	$fullname = ( isset( $_POST['name'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : 'n/a';
	$email = ( isset( $_POST['email'] ) ) ? trim( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : 'n/a';
	$phone = ( isset( $_POST['phone'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : 'n/a';
	$position_id = ( isset( $_POST['position'] ) ) ? absint( wp_unslash( $_POST['position'] ) ) : - 1;
	$user_message = ( isset( $_POST['message'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['message'] ) ) ) : 'n/a';
	$title = '';
	$attachments = array();

	if ( 0 < $position_id ) {
		$args = array(
			'meta_query' => array(
				array(
					'key' => 'bullhorn_job_id',
					'value' => $position_id,
					'compare' => '=',
				),
			),
			'post_type' => 'bullhornjoblisting',
			'posts_per_page' => 1,
		);

		$query = new WP_Query( $args );

		$title = isset( $query->posts[0]->post_title ) ? $query->posts[0]->post_title : '';
	}
	// The email subject
	$subject = 'Submission Notification for' . $title;
	// Build the message
	$message = '<p>Job Applied For :' . $title . '</p>' . PHP_EOL;
	if ( '' === $title ) {
		$subject = 'CV Uploaded by';
		$message = '<p>A CV Uploaded by:</p>' . PHP_EOL;
	}

	$message .= '<p>Name :' . $fullname . '</p>' . PHP_EOL;
	$message .= '<p>Email :' . $email . '</p>' . PHP_EOL;
	$message .= '<p>Phone :' . $phone . '</p>' . PHP_EOL;
	$message .= '<p>Message: ' . $user_message . '</p>' . PHP_EOL;

	//set the form headers
	$headers = 'From: New Submission on NYCM Search Website <resume@sitename.com>';

	// Who are we going to send this form too
	$send_to = 'resume@sitename.com';

	if ( file_exists( $local_post_data['cv_dir'] ) ) {
		$attachments = array( $local_post_data['cv_dir'] );
	}

	add_filter( 'wp_mail_content_type', 'ah_set_content_type', 22 );
	wp_mail( $send_to, $subject, $message, $headers, $attachments );
	remove_filter( 'wp_mail_content_type', 'ah_set_content_type' );
}

add_action( 'wp-bullhorn-cv-upload-complete', 'send_me_form', 10, 4 );


/**
 * change the CV upload error mesage
 *
 * @param $text
 * @return string
 */
function ah_parse_resume_failed_text( $text ) {

	return 'Your file formatting does not agree with our system. Please email it directly to resume@jobsite.com and we will get back to you shortly.';

}

add_filter( 'parse_resume_failed_text', 'ah_parse_resume_failed_text' );



function bullhorn_shortcode_bottom_job( $output, $id ) {
	$output .= '<div id="linky">';
	$output .= '<a href="' . get_permalink( $id ) . '">' . 'Read more >>' . '</a>';
	$output .= '</div>';

	return $output;
}

add_filter( 'bullhorn_shortcode_bottom_job', 'bullhorn_shortcode_bottom_job', 10, 2 );

function bullhorn_shortcode_base_salary_meta_value( $meta_value ) {

	return 'Payrate: $'. $meta_value;
}

add_filter( 'bullhorn-shortcode-baseSalary-meta-value', 'bullhorn_shortcode_base_salary_meta_value' );