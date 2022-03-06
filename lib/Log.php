<?php
/**
 * Logging class
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Log {

	/**
	 * Instance of the logger class
	 *
	 * @var Monolog\Logger
	 */
	protected static $log = null;

	/**
	 * Logs an info message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public static function info( $message, $details = array() ) {
		self::$log = new Logger( 'wp-job-manager-jobadder' );
    	self::$log->pushHandler( new StreamHandler( WP_JOB_MANAGER_JOBADDER_LOG, Logger::DEBUG ) );
		self::$log->info( esc_html( $message ), (array) $details );
	}


	/**
	 * Logs an error message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public static function error( $message, $details = array() ) {
		self::$log = new Logger( 'wp-job-manager-jobadder' );
    	self::$log->pushHandler( new StreamHandler( WP_JOB_MANAGER_JOBADDER_LOG, Logger::DEBUG ) );
		self::$log->error( esc_html( $message ), (array) $details );
	}
	
}
