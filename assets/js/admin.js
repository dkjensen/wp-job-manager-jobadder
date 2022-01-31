( function( $ ) {

    jQuery( '.wp-job-manager-applications-form-editor' ).on( 'init', function( e ) {
        for ( var i = 0; i < job_manager_jobadder.application_clients.length; i++ ) {
            var client_name_slug = job_manager_jobadder.application_clients[ i ];
            var client_name      = client_name_slug[0].toUpperCase() + client_name_slug.substring(1);

            var application_fields = jQuery( '<select name="field_"' + client_name_slug + '"><option value="">N/A</option></select>' );

            jQuery.each( job_manager_jobadder.application_client_fields[ client_name_slug ], function( i, n ) {
                application_fields.append( '<option value="' + i + '">' + n + '</option' );
            } );

            jQuery( 'tbody#form-fields' ).siblings( 'thead' )
                .find( 'th:nth-child(4)' )
                .after( '<th>' + client_name + ' ' + job_manager_jobadder.application_form_column_jobadder_label + '</th>' );

            
                jQuery( this ).find( 'tbody tr' ).each( function( i, n ) {
                    if( jQuery( this ).find( '[name^="field_' + client_name_slug + '"]' ).length ) {
                        return true;
                    }
            
                    var i = jQuery( this ).find( '[name^="field_label"]' ).attr( 'name' ).match( /\[(.*?)\]/ )[ 1 ];
            
                    var jobadder_field = application_fields.clone();
                    jobadder_field.attr( 'name', 'field_' + client_name_slug + '[' + i + ']' );
            
                    jQuery.each( job_manager_jobadder.application_form_fields, function( a, b ) {
                        if( b.priority == i && typeof b[ client_name_slug ] !== 'undefined' && b[ client_name_slug ].length ) {
                            jobadder_field.find( '[value="' + b[ client_name_slug ] + '"]' ).attr( 'selected', 'selected' );
                        }
                    } );
            
                    jQuery( this ).find( 'td:nth-child(4)' ).after( '<td>' + jobadder_field.wrapAll( '<div/>' ).parent().html() + '</td>' );
                } );
            
        }
    } );
    

} )( jQuery );
