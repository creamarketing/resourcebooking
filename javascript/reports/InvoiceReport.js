function ExtraReportButtons() {
	var buttonsOptions = {};
	buttonsOptions[ss.i18n._t('InvoiceReport.MARKASBILLED', 'Märk som fakturerade')] = function(button) {
		StartFormAction('MarkAsBilled');
		
		jQuery('#Form_ReportForm').ajaxSubmit({
			success: function(responseText, statusText, xhr, $form){
				jQuery('#StatusMessage').html(responseText);
				jQuery('#StatusMessage').show();
				if (button) {
					jQuery(button).hide();
				}
				StopFormAction();
			},
			error: function(XMLHttpRequest, textStatus, errorThrown){
				jQuery('#ErrorMessage').html(XMLHttpRequest.responseText);
				jQuery('#ErrorMessage').show();
				StopFormAction();
			}
		});
	}
	
	return buttonsOptions;
}