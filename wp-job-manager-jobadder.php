<?php
/**
 * Plugin Name: WP Job Manager - JobAdder Integration
 * Description: 
 * Version: 1.0.0
 * Author: David Jensen
 * Author URI: https://dkjensen.com
 * Text Domain: wp-job-manager-jobadder
 *
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wp_version;

define( 'WP_JOB_MANAGER_JOBADDER_VER', '1.0.0' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_NAME', 'WP Job Manager - JobAdder Integration' );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JOB_MANAGER_JOBADDER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


// Load Composer
require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'vendor/autoload.php';
require WP_JOB_MANAGER_JOBADDER_PLUGIN_DIR . 'includes/class-wp-job-manager-jobadder.php';


function WP_Job_Manager_JobAdder() {
    return WP_Job_Manager_JobAdder::instance();
}
WP_Job_Manager_JobAdder();