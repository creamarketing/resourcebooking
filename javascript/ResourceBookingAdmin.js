// disable separator resizing, it messes with the calendar resizing!
DraggableSeparator.prototype.onmousedown = function() {};

// set tree-node selection to normal anchor behaviour (i.e. go to href location)
TreeNodeAPI.prototype.selectTreeNode = function() {
	if (this.getElementsByTagName('a')[0].href) {
		location.href = this.getElementsByTagName('a')[0].href;
	}
};

// disable tree-node context-menu
TreeNodeAPI.prototype.oncontextmenu = function(event){};

jQuery(document).ready(function() {
	jQuery('div.dialogtabset').tabs({
		select: function(event, ui) {
			if (typeof onDialogTabChange != 'undefined') {
				return onDialogTabChange(event, ui);
			}
		}
	});
});

function MoveBooking(event, dayDelta, minuteDelta, allDay, revertFunc) {
	var delta = dayDelta*24*60*60 + minuteDelta*60;
	showBookingEditConfirmationDialog(event, delta, delta, revertFunc);
}

function ResizeBooking(event, dayDelta, minuteDelta, revertFunc) {
	var delta = dayDelta*24*60*60 + minuteDelta*60;
	showBookingEditConfirmationDialog(event, 0, delta, revertFunc);
}

function showBookingEditConfirmationDialog(event, startDelta, endDelta, revertFunc) {
	var buttonOptions = {};
	var accepted = false;
	buttonOptions[ss.i18n._t('BookingCalendar.YES', 'Yes')] = function() {
		var editType = jQuery('input:radio[name="editType"]:checked').val();
		ChangeBooking(event, startDelta, endDelta, editType, revertFunc);
		accepted = true;
		jQuery(this).dialog("close");
	};
	buttonOptions[ss.i18n._t('BookingCalendar.NO', 'No')] = function() {
		jQuery(this).dialog("close");
	};
	
	var content = '<div><h3>' + ss.i18n._t('BookingCalendar.CONFIRMEDITBOOKING', 'Really edit booking?') + '</h3>';
	if (event.recurring == "1") {
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
				revertFunc.call();
			}
			jQuery(this).remove();
		}
	});
}

function ChangeBooking(event, startDelta, endDelta, editType, revertFunc) {
	CalendarLoading(true);
	var start = event.start.getTime()/1000 - startDelta;
	var end = event.end.getTime()/1000 - endDelta;
	var resourceFilter = jQuery('#ResourceFilter').val();
	jQuery.ajax({
		type: "GET",
		url: changeURL,
		data: "id="+event.id+"&start="+start+"&end="+end+"&startDelta="+startDelta+"&endDelta="+endDelta+"&resource="+resourceFilter+"&editType="+editType,
		success: function(data, textStatus, XMLHttpRequest) {
			jQuery("#StatusMessage").html(data);
			saveStatusMessages = true;
			jQuery('#BookingCalendar').fullCalendar('refetchEvents');
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			revertFunc.call();
			jQuery("#ErrorMessage").html(XMLHttpRequest.responseText);
			jQuery("#ErrorMessage").show(500);
		}
	});
}

function ShowBookingRequestDialog(id){
	jQuery("#Form_BookingRequestEditForm_BookingRequestID").val(id);
	
	var buttonOptions = {};
	buttonOptions[ss.i18n._t('BookingCalendar.OK', 'Ok')] = function(){
		// submit booking request
		jQuery("#Form_BookingRequestEditForm").ajaxSubmit({
			beforeSubmit: CalendarLoading(true),
			success: BookingSuccess,
			error: BookingError
		});
		jQuery(this).dialog("close");
	};
	buttonOptions[ss.i18n._t('BookingCalendar.CANCEL', 'Cancel')] = function() {
		jQuery(this).dialog("close");
	};
	
	jQuery('#BookingRequestEditDialog').dialog({
		title: ss.i18n._t('BookingCalendar.EDITREQUESTDIALOGTITLE', 'Edit booking request'),
		buttons: buttonOptions,
		modal: true,
		width: 500
	});
}

function ShowBookingEditDialog(id, type){
	var content = document.createElement('div');
	var ajaxLoader = '<div id="DialogAjaxLoader"><h2>Laddar...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
	content.innerHTML = ajaxLoader;
	
	var buttonOptions = {};
	buttonOptions[ss.i18n._t('BookingCalendar.OK', 'Ok')] = function(){
		// submit booking request
		jQuery(bookingFormID).ajaxSubmit({
			beforeSubmit: CalendarLoading(true),
			success: BookingSuccess,
			error: BookingError
		});
		jQuery(this).dialog("close");
	};
	buttonOptions[ss.i18n._t('BookingCalendar.CANCEL', 'Cancel')] = function() {
		jQuery(this).dialog("close");
	};
	
	title = ss.i18n._t('BookingCalendar.EDITREQUESTDIALOGTITLE', 'Edit booking request');
	if (type == 'booking') {
		title = ss.i18n._t('BookingCalendar.EDITBOOKINGDIALOGTITLE', 'Edit booking');
	}
	jQuery(content).dialog({
		title: title,
		buttons: buttonOptions,
		modal: true,
		width: 600,
		height: 500,
		close: function() {
			// remove the dialog from the DOM, so that we do not leave forms with identical ids in the DOM tree
			jQuery(this).remove();
		}
	});
	
	jQuery.ajax({
		url: resourceBookingAdminHref + 'BookingEditFormAjax?BookingEditID=' + id,
		dataType: 'html',
		success: function(data){
			jQuery(content).html(data);
			// open tabs, if present
			jQuery(content).find('div.dialogtabset').tabs();
			jQuery('.ui-button:button').attr('disabled',false).removeClass('ui-state-disabled');
			
			// trigger the custom dialogLoaded event
			jQuery(document).trigger('dialogLoaded');
		},
		error: function() {
			alert('error');
		}
	});
}