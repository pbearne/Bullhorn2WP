<?php

class Bullhorn_Settings {

	static $authorize;

	/**
	 * Bullhorn_Settings constructor.
	 */
	public function __construct() {
		if ( isset( $_GET['sync'] ) && 'bullhorn' === $_GET['sync'] ) {
			add_action( 'admin_init', 'bullhorn_sync_now' );
		}

		add_action( 'admin_init', array( __CLASS__, 'init' ) );
		add_action( 'current_screen', array( __CLASS__, 'tasks' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}


	/**
	 *
	 */
	public static function tasks() {
		// If being redirected back to the site with a code, we need to auth
		// with the API and save the access_token.
		$currentScreen = get_current_screen();
		if ( 'settings_page_bullhorn' !== $currentScreen->id ) {
			return;
		}

		if ( isset( $_GET['code'] ) ) {
			self::authorize();
		}
		//if ( isset( $_GET['sync'] ) && 'bullhorn' === $_GET['sync'] ) {
		//	add_action( 'admin_init', 'bullhorn_sync' );
		//}
	}

	/**
	 * Sets up the plugin by adding the settings link on the GF Settings page
	 */
	public static function init() {

		if ( isset( $_GET['sync'] ) && 'bullhorn' === $_GET['sync'] ) {
			wp_redirect( admin_url( 'options-general.php?page=bullhorn' ) );
		}

		register_setting( 'bullhorn_settings', 'bullhorn_settings', array( __CLASS__, 'validate' ) );

		add_settings_section( 'bullhorn_api', __( 'API Settings', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'api_settings' ), 'bullhornwp' );

		add_settings_field( 'client_id', __( 'Client ID', 'bullhorn' ), array( __CLASS__, 'client_id' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'client_secret', __( 'Client Secret', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'client_secret' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'client_corporation', __( 'Client Corporation', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'client_corporation' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'listings_page', __( 'Job Listings page slug', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'listings_page' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'form_page', __( 'Form Page or CV upload', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'form_page' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'default_shortcode', __( 'Default form inputs', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'default_shortcode' ), 'bullhornwp', 'bullhorn_api' );


		add_settings_field( 'thanks_page', __( 'CV Thanks Page', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'thanks_page' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'listings_sort', __( 'Listings Sort', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'listings_sort' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'description_field', __( 'Description Field', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'description_field' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'run_cron', __( 'Auto-sync', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'run_cron' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'cron_error_email', __( 'Auto-sync Error Email', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'cron_error_email' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'is_public', __( 'Filter isPublic', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), array( __CLASS__, 'is_public' ), 'bullhornwp', 'bullhorn_api' );

	}

	/**
	 * Adds a link to the Bullhorn to the Settings menu
	 */
	public static function menu() {
		add_options_page( 'Bullhorn', __( 'Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), 'manage_options', 'bullhorn', array( __CLASS__, 'settings_page' ) );
	}

	/**
	 * @return array|bool
	 */
	public static function authorize() {

		// check for
		if ( null !== self::$authorize ) {
			return self::$authorize;
		}

		$settings = (array) get_option( 'bullhorn_settings' );

		if (
			isset( $settings['client_id'] ) and ! empty( $settings['client_id'] ) and
			                                    isset( $settings['client_secret'] ) and ! empty( $settings['client_secret'] ) and
			                                                                            isset( $_GET['code'] )
		) {
			$url = add_query_arg(
				array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
					'redirect_uri'  => admin_url( 'options-general.php?page=bullhorn' ),
				), 'https://auth.bullhornstaffing.com/oauth/token'
			);

			$response = wp_remote_post( $url );
			$body     = json_decode( $response['body'], true );

			if ( isset( $body['error_description'] ) ) {
				return $body;
			}
			if ( isset( $body['access_token'] ) ) {
				$body['last_refreshed'] = time();
				update_option( 'bullhorn_api_access', $body );
				self::$authorize = true;

				return true;
			}
		}

		return false;
	}

	/**
	 * Callback for the API settings section, which is left blank
	 */
	public static function api_settings() {
		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		if ( true === self::authorize() ) {
			echo '<div class="updated"><p>' . __( 'You have successfully connected to Bullhorn.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</p></div>';
		} elseif ( isset( $body['error_description'] ) ) {
			echo '<div class="error"><p><strong>' . __( 'Bullhorn Error:', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</strong> ' . esc_html( $body['error_description'] ) . '</p></div>';
		}
	}

	/**
	 * Displays the API key settings field
	 */
	public static function client_id() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['client_id'] ) ) {
			$client_id = $settings['client_id'];
		} else {
			$client_id = null;
		}
		echo '<input type="text" size="40" name="bullhorn_settings[client_id]" value="' . esc_attr( $client_id ) . '" />';

	}

	/**
	 * Displays the API key settings field
	 */
	public static function client_secret() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['client_secret'] ) ) {
			$client_secret = $settings['client_secret'];
		} else {
			$client_secret = null;
		}
		echo '<input type="text" size="40" name="bullhorn_settings[client_secret]" value="' . esc_attr( $client_secret ) . '" />';

		if ( isset( $settings['client_id'] ) ) {
			$state_string = __( 'not ready', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
			if ( self::authorized() ) {
				$state_string = __( 'Re-connect to Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
			} elseif ( self::connected() ) {
				$state_string = __( 'Connect to Bullhorn', 'bh-staffing-job-listing-and-cv-upload-for-wp' );
			}
			$url = add_query_arg(
				array(
					'client_id'     => $settings['client_id'],
					'response_type' => 'code',
					'redirect_uri'  => admin_url( 'options-general.php?page=bullhorn' ),
				),
				'auth.bullhornstaffing.com/oauth/authorize'
			);

			printf( '<a class="button" href="https://%s">%s</a>', $url, esc_html( $state_string ) );
		} else {
			printf( ' <strong> %s</strong>', __( 'Enter Your Client Id and Secret', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) );
		}
		echo '<br><span class="description">' .
		     __(
			     'Note: You will have to ask Bullhorn support to add your domain/s to the API white list for this to work. (see the plugin install notes for more info)',
			     'bh-staffing-job-listing-and-cv-upload-for-wp'
		     ) . '</span>';
	}

	/**
	 * Displays the settings field for picking the client corporation.
	 */
	public static function client_corporation() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['client_corporation'] ) ) {
			$client_corporation = $settings['client_corporation'];
		} else {
			$client_corporation = null;
		}
		echo '<input type="text" size="40" name="bullhorn_settings[client_corporation]" value="' . esc_attr( $client_corporation ) . '" />';
		echo '<br><span class="description">' . __( 'This field is optional, but will filter the jobs retreived from Bullhorn to only those listed under a specific
														Client Corporation. This must be the ID of the corporation. Leave blank to sync all job listings.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</span>';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function listings_page() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['listings_page'] ) ) {
			$listings_page = $settings['listings_page'];
		} else {
			$listings_page = null;
		}

		echo '<input type="text" size="40" name="bullhorn_settings[listings_page]" value="' . esc_attr( $listings_page ) . '" placeholder="bullhornjoblisting" />';
		echo '<br><span class="description">' . __( 'This field is optional, but changing it will adjust the URL of the job listing pages from "bullhornjoblisting" to the set value.
														You must run the sync after changing this as it changes the Custom Post Slug.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</span>';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function thanks_page() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['thanks_page'] ) ) {
			$thanks_page = $settings['thanks_page'];
		} else {
			$thanks_page = null;
		}

		wp_dropdown_pages( array(
			'name'             => 'bullhorn_settings[thanks_page]',
			'selected'         => $thanks_page,
			'show_option_none' => __( 'Select a page...', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		) );
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function form_page() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['form_page'] ) ) {
			$form_page = $settings['form_page'];
		} else {
			$form_page = null;
		}

		wp_dropdown_pages( array(
			'name'             => 'bullhorn_settings[form_page]',
			'selected'         => $form_page,
			'show_option_none' => __( 'Use CV upload form', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		) );
	}


	/**
	 * Displays the job listings page settings field.
	 */
	public static function default_shortcode() {
		$settings          = (array) get_option( 'bullhorn_settings' );
		$default_shortcode = array( 'name', 'email', 'phone' );

		if ( isset( $settings['default_shortcode'] ) ) {
			$default_shortcode = $settings['default_shortcode'];
		}

		$sorts = array(
			'name'    => __( 'Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'email'   => __( 'Email', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'phone'   => __( 'Phone', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'address' => __( 'Address', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		foreach ( $sorts as $value => $name ) {
			$checked = in_array( $value, $default_shortcode );
			printf( '<label for="%1$s">%2$s&nbsp;<input name="bullhorn_settings[default_shortcode][]" id="%1$s" value="%1$s" type="checkbox" %3$s>&nbsp;</label>',
				esc_attr( $value ),
				esc_attr( $name ),
				checked( $checked, true, false )
			);
		}

	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function is_public() {
		$settings  = (array) get_option( 'bullhorn_settings' );
		$is_public = 'true';

		if ( isset( $settings['is_public'] ) ) {
			$is_public = $settings['is_public'];
		}

		$sorts = array(
			'true'  => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'false' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		foreach ( $sorts as $value => $name ) {
			printf( '<label for="%1$s">%2$s&nbsp;<input name="bullhorn_settings[is_public]" id="%1$s" value="%1$s" type="radio" %3$s>&nbsp;</label>',
				esc_attr( $value ),
				esc_attr( $name ),
				checked( $is_public, $value, false )
			);
		}
		echo '<br><span class="description">' . __( 'By Default the isPublic field is hidden in Vacancy by default if no job as syniced try set to this to false.
						To show the field the steps are : Fields Mapping Vacancy isPublic, uncheck "hidden"  .', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</span>';


	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function run_cron() {
		$settings = (array) get_option( 'bullhorn_settings' );
		$run_cron = 'false';

		if ( isset( $settings['run_cron'] ) ) {
			$run_cron = $settings['run_cron'];
		}

		$sorts = array(
			'true'  => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'false' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		foreach ( $sorts as $value => $name ) {
			printf( '<label for="%1$s">%2$s&nbsp;<input name="bullhorn_settings[run_cron]" id="%1$s" value="%1$s" type="radio" %3$s>&nbsp;</label>',
				esc_attr( $value ),
				esc_attr( $name ),
				checked( $run_cron, $value, false )
			);
		}
		echo '<br><span class="description">' . __( 'Fetch Jobs from Bullhorn every hour or using the manual sync button below ( shows once you have connected to Bullhorn ).', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</span>';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public static function cron_error_email() {
		$settings = (array) get_option( 'bullhorn_settings' );
		$cron_error_email = 'true';

		if ( isset( $settings['cron_error_email'] ) ) {
			$cron_error_email = $settings['cron_error_email'];
		}

		$sorts = array(
			'true'  => __( 'On', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'false' => __( 'Off', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		foreach ( $sorts as $value => $name ) {
			printf( '<label for="%1$s">%2$s&nbsp;<input name="bullhorn_settings[cron_error_email]" id="%1$s" value="%1$s" type="radio" %3$s>&nbsp;</label>',
				esc_attr( $value ),
				esc_attr( $name ),
				checked( $cron_error_email, $value, false )
			);
		}
		echo '<br><span class="description">' . __( 'Send an Email to the WP admin email address if the synic errors.', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) . '</span>';
	}
	/**
	 * Displays the job listings sort settings field.
	 */
	public static function listings_sort() {
		$settings      = (array) get_option( 'bullhorn_settings' );
		$listings_sort = null;
		if ( isset( $settings['listings_sort'] ) ) {
			$listings_sort = $settings['listings_sort'];
		}

		$sorts = array(
			'date'            => __( 'Date', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'employment-type' => __( 'Employment Type', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'name'            => __( 'Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
			'state'           => __( 'State', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		echo '<select name="bullhorn_settings[listings_sort]">';
		echo '<option value="">Select a field to sort by...</option>';
		foreach ( $sorts as $value => $name ) {
			$selected = selected( $listings_sort, $value, false );
			echo '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Displays the description field settings field.
	 */
	public static function description_field() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['description_field'] ) ) {
			$description_field = $settings['description_field'];
		} else {
			$description_field = 'description';
		}

		$fields = array(
			'description'       => 'Description (default)',
			'publicDescription' => __( 'Public Description', 'bh-staffing-job-listing-and-cv-upload-for-wp' ),
		);

		echo '<select name="bullhorn_settings[description_field]">';
		echo '<option value="">Select the description field to use...</option>';
		foreach ( $fields as $value => $name ) {
			$selected = ( $description_field === $value ) ? ' selected="selected"' : '';
			echo '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Validates the user input
	 *
	 * @param array $input POST data
	 *
	 * @return array        Sanitized POST data
	 */
	public static function validate( $input ) {
		$input['client_id']         = esc_html( $input['client_id'] );
		$input['client_secret']     = esc_html( $input['client_secret'] );
		$input['listings_page']     = esc_html( $input['listings_page'] );
		$input['form_page']         = intval( $input['form_page'] );
		$input['listings_sort']     = esc_html( $input['listings_sort'] );
		$input['description_field'] = esc_html( $input['description_field'] );
		$input['thanks_page']       = intval( $input['thanks_page'] );
		$input['run_cron']          = esc_attr( $input['run_cron'] );
		$input['cron_error_email']  = esc_attr( $input['cron_error_email'] );
		$input['is_public']         = esc_attr( $input['is_public'] );

		// Since the listings page has probably been updated, we need to flush
		// the rewrite rules for the site.
		flush_rewrite_rules();

		return $input;
	}

	/**
	 * Output the main settings page with the title and form
	 */
	public static function settings_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Bullhorn Developer</h2>
			<form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
				<?php settings_fields( 'bullhorn_settings' ); ?>
				<?php do_settings_sections( 'bullhornwp' ); ?>
				<p class="submit">
					<?php submit_button( __( 'Save Changes', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), 'primary', 'submit', false ); ?>
					<?php
					if ( self::authorized() ) {
						printf( '<a href="%s" class="button">%s</a>',
							admin_url( 'options-general.php?page=bullhorn&sync=bullhorn' ),
							__( 'Sync Now', 'bh-staffing-job-listing-and-cv-upload-for-wp' )
						);
					}
					?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Determines if we are authorized to Bullhorn (have an API access token).
	 *
	 * @return boolean
	 */
	public static function authorized() {
		$settings = get_option( 'bullhorn_api_access' );

		return ( $settings and isset( $settings['access_token'] ) );
	}

	/**
	 * Determines if we are connected to Bullhorn (which means that the client
	 * ID and secret have been entered).
	 *
	 * @return boolean
	 */
	public static function connected() {
		$settings = get_option( 'bullhorn_settings' );

		return (
			isset( $settings['client_id'] ) and
			! empty( $settings['client_id'] ) and
			isset( $settings['client_secret'] ) and
			! empty( $settings['client_secret'] )
		);
	}
}

$bullhorn_settings = new Bullhorn_Settings;
