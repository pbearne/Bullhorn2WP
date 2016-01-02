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
	 * Takes the posted 'resume' file and returns a parsed version from bullhorn
	 *
	 * @return mixed
	 */
	public function parseResume() {

		// check to make sure file was posted
		if ( ! isset( $_FILES['resume'] ) ) {
			self::throwJsonError( 500, 'No "resume" file found.' );
		}
		list( $ext, $format ) = self::get_filetype();

		// API authentication
		self::apiAuth();

		// http://gerhardpotgieter.com/2014/07/30/uploading-files-with-wp_remote_post-or-wp_remote_request/
		$local_file = $_FILES['resume']['tmp_name'];
		// wp_remote_request way

		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() );
		$payload  = '';
		$payload .= '--' . $boundary;
		$payload .= "\r\n";
		$payload .= 'Content-Disposition: form-data; name="photo_upload_file_name"; filename="' . $_FILES['resume']['name'] . '"' . "\r\n";
		$payload .= 'Content-Type: ' . $ext . '\r\n'; // If you	know the mime-type
		$payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload .= "\r\n";
		$payload .= file_get_contents( $local_file );
		$payload .= "\r\n";
		$payload .= '--' . $boundary . '--';
		$payload .= "\r\n\r\n";

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'multipart/form-data;boundary=' . $boundary, // Set content type to multipart/form-data
			),
			'body'    => $payload,
		);

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
				'format'      => $format,
			), self::$url . 'resume/parseToCandidate'
		);

		$response = wp_remote_request( $url, $args );
		if ( 200 === $response['response']['code'] ) {
			return json_decode( $response['body'] );
		}

		return false;
	}

	/**
	 * Create a candidate int he system
	 *
	 * @param $resume
	 *
	 * @return mixed
	 */
	public function createCandidate( $resume ) {

		// Make sure country ID is correct
		if ( is_null( $resume->candidate->address->countryID ) ) {
			$resume->candidate->address->countryID = 1;
		}

		if ( isset( $_POST['email'] ) ) {
			$cv_email = $resume->candidate->email;

			$resume->candidate->email  = esc_attr( $_POST['email'] );
			$resume->candidate->email2 = esc_attr( $cv_email );
		}
		if ( isset( $_POST['phone'] ) ) {
			$cv_phone = $resume->candidate->phone;

			$resume->candidate->phone  = esc_attr( $_POST['phone'] );
			$resume->candidate->phone2 = esc_attr( $cv_phone );
		}
		if ( isset( $_POST['name'] ) ) {
			$resume->candidate->name = esc_attr( $_POST['name'] );
		}

		//$candidate_data = json_encode( $resume->candidate );

		// API authentication
		self::apiAuth();

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/Candidate'
		);

		$response = wp_remote_get( $url, array( 'body' => json_encode( $resume->candidate ), 'method' => 'PUT' ) );

		if ( 200 === $response['response']['code'] ) {
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
	public function attachEducation( $resume, $candidate ) {

		if ( empty( $resume->candidateEducation ) ) {
			return false;
		}

		// API authentication
		self::apiAuth();

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

			$response = wp_remote_get( $url, array( 'body' => json_encode( $edu ), 'method' => 'PUT' ) );

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
	public function attachWorkHistory( $resume, $candidate ) {
		echo ( '<pre>');
		if ( empty( $resume->candidateWorkHistory ) ) {
			return false;
		}
		// API authentication
		self::apiAuth();

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

			$response = wp_remote_get( $url, array( 'body' => json_encode( $job ), 'method' => 'PUT' ) );

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
	public function attachSkills( $resume, $candidate ) {

		if ( empty( $resume->skillList ) ) {
			return false;
		}
		// API authentication
		self::apiAuth();
		$resume->skillList[] = 'Java';
		$skillList = self::get_skill_list();

		$skill_ids = array();
		foreach ( $resume->skillList as $skill ) {
			if ( false !== $key = array_search( strtolower( $skill ), $skillList ) ) {
				$skill_ids[] = $key;
			}
		}
		$skill_ids = array_unique( $skill_ids );

		// Create the url && variables array
		$url       = add_query_arg(
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
	 * @return array $skill_list
	 */
	public static function get_skill_list() {
		$skill_list_id = 'bullhorn_skill_list';

		$skill_list = get_transient( $skill_list_id );
		if ( false === $skill_list ) {
			$url       = add_query_arg(
				array(
					'BhRestToken' => self::$session,
				), self::$url . 'options/Skill'
			);

			$response = wp_remote_get( $url, array( 'method' => 'GET' ) );
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true )['data'];

			$skill_list = array();
			foreach ( $data as $skill ) {
				$skill_list[ $skill['value'] ] = self::clean_skill_label( $skill['label'] );
			}

			set_transient( $skill_list_id, $skill_list, HOUR_IN_SECONDS * 6 );
		}

		return $skill_list;
	}

	private static function clean_skill_label( $label ) {
		$label = strtolower( trim( $label ) );

		return $label;
	}


	/**
	 * @param $candidate
	 *
	 * @return array|bool|mixed|object
	 */
	public function wp_upload_file_request( $candidate ) {

		list( $ext, $format ) = self::get_filetype();

		$local_file = $_FILES['resume']['tmp_name'];
		// wp_remote_request way

		//https://github.com/jeckman/wpgplus/blob/master/gplus.php#L554
		$boundary = md5( time() . $ext );
		$payload  = '';
		$payload .= '--' . $boundary;
		$payload .= "\r\n";
		$payload .= 'Content-Disposition: form-data; name="photo_upload_file_name"; filename="' . $_FILES['resume']['name'] . '"' . "\r\n";
		$payload .= 'Content-Type: ' . $format . '\r\n'; // If you	know the mime-type
		$payload .= 'Content-Transfer-Encoding: binary' . "\r\n";
		$payload .= "\r\n";
		$payload .= file_get_contents( $local_file );
		$payload .= "\r\n";
		$payload .= '--' . $boundary . '--';
		$payload .= "\r\n\r\n";

		$args = array(
			'method'  => 'PUT',
			'headers' => array(
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'multipart/form-data;boundary=' . $boundary, // Set content type to multipart/form-data
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
	public function link_candidate_to_job( $candidate ) {
		// API authentication
		self::apiAuth();

		if ( ! isset( $_POST['position'] ) ) {
			return false;
		}
		$jobOrder = absint( $_POST['position'] );

		$url = add_query_arg(
			array(
				'BhRestToken' => self::$session,
			), self::$url . 'entity/JobSubmission'
		);

		$body = array(
			'candidate'       => array( 'id' => absint( $candidate->changedEntityId ) ),
			'jobOrder'        => array( 'id' => absint( $jobOrder ) ),
			'status'          => 'New Lead',
			'dateWebResponse' => self::microtime_float(), //date( 'u', $date ),// time(),
		);

		$response = wp_remote_get( $url, array( 'body' => json_encode( $body ), 'method' => 'PUT' ) );

		if ( 200 === $response['response']['code'] ) {
			return json_decode( $response['body'] );
		}

		return false;
	}


	/**
	 * get time in microseconds
	 * @return float
	 */
	private function microtime_float() {
		list( $usec, $sec ) = explode( ' ', microtime() );

		return ( (float) $usec + (float) $sec ) * 100;
	}

	/**
	 * Send a json error to the screen
	 *
	 * @param $status
	 * @param $error
	 */
	function throwJsonError( $status, $error ) {
		$response = array( 'status' => $status, 'error' => $error );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Run this before any api call.
	 *
	 * @return void
	 */
	private function apiAuth() {
		// Refresh the token if necessary before doing anything
		self::refresh_token();

		// login to bullhorn api
		$logged_in = self::login();
		if ( ! $logged_in ) {
			self::throwJsonError( 500, 'There was a problem logging into the Bullhorn API.' );
		}
	}

	/**
	 * Update vars
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	function add_query_vars( $vars ) {
		$vars[] = '__api';
		$vars[] = 'endpoint';

		return $vars;
	}

	/**
	 * Initialize the reqrite rule
	 *
	 * @return void
	 */
	function add_endpoint() {
		add_rewrite_rule( '^api/bullhorn/([^/]+)/?', 'index.php?__api=1&endpoint=$matches[1]', 'top' );
	}

	/**
	 * Check to see if the request is a bullhorn API request
	 *
	 * @return void
	 */
	function sniff_requests() {
		global $wp;
		if ( isset( $wp->query_vars['__api'] ) && isset( $wp->query_vars['endpoint'] ) ) {
			switch ( $wp->query_vars['endpoint'] ) {
				case 'resume':

					if (
						! isset( $_POST['bullhorn_cv_form'] )
						|| ! wp_verify_nonce( $_POST['bullhorn_cv_form'], 'bullhorn_cv_form' )
					) {
						print 'Sorry, your nonce did not verify.';
						die();

					}
					//$bullhorn = new Bullhorn_Extended_Connection;

					// Get Resume
					$resume = self::parseResume();

					// Create candidate
					$candidate = self::createCandidate( $resume );

					// Attach education to candidate
					self::attachEducation( $resume, $candidate );

					// Attach work history to candidate
					self::attachWorkHistory( $resume, $candidate );
					//var_dump($resume->candidateWorkHistory);

					// Attach work history to candidate
					self::attachSkills( $resume, $candidate );

					// Attach resume file to candidate
					self::wp_upload_file_request( $candidate );

					// link to job
					self::link_candidate_to_job( $candidate );

					// Redirect
					$settings  = (array) get_option( 'bullhorn_extension_settings' );
					$permalink = add_query_arg( array(
						'bh_applied' => true,
					), get_permalink( $settings['thanks_page'] ) );

					header( "location: $permalink" );
					exit;

					break;
				default:
					$response = array(
						'status' => 404,
						'error'  => 'The endpoint you are trying to reach does not exist.',
					);
					echo json_encode( $response );
			}
			exit;
		}
	}

	/**
	 * @return array
	 */
	private static function get_filetype() {
		// Get file extension
		$file_type = wp_check_filetype_and_ext( $_FILES['resume']['tmp_name'], $_FILES['resume']['name'] );
		$ext       = $file_type['type'];

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
				self::throwJsonError( 500, 'File format error. (txt, html, pdf, doc, docx, rft)' );

				return array( $ext, $format );
		}

		return array( $ext, $format );
	}
}

new Bullhorn_Extended_Connection();
