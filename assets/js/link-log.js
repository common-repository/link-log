jQuery(document).ready(function($) {
  
  $( '.pp-link-log-admin-notice' ).on( 'click', '.notice-dismiss', function ( event ) {
    event.preventDefault();
		data = {
			action: 'pp_linklog_dismiss_admin_notice',
			pp_linklog_dismiss_admin_notice: $( this ).parent().attr( 'id' )
		};
		$.post( ajaxurl, data );
		return false;
	});
 
});