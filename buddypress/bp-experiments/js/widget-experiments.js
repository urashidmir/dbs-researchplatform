jQuery(document).ready( function() {
	jQuery(".widget div#experiments-list-options a").on('click',
		function() {
			var link = this;
			jQuery(link).addClass('loading');

			jQuery(".widget div#experiments-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');

			jQuery.post( ajaxurl, {
				action: 'widget_experiments_list',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-experiments").val(),
				'max_experiments': jQuery("input#experiments_widget_max").val(),
				'filter': jQuery(this).attr('id')
			},
			function(response)
			{
				jQuery(link).removeClass('loading');
				experiments_wiget_response(response);
			});

			return false;
		}
	);
});

function experiments_wiget_response(response) {
	response = response.substr(0, response.length-1);
	response = response.split('[[SPLIT]]');

	if ( response[0] != "-1" ) {
		jQuery(".widget ul#experiments-list").fadeOut(200,
			function() {
				jQuery(".widget ul#experiments-list").html(response[1]);
				jQuery(".widget ul#experiments-list").fadeIn(200);
			}
		);

	} else {
		jQuery(".widget ul#experiments-list").fadeOut(200,
			function() {
				var message = '<p>' + response[1] + '</p>';
				jQuery(".widget ul#experiments-list").html(message);
				jQuery(".widget ul#experiments-list").fadeIn(200);
			}
		);
	}
}
