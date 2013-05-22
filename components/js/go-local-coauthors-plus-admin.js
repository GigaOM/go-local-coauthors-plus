if ( typeof go_local_coauthors_plus_admin == 'undefined' ) {
	// a wp_localize_script should always define this...but let's
	// cover our bases.
	go_local_coauthors_plus_admin = {
		authors: []
	};
}//end if

( function( $ ) {
	/**
	 * initialization of the plugin
	 */
	go_local_coauthors_plus_admin.init = function() {
		go_local_coauthors_plus_admin.$input = $('input[name^="coauthorsinput"]');

		// hijack the coauthors_ajax_suggest request and, instead, force the search through
		// our search method
		$.mockjax({
			url: 'admin-ajax.php?*&action=coauthors_ajax_suggest',
			logging: false,
			response: go_local_coauthors_plus_admin.search
		});
	};

	/**
	 * Searches a local authors object for author matches
	 */
	go_local_coauthors_plus_admin.search = function( settings ) {
		var results = '';
		var current = null;

		var value = settings.data.q;
		var value_regexp = new RegExp( value, 'i' );

		this.responseText = '';

		if ( ! value ) {
			return;
		}//end if

		for ( var i in go_local_coauthors_plus_admin.authors ) {
			current = go_local_coauthors_plus_admin.authors[ i ];

			if (
				value_regexp.test( current.display_name )
				|| value_regexp.test( current.user_email )
				|| value_regexp.test( current.user_login )
				|| value_regexp.test( current.user_nicename )
			) {
				results += current.ID + ' | ' + current.user_login + ' | ' + current.display_name + ' | ' + current.user_email + ' | ' + current.user_nicename + '\n';
			}//end if
		}//end for

		this.responseText = results;
	};
})( jQuery );

jQuery( function( $ ) {
	go_local_coauthors_plus_admin.init();
});
