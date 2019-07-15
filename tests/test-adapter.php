<?php
/**
 * Class AdapterTest
 *
 * @package Wp_Job_Manager_Jobadder_Api
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;


class AdapterTest extends \WP_UnitTestCase {

	public $provider;


	public function setUp() {
		$this->provider = $this->getMockBuilder( '\SeattleWebCo\WPJobManager\Recruiter\JobAdder\JobAdder_Provider' )
						 ->setConstructorArgs( array( array( 'test', 'test', admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) ) ) )
						 ->getMock();
	}


	public function test_connected() {
		$adapter = $this->getMockBuilder( '\SeattleWebCo\WPJobManager\Recruiter\JobAdder\JobAdder_Adapter' )
						->setConstructorArgs( array( $this->provider ) )
						->getMock();

		$adapter->expects( $this->any() )
				->method( 'connected' )
				->will( $this->returnValue( true ) );

		$client = new Client( $adapter );

		$this->assertTrue( $client->connected() );
	}
}
