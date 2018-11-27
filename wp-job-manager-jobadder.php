<?php
/**
 * Plugin Name: WP Job Manager - JobAdder
 * Description: Integrates JobAdder job listings with the WP Job Manager plugin.
 * Version: 1.0.0
 * Author: David Jensen
 * Author URI: http://dkjensen.com
 * License: GPL2
 */


require_once 'vendor/autoload.php';


// Log path
if( ! defined( 'WP_JOB_MANAGER_JOBADDER_LOG' ) ) {
    define( 'WP_JOB_MANAGER_JOBADDER_LOG', plugin_dir_path(  __FILE__ ) . 'logs/log-inbox.log' );
}


class WPJM_JobAdder {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	public function includes() {
		require_once WPJM_JobAdder::plugin_path() . '/includes/wp-job-manager-jobadder-functions.php';
		require_once WPJM_JobAdder::plugin_path() . '/includes/admin/wp-job-manager-job-listing-meta.php';
	}

	public function hooks() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'jobadder_inbox' ) );
	}

	public function query_vars( $query_vars ) {
	    $query_vars[] = apply_filters( 'wpjmja_query_var', 'wpjmja' );
	    
	    return $query_vars;
	}

	public function jobadder_inbox() {
		$query_var = get_query_var( apply_filters( 'wpjmja_query_var', 'wpjmja' ) );

		if( $query_var == 'jobadder' ) {
			require_once apply_filters( 'wpjmja_jobadder_inbox', WPJM_JobAdder::plugin_path() . '/includes/inbox.php' );
		}
	}

	public static function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

WPJM_JobAdder::instance();