// set times in the booking dialog based on clicked event
function SetTimes(start, end, targetStartTime, targetEndTime) {
	var startDay = start.getDate();
	if (startDay < 10) {
		startDay = '0' + startDay;
	}
	var startMonth = start.getMonth() + 1;
	if (startMonth < 10) {
		startMonth = '0' + startMonth;
	}
	jQuery('#BookingEditPopup_ResourceBookingForm_StartDate').val(startDay + '.' + startMonth + '.' + start.getFullYear());
	jQuery('#BookingEditPopup_ResourceBookingForm_EndDate').val(startDay + '.' + startMonth + '.' + start.getFullYear());
	
	var interval = 15;
	
	if (!targetEndTime || targetEndTime == targetStartTime) {
		targetEndTime = targetStartTime + interval*60;
	}
	
	if (targetStartTime > targetEndTime) {
		var temp = targetStartTime;
		targetStartTime = targetEndTime;
		targetEndTime = temp;
	}
	
	startDate = new Date(start.getFullYear(), start.getMonth(), start.getDate(), 0, 0);
	var startTimeInMinutes = (targetStartTime - startDate.getTime()/1000)/60;
	endDate = new Date(start.getFullYear(), start.getMonth(), start.getDate(), 0, 0);
	var endTimeInMinutes = (targetEndTime - endDate.getTime()/1000)/60;
	var startText = jQuery('#BookingEditPopup_ResourceBookingForm_StartSelect option[value='+startTimeInMinutes+']').html();
	var endText = jQuery('#BookingEditPopup_ResourceBookingForm_EndSelect option[value='+endTimeInMinutes+']').html();
	jQuery('#BookingEditPopup_ResourceBookingForm_Start').val(targetStartTime);
	jQuery('#BookingEditPopup_ResourceBookingForm_StartText').val(startText);
	jQuery('#BookingEditPopup_ResourceBookingForm_StartSelect').val(startTimeInMinutes);
	jQuery('#BookingEditPopup_ResourceBookingForm_End').val(targetEndTime);
	jQuery('#BookingEditPopup_ResourceBookingForm_EndText').val(endText);
	jQuery('#BookingEditPopup_ResourceBookingForm_EndSelect').val(endTimeInMinutes);
	
	// trigger the custom bookingTimesSet event
	jQuery(document).trigger('bookingTimesSet');
}

function CustomerOrganizationSelected(event, ui) {
	var organizationID = jQuery(bookingFormID + '_CustomerOrganizationID').val();
	if (ui) {
		organizationID = jQuery(ui.item.option).val();
	}
	if (organizationID && organizationID > 0) {
		var allowedGroups = customerOrganizationToGroupMapping[organizationID];
		// disable all groups
		jQuery(bookingFormID + '_CustomerGroupIDSelect option[value!=""]').attr('disabled', true);
		// disable all customers
		jQuery(bookingFormID + '_CustomerIDSelect option[value!=""]').attr('disabled', true);
		
		// enable all group options which are mapped to the selected organization
		for (var i = 0; i < allowedGroups.length; i++) {
			var groupID = allowedGroups[i];
			jQuery(bookingFormID + '_CustomerGroupIDSelect option[value=' + groupID + ']').attr('disabled', false);
			
			// enable all related customers
			var allowedCustomers = customerGroupToCustomerMapping[groupID];
			for (var j = 0; j < allowedCustomers.length; j++) {
				var customerID = allowedCustomers[j];
				jQuery(bookingFormID + '_CustomerIDSelect option[value=' + customerID + ']').attr('disabled', false);
			}
		}
		jQuery(bookingFormID + '_CustomerOrganizationID').val(organizationID);
	}
	else {
		jQuery(bookingFormID + '_CustomerOrganizationID').val(0);
		// enable all groups
		jQuery(bookingFormID + '_CustomerGroupIDSelect option[value!=0]').attr('disabled', false);
		CustomerGroupSelected(null, null, true);
	}
}

function CustomerGroupSelected(event, ui, noOrganizationSelected) {
	var groupID = jQuery(bookingFormID + '_CustomerGroupID').val();
	if (ui) {
		groupID = jQuery(ui.item.option).val();
	}
	if (groupID && groupID > 0) {
		// disable all customers
		jQuery(bookingFormID + '_CustomerIDSelect option[value!=""]').attr('disabled', true);
		
		// enable all related customers
		var allowedCustomers = customerGroupToCustomerMapping[groupID];
		for (var i = 0; i < allowedCustomers.length; i++) {
			var customerID = allowedCustomers[i];
			jQuery(bookingFormID + '_CustomerIDSelect option[value=' + customerID + ']').attr('disabled', false);
		}
		
		// select organization
		for (var organizationID in customerOrganizationToGroupMapping) {
			if (jQuery.inArray(groupID, customerOrganizationToGroupMapping[organizationID]) != -1) {
				jQuery(bookingFormID + '_CustomerOrganizationID').val(organizationID);
				jQuery(bookingFormID + '_CustomerOrganizationIDText').val(jQuery(bookingFormID + '_CustomerOrganizationIDSelect option[value=' + organizationID + ']').text());
				break;
			}
		}
		jQuery(bookingFormID + '_CustomerGroupID').val(groupID);
	}
	else {
		jQuery(bookingFormID + '_CustomerGroupID').val(0);
		// enable all customers
		jQuery(bookingFormID + '_CustomerIDSelect option[value!=""]').attr('disabled', false);
		if (!noOrganizationSelected) {
			CustomerOrganizationSelected();
		}
	}
}

