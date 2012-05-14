jQuery(document).bind('dialogLoaded', function() {
	// set handlers
	jQuery(bookingFormID + '_Confirmation').change(function() {
		if (jQuery(bookingFormID + '_Confirmation').attr('checked')) {
			jQuery('#ConfirmationGroup').show(500);
		}
		else {
			jQuery('#ConfirmationGroup').hide(500);
		}
	});
	
	jQuery(bookingFormID + '_EMailConfirmation').change(function() {
		if (jQuery(bookingFormID + '_EMailConfirmation').attr('checked')) {
			jQuery('#ConfirmationEmailGroup').show(500);
		}
		else {
			jQuery('#ConfirmationEmailGroup').hide(500);
		}
	});
	
	jQuery('#PreviewConfirmationButton').click(function() {
		// submit booking request
		top.jQuery('.ui-button:button').attr('disabled',true).addClass('ui-state-disabled');
		if (jQuery(bookingFormID + '_PDFConfirmation').attr('checked')) {
			jQuery(bookingFormID + ' fieldset').append('<input id="CheckOnly" type="hidden" value="1" name="CheckOnly" />');
			jQuery(bookingFormID + ' fieldset').append('<input id="PreviewConfirmation" type="hidden" value="1" name="PreviewConfirmation" />');
			jQuery(bookingFormID).unbind('submit'); // unbind validation handler
			jQuery(bookingFormID).submit();
			jQuery('#CheckOnly').remove();
			jQuery('#PreviewConfirmation').remove();
			jQuery('.ui-button:button').attr('disabled', false).removeClass('ui-state-disabled');
		}
		else {
			jQuery(bookingFormID).ajaxSubmit({
				data: {
					CheckOnly: 1,
					PreviewConfirmation: 1
				},
				url: resourceBookingAdminHref + 'BookingEditForm',
				success: function(responseText, statusText, xhr, $form){
					top.jQuery('.ui-button:button').attr('disabled', false).removeClass('ui-state-disabled');
					
					var iframeWrapper = document.createElement("div");
					iframeWrapper.style.display = "none";
					var iframe = document.createElement("iframe");
					iframe.width = 740;
					iframe.height = 600;
					iframeWrapper.appendChild(iframe);
					document.body.appendChild(iframeWrapper);
					
					var closeText = ss.i18n._t('DialogDataObjectManager.CLOSE', 'Stäng');
					// find parent dialog (if one exists) and move it upwards and left
					var nrOfDialogs = jQuery('.ui-dialog').length;
					var left = 200 - (nrOfDialogs-1)*50;
					var parentDialog = top.jQuery('.ui-dialog').last();
					if (parentDialog.html()) {
						top.jQuery(parentDialog).animate({
							left: '-=' + left,
							top: '-=50'
						},
						800);
					}
					
					var buttonOptions = {};
					buttonOptions[closeText] = function(){
						top.jQuery(this).dialog("close");
					};
					
					// show jQuery dialog
					top.jQuery(iframeWrapper).dialog({
						modal: true,
						title: 'Förhandsgranskning av bokningsbekräftelse',
						width: 780,
						height: 600,
						buttons: buttonOptions,
						open: function() {
							var iframeContent = iframe.contentDocument;
							iframeContent.open();
						    iframeContent.writeln(responseText);
						    iframeContent.close();
							iframe.height = iframeContent.body.clientHeight + 20;
						},
						close: function(event, ui){
							// move the parent dialog back
							if (parentDialog.html()) {
								top.jQuery(parentDialog).animate({
									left: '+=' + left,
									top: '+=50'
								},
								800);
							}
							// remove the dialog from the DOM, so that we do not leave a lot of unecessary data in the DOM tree
							top.jQuery(this).remove();
						}
					});
				},
				error: function(XMLHttpRequest, textStatus, errorThrown){
					top.jQuery('.ui-button:button').attr('disabled', false).removeClass('ui-state-disabled');
				}
			});
		}
	});
});

//validation rules
validationRules.ConfirmBefore = {
	required: false
};