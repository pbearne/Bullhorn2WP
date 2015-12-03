<?php

class Bullhorn_Connection {

	/**
	 * Stores the settings for the connection, including the client ID, client
	 * secret, etc.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Stores the credentials we need for logging into the API (access token,
	 * refresh token, etc.).
	 *
	 * @var array
	 */
	private $api_access;

	/**
	 * Stores the session variable we need in requests to Bullhorn.
	 *
	 * @var string
	 */
	protected $session;

	/**
	 * Stores the URL we need to make requests to (includes the corpToken).
	 *
	 * @var string
	 */
	protected $url;

	/**
	 * Array to cache the categories retrieved from bullhorn.
	 *
	 * @var array
	 */
	private $categories = array();

	/**
	 * Constructor that just gets and sets the settings/access arrays.
	 *
	 * @return \Bullhorn_Connection
	 */
	public function __construct() {
		$this->settings = get_option( 'bullhorn_settings' );
		$this->api_access = get_option( 'bullhorn_api_access' );
	}

	/**
	 * This should be the only method that is called externally, as it handles
	 * all processing of jobs from Bullhorn into WordPress.
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function sync() {
		// Refresh the token if necessary before doing anything
		$this->refreshToken();

		$logged_in = $this->login();
		if ( ! $logged_in ) {
			throw new Exception('There was a problem logging into the Bullhorn API.');
		}

		wp_defer_term_counting( true );

		$this->getCategoriesFromBullhorn();

		$jobs = $this->getJobsFromBullhorn();
		$existing = $this->getExisting();

		$this->removeOld( $jobs );

		if ( count( $jobs ) ) {
			foreach ( $jobs as $job ) {
				if ( isset( $existing[$job->id] ) ) {
					$this->syncJob( $job, $existing[$job->id] );
				} else {
					$this->syncJob( $job );
				}
			}
		}

		wp_defer_term_counting( false );

		return true;
	}

	/**
	 * This allows our application to log into the API so we can get the session
	 * and corpToken to use in subsequent requests.
	 *
	 * @throws Exception
	 * @return boolean
	 */
	protected function login() {
		$url = 'https://rest.bullhornstaffing.com/rest-services/login?version=*&access_token=' . $this->api_access['access_token'];
		$response = $this->request( $url );
		$body = json_decode( $response['body'] );

		if ( isset( $body->BhRestToken ) ) {
			$this->session = $body->BhRestToken;
			$this->url = $body->restUrl;

			return true;
		}

		if ( isset( $body->errorMessage ) ) {
			throw new Exception($body->errorMessage);
		}

		return false;
	}

	/**
	 * Every 10 minutes we need to refresh our access token for continued access
	 * to the API. We first determine if we need to refresh, and then we need to
	 * request a new token from Bullhorn if our current one has expired.
	 *
	 * @return boolean
	 */
	protected function refreshToken() {
		$url = 'https://auth.bullhornstaffing.com/oauth/token';
		$params = array(
			'grant_type'    => 'refresh_token',
			'refresh_token' => $this->api_access['refresh_token'],
			'client_id'     => $this->settings['client_id'],
			'client_secret' => $this->settings['client_secret'],
		);

		$response = wp_remote_post( $url . '?' . http_build_query( $params ) );
		$body = json_decode( $response['body'], true );

		if ( isset( $body['access_token'] ) ) {
			$body['last_refreshed'] = time();
			update_option( 'bullhorn_api_access', $body );

			$this->api_access = get_option( 'bullhorn_api_access' );
		}

		return true;
	}

	/**
	 * This retreives all available categories from Bullhorn.
	 *
	 * @return array
	 */
	private function getCategoriesFromBullhorn() {
		$url = $this->url . 'options/Category';
		$params = array(
			'BhRestToken' => $this->session,
		);

		$response = $this->request( $url . '?' . http_build_query( $params ) );
		$body = json_decode( $response['body'] );
		if ( isset( $body->data ) ) {
			foreach ( $body->data as $category ) {
				wp_insert_term( $category->label, 'bullhorn_category' );
			}
		}

		return array();
	}

	/**
	 * Gets the description field as chosen by the user in settings.
	 *
	 * @return string
	 */
	private function getDescriptionField()
	{
		if (isset($this->settings['description_field'])) {
			$description = $this->settings['description_field'];
		} else {
			$description = 'description';
		}

		return $description;
	}

