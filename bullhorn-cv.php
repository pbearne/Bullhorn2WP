<?php


// require_once dirname( dirname( __FILE__ ) ) . '/bullhorn-2-wp.php';

/**
 * This class is an extension of Bullhorn_Connection.  Its purpose
 * is to allow for resume and candidate posting
 *
 * Class Bullhorn_Extended_Connection
 */
class Bullhorn_Extended_Connection extends Bullhorn_Connection {

	/**
	 * Class Constructor
	 *
	 * @return \Bullhorn_Extended_Connection
	 */
	public function __construct() {
		// Call parent __construct()
		parent::__construct();

		add_filter( 'query_vars', array( __CLASS__, 'add_query_vars' ) );
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_action( 'parse_request', array( __CLASS__, 'sniff_requests' ) );
	}

	/**
	 * Update vars
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = '__api';
		$vars[] = 'endpoint';

		return $vars;
	}

	/**
	 * Initialize the reqrite rule
	 *
	 * @return void
	 */
	public static function add_endpoint() {
		add_rewrite_rule( '^api/bullhorn/([^/]+)/?', 'index.php?__api=1&endpoint=$matches[1]', 'top' );
	}

	/**
	 * Check to see if the request is a bullhorn API request
	 *
	 * @return void
	 */
	public static function sniff_requests() {
		global $wp;
		if ( isset( $wp->query_vars['__api'] ) && isset( $wp->query_vars['endpoint'] ) ) {
			switch ( $wp->query_vars['endpoint'] ) {
				case 'resume':

					if (
						! isset( $_POST['bullhorn_cv_form'] )
						|| ! wp_verify_nonce( $_POST['bullhorn_cv_form'], 'bullhorn_cv_form' )
					) {
						esc_attr_e( 'Sorry, your nonce did not verify.', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
						die();

					}
					$thanks_page_url = esc_url( ( isset( $_POST['_wp_http_referer'] ) ) ? $_POST['_wp_http_referer'] : '' );

					$settings = (array) get_option( 'bullhorn_settings' );
					if ( 0 < $settings['thanks_page'] ) {
						$thanks_page_url = get_permalink( $settings['thanks_page'] );
					}


					list( $local_post_id, $local_post_data ) = self::save_application();

					if ( ! isset( self::$api_access['refresh_token'] ) ) {
						$permalink = add_query_arg( array(
							'bh_applied'    => true,
							'refresh_token' => true,
						), $thanks_page_url );

						wp_safe_redirect( $permalink );
						die();
					}
					if ( apply_filters( 'bullhorn_upload_via_cron', false ) ) {

						wp_schedule_single_event( time(), 'bullhorn_application_sync_now', $local_post_id );

						$permalink = add_query_arg( array(
							'bh_applied' => true,
							'cron_used'  => true,
						), $thanks_page_url );

						wp_safe_redirect( $permalink );
						die();
					}

					if ( isset( $local_post_data['cv_name'] ) && isset( $local_post_data['cv_dir'] ) ) {
						$file_data['resume']['name']     = $local_post_data['cv_name'];
						$file_data['resume']['tmp_name'] = $local_post_data['cv_dir'];

						$resume = self::parseResume( $file_data );

					} else {
						// Get Resume
						$resume = self::parseResume();
					}


					if ( false === $resume ) {
						// Redirect
						$permalink = add_query_arg( array(
							'bh_applied' => false,
						), $thanks_page_url );

						wp_safe_redirect( $permalink );
						die();
					}

					if ( is_array( $resume ) ) {
						$orig_url = $_POST['_wp_http_referer'];
						$url      = add_query_arg(
							array(
								'bh-message' => rawurlencode( apply_filters( 'parse_resume_failed_text', $resume['errorMessage'] ) ),

							), $orig_url
						);

						wp_safe_redirect( $url );
						die();
					}

//					if ( 10 > $resume->confidenceScore ) {
//						$orig_url = $_POST['_wp_http_referer'];
//						$url      = add_query_arg(
//							array(
//								'bh-message' => rawurlencode(
//									apply_filters( 'parse_resume_low_score_text',
//										sprintf( __('Submission failed - We go a low Confidence Score ( %s ) when we paused your CV was it empty?', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), $resume->confidenceScore )
//									)
//								),
//
//							), $orig_url
//						);
//						update_post_meta( $local_post_id, 'bullhorn_synced', 'bad_resume' );
//
//						wp_safe_redirect( $url );
//						die();
//					}


					// Create candidate
					$candidate = self::create_candidate( $resume );

					if ( false === $candidate || ! isset( $candidate->changedEntityId ) ) {
						error_log( 'Candidate ID not set: ' . serialize( $candidate ) );

						$permalink = add_query_arg( array(
							'bh_applied' => false,
						), $thanks_page_url );

						wp_safe_redirect( $permalink );
						die();
					} else {
						// Attach education to candidate
						self::attachEducation( $resume, $candidate );

						// Attach work history to candidate
						self::attach_work_history( $resume, $candidate );

						// Attach work history to candidate
						self::attach_skills( $resume, $candidate );

						// Attach note to candidate
						// not working yet
						// self::attach_note( $candidate, $local_post_data );

						// link to job
						self::link_candidate_to_job( $candidate );

						// Attach resume file to candidate
						error_log( 'wp_upload_file_request: ' . self::wp_upload_file_request( $candidate, $file_data ) );

						if ( apply_filters( 'bullhorn_delete_local_copy', false ) ) {

							wp_delete_post( $local_post_id );
							//TODO: remove and file saved
						} else {

							update_post_meta( $local_post_id, 'bh_candidate_data', $candidate );
							update_post_meta( $local_post_id, 'bullhorn_synced', 'true' );
						}

						do_action( 'wp-bullhorn-cv-upload-complete', $candidate, $resume, $local_post_id, $local_post_data );

						// Redirect
						$permalink = add_query_arg( array(
							'bh_applied' => true,
						), $thanks_page_url );
						wp_safe_redirect( $permalink );
						die();
					}


					break;
				default:
					$response = array(
						'status' => 404,
						'error'  => __( 'The endpoint you are trying to reach does not exist.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
					);
					echo wp_json_encode( $response );
			}
			exit;
		}
	}


	/**
	 *
	 *
	 * @static
	 * @return int|WP_Error
	 */
	public static function save_application() {

		$ext = $format = '';
		// get cv file
		if ( ! isset( $_FILES['resume'] ) ) {

			self::throwJsonError( 500, 'No resume file found.' );
		}

//		list( $ext, $format ) = self::get_filetype();

		// http://gerhardpotgieter.com/2014/07/30/uploading-files-with-wp_remote_post-or-wp_remote_request/
		$local_file = $_FILES['resume']['tmp_name'];

		$name = '--';
		if ( isset( $_REQUEST['name'] ) && ! empty( $_REQUEST['name'] ) ) {
			$name = sanitize_text_field( $_REQUEST['name'] );
		}

		$job_title = '--';
		$job_id    = null;
		if ( isset( $_REQUEST['post'] ) && ! empty( $_REQUEST['post'] ) ) {
			$job_title = get_the_title( absint( $_REQUEST['post'] ) );
			$job_id    = get_post_meta( absint( $_REQUEST['post'] ), 'bullhorn_job_id', true );
		} elseif ( isset( $_REQUEST['position'] ) && ! empty( $_REQUEST['position'] ) ) {

			$job_post = self::get_post_by_bullhorn_id( absint( $_REQUEST['position'] ) );
			$job_title = $job_post->post_title . ' (' . absint( $_REQUEST['position'] ) . ')';
			$job_id    = get_post_meta( $job_post->ID, 'bullhorn_job_id', true );
		}


		$uploads   = wp_upload_dir();
		$cv_folder = trailingslashit( trailingslashit( $uploads['basedir'] ) . 'cv' );
		if ( ! file_exists( $cv_folder ) ) {
			mkdir( $cv_folder );
		}

		$new_filename = $_FILES['resume']['name'];
		$posfix       = 1;
		while ( file_exists( $cv_folder . $new_filename ) ) {
			$new_filename = str_replace( '.', '-' . $posfix . '.', $_FILES['resume']['name'] );
			++ $posfix;
		}

		move_uploaded_file( $local_file, $cv_folder . $new_filename );

		$cv_url = trailingslashit( $uploads['baseurl'] ) . 'cv/' . $new_filename;


		$post_title = $name . ' ' . __( 'applied for', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . ' ' . $job_title;

		$posiable_fields = array(
			'name',
			'email',
			'phone',
			'message',
			'address1',
			'address2',
			'city',
			'state',
			'zip',
			'position',
			'post'
		);

		$data         = array();
		$post_content = '';
		foreach ( $posiable_fields as $key ) {
			$data[ $key ] = ( isset( $_REQUEST[ $key ] ) ) ? sanitize_text_field( $_REQUEST[ $key ] ) : '';
			$post_content .= $key . ': ' . $data[ $key ] . PHP_EOL;
		}

		$data['cv_url']  = $cv_url;
		$data['cv_dir']  = $cv_folder . $new_filename;
		$data['cv_name'] = $new_filename;
		$data['job_id']  = $job_id;

		$post_content .= 'CV: ' . sprintf( '<a href="%1$s">%1$s</a>', esc_url( $cv_url ) ) . PHP_EOL;

		// Create post object
		$my_post = array(
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_type'    => Bullhorn_2_WP::$post_type_application,
			'post_author'  => 1,
			'post_status'  => 'publish',
		);// Insert the post into the database
		;
		$post_id = wp_insert_post( $my_post );

		update_post_meta( $post_id, 'bh_candidate_data', $data );
		update_post_meta( $post_id, 'bullhorn_synced', 'false' );

		add_action( 'bullhorn_appication_post_saved', $data, $post_content );

		return array( $post_id, $data );
	}


	/**
	 *
	 *
	 * @static
	 *
	 * @param $profile_data
	 * @param $file_data
	 *
	 * @return bool
	 */
	public static function add_bullhorn_candidate( $profile_data, $file_data ) {
		// Get Resume
		if ( is_array( $file_data ) ) {
			$resume = self::parseResume( $file_data, $profile_data['application_post_id'] );

			if ( false === $resume ) {

				return false;
			}
		} else {
			// create data object to create Candidate

			$resume                     = new stdClass();
			$resume->candidate          = new stdClass();
			$resume->candidate->address = array();
			$resume->skillList          = array();
		}

		// Create candidate
		$candidate = self::create_candidate( $resume, $profile_data );

		// Attach education to candidate
		self::attachEducation( $resume, $candidate );

		// Attach work history to candidate
		self::attach_work_history( $resume, $candidate );

		// Attach work history to candidate
		self::attach_skills( $resume, $candidate );

		$job_id = null;

		// link to job
		self::link_candidate_to_job( $candidate, $profile_data['job_id'] );


		// Attach resume file to candidate
		if ( is_array( $file_data ) ) {

			error_log( 'wp_upload_file_request: ' . self::wp_upload_file_request( $candidate, $file_data ) );
		}


		do_action( 'add_bullhorn_candidate_complete', $candidate, $resume, $profile_data, $file_data );


		if ( isset( $profile_data['application_post_id'] ) ) {
			if ( apply_filters( 'bullhorn_delete_local_copy', false ) ) {

				wp_delete_post( $profile_data['application_post_id'] );
				unlink( $file_data['resume']['tmp_name'] );
			} else {

				update_post_meta( $profile_data['application_post_id'], 'bh_candidate_data', $candidate->data );
				update_post_meta( $profile_data['application_post_id'], 'bullhorn_synced', 'true' );
			}
		}

		return $candidate->changedEntityId;
	}

//TODO: finish
	public static function update_bullhorn_candidate( $candidate_id, $profile_data, $file_data ) {

		// Get Resume
		if ( is_array( $file_data ) ) {
			$resume = self::parseResume( $file_data );

			if ( false === $resume ) {

				return false;
			}
		} else {
			// create an empty data object to create Candidate

			$resume                     = new stdClass();
			$resume->candidate          = new stdClass();
			$resume->candidate->address = array();
			$resume->skillList          = array();
		}

		$resume = self::add_data_to_canditate_data( $resume, $profile_data );

		// Update candidate
		$candidate = self::update_candidate( $candidate_id, $resume->candidate );

		do_action( 'update_bullhorn_candidate_complete', $candidate_id, $resume, $profile_data, $file_data, $candidate );

		return true;
	}

	/**
	 * Takes the posted 'resume' file and returns a parsed version from bullhorn
	 *
	 * @param null|array|int $local_files
	 *
	 * @return mixed
	 */
	public static function parseResume( $local_files = null, $local_id = null ) {

		$ext = $format = '';
		if ( null === $local_files ) {
			// check to make sure file was posted
			if ( ! isset( $_FILES['resume'] ) ) {

				self::throwJsonError( 500, 'No resume file found.' );
			}
			$file_name = $_FILES['resume']['name'];

			// http://gerhardpotgieter.com/2014/07/30/uploading-files-with-wp_remote_post-or-wp_remote_request/
			$local_file = $_FILES['resume']['tmp_name'];
		} else {
			if ( ! is_array( $local_files ) ) {
				$application_post_data = (array) get_post_meta( absint( $local_files ), 'bh_candidate_data', true );

				if ( isset( $application_post_data['cv_name'] ) && isset( $application_post_data['cv_dir'] ) ) {
					$files['resume']['name']     = $application_post_data['cv_name'];
					$files['resume']['tmp_name'] = $application_post_data['cv_dir'];

				}
			} else {
				$files = $local_files;
			}

			if ( ! isset( $files['resume'] ) ) {

				self::throwJsonError( 500, 'No resume file found.' );
			}

			$file_name = $files['resume']['name'];
			list( $ext, $format ) = self::get_filetype( $files, $local_id );

			// http://gerhardpotgieter.com/2014/07/30/uploading-files-with-wp_remote_post-or-wp_remote_request/
			$local_file = $files['resume']['tmp_name'];
		}


		if ( ! file_exists( $local_file ) ) {

			return false;
		}

		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() );
		$payload  = '';
		$payload  .= '--' . $boundary;
		$payload  .= "\r\n";
		$payload  .= 'Content-Disposition: form-data; name="photo_upload_file_name"; filename="' . $file_name . '"' . "\r\n";
		$payload  .= 'Content-Type: ' . $ext . "\r\n"; // If you	know the mime-type
		$payload  .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload  .= "\r\n";
		$payload  .= file_get_contents( $local_file );
		$payload  .= "\r\n";
		$payload  .= '--' . $boundary . '--';
		$payload  .= "\r\n\r\n";

		$args = array(
			'method'  => 'POST',
			'timeout' => 120, // default is 45 set to 2 minuets for this one call
			'headers' => array(
				'accept'       => 'application/json',
				// The API returns JSON
				'content-type' => 'multipart/form-data;boundary=' . $boundary,
				// Set content type to multipart/form-data
			),
			'body'    => $payload,
		);
		// API authentication
		self::api_auth();

		$url          = add_query_arg(
			array(
				'BhRestToken'         => self::$session,
				'format'              => $format,
				'populateDescription' => 'html',
			), self::$url . 'resume/parseToCandidate'
		);
		$safety_count = 0;
		// make call to the parse the CV
		$response = wp_remote_request( $url, $args );

		while ( 10 > $safety_count ) {

			// sometimes we will get an REX error this is due to a comms failing between bullhorn servers aand the 3rd party servers

			// if are good exit while loop
			if ( ! is_wp_error( $response ) && isset( $response['body'] ) && false === strpos( strtolower( $response['body'] ), 'convert failed' ) ) {
				break;
			}
			if ( is_wp_error( $response ) ) {
				error_log( 'CV parse looped with : ' . serialize( $response ) . ': ' . $safety_count );
			} elseif ( isset( $response['errorMessage'] ) ) {
				error_log( 'CV parse looped with : ' . $response['errorMessage'] . ': ' . $safety_count );
			}

			// make a attempt call to the parse the CV
			$response = wp_remote_request( $url, $args );
			$safety_count ++;
		}

		if ( is_wp_error( $response ) ) {

			return false;
		}

		if ( 200 === $response['response']['code'] ) {

			return json_decode( $response['body'] );
		}

		return json_decode( $response['body'], true );
	}

	/**
	 * Send a json error to the screen
	 *
	 * @param $status
	 * @param $error
	 */
	public static function throwJsonError( $status, $error ) {
		$response = array( 'status' => $status, 'error' => $error );
		echo wp_json_encode( $response );
		exit;
	}

	/**
	 * @return array
	 */
	private static function get_filetype( $local_files = null, $local_post_id = null ) {

		// Get file extension
		$mine_types = wp_get_mime_types();
		unset( $mine_types['swf'], $mine_types['exe'], $mine_types['htm|html'] );
		if ( null === $local_files ) {
			$file_type = wp_check_filetype_and_ext( $_FILES['resume']['tmp_name'], $_FILES['resume']['name'], $mine_types );
		} else {
			$file_type = wp_check_filetype_and_ext( $local_files['resume']['tmp_name'], $local_files['resume']['name'], $mine_types );
		}
		$ext = $file_type['type'];

		switch ( strtolower( $ext ) ) {
			case 'text/plain':
				$format = 'TEXT';
				break;
			case 'application/msword':
				$format = 'DOC';
				break;
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				$format = 'DOCX';
				break;
			case 'application/pdf':
				$format = 'PDF';
				break;
			case 'text/rtf':
				$format = 'RTF';
				break;
			case 'text/html':
				$format = 'HTML';
				break;
			default:
				$format = '';
				if ( null !== $local_post_id ) {

					update_post_meta( $local_post_id, 'bullhorn_synced', 'bad_file' );
				}
				$orig_url = $_POST['_wp_http_referer'];
				unset( $_GET['sync'] );
				$url = add_query_arg(
					array_merge( array(
						'bh-message' => rawurlencode( apply_filters( 'file_type_failed_text', __( "Oops. This document isn't the correct format. Please upload it as one of the following formats: .txt, .html, .pdf, .doc, .docx, .rft.", 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ) ),

					), $_GET ), $orig_url
				);

				wp_safe_redirect( $url );
				die();
		}

		return array( $ext, $format );
	}

	/**
	 * Run this before any api call.
	 *
	 * @return void
	 */
	private static function api_auth() {
		// login to bullhorn api
		$logged_in = self::login();
		if ( ! $logged_in ) {
			self::throwJsonError( 500, __( 'There was a problem logging into the Bullhorn API.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) );
		}
	}

	/**
	 * Create a candidate int he system
	 *
	 * @param $resume
	 * @param array $profile_data
	 *
	 * @return mixed
	 */
	public static function create_candidate( $resume, $profile_data = array() ) {

		if ( ! isset( $resume->candidate ) ) {

			return false;
		}
		$resume = self::add_data_to_canditate_data( $resume, $profile_data );

		$resume->candidate->source = 'New Website';

		// API authentication
		self::api_auth();

		if ( isset( $profile_data['phone'] ) ) {
			$cv_phone = $resume->candidate->phone;

			$resume->candidate->phone  = esc_attr( $profile_data['phone'] );
			$resume->candidate->phone2 = esc_attr( $cv_phone );
		} elseif ( isset( $_POST['phone'] ) ) {
			$cv_phone = $resume->candidate->phone;

			$resume->candidate->phone  = esc_attr( $_POST['phone'] );
			$resume->candidate->phone2 = esc_attr( $cv_phone );
		}

		if ( isset( $profile_data['name'] ) ) {

			$resume->candidate->name = esc_attr( $profile_data['name'] );
		} elseif ( isset( $_POST['name'] ) ) {

			$resume->candidate->name = esc_attr( $_POST['name'] );
		}

		$address_fields = array( 'address1', 'address2', 'city', 'state', 'zip' );
		if ( isset( $profile_data['address'] ) ) {
			$cv_address = $resume->candidate->address;

			$address_data = array();

			foreach ( $address_fields as $key ) {
				$address_data[ $key ] = ( isset( $profile_data[ $key ] ) ) ? $profile_data[ $key ] : '';
			}
			$resume->candidate->address          = $address_data;
			$resume->candidate->secondaryAddress = $cv_address;
		} elseif ( isset( $_POST['address1'] ) ) {
			$cv_address = $resume->candidate->address;

			$address_data = array();

			foreach ( $address_fields as $key ) {
				$address_data[ $key ] = ( isset( $_POST[ $key ] ) ) ? $_POST[ $key ] : '';
			}

			$resume->candidate->address          = $address_data;
			$resume->candidate->secondaryAddress = $cv_address;

		}

		$resume->candidate->comments = '';

		$position_prex = apply_filters( 'bullhorn_position_prex', __( 'Position applied for: ', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) );
		if ( isset( $profile_data['position'] ) && null !== $profile_data['position'] ) {
			if ( is_numeric( $profile_data['position'] ) ) {
				$position_text = self::get_post_by_bullhorn_id( absint( $profile_data['position'] ) )->post_title;
			} else {
				$position_text = $profile_data['position'];
			}

			$resume->candidate->comments .= esc_html( $position_prex . $position_text . PHP_EOL );
		} elseif ( isset( $_POST['position'] ) && ! empty( $_POST['position'] ) ) {
			if ( is_numeric( $_POST['position'] ) ) {
				$position_text = self::get_post_by_bullhorn_id( absint( $_POST['position'] ) )->post_title;
			} else {
				$position_text = $_POST['position'];
			}
			$resume->candidate->comments .= esc_html( $position_prex . $position_text . PHP_EOL );
		}

		$message_prex = apply_filters( 'bullhorn_message_prex', __( 'Message: ', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) );
		if ( isset( $profile_data['message'] ) ) {

			$resume->candidate->comments .= esc_html( PHP_EOL . $message_prex . $profile_data['message'] );
		} elseif ( isset( $_POST['message'] ) ) {

			$resume->candidate->comments .= esc_html( PHP_EOL . $message_prex . $_POST['message'] );
		}

		$resume->candidate->source = 'New Website';

		// API authentication
		self::api_auth();

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/Candidate'
		);

		$response = wp_remote_get( $url, array( 'body' => json_encode( $resume->candidate ), 'method' => 'PUT' ) );

		$safety_count = 0;
		while ( 500 === $response['response']['code'] && 5 > $safety_count ) {
			error_log( 'Create Canditate failed( ' . $safety_count . '): ' . serialize( $response ) );
			$response = wp_remote_get( $url, array( 'body' => json_encode( $resume->candidate ), 'method' => 'PUT' ) );
			$safety_count ++;
		}

		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

			return json_decode( $response['body'] );
		}

		return false;
	}

	public static function get_post_by_bullhorn_id( $id ){
		$args      = array(
			'meta_key'   => 'bullhorn_job_id',
			'meta_value' => absint( $id ),
			'post_type'  => Bullhorn_2_WP::$post_type_job_listing,
			'number'     => 1,
		);

		$job_post  = get_posts( $args );

		return $job_post[0];

	}

	/**
	 * Create a candidate int he system
	 *
	 * @param $resume
	 * @param array $profile_data
	 *
	 * @return mixed
	 */
	public static function update_candidate( $candidate_id, $candidate ) {


		// API authentication
		self::api_auth();

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/Candidate/' . $candidate_id
		);

		$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $candidate ), 'method' => 'PUT' ) );

