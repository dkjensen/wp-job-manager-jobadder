<?php
/**
 * Inbox to receive XML
 * 
 * @package wp-job-manager-jobadder
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class WPJMJA_Feed {

	private $params;

	private $updated_jobs = array();

    private $removed_jobs = array();
    
    public $log;

	public function __construct() {
        $this->log = new Logger( 'wp-job-manager-jobadder' );
        $this->log->pushHandler( new StreamHandler( WP_JOB_MANAGER_JOBADDER_LOG, Logger::DEBUG ) );

		$xml = file_get_contents( apply_filters( 'wpjmja_xml_feed', 'php://input' ) );

		if( empty( $xml ) ) {
			wp_die( __( '<p>The XML supplied was empty or invalid.</p>', 'wpjmja' ) );
		}

		$this->params = simplexml_load_string( $xml );

		$this->parse( $this->params );
	}


	public function parse( $params ) {
		if( ! empty( $params->Job ) ) {
			foreach( $params->Job as $job ) {
				$jid = (int) $job['jid'];

				// Add job ID to the array containing the job IDs of this current XML feed
				$this->updated_jobs[] = $jid;

				// Check if the job has already been imported, if so skip it
				if( $this->job_exists( $jid ) ) {
                    $this->log->info( sprintf( __( 'Job %d already exists, skipping...', 'wpjmja' ), (int) $job['jid'] ) );

                    continue;
                }

				// Since this job is not yet imported, lets add it
				$this->add_job( $job );
            }
            
            $this->update_jobs( $this->updated_jobs );

            // Flush WP Job Manager job listings cache
            WP_Job_Manager_Cache_Helper::get_transient_version( 'get_job_listings', true );
            
            exit;
		}else {
			wp_die( __( '<p>Could not find any jobs in the provided XML.</p>', 'wpjmja' ) );
		}
	}


	public function job_exists( $jid ) {
		$jobadder_jobs = get_option( '_jobadder_jobs' );

		if( ! empty( $jobadder_jobs ) ) {
			$jobs = array_map( 'intval', $jobadder_jobs );

			if( in_array( $jid, $jobs ) ) {
				return true;
			}
		}

		return false;
    }


	public function add_job( $job ) {
		$_post = wp_insert_post( apply_filters( 'wpjmja_insert_post', array(
			'post_title' 		=> (string) $job->Title,
			'post_excerpt' 		=> (string) $job->Summary,
			'post_content'		=> (string) $job->Description,
			'post_status'		=> 'publish',
			'post_type'			=> 'job_listing',
			'meta_input'		=> array(
				'_jid'			        => (int) $job['jid'],
                '_job_salary'           => (string) $job->Salary->MinValue . '&mdash;' . (string) $job->Salary->MaxValue,
                '_job_salary_period'    => (string) $job->Salary['period'],
				'_job_benefits'         => (string) $job->Salary->Text
			)
		) ) );

		if( is_wp_error( $_post ) ) {
            $this->log->error( $_post->get_error_message() );
		}else {
			$this->log->info( sprintf( __( 'Job %d added: Post ID #%d', 'wpjmja' ), (int) $job['jid'], intval( $_post ) ) );
		}
    }
    

    private function update_jobs( array $jobs ) {
        $old_jobs       = get_option( '_jobadder_jobs' );
        $removed_jobs   = array();

		foreach( (array) $old_jobs as $job ) {
			if( ! in_array( $job, $jobs ) ) {
				$removed_jobs[] = $job;
			}
        }

        // Remove jobs that no longer exist
		$this->remove_jobs( $removed_jobs );

        update_option( '_jobadder_jobs', $jobs );
    }


	private function remove_jobs( array $removed_jobs ) {
        global $wpdb;

        $removed = $wpdb->get_col( $wpdb->prepare( "
            SELECT post_ID 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_jid' 
            AND meta_value IN (%s)
        ", implode( ',', $removed_jobs ) ) );

		if( ! empty( $removed ) ) {
			foreach( $removed as $job ) {
				wp_trash_post( $job );

                $this->log->info( sprintf( __( 'Job deleted: Post ID #%s', 'wpjmja' ), $job ) );
			}
		}
	}

}

new WPJMJA_Feed;

exit;