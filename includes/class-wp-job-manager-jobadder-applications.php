<?php
/**
 * Applications handling
 * 
 * @package WP Job Manager - JobAdder Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WP_Job_Manager_JobAdder_Applications {

    public function __construct() {
        add_action( 'new_job_application', array( $this, 'apply' ), 10, 2 );

        add_filter( 'pre_update_option', array( $this, 'save_application_form_fields' ), 10, 3 );
        add_action( 'delete_option_job_application_form_fields', array( $this, 'reload_application_form' ) );
    }


    public function apply( $application_id, $job_id ) {
        $apply  = new WP_Job_Manager_Applications_Apply;
        $fields = array();

        /**
         * Lets try and get the real job ID because the second parameter here does not work consistently
         */
        $application = get_post( $application_id );
        
        if ( $application ) {
            $job = get_post( $application->post_parent );

            if ( $job->post_type == 'job_listing' ) {
                $job_id = $job->ID;
            }
        }

        if ( $job_id ) {
            foreach ( $apply->get_fields() as $key => $field ) {
                if ( empty( $field['jobadder'] ) ) {
                    continue;
                }

                $object = explode( ':', $field['jobadder'], 2 );

                if ( sizeof( $object ) > 1 ) {
                    $fields[ $object[0] ][ $object[1] ] = $field['value'];
                } else {
                    $fields[ $field['jobadder'] ] = $field['value']; 
                }
            }

            if ( ! empty( $fields ) ) {
                /**
                 * @var integer $job_board
                 * @var integer $job_ad
                 * @var array   $fields
                 */
                $posted = WP_Job_Manager_JobAdder()->client->post_job_application( 
                    absint( get_post_meta( $job_id, '_job_boardid', true ) ),
                    absint( get_post_meta( $job_id, '_jobadid', true ) ),
                    $fields
                );

                if ( is_wp_error( $posted ) ) {
                    WP_Job_Manager_JobAdder()->log->error( $posted->get_error_message() );
                }
            }
        }
    }


    public function save_application_form_fields( $value, $option, $old_value ) {
        if ( $option == 'job_application_form_fields' && isset( $_POST ) && isset( $_POST['field_jobadder'] ) ) {
            $field_labels = ! empty( $_POST['field_label'] ) ? array_map( 'wp_kses_post', $_POST['field_label'] ) : array();

            $new_value = array();

            $i = 0;
            foreach ( $field_labels as $key => $field ) {
                if ( empty( $field_labels[ $key ] ) ) {
                    continue;
                }

                $field_name = sanitize_title( $field_labels[ $key ] );

                if ( isset( $new_value[ $field_name ] ) ) {
                    // Generate a unique field name by appending a number to the existing field name.
                    // Assumes no more than 100 fields with the same name would be needed? Otherwise it will override the field.
                    $counter = 1;
                    while ( $counter <= 100 ) {
                        $candidate = $field_name . '-' . $counter;
                        if ( ! isset( $new_value[ $candidate ] ) ) {
                            $field_name = $candidate;
                            break;
                        }
                        $counter++;
                    }
                }

                $new_value[ $field_name ] = $value[ $field_name ];

                $new_value[ $field_name ]['jobadder'] = isset( $_POST['field_jobadder'] ) && isset( $_POST['field_jobadder'][ $i ] ) ? $_POST['field_jobadder'][ $i ] : '';
            
                $i++;
            }

            wp_redirect( admin_url( 'edit.php?post_type=job_application&page=job-applications-form-editor&tab=fields' ) );

            return $new_value;
        }

        return $value;
    }


    /**
     * After resetting application form to defaults, reload the page again
     * due to the localized JS not being updated yet
     *
     * @return void
     */
    public function reload_application_form() {
        wp_redirect( admin_url( 'edit.php?post_type=job_application&page=job-applications-form-editor&tab=fields' ) );
        exit;
    }

}