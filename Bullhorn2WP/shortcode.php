<?php

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
function bullhorn_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'limit'     => 5,
		'show_date' => false,
		'state'     => null,
		'type'      => null,
		'title'     => null,
		'columns'   => 1,
	), $atts ) );

	$output = null;

	// Only allow up to two columns for now
	if ( $columns > 4 or $columns < 1 ) {
		$columns = 1;
	}

	$args = array(
		'post_type'      => 'bullhornjoblisting',
		'posts_per_page' => intval( $limit ),
		'tax_query'      => array(),
	);

	if ( $state ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'bullhorn_state',
			'field'    => 'slug',
			'terms'    => sanitize_title( $state ),
		);
	}

	if ( isset( $_GET['bullhorn_state'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'bullhorn_state',
			'field'    => 'slug',
			'terms'    => sanitize_key( $_GET['bullhorn_state'] ),
		);
	}

	if ( $type ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'bullhorn_category',
			'field'    => 'slug',
			'terms'    => sanitize_title( $type ),
		);
	}

	if ( isset( $_GET['bullhorn_category'] ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'bullhorn_category',
			'field'    => 'slug',
			'terms'    => sanitize_key( $_GET['bullhorn_category'] ),
		);
	}

	if ( $title ) {
		$args['post_title_like'] = $title;
	}

	$jobs = new WP_Query( $args );
	if ( $jobs->have_posts() ) {
		$output .= '<ul class="bullhorn-listings">';
		while ( $jobs->have_posts() ) {
			$jobs->the_post();

			$output .= '<li>';
			$output .= '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
			if ( $show_date ) {
				$output .= ' posted on ' . get_the_date( 'F jS, Y' );
			}
			$output .= '</li>';
		}
		$output .= '</ul>';
	} else {
		$output .= '<p>Nothing Matches Your Search</p>';
	}

	$c = intval( $columns );
	$output .= '<style>';
	$output .= '.bullhorn-listings { -moz-column-count: ' . $c . '; -moz-column-gap: 20px; -webkit-column-count: ' . $c . '; -webkit-column-gap: 20px; column-count: ' . $c . '; column-gap: 20px; }';
	$output .= '</style>';
	$output .= '<!--[if lt IE 10]><style>.bullhorn-listings li { width: ' . ( 100 / $c ) . '%; float: left; }</style><![endif]-->';

	return $output;
}

add_shortcode( 'bullhorn', 'bullhorn_shortcode' );

/**
 * Added so shortcodes are processed in text widgets.
 */
add_filter( 'widget_text', 'do_shortcode' );

/**
 * Adds the ability to filter posts in WP_Query by post title.
 *
 * @param string   $where
 * @param WP_Query $wp_query
 *
 * @return string
 */
function bullhorn_title_like_posts_where( $where, &$wp_query ) {
	global $wpdb;

	if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
		$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' . esc_sql( like_escape( $post_title_like ) ) . '%\'';
	}

	return $where;
}

add_filter( 'posts_where', 'bullhorn_title_like_posts_where', 10, 2 );

/**
 * Adds the shortcode for generating a list of Bullhorn states.
 *
 * @param  array $atts
 *
 * @return string
 */
function bullhorn_categories( $atts ) {
	$output = '<select onchange="if (this.value) window.location.href=this.value">';
	$output .= '<option value="">Filter by category...</option>';

	$categories = get_categories( array(
		'taxonomy'   => 'bullhorn_category',
		'hide_empty' => 0,
	) );
	foreach ( $categories as $category ) {
		$params = array( 'bullhorn_category' => $category->slug );
		if ( isset( $_GET['bullhorn_state'] ) ) {
			$params['bullhorn_state'] = $_GET['bullhorn_state'];
		}

		$selected = null;
		if ( isset( $_GET['bullhorn_category'] ) and $_GET['bullhorn_category'] === $category->slug ) {
			$selected = 'selected="selected"';
		}

		$output .= '<option value="?' . http_build_query( $params ) . '" ' . $selected . '>' . esc_html( $category->name ) . '</option>';
	}

	$output .= '</select>';

	return $output;
}

add_shortcode( 'bullhorn_categories', 'bullhorn_categories' );

/**
 * Adds the shortcode for generating a list of Bullhorn states.
 *
 * @param  array $atts
 *
 * @return string
 */
function bullhorn_states( $atts ) {
	$output = '<select onchange="if (this.value) window.location.href=this.value">';
	$output .= '<option value="">Filter by state...</option>';

	$states = get_categories( array(
		'taxonomy'   => 'bullhorn_state',
		'hide_empty' => 0,
	) );
	foreach ( $states as $state ) {
		$params = array( 'bullhorn_state' => $state->slug );
		if ( isset( $_GET['bullhorn_category'] ) ) {
			$params['bullhorn_category'] = $_GET['bullhorn_category'];
		}

		$selected = null;
		if ( isset( $_GET['bullhorn_state'] ) and $_GET['bullhorn_state'] === $state->slug ) {
			$selected = 'selected="selected"';
		}

		$output .= '<option value="?' . http_build_query( $params ) . '" ' . $selected . '>' . esc_html( $state->name ) . '</option>';
	}

	$output .= '</select>';

	return $output;
}

add_shortcode( 'bullhorn_states', 'bullhorn_states' );

/**
 * Adds the shortcode for searching job postings.
 *
 * @param  array $atts
 *
 * @return string
 */
function bullhorn_search( $atts ) {
	$form   = get_search_form( false );
	$hidden = '<input type="hidden" name="post_type" value="bullhornjoblisting" />';

	return str_replace( '</form>', $hidden . '</form>', $form );
}

add_shortcode( 'bullhorn_search', 'bullhorn_search' );