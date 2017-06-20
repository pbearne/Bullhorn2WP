<?php

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

			$cat_ids        = array();
			$parent_term_id = 1;
			$slug           = '';

			if (  isset( $meta['Country'] ) || isset( $meta['geolocation_country_long'] ) ) {
				$geo  = isset( $meta['Country'] ) ? $meta['Country'][0] : $meta['geolocation_country_long'][0];
				$term = self::get_region_term_by_name_and_parent( $geo, null );

				if ( false !== $term ) {
					$cat_ids[]      = $term->term_id;
					$parent_term_id = $term->term_id;
					$slug           = $term->slug;
				} else {
					$parent_term    = wp_insert_term( $geo, self::$job_listing_region_tax );
					$term           = self::get_region_term_by_name_and_parent( $geo, null );
					$slug           = $term->slug;
					$parent_term_id = $parent_term['term_id'];

					$cat_ids[] = $parent_term_id;
				}
			}

			if ( isset( $meta['state'] ) || isset( $meta['geolocation_state_long'] ) ) {
				$geo  = isset( $meta['state'] ) ? $meta['state'][0] : $meta['geolocation_state_long'][0];
				$term = self::get_region_term_by_name_and_parent( $geo, $parent_term_id );

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
					$term           = self::get_region_term_by_name_and_parent( $geo, $parent_term_id );
					$slug           = $term->slug;
					$parent_term_id = $parent_term['term_id'];

					$cat_ids[] = $parent_term_id;
				}
			}

			if ( isset( $meta['city'] ) || isset( $meta['geolocation_city'] ) ) {
				$geo  = isset( $meta['city'] ) ? $meta['city'][0] : $meta['geolocation_city'][0];
				$term = self::get_region_term_by_name_and_parent( $geo, $parent_term_id );

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
		}
	}

	public static function get_region_term_by_name_and_parent( $name, $parent_id ) {

		$args = array(
			'name' => $name,
			'taxonomy' => self::$job_listing_region_tax,
			'hide_empty' => false,
		);

		if ( $parent_id ) {
			$args['parent'] = $parent_id;
		} else {
			$args['childless'] = true;
		}

		$terms = get_terms( $args );

		//remove childless

		if ( empty( $terms ) ) {
			return false;
		} else {
			return $terms[0];
		}
	}


}