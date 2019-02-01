<?php
/**
 * Inbox to receive XML
 * 
 * @package wp-job-manager-jobadder
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class WPJMJA_Feed {

	private $updated_jobs = array();

    private $removed_jobs = array();
    
    public $log;

	public function __construct() {
        $this->log = new Logger( 'wp-job-manager-jobadder' );
        $this->log->pushHandler( new StreamHandler( WP_JOB_MANAGER_JOBADDER_LOG, Logger::DEBUG ) );

        if( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
            wp_die( __( '<p>Invalid requeset method.', 'wpjmja' ) );
        }

		$xml = file_get_contents( apply_filters( 'wpjmja_xml_feed', 'php://input' ) );
		$xml = simplexml_load_string( $xml );

        if( empty( $xml ) ) {
			wp_die( __( 'The XML supplied was empty or invalid.', 'wpjmja' ) );
		}

        if( ! $this->is_authorized() ) {
            $this->log->error( sprintf( __( 'Unauthorized attempt: %s', 'wpjmja' ), $_SERVER['REMOTE_ADDR'] ) );

            wp_die( __( 'Invalid authorization.', 'wpjmja' ) );
        }

        $this->log->info( __( 'Starting XML parsing...' ) );
		$this->parse( $xml );
    }
    

    private function is_authorized() {
        $authorized = false;

        $authorized_ips = apply_filters( 'wpjmja_authorized_ips', array( 
            '13.55.194.240',
            '13.54.40.134',
            '13.210.83.204'
        ) );

        if( in_array( $_SERVER['REMOTE_ADDR'], $authorized_ips ) ) {
            $authorized = true;
        }

        return apply_filters( 'wpjmja_request_authorized', $authorized );
    }


	public function parse( $params ) {
		if( ! empty( $params->Job ) ) {
			foreach( $params->Job as $job ) {
				$jid = (int) $job['jid'];

				// Add job ID to the array containing the job IDs of this current XML feed
				$this->updated_jobs[] = $jid;

				// Check if the job has already been imported, if so skip it
				if( $this->job_exists( $jid ) ) {
                    $this->log->info( sprintf( __( 'Job %d already exists, updating...', 'wpjmja' ), (int) $job['jid'] ) );
                }

				// Since this job is not yet imported, lets add it
				$this->add_job( $job );
            }
            
            $this->update_jobs( $this->updated_jobs );

            // Flush WP Job Manager job listings cache
            WP_Job_Manager_Cache_Helper::get_transient_version( 'get_job_listings', true );
            
            exit;
		}else {
            $this->log->info( __( 'No jobs present in given XML' ) );

			wp_die( __( 'Could not find any jobs in the provided XML', 'wpjmja' ) );
		}
	}


	public function job_exists( $jid ) {
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_jid' and meta_value = '%s' LIMIT 1", $jid ) );

        if( null === $exists ) {
            return false;
        }

        return $exists;
    }


	public function add_job( $job ) {
        global $wpdb;

        $category =
        $location =
        $featured_image = 
        $salary = 
        $work_type = '';

        foreach( $job->Classifications->children() as $classification ) {
            switch( $classification['name'] ) {
                case 'Category' :
                    $category = (string) $classification;
                    break;
                case 'Featured Image' :
                    $featured_image = (string) $classification;
                    break;
                case 'Work Type' :
                    $work_type = (string) $classification;
                    break;
                case 'Location' :
                    $location = (string) $classification;
                    break;
            }
        }

        foreach( $job->Fields->children() as $field ) {
            if( (string) strtolower( $field['name'] ) == 'salary' ) {
                $salary = (string) $field;
                break;
            }
        }

        $email_to = (string) $job->Apply->EmailTo;

        $ejob = $this->job_exists( (int) $job['jid'] );

        $job_post = apply_filters( 'wpjmja_insert_post', array(
			'post_title' 		=> (string) $job->Title,
			'post_excerpt' 		=> (string) $job->Summary,
			'post_content'		=> (string) $job->Description,
			'post_status'		=> 'publish',
			'post_type'			=> 'job_listing',
			'meta_input'		=> array(
				'_jid'			        => (int) $job['jid'],
                '_job_salary'           => ! empty( $salary ) ? $salary : (string) $job->Salary->MinValue . '&mdash;' . (string) $job->Salary->MaxValue,
                '_job_salary_period'    => (string) $job->Salary['period'],
                '_job_benefits'         => (string) $job->Salary->Text,
                '_job_location'         => $location,
                '_job_image'            => $featured_image,
                '_application'          => is_email( $email_to ) ? $email_to : get_option( 'admin_email' )
            ),
        ) );

        if( $ejob ) {
            $job_post['ID'] = $ejob;

            $job_id = wp_update_post( $job_post );
        }else {
            $job_id = wp_insert_post( $job_post );
        }

        if( ! empty( $work_type ) ) {
            switch( $work_type ) {
                case 'Permanent / Full Time' :
                    $work_type = 'Full Time';
                    break;
                case 'Contract or Temp' :
                    $work_type = 'Temporary';
                    break;
            }

            $work_type_term = get_term_by( 'name', $work_type, 'job_listing_type' );

            if( ! $work_type_term ) {
                $work_type_term = wp_insert_term( $work_type, 'job_listing_type' );
                $work_type_term = $work_type_term['term_id'];
            }else {
                $work_type_term = $work_type_term->term_id;
            }

            wp_set_post_terms( $job_id, (int) $work_type_term, 'job_listing_type' );
        }

        do_action( 'wpjmja_job_added', $job_id, $job );

		if( is_wp_error( $job_id ) ) {
            $this->log->error( $job_id->get_error_message() );
		}else {
			$this->log->info( sprintf( __( 'Job %d added: Post ID #%d', 'wpjmja' ), (int) $job['jid'], intval( $job_id ) ) );
		}
    }


    private function update_jobs( array $jobs ) {
        update_option( '_jobadder_jobs', $jobs );

        // Remove jobs that no longer exist
		$this->remove_jobs();
    }


	private function remove_jobs() {
        global $wpdb;

        $current_jobs = get_option( '_jobadder_jobs' );

        $jobs_list = implode( "','", $current_jobs );

        $removed = $wpdb->get_col( "
            SELECT post_id 
            FROM $wpdb->postmeta 
            WHERE meta_key = '_jid' 
            AND meta_value NOT IN ('{$jobs_list}')
        " );

		if( ! empty( $removed ) ) {
			foreach( $removed as $job ) {
				wp_trash_post( $job );

                $this->log->info( sprintf( __( 'Job trashed: Post ID #%s', 'wpjmja' ), $job ) );
			}
		}
	}

}

new WPJMJA_Feed;

exit;