( function( $ ) {

    var application_fields = jQuery( '<select name="field_jobadder"><option value="">N/A</option></select>' );

    jQuery.each( job_manager_jobadder.application_fields, function( i, n ) {
        application_fields.append( '<option value="' + i + '">' + n + '</option' );
    } );

    function new_field( i, n ) {
        if( jQuery( this ).find( '[name^="field_jobadder"]' ).length ) {
            return true;
        }

        var i = jQuery( this ).find( '[name^="field_label"]' ).attr( 'name' ).match( /\[(.*?)\]/ )[ 1 ];

        jobadder_field = application_fields.clone();
        jobadder_field.attr( 'name', 'field_jobadder[' + i + ']' );

        jQuery.each( job_manager_jobadder.application_form_fields, function( a, b ) {
            if( b.priority == i && typeof b.jobadder !== 'undefined' && b.jobadder.length ) {
                jobadder_field.find( '[value="' + b.jobadder + '"]' ).attr( 'selected', 'selected' );
            }
        } );

        jQuery( this ).find( 'td:nth-child(4)' ).after( '<td>' + jobadder_field.wrapAll( '<div/>' ).parent().html() + '</td>' );
    }

    jQuery( 'tbody#form-fields' ).siblings( 'thead' )
                                 .find( 'th:nth-child(4)' )
                                 .after( '<th>' + job_manager_jobadder.application_form_column_jobadder_label + '</th>' );

    jQuery( '.wp-job-manager-applications-form-editor' ).on( 'init', function( e ) {
        jQuery( this ).find( 'tbody tr' ).each( new_field );
    } );

} )( jQuery );