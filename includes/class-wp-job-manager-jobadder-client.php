<?php
/**
 * JobAdder API client wrapper
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder_Client {


    /**
     * OAuth2 provider object
     *
     * @var League\OAuth2\Client\Provider\GenericProvider
     */
    protected $provider;


    /**
     * Guzzle client
     *
     * @var GuzzleHttp\Client
     */
    protected $client;


    /**
     * Client ID
     *
     * @var string
     */
    protected $client_id;


    /**
     * Client secret
     *
     * @var string
     */
    protected $client_secret;


    /**
     * Redirect URL
     *
     * @var string
     */
    public $redirect_uri;


    /**
     * Authorization endpoint
     *
     * @var string
     */
    public $url_authorize = 'https://id.jobadder.com/connect/authorize';


    /**
     * Access token retrieval endpoint
     *
     * @var string
     */
    public $url_access_token = 'https://id.jobadder.com/connect/token';


    /**
     * Setup properties
     *
     * @param string $client_id
     * @param string $client_secret
     */
    public function __construct( $client_id = '', $client_secret = '' ) {
        $this->client_id     = empty( $client_id ) ? (string) get_option( 'jobadder_client_id' ) : $client_id;
        $this->client_secret = empty( $client_secret ) ? (string) get_option( 'jobadder_client_secret' ) : $client_secret;
        $this->redirect_uri  = admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' );
        $this->client        = new GuzzleHttp\Client( array( 'base_uri' => 'https://api.jobadder.com/v2/' ) );;
    }


    /**
     * OAuth Provider object
     *
     * @return League\OAuth2\Client\Provider\GenericProvider
     */
    public function oauth() {
        return new \League\OAuth2\Client\Provider\GenericProvider( array(
            'clientId'                => $this->client_id,
            'clientSecret'            => $this->client_secret,
            'redirectUri'             => $this->redirect_uri,
            'urlAuthorize'            => $this->url_authorize,
            'urlAccessToken'          => $this->url_access_token,
            'urlResourceOwnerDetails' => ''
        ) );
    }

    
    public function authorize( $code ) {
        try {
            $token = $this->oauth()->getAccessToken( 'authorization_code', array( 'code' => $code ) );

            update_option( 'job_manager_jobadder_token', array( 
                'token'         => $token->getToken(),
                'expires'       => $token->getExpires(),
                'refresh_token' => $token->getRefreshToken()
            ) );

            return $token->getToken();

        } catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
            return new WP_error( 'job_manager_jobadder_authorization', $e->getMessage() );
        }
    }


    public function get_access_token() {
        $tokens = get_option( 'job_manager_jobadder_token' );

        if ( isset( $tokens['expires'] ) && time() > $tokens['expires'] ) {
            $token = $this->oauth()->getAccessToken( 'refresh_token', array( 'refresh_token' => $tokens['refresh_token'] ) );

            update_option( 'job_manager_jobadder_token', array( 
                'token'         => $token->getToken(),
                'expires'       => $token->getExpires(),
                'refresh_token' => $token->getRefreshToken()
            ) );

            return $token->getToken();
        }

        return isset( $tokens['token'] ) ? $tokens['token'] : '';
    }


    public function get_job_boards() {
        return $this->request( 'GET', 'jobboards' );
    }


    public function get_job_ads( $job_board ) {
        return $this->request( 'GET', 'jobboards/' . $job_board . '/ads' );
    }


    public function get_jobs() {
        return $this->request( 'GET', 'jobs' );
    }


    public function get_job( $job ) {
        return $this->request( 'GET', 'jobs/' . $job );
    }


    public function get_webhooks( $events, $status ) {
        return $this->request( 'GET', 'webhooks', array(
            'events'            => (array) $events,
            'status'            => $status
        ) );
    }


    public function post_webhook( $name, $events, $status ) {
        $this->request( 'POST', 'webhooks', array(
            'name'              => $name,
            'events'            => (array) $events,
            'url'               => 'https://webhooks.site',
            'status'            => $status
        ) );

        return $this->request( 'POST', 'webhooks', array(
            'name'              => $name,
            'events'            => (array) $events,
            'url'               => add_query_arg( array( 'job_manager_webhook' => 'jobadder' ), home_url( '/', 'https' ) ),
            'status'            => $status
        ) );
    }


    public function delete_webhook( $webhook_id ) {
        return $this->request( 'DELETE', 'webhooks/' . $webhook_id );
    }


    public function post_job_application( $job_board, $job_ad, $fields ) {
        return $this->request( 'POST', 'jobboards/' . $job_board . '/ads/' . $job_ad . '/applications', (array) $fields );
    }


    public function request( $method, $endpoint, $json = array() ) {
        try {
            /*
            $response = $this->client->request( $method, $endpoint, array(
                'headers' => array( 'Authorization'     => 'Bearer ' . $this->get_access_token() ),
                'json'    => apply_filters( 'job_manager_jobadder_request_json', $json )
            ) );

            $body = json_decode( (string) $response->getBody() );

            if ( substr( $response->getStatusCode(), 0, 1 ) != 2 ) {
                throw new Exception( __( 'JobAdder Message: ', 'wp-job-manager-jobadder' ) . $body->message . ' ' . (string) $response->getBody() );
            }
            */

            $response = wp_remote_request( 'https://api.jobadder.com/v2/' . $endpoint, array(
                'headers'   => array( 
                    'Content-Type'      => 'application/json',
                    'Authorization'     => 'Bearer ' . $this->get_access_token()
                ),
                'method'    => $method,
                'body'      => json_encode( $json )
            ) );

            $body = json_decode( (string) wp_remote_retrieve_body( $response ) );

            if ( substr( wp_remote_retrieve_response_code( $response ), 0, 1 ) != 2 ) {
                throw new WP_Job_Manager_JobAdder_Exception( $body->message, $body->errors );
            }

            return $body;
        } catch( WP_Job_Manager_JobAdder_Exception $e ) {
            return new WP_Error( 'job_manager_jobadder_request', esc_html( $e->getMessage() ), $e->getDetails() );
        }
    }

    
}