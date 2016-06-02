<?php
/**
 * Examples of how hook into the plugin
 * User: Paul
 * Date: 2016-05-28
 *
 */


add_action( 'wp-bullhorn-cv-upload-complete', 'ah_send_me_form' );

function ah_send_me_form() {

	// get the info from the form
	$fullname = ( isset( $_POST['name'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : 'n/a';
	$email = ( isset( $_POST['name'] ) ) ? trim( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : 'n/a';
	$phone = ( isset( $_POST['name'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : 'n/a';
	$position_id = ( isset( $_POST['name'] ) ) ? absint( wp_unslash( $_POST['position'] ) ) : - 1;
	$message1 = ( isset( $_POST['name'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['message'] ) ) ) : 'n/a';
	$title = '';
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
	$message .= 'Name :' . $fullname . '\n';
	$message .= 'Email :' . $email . '\n';
	$message .= 'Phone :' . $phone . '\n';
	$message .= 'Message: ' . $message1 . '\n';

	//set the form headers
	$headers = 'From: New Submission on NYCM Search Website <wordpress@newyorkcm.com>';


	// Who are we going to send this form too
	$send_to = 'retuer@jobsite.com';

	$attachments = array();
	$tmp_nae = $_FILES['resume']['tmp_name'];
	$new_name = WP_CONTENT_DIR . '/uploads/cv/' . $_FILES['resume']['name'];

	move_uploaded_file( $tmp_nae, $new_name );

	if ( file_exists( $new_name ) ) {
		$attachments = array( $new_name );
	}

	wp_mail( $send_to, $subject, $message, $headers, $attachments );
}


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