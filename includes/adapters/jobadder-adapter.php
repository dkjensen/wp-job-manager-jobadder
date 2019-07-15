<?php
/**
 * JobAdder API adapter
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;

use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class JobAdder_Adapter implements Adapter_Interface {


    private $oauth;


    private $access_token;


    public function __construct( AbstractProvider $oauth ) {
        $this->oauth        = $oauth;
        $this->access_token = $oauth->get_access_token();
    }


    public function connected() {
        return $this->get_job_boards();
    }


    public function get_job_boards() {
        $job_boards = $this->request( 'GET', 'jobboards' );

        if ( ! is_wp_error( $job_boards ) ) {
            return $job_boards->items;
        }

        return false;
    }


    public function get_job_ads( $job_board ) {
        $job_ads = $this->request( 'GET', 'jobboards/' . $job_board . '/ads' );

        if ( ! is_wp_error( $job_ads ) ) {
            return $job_ads->items;
        }

        return false;
    }


    public function get_jobs() {
        $jobs = array();
        
        foreach ( $this->get_synced_job_boards() as $job_board ) {
            $job_ads = $this->get_job_ads( $job_board );

            if ( $job_ads ) {
                foreach ( $job_ads as $job_ad ) {
                    $jobs[] = $this->get_job( $job_ad->reference );
                }
            }
        }

        return $jobs;
    }

    /*
    public function post_job() {
        $job = $this->request( 'POST', 'jobs', array(
            'jobTitle'      => rand( 10000, 99999 )
        ) );

        if ( ! is_wp_error( $job ) ) {
            $ref = $job->jobId;
            $owner = $job->createdBy->userId;

            $job_ad = $this->request( 'POST', 'jobads', array(
                'title'     => rand( 10000, 99999 ),
                'reference' => $ref,
                'ownerUserId' => $owner
            ) );
        }
    }
    */


    public function get_synced_job_boards() {
        return array_filter( (array) get_option( 'jobadder_job_boards' ) );
    }
    
    
    public function sync_jobs() {
        $jobs = array();

        foreach ( $this->get_synced_job_boards() as $job_board ) {
            $job_ads = $this->get_job_ads( $job_board );

            if ( $job_ads ) {
                foreach ( $job_ads as $job_ad ) {
                    $job = $this->get_job( $job_ad->reference );

                    $jobs[] = array(
                        'post_title' 		=> isset( $job->jobTitle ) ? $job->jobTitle : __( 'Untitled job', 'wp-job-manager-jobadder' ),
                        'post_content' 		=> isset( $job->jobDescription ) ? $job->jobDescription : '',
                        'post_status'		=> 'publish',
                        'post_type'			=> 'job_listing',
                        'meta_input'		=> array(
                            '_jid'			        => $job->jobId,
                            '_jobadid'              => $job_ad->adId,
                            '_job_boardid'          => $job_board,
                            '_job_salary'           => isset( $job->salary ) ? job_manager_jobadder_format_salary( $job->salary ) : '',
                            '_job_salary_period'    => isset( $job->salary ) && isset( $job->salary->ratePer ) ? $job->salary->ratePer : '',
                            '_job_location'         => isset( $job->location ) && isset( $job->location->name ) ? $job->location->name : '',
                            '_application'          => get_option( 'admin_email' ),
                            '_company_name'         => isset( $job->company ) && isset( $job->company->name ) ? $job->company->name : '',
                            '_filled'               => isset( $job->status ) && isset( $job->status->active ) && $job->status->active ? 0 : 1
                        ),
                    );
                }
            }
        }

        return $jobs;
    }


    public function job_exists( $id ) {
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare( "
            SELECT post_id 
            FROM   $wpdb->postmeta 
            WHERE  meta_key = '_jid'
            AND    meta_value = '%s' 
            LIMIT  1", $id 
        ) );

        if ( ! $exists ) {
            return false;
        }

        return $exists;
    }


    public function get_job( $job ) {
        $job = $this->request( 'GET', 'jobs/' . $job );

        if ( ! is_wp_error( $job ) ) {
            return $job;
        }

        return false;
    }


    public function get_webhooks( $data ) {
        $data = wp_parse_args( $data, array(
            'status'                => '',
            'events'                => array()
        ) );

        extract( $data );

        $webhooks = $this->request( 'GET', 'webhooks', array(
            'events'            => (array) $events,
            'status'            => $status
        ) );

        if ( ! is_wp_error( $webhooks ) ) {
            return $webhooks->items;
        }

        return false;
    }


    public function post_webhook( $data ) {
        $data = wp_parse_args( $data, array(
            'name'                  => '',
            'status'                => '',
            'events'                => array()
        ) );

        extract( $data );

        $webhook = $this->request( 'POST', 'webhooks', array(
            'name'              => $name,
            'events'            => (array) $events,
            'url'               => add_query_arg( array( 'job_manager_webhook' => 'jobadder' ), home_url( '/', 'https' ) ),
            'status'            => $status
        ) );
    }


    public function delete_webhook( $webhook_id ) {
        $webhook = $this->request( 'DELETE', 'webhooks/' . $webhook_id );

        if ( ! is_wp_error( $webhook ) ) {
            return true;
        }

        return false;
    }


    public function post_job_application( $job_id, $fields ) {
        $job_board = absint( get_post_meta( $job_id, '_job_boardid', true ) );
        $job_ad = absint( get_post_meta( $job_id, '_jobadid', true ) );

        $application = $this->request( 'POST', 'jobboards/' . $job_board . '/ads/' . $job_ad . '/applications', (array) $fields );

        if ( ! is_wp_error( $application ) ) {
            return $application;
        }

        return false;
    }


    public function request( $method, $endpoint, $json = array() ) {
        $cache_key = md5( implode( '/', array( $method, $endpoint, json_encode( $json ) ) ) );

        if ( false === ( $cache = wp_cache_get( $cache_key ) ) ) {
            try {
                $response = wp_remote_request( 'https://api.jobadder.com/v2/' . $endpoint, array(
                    'headers'       => array( 
                        'Content-Type'      => 'application/json',
                        'Authorization'     => 'Bearer ' . $this->access_token
                    ),
                    'method'        => $method,
                    'data_format'   => 'body',
                    'body'          => ! empty( $json ) ? json_encode( $json ) : null
                ) );

                $body = json_decode( (string) wp_remote_retrieve_body( $response ) );
                $code = wp_remote_retrieve_response_code( $response );

                if ( substr( $code, 0, 1 ) != 2 ) {
                    throw new Exception( $body->message, $code, isset( $body->errors ) ? $body->errors : null );
                }

                wp_cache_set( $cache_key, $body );

                return $body;
            } catch( Exception $e ) {
                return new \WP_Error( 'job_manager_jobadder_request', esc_html( $e->getMessage() ), $e->getDetails() );
            }
        } else {
            return $cache;
        }
    }
}