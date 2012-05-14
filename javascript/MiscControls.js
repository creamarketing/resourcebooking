jQuery(document).bind('dialogLoaded', function() {
	// set handlers
	jQuery(bookingFormID + '_Service').change(function() {
		if (jQuery(bookingFormID + '_Service').attr('checked')) {
			jQuery('#ServiceGroup').show(500);
		}
		else {
			jQuery('#ServiceGroup').hide(500);
		}
	});
	jQuery(bookingFormID + '_Service').change();
	
	jQuery(bookingFormID + '_ServiceTime').change(function() {
		var dateParts = jQuery(bookingFormID + '_ServiceDate').val().split('.', 3);
		if (dateParts.length == 3) {
			var serviceDate = new Date(dateParts[2], dateParts[1]-1, dateParts[0], 0, 0);
			var selectedTime = jQuery(bookingFormID + '_ServiceTimeSelect').val();
			jQuery(bookingFormID + '_ServiceTime').val(serviceDate.getTime()/1000 + selectedTime*60);
		}
	});
});

jQuery(document).bind('bookingTimesSet', function() {
	jQuery(bookingFormID + '_ServiceDate').val(jQuery(bookingFormID + '_StartDate').val());
	jQuery(bookingFormID + '_ServiceTimeText').val(jQuery(bookingFormID + '_StartText').val());
	jQuery(bookingFormID + '_ServiceTimeSelect').val(jQuery(bookingFormID + '_StartSelect').val());
	jQuery(bookingFormID + '_ServiceTime').change();
});

//validation rules
validationRules.ServiceTime = {
	required: false
};