	/**
	 * This retreives all available jobs from Bullhorn.
	 *
	 * @return array
	 */
	private function getJobsFromBullhorn() {
		// Use the specified description field if set, otherwise the default
		$description = $this->getDescriptionField();

		$start = 0;
		$page = 100;
		$jobs = array();
		while ( true ) {
			$url = $this->url . 'query/JobOrder';
			$params = array(
				'BhRestToken' => $this->session,
				'fields' => 'id,title,' . $description . ',dateAdded,categories,address',
				'where' => 'isPublic=1 AND isOpen=true AND isDeleted=false',
				'count' => $page,
				'start' => $start,
			);

			if ( isset( $this->settings['client_corporation'] ) and ! empty( $this->settings['client_corporation'] ) ) {
				$ids = explode(',', $this->settings['client_corporation']);
				$ids = array_map('trim', $ids);

				$params['where'] .= ' AND (clientCorporation.id=' . implode(' OR clientCorporation.id=', $ids) . ')';
			}

			$response = $this->request( $url . '?' . http_build_query( $params ) );
			$body = json_decode( $response['body'] );
			if ( isset( $body->data ) ) {
				$start += $page;

				$jobs = array_merge( $jobs, $body->data );

				if ( count( $body->data ) < $page ) {
					break;
				}
			} else {
				break;
			}
		}

		return $jobs;
	}

	/**
	 * This will take a job object from Bullhorn and insert it into WordPress
	 * with the proper fields, custom fields, and taxonomy relationships. If
	 * the job already exists in WordPress it simply updates the fields.
	 *
	 * @return boolean
	 */
	private function syncJob( $job, $id = null ) {
		$description = $this->getDescriptionField();

		$post = array(
			'post_title'   => $job->title,
			'post_content' => $job->{$description},
			'post_type'    => 'bullhornjoblisting',
			'post_status'  => 'publish',
			'post_date'    => date( 'Y-m-d H:i:s', $job->dateAdded / 1000 ),
		);

		if ( $id ) {
			$post['ID'] = $id;

			$id = wp_update_post( $post );
		} else {
			$id = wp_insert_post( $post );
		}

		$address = (array) $job->address;
		unset( $address['countryID'] );

		$custom_fields = array(
			'bullhorn_job_id' => $job->id,
			'bullhorn_job_address' => implode( ' ', $address ),
		);
		foreach ( $custom_fields as $key => $val ) {
			add_post_meta( $id, $key, $val, true );
		}

		$categories = array();
		foreach ( $job->categories->data as $category ) {
			$category_id = $category->id;

			// Check to see if this category name has been cached already
			if ( isset( $this->categories[$category_id] ) ) {
				$categories[] = $this->categories[$category_id];
			} else {
				$url = $this->url . 'entity/Category/' . $category_id;
				$params = array('BhRestToken' => $this->session, 'fields' => 'id,name');
				$response = $this->request( $url . '?' . http_build_query( $params ) );

				$category = json_decode( $response['body'] );
				if ( isset( $category->data->name ) ) {
					$categories[] = $category->data->name;

					// Cache this category in an array
					$this->categories[$category_id] = $category->data->name;
				}
			}
		}

		wp_set_object_terms( $id, $categories, 'bullhorn_category' );
		wp_set_object_terms( $id, array( $job->address->state ), 'bullhorn_state' );

		return true;
	}

	/**
	 * Before we start adding in new jobs, we need to delete jobs that are no
	 * longer in Bullhorn.
	 *
	 * @param  array   $jobs
	 * @return boolean
	 */
	private function removeOld( $jobs ) {
		$ids = array();
		foreach ( $jobs as $job ) {
			$ids[] = $job->id;
		}

		$jobs = new WP_Query( array(
			'post_type'      => 'bullhornjoblisting',
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'meta_query'     => array(
				array(
					'key'     => 'bullhorn_job_id',
					'value'   => $ids,
					'compare' => 'NOT IN',
				),
			),
		) );

		if ( $jobs->have_posts() ) {
			while ( $jobs->have_posts() ) {
				$jobs->the_post();

				// Don't trash post, actually delete it
				wp_delete_post( get_the_ID(), true );
			}
		}

		return true;
	}

	/**
	 * Gets an array of IDs for existing jobs in the WordPress CPT.
	 *
	 * @return array
	 */
	private function getExisting() {
		global $wpdb;

		$posts = $wpdb->get_results( "SELECT $wpdb->posts.id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta JOIN $wpdb->posts ON $wpdb->posts.id = $wpdb->postmeta.post_id WHERE meta_key = 'bullhorn_job_id'", ARRAY_A );

		$existing = array();
		foreach ($posts as $post)
		{
			$existing[$post['meta_value']] = $post['id'];
		}

		return $existing;
	}

	/**
	 * Wrapper around wp_remote_get() so any errors are reported to the screen.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function request( $url ) {
		$response = wp_remote_get( $url, array( 'timeout' => 180 ) );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		return $response;
	}

}