function CustomerSelected(event, ui) {
	var customerID = jQuery(ui.item.option).val();
	if (customerID && customerID > 0) {
		// select group
		if (jQuery(bookingFormID + '_CustomerGroupID').val() == 0) {
			var groupID = 0;
			for (groupID in customerGroupToCustomerMapping) {
				if (jQuery.inArray(customerID, customerGroupToCustomerMapping[groupID]) != -1) {
					jQuery(bookingFormID + '_CustomerGroupID').val(groupID);
					jQuery(bookingFormID + '_CustomerGroupIDText').val(jQuery(bookingFormID + '_CustomerGroupIDSelect option[value=' + groupID + ']').text());
					break;
				}
			}
		}
		
		// select organization
		if (jQuery(bookingFormID + '_CustomerOrganizationID').val() == 0) {
			for (var organizationID in customerOrganizationToGroupMapping) {
				if (jQuery.inArray(groupID, customerOrganizationToGroupMapping[organizationID]) != -1) {
					jQuery(bookingFormID + '_CustomerOrganizationID').val(organizationID);
					jQuery(bookingFormID + '_CustomerOrganizationIDText').val(jQuery(bookingFormID + '_CustomerOrganizationIDSelect option[value=' + organizationID + ']').text());
					break;
				}
			}
		}
		jQuery(bookingFormID + '_CustomerID').val(customerID);
	}
	else {
		jQuery(bookingFormID + '_CustomerID').val(0);
		CustomerGroupSelected();
	}
}

// add custom validation methods
jQuery.validator.addMethod(
	'maxlength',
	function(value, element) {
		return value.length <= jQuery(element).attr('maxlength');
	},
	'Max längd överskriden'
);
jQuery.validator.addMethod(
	'date',
	function(value, element) {
		return this.optional(element) != false || value.match(/^\d\d?.\d\d?.\d\d\d\d$/);
	},
	'Fel datumformat'
);

// add custom validation message translations
jQuery.extend(jQuery.validator.messages, {
	required: "Detta fält är obligatoriskt!"
});

// initialize validation rules
var validationRules = {}
function validateCurrentTab() {
	// validate currently selected tab
	var currSelected = jQuery('#Tabs ul li.ui-tabs-selected a');
	var tabContent = jQuery(currSelected.attr('href'));
	var validation = jQuery(bookingFormID).validate({
		rules: validationRules
	});
	var tabOK = true;
	tabContent.find('input').each(function() {
		if (jQuery(this).parents('.field').hasClass('date')) {
			var savedClass = jQuery(this).attr('class');
			jQuery(this).attr('class', 'date');
		}
		var result = validation.element(jQuery(this));
		if (jQuery(this).parents('.field').hasClass('date')) {
			jQuery(this).attr('class', savedClass);
		}
		if (!result) {
			tabOK = false;
		}
	});
	return tabOK;
}

function onDialogTabChange(event, ui) {
	if (!validateCurrentTab()) {
		return false;
	}
	
	if (jQuery(ui.tab).parent('li').hasClass('last')) {
		jQuery(ui.tab).parents('.ui-dialog').find('.ui-button').first().children('.ui-button-text').html(ss.i18n._t('BookingCalendar.BOOK', 'Book'));
	}
	else {
		jQuery(ui.tab).parents('.ui-dialog').find('.ui-button').first().children('.ui-button-text').html(ss.i18n._t('BookingCalendar.CONTINUE', 'Continue'));
	}
	
	if (jQuery(ui.tab).parent('li').hasClass('first')) {
		jQuery(ui.tab).parents('.ui-dialog').find('.ui-button').last().children('.ui-button-text').html(ss.i18n._t('BookingCalendar.CANCEL', 'Cancel'));
	}
	else {
		jQuery(ui.tab).parents('.ui-dialog').find('.ui-button').last().children('.ui-button-text').html(ss.i18n._t('BookingCalendar.BACK', 'Back'));
	}
	return true;
}

var bookingFormID = '#BookingEditPopup_ResourceBookingForm';
function findBookingFormID() {
	bookingFormID = '#BookingEditPopup_ResourceBookingForm';
	if (!jQuery(bookingFormID).html()) {
		bookingFormID = '#BookingEditPopup_BookingEditForm';
	}
	if (!jQuery(bookingFormID).html()) {
		bookingFormID = '#BookingEditPopup_AddForm';
		jQuery(bookingFormID).addClass('ResourceBookingForm');
	}
	if (!jQuery(bookingFormID).html()) {
		bookingFormID = '#BookingEditPopup_DuplicateForm';
		jQuery(bookingFormID).addClass('ResourceBookingForm');
	}
	if (!jQuery(bookingFormID).html()) {
		bookingFormID = '#BookingEditPopup_DetailForm';
		jQuery(bookingFormID).addClass('ResourceBookingForm');
	}
	if (!jQuery(bookingFormID).html()) {
		bookingFormID = '#DialogDataObjectManager_Popup_DetailForm';
		jQuery(bookingFormID).addClass('ResourceBookingForm');
	}
}

var startDate;
var endDate;
var selectedStatus;
jQuery(document).bind('dialogLoaded', function() {
	findBookingFormID();
	
	jQuery(bookingFormID + '_StartDate').change(function() {
		var dateParts = jQuery(bookingFormID + '_StartDate').val().split('.', 3);
		if (dateParts.length == 3) {
			startDate = new Date(dateParts[2], dateParts[1]-1, dateParts[0], 0, 0);
			jQuery(bookingFormID + '_Start').change();
		}
	});
	jQuery(bookingFormID + '_EndDate').change(function() {
		var dateParts = jQuery(bookingFormID + '_EndDate').val().split('.', 3);
		if (dateParts.length == 3) {
			endDate = new Date(dateParts[2], dateParts[1]-1, dateParts[0], 0, 0);
			jQuery(bookingFormID + '_End').change();
		}
	});
	
	jQuery(bookingFormID + '_Start').change(function() {
		var selectedTime = jQuery(bookingFormID + '_StartSelect').val();
		jQuery(bookingFormID + '_Start').val(startDate.getTime()/1000 + selectedTime*60);
	});
	jQuery(bookingFormID + '_End').change(function() {
		var selectedTime = jQuery(bookingFormID + '_EndSelect').val();
		jQuery(bookingFormID + '_End').val(endDate.getTime()/1000 + selectedTime*60);
	});
	jQuery(bookingFormID + '_StartDate').change();
	jQuery(bookingFormID + '_EndDate').change();
	
	if (jQuery('#SpecificBookingID').length > 0) {
		jQuery('#BookingID').addClass('specific').appendTo('#SpecificBookingID ul li.val1');
		jQuery(bookingFormID + '_SpecificBookingID_1').change(function() {
			jQuery('#BookingID').show(500);
		});
		jQuery(bookingFormID + '_SpecificBookingID_0').change(function() {
			jQuery('#BookingID').hide(500);
			jQuery(bookingFormID + '_BookingID').val('');
		});
	}
	
	selectedStatus = jQuery(bookingFormID + '_Status input:checked').val();
	jQuery(bookingFormID + '_Status input').change(function() {
		if (jQuery(this).val() == 'Cancelled' || jQuery(this).val() == 'Rejected') {
			var buttonOptions = {};
			var accepted = false;
			buttonOptions[ss.i18n._t('BookingCalendar.YES', 'Yes')] = function() {
				var editType = jQuery('#ConfirmCancelBooking input:checked').val();
				jQuery(bookingFormID + '_RecurringEditType_' + editType).attr('checked', true);
				accepted = true;
				jQuery(this).dialog("close");
				jQuery('#tab-Tabs_Confirmation').click();
			};
			buttonOptions[ss.i18n._t('BookingCalendar.NO', 'No')] = function() {
				jQuery(this).dialog("close");
			};
			
			var confirmText = ss.i18n._t('BookingCalendar.CONFIRMCANCELBOOKING', 'Really cancel booking?');
			if (jQuery(this).val() == 'Rejected') {
				confirmText = ss.i18n._t('BookingCalendar.CONFIRMREJECTBOOKING', 'Really reject booking request?');
			}
			var content = '<div id="ConfirmCancelBooking"><h3>' + confirmText + '</h3>';
			if (jQuery(bookingFormID + '_RepeatBookings').length > 0) {
				content += '<div><input id="editTypeSingle" type="radio" name="editType" value="single" checked="checked" /><label for="editTypeSingle">' + ss.i18n._t('BookingCalendar.EDITSINGLERECURRING', 'Edit this only this booking') + '</label></div>';
				content += '<div><input id="editTypeDay" type="radio" name="editType" value="day" /><label for="editTypeDay">' + ss.i18n._t('BookingCalendar.EDITDAYRECURRING', 'Edit all recurring on this day') + '</label></div>';
				content += '<div><input id="editTypeAll" type="radio" name="editType" value="all" /><label for="editTypeAll">' + ss.i18n._t('BookingCalendar.EDITALLRECURRING', 'Edit all related recurring') + '</label></div>';
			}
			content += '</div>';
			jQuery(content).dialog({
				modal: true,
				title: ss.i18n._t('BookingCalendar.CONTINUE', 'Continue') + '?',
				buttons: buttonOptions,
				close: function() {
					if (!accepted) {
						jQuery(bookingFormID + '_Status input[value="' + selectedStatus + '"]').attr('checked', true);
					}
					jQuery(this).remove();
				}
			});
		}
		else {
			selectedStatus = jQuery(this).val();
		}
	});
});

//validation rules
validationRules.Type = {
	required: true
};