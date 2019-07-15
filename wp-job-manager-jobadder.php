<?php
/**
 * Plugin Name: WP Job Manager - JobAdder Integration
 * Description: 
 * Version: 1.0.0
 * Author: Seattle Web Co.
 * Author URI: https://seattlewebco.com
 * Text Domain: wp-job-manager-jobadder
 *
 * @package WP Job Manager - JobAdder Integration
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_JOB_MANAGER_JOBADDER_VER', '1.0.0' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_NAME', 'WP Job Manager - JobAdder Integration' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_JOB_MANAGER_RECRUITER_SLUG', 'jobadder' );

if ( ! defined( 'WP_JOB_MANAGER_JOBADDER_LOG' ) ) {
    define( 'WP_JOB_MANAGER_JOBADDER_LOG', WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'logs/log-debug.log' );
}


require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'vendor/autoload.php';
require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-recruiter.php';


function WP_Job_Manager_JobAdder() {
    return \SeattleWebCo\WPJobManager\Recruiter\JobAdder\Recruiter::instance();
}
WP_Job_Manager_JobAdder();


register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'job_manager_' . WP_JOB_MANAGER_RECRUITER_SLUG . '_sync_jobs' );
} );