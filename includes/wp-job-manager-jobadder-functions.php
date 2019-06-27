<?php
/**
 * General functions
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function job_manager_jobadder_get_synced_job_boards() {
    return array_filter( (array) get_option( 'jobadder_job_boards' ) );
}