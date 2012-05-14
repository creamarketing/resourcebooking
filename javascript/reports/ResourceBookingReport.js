var fileDownloadCheckTimer;
var actionInProgress = false;
jQuery(document).ready(function() {
	// init i18n to get correct locale
	ss.i18n.init();
	
	jQuery('#Form_ReportForm_action_GenerateReport').click(function(e) {
		StopFormAction();
		
		var loadingText = ss.i18n._t('ResourceBookingReport.LOADING', 'Generating report...');
		var ajaxLoader = '<div id="DialogAjaxLoader"><h2>' + loadingText + '</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
		// add iframe container div containing the iframe to the body
		jQuery('body').append('<div id="ReportDialog" class="iframe_wrap" style="display:none;"><iframe id="ReportDialog_iframe" src="about:blank" frameborder="0" width="100%" height="100%" style="display: none;"></iframe>' + ajaxLoader + '</div>');
		
		var buttonsOptions = {};
		buttonsOptions[ss.i18n._t('ResourceBookingReport.PRINT', 'Print')] = function() {
			try {
				var iframe = jQuery('#ReportDialog_iframe')[0].contentWindow;
				iframe.focus();
				iframe.print();
			} catch (e) {
				alert("Sorry, unable to print!");
			}
		};
		buttonsOptions[ss.i18n._t('ResourceBookingReport.SAVEPDF', 'Save PDF')] = function() {
			// create a download token from current timestamp
			var downloadToken = new Date().getTime();
			jQuery('#Form_ReportForm_DownloadToken').val(downloadToken);
			
			// set an interval function to check for the file download token cookie,
			// if it is set to our download token value it means the request has finnished
			var timeout = 60; // in seconds
			fileDownloadCheckTimer = window.setInterval(function () {
				var cookieValue = jQuery.cookie('fileDownloadToken');
				if (cookieValue == downloadToken) {
					DownloadFinished();
				}
				else if(--timeout == 0) {
					DownloadTimeout();
				}
			}, 1000);
			
			StartFormAction('PDF');
			jQuery('#Form_ReportForm').submit();
		};
		buttonsOptions[ss.i18n._t('ResourceBookingReport.CLOSE', 'Close')] = function() {
			jQuery(this).dialog('close');
		};
		
		var extraButtons = null;
		if (typeof ExtraReportButtons == 'function') {
			extraButtons = ExtraReportButtons();
		}
		
		var dialogWidth = 800;
		var dialogHeight = 600;
		if (jQuery('#Form_ReportForm_Orientation').val() == 'landscape') {
			dialogWidth = 1000;
		}
		jQuery('#ReportDialog').dialog({
			title: jQuery('#Form_ReportForm_Header').html(),
			width: dialogWidth,
			height: dialogHeight,
			resizable: false,
			modal: true,
			buttons: buttonsOptions,
			open: function() {
				var uiDialogButtonPane = jQuery(this).siblings('.ui-dialog-buttonpane');
				uiDialogButtonPane.prepend('<div class="Output" style="float:left;"><div id="AjaxLoader" style="display:none;"><img src="dataobject_manager/images/ajax-loader-white.gif" alt="loading..." /></div><div id="StatusMessage" class="StatusMessage Message" style="display:none;"></div><div id="ErrorMessage" class="ErrorMessage Message" style="display:none;"></div></div>');
				if (extraButtons) {
					// add extra buttons in a new buttonset, to the left
					var uiButtonSet = jQuery("<div></div>").addClass("ui-dialog-buttonset left").prependTo(uiDialogButtonPane);
					
					jQuery.each(extraButtons, function(name, fn) {
						var button = jQuery('<button type="button"></button>');
						button.text(name);
						button.appendTo(uiButtonSet);
						button.click(function() { fn.call(this, button); });
						(jQuery.fn.button && button.button());
					});
				}
				jQuery(".ui-button:button").attr("disabled", true).addClass('ui-state-disabled');
				
				jQuery('#Form_ReportForm').ajaxSubmit({
					success: function(responseText, statusText, xhr, $form){
						var iframeContent = jQuery('#ReportDialog_iframe')[0].contentDocument;
						if (!iframeContent) 
							iframeContent = jQuery('#ReportDialog_iframe')[0].contentWindow.document;
						iframeContent.open();
						iframeContent.writeln(responseText);
						iframeContent.close();
						
						setTimeout("jQuery('#ReportDialog_iframe').attr('height', jQuery('#ReportDialog_iframe').contents().find('body').height() + 20);", 100);
						
						jQuery('#DialogAjaxLoader').hide(); 
						jQuery('#ReportDialog_iframe').show();
						jQuery(".ui-button:button").attr("disabled", false).removeClass('ui-state-disabled');
					},
					error: function(XMLHttpRequest, textStatus, errorThrown){
						alert(XMLHttpRequest.responseText);
					}
				});
			},
			close: function() {
				jQuery(this).remove();
			},
			beforeClose: function() {
				if (actionInProgress) {
					return false;
				}
				return true;
			}
		});
		
		e.stopPropagation();
		return false;
	});
});

