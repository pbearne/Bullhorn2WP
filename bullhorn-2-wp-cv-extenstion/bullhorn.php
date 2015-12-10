<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Post\PostFile;


require_once dirname( dirname( __FILE__ ) ) . '/bullhorn-2-wp/bullhorn-2-wp.php';

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
	}


	/**
	 * Takes the posted 'resume' file and returns a parsed version from bullhorn
	 *
	 * @return mixed
	 */
	public function parseResume() {

		// check to make sure file was posted
		if ( ! isset( $_FILES['resume'] ) ) {
			$this->throwJsonError( 500, 'No "resume" file found.' );
		}
//TODO: cahnge to WP function  wp_check_filetype( $filename, $mimes ) as this doen't work on windows
		// Get file extension
		$finfo = finfo_open( FILEINFO_MIME_TYPE ); // return mime type ala mimetype extension
		$ext   = finfo_file( $finfo, $_FILES['resume']['tmp_name'] );
		finfo_close( $finfo );

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
				$this->throwJsonError( 500, 'File format error. (txt, html, pdf, doc, docx, rft)' );
		}

		// API authentication
		$this->apiAuth();

		// Create the url && variables array
		$url    = $this->url . 'resume/parseToCandidate';
		$params = array( 'BhRestToken' => $this->session, 'format' => $format );

		try {
			$client   = new Client();
			$request  = $client->createRequest( 'POST', $url . '?' . http_build_query( $params ) );
			$postBody = $request->getBody();
			$postBody->addFile( new PostFile( 'resume', fopen( $_FILES['resume']['tmp_name'], 'r' ) ) );
			$response = $client->send( $request );

			return json_decode( $response->getBody() );
		} catch ( ClientException $e ) {
			$error = json_decode( $e->getResponse()->getBody() );
			$this->throwJsonError( 500, $error->errorMessage );
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

		$candidate_data = json_encode( $resume->candidate );

		// API authentication
		$this->apiAuth();

		// Create the url && variables array
		$url    = $this->url . 'entity/Candidate';
		$params = array( 'BhRestToken' => $this->session );

		try {
			$client   = new Client();
			$response = $client->put( $url . '?' . http_build_query( $params ), array( 'body' => $candidate_data ) );

			return json_decode( $response->getBody() );
		} catch ( ClientException $e ) {
			$error = json_decode( $e->getResponse()->getBody() );
			$this->throwJsonError( 500, $error->errorMessage );
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

		if ( ! isset( $resume->candidateEducation ) ) {
			return false;
		}

		// API authentication
		$this->apiAuth();

		// Create the url && variables array
		$url    = $this->url . 'entity/CandidateEducation';
		$params = array( 'BhRestToken' => $this->session );

		$responses = array();

		foreach ( $resume->candidateEducation as $edu ) {
			$edu->candidate     = new stdClass;
			$edu->candidate->id = $candidate->changedEntityId;
			if ( ! is_int( $edu->gpa ) || ! is_float( $edu->gpa ) ) {
				unset( $edu->gpa );
			}

			$edu_data = json_encode( $edu );

			try {
				$client   = new Client();
				$response = $client->put( $url . '?' . http_build_query( $params ), array( 'body' => $edu_data ) );

				$responses[] = $response->getBody();
			} catch ( ClientException $e ) {
				$error = json_decode( $e->getResponse()->getBody() );
				$this->throwJsonError( 500, $error->errorMessage );
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
		if ( ! isset( $resume->CandidateWorkHistory ) ) {
			return false;
		}
		// API authentication
		$this->apiAuth();

		// Create the url && variables array
		$url    = $this->url . 'entity/CandidateWorkHistory';
		$params = array( 'BhRestToken' => $this->session );

		$responses = array();

		foreach ( $resume->candidateWorkHistory as $job ) {
			$job->candidate     = new stdClass;
			$job->candidate->id = $candidate->changedEntityId;

			$job_data = json_encode( $job );

			try {
				$client   = new Client();
				$response = $client->put( $url . '?' . http_build_query( $params ), array( 'body' => $job_data ) );

				$responses[] = $response->getBody();
			} catch ( ClientException $e ) {
				$error = json_decode( $e->getResponse()->getBody() );
				$this->throwJsonError( 500, $error->errorMessage );
			}
		}

		return json_decode( '[' . implode( ',', $responses ) . ']' );
	}

	/**
	 * Attach Resume to a candidate.  this pulls the original resume file from the $_FILES array
	 *
	 * @param $candidate
	 *
	 * @return mixed
	 */
	public function attachResume( $candidate ) {
		// API authentication
		$this->apiAuth();
//echo '<pre>';
		// Create the url && variables array
		$url    = $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw';
		$params = array( 'BhRestToken' => $this->session, 'externalID' => 'Portfolio', 'fileType' => 'SAMPLE' );

		try {
			$client   = new Client();
			$response = $client->put( $url . '?' . http_build_query( $params ),
				array( 'body' => array( 'resume' => new PostFile( 'resume', fopen( $_FILES['resume']['tmp_name'], 'r' ) ) ) ) );
//var_dump($response);

//			$request  = $client->createRequest( 'POST', $url . '?' . http_build_query( $params ) );
//			$postBody = $request->getBody();
//			$postBody->addFile( new PostFile( 'resume', fopen( $_FILES['resume']['tmp_name'], 'r' ) ) );
//			$response = $client->send( $request );

//			var_dump($response->getBody());

//			$url = add_query_arg(
//				array(
//					'BhRestToken' => $this->session,
//				), $this->url . '/entityFiles/Candidate/'. $candidate->changedEntityId
//			);
//
//			$response = wp_remote_get( $url );

//			var_dump($response);
//
//			die();
			return json_decode( $response->getBody() );
		} catch ( ClientException $e ) {
			$error = json_decode( $e->getResponse()->getBody() );
			$this->throwJsonError( 500, $error->errorMessage );
		}

		return false;
	}


	public function wp_upload_file_request( $candidate ) {
		echo '<pre>';
		// http://gerhardpotgieter.com/2014/07/30/uploading-files-with-wp_remote_post-or-wp_remote_request/
		$local_file = $_FILES['resume']['tmp_name'];
		// wp_remote_request way
		$file      = fopen( $local_file, 'r' );
		$file_size = filesize( $local_file );
		$file_data = fread( $file, $file_size );
		$args      = array(
			'method'  => 'PUT',
			'headers' => array(
				'accept'       => 'application/json', // The API returns JSON
				'content-type' => 'application/binary', // Set content type to binary
			),
			'body'    => $file_data,
		);

		$url = add_query_arg(
			array(
				'BhRestToken' => $this->session,
			), $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw'
		);

		$response = wp_remote_request( $url, $args );
		var_dump( $response );
		die();
//		if ( 200 === $response['response']['code'] ) {
//			return json_decode( $response['body'] );
//		}
//		return false;

	}


	public function wp_upload_file( $candidate ) {
		echo '<pre>';


		// Create the url && variables array
//		$url    = $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw';
//		$params = array( 'BhRestToken' => $this->session, 'externalID' => 'Portfolio', 'fileType' => 'SAMPLE' );

//		try {
//			$client   = new Client();
//			$response = $client->put( $url . '?' . http_build_query( $params ), array( 'body' => array( 'resume' => fopen( $_FILES['resume']['tmp_name'], 'r' ) ) ) );
		$boundary = wp_generate_password( 24 );
		$headers  = array(
			'content-type' => 'multipart/form-data; boundary=' . $boundary
		);


		$url        = add_query_arg(
			array(
				'BhRestToken' => $this->session,
			), $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw'
		);
		$payload    = '';
		$local_file = $_FILES['resume']['tmp_name'];
// Upload the file
		if ( $local_file ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . 'upload' .
			            '"; filename="' . basename( $local_file ) . '"' . "\r\n";
			$payload .= 'Content-Type: image/jpeg' . "\r\n";
			$payload .= "\r\n";
			$payload .= file_get_contents( $local_file );
			$payload .= "\r\n";
		}
		$payload .= '--' . $boundary . '--';


		$response = wp_remote_get( $url, array( 'body' => $payload, 'method' => 'PUT', 'headers' => $headers ) );

//		if ( 200 === $response['response']['code'] ) {
//			return json_decode( $response['body'] );
//		}
//		return false;


		var_dump( $response );
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
		$this->apiAuth();

		echo '<pre>';
		//	var_dump( $candidate );

		// Create the url && variables array
//		$url    = $this->url . 'entity/JobSubmission';
//		$params = array( 'BhRestToken' => $this->session  );

		if ( ! isset( $_POST['position'] ) ) {
			return false;
		}
		$jobOrder = absint( $_POST['position'] );

		$url = add_query_arg(
			array(
				'BhRestToken' => $this->session,
			), $this->url . 'entity/JobSubmission'
		);

		$body = array(
			'candidate'       => array( 'id' => absint( $candidate->changedEntityId ) ),
			'jobOrder'        => array( 'id' => absint( $jobOrder ) ),
			'status'          => 'New Lead',
			'dateWebResponse' => $this->microtime_float(), //date( 'u', $date ),// time(),
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
		$this->refresh_token();

		// login to bullhorn api
		$logged_in = $this->login();
		if ( ! $logged_in ) {
			$this->throwJsonError( 500, 'There was a problem logging into the Bullhorn API.' );
		}
	}
}