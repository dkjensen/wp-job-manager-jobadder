<?php
/**
 * Functions
 * 
 * @package wp-job-manager-jobadder
 */

function jobadder_get_jobs() {
	return (array) get_option( '_jobadder_jobs' );
}


function jobadder_job_imported( $jid ) {
	$jobadder_jobs = get_option( '_jobadder_jobs' );

	if( ! empty( $jobadder_jobs ) ) {
		$jobs = array_map( 'intval', $jobadder_jobs );

		if( in_array( $jid, $jobs ) ) {
			return true;
		}
	}

	return false;
}


function jobadder_job_deleted( $jid ) {
	$jobadder_jobs = get_option( '_jobadder_jobs' );

	if( ! empty( $jobadder_jobs ) ) {
		$jobs = array_map( 'intval', $jobadder_jobs );

	}
}


function jobadder_get_fields() {
	return apply_filters( 'wpjmja_job_fields', array(
		'_bullet_points' => array(
			''
		)
	) );
}