function ShowDetailsDialog(itemID) {
	jQuery('#Form_ReportForm_DetailItemID').val(itemID);
	StartFormAction('ItemDetails');
	
	var loadingText = ss.i18n._t('ResourceBookingReport.DETAILSLOADING', 'Laddar detaljer...');
	var ajaxLoader = '<div id="DetailsDialogAjaxLoader"><h2>' + loadingText + '</h2><img src="dataobject_manager/images/ajax-loader-white.gif" alt="Loading in progress..." /></div>';
	// add iframe container div containing the iframe to the body
	jQuery('body').append('<div id="DetailsDialog" class="iframe_wrap" style="display:none;"><iframe id="DetailsDialog_iframe" src="about:blank" frameborder="0" width="100%" height="100%" style="display: none;"></iframe>' + ajaxLoader + '</div>');
	
	var buttonsOptions = {};
	buttonsOptions[ss.i18n._t('ResourceBookingReport.CLOSE', 'Close')] = function() {
		jQuery(this).dialog('close');
	};
	
	var dialogWidth = 800;
	var dialogHeight = 600;
	if (jQuery('#Form_ReportForm_Orientation').val() == 'landscape') {
		dialogWidth = 1000;
	}
	
	// find parent dialog (if one exists) and move it upwards and left
	var nrOfDialogs = jQuery('.ui-dialog').length;
	var left = 200 - (nrOfDialogs-1)*50;
	var parentDialog = jQuery('.ui-dialog').last();
	if (parentDialog.html()) {
		jQuery(parentDialog).animate({
			left: '-=' + left,
			top: '-=50'
		},
		800);
	}
	
	jQuery('#DetailsDialog').dialog({
		title: ss.i18n._t('ResourceBookingReport.DETAILSTITLE', 'Detaljer för fakturarad'),
		width: dialogWidth,
		height: dialogHeight,
		resizable: false,
		modal: true,
		buttons: buttonsOptions,
		open: function() {
			jQuery(".ui-button:button").attr("disabled", true).addClass('ui-state-disabled');
			
			jQuery('#Form_ReportForm').ajaxSubmit({
				success: function(responseText, statusText, xhr, $form){
					var iframeContent = jQuery('#DetailsDialog_iframe')[0].contentDocument;
					if (!iframeContent) 
						iframeContent = jQuery('#DetailsDialog_iframe')[0].contentWindow.document;
					iframeContent.open();
					iframeContent.writeln(responseText);
					iframeContent.close();
					
					setTimeout("jQuery('#DetailsDialog_iframe').attr('height', jQuery('#DetailsDialog_iframe').contents().find('body').height() + 20);", 100);
					
					jQuery('#DetailsDialogAjaxLoader').hide(); 
					jQuery('#DetailsDialog_iframe').show();
					jQuery(".ui-button:button").attr("disabled", false).removeClass('ui-state-disabled');
					StopFormAction();
				},
				error: function(XMLHttpRequest, textStatus, errorThrown){
					alert(XMLHttpRequest.responseText);
					StopFormAction();
				}
			});
		},
		close: function() {
			// move the parent dialog back
			if (parentDialog.html()) {
				jQuery(parentDialog).animate({
					left: '+=' + left,
					top: '+=50'
				},
				800);
			}
			// remove the dialog from the DOM, so that we do not leave a lot of unecessary data in the DOM tree
			jQuery(this).remove();
		}
	});
}

function StartFormAction(action) {
	actionInProgress = true;
	if (action) {
		if (action.substr(0, 7) != 'action_') {
			action = 'action_' + action;
		}
		jQuery('#Form_ReportForm_FormAction').attr('name', action);
	}
	
	jQuery(".ui-button:button").attr("disabled", true).addClass('ui-state-disabled');
	
	jQuery('#AjaxLoader').show();
	
	jQuery('#StatusMessage').html('');
	jQuery('#StatusMessage').hide();
	
	jQuery('#ErrorMessage').html('');
	jQuery('#ErrorMessage').hide();
}

function StopFormAction() {
	jQuery('#Form_ReportForm_FormAction').attr('name', 'FormAction');
	
	jQuery('#AjaxLoader').hide();
	
	jQuery(".ui-button:button").attr("disabled", false).removeClass('ui-state-disabled');
	
	actionInProgress = false;
}

function DownloadFinished() {
	// clear timer, cookie, fake action etc...
	window.clearInterval(fileDownloadCheckTimer);
	StopFormAction();
	jQuery('#Form_ReportForm_DownloadToken').val('');
	jQuery.cookie('fileDownloadToken', null);
}

function DownloadTimeout() {
	DownloadFinished();
	jQuery('#ErrorMessage').html('Download timeout!');
	jQuery('#ErrorMessage').show();
}