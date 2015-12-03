<?php

class Bullhorn_Extension_Settings {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Sets up the plugin by adding the settings link on the GF Settings page
	 */
	public function init() {
		register_setting( 'bullhorn_extension_settings', 'bullhorn_extension_settings' );
		add_settings_section( 'bullhorn_api', 'Resume Settings', array( $this, 'api_settings' ), 'bullhornwpext' );
		add_settings_field( 'thanks_page', 'Thanks Page', array( $this, 'thanks_page' ), 'bullhornwpext', 'bullhorn_api' );
	}

	/**
	 * Adds a link to the Bullhorn to the Settings menu
	 */
	public function menu() {
		add_options_page( 'Bullhorn Ext', 'Bullhorn Ext', 'manage_options', 'bullhornext', array( $this, 'settings_page' ) );
	}

	/**
	 * Callback for the API settings section, which is left blank
	 */
	public function api_settings() {
		return;
	}

	/**
	 * Displays the job listings page settings field.
	 */
	public function thanks_page() {
		$settings = (array) get_option( 'bullhorn_extension_settings' );
		if ( isset( $settings['thanks_page'] ) ) {
			$thanks_page = $settings['thanks_page'];
		} else {
			$thanks_page = null;
		}

		wp_dropdown_pages( array(
			'name'             => 'bullhorn_extension_settings[thanks_page]',
			'selected'         => $thanks_page,
			'show_option_none' => 'Select a page...',
		) );
	}

	/**
	 * Validates the user input
	 *
	 * @param array $input POST data
	 *
	 * @return array        Sanitized POST data
	 */
	public function validate( $input ) {
		$input['thanks_page'] = intval( $input['thanks_page'] );

		return $input;
	}

	/**
	 * Output the main settings page with the title and form
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Bullhorn Resume Extension</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'bullhorn_extension_settings' ); ?>
				<?php do_settings_sections( 'bullhornwpext' ); ?>
				<p class="submit">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
				</p>
			</form>
		</div>
		<?php
	}
}

$bullhorn_extension_settings = new Bullhorn_Extension_Settings;
