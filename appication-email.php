<?php

/**
 * Created by IntelliJ IDEA.
 * User: pbear
 * Date: 2017-05-26
 * Time: 10:38 AM
 */
class Appication_Email {

	public function __construct() {
		add_action( 'wp-bullhorn-cv-upload-complete', array( __CLASS__, 'send_me_form' ), 10, 4 );
	}

	public static function send_me_form( $candidate, $resume, $local_post_id, $local_post_data ) {

		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['send_email'] ) ) {

			// get the info from the form
			$address_fields = array( 'address1', 'address2', 'city', 'state', 'zip' );


			$fullname     = ( isset( $_POST['name'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : 'n/a';
			$email        = ( isset( $_POST['email'] ) ) ? trim( sanitize_email( wp_unslash( $_POST['email'] ) ) ) : 'n/a';
			$phone        = ( isset( $_POST['phone'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['phone'] ) ) ) : 'n/a';
			$position_id  = ( isset( $_POST['position'] ) ) ? absint( wp_unslash( $_POST['position'] ) ) : - 1;
			$user_message = ( isset( $_POST['message'] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST['message'] ) ) ) : 'n/a';
			$title        = '';
			$attachments  = array();

			$address = '';

			foreach ( $address_fields as $field ) {
				$address .= ( isset( $_POST[ $field ] ) && ! empty( $_POST[ $field ] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ) . '<br />' : '';
			}

			if ( 0 < $position_id ) {
				$args = array(
					'meta_query'     => array(
						array(
							'key'     => 'bullhorn_job_id',
							'value'   => $position_id,
							'compare' => '=',
						),
					),
					'post_type'      => 'bullhornjoblisting',
					'posts_per_page' => 1,
				);

				$query = new WP_Query( $args );

				$title = isset( $query->posts[0]->post_title ) ? $query->posts[0]->post_title : '';
			}
			// The email subject
			$subject = 'Submission Notification for ' . $title;
			// Build the message
			$message = '<p>Job Applied For :' . $title . '</p>' . PHP_EOL;
			if ( '' === $title ) {
				$subject = 'CV Uploaded by';
				$message = '<p>A CV Uploaded by:</p>' . PHP_EOL;
			}

			$message .= '<p>' . esc_html__( 'Name :', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . $fullname . '</p>' . PHP_EOL;
			$message .= '<p>' . esc_html__( 'Email :', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . $email . '</p>' . PHP_EOL;
			$message .= '<p>' . esc_html__( 'Phone :', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . $phone . '</p>' . PHP_EOL;
			$message .= ( ! empty( $address ) ) ? '<p>' . esc_html__( 'Address :', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '<br />' . $address . '</p>' . PHP_EOL : '';
			$message .= '<p>' . esc_html__( 'Message: ', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . $user_message . '</p>' . PHP_EOL;

			//set the form headers
			$headers = 'From: New Submission on ' . get_bloginfo( 'name' ) . ' Website <' . get_bloginfo( 'admin_email' ) . '>';

			// Who are we going to send this form too
			$send_to = $settings['send_email'];

			if ( file_exists( $local_post_data['cv_dir'] ) ) {
				$attachments = array( $local_post_data['cv_dir'] );
			}

			add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_content_type' ), 22 );
			wp_mail( $send_to, $subject, $message, $headers, $attachments );
			remove_filter( 'wp_mail_content_type', array( __CLASS__, 'set_content_type' ) );
		}
	}

	public static function set_content_type( $content_type ) {
		return 'text/html';
	}

}

new Appication_Email();