FilterResources();

var customLeftButtonText = '';
var customDialogTitle = '';
jQuery(document).ready(function() {
	customLeftButtonText = ss.i18n._t('BookingCalendar.MOVE', 'Move');
	customDialogTitle = ss.i18n._t('BookingCalendar.MOVEDIALOGTITLE', 'Move booking');
});

function ShowDeleteConfirmation(bookingID) {
	jQuery("#DeleteBookingID").val(bookingID);
	
	var buttonOptions = {};
	buttonOptions[ss.i18n._t('BookingCalendar.YES', 'Yes')] = function() {
		// disable buttons until loading is finished
		jQuery(".ui-button:button").attr("disabled","disabled").addClass('ui-state-disabled');
		jQuery("#BookingDeleteForm").ajaxSubmit({
			success: function(responseText, statusText, xhr, form) {
				jQuery(this).dialog("close");
				window.location.reload();
			}
		});
	};
	buttonOptions[ss.i18n._t('BookingCalendar.NO', 'No')] = function() {
		jQuery(this).dialog("close");
	};
	jQuery("#BookingDeleteForm").dialog({
		title: ss.i18n._t('BookingCalendar.DELETEDIALOGTITLE', 'Delete booking'),
		modal: true,
		buttons: buttonOptions
	});
}

function DoPaymentCheck(bookingID){
	jQuery("input").attr('disabled', true);
	jQuery("#StatusMessage").hide();
	jQuery("#ErrorMessage").hide();
	jQuery("#AjaxLoader").show();
	jQuery("#SuomenVerkkomaksutCheckForm-" + bookingID).submit();
}

function MoveBooking(bookingID) {
	jQuery("input").attr('disabled', true);
	jQuery("#StatusMessage").hide();
	jQuery("#ErrorMessage").hide();
	jQuery("#AjaxLoader").show();
	window.location = window.location.toString().replace(/\/$/i, '') + '/movebooking/' + bookingID;
}
