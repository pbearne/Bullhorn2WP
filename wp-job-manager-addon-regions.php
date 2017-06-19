<?php

/**
 * Created by IntelliJ IDEA.
 * User: pbear
 * Date: 2017-06-16
 * Time: 8:46 AM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Job_Manager_Addon_Regions {

	static $job_listing_region_tax = 'job_listing_region';
	static $last_job_done = 0;


	function __construct() {
		// run after the geogcode has been set
		add_action( 'job_manager_update_job_data', array( $this, 'set_location_tax' ), 30 );
		add_action( 'job_manager_job_location_edited', array( $this, 'set_location_tax' ), 30 );


		add_action( 'bullhorn_sync_complete', array( $this, 'set_location_tax' ) );
	}


	public static function set_location_tax( $job_id ) {

		if ( self::$last_job_done === $job_id ) {

			return;
		}

		if ( apply_filters( 'job_manager_geolocation_enabled', true ) ) {

			$meta = get_post_meta( $job_id );

//			if ( ! isset( $meta['geolocated'] ) && '1' !== $meta['geolocated'] ) {
//
//				return;
//			}

			$cat_ids        = array();
			$parent_term_id = 1;
			$slug           = '';

			if ( isset( $meta['geolocation_country_long'] ) ) {
				$geo  = $meta['geolocation_country_long'][0];
				$term = get_term_by( 'name', $geo, self::$job_listing_region_tax );

				if ( false !== $term ) {
					$cat_ids[]      = $term->term_id;
					$parent_term_id = $term->term_id;
					$slug           = $term->slug;
				} else {
					$parent_term    = wp_insert_term( $geo, self::$job_listing_region_tax );
					$parent_term_id = $parent_term['term_id'];
					$term           = get_term_by( 'id', $parent_term_id, self::$job_listing_region_tax );
					$slug           = $term->slug;

					$cat_ids[] = $parent_term_id;
				}
			}

			if ( isset( $meta['geolocation_state_long'] ) ) {
				$geo  = $meta['geolocation_state_long'][0];
				$term = get_term_by( 'name', $geo, self::$job_listing_region_tax );

				if ( false !== $term ) {
					$cat_ids[]      = $term->term_id;
					$parent_term_id = $term->term_id;
					$slug           = $term->slug;
				} else {
					$args = array(
						'parent' => $parent_term_id,
						'slug'   => $slug . '-' . $geo,
					);

					$parent_term    = wp_insert_term( $geo, self::$job_listing_region_tax, $args );
					$parent_term_id = $parent_term['term_id'];
					$term           = get_term_by( 'id', $parent_term_id, self::$job_listing_region_tax );
					$slug           = $term->slug;

					$cat_ids[] = $parent_term_id;
				}
			}

			if ( isset( $meta['geolocation_city'] ) ) {
				$geo  = $meta['geolocation_city'][0];
				$term = get_term_by( 'name', $geo, self::$job_listing_region_tax );

				if ( false !== $term ) {
					$cat_ids[]      = $term->term_id;
					$parent_term_id = $term->term_id;
				} else {
					$args = array(
						'parent' => $parent_term_id,
						'slug'   => $slug . '-' . $geo,
					);

					$parent_term    = wp_insert_term( $geo, self::$job_listing_region_tax, $args );
					$parent_term_id = $parent_term['term_id'];

					$cat_ids[] = $parent_term_id;
				}
			}


			$cat_ids = array_map( 'intval', $cat_ids );
			$cat_ids = array_unique( $cat_ids );

			wp_set_object_terms( $job_id, $cat_ids, self::$job_listing_region_tax );
		} // End if().
	}


}