<?php

/**
 * Created by IntelliJ IDEA.
 * User: Paul
 * Date: 2015-12-04
 * Time: 1:29 PM
 */

namespace bullhorn_2_wp;


use WP_Query;

class Shortcodes {

	public static $show_content_count;

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {

		add_shortcode( 'bullhorn_cv_form', array( __CLASS__, 'render_cv_form' ) );
		add_shortcode( 'bullhorn_cv_form_with_jobs', array( __CLASS__, 'render_cv_form_with_jobs' ) );
		add_shortcode( 'bullhorn', array( __CLASS__, 'bullhorn_shortcode' ) );
		add_shortcode( 'bullhorn_categories', array( __CLASS__, 'bullhorn_categories' ) );
		add_shortcode( 'bullhorn_states', array( __CLASS__, 'bullhorn_states' ) );
		add_shortcode( 'bullhorn_search', array( __CLASS__, 'bullhorn_search' ) );

		add_shortcode( 'b2wp_resume_form', array( __CLASS__, 'render_cv_only' ) );
		add_shortcode( 'b2wp_application', array( __CLASS__, 'render_cv_appication' ) );

		add_shortcode( 'b2wp_application_with_jobs', array( __CLASS__, 'render_cv_appication_with_jobs' ) );
		add_shortcode( 'b2wp_application_with_job_text', array( __CLASS__, 'render_cv_form_with_job_text' ) );
		add_shortcode( 'b2wp_shortapp', array( __CLASS__, 'render_cv_form' ) );

		add_filter( 'posts_where', array( __CLASS__, 'bullhorn_title_like_posts_where' ), 10, 2 );

		/**
		 * Added so shortcodes are processed in text widgets.
		 */
		add_filter( 'widget_text', 'do_shortcode' );
	}

	public static function render_cv_only() {

		return self::render_cv( array( 'cv' ) );
	}

	public static function render_cv_form() {

		return self::render_cv( array( 'name', 'email', 'phone', 'message', 'cv' ) );
	}

	public static function render_cv_form_with_job_text() {

		return self::render_cv( array( 'name', 'email', 'phone', 'address', 'job_text', 'message', 'cv' ) );
	}

	public static function render_cv_appication() {

		return self::render_cv( array( 'name', 'email', 'phone', 'address', 'message', 'cv' ) );
	}

	public static function render_cv_appication_with_jobs() {

		return self::render_cv( array( 'name', 'email', 'phone', 'address', 'jobs_list', 'message', 'cv' ) );
	}


