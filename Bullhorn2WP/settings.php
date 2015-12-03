<?php

class Bullhorn_Settings {

	public function __construct() {
		if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'bullhorn' ) {
			add_action( 'admin_init', 'bullhorn_sync' );
		}

		// If being redirected back to the site with a code, we need to auth
		// with the API and save the access_token.
		if ( isset( $_GET['code'] ) ) {
			$this->authorize();
		}

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Sets up the plugin by adding the settings link on the GF Settings page
	 */
	public function init() {
		if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'bullhorn' ) {
			wp_redirect( admin_url( 'options-general.php?page=bullhorn' ) );
		}

		register_setting( 'bullhorn_settings', 'bullhorn_settings', array( $this, 'validate' ) );

		add_settings_section( 'bullhorn_api', 'API Settings', array( $this, 'api_settings' ), 'bullhornwp' );

		add_settings_field( 'client_id', 'Client ID', array( $this, 'client_id' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'client_secret', 'Client Secret', array( $this, 'client_secret' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'client_corporation', 'Client Corporation', array( $this, 'client_corporation' ), 'bullhornwp', 'bullhorn_api' );

		add_settings_field( 'listings_page', 'Job Listings Page', array( $this, 'listings_page' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'form_page', 'Form Page', array( $this, 'form_page' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'listings_sort', 'Listings Sort', array( $this, 'listings_sort' ), 'bullhornwp', 'bullhorn_api' );
		add_settings_field( 'description_field', 'Description Field', array( $this, 'description_field' ), 'bullhornwp', 'bullhorn_api' );
	}

	/**
	 * Adds a link to the Bullhorn to the Settings menu
	 */
	public function menu() {
		add_options_page( 'Bullhorn', 'Bullhorn', 'manage_options', 'bullhorn', array( $this, 'settings_page' ) );
	}

	/**
	 * @return array|bool
	 */
	public function authorize() {
		$settings = (array) get_option( 'bullhorn_settings' );

		if ( isset( $settings['client_id'] ) and ! empty( $settings['client_id'] ) and isset( $settings['client_secret'] ) and ! empty( $settings['client_secret'] ) ) {
			$url = 'https://auth.bullhornstaffing.com/oauth/token?grant_type=authorization_code&code=' . $_GET['code'] . '&client_id=' . $settings['client_id'] . '&client_secret=' . $settings['client_secret'];

			$response = wp_remote_post( $url );
			$body     = json_decode( $response['body'], true );

			if ( isset( $body['error_description'] ) ) {
				return $body;
			}

			if ( isset( $body['access_token'] ) ) {
				$body['last_refreshed'] = time();
				update_option( 'bullhorn_api_access', $body );

				return true;
			}
		}
	}

	/**
	 * Callback for the API settings section, which is left blank
	 */
	public function api_settings() {
		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		if ( $this->authorize() === true ) {
			echo '<div class="updated"><p>You have successfully connected to Bullhorn.</p></div>';
		} elseif ( isset( $body['error_description'] ) ) {
			echo '<div class="error"><p><strong>Bullhorn Error:</strong> ' . $body['error_description'] . '</p></div>';
		}
	}

	/**
	 * Displays the API key settings field
	 */
	public function client_id() {
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
	public function client_secret() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['client_secret'] ) ) {
			$client_secret = $settings['client_secret'];
		} else {
			$client_secret = null;
		}
		echo '<input type="text" size="40" name="bullhorn_settings[client_secret]" value="' . esc_attr( $client_secret ) . '" />';

		if ( isset( $settings['client_id'] ) ) {
			$start = '<a class="button" href="https://auth.bullhornstaffing.com/oauth/authorize?client_id=' . $settings['client_id'] . '&amp;response_type=code">';
			if ( $this->authorized() ) {
				echo $start . 'Re-connect to Bullhorn</a>';
			} elseif ( $this->connected() ) {
				echo $start . 'Connect to Bullhorn</a>';
			}
		}
	}

	/**
	 * Displays the settings field for picking the client corporation.
	 */
	public function client_corporation() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['client_corporation'] ) ) {
			$client_corporation = $settings['client_corporation'];
		} else {
			$client_corporation = null;
		}
		echo '<input type="text" size="40" name="bullhorn_settings[client_corporation]" value="' . esc_attr( $client_corporation ) . '" />';
		echo '<br><span class="description">This field is optional, but will filter the jobs retreived from Bullhorn to only those listed under a specific Client Corporation. This must be the ID of the corporation. Leave blank to sync all job listings.</span>';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public function listings_page() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['listings_page'] ) ) {
			$listings_page = $settings['listings_page'];
		} else {
			$listings_page = null;
		}

		echo '<input type="text" size="40" name="bullhorn_settings[listings_page]" value="' . esc_attr( $listings_page ) . '" />';
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public function form_page() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['form_page'] ) ) {
			$form_page = $settings['form_page'];
		} else {
			$form_page = null;
		}

		wp_dropdown_pages( array(
			'name'             => 'bullhorn_settings[form_page]',
			'selected'         => $form_page,
			'show_option_none' => 'Select a page...',
		) );
	}

	/**
	 * Displays the job listings sort settings field.
	 */
	public function listings_sort() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['listings_sort'] ) ) {
			$listings_sort = $settings['listings_sort'];
		} else {
			$listings_sort = null;
		}

		$sorts = array(
			'date'            => 'Date',
			'employment-type' => 'Employment Type',
			'name'            => 'Name',
			'state'           => 'State',
		);

		echo '<select name="bullhorn_settings[listings_sort]">';
		echo '<option value="">Select a field to sort by...</option>';
		foreach ( $sorts as $value => $name ) {
			$selected = ( $listings_sort === $value ) ? ' selected="selected"' : '';
			echo '<option value="' . $value . '"' . $selected . '>' . $name . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Displays the description field settings field.
	 */
	public function description_field() {
		$settings = (array) get_option( 'bullhorn_settings' );
		if ( isset( $settings['description_field'] ) ) {
			$description_field = $settings['description_field'];
		} else {
			$description_field = 'description';
		}

		$fields = array(
			'description'       => 'Description (default)',
			'publicDescription' => 'Public Description',
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
	public function validate( $input ) {
		$input['client_id']         = esc_html( $input['client_id'] );
		$input['client_secret']     = esc_html( $input['client_secret'] );
		$input['listings_page']     = esc_html( $input['listings_page'] );
		$input['form_page']         = intval( $input['form_page'] );
		$input['listings_sort']     = esc_html( $input['listings_sort'] );
		$input['description_field'] = esc_html( $input['description_field'] );

		// Since the listings page has probably been updated, we need to flush
		// the rewrite rules for the site.
		flush_rewrite_rules();

		return $input;
	}

	/**
	 * Output the main settings page with the title and form
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Bullhorn Developer</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'bullhorn_settings' ); ?>
				<?php do_settings_sections( 'bullhornwp' ); ?>
				<p class="submit">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
					<a href="<?php echo admin_url( 'options-general.php?page=bullhorn&sync=bullhorn' ); ?>" class="button">
						Sync Now
					</a>
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
	public function authorized() {
		$settings = get_option( 'bullhorn_api_access' );

		return ( $settings and isset( $settings['access_token'] ) );
	}

	/**
	 * Determines if we are connected to Bullhorn (which means that the client
	 * ID and secret have been entered).
	 *
	 * @return boolean
	 */
	public function connected() {
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