		$safety_count = 0;
		while ( 500 === $response['response']['code'] && 5 > $safety_count ) {
			error_log( 'Create Canditate failed( ' . $safety_count . '): ' . serialize( $response ) );
			$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $candidate ), 'method' => 'PUT' ) );
			$safety_count ++;
		}

		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

			return json_decode( $response['body'] );
		}

		return false;
	}


	private static function get_candidate( $candidate_id ) {

		// API authentication
		self::api_auth();

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/Candidate/' . $candidate_id . '?fields=*'
		);

		$response = wp_remote_get( $url );

		$safety_count = 0;
		while ( 500 === $response['response']['code'] && 5 > $safety_count ) {
			error_log( 'Get Canditate failed( ' . $safety_count . '): ' . serialize( $response ) );
			$response = wp_remote_get( $url );
			$safety_count ++;
		}

		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

			return json_decode( $response['body'] );
		}

		return false;
	}

	/**
	 * Attach education to cantitates
	 *
	 * @param $resume
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public static function attachEducation( $resume, $candidate ) {

		if ( empty( $resume->candidateEducation ) ) {

			return false;
		}

		// API authentication
		self::api_auth();

		$responses = array();
		$url       = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/CandidateEducation'
		);

		foreach ( $resume->candidateEducation as $edu ) {
			$edu->candidate     = new stdClass;
			$edu->candidate->id = $candidate->changedEntityId;
			if ( ! is_int( $edu->gpa ) || ! is_float( $edu->gpa ) ) {
				unset( $edu->gpa );
			}

			//$edu_data = json_encode( $edu );

			$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $edu ), 'method' => 'PUT' ) );

			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {
				$responses[] = wp_remote_retrieve_body( $response );
			}
		}

		return json_decode( '[' . implode( ',', $responses ) . ']' );
	}

	/**
	 * Attach Work History to a candidate
	 *
	 * @param $resume
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public static function attach_work_history( $resume, $candidate ) {

		if ( empty( $resume->candidateWorkHistory ) ) {

			return false;
		}
		// API authentication
		self::api_auth();

		// Create the url && variables array
		$responses = array();
		$url       = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/CandidateWorkHistory'
		);

		foreach ( $resume->candidateWorkHistory as $job ) {

			$job->candidate     = new stdClass;
			$job->candidate->id = $candidate->changedEntityId;

			$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $job ), 'method' => 'PUT' ) );

			if ( 200 === $response['response']['code'] ) {
				$responses[] = wp_remote_retrieve_body( $response );
			}
		}

		return json_decode( '[' . implode( ',', $responses ) . ']' );
	}

	/**
	 * Attach Work History to a candidate
	 *
	 * @param $resume
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public static function attach_skills( $resume, $candidate ) {

		if ( empty( $resume->skillList ) ) {
			return false;
		}
		// API authentication
		self::api_auth();
		$skill_ids = array();
		$skillList = self::get_skill_list();
		if ( is_array( $skillList ) ) {
			foreach ( $skillList as $key => $skill ) {

				$skill_ids[] = $key;
			}
		}

		if ( ! empty( $skill_ids ) ) {
			foreach ( $resume->skillList as $skill ) {
				if ( false !== $key = array_search( strtolower( $skill ), $skillList ) ) {
					$skill_ids[] = $key;
				}
			}
			$skill_ids = array_unique( $skill_ids );
		}

		// Create the url && variables array
		$url      = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . '/entity/Candidate/' . $candidate->changedEntityId . '/primarySkills/' . implode( ',', $skill_ids )
		);
		$response = wp_remote_get( $url, array( 'method' => 'PUT' ) );

		if ( 200 === $response['response']['code'] ) {

			return wp_remote_retrieve_body( $response );
		}

		return false;
	}

	/**
	 * Attach Note to a candidate
	 * // not working yet
	 *
	 * @param $resume
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public static function attach_note( $candidate, $local_post_data ) {

		if ( ! isset( $local_post_data['message'] ) ) {
			return false;
		}
		// API authentication
		self::api_auth();

		$data['comments']         = $local_post_data['message'];
//		$data['commentingPerson'] = array( 'id' => $candidate->changedEntityId );
		$data['candidates']       = array(
			array( 'id' => $candidate->changedEntityId )
		);
//		$data['personReference']  = array( 'id' => $candidate->changedEntityId );

//		var_dump(wp_json_encode( $data ));
//		{
//			"commentingPerson": { "id" : "2"},
//"candidates" : [
//            { "id" : "4"}
//            ],
//"comments":"This is note",
//"personReference": { "id" : "2"}
//}
		//https://rest9.bullhornstaffing.com/rest-services/13n5s0/entity/Note?BhRestToken=96dc2cad-8bbd-4826-80d5-f958a56fdad3
// http://developer.bullhorn.com/doc/version_2-0/operations/addnotereference.htm // we might to link it afterwards

		// http://developer.bullhorn.com/doc/version_2-0/entities/entity-note.htm


		// Create the url && variables array
		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/note/'
		);
		var_dump($url);
		$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $data ), 'method' => 'PUT' ) );
		var_dump($response);
die();
		if ( 200 === $response['response']['code'] ) {

			return wp_remote_retrieve_body( $response );
		}

		return false;
	}


	/**
	 * @return array $skill_list
	 */
	public static function get_skill_list() {
		if ( null === self::$session ) {
			self::login();
		}

		$skill_list_id = 'bullhorn_skill_list';

		$skill_list = get_transient( $skill_list_id );
		if ( false === $skill_list ) {
			$skill_list = array();
			$url        = add_query_arg(
				array(
					'BhRestToken' => self::$session,
				), self::$url . 'options/Skill'
			);

			$response = self::request( $url, false );
			if ( is_wp_error( $response ) ) {

				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! isset( $data['data'] ) ) {

				return $skill_list;
			}

			foreach ( $data['data'] as $skill ) {
				$skill_list[ $skill['value'] ] = self::clean_skill_label( $skill['label'] );
			}
			$skill_list = array_unique( $skill_list );
			set_transient( $skill_list_id, $skill_list, HOUR_IN_SECONDS * 6 );
		}

		return $skill_list;
	}

	/**
	 * @return array $skill_list
	 */
	public static function get_userType() {
		if ( null === self::$session ) {
			self::login();
		}

		$user_type_list_id = 'bullhorn_user_type';

		$user_type_list = false;// get_transient( $user_type_list_id );
		if ( false === $user_type_list ) {
			$user_type_list = array();
			$url            = add_query_arg(
				array(
					'BhRestToken' => self::$session,
				), self::$url . 'options/userType'
			);

			$response = self::request( $url, false );
			if ( is_wp_error( $response ) ) {

				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! isset( $data['data'] ) ) {

				return $user_type_list;
			}

			foreach ( $data['data'] as $skill ) {
				$skill_list[ $skill['value'] ] = self::clean_skill_label( $skill['label'] );
			}
			$skill_list = array_unique( $user_type_list );
			set_transient( $user_type_list_id, $skill_list, HOUR_IN_SECONDS * 6 );
		}

		return $user_type_list;
	}

	private static function clean_skill_label( $label ) {
		$label = strtolower( trim( $label ) );

		return $label;
	}

	/**
	 * @param $candidate
	 *
	 * @param null $local_file
	 * @param null $file_name
	 *
	 * @return array|bool|mixed|object
	 */
	public static function wp_upload_file_request( $candidate, $file_data = null ) {

		list( $ext, $format ) = self::get_filetype( $file_data );

		$local_file = ( null === $file_data ) ? $_FILES['resume']['tmp_name'] : $file_data['resume']['tmp_name'];
		$file_name  = ( null === $file_data ) ? $_FILES['resume']['name'] : $file_data['resume']['name'];

		// wp_remote_request way

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
				'externalID'  => 'portfolio', //'Portfolio',
				'fileType'    => 'SAMPLE',
			), self::$url . 'file/Candidate/' . $candidate->changedEntityId . '/raw'
		);

		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() );
		$payload  = '';
		$payload  .= '--' . $boundary;
		$payload  .= "\r\n";
		$payload  .= 'Content-Disposition: form-data; name="portfolio"; filename="' . $file_name . '"' . "\r\n";
		$payload  .= 'Content-Type: ' . $ext . "\r\n"; // If you	know the mime-type
		$payload  .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload  .= "\r\n";
		$payload  .= file_get_contents( $local_file );
		$payload  .= "\r\n";
		$payload  .= '--' . $boundary . '--';
		$payload  .= "\r\n\r\n";

		$args = array(
			'method'  => 'PUT',
			'timeout' => 120, // default is 45 set to 2 minuets for this one call
			'headers' => array(
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'multipart/mixed;boundary=' . $boundary, // Set content type to multipart/form-data
			),
			'body'    => $payload,
		);

		$response = wp_remote_request( $url, $args );

		// try once more if we get an error
		if ( is_wp_error( $response ) || 201 !== $response['response']['code'] ) {
			$response = wp_remote_request( $url, $args );
		}

		if ( 200 === $response['response']['code'] ) {

			return json_decode( $response );
		}

		return false;
	}

	/**
	 * @param $candidate
	 *
	 * @return array|bool|mixed|object
	 */
	public static function wp_upload_html_request( $candidate ) {

		list( $ext, $format ) = self::get_filetype();

		$local_file = $_FILES['resume']['tmp_name'];
		// wp_remote_request way

		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() . $ext );
		$payload  = '';
		$payload  .= '--' . $boundary;
		$payload  .= "\r\n";
		$payload  .= 'Content-Disposition: form-data; name="photo_upload_file_name"; filename="' . $_FILES['resume']['name'] . '"' . "\r\n";
		$payload  .= 'Content-Type: ' . $format . '\r\n'; // If you	know the mime-type
		$payload  .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload  .= "\r\n";
		$payload  .= file_get_contents( $local_file );
		$payload  .= "\r\n";
		$payload  .= '--' . $boundary . '--';
		$payload  .= "\r\n\r\n";

		$args = array(
			'method'  => 'PUT',
			'headers' => array(
				'accept'       => 'application/json',
				// The API returns JSON
				'content-type' => 'multipart/form-data;boundary=' . $boundary,
				// Set content type to multipart/form-data
			),
			'body'    => $payload,
		);

		$url      = add_query_arg(
			array(
				'BhRestToken' => self::$session,
				'format'      => $ext,
			), self::$url . '}/resume/convertToHTML'
		);
		$response = wp_remote_request( $url, $args );

		// try once more if we get an error
		if ( is_wp_error( $response ) || 201 !== $response['response']['code'] ) {
			$response = wp_remote_request( $url, $args );
		}


		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() . $ext );
		$payload  = '';
		$payload  .= '--' . $boundary;
		$payload  .= "\r\n";
		$payload  .= 'Content-Disposition: form-data; name="photo_upload_file_name"; filename="' . $_FILES['resume']['name'] . '"' . "\r\n";
		$payload  .= 'Content-Type: ' . $format . '\r\n'; // If you	know the mime-type
		$payload  .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload  .= "\r\n";
		$payload  .= $response;
		$payload  .= "\r\n";
		$payload  .= '--' . $boundary . '--';
		$payload  .= "\r\n\r\n";

		$args = array(
			'method'  => 'PUT',
			'headers' => array(
				'accept'       => 'application/json',
				// The API returns JSON
				'content-type' => 'multipart/form-data;boundary=' . $boundary,
				// Set content type to multipart/form-data
			),
			'body'    => $payload,
		);

		$url      = add_query_arg(
			array(
				'BhRestToken' => self::$session,
				'externalID'  => 'Portfolio',
				'fileType'    => 'SAMPLE',
			), self::$url . '/file/Candidate/' . $candidate->changedEntityId . '/raw'
		);
		$response = wp_remote_request( $url, $args );

		// try once more if we get an error
		if ( is_wp_error( $response ) || 201 !== $response['response']['code'] ) {
			$response = wp_remote_request( $url, $args );
		}

		if ( 200 === $response['response']['code'] ) {
			return json_decode( $response['body'] );
		}

		return false;

	}

	/**
	 * Link a candidate to job.
	 *
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public static function link_candidate_to_job( $candidate, $job_id = null ) {
		// API authentication
		self::api_auth();

		if ( ! isset( $_POST['position'] ) && null !== $job_id ) {

			return false;
		}

		$job_order = ( null !== $job_id ) ? $job_id : absint( $_POST['position'] );

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/JobSubmission'
		);

		$settings       = (array) get_option( 'bullhorn_settings' );
		$mark_submitted = 'true';
		if ( isset( $settings['mark_submitted'] ) ) {
			$mark_submitted = $settings['mark_submitted'];
		}

		$body = array(
			'candidate'       => array( 'id' => absint( $candidate->changedEntityId ) ),
			'jobOrder'        => array( 'id' => absint( $job_order ) ),
			'source'          => get_bloginfo( 'name' ) . ' Web Site',
			'status'          => 'New Lead',
			'dateWebResponse' => self::microtime_float(), //date( 'u', $date ),// time(),
		);

		$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $body ), 'method' => 'PUT' ) );


		$safety_count = 0;
		while ( 500 === $response['response']['code'] && 5 > $safety_count ) {
			error_log( 'Add to job failed( ' . $safety_count . '): ' . serialize( $response ) );
			$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $body ), 'method' => 'PUT' ) );
			$safety_count ++;
		}

		if ( 200 === $response['response']['code'] ) {

			if ( $mark_submitted ) {
				$body = json_decode( $response['body'] );

				$changed_entity_id = $body->changedEntityId;


				$url = add_query_arg(
					array(
						'BhRestToken' => self::$session,
					), self::$url . 'entity/JobSubmission/' . $changed_entity_id
				);

				$body = array(
					'status'          => 'Submitted',
					'dateWebResponse' => self::microtime_float(), //date( 'u', $date ),// time(),
				);

				$response = wp_remote_post( $url, array( 'body' => wp_json_encode( $body ), 'method' => 'POST' ) );

				while ( 500 === $response['response']['code'] && 5 > $safety_count ) {
					error_log( 'Link to job failed( ' . $safety_count . '): ' . serialize( $response ) );
					$response = wp_remote_get( $url, array( 'body' => wp_json_encode( $body ), 'method' => 'POST' ) );
					$safety_count ++;
				}


			}

			return json_decode( $response['body'] );
		}


		return false;
	}

	/**
	 * get time in microseconds
	 * @return float
	 */
	private static function microtime_float() {
		list( $usec, $sec ) = explode( ' ', microtime() );

		return absint( ( (float) $usec + (float) $sec ) * 100 );
	}

	/**
	 *
	 *
	 * @static
	 *
	 * @param $resume
	 * @param $profile_data
	 */
	private static function add_data_to_canditate_data( $resume, $profile_data ) {
// Make sure country ID is correct
		if ( isset( $resume->candidate->address->countryID ) && is_null( $resume->candidate->address->countryID ) ) {
			$resume->candidate->address->countryID = 1;
		}

		if ( isset( $profile_data['email'] ) && ! empty( $profile_data['email'] ) ) {
			if ( isset( $resume->candidate->email ) ) {
				$cv_email                  = $resume->candidate->email;
				$resume->candidate->email2 = esc_attr( $cv_email );
			}

			$resume->candidate->email = esc_attr( $profile_data['email'] );
		} elseif ( isset( $_POST['email'] ) && ! empty( $_POST['email'] ) ) {
			if ( isset( $resume->candidate->email ) ) {
				$cv_email                  = $resume->candidate->email;
				$resume->candidate->email2 = esc_attr( $cv_email );
			}

			$resume->candidate->email = sanitize_text_field( $_POST['email'] );
		}

		if ( isset( $profile_data['phone'] ) && ! empty( $profile_data['phone'] ) ) {
			if ( isset( $resume->candidate->phone ) ) {
				$cv_phone                  = $resume->candidate->phone;
				$resume->candidate->phone2 = esc_attr( $cv_phone );
			}

			$resume->candidate->phone = esc_attr( $profile_data['phone'] );

		} elseif ( isset( $_POST['phone'] ) && ! empty( $_POST['phone'] ) ) {
			if ( isset( $resume->candidate->phone ) ) {
				$cv_phone                  = $resume->candidate->phone;
				$resume->candidate->phone2 = esc_attr( $cv_phone );
			}

			$resume->candidate->phone = sanitize_text_field( $_POST['phone'] );
		}

		if ( isset( $profile_data['work_phone'] ) ) {

			$resume->candidate->workPhone = esc_attr( $profile_data['work_phone'] );
		} elseif ( isset( $_POST['workPhone'] ) && ! empty( $_POST['workPhone'] ) ) {

			$resume->candidate->workPhone = sanitize_text_field( $_POST['workPhone'] );
		}

		if ( isset( $profile_data['mobile_phone'] ) ) {

			$resume->candidate->mobile = esc_attr( $profile_data['mobile_phone'] );
		} elseif ( isset( $_POST['mobile'] ) && ! empty( $_POST['mobile'] ) ) {

			$resume->candidate->mobile = sanitize_text_field( $_POST['workPhone'] );
		}
		if ( isset( $profile_data['name'] ) ) {

			$resume->candidate->name = esc_attr( $profile_data['name'] );
		} elseif ( isset( $_POST['name'] ) && ! empty( $_POST['name'] ) ) {

			$resume->candidate->name = sanitize_text_field( $_POST['name'] );
		}

		if ( isset( $profile_data['first_name'] ) ) {

			$resume->candidate->firstName = esc_attr( $profile_data['first_name'] );
		} elseif ( isset( $_POST['firstName'] ) && ! empty( $_POST['firstName'] ) ) {

			$resume->candidate->firstName = sanitize_text_field( $_POST['firstName'] );
		}

		if ( isset( $profile_data['last_name'] ) ) {

			$resume->candidate->lastName = esc_attr( $profile_data['last_name'] );
		} elseif ( isset( $_POST['lastName'] ) && ! empty( $_POST['lastName'] ) ) {

			$resume->candidate->lastName = sanitize_text_field( $_POST['lastName'] );
		}

		$address_fields = array(
			'address1'    => 40,
			'address2'    => 40,
			'city'        => 40,
			'state'       => 30,
			'zip'         => 15,
			'countryName' => 99
		);

		foreach ( $address_fields as $key => $length ) {
			if ( isset( $resume->candidate->address->$key ) ) {

				$resume->candidate->address->$key = substr( $resume->candidate->address->$key, 0, $length );
			}
		}

		if ( isset( $profile_data['address'] ) && ! empty( $profile_data['address'] ) ) {
			if ( isset( $resume->candidate->address ) ) {
				$cv_address = $resume->candidate->address;
				if ( is_array( $cv_address ) && ! empty( $cv_address ) ) {

					$resume->candidate->secondaryAddress = $cv_address;
				}
			}

			$address_data = array();

			foreach ( $address_fields as $key => $length ) {
				$address_data[ $key ] = ( isset( $profile_data[ $key ] ) ) ? substr( $profile_data[ $key ], 0, $length ) : '';
			}

			$resume->candidate->address = $address_data;
		} elseif ( isset( $_POST['address1'] ) && ! empty( $_POST['address1'] ) ) {

			if ( isset( $resume->candidate->address ) ) {
				$cv_address = $resume->candidate->address;
				if ( is_array( $cv_address ) && ! empty( $cv_address ) ) {

					$resume->candidate->secondaryAddress = $cv_address;
				}
			}

			$address_data = array();

			foreach ( $address_fields as $key => $length ) {
				$address_data[ $key ] = ( isset( $_POST[ $key ] ) ) ? substr( sanitize_text_field( $_POST[ $key ] ), 0, $length ) : '';
			}

			$resume->candidate->address = $address_data;
		}

		if ( isset( $profile_data['skillList'] ) && ! empty( $profile_data['skillList'] ) ) {
			if ( ! isset( $resume->skillList ) ) {
				$resume->skillList = $profile_data['skillList'];
			} else {
				$resume->skillList = array_merge( $resume->skillList, $profile_data['skillList'] );
			}
		}

		// remove bad fields
		unset( $resume->candidate->editHistoryValue );


		return apply_filters( 'bullhorn_add_data_to_canditate_data', $resume, $profile_data );
	}
}
