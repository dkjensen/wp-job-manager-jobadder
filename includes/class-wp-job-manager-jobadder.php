<?php
/**
 * Main WP_Job_Manager_JobAdder class file
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder {

    /**
	 * Plugin object
	 */
    private static $instance;


    /**
     * Applications handling class
     *
     * @var WP_Job_Manager_JobAdder_Applications
     */
    public $applications;


    /**
     * API client
     *
     * @var WP_Job_Manager_JobAdder_Client
     */
    public $client;


    /**
     * Jobs handling class
     *
     * @var WP_Job_Manager_JobAdder_Jobs
     */
    public $jobs;
    

    /**
     * Webhooks handling class
     *
     * @var WP_Job_Manager_JobAdder_Webhooks
     */
    public $webhooks;


    /**
     * Logger class
     *
     * @var WP_Job_Manager_JobAdder_Log
     */
    public $log;

    
    /**
     * Insures that only one instance of WP_Job_Manager_JobAdder exists in memory at any one time.
     * 
     * @return WP_Job_Manager_JobAdder The one true instance of WP_Job_Manager_JobAdder
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Job_Manager_JobAdder ) ) {
            self::$instance = new WP_Job_Manager_JobAdder;
            self::$instance->includes();

            self::$instance->client = new WP_Job_Manager_JobAdder_Client;
            self::$instance->applications = new WP_Job_Manager_JobAdder_Applications;
            self::$instance->jobs = new WP_Job_Manager_JobAdder_Jobs;
            self::$instance->webhooks = new WP_Job_Manager_JobAdder_Webhooks;
            self::$instance->log = new WP_Job_Manager_JobAdder_Log;

            do_action_ref_array( 'wp_job_manager_jobadder_loaded', self::$instance ); 
        }
        
        return self::$instance;
    }


    /**
     * Include the goodies
     *
     * @return void
     */
    public function includes() {
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/wp-job-manager-jobadder-functions.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-client.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-applications.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-jobs.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-webhooks.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-log.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder-exception.php';

        if ( is_admin() ) {
            require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/admin/class-wp-job-manager-jobadder-settings.php';
        }
    }


    /**
     * Throw error on object clone
     *
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-job-manager-jobadder' ), '1.0.0' );
    }


    /**
     * Disable unserializing of the class
     * 
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-job-manager-jobadder' ), '1.0.0' );
    }

}