<?php

class Bullhorn_Connection {

	/**
	 * Stores the settings for the connection, including the client ID, client
	 * secret, etc.
	 *
	 * @var array
	 */
	protected static $settings;

	/**
	 * Stores the credentials we need for logging into the API (access token,
	 * refresh token, etc.).
	 *
	 * @var array
	 */
	protected static $api_access;

	/**
	 * Stores the session variable we need in requests to Bullhorn.
	 *
	 * @var string
	 */
	protected static $session;

	/**
	 * Stores the URL we need to make requests to (includes the corpToken).
	 *
	 * @var string
	 */
	protected static $url;

	/**
	 * Array to cache the categories retrieved from bullhorn.
	 *
	 * @var array
	 */
	private static $categories = array();

	//protected $settings;

	/**
	 * Constructor that just gets and sets the settings/access arrays.
	 *
	 * @return \Bullhorn_Connection
	 */
	public function __construct() {
		self::$settings   = get_option( 'bullhorn_settings' );
		self::$api_access = get_option( 'bullhorn_api_access' );
	}

	/**
	 * This should be the only method that is called externally, as it handles
	 * all processing of jobs from Bullhorn into WordPress.
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public static function sync( $throw = true ) {
		// Refresh the token if necessary before doing anything
		if ( false === self::refresh_token() ) {
			return false;
		};

		$logged_in = self::login();
		if ( ! $logged_in ) {
			if ( $throw ) {
				throw new Exception( __( 'There was a problem logging into the Bullhorn API.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) );
			} else {
				return __( 'There was a problem logging into the Bullhorn API.', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
			}
		}

		wp_defer_term_counting( true );

		$response = self::get_categories_from_bullhorn();
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}

		$jobs     = self::get_jobs_from_bullhorn();
		if ( is_wp_error( $jobs ) ) {
			return $jobs;
		}

		$existing = self::get_existing();
		// emove job on in current job list
		self::remove_old( $jobs );

		if ( count( $jobs ) ) {
			foreach ( $jobs as $job ) {
				if ( 'Archive' !== $job->status ) {
					if ( isset( $existing[ $job->id ] )  ) {
						self::sync_job( $job, $existing[ $job->id ] );
					} else {
						self::sync_job( $job );
					}
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
	protected static function login() {
		if ( false === self::refresh_token() ) {
			return false;
		};

		$url = add_query_arg(
			array(
				'version'      => '*',
				'access_token' => self::$api_access['access_token'],
			), 'https://rest.bullhornstaffing.com/rest-services/login'
		);

		$response = self::request( $url );
		$body     = json_decode( $response['body'] );

		if ( isset( $body->BhRestToken ) ) {
			self::$session = $body->BhRestToken;
			self::$url     = $body->restUrl;

			return true;
		}
		// TODO: make to user freindly
		if ( isset( $body->errorMessage ) ) {
			throw new Exception( $body->errorMessage );
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
	protected static function refresh_token( $force = false ) {
		// TODO: stop re-calling every time
		//      $eight_mins_ago = strtotime( '8 minutes ago' );
		//      if ( false === $force && $eight_mins_ago <= self::api_access['last_refreshed'] ) {
		//         return true;
		//      }
		// TODO: return false if client not set and add handlers for the call
		if (
			null === self::$api_access['refresh_token'] ||
			null === self::$settings['client_id'] ||
			null === self::$settings['client_secret']
		) {
			add_action( 'admin_notices', array( __CLASS__, 'no_token_admin_notice' ) );

			return false;
		}

		$url = add_query_arg(
			array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => self::$api_access['refresh_token'],
				'client_id'     => self::$settings['client_id'],
				'client_secret' => self::$settings['client_secret'],
			), 'https://auth.bullhornstaffing.com/oauth/token'
		);

		$response = wp_remote_post( $url );

		if ( ! is_array( $response ) ) {
			return false;
		}
		$body = json_decode( $response['body'], true );

		if ( isset( $body['access_token'] ) ) {
			$body['last_refreshed'] = time();
			update_option( 'bullhorn_api_access', $body );
			self::$api_access = $body;

			return true;
		} elseif ( isset( $body['error_description'] ) ) {
			wp_die( $body['error_description'] );
		}

		return false;
	}

	/**
	 * This retreives all available categories from Bullhorn.
	 *
	 * @return array
	 */
	private static function get_categories_from_bullhorn() {
		//TODO: cache this
		$url    = self::$url . 'options/Category';
		$params = array(
			'BhRestToken' => self::$session,
		);

		$response = self::request( $url . '?' . http_build_query( $params ), false );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body     = json_decode( $response['body'] );
		if ( isset( $body->data ) ) {
			foreach ( $body->data as $category ) {
				wp_insert_term( $category->label, 'bullhorn_category' );
			}
		}

		return array();
	}

	public static function no_token_admin_notice() {
		$class   = 'error';
		$message = 'Error in saving';
		echo "<div class=\"$class\"> <p>$message</p></div>";
	}

	/**
	 * Gets the description field as chosen by the user in settings.
	 *
	 * @return string
	 */
	private static function get_description_field() {
		if ( isset( self::$settings['description_field'] ) ) {
			$description = self::$settings['description_field'];
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
	private static function get_jobs_from_bullhorn() {
		// Use the specified description field if set, otherwise the default
		$description = self::get_description_field();

		$where = 'isPublic=1 AND isOpen=true AND isDeleted=false';

		$settings = (array) get_option( 'bullhorn_settings' );

		if ( isset( $settings['is_public'] ) ) {
			$is_public = $settings['is_public'];
			if ( 'false' === $is_public ) {
				$where = 'isOpen=true AND isDeleted=false';
			}
		}

		$start = 0;
		$page  = 100;
		$jobs  = array();
		while ( true ) {
			$url    = self::$url . 'query/JobOrder';
			$params = array(
				'BhRestToken' => self::$session,
				'fields'      => 'id,title,' . $description . ',dateAdded,categories,address,benefits,salary,educationDegree,employmentType,yearsRequired,clientCorporation,degreeList,skillList,bonusPackage,status',
				//'fields'        => '*',
				'where'       => $where,
				'count'       => $page,
				'start'       => $start,
			);

			if ( isset( self::$settings['client_corporation'] ) and ! empty( self::$settings['client_corporation'] ) ) {
				$ids = explode( ',', self::$settings['client_corporation'] );
				$ids = array_map( 'trim', $ids );

				$params['where'] .= ' AND (clientCorporation.id=' . implode( ' OR clientCorporation.id=', $ids ) . ')';
			}

			$response = self::request( $url . '?' . http_build_query( $params ), false );

			$body     = json_decode( $response['body'] );

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
	 * @param      $job
	 * @param null $id
	 *
	 * @return bool
	 * @throws Exception
	 */
	private static function sync_job( $job, $id = null ) {
		global $post;
		$description = self::get_description_field();

		$post_args = array(
			'post_title'   => $job->title,
			'post_content' => $job->{$description},
			'post_type'    => 'bullhornjoblisting',
			'post_status'  => 'publish',
			'post_date'    => date( 'Y-m-d H:i:s', $job->dateAdded / 1000 ),
		);

		if ( null !== $id ) {
			$post_args['ID'] = $id;
			wp_update_post( $post_args );
		} else {
			$id = wp_insert_post( $post_args );
		}

		$address = (array) $job->address;
		unset( $address['countryID'] );

		$categories = array();
		// TODO: cache as trans to save API calls
		foreach ( $job->categories->data as $category ) {
			$category_id = $category->id;

			// Check to see if this category name has been cached already
			if ( isset( self::$categories[ $category_id ] ) ) {
				$categories[] = self::$categories[ $category_id ];
			} else {
				$url      = self::$url . 'entity/Category/' . $category_id;
				$params   = array( 'BhRestToken' => self::$session, 'fields' => 'id,name' );
				$response = self::request( $url . '?' . http_build_query( $params ) );

				$category = json_decode( $response['body'] );
				if ( isset( $category->data->name ) ) {
					$categories[] = $category->data->name;

					// Cache this category in an array
					self::$categories[ $category_id ] = $category->data->name;
				}
			}
		}

		wp_set_object_terms( $id, $categories, 'bullhorn_category' );
		wp_set_object_terms( $id, array( $job->address->state ), 'bullhorn_state' );

		$create_json_ld = self::create_json_ld( $job, $categories );

		foreach ( $create_json_ld as $key => $val ) {
			update_post_meta( $id, $key, $val );
		}

		update_post_meta( $id, 'city', $create_json_ld['jobLocation']['address']['addressLocality'] );
		update_post_meta( $id, 'state', $create_json_ld['jobLocation']['address']['addressRegion'] );
		update_post_meta( $id, 'Country', $create_json_ld['jobLocation']['address']['addressCountry'] );
		update_post_meta( $id, 'zip', $create_json_ld['jobLocation']['address']['postalCode'] );

		$custom_fields = array(
			'bullhorn_job_id'      => $job->id,
			'bullhorn_job_address' => implode( ' ', $address ),
			'bullhorn_json_ld'     => $create_json_ld,
			'employmentType'       => $job->employmentType,
			'baseSalary'           => $job->salary,
		);

		foreach ( $custom_fields as $key => $val ) {
			update_post_meta( $id, $key, $val );
		}

		return true;
	}

	private static function create_json_ld( $job, $categories ) {
		$description = self::get_description_field();
		$address     = (array) $job->address;

		$ld                                    = array();
		$ld['@context']                        = 'http://schema.org';
		$ld['@type']                           = 'JobPosting';
		$ld['title']                           = $job->title;
		$ld['description']                     = $job->{$description};
		$ld['datePosted']                      = self::format_date_to_8601( $job->dateAdded );
		$ld['occupationalCategory']            = implode( ',', $categories );
		$ld['jobLocation']['@type']            = 'place';
		$ld['jobLocation']['address']['@type'] = 'PostalAddress';

		if ( ! empty( $address['city'] ) ) {
			$ld['jobLocation']['address']['addressLocality'] = $address['city'];
		}
		if ( ! empty( $address['state'] ) ) {
			$ld['jobLocation']['address']['addressRegion'] = $address['state'];
		}
		if ( ! empty( $address['zip'] ) ) {
			$ld['jobLocation']['address']['postalCode'] = $address['zip'];
		}
		if ( ! empty( $address['countryID'] ) ) {
			$ld['jobLocation']['address']['addressCountry'] = $address['countryID'];
		}
		if ( ! empty( $address['countryID'] ) ) {
			$addressCountry = self::get_country_name( $address['countryID'] );
			if ( false !== $addressCountry ) {
				$ld['jobLocation']['address']['addressCountry'] = $addressCountry;
			}
		}

		if ( isset( $job->clientCorporation->name ) ) {
			$ld['hiringOrganization']['name'] = $job->clientCorporation->name;
		}
		if ( isset( $job->benefits ) && null !== $job->benefits ) {
			$ld['jobBenefits'] = $job->benefits;
		}
		if ( isset( $job->salary ) && 0 < $job->salary ) {
			$ld['baseSalary'] = $job->clientCorporation->name;
		}
		if ( isset( $job->yearsRequired ) ) {
			$ld['experienceRequirements'] = $job->yearsRequired;
		}
		if ( isset( $job->degreeList ) ) {
			$ld['educationRequirements'] = $job->degreeList;
		}
		if ( isset( $job->skillList ) ) {
			$ld['skills'] = $job->skillList;
		}
		if ( isset( $job->bonusPackage ) ) {
			$ld['incentiveCompensation'] = $job->bonusPackage;
		}

		return $ld;
	}

	/**
	 * format the date
	 *
	 * @param $microtime
	 *
	 * @return string
	 * @internal param $date
	 *
	 */
	private static function format_date_to_8601( $microtime ) {
		$microtime = $microtime / 1000;
		// make sure the have a .00 in the date format
		if ( ! strpos( $microtime, '.' ) ) {
			$microtime = $microtime . '.00';
		}

		$utc = DateTime::createFromFormat( 'U.u', $microtime );

		return $utc->format( 'c' );
	}

	private static function get_country_name( $country_id ) {

		$country_list_id = 'bullhorn_country_list';

		$country_list = get_transient( $country_list_id );
		if ( false === $country_list || ! isset( $country_list[ $country_id ] ) ) {
			$url = add_query_arg(
				array(
					'BhRestToken' => self::$session,
					//   'fields'      => 'name',
					'count'       => '300',
				), self::$url . 'options/Country'// . absint( $country_id )
			);

			$response = wp_remote_get( $url, array( 'method' => 'GET' ) );

			if ( 200 === $response['response']['code'] ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				if ( isset( $data['data'] ) ) {
					return false;
				}
				$data = $data['data'];

				$country_list = array();
				foreach ( $data as $key ) {
					$country_list[ $key['value'] ] = $key['label'];
				}

				set_transient( $country_list_id, $country_list, HOUR_IN_SECONDS * 1 );
			}
		}
		if ( isset( $country_list[ $country_id ] ) ) {
			return $country_list[ $country_id ];
		}

		return _x( '- None Specified -', ' no county set', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
	}


	/**
	 * Before we start adding in new jobs, we need to delete jobs that are no
	 * longer in Bullhorn.
	 *
	 * @param  array $jobs
	 *
	 * @return boolean
	 */
	private static function remove_old( $jobs ) {
		$ids = array();
		foreach ( $jobs as $job ) {
			if ( 'Archive' !== $job->status ) {
				$ids[] = $job->id;
			}
		}

		$jobs = new WP_Query( array(
			'post_type'      => 'bullhornjoblisting',
			'post_status'    => 'any',
			'posts_per_page' => 500,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'bullhorn_job_id',
					'compare' => 'NOT IN',
					'value'   => $ids,
				),
				array(
					'key'     => 'bullhorn_job_id',
					'compare' => 'EXISTS', // works!
					'value'   => '', // This is ignored, but is necessary...
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
	private static function get_existing() {
		global $wpdb;
		//TODO: change this the WP_QUERY meta select
		$posts = $wpdb->get_results( "SELECT $wpdb->posts.id, $wpdb->postmeta.meta_value FROM $wpdb->postmeta JOIN $wpdb->posts ON $wpdb->posts.id = $wpdb->postmeta.post_id WHERE meta_key = 'bullhorn_job_id'", ARRAY_A );

		$existing = array();
		foreach ( $posts as $post ) {
			$existing[ $post['meta_value'] ] = $post['id'];
		}

		return $existing;
	}

	/**
	 * Wrapper around wp_remote_get() so any errors are reported to the screen.
	 *
	 * @param $url
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function request( $url, $throw = true ) {
		$response = wp_remote_get( $url, array( 'timeout' => 180 ) );
		if ( is_wp_error( $response ) ) {
			if ( $throw ) {
				throw new Exception( $response->get_error_message() );
			} else {
				return $response;
			}
		}

		return $response;
	}
}
