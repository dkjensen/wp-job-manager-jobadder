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
     * Insures that only one instance of WP_Job_Manager_JobAdder exists in memory at any one time.
     * 
     * @return WP_Job_Manager_JobAdder The one true instance of WP_Job_Manager_JobAdder
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Job_Manager_JobAdder ) ) {
            self::$instance = new WP_Job_Manager_JobAdder;
            self::$instance->includes();

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