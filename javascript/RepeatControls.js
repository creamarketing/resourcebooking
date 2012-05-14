// function definitions
function RepeatTypeChanged(event, ui) {
	var typeID = jQuery(ui.item.option).val();
	if (typeID == '') {
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEachLabel').html('dagar');
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays').parent().hide();
	}
	else if (typeID == 'w') {
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEachLabel').html('veckor');
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays').parent().show();
	}
	else if (typeID == 'm') {
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEachLabel').html('månader');
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays').parent().hide();
	}
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatType').val(typeID);
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatTypeText').val(ui.item.value);
	
	UpdateSummary();
}

function RepeatEachChanged(event, ui){
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEach').val(jQuery(ui.item.option).val());
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEachText').val(ui.item.value);
	UpdateSummary();
}

function UpdateSummary() {
	var each = '';
	var repeat = jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEach').val();
	if (repeat == '') {
		each = 'varje ';
	}
	else if (repeat == 2) {
		each = 'var annan ';
	}
	else {
		each = 'var ' + repeat + ':e ';
	}
	
	var typeID = jQuery('#BookingEditPopup_ResourceBookingForm_RepeatType').val();
	if (typeID == '') {
		each += 'dag, ';
	}
	else if (typeID == 'w') {
		each += 'vecka, ';
		jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays input').each(function() {
			if (jQuery(this).attr('checked')) {
				each += jQuery(this).siblings('label').html() + ', ';
			}
		});
	}
	else if (typeID == 'm') {
		each += 'månad, ';
	}
	
	var stop = '';
	if (jQuery('#BookingEditPopup_ResourceBookingForm_RepeatStop_0').attr('checked')) {
		stop = '' + jQuery('#BookingEditPopup_ResourceBookingForm_RepeatTimes').val() + ' gånger';
	}
	else {
		stop = 'tills ' + jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEndDay').val();
	}
	
	var start = 'från ' + jQuery('#BookingEditPopup_ResourceBookingForm_StartDate').val() + ', ';
	
	jQuery('#RepeatSummary').html(each + start + stop);
}

function SetRepeatOptions(start) {
	var startDate = new Date(start.getTime()); 
	var day = startDate.getDay();
	// back-end treats Sunday as day 7, but javascript Date treats Sunday as day 0
	if (day == 0) {
		day == 7;
	}
	
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays input').attr('checked', false);
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays_' + day).attr('checked', true);
	UpdateSummary();
	jQuery('#CheckResult').html('');
}

jQuery(document).bind('dialogLoaded', function() {
	// reorder controls
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatDays').parent().hide();
	
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEachLabel').parent().addClass('inner').appendTo(jQuery('#BookingEditPopup_ResourceBookingForm_RepeatEach').parent());
	
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatTimes').parent().addClass('inner').appendTo(jQuery('#BookingEditPopup_ResourceBookingForm_RepeatStop_0').parent());
	jQuery('#BookingEditPopup_ResourceBookingForm_RepeatTimesLabel').parent().addClass('inner').appendTo(jQuery('#BookingEditPopup_ResourceBookingForm_RepeatStop_0').parent());
	
	jQuery('#RepeatEndDay').appendTo(jQuery('#BookingEditPopup_ResourceBookingForm_RepeatStop_1').parent());
	
	// set handlers
	jQuery('#BookingEditPopup_ResourceBookingForm_Repeat').change(function() {
		if (jQuery('#BookingEditPopup_ResourceBookingForm_Repeat').attr('checked')) {
			jQuery('#RepeatGroup').show(500);
		}
		else {
			jQuery('#RepeatGroup').hide(500);
		}
	});
	
	jQuery('#RepeatGroup input.radio').change(function() {
		UpdateSummary();
	});
	
	jQuery('#RepeatGroup input.text').change(function() {
		UpdateSummary();
	});
	
	jQuery('#RepeatGroup input.checkbox').change(function() {
		UpdateSummary();
	});
	
	jQuery('#CheckRepeatButton').click(function() {
		// submit booking request
		jQuery('.ui-button:button').attr('disabled',true).addClass('ui-state-disabled');
		jQuery('#CheckResult').html('');
		jQuery("#BookingEditPopup_ResourceBookingForm").ajaxSubmit({
			data: {
				CheckOnly: 1
			},
			success: function(responseText, statusText, xhr, $form) {
				jQuery('.ui-button:button').attr('disabled',false).removeClass('ui-state-disabled');
				jQuery('#CheckResult').html('<div class="Message Success">' + responseText + '</div>');
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				jQuery('.ui-button:button').attr('disabled',false).removeClass('ui-state-disabled');
				jQuery('#CheckResult').html(XMLHttpRequest.responseText);
			}
		});
	});
	
	// make add button duplicate the current booking instead of adding a new empty one
	var addButton = jQuery('#BookingEditPopup_BookingEditForm_RepeatBookings .dataobjectmanager-actions a.popup-button');
	if (addButton.length == 1) {
		var bookingEditID = jQuery('#BookingEditPopup_BookingEditForm_BookingEditID').val();
		var addHref = addButton.attr('href');
		var duplicateHref = addHref.replace('/add?', '/item/' + bookingEditID + '/duplicate?');
		addButton.attr('href', duplicateHref);
	}
});

//validation rules
validationRules.RepeatEndDay = {
	required: false
};