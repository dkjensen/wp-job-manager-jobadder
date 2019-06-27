<?php
/**
 * Admin settings
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder_Settings {

    /**
     * Grabs job boards / tests API connection
     *
     * @var mixed array|WP_Error
     */
    private $job_boards;


    public function __construct() {
        add_filter( 'job_manager_settings', array( $this, 'settings' ) );

        // Authorization field callback
        add_action( 'wp_job_manager_admin_field_jobadder_setup', array( $this, 'setup_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_jobadder_authorization', array( $this, 'authorization_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_jobadder_job_boards', array( $this, 'job_boards_field_callback' ), 10, 4 );

        add_action( 'admin_init', array( $this, 'jobadder_authorization' ) );
        add_action( 'admin_init', array( $this, 'jobadder_sync_jobs' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    }


    /**
     * WP Job Manager settings
     *
     * @param array $settings
     * @return array
     */
    public function settings( $settings ) {
        $settings['jobadder'] = array(
            __( 'JobAdder', 'wp-job-manager-jobadder' ),
            array(
                array(
                    'name'      => 'jobadder_setup',
                    'label'     => __( 'Instructions', 'wp-job-manager-jobadder' ),
                    'type'      => 'jobadder_setup',
                ),
                array(
                    'name'      => 'jobadder_client_id',
                    'label'     => __( 'JobAdder Client ID', 'wp-job-manager-jobadder' ),
                    'type'      => 'text',
                ),
                array(
                    'name'      => 'jobadder_client_secret',
                    'label'     => __( 'JobAdder Client Secret', 'wp-job-manager-jobadder' ),
                    'type'      => 'password',
                ),
                array(
                    'name'      => 'jobadder_authorization',
                    'label'     => __( 'JobAdder Authorization', 'wp-job-manager-jobadder' ),
                    'type'      => 'jobadder_authorization',
                ),
                array(
                    'name'      => 'jobadder_applications',
                    'label'     => __( 'Post Applications to JobAdder', 'wp-job-manager-jobadder' ),
                    'type'      => 'checkbox',
                    'cb_label'  => __( 'Job applications submitted via the WP Job Manager - Applications plugin will be sent to JobAdder', 'wp-job-manager-jobadder' )
                )
            ),
        );

        if ( $this->get_job_boards() ) {
            $settings['jobadder'][1][] = array(
                'name'    => 'jobadder_job_boards',
                'label'   => __( 'Job Boards To Sync', 'wp-job-manager-jobadder' ),
                'type'    => 'jobadder_job_boards',
            );

            // Get enabled webhooks
            WP_Job_Manager_JobAdder()->webhooks->get_enabled_events();

            // Check if duplicate webhooks and if so reset them
            WP_Job_Manager_JobAdder()->webhooks->reset();
        }

        return $settings;
    }
    

    private function get_job_boards() {
        if ( ! $this->job_boards ) {
            $job_boards = WP_Job_Manager_JobAdder()->client->get_job_boards();

            if ( ! is_wp_error( $job_boards ) ) {
                $this->job_boards = $job_boards->items;
            }
        }

        return $this->job_boards;
    }


    public function setup_field_callback( $option, $attributes, $value, $placeholder ) {
        ?>

        <p><?php printf( __( 'Login to your JobAdder developer account <a href="%s" target="_blank">here</a>. If you do not yet have a developer account, you may <a href="%s" target="_blank">register here</a>.', 'wp-job-manager-jobadder' ), 'https://developers.jobadder.com/signin', 'https://developers.jobadder.com/register' ); ?></p>
        <p><?php printf( __( 'Once logged in, register a new application. In the Authorized Redirect URIs field, use this address: <code>%s</code>', 'wp-job-manager-jobadder' ), admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) ); ?></p>
        <p><?php printf( __( 'After your application has been registered, use the given Client ID and Secret in the fields below. You may then connect the site with JobAdder.', 'wp-job-manager-jobadder' ) ); ?></p>

        <?php
    }


    public function authorization_field_callback( $option, $attributes, $value, $placeholder ) {

        $authorization_url = add_query_arg( apply_filters( 'job_manager_jobadder_authorization_params', array(
            'response_type'         => 'code',
            'client_id'             => get_option( 'jobadder_client_id' ),
            'scope'                 => urlencode( implode( ' ', array(
                'read', 
                'write', 
                'read_jobad', 
                'write_jobapplication',
                'offline_access' // to get refresh token
            ) ) ),
            'state'                 => 'jobadder-authorization',
            'redirect_uri'          => urlencode( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) ),
        ) ), 'https://id.jobadder.com/connect/authorize' );

        ?>

        <p>
            <a href="<?php print esc_url( $authorization_url ); ?>" class="button-primary">
                <?php

                if ( get_option( 'jobadder_client_id' ) && get_option( 'jobadder_client_secret' ) && $this->get_job_boards() ) {
                    _e( 'Reconnect with JobAdder', 'wp-job-manager-jobadder' );
                } else {
                    _e( 'Connect with JobAdder', 'wp-job-manager-jobadder' );
                }

                ?>
            </a>
        </p>

        <p>
            <?php _e( 'Status', 'wp-job-manager-jobadder' ); ?>: 

            <?php 

            if ( $this->get_job_boards() ) {
                printf( '<span style="color: green;">%s</span>', __( 'Connected', 'wp-job-manager-jobadder' ) );
            } else {
                printf( '<span style="color: red;">%s</span>', __( 'Not connected', 'wp-job-manager-jobadder' ) );
            }

            ?>
        </p>

        <?php
    }


    public function job_boards_field_callback( $option, $attributes, $value, $placeholder ) {
        WP_Job_Manager_JobAdder()->webhooks->reset();

        ?>

        <fieldset>
            <legend class="screen-reader-text"><span><?php _e( 'Job Boards To Sync', 'wp-job-manager-jobadder' ); ?></span></legend>

        <?php foreach( $this->get_job_boards() as $job_board ) : ?>
            
            <label>
                <input name="jobadder_job_boards[]"  type="checkbox" value="<?php print esc_attr( $job_board->boardId ); ?>" <?php checked( true, in_array( $job_board->boardId, job_manager_jobadder_get_synced_job_boards() ) ); ?>>
                <?php print esc_html_e( $job_board->name, 'wp-job-manager-jobadder' ); ?>
            </label><br>

        <?php endforeach; ?>

        </fieldset>

        <p><a href="<?php print wp_nonce_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings&sync=true' ) ); ?>" class="button-secondary"><?php _e( 'Sync all job ads', 'wp-job-manager-jobadder' ); ?></a></p>

        <?php
    }


    public function jobadder_authorization() {
        if ( current_user_can( 'manage_options' ) && is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'job-manager-settings' && isset( $_GET['state'] ) && $_GET['state'] == 'jobadder-authorization' && isset( $_GET['code'] ) ) {
                $authorization = WP_Job_Manager_JobAdder()->client->authorize( $_GET['code'] );

                if ( ! is_wp_error( $authorization ) ) {
                    // Enable webhooks
                    WP_Job_Manager_JobAdder()->webhooks->setup();

                    wp_redirect( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings&connected=true#settings-jobadder' ) );
                    exit;
                } else {
                    add_action( 'admin_notices', function() use ( $authorization ) {
                        ?>
    
                        <div class="notice notice-error is-dismissible">
                            <p><?php esc_html_e( $authorization->get_error_message(), 'wp-job-manager-jobadder' ); ?></p>
                        </div>
    
                        <?php
                    } );
                }
            }
        }
    }


    public function jobadder_sync_jobs() {
        if ( current_user_can( 'manage_options' ) && is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'job-manager-settings' && isset( $_GET['sync'] ) && $_GET['sync'] == 'true' && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
                $job_boards = job_manager_jobadder_get_synced_job_boards();

                foreach ( $job_boards as $job_board ) {
                    $job_ads = WP_Job_Manager_JobAdder()->client->get_job_ads( $job_board );

                    if ( ! is_wp_error( $job_ads ) ) {
                        foreach ( $job_ads->items as $job_ad ) {
                            $job = WP_Job_Manager_JobAdder()->client->get_job( $job_ad->reference );

                            WP_Job_Manager_JobAdder()->jobs->update_job( $job, $job_ad, $job_board );
                        }
                    }
                }
            }
        }
    }


    public function scripts() {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_script( 'wp-job-manager-jobadder-admin', WP_JOB_MANAGER_JOBADDER_PLUGIN_URL . '/assets/js/admin' . $suffix . '.js', array( 'jquery' ), WP_JOB_MANAGER_JOBADDER_VER, true );

        wp_localize_script( 'wp-job-manager-jobadder-admin', 'job_manager_jobadder', array(
            'application_form_column_jobadder_label'    => __( 'JobAdder Field', 'wp-job-manager-jobadder' ),
            'application_form_fields'                   => get_option( 'job_application_form_fields' ),
            'application_fields'                        => array(
                'firstName'             => __( 'First name', 'wp-job-manager-jobadder' ),
                'lastName'              => __( 'Last name', 'wp-job-manager-jobadder' ),
                'salutation'            => __( 'Salutation', 'wp-job-manager-jobadder' ),
                'email'                 => __( 'Email', 'wp-job-manager-jobadder' ),
                'phone'                 => __( 'Phone', 'wp-job-manager-jobadder' ),
                'mobile'                => __( 'Mobile', 'wp-job-manager-jobadder' ),
                'address:street'        => __( 'Street address', 'wp-job-manager-jobadder' ),
                'address:city'          => __( 'City', 'wp-job-manager-jobadder' ),
                'address:state'         => __( 'State', 'wp-job-manager-jobadder' ),
                'address:postalCode'    => __( 'Postal code', 'wp-job-manager-jobadder' ),
                'address:countryCode'   => __( 'Country code', 'wp-job-manager-jobadder' ),
                'social:facebook'       => __( 'Facebook', 'wp-job-manager-jobadder' ),
                'social:twitter'        => __( 'Twitter', 'wp-job-manager-jobadder' ),
                'social:linkedin'       => __( 'LinkedIn', 'wp-job-manager-jobadder' ),
                'social:googleplus'     => __( 'Google Plus', 'wp-job-manager-jobadder' ),
                'social:youtube'        => __( 'YouTube', 'wp-job-manager-jobadder' ),
                'social:other'          => __( 'Other social', 'wp-job-manager-jobadder' ),
                'availability:date'     => __( 'Availability date', 'wp-job-manager-jobadder' ),
            ),
            'file_fields'                               => array(
                'Resume'                => __( 'Resume', 'wp-job-manager-jobadder' ),
                'CoverLetter'           => __( 'Cover letter (file)', 'wp-job-manager-jobadder' ),
                'screening'             => __( 'Screening (file)', 'wp-job-manager-jobadder' ),
                'check'                 => __( 'Check (file)', 'wp-job-manager-jobadder' ),
                'reference'             => __( 'References (file)', 'wp-job-manager-jobadder' ),
                'license'               => __( 'License (file)', 'wp-job-manager-jobadder' ),
                'other'                 => __( 'Other (file)', 'wp-job-manager-jobadder' )
            )
        ) );

        wp_enqueue_script( 'wp-job-manager-jobadder-admin' );
    }

}

return new WP_Job_Manager_JobAdder_Settings;