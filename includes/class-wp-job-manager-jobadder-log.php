<?php
/**
 * Logging class
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class WP_Job_Manager_JobAdder_Log {

	/**
	 * Instance of the logger class
	 *
	 * @var Monolog\Logger
	 */
	protected $log = null;


	/**
	 * Setup
	 */
	public function __construct() {
		$this->log = new Logger( 'wp-job-manager-jobadder' );
    	$this->log->pushHandler( new StreamHandler( WP_JOB_MANAGER_JOBADDER_LOG, Logger::DEBUG ) );
	}


	/**
	 * Logs an info message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public function info( $message, $details = array() ) {
		$this->log->info( esc_html__( $message, 'wp-job-manager-jobadder' ), (array) $details );
	}


	/**
	 * Logs an error message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public function error( $message, $details = array() ) {
		$this->log->error( esc_html__( $message, 'wp-job-manager-jobadder' ), (array) $details );
	}
	
}