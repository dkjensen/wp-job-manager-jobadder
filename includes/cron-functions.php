<?php
/**
 * Cron jobs
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;


function sync_jobs() {
    do_action( 'job_manager_jobadder_before_job_sync' );

    foreach ( WP_Job_Manager_JobAdder()->clients as $client ) {
        $client->sync_jobs();
    }

    do_action( 'job_manager_jobadder_after_job_sync' );
}
add_action( 'job_manager_jobadder_sync_jobs', __NAMESPACE__ . '\sync_jobs' );

function schedule_sync() {
    wp_clear_scheduled_hook( 'job_manager_jobadder_sync_jobs' );

    wp_schedule_event( time(), 'jobadder_sync', 'job_manager_jobadder_sync_jobs' );
}
add_action( 'update_option_jobadder_sync_interval', __NAMESPACE__ . '\schedule_sync' );
