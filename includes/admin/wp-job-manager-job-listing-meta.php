<?php

function wpjmja_job_listing_meta( $fields ) {
	$fields['_jid'] = array(
		'label'       => __( 'JobAdder Job ID', 'wpjmja' ),
		'type'        => 'text',
		'placeholder' => '',
		'priority'    => 1
	);

	if( ! array_key_exists( '_job_salary', $fields ) ) {

	$fields['_job_salary'] = array(
		'label'       => __( 'Job Salary', 'wpjmja' ),
		'type'        => 'text',
		'placeholder' => '',
		'priority'    => 13
	);

	}

	if( ! array_key_exists( '_job_benefits', $fields ) ) {

	$fields['_job_benefits'] = array(
		'label'       => __( 'Job Benefits', 'wpjmja' ),
		'type'        => 'textarea',
		'placeholder' => '',
		'priority'    => 14
	);

	}

	return $fields;
}
add_filter( 'job_manager_job_listing_data_fields', 'wpjmja_job_listing_meta' );