	/**
	 *
	 *
	 * @static
	 *
	 * @param array $element_to_show
	 *
	 * @return string
	 */
	public static function render_cv( $element_to_show = array(), $address_options = null ) {
		$settings = (array) get_option( 'bullhorn_settings' );

		if ( isset( $settings['form_page'] ) && 0 < $settings['form_page'] && get_the_ID() !== $settings['form_page'] ) {

			return sprintf( '<a href="%s" class="bullhorn-apply-here-link">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'position' => get_post_meta( get_the_ID(), 'bullhorn_job_id', true ),
							'post'     => get_the_ID(),
						),
						get_permalink( $settings['form_page'] )
					)
				),
				__( 'Apply Here.', 'bh-staffing-job-listing-and-cv-upload-for-wp' )
			);
		}

		if ( null === $element_to_show || empty( $element_to_show ) ) {
			$element_to_show = apply_filters( 'wp_bullhorn_render_cv_defaault_to_show', array(
				'name',
				'email',
				'phone',
			), $settings );
		}
		if ( null === $address_options ) {
			$address_options = apply_filters( 'wp_bullhorn_render_cv_default_address_options', array(
				'address1',
				'address2',
				'city',
				'state',
				'zip',

			), $settings );
		}
		ob_start();
		if ( isset( $_GET['bh-message'] ) ) {
			printf( '<div class="bh-message"><strong>%s</strong></div>', esc_html( apply_filters( 'bh-message', wp_unslash( $_GET['bh-message'] ) ) ) );
		}
		?>
        <style type="text/css">'
            <?php
			ob_start();
			?>
            #bullhorn-resume {
                position: relative;
            }

            #bullhorn_upload_overlay {
                display: none;
                width: 104%;
                height: 104%;
                position: absolute;
                top: -2%;
                left: -2%;

            }

            #bullhorn_upload_overlay .bullhorn_upload_overlay_background {
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                background: whitesmoke;
                opacity: .6;
                filter: alpha(opacity=70);
                border-radius: 8px;
                -moz-border-radius: 8px;
                -webkit-border-radius: 8px;
                border: 0 solid #800000;
            }

            #bullhorn_upload_overlay div.bullhorn_upload_overlay_message {
                margin-top: -30%;
                text-align: center;
                border: 1px solid;
                border-radius: 8px;
                -moz-border-radius: 8px;
                -webkit-border-radius: 8px;
                margin-left: 2%;
                margin-right: 2%;
                padding: 40px;
                background: #fff;
                opacity: 1;
                filter: alpha(opacity=100);
            }

            #bullhorn_upload_overlay .spinner {
                background: url(<?php echo esc_js( admin_url( 'images/spinner.gif' ) ); ?> ) no-repeat;
                -webkit-background-size: 20px 20px;
                background-size: 20px 20px;
                display: inline-block;
                vertical-align: middle;
                opacity: .7;
                filter: alpha(opacity=70);
                width: 20px;
                height: 20px;
                margin: -4px 6px 0;
            }

            <?php
				$css = ob_get_contents();
				ob_end_clean();
				echo wp_kses_post( apply_filters( 'wp_bullhorn_form_css', $css ) );
			?>
        </style>
        <form id="bullhorn-resume" action="<?php echo esc_url( site_url( '/api/bullhorn/resume' ) ); ?>"
              enctype="multipart/form-data" method="post" style="position: relative">

			<?php
			do_action( 'wp_bullhorn_render_cv_form_top', $element_to_show, $settings );

			$element_to_show = apply_filters( 'wp_bullhorn_shortcode_elements_to_show', $element_to_show );

			$position_added   = false;

			foreach ( $element_to_show as $index => $element ) {

				switch ( $element ) {
					case 'name':
						?>
                        <label for="name"><?php _e( 'Name', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?> <span
                                    class="gfield_required"> *</span></label>
                        <input id="name" name="name" type="text"/>

						<?php
						break;
					case 'email':
						?>
                        <label for="email"><?php _e( 'Email', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?><span
                                    class="gfield_required"> *</span></label>
                        <input id="email" name="email" type="text"/>

						<?php
						break;
					case 'phone':
						?>
                        <label for="phone"><?php _e( 'Phone', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                        <input id="phone" name="phone" type="text"/>

						<?php
						break;
					case 'address':
						foreach ( $address_options as $option ) {
							switch ( $option ) {
								case 'address1':
									?>
                                    <label for="address1"><?php _e( 'Address', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                                    <input id="address1" name="address1" type="text"/>
									<?php
									break;
								case 'address2':
									?>
                                    <label for="address2"><?php _e( 'Address Cont', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                                    <input id="address2" name="address2" type="text"/>
									<?php
									break;
								case 'city':
									?>
                                    <label for="city"><?php _e( 'City', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                                    <input id="city" name="city" type="text"/>
									<?php
									break;
								case  'state':
									?>
                                    <label for="state"><?php _e( 'State/Province', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                                    <input id="state" name="state" type="text"/>
									<?php
									break;
								case 'zip':
									?>
                                    <label for="zip"><?php _e( 'Zip/Postal Code', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                                    <input id="zip" name="zip" type="text"/>
									<?php
									break;
							}
						}
						break;
					case 'jobs_list':
						$args = array(
							'posts_per_page' => - 1,
							'orderby'        => 'title',
							'order'          => 'DESC',
							'post_type'      => \Bullhorn_2_WP::$post_type_job_listing,
							'post_status'    => 'publish',
						);
						$jobs = get_posts( $args );

						if ( ! empty( $jobs ) ) { ?>
                            <label for="position"><?php esc_html_e( 'Position applying for', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?>
                                <span class="gfield_required"> *</span></label>
                            <select id="position" name="position">
                                <option value="-1"><?php esc_html_e( 'Select a Job to apply for', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?> </option>
								<?php
								foreach ( $jobs as $job ) {
									$bullhorn_job_id = get_post_meta( $job->ID, 'bullhorn_job_id', true );
									printf( '<option value="%s">%s</option>', $bullhorn_job_id, $job->title );

								}
								?>
                            </select>

							<?php
							$position_added = true;
						}

						break;
					case 'job_text':
						?>
                        <label for="position"><?php _e( 'Position', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                        <input id="position" name="position" type="text"/>

						<?php
						$position_added = true;
						break;
					case 'message':
						?>
                        <label for="message"><?php _e( '<br/>Message<br/>', 'bh-staffing-job-listing-and-cv-upload-for-wp' ) ?></label>
                        <textarea name="message" cols="40" rows="10" id="message"></textarea>

						<?php
						break;
					case 'cv':
						do_action( 'wp_bullhorn_render_cv_form_pre_cv', $element_to_show, $settings );

						?>
                        <label for="fileToUpload"><?php esc_html_e( 'Your Resume', 'bh-staffing-job-listing-and-cv-upload-for-wp' ); ?>
                            <span class="gfield_required"> *</span></label>
                        <span class="<?php echo apply_filters( 'wp_bullhorn_render_cv_form_file_input_styles', 'file-to-upload-wrap' ); ?>">
                            <input id="fileToUpload" name="resume" type="file"
                                   accept=".pdf,.docx,.doc,.text,.rft,.html"/>
                        </span>
                        <br/><br/>

						<?php
						do_action( 'wp_bullhorn_render_cv_form_post_cv', $element_to_show, $settings );
						break;


				}
			}

			if ( false === $position_added ) {
				if ( isset( $_GET['position'] ) ) {
					printf( '<input id="position" name="position" type="hidden" value="%s" />', esc_attr( $_GET['position'] ) );
				} elseif ( \Bullhorn_2_WP::$post_type_job_listing === get_post_type() ) {
					printf( '<input id="position" name="position" type="hidden" value="%s" />', esc_attr( get_post_meta( get_the_ID(), 'bullhorn_job_id', true ) ) );
				}
			}

			printf( '<input id="post" name="post" type="hidden" value="%s" />',
				esc_attr( ( isset( $_GET['post'] ) ) ? isset( $_GET['post'] ) : get_the_ID() )
			);

			wp_nonce_field( 'bullhorn_cv_form', 'bullhorn_cv_form' );
			do_action( 'wp_bullhorn_render_cv_form_bottom', $element_to_show, $settings );

            printf( '<input name="submit" type="submit" value="%s"/>', apply_filters( 'bullhorn_submit_text', __( 'Upload Resume',  'bh-staffing-job-listing-and-cv-upload-for-wp' ) ) )

            do_action( 'wp_bullhorn_render_cv_form_close', $element_to_show, $settings ); ?>
            <div id="bullhorn_upload_overlay">
                <div class="bullhorn_upload_overlay_background"></div>
				<?php
				ob_start();
				?>
                <div class="bullhorn_upload_overlay_message">
                    <span class="spinner"></span>
					<?php esc_html_e( apply_filters( 'wp_bullhorn_form_submitted_message', 'We are uploading your application, it may it take few moments to read your resume. Please wait!' ) ); ?>
                </div>
				<?php
				$html = ob_get_contents();
				ob_end_clean();
				echo wp_kses_post( apply_filters( 'wp_bullhorn_form_overlay_contents', $html ) );
				?>
            </div>

        </form>
        <script type="application/javascript">

            jQuery(document).ready(function () {
                error_color = '#FFDFE0';

                defaut_file_color = jQuery('#fileToUpload').css('background-color'); //'#fff';
                defaut_color = jQuery('#email').css('background-color'); //'#d0eafa';
                jQuery('#bullhorn-resume').on('submit', function () {


                    var $email = jQuery('#email'),
                        $no_error = true,
                        $name,
                        $fileToUpload;

                    if (( 3 > $email.val().length ) || !isValidEmailAddress($email.val())) {
                        $email.css('background-color', error_color);
                        $no_error = false;
                    } else {
                        $email.css('background-color', defaut_color);
                    }
                    $name = jQuery('#name');
                    if (3 > $name.val().length) {
                        $name.css('background-color', error_color);
                        $no_error = false;
                    } else {
                        $name.css('background-color', defaut_color);
                    }
                    $fileToUpload = jQuery('#fileToUpload');
                    if (3 > $fileToUpload.val().length) {
                        $fileToUpload.css('background-color', error_color);
                        $no_error = false;
                    } else {
                        $fileToUpload.css('background-color', defaut_file_color);
                    }

                    if (1 === jQuery('select#position').length) {

                        $position = jQuery('select#position');
                        if (0 > $position.val()) {
                            $position.css('background-color', error_color);
                            $no_error = false;
                        } else {
                            $position.css('background-color', defaut_color);
                        }
                    }

                    //	e.preventDefault();
                    if (true === $no_error) {
                        jQuery('#bullhorn_upload_overlay').show();
                    }
                    return $no_error;
                });

                function isValidEmailAddress(emailAddress) {
                    var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;
                    return pattern.test(emailAddress);
                }
            });
        </script>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return apply_filters( 'wp_bullhorn_render_cv_return_html', $content, $element_to_show, $settings );
	}

	/**
	 * Adds the shortcode for generating a list of Bullhorn job listings. It has the
	 * ability to filter by state, category, and partial matching of job titles.
	 *
	 * Example usages:
	 * [bullhorn] -- Default usage
	 * [bullhorn state="California" type="Contract"] -- Shows contract jobs in CA
	 * [bulllhorn limit=50 show_date=true] -- Shows 50 jobs with their posting date
	 * [bullhorn title="Intern"] -- Only shows jobs that have the word "Intern" in the title
	 *
	 * @param  array $atts
	 *
	 * @return string
	 */
	public static function bullhorn_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'limit'        => 5,
			'show_date'    => false,
			'state'        => null,
			'type'         => null,
			'title'        => null,
			'columns'      => 1,
			'show_content' => 'extract',
			// full, 10 (words), extract, null
			'meta_to_show' => 'title, content',
			// 'bullhorn_job_id','bullhorn_job_address','bullhorn_json_ld','employmentType','baseSalary','city','state','Country','zip',
		), $atts );

		$output = null;

		$limit        = absint( $atts['limit'] );
		$show_date    = (bool) $atts['show_date'];
		$state        = esc_attr( $atts['state'] );
		$type         = esc_attr( $atts['type'] );
		$title        = esc_attr( $atts['title'] );
		$columns      = absint( $atts['columns'] );
		$show_content = esc_attr( $atts['show_content'] );
		$meta_to_show = array_map( 'trim', explode( ',', esc_attr( $atts['meta_to_show'] ) ) );


		// Only allow up to two columns for now
		if ( $columns > 4 or $columns < 1 ) {
			$columns = 1;
		}

		$args = array(
			'post_type'      => \Bullhorn_2_WP::$post_type_job_listing,
			'posts_per_page' => intval( $limit ),
			'tax_query'      => array(),
		);

		if ( $state ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Bullhorn_2_WP::$taxonomy_listing_state,
				'field'    => 'slug',
				'terms'    => sanitize_title( $state ),
			);
		}

		if ( isset( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Bullhorn_2_WP::$taxonomy_listing_state,
				'field'    => 'slug',
				'terms'    => sanitize_key( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ] ),
			);
		}

		if ( $type ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Bullhorn_2_WP::$taxonomy_listing_category,
				'field'    => 'slug',
				'terms'    => sanitize_title( $type ),
			);
		}

		if ( isset( $_GET[\Bullhorn_2_WP::$taxonomy_listing_category] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => \Bullhorn_2_WP::$taxonomy_listing_category,
				'field'    => 'slug',
				'terms'    => sanitize_key( $_GET[\Bullhorn_2_WP::$taxonomy_listing_category] ),
			);
		}

		if ( $title ) {
			$args['post_title_like'] = $title;
		}
		$possible_fields = apply_filters( 'bullhorn_shortcode_possiable_fields_and_order', array(
			'bullhorn_job_id',
			'title',
			'content',
			'type',
			'show_date',
			'bullhorn_job_address',
			'bullhorn_json_ld',
			'employmentType',
			'baseSalary',
			'city',
			'state',
			'Country',
			'zip',
		) );

		$jobs = new \WP_Query( $args );
		if ( $jobs->have_posts() ) {

			$output .= '<ul class="bullhorn-listings">';
			while ( $jobs->have_posts() ) {
				$jobs->the_post();
				$id     = get_the_ID();
				$output .= sprintf( '<li id="job-%s">', $id );
				$output = apply_filters( 'bullhorn_shortcode_top_job', $output, $id );


				foreach ( $possible_fields as $possible_field ) {


					if ( ! in_array( $possible_field, $meta_to_show, true ) ) {

						continue;
					}

					switch ( $possible_field ) {
						case 'title':
							$output .= sprintf( '<a href="%s">%s</a>', esc_url_raw( get_permalink() ), esc_html( get_the_title() ) );

							if ( $show_date ) {
								$output .= sprintf( '<span class="date"> posted on %s</span>', esc_html( get_the_date( 'F jS, Y' ) ) );
							}

							break;
						case 'content':
							if ( 'full' === $show_content ) {
								$output .= sprintf( '<div class="%s %s">%s</div>', $possible_field, $show_content, wp_kses_post( get_the_content() ) );
							} elseif ( is_numeric( $show_content ) || 'extract' === $show_content ) {
								if ( is_numeric( $show_content ) ) {
									self::$show_content_count = absint( $show_content );
									add_filter( 'excerpt_length', function ( $length ) {
										return self::$show_content_count;
									}
										, 999 );
								}
								$output .= sprintf( '<div class="%s %s">%s</div>', $possible_field, $show_content, wp_kses_post( get_the_excerpt() ) );
							}
							break;

						default:
							if ( in_array( $possible_field, $meta_to_show, true ) ) {
								$meta_value = get_post_meta( $id, $possible_field, true );
								if ( false !== $meta_value ) {
									$meta_value = apply_filters( 'bullhorn-shortcode-' . $possible_field . '-meta-value', $meta_value, $possible_field, $id );
									$output     .= sprintf( '<div class="%s">%s</div>', esc_attr( $possible_field ), esc_html( $meta_value ) );
								}
							}
							break;
					}
				}
				$output = apply_filters( 'bullhorn_shortcode_bottom_job', $output, $id );
				$output .= '</li>';
			}
			$output .= '</ul>';
		} else {
			$output .= '<p>Nothing Matches Your Search</p>';
		}

		$c      = intval( $columns );
		$output .= '<style>';
		$output .= '.bullhorn-listings { -moz-column-count: ' . $c . '; -moz-column-gap: 20px; -webkit-column-count: ' . $c . '; -webkit-column-gap: 20px; column-count: ' . $c . '; column-gap: 20px; }';
		$output .= '</style>';
		$output .= '<!--[if lt IE 10]><style>.bullhorn-listings li { width: ' . ( 100 / $c ) . '%; float: left; }</style><![endif]-->';

		return $output;
	}


	/**
	 * Adds the ability to filter posts in WP_Query by post title.
	 *
	 * @param string $where
	 * @param WP_Query $wp_query
	 *
	 * @return string
	 */
	public static function bullhorn_title_like_posts_where( $where, &$wp_query ) {
		global $wpdb;

		if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql( like_escape( $post_title_like ) ) . '%\'';
		}

		return $where;
	}


	/**
	 * Adds the shortcode for generating a list of Bullhorn states.
	 *
	 * @param  array $atts
	 *
	 * @return string
	 */
	public static function bullhorn_categories( $atts ) {
		if ( ! empty( $atts ) ) {
			_doing_it_wrong( __FUNCTION__, 'bullhorn categories Shortcode does not need attributes ', 2.0 );
		}
		$output = '<select onchange="if (this.value) window.location.href=this.value">';
		$output .= '<option value="">Filter by category...</option>';

		$categories = get_categories( array(
			'taxonomy'   => \Bullhorn_2_WP::$taxonomy_listing_category,
			'hide_empty' => 0,
		) );
		foreach ( $categories as $category ) {
			$params = array( \Bullhorn_2_WP::$taxonomy_listing_category => $category->slug );
			if ( isset( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ] ) ) {
				$params[ \Bullhorn_2_WP::$taxonomy_listing_state ] = $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ];
			}

			$selected = null;
			if ( isset( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_category ] ) and $_GET[ \Bullhorn_2_WP::$taxonomy_listing_category ] === $category->slug ) {
				$selected = 'selected="selected"';
			}

			$output .= '<option value="' . get_post_type_archive_link( \Bullhorn_2_WP::$post_type_job_listing ) . '?' . http_build_query( $params ) . '" ' . $selected . '>' . esc_html( $category->name ) . '</option>';
		}

		$output .= '</select>';

		return $output;
	}


	/**
	 * Adds the shortcode for generating a list of Bullhorn states.
	 *
	 * @param  array $atts
	 *
	 * @return string
	 */
	public static function bullhorn_states( $atts ) {
		if ( ! empty( $atts ) ) {
			_doing_it_wrong( __FUNCTION__, 'bullhorn categories Shortcode does not need attributes ', 2.0 );
		}
		$output = '<select onchange="if (this.value) window.location.href=this.value">';
		$output .= '<option value="">Filter by state...</option>';

		$states = get_categories( array(
			'taxonomy'   => \Bullhorn_2_WP::$taxonomy_listing_state,
			'hide_empty' => 0,
		) );
		foreach ( $states as $state ) {
			$params = array( \Bullhorn_2_WP::$taxonomy_listing_state => $state->slug );
			if ( isset( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_category ] ) ) {
				$params[ \Bullhorn_2_WP::$taxonomy_listing_category ] = $_GET[ \Bullhorn_2_WP::$taxonomy_listing_category ];
			}

			$selected = null;
			if ( isset( $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ] ) and $_GET[ \Bullhorn_2_WP::$taxonomy_listing_state ] === $state->slug ) {
				$selected = 'selected="selected"';
			}

			$output .= '<option value="' . get_post_type_archive_link( \Bullhorn_2_WP::$post_type_job_listing ) . '?' . http_build_query( $params ) . '" ' . $selected . '>' . esc_html( $state->name ) . '</option>';
		}

		$output .= '</select>';

		return $output;
	}


	/**
	 * Adds the shortcode for searching job postings.
	 *
	 * @param  array $atts
	 *
	 * @return string
	 */
	public static function bullhorn_search( $atts ) {
		if ( ! empty( $atts ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'bullhorn categories Shortcode does not need attributes', 'bh-staffing-job-listing-and-cv-upload-for-wp' ), 2.0 );
		}
		$form   = get_search_form( false );
		$hidden = '<input type="hidden" name="post_type" value="' . \Bullhorn_2_WP::$post_type_job_listing . '" />';

		return str_replace( '</form>', $hidden . '</form>', $form );
	}
}
