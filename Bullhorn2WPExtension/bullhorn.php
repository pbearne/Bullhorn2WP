<?php

	use GuzzleHttp\Client;
	use GuzzleHttp\Post\PostFile;
	use GuzzleHttp\Exception\ClientException;


	/**
	 * This class is an extension of Bullhorn_Connection.  Its purpose
	 * is to allow for resume and candidate posting
	 *
	 * Class Bullhorn_Extended_Connection
	 */
	class Bullhorn_Extended_Connection extends Bullhorn_Connection
	{

		/**
		 * Class Constructor
		 *
		 * @return \Bullhorn_Extended_Connection
		 */
		public function __construct()
		{
			// Call parent __construct()
			parent::__construct();
		}


		/**
		 * Takes the posted 'resume' file and returns a parsed version from bullhorn
		 *
		 * @return mixed
		 */
		public function parseResume()
		{

			// check to make sure file was posted
			if ( ! isset($_FILES['resume']))
			{
				$this->throwJsonError(500, 'No "resume" file found.');
			}

			// Get file extension
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$ext   = finfo_file($finfo, $_FILES['resume']['tmp_name']);
			finfo_close($finfo);

			switch (strtolower($ext))
			{
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
					$this->throwJsonError(500, 'File format error. (txt, html, pdf, doc, docx, rft)');
			}

			// API authentication
			$this->apiAuth();

			// Create the url && variables array
			$url    = $this->url . 'resume/parseToCandidate';
			$params = array('BhRestToken' => $this->session, 'format' => $format);

			try
			{
				$client   = new Client();
				$request  = $client->createRequest('POST', $url . '?' . http_build_query($params));
				$postBody = $request->getBody();
				$postBody->addFile(new PostFile('resume', fopen($_FILES['resume']['tmp_name'], 'r')));
				$response = $client->send($request);

				return json_decode($response->getBody());
			}
			catch (ClientException $e)
			{
				$error = json_decode($e->getResponse()->getBody());
				$this->throwJsonError(500, $error->errorMessage);
			}
		}

		/**
		 * Create a candidate int he system
		 *
		 * @param $resume
		 * @return mixed
		 */
		public function createCandidate($resume)
		{
			// Make sure country ID is correct
			if (is_null($resume->candidate->address->countryID))
			{
				$resume->candidate->address->countryID = 1;
			}

			$candidate_data = json_encode($resume->candidate);

			// API authentication
			$this->apiAuth();

			// Create the url && variables array
			$url    = $this->url . 'entity/Candidate';
			$params = array('BhRestToken' => $this->session);

			try
			{
				$client   = new Client();
				$response = $client->put($url . '?' . http_build_query($params), array('body' => $candidate_data));

				return json_decode($response->getBody());
			}
			catch (ClientException $e)
			{
				$error = json_decode($e->getResponse()->getBody());
				$this->throwJsonError(500, $error->errorMessage);
			}
		}

		/**
		 * Attach education to cantitates
		 *
		 * @param $resume
		 * @param $candidate
		 * @return mixed
		 */
		public function attachEducation($resume, $candidate)
		{
			// API authentication
			$this->apiAuth();

			// Create the url && variables array
			$url    = $this->url . 'entity/CandidateEducation';
			$params = array('BhRestToken' => $this->session);

			$responses = array();

			foreach ($resume->candidateEducation as $edu)
			{
				$edu->candidate     = new stdClass;
				$edu->candidate->id = $candidate->changedEntityId;
				if ( ! is_int($edu->gpa) || ! is_float($edu->gpa))
				{
					unset($edu->gpa);
				}

				$edu_data = json_encode($edu);

				try
				{
					$client   = new Client();
					$response = $client->put($url . '?' . http_build_query($params), array('body' => $edu_data));

					$responses[] = $response->getBody();
				}
				catch (ClientException $e)
				{
					$error = json_decode($e->getResponse()->getBody());
					$this->throwJsonError(500, $error->errorMessage);
				}
			}

			return json_decode('[' . implode(',', $responses) . ']');
		}

		/**
		 * Attach Work History to a candidate
		 *
		 * @param $resume
		 * @param $candidate
		 * @return mixed
		 */
		public function attachWorkHistory($resume, $candidate)
		{
			// API authentication
			$this->apiAuth();

			// Create the url && variables array
			$url    = $this->url . 'entity/CandidateWorkHistory';
			$params = array('BhRestToken' => $this->session);

			$responses = array();

			foreach ($resume->candidateWorkHistory as $job)
			{
				$job->candidate     = new stdClass;
				$job->candidate->id = $candidate->changedEntityId;

				$job_data = json_encode($job);

				try
				{
					$client   = new Client();
					$response = $client->put($url . '?' . http_build_query($params), array('body' => $job_data));

					$responses[] = $response->getBody();
				}
				catch (ClientException $e)
				{
					$error = json_decode($e->getResponse()->getBody());
					$this->throwJsonError(500, $error->errorMessage);
				}
			}

			return json_decode('[' . implode(',', $responses) . ']');
		}

		/**
		 * Attach Resume to a candidate.  this pulls the original resume file from the $_FILES array
		 *
		 * @param $candidate
		 * @return mixed
		 */
		public function attachResume($candidate)
		{
			// API authentication
			$this->apiAuth();

			// Create the url && variables array
			$url    = $this->url . '/file/Candidate/' . $candidate->changedEntityId . '/raw';
			$params = array('BhRestToken' => $this->session, 'externalID' => 'Portfolio', 'fileType' => 'SAMPLE');

			try
			{
				$client   = new Client();
				$response = $client->put($url . '?' . http_build_query($params), array('body' => array('resume' => fopen($_FILES['resume']['tmp_name'], 'r'))));

				return json_decode($response->getBody());
			}
			catch (ClientException $e)
			{
				$error = json_decode($e->getResponse()->getBody());
				$this->throwJsonError(500, $error->errorMessage);
			}
		}

		/**
		 * Send a json error to the screen
		 *
		 * @param $status
		 * @param $error
		 */
		private function throwJsonError($status, $error)
		{
			$response = array('status' => $status, 'error' => $error);
			echo json_encode($response);
			exit;
		}

		/**
		 * Run this befor any api call.
		 *
		 * @return void
		 */
		private function apiAuth()
		{
			// Refresh the token if necessary before doing anything
			$this->refreshToken();

			// login to bullhorn api
			$logged_in = $this->login();
			if ( ! $logged_in)
			{
				$this->throwJsonError(500, 'There was a problem logging into the Bullhorn API.');
			}
		}

	}