<?php
/**
 * Admin settings
 * 
 * @package WP Job Manager - JobAdder Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\JobAdder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Settings {

    /**
     * Are we connected to the JobAdder API?
     * 
     * @var boolean
     */
    public $connected = false;


    public function __construct() {
        add_action( 'admin_init', array( $this, 'init_settings' ) );

        add_filter( 'job_manager_settings', array( $this, 'settings' ) );

        // Authorization field callback
        add_action( 'wp_job_manager_admin_field_jobadder_setup', array( $this, 'setup_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_jobadder_authorization', array( $this, 'jobadder_authorization_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_jobadder_job_boards', array( $this, 'job_boards_field_callback' ), 10, 4 );

        add_action( 'job_manager_jobadder_settings', array( $this, 'jobadder_authorization' ) );
        add_action( 'job_manager_jobadder_settings', array( $this, 'jobadder_deauthorization' ) );
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
        $settings = (array) $settings;
        $settings['jobadder'] = array(
            __( 'JobAdder', 'wp-job-manager-jobadder' ),
            array(
                array(
                    'name'          => 'jobadder_client_id',
                    'label'         => __( 'JobAdder Client ID', 'wp-job-manager-jobadder' ),
                    'type'          => 'text',
                ),
                array(
                    'name'          => 'jobadder_client_secret',
                    'label'         => __( 'JobAdder Client Secret', 'wp-job-manager-jobadder' ),
                    'type'          => 'password',
                ),
                array(
                    'name'          => 'jobadder_authorization',
                    'label'         => __( 'JobAdder Authorization', 'wp-job-manager-jobadder' ),
                    'type'          => 'jobadder_authorization',
                ),
                array(
                    'name'    => 'jobadder_job_boards',
                    'label'   => __( 'Job Boards To Sync', 'wp-job-manager-jobadder' ),
                    'type'    => 'jobadder_job_boards',
                ),
                array(
                    'name'          => 'jobadder_applications',
                    'label'         => __( 'Post Applications to JobAdder', 'wp-job-manager-jobadder' ),
                    'type'          => 'checkbox',
                    'cb_label'      => __( 'Job applications submitted via the WP Job Manager - Applications plugin will be sent to JobAdder', 'wp-job-manager-jobadder' )
                )
            ),
            array(
                'before' => sprintf( __( '<a href="%1$s" target="_blank">Register your JobAdder Developers application</a> using the following value as an authorized redirect URI: <code>%2$s</code>', 'wp-job-manager-jobadder' ), 'https://developers.jobadder.com/partners/clients/add', admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) ),
                'after' => sprintf( '<a href="%s">%s</a>', wp_nonce_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings&sync=jobadder' ) ), __( 'Sync now', 'wp-job-manager-jobadder' ) )
            ),
        );

        return $settings;
    }
 

    public function jobadder_authorization_field_callback( $option, $attributes, $value, $placeholder ) {
        if ( $this->connected ) :
        ?>

        <p>
            <a href="<?php print wp_nonce_url( add_query_arg( array( 'state' => 'jobadder-deauthorization' ), admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) ) ); ?>" class="button button-error"><?php _e( 'Disconnect', 'wp-job-manager-jobadder' ); ?></a>
        </p>

        <?php else :
            $authorization_url = WP_Job_Manager_JobAdder()->oauth->getAuthorizationUrl();

            update_option( 'jobadder_oauth_state', WP_Job_Manager_JobAdder()->oauth->getState() );
            ?>

        <p>
            <a href="<?php print esc_url( $authorization_url ); ?>" class="button button-primary">
                <?php _e( 'Connect with JobAdder', 'wp-job-manager-jobadder' ); ?>
            </a>
        </p>

        <?php 
        endif;
    }


    public function job_boards_field_callback( $option, $attributes, $value, $placeholder ) {
        if ( ! $this->connected ) {
            return;
        }
        ?>

        <fieldset>
            <legend class="screen-reader-text"><span><?php _e( 'Job Boards To Sync', 'wp-job-manager-jobadder' ); ?></span></legend>

        <?php foreach ( WP_Job_Manager_JobAdder()->clients['jobadder']->adapter()->get_job_boards() as $job_board ) : ?>
            
            <label>
                <input name="jobadder_job_boards[]"  type="checkbox" value="<?php print esc_attr( $job_board->boardId ); ?>" <?php checked( true, in_array( $job_board->boardId, WP_Job_Manager_JobAdder()->clients['jobadder']->adapter()->get_synced_job_boards() ) ); ?>>
                <?php print esc_html_e( $job_board->name, 'wp-job-manager-jobadder' ); ?>
            </label><br>

        <?php endforeach; ?>

        </fieldset>

        <?php
    }


    public function init_settings() {
        if ( current_user_can( 'manage_options' ) && is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'job-manager-settings' ) {
                $this->connected = WP_Job_Manager_JobAdder()->clients['jobadder']->connected();

                if ( ! $this->connected ) {
                }

                do_action( 'job_manager_jobadder_settings' );
            }
        }

        return false;
    }


    public function jobadder_authorization() {
        if ( isset( $_GET['state'] ) && $_GET['state'] == get_option( 'jobadder_oauth_state' ) && isset( $_GET['code'] ) && current_user_can( 'manage_options' ) ) {
            $authorization = WP_Job_Manager_JobAdder()->oauth->get_access_token( $_GET['code'] );

            if ( ! is_wp_error( $authorization ) ) {
                schedule_sync();

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


    public function jobadder_sync_jobs() {
        if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'jobadder' && wp_verify_nonce( $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) ) {
            do_action( 'job_manager_jobadder_sync_jobs' );
        }
    }


    public function jobadder_deauthorization() {
        if ( isset( $_GET['state'] ) && $_GET['state'] == 'jobadder-deauthorization' && wp_verify_nonce( $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) ) {
            delete_option( 'jobadder_client_id' );
            delete_option( 'jobadder_client_secret' );
            delete_option( 'job_manager_jobadder_token' );
            delete_option( 'jobadder_job_boards' );

            wp_cache_flush();

            wp_redirect( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings' ) );
            exit;
        }
    }


    public function scripts() {
        wp_enqueue_style( 'wp-job-manager-jobadder-admin', WP_JOB_MANAGER_JOBADDER_PLUGIN_URL . '/assets/css/admin.min.css', array(), WP_JOB_MANAGER_JOBADDER_VER );

        wp_register_script( 'wp-job-manager-jobadder-admin', WP_JOB_MANAGER_JOBADDER_PLUGIN_URL . '/assets/js/admin.min.js', array( 'jquery' ), WP_JOB_MANAGER_JOBADDER_VER, true );

        wp_localize_script( 'wp-job-manager-jobadder-admin', 'job_manager_jobadder', array(
            'application_form_column_jobadder_label'    => __( 'Field', 'wp-job-manager-jobadder' ),
            'application_form_fields'                   => get_option( 'job_application_form_fields' ),
            'application_clients'                       => array_keys( WP_Job_Manager_JobAdder()->clients ),
            'application_client_fields'                 => array(
                'jobadder'  => array(
                    'firstName'             => __( 'First name', 'wp-job-manager-jobadder' ),
                    'lastName'              => __( 'Last name', 'wp-job-manager-jobadder' ),
                    'salutation'            => __( 'Salutation', 'wp-job-manager-jobadder' ),
                    'email'                 => __( 'Email', 'wp-job-manager-jobadder' ),
                    'phone'                 => __( 'Phone', 'wp-job-manager-jobadder' ),
                    'mobile'                => __( 'Mobile', 'wp-job-manager-jobadder' ),
                    'address:street[]'      => __( 'Street address', 'wp-job-manager-jobadder' ),
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
                    'Resume'                => __( 'Resume', 'wp-job-manager-jobadder' ),
                    'CoverLetter'           => __( 'Cover letter (file)', 'wp-job-manager-jobadder' ),
                    'screening'             => __( 'Screening (file)', 'wp-job-manager-jobadder' ),
                    'check'                 => __( 'Check (file)', 'wp-job-manager-jobadder' ),
                    'reference'             => __( 'References (file)', 'wp-job-manager-jobadder' ),
                    'license'               => __( 'License (file)', 'wp-job-manager-jobadder' ),
                    'other'                 => __( 'Other (file)', 'wp-job-manager-jobadder' )
                )
            )
        ) );

        wp_enqueue_script( 'wp-job-manager-jobadder-admin' );
    }

}

return new Settings;
