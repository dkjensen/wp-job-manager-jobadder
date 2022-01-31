<?php
/**
 * Plugin Name: WP Job Manager - JobAdder Integration
 * Description: 
 * Version: 0.0.0-development
 * Author: Seattle Web Co.
 * Author URI: https://seattlewebco.com
 * Text Domain: wp-job-manager-jobadder
 * Requires PHP: 7.2.5
 *
 * @package WP Job Manager - JobAdder Integration
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_JOB_MANAGER_JOBADDER_VER', '0.0.0-development' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_NAME', 'WP Job Manager - JobAdder Integration' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'WP_JOB_MANAGER_JOBADDER_LOG' ) ) {
    define( 'WP_JOB_MANAGER_JOBADDER_LOG', WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'logs/log-debug.log' );
}


require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'vendor/autoload.php';
require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-recruiter.php';


function WP_Job_Manager_JobAdder() {
    return \SeattleWebCo\WPJobManager\Recruiter\JobAdder\Recruiter::instance();
}
WP_Job_Manager_JobAdder();

register_activation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'job_manager_jobadder_sync_jobs' );

    wp_schedule_event( time(), 'jobadder_sync', 'job_manager_jobadder_sync_jobs' );
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'job_manager_jobadder_sync_jobs' );
} );
