<?php
/**
 * Exception handling class
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder_Exception extends Exception {

    /**
     * Additional details to describe the exception
     *
     * @var mixed
     */
    protected $details;

    /**
     * Overwrite constructor
     *
     * @param string  $message
     * @param integer $code
     * @param array   $details
     */
	public function __construct( $message = '', int $code = 0, array $details = array() ) {
        $this->details = json_encode( $details );

        parent::__construct( __( 'JobAdder Message: ', 'wp-job-manager-jobadder' ) . $message, $code, null );
    }


    /**
     * Additional details
     *
     * @return mixed
     */
    public function getDetails() {
        return $this->details;
    }
	
}