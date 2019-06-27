<?php
/**
 * Jobs synchronization
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder_Jobs {


    /**
     * Inserts/updates a job listing
     *
     * @param object  $job
     * @param object  $job_ad
     * @param integer $job_board
     * @return mixed  integer|boolean
     */
    public function update_job( $job, $job_ad, $job_board ) {
        if ( $job && isset( $job->jobId ) && isset( $job_ad->adId ) ) {
            $job_postdata = apply_filters( 'job_manager_jobadder_post_data', array(
                'post_title' 		=> isset( $job->jobTitle ) ? $job->jobTitle : __( 'Untitled job', 'wp-job-manager-jobadder' ),
                'post_content' 		=> isset( $job->jobDescription ) ? $job->jobDescription : '',
                'post_status'		=> 'publish',
                'post_type'			=> 'job_listing',
                'meta_input'		=> array(
                    '_jid'			        => $job->jobId,
                    '_jobadid'              => $job_ad->adId,
                    '_job_boardid'          => $job_board,
                    '_job_salary'           => isset( $job->salary ) ? $this->format_salary( $job->salary ) : '',
                    '_job_salary_period'    => isset( $job->salary ) && isset( $job->salary->ratePer ) ? $job->salary->ratePer : '',
                    '_job_location'         => isset( $job->location ) && isset( $job->location->name ) ? $job->location->name : '',
                    '_application'          => get_option( 'admin_email' ),
                    '_company_name'         => isset( $job->company ) && isset( $job->company->name ) ? $job->company->name : '',
                    '_filled'               => isset( $job->status ) && isset( $job->status->active ) && $job->status->active ? 0 : 1
                ),
            ) );

            if ( false !== ( $existing = $this->job_exists( $job->jobId ) ) ) {
                $job_postdata['ID'] = $existing;
    
                $post_job = wp_update_post( $job_postdata );

                do_action( 'job_manager_jobadder_job_updated', $post_job, $job, $job_ad );
            } else {
                $post_job = wp_insert_post( $job_postdata );

                do_action( 'job_manager_jobadder_job_inserted', $post_job, $job, $job_ad );
            }

            return $post_job;
        }

        return false;
    }


    /**
     * Check the database if a job already exists with the JobAdder job ID
     *
     * @param integer $jid
     * @return mixed boolean|string
     */
    public function job_exists( $jid ) {
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare( "
            SELECT post_id 
            FROM   $wpdb->postmeta 
            WHERE  meta_key = '_jid'
            AND    meta_value = '%s' 
            LIMIT  1", $jid 
        ) );

        if ( null === $exists ) {
            return false;
        }

        return $exists;
    }


    /**
     * Format the salary object into a human-readable string
     *
     * @param stdClass $salary
     * @return string
     */
    public function format_salary( stdClass $salary ) {
        $low = isset( $salary->rateLow ) ? $salary->rateLow : '';
        $high = isset( $salary->rateHigh ) ? $salary->rateHigh : '';
        $per = isset( $salary->ratePer ) ? $salary->ratePer : '';

        if ( $low === $high ) {
            $salary = $high;
        } else {
            $salary = min( $low, $high ) . ' &ndash; ' . max( $low, $high );
        }

        if ( $per ) {
            $salary .= '/' . esc_html_e( $per, 'wp-job-manager-jobadder' ); 
        }

        return $salary;
    }

}