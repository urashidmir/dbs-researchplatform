(function($) {
	function add_member_to_list( e, ui ) {
		var remove_id = 'bp-experiments-remove-new-member-' + ui.item.value;
		$('#bp-experiments-new-members-list').append('<li><a href="#" class="bp-experiments-remove-new-member" id="' + remove_id + '">x</a> ' + ui.item.label + '</li>');
		$('#' + remove_id).bind('click', function(e) { remove_member_from_list(e); return false; });

		$('#bp-experiments-new-members-list').after('<input name="new_members[]" type="hidden" value="' + ui.item.value + '" />');
	}

	function remove_member_from_list( e ) {
		$(e.target).closest('li').remove();
	}

	var id = 'undefined' !== typeof experiment_id ? '&experiment_id=' + experiment_id : '';
	$(document).ready( function() {
		window.warn_on_leave = false;

		/* Initialize autocomplete */
		$( '.bp-suggest-user' ).autocomplete({
			source:    ajaxurl + '?action=bp_experiment_admin_member_autocomplete' + id,
			delay:     500,
			minLength: 2,
			position:  ( 'undefined' !== typeof isRtl && isRtl ) ? { my: 'right top', at: 'right bottom', offset: '0, -1' } : { offset: '0, -1' },
			open:      function() { $(this).addClass('open'); },
			close:     function() { $(this).removeClass('open'); $(this).val(''); },
			select:    function( event, ui ) { add_member_to_list( event, ui ); }
		});
		
		/* Replace noscript placeholder */
		$( '#bp-experiments-new-members' ).attr( 'placeholder', BP_experiment_Admin.add_member_placeholder );

		/* Warn before leaving unsaved changes */
		$(document).on( 'change', 'input#bp-experiments-name, input#bp-experiments-description, select.bp-experiments-role, #bp-experiments-settings-section-status input[type="radio"]', function() {
			window.warn_on_leave = true;
		});

		$( 'input#save' ).on( 'click', function() {
			window.warn_on_leave = false;
		});

		window.onbeforeunload = function(e) {
			if ( window.warn_on_leave ) {
				return BP_experiment_Admin.warn_on_leave;
			}
		};
	});
})(jQuery);
