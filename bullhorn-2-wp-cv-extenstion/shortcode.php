<?php
/**
 * Created by IntelliJ IDEA.
 * User: Paul
 * Date: 2015-12-04
 * Time: 1:29 PM
 */
namespace bullhorn_2_wp;


class shortcodes{

	public function __construct() {
		// Call parent __construct()
		//	parent::__construct();
		add_shortcode( 'bullhorn_cv_form', array( __CLASS__, 'render_cv_form' ) );
	}

	public function render_cv_form(){
		ob_start();
		?>
		<form id="bullhorn-resume" action="/api/bullhorn/resume" enctype="multipart/form-data" method="post">
			<label for="name">Name<span class="gfield_required"> *</span></label>
			<input id="name" name="name" type="text" />
			<label for="email">Email<span class="gfield_required"> *</span></label>
			<input id="email" name="email" type="text" />
			<label for="name">Phone</label>
			<input id="phone" name="phone" type="text" />
			<label for="fileToUpload">Your Resume<span class="gfield_required"> *</span></label>
			<input id="fileToUpload" name="resume" type="file" />
			<br /><br />
			<?php
			if ( isset( $_GET['position'] ) ) {
				printf( '<input id="position" name="position" type="hidden" value="%s" />',
				esc_attr( $_GET['position'] ) );
			} elseif ( 'bullhornjoblisting' === get_post_type() ) {
				printf( '<input id="position" name="position" type="hidden" value="%s" />',
				esc_attr( get_post_meta( get_the_ID(), 'bullhorn_job_id', true ) ) );
			}

			wp_nonce_field( 'bullhorn_cv_form' , 'bullhorn_cv_form' );
		?>
		<input name="submit" type="submit" value="Upload Resume" />
		</form>
		<script type="application/javascript">

		jQuery(document).ready(function(e) {
			var error_color = '#FFDFE0';
			var defaut_file_color =  jQuery('#fileToUpload').css( 'background-color'); //'#fff';
			var defaut_color =   jQuery('#email').css( 'background-color'); //'#d0eafa';
			jQuery( '#bullhorn-resume').on('submit', function(){

				$no_error = true;
				$email = jQuery('#email');
				if ( ( 3 > $email.val().length ) || ! isValidEmailAddress( $email.val() ) ){
					$email.css( 'background-color', error_color );
					$no_error = false;
				} else {
					$email.css( 'background-color', defaut_color );
				}
				$name = jQuery('#name');
				if ( 3 > $name.val().length ){
					$name.css( 'background-color', error_color );
					$no_error = false;
				} else {
					$name.css( 'background-color', defaut_color );
				}
				$fileToUpload = jQuery('#fileToUpload');
				if ( 3 > $fileToUpload.val().length ){
					$fileToUpload.css( 'background-color', error_color );
					$no_error = false;
				} else {
					$fileToUpload.css( 'background-color', defaut_file_color );
				}


			//	e.preventDefault();
				return $no_error;
			});

			function isValidEmailAddress(emailAddress) {
				var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
				return pattern.test(emailAddress);
			};
		});
		</script>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}

new shortcodes();
