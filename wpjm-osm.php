<?php
/*
 * Plugin Name: WP Job Manager - Open Street Map
 * Description: Replace Google Map by Open Street Map and Leaflet in WP Job Manager
 * Author:      Marie Comet
 * Author URI:  https://mariecomet.fr
 * Version:     1.0.0
 * Text Domain: wpjm-osm
 * Domain Path: /languages/
 * License:     GPL v2 or later
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


add_action( 'wp_enqueue_scripts', 'wpjm_osm_script' );
function wpjm_osm_script() {

	wp_register_style( 'leaflet-css', plugin_dir_url( __FILE__ ) . 'assets/leaflet/leaflet.css' );
	wp_register_script( 'leaflet-js', plugin_dir_url( __FILE__ ) . 'assets/leaflet/leaflet.js', array( 'jquery' ) );
	wp_register_script( 'leaflet-map', plugin_dir_url( __FILE__ ) . 'assets/map.js', array( 'jquery', 'leaflet-js' ) );
	wp_register_style( 'wpjm-osm-style', plugin_dir_url( __FILE__ ) . 'assets/wpjm-osm-style.css' );

	if ( is_singular( 'job_listing' ) ) {
		wp_enqueue_style( 'leaflet-css' );
		wp_enqueue_script( 'leaflet-js' );
		wp_enqueue_script( 'leaflet-map' );
		wp_enqueue_style( 'wpjm-osm-style' );
	}

}

add_action( 'plugins_loaded', 'wpjm_osm_plugins_loaded' );
function wpjm_osm_plugins_loaded() {


	add_action( 'after_setup_theme', 'wpjm_osm_after_setup_theme', 2 );

	// remove WPJM geocode functions, replace them with OSM geocoding
	if( class_exists( 'WP_Job_Manager_Geocode' ) ) {

		remove_action( 'job_manager_update_job_data', array( WP_Job_Manager_Geocode::instance(), 'update_location_data' ), 20 );
		remove_action( 'job_manager_job_location_edited', array( WP_Job_Manager_Geocode::instance(), 'change_location_data' ), 20 );

		add_action( 'job_manager_update_job_data', 'wpjm_osm_update_location_data', 20, 2 );
		add_action( 'save_post', 'wpjm_osm_update_location_data', 10, 3 );

	}
}

function wpjm_osm_after_setup_theme() {

	add_filter( 'job_manager_output_jobs_defaults', 'wpjm_osm_job_manager_output_jobs_defaults', 10, 1 );
	add_action( 'job_manager_job_filters_after', 'wpjm_osm_job_manager_job_filters_after' );
	add_filter( 'the_job_location_map_link', 'wpjm_osm_the_job_location_map_link', 10, 2 );
	add_action( 'single_job_listing_start', 'wpjm_osm_single_job_listing_start', 40 );

}

/**
 * Update job listing location data
 *
 * @since 1.0.0
 *
 * @param int   $job_id The post ID.
 * @param int   $post (WP_Post) Post object.
 * @param int   $job_id The post ID.
 * @return
 */
function wpjm_osm_update_location_data( $job_id, $post, $update ) {

	$post_type = get_post_type( $job_id );

	if ( "job_listing" != $post_type ) return;

	// If this is just a revision, return
	if ( wp_is_post_revision( $job_id ) ) {
		return;
    }

	if ( isset( $_POST['_job_location'] ) && !empty( $_POST['_job_location'] ) ) {

		$complete_adress = wp_strip_all_tags( $_POST['_job_location'] );
		$existing_address = get_post_meta( $job_id, '_job_location', true );

		// Construct adress meta check if address is not empty and if different of existing address. If no do not call OSM api 
		if ( $complete_adress != $existing_address ) {

			$map_url = 'https://nominatim.openstreetmap.org/search/' . $complete_adress . '?format=json&addressdetails=1&limit=1';

			$request = wp_remote_get( $map_url );

			if ( is_array( $request ) && ! is_wp_error( $request ) ) {
				$json = wp_remote_retrieve_body( $request );

				if( empty( $json ) ) {
					update_post_meta( $job_id, 'geolocated', 0 );
					return false;
				}

				$json = json_decode( $json );
				$lat = $json[0]->lat;
				$long = $json[0]->lon;
				$display_name = $json[0]->display_name;

				if ( $lat  && '' != $lat ) {
					update_post_meta( $job_id, 'geolocation_lat', $lat );
				}

				if ( $long  && '' != $long ) {
					update_post_meta( $job_id, 'geolocation_long', $long );
				}

				if ( $complete_adress  && '' != $complete_adress ) {
					update_post_meta( $job_id, '_job_location', $complete_adress );
					update_post_meta( $job_id, 'geolocated', 1 );
				}

				if ( $display_name  && '' != $display_name ) {
					update_post_meta( $job_id, 'geolocation_formatted_address', $display_name );
				}
			}
		}
	} else {
		delete_post_meta( $job_id, 'geolocation_formatted_address' );
		delete_post_meta( $job_id, 'geolocation_lat' );
		delete_post_meta( $job_id, 'geolocation_long' );
		update_post_meta( $job_id, 'geolocated', 0 );
	}
}

/**
 * Set default value for "show_map" attribute in [jobs] shortcode
 *
 * @since 1.0.0
 * @param array $atts [jobs] shortcode attributes
 * @return array $atts
 */
function wpjm_osm_job_manager_output_jobs_defaults( $atts ) {

	$atts['show_map'] = 0;
	return $atts;
}

/**
 * Display Map on job listings list and enqueue scripts & styles
 *
 * @since 1.0.0
 * @param array $atts [jobs] shortcode attributes
 */
function wpjm_osm_job_manager_job_filters_after( $atts ) {
	
	if ( isset( $atts[ 'show_map' ] ) && $atts[ 'show_map' ] ) {
		wp_enqueue_style( 'leaflet-css' );
		wp_enqueue_script( 'leaflet-js' );
		wp_enqueue_script( 'leaflet-map' );
		wp_enqueue_style( 'wpjm-osm-style' );
		echo '<div id="jobsMap" class="wpjm-osm-map"></div>';
	}
}

/**
 * Filter the location for the job listing.
 *
 * @since 1.0.0
 * @param  string $location job location post meta
 * @param int|WP_Post $post
 * @return string $map_link 
 */
function wpjm_osm_the_job_location_map_link( $location, $post ) {

	$map_link = '<a class="osm_map_link" href="' . esc_url( 'https://www.openstreetmap.org/search?query=' . rawurlencode( wp_strip_all_tags( $location ) ) ) . '">' . esc_html( wp_strip_all_tags( $location ) ) . '</a>';

	return $map_link;
}

/**
 * Displays the map on single job listing
 *
 * @since 1.0.0
 */
function wpjm_osm_single_job_listing_start() {
	if ( !get_the_job_location() ) {
		return;
	}
	global $post;
	?>
	<div id="jobSingleMap" class="wpjm-osm-map">
		<li class="job-listing-marker" data-longitude="<?php echo esc_attr( $post->geolocation_long ); ?>" data-latitude="<?php echo esc_attr( $post->geolocation_lat ); ?>" data-title="<?php echo esc_attr( $post->post_title ); ?>"></li>
	</div>
	<?php

}