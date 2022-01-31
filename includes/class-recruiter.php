<?php
/**
 * Main WP_Job_Manager_JobAdder class file
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Recruiter {

    /**
	 * Plugin object
	 */
    private static $instance;


    /**
     * OAuth adapter
     *
     * @var mixed
     */
    public $oauth;


    /**
     * Webhooks handling class
     *
     * @var Webhooks
     */
    public $webhooks;


    /**
     * Logger class
     *
     * @var Log
     */
    public $log;


    /**
     * Provider client
     *
     * @var Client
     */
    public $clients;

    
    /**
     * Insures that only one instance of WP_Job_Manager_JobAdder exists in memory at any one time.
     * 
     * @return Recruiter The one true instance of Recruiter
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Job_Manager_JobAdder ) ) {
            self::$instance = new Recruiter;
            self::$instance->includes();

            self::$instance->oauth          = new Provider\JobAdderProvider( array(
                'clientId'       => get_option( 'jobadder_client_id' ),
                'clientSecret'   => get_option( 'jobadder_client_secret' ),
                'redirectUri'    => admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' )
            ) );

            self::$instance->clients        = array(
                'jobadder'  => new Client( new Adapter\JobAdderAdapter( self::$instance->oauth ) ),
            );
            self::$instance->webhooks       = new Webhooks;
            self::$instance->log            = new Log;

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
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-applications.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-webhooks.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/cron-functions.php';
        require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/wp-job-manager-jobadder-functions.php';

        if ( is_admin() ) {
            require_once WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/admin/class-settings.php';
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
