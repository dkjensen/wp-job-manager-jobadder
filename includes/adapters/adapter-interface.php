<?php
/**
 * API interface
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Adapter_Interface {


    public function connected();

    
    public function get_jobs();


    public function get_job( $job_id );


    public function post_job_application( $job_id, $data );


    public function sync_jobs();


    public function job_exists( $job_id );

}