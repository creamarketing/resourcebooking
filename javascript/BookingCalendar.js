// calendar variables
var eventURL = document.location.toString().replace(/\/$/i, '') + "/events";
var calendarURL = '';
var allowBooking = true;
var allowSelection = false;
var minHour = 7;
var maxHour = 22;
var resourceCheckboxClicked = false;
var selectionInProgress = false;
var gotoDate = "";
var preselectedOrganizationID = 0;
var preselectedGroupID = 0;
var preselectedResourceID = 0;
var printButton = null;
var printDownloadCheckTimer = null;
jQuery(document).ready(function() {
	if (jQuery('#BookingCalendar').length > 0) {
		// init i18n to get correct locale
		ss.i18n.init();
		
		calendarURL = eventURL;
		
		var defaultView = 'agendaWeek';
		if (gotoDate) {
			defaultView = 'agendaDay';
		}
		
		// initialize calendar
		jQuery('#BookingCalendar').fullCalendar({
			events: calendarURL,
			editable: false,
			selectable: allowSelection,
			selectHelper: true,
			allDayDefault: false,
			lazyFetching: false,
			loading: CalendarLoading,
			aspectRatio: 1,
	        firstDay: 1,
			slotMinutes: 15,
			defaultView: defaultView,
			timeFormat: {
				agenda: 'HH:mm{ - HH:mm}',
				'':     'HH:mm'
			},
			columnFormat: {
				week: 'ddd d.M',
				day: 'dddd d.M'
			},
			axisFormat: 'HH:mm',
			minTime: minHour,
			maxTime: maxHour,
			header: {
				left:   'prev,next today ',
				center: 'title',
				right:  'month,agendaWeek,agendaDay'
			},
			buttonText: {
				today: ss.i18n._t('BookingCalendar.TODAY', 'today'),
			    month: ss.i18n._t('BookingCalendar.MONTH', 'month'),
			    week:  ss.i18n._t('BookingCalendar.WEEK', 'week'),
			    day:   ss.i18n._t('BookingCalendar.DAY', 'day')
			},
			allDayText: ss.i18n._t('BookingCalendar.ALLDAY', 'all-day'),
			monthNames: ss.i18n._t('BookingCalendar.MONTHNAMES', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']),
			monthNamesShort: ss.i18n._t('BookingCalendar.MONTHNAMESSHORT', ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']),
			dayNames: ss.i18n._t('BookingCalendar.DAYNAMES', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
			dayNamesShort: ss.i18n._t('BookingCalendar.DAYNAMESSHORT', ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']),
			dayClick: function(date, allDay, jsEvent, view) {
		        if (allDay) {
		            jQuery('#BookingCalendar').fullCalendar('gotoDate', date);
					jQuery('#BookingCalendar').fullCalendar('changeView', 'agendaDay');
		        }
		    },
			select: function(startDate, endDate, allDay, jsEvent, view) {
				if (view.name != 'month' && !allDay && allowSelection && typeof ShowSelectionDialog == "function") {
					ShowSelectionDialog(startDate, endDate);
				}
				else {
					jQuery('#BookingCalendar').fullCalendar('unselect');
				}
			},
			eventClick: function(calEvent, jsEvent, view) {
				if (view.name == 'month') {
					jQuery('#BookingCalendar').fullCalendar('gotoDate', calEvent.start);
					jQuery('#BookingCalendar').fullCalendar('changeView', 'agendaDay');
				}
				else if (allowBooking) {
					jQuery(this).qtip('hide');
					if (typeof calEvent.type != 'undefined' && (calEvent.type == 'request' || calEvent.type == 'booking') && calEvent.editable) {
						if (typeof CustomShowBookingDialog == 'function') {
							CustomShowBookingDialog(calEvent);
						}
					}
				}
			},
			eventDrop: function(event, dayDelta, minuteDelta, allDay, revertFunc){
				if (typeof MoveBooking == 'function') {
					MoveBooking(event, dayDelta, minuteDelta, allDay, revertFunc);
				}
				jQuery(this).qtip('hide');
			},
			eventResize: function(event, dayDelta, minuteDelta, revertFunc){
				if (typeof ResizeBooking == 'function') {
					ResizeBooking(event, dayDelta, minuteDelta, revertFunc);
				}
				jQuery(this).qtip('hide');
			},
			eventRender: function(event, element, view) {
				// add event description to DOM element (only if we are not in month view though)
				if (view.name != 'month') {
					//element.find('.fc-event-inner').append('<span class="fc-event-description">'+event.description+'</span>');
				}
				/*element.bind('touchmove', function(event) {
					trigger('mousemove', event);
				});*/
				
				// initialize qtip
				var content = "";
				if (view.name == "month") {
					content = event.title;
				}
				else {
					var hour = event.start.getHours();
					if (hour < 10) {
						hour = "0" + hour;
					}
					var minutes = event.start.getMinutes();
					if (minutes < 10) {
						minutes = "0" + minutes;
					}
					var startTime = hour + ":" + minutes;
					startHour = event.end.getHours();
					if (startHour < 10) {
						startHour = "0" + startHour;
					}
					hour = event.end.getHours();
					if (hour < 10) {
						hour = "0" + hour;
					}
					minutes = event.end.getMinutes();
					if (minutes < 10) {
						minutes = "0" + minutes;
					}
					var endTime = hour + ":" + minutes;
					content = startTime + " - " + endTime + "<br />" + event.title
					if (event.description) {
						content += "<br />" + event.description;
					}
				}
				element.qtip({
					content: content,
					position: {
						corner: {
							target: 'topMiddle',
							tooltip: 'bottomLeft'
						},
						adjust: {
							screen: true
						}
					},
					style: {
						name: 'cream',
						tip: "bottomLeft"
					}
				});
				
				if (typeof event.type != 'undefined') {
					element.find('.fc-event-bg').addClass(event.type);
					if (event.type == 'resource') {
						var targetStartTime;
						var targetEndTime;
						var startRowNr = 0;
						var selectionRowNr = 0;
						var endRowNr = 1;
						jQuery(element).mousedown(function(jsEvent){
							selectionInProgress = true;
							jQuery(element).find('.fc-event-inner').append('<div class="hovered"></div>');
							// find target time by first calculating pixel differense from agenda body table top to clicked position
							var offset = jQuery(element).parent().siblings('table.fc-agenda-slots').offset();
							var diff = jsEvent.pageY - offset.top;
							// create a start date to calculate target time from, based on day of event start time (starting at min time for the calendar)
							var targetDate = new Date(event.start.getFullYear(), event.start.getMonth(), event.start.getDate(), minHour, 0);
							// each row in the agenda body table is 21 pixels high, and each row represents 15 minutes, thus we can calculate
							// the target time (in secods) as follows
							selectionRowNr = parseInt(diff / 21);
							startRowNr = selectionRowNr;
							endRowNr = startRowNr + 1;
							targetStartTime = (targetDate.getTime() / 1000) + selectionRowNr * 15 * 60;
							
							diff = jQuery(element).parent().siblings('table.fc-agenda-slots').find('tr').slice(selectionRowNr, selectionRowNr+1).offset().top - jQuery(element).offset().top - 25;
							var hoveredArea = jQuery(element).find('.hovered');
							hoveredArea.css('height', 21 + 'px');
							hoveredArea.css('top', diff + 'px');
						});
						
						jQuery(element).mousemove(function(jsEvent){
							jQuery(element).parent().siblings('table.fc-agenda-slots').find('tr').removeClass("hovered");
							var offset = jQuery(element).parent().siblings('table.fc-agenda-slots').offset();
							var diff = jsEvent.pageY - offset.top;
							var rowNr = parseInt(diff/21);
							jQuery(element).parent().siblings('table.fc-agenda-slots').find('tr').slice(rowNr, rowNr+1).addClass("hovered");
							
							if (selectionInProgress) {
								var hoveredArea = jQuery(element).find('.hovered');
								if (rowNr <= startRowNr) {
									startRowNr = rowNr;
									endRowNr = selectionRowNr;
									diff = jQuery(element).parent().siblings('table.fc-agenda-slots').find('tr').slice(startRowNr, startRowNr+1).offset().top - jQuery(element).offset().top - 25;
									hoveredArea.css('top', diff + 'px');
								}
								else if (rowNr <= selectionRowNr) {
									startRowNr = rowNr;
								}
								else if (rowNr > selectionRowNr){
									endRowNr = rowNr;
									if (startRowNr < selectionRowNr) {
										startRowNr = selectionRowNr;
										diff = jQuery(element).parent().siblings('table.fc-agenda-slots').find('tr').slice(startRowNr, startRowNr+1).offset().top - jQuery(element).offset().top - 25;
										hoveredArea.css('top', diff + 'px');
									}
								}
								
								var height = (endRowNr - startRowNr + 1)*21;
								hoveredArea.css('height', height + 'px');
							}
						});
						
						jQuery(element).mouseup(function(jsEvent){
							selectionInProgress = false;
							jQuery(element).find('.hovered').remove();
							// find target time by first calculating pixel differense from agenda body table top to clicked position
							var offset = jQuery(element).parent().siblings('table.fc-agenda-slots').offset();
							var diff = jsEvent.pageY - offset.top;
							// create a start date to calculate target time from, based on day of event start time (starting at min time for the calendar)
							var targetDate = new Date(event.start.getFullYear(), event.start.getMonth(), event.start.getDate(), minHour, 0);
							// each row in the agenda body table is 21 pixels high, and each row represents 15 minutes, thus we can calculate
							// the target time (in secods) as follows (adding one row to end time, to include selected row)
							targetEndTime = (targetDate.getTime() / 1000) + (parseInt(diff / 21) + 1) * 15 * 60;
							
							element.qtip('hide');
							
							ShowBookingDialog(event, targetStartTime, targetEndTime);
						});
					}
					else if (event.type == 'booking') {
						element.find('.fc-event-inner').append('<span class="fc-event-booking"><img src="' + jQuery('base').attr('href') + 'resourcebooking/images/booking.png" alt="Booking" /></span>');
					}
				}
				
				if (typeof event.recurring != 'undefined' && event.recurring == true) {
					element.find('.fc-event-inner').append('<span class="fc-event-recurring"><img src="' + jQuery('base').attr('href') + 'resourcebooking/images/recurring.gif" alt="Recurring" /></span>');
				}
			},
			eventMouseover: function(event, jsEvent, view) {
				// style the row in hte agenda view table that was hovered, by calculating the offset from agenda body top to current target
				var offset = jQuery(jsEvent.currentTarget).parent().siblings('table.fc-agenda-slots').offset();
				var mouseY = jsEvent.pageY;
				var diff = mouseY - offset.top;
				// row height is 21 pixels
				var rowNr = parseInt(diff/21);
				jQuery(jsEvent.currentTarget).parent().siblings('table.fc-agenda-slots').find('tr').slice(rowNr, rowNr+1).addClass("hovered");
				
				var target = jsEvent.currentTarget;
				jQuery('#right').scroll(function(jsEvent) {
					jQuery(target).parent().siblings('table.fc-agenda-slots').find('tr').removeClass("hovered");
					var offset = jQuery(target).parent().siblings('table.fc-agenda-slots').offset();
					var diff = mouseY - offset.top;
					var rowNr = parseInt(diff/21);
					jQuery(target).parent().siblings('table.fc-agenda-slots').find('tr').slice(rowNr, rowNr+1).addClass("hovered");
				});
			},
			eventMouseout: function(event, jsEvent, view) {
				if (view.name != 'month') {
					// remove hovered style from row
					jQuery('#right').unbind('scroll');
					jQuery(jsEvent.currentTarget).parent().siblings('table.fc-agenda-slots').find('tr').removeClass("hovered");
				}
	    		var isIpad = (navigator.userAgent.match(/iPad/i));
				if (isIpad) {
					trigger('eventClick', event, jsEvent, view);
				}
			},
			viewDisplay: function(view) {
				// set aspect ratio depending on which view we are in
				if (view.name == 'month') {
					// month view needs bigger aspect ratio here, since it will grow too big otherwise
					jQuery('#BookingCalendar').fullCalendar('option', 'aspectRatio', 2);
				}
				else {
					jQuery('#BookingCalendar').fullCalendar('option', 'aspectRatio', 1);
				}
				
				// set mouseover and mouseout handlers for table rows in agenda views, to style hovered row 
				jQuery('table.fc-agenda-slots tr').mouseover(function() {
					jQuery(this).addClass("hovered");
				});
				jQuery('table.fc-agenda-slots tr').mouseout(function() {
					jQuery(this).removeClass("hovered");
				});
				
				var selectedMonth = jQuery('#BookingCalendar').fullCalendar('getDate').getMonth();
				jQuery('#QuickMonth').val(selectedMonth);
				jQuery('#QuickMonthSelect').val(selectedMonth);
				jQuery('#QuickMonthText').val(jQuery('#QuickMonthSelect option[value=' + selectedMonth + ']').text());
			}
	    });
		
		// initialize quick month selection with localized month names
		var monthNames = ss.i18n._t('BookingCalendar.MONTHNAMES', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']);
		for (var i = 0; i < monthNames.length; i++) {
			jQuery('#QuickMonthSelect').append('<option value="' + i + '">' + monthNames[i] + '</option>');
		}
		var initialMonth = jQuery('#BookingCalendar').fullCalendar('getDate').getMonth();
		jQuery('#QuickMonth').val(initialMonth);
		jQuery('#QuickMonthSelect').val(initialMonth);
		jQuery('#QuickMonthText').val(jQuery('#QuickMonthSelect option[value=' + initialMonth + ']').text());
		
		if (gotoDate) {
			// goto preselected date
			jQuery('#BookingCalendar').fullCalendar('gotoDate', new Date(gotoDate));
		}
		
		if (preselectedOrganizationID) {
			jQuery('#ResourceOrganizationFilter').val(preselectedOrganizationID);
			jQuery('#ResourceOrganizationFilterText').val(jQuery('#ResourceOrganizationFilterSelect option[value='+preselectedOrganizationID+']').html());
			
			FilterGroups();
		}
		if (preselectedGroupID) {
			var organizationID = groupToOrganizationMapping[preselectedGroupID];
			jQuery('#ResourceOrganizationFilter').val(organizationID);
			jQuery('#ResourceOrganizationFilterText').val(jQuery('#ResourceOrganizationFilterSelect option[value='+organizationID+']').html());
			
			jQuery('#ResourceGroupFilter').val(preselectedGroupID);
			jQuery('#ResourceGroupFilterText').val(jQuery('#ResourceGroupFilterSelect option[value='+preselectedGroupID+']').html());
			
			FilterGroups();
		}
		if (preselectedResourceID) {
			// select preselected resource, and corresponding group and organization
			jQuery('#ResourceFilter').val(preselectedResourceID);
			jQuery('#ResourceFilterText').val(jQuery('#ResourceFilterSelect option[value='+preselectedResourceID+']').html());
			
			var groupID = resourceToGroupMapping[preselectedResourceID];
			jQuery('#ResourceGroupFilter').val(groupID);
			jQuery('#ResourceGroupFilterText').val(jQuery('#ResourceGroupFilterSelect option[value='+groupID+']').html());
			
			var organizationID = groupToOrganizationMapping[groupID];
			jQuery('#ResourceOrganizationFilter').val(organizationID);
			jQuery('#ResourceOrganizationFilterText').val(jQuery('#ResourceOrganizationFilterSelect option[value='+organizationID+']').html());
			
			FilterGroups();
			SetFilters();
		}
		
		// printing
		printButton = jQuery('<span class="fc-button fc-button-print fc-state-default fc-corner-left fc-corner-right">'+
								'<span class="fc-button-inner">' +
									'<span class="fc-button-content">' + ss.i18n._t('BookingCalendar.PRINT', 'print') + '</span>'+
									'<span class="fc-button-effect"><span></span></span>'+
								'</span>'+
							'</span>');
		
		printButton.click(function() {
			if (!printButton.hasClass('fc-state-disabled')) {
				printButton.addClass('fc-state-disabled');
				
				// create a form for submitting the print calendar html to the server, so that the server can generate a pdf.
				// we can't generate the calendar on the server, since it is heavily dependent on javascript...
				var printForm = jQuery(' <form id="PrintForm" action="' + resourceBookingAdminHref + 'PrintCalendarPDF" method="post">' + 
											'<input id="PrintCalendarHTML" type="hidden" name="CalendarHTML" value="" />' +
											'<input id="PrintCalendarTitle" type="hidden" name="Title" value="" />' +
											'<input id="PrintCalendarView" type="hidden" name="View" value="" />' +
											'<input id="PrintCalendarResourceID" type="hidden" name="ResourceID" value="" />' +
											'<input id="PrintCalendarDownloadToken" type="hidden" name="DownloadToken" value="" />' +
										'</form>');
				
				// create a new print calendar, with slightly different options than the normal calendar (so that it will fit on one A4 page)
				var printCalendar = jQuery('<div id="PrintCalendar""></div>');
				// we need to add the print calendar div to the DOM before initializing the fullcalendar
				jQuery('#BookingCalendarContainer').append(printCalendar);
				jQuery('#BookingCalendarContainer').append(printForm);
				var currentView = jQuery('#BookingCalendar').fullCalendar('getView').name;
				var currentDate = jQuery('#BookingCalendar').fullCalendar('getDate');
				var year = currentDate.getFullYear();
				var month = currentDate.getMonth();
				var day = currentDate.getDate();
				printCalendar.fullCalendar({
					events: jQuery('#BookingCalendar').fullCalendar('clientEvents'),
					slotMinutes: 30,
					height: 3000,
					firstDay: 1,
					year: year,
					month: month,
					date: day,
					defaultView: currentView,
					timeFormat: {
						agenda: 'HH:mm{ - HH:mm}',
						'':     'HH:mm'
					},
					columnFormat: {
						week: 'ddd d.M',
						day: 'dddd d.M'
					},
					axisFormat: 'HH:mm',
					minTime: 7,
					maxTime: 24,
					header: {
						left:   '',
						center: '',
						right:  ''
					},
					allDaySlot: false,
					monthNames: ss.i18n._t('BookingCalendar.MONTHNAMES', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']),
					monthNamesShort: ss.i18n._t('BookingCalendar.MONTHNAMESSHORT', ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']),
					dayNames: ss.i18n._t('BookingCalendar.DAYNAMES', ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
					dayNamesShort: ss.i18n._t('BookingCalendar.DAYNAMESSHORT', ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']),
					eventRender: function(event, element, view) {
						if (typeof event.type != 'undefined') {
							element.find('.fc-event-bg').addClass(event.type);
						}
					}
				});
				
				var title = jQuery('#BookingCalendar').fullCalendar('getView').title;
				if (currentView == 'agendaWeek') {
					var week = jQuery('#PrintCalendar .rk-workweek').html();
					week = week.replace('v', '');
					title = 'Vecka ' + week;
				}
				jQuery('#PrintCalendarHTML').val(printCalendar.html());
				jQuery('#PrintCalendarTitle').val(title);
				jQuery('#PrintCalendarView').val(currentView);
				jQuery('#PrintCalendarResourceID').val(jQuery('#ResourceFilter').val());
				
				// create a download token from current timestamp
				var downloadToken = new Date().getTime();
				jQuery('#PrintCalendarDownloadToken').val(downloadToken);
				
				// set an interval function to check for the print download token cookie,
				// if it is set to our download token value it means the request has finnished
				var timeout = 60; // in seconds
				printDownloadCheckTimer = window.setInterval(function () {
					var cookieValue = jQuery.cookie('printDownloadToken');
					if (cookieValue == downloadToken) {
						PrintDownloadFinished();
					}
					else if(--timeout == 0) {
						PrintDownloadTimeout();
					}
				}, 1000);
				
				// post the print form, to download the pdf
				printForm.submit();
				
				// remove the form and print calendar from the DOM
				printForm.remove();
				printCalendar.remove();
			}
		})
		.mousedown(function() {
			printButton
				.not('.fc-state-active')
				.not('.fc-state-disabled')
				.addClass('fc-state-down');
		})
		.mouseup(function() {
			printButton.removeClass('fc-state-down');
		})
		.hover(
			function() {
				printButton
					.not('.fc-state-active')
					.not('.fc-state-disabled')
					.addClass('fc-state-hover');
			},
			function() {
				printButton
					.removeClass('fc-state-hover')
					.removeClass('fc-state-down');
			}
		);
		jQuery('#BookingCalendar .fc-header-left').append(printButton);
	}
});

function PrintDownloadFinished() {
	// clear timer, cookie etc...
	window.clearInterval(printDownloadCheckTimer);
	jQuery.cookie('printDownloadToken', null);
	printButton.removeClass('fc-state-disabled');
}

function PrintDownloadTimeout() {
	PrintDownloadFinished();
	jQuery('#ErrorMessage').html('Download timeout!');
	jQuery('#ErrorMessage').show();
}

function onQuickMonthSelect(event, ui) {
	currDate = jQuery('#BookingCalendar').fullCalendar('getDate');
	jQuery('#BookingCalendar').fullCalendar('gotoDate', currDate.getFullYear(), jQuery(ui.item.option).val());
}

function ResourceSelected(skipSetFilters) {
	if (!skipSetFilters) {
		FilterResources();
	}
}

// set filters for events by adding appropriate get parameters
function SetFilters() {
	// first remove the existing event source
	jQuery('#BookingCalendar').fullCalendar('removeEventSource', calendarURL);
	jQuery('#BookingCalendar').fullCalendar('removeEvents');
	
	calendarURL = eventURL;
	
	var resourceFilter = jQuery('#ResourceFilter').val();
	if (resourceFilter == 0) {
		resourceFilter = '';
	}
	else {
		if (jQuery('#ResourceFilterSelect option[value=' + resourceFilter + ']').hasClass('level0')) {
			if (resourceToResourceMapping[resourceFilter]) {
				resourceFilter = resourceToResourceMapping[resourceFilter];
			}
		}
		resourceFilter = '?resourceFilter=' + resourceFilter;
	}
	calendarURL += resourceFilter;
	
	if (jQuery('#BookingsSection').html()) {
		var freeTimeFilter = 0;
		if (jQuery('#FreeTimeFilter').attr('checked')) {
			freeTimeFilter = 1;
		}
		if (calendarURL == eventURL) {
			calendarURL += '?';
		}
		else {
			calendarURL += '&';
		}
		calendarURL += 'showFreeTime=' + freeTimeFilter;
		
		var bookingsFilter = 0;
		if (jQuery('#BookingsFilter').attr('checked')) {
			bookingsFilter = 1;
		}
		if (calendarURL == eventURL) {
			calendarURL += '?';
		}
		else {
			calendarURL += '&';
		}
		calendarURL += 'showBookings=' + bookingsFilter;
		
		var requestsFilter = 0;
		if (jQuery('#RequestsFilter').attr('checked')) {
			requestsFilter = 1;
		}
		if (calendarURL == eventURL) {
			calendarURL += '?';
		}
		else {
			calendarURL += '&';
		}
		calendarURL += 'showRequests=' + requestsFilter;
		
		var cancelledFilter = 0;
		if (jQuery('#CancelledFilter').attr('checked')) {
			cancelledFilter = 1;
		}
		if (calendarURL == eventURL) {
			calendarURL += '?';
		}
		else {
			calendarURL += '&';
		}
		calendarURL += 'showCancelled=' + cancelledFilter;
		
		var rejectedFilter = 0;
		if (jQuery('#RejectedFilter').attr('checked')) {
			rejectedFilter = 1;
		}
		if (calendarURL == eventURL) {
			calendarURL += '?';
		}
		else {
			calendarURL += '&';
		}
		calendarURL += 'showRejected=' + rejectedFilter;
	}
	
	// add the modified event source
	jQuery('#BookingCalendar').fullCalendar('addEventSource', calendarURL);
}

// show a jQuery dialog for booking a resource
function ShowBookingDialog(calEvent, targetStartTime, targetEndTime){
	var content = document.createElement('div');
	var ajaxLoader = '<div id="TempDialogAjaxLoader"><h2>Laddar...</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
	content.innerHTML = ajaxLoader;
	
	var buttonOptions = {};
	buttonOptions[ss.i18n._t('BookingCalendar.CONTINUE', 'Continue')] = function(event) {
		// do booking if last tab is selected
		if (jQuery('#Tabs ul.tabstrip li.last').hasClass('ui-tabs-selected')) {
			if (validateCurrentTab()) {
				// submit booking request
				jQuery('.ui-button').attr('disabled', true).addClass('ui-state-disabled');
				jQuery("#BookingEditPopup_ResourceBookingForm").ajaxSubmit({
					beforeSubmit: CalendarLoading(true),
					success: function(responseText, statusText, xhr, $form){
						jQuery(content).dialog("close");
						BookingSuccess(responseText, statusText, xhr, $form);
					},
					error: function(XMLHttpRequest, textStatus, errorThrown){
						jQuery("#DialogErrorMessage").html(XMLHttpRequest.responseText);
						jQuery(".AjaxLoader").hide();
						jQuery("#DialogErrorMessage").show(500);
						jQuery('.ui-button').attr('disabled', false).removeClass('ui-state-disabled');
					}
				});
			}
		}
		// otherwise select next tab
		else {
			var nextTab = jQuery('#Tabs ul.tabstrip li.ui-tabs-selected').next('li');
			if (nextTab) {
				nextTab.children('a').click();
			}
		}
	};
	buttonOptions[ss.i18n._t('BookingCalendar.CANCEL', 'Cancel')] = function(event){
		// select previous tab if first tab is not selected
		if (!jQuery('#Tabs ul.tabstrip li.first').hasClass('ui-tabs-selected')) {
			var prevTab = jQuery('#Tabs ul.tabstrip li.ui-tabs-selected').prev('li');
			if (prevTab) {
				prevTab.children('a').click();
			}
		}
		// otherwise close the dialog
		else {
			jQuery(this).dialog("close");
		}
	}
	
	var dialogTitle = ss.i18n._t('BookingCalendar.BOOKINGDIALOGTITLE', 'Book resource');
	if (typeof customDialogTitle != 'undefined') {
		dialogTitle = customDialogTitle;
	}
	
	jQuery(content).dialog({
		title: dialogTitle,
		buttons: buttonOptions,
		modal: true,
		create: function() {
			// disable dialog buttons (will be enabled when form content is fully loaded)
			//if (!(jQuery.browser.msie && parseInt(jQuery.browser.version, 10) < 9)) {
				jQuery('.ui-button').attr('disabled', true).addClass('ui-state-disabled');
			//}
			// add ajax loader and output messages to dialog button-pane
			jQuery(this).parent().find('.ui-dialog-buttonpane').append('<div id="DialogOutput" class="Output" style="float:left;"><div id="DialogAjaxLoader" class="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div><div id="DialogStatusMessage" class="Message StatusMessage" style="display:none;"></div><div id="DialogErrorMessage" class="Message ErrorMessage" style="display:none;"></div></div>');
		},
		close: function() {
			// remove the dialog from the DOM, so that we do not leave forms with identical ids in the DOM tree
			jQuery(this).remove();
		},
		width: 500
	});
	
	jQuery.ajax({
		url: resourceBookingAdminHref + 'ResourceBookingFormAjax?ResourceID=' + calEvent.id,
		dataType: 'html',
		success: function(data){
			jQuery(content).html(data);
			// open tabs, if present
			jQuery(content).find('div.dialogtabset').tabs({
				select: function(event, ui){
					return onDialogTabChange(event, ui);
				}
			});
			
			// trigger the custom dialogLoaded event
			jQuery(document).trigger('dialogLoaded');
			
			// set times
			SetTimes(calEvent.start, calEvent.end, targetStartTime, targetEndTime);
			
			// set repeat options
			SetRepeatOptions(calEvent.start);
			
			// set billing options
			SetBillingOptions();
			
			jQuery('.ui-button').attr('disabled',false).removeClass('ui-state-disabled');
		},
		error: function() {
			alert('error');
		}
	});
}

// show ajax loader
var saveStatusMessages = false;
function CalendarLoading(isLoading, view) {
	if (isLoading) {
		if (!saveStatusMessages) {
			jQuery(".StatusMessage").html('');
			jQuery(".ErrorMessage").html('');
		}
		jQuery(".StatusMessage").hide();
		jQuery(".ErrorMessage").hide();
		jQuery(".AjaxLoader").show();
	}
	else {
		jQuery(".AjaxLoader").hide();
		saveStatusMessages = false;
		if (jQuery("#StatusMessage").html() != '') {
			jQuery("#StatusMessage").show(500);
		}
		else if (jQuery("#ErrorMessage").html() != '') {
			jQuery("#ErrorMessage").show(500);
		}
		// remove all qtip elements on loading finished, to prevent ghost tooltips
		jQuery('.qtip').remove();
	}
}

// booking ajax-post success
function BookingSuccess(responseText, statusText, xhr, $form) {
	jQuery("#StatusMessage").html(responseText);
	saveStatusMessages = true;
	jQuery('#BookingCalendar').fullCalendar('refetchEvents');
	
	if (typeof HandleBookingSuccess == 'function') {
		HandleBookingSuccess();
	}
	else if (typeof resourceBookingMemberPage != 'undefined' && resourceBookingMemberPage != '') {
		window.location = resourceBookingMemberPage;
	}
}

// booking ajax-post error
function BookingError(XMLHttpRequest, textStatus, errorThrown) {
	jQuery("#ErrorMessage").html(XMLHttpRequest.responseText);
	CalendarLoading(false);
}

// login/registration ajax-post success
function LoginSuccess(responseText, statusText, xhr, $form) {
	alert(responseText);
	isLoggedIn = true;
	jQuery("#BookingMemberDialog").dialog("close");
	// re-enable jquery dialog buttons
	jQuery(".ui-button").attr("disabled","").removeClass('ui-state-disabled');
}

// login/registration ajax-post error
function LoginError(XMLHttpRequest, textStatus, errorThrown) {
	alert(XMLHttpRequest.responseText);
	isLoggedIn = false;
	// re-enable jquery dialog buttons
	jQuery(".ui-button").attr("disabled","").removeClass('ui-state-disabled');
}
