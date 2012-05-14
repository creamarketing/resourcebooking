function SetBillingOptions() {
	var normalHourPrice = new Number(jQuery('#HourPriceTaxExcl').html());
	var duration = (jQuery(bookingFormID + '_End').val() - jQuery(bookingFormID + '_Start').val())/(60*60);
	var taxType = jQuery(bookingFormID + '_TaxType').val();
	var taxPercent = 0;
	if (taxType) {
		taxPercent = taxTypes[taxType] / 100;
	}
	
	var normalHourPriceTaxIncl = new Number(normalHourPrice + taxPercent*normalHourPrice);
	jQuery('#HourPriceTaxIncl').html(normalHourPriceTaxIncl.toFixed(2));
	
	var normalPriceTaxExcl = new Number(normalHourPrice * duration);
	var normalPriceTaxIncl = new Number(normalPriceTaxExcl + taxPercent*normalPriceTaxExcl);
	jQuery('#NormalPriceTaxExcl').html(normalPriceTaxExcl.toFixed(2));
	jQuery('#NormalPriceTaxIncl').html(normalPriceTaxIncl.toFixed(2));
	
	jQuery('#TotalHours').html(duration);
	
	var customPriceTaxExcl = new Number(jQuery('#CustomPrice').val());
	if (customPriceTaxExcl == 0) {
		customPriceTaxExcl = normalPriceTaxExcl;
	}
	var customPriceTaxIncl = new Number(customPriceTaxExcl + taxPercent*customPriceTaxExcl);
	jQuery('#CustomPrice').val(customPriceTaxExcl.toFixed(2));
	jQuery('#CustomPriceTaxIncl').html(customPriceTaxIncl.toFixed(2));
	
	var totalPriceTaxExcl = customPriceTaxExcl;
	if (jQuery(bookingFormID + '_Subsidize').attr('checked')) {
		var subsidizePercent = 0;
		var priceGroup = jQuery(bookingFormID + '_PriceGroup').val();
		if (priceGroup) {
			subsidizePercent = subsidize[priceGroup] / 100;
		}
		totalPriceTaxExcl = new Number(totalPriceTaxExcl - subsidizePercent*totalPriceTaxExcl);
	}
	var totalPriceTaxIncl = new Number(totalPriceTaxExcl + taxPercent*totalPriceTaxExcl);
	jQuery('#TotalPriceTaxExcl').html(totalPriceTaxExcl.toFixed(2));
	jQuery('#TotalPriceTaxIncl').html(totalPriceTaxIncl.toFixed(2));
	
	var customerGroupID = jQuery(bookingFormID + '_CustomerGroupID').val();
	var billingAddress = jQuery('#BillingAddress').html();
	if (customerGroupID && customerGroupID > 0 && billingAddress == '') {
		jQuery.ajax({
			url: 'admin/resourcebooking/GetCustomerGroupAddress?CustomerGroupID=' + customerGroupID,
			dataType: 'html',
			success: function(data){
				// replace newlines with breaklines, for inserting into a paragraph
				var address = data.replace(/\n/gim, '<br />');
				jQuery('#BillingAddress').html(address);
			},
			error: function(){
				
			}
		});
	}
}

jQuery(document).bind('dialogLoaded', function() {
	// set handlers
	jQuery(bookingFormID + '_Billing').change(function() {
		if (jQuery(bookingFormID + '_Billing').val() > 1) {
			jQuery('#BillingGroup').show(500);
		}
		else {
			jQuery('#BillingGroup').hide(500);
		}
	});
	jQuery(bookingFormID + '_Billing').change();
	
	jQuery(bookingFormID + '_Subsidize').change(function() {
		if (jQuery(bookingFormID + '_Subsidize').attr('checked')) {
			jQuery('#SubsidizeGroup').show(500);
		}
		else {
			jQuery('#SubsidizeGroup').hide(500);
		}
	});
	jQuery(bookingFormID + '_Subsidize').change();
	
	SetBillingOptions();
	
	jQuery(bookingFormID + '_Start').change(function() {
		SetBillingOptions();
	});
	
	jQuery(bookingFormID + '_End').change(function() {
		SetBillingOptions();
	});
	
	jQuery(bookingFormID + '_CustomerGroupID').change(function() {
		SetBillingOptions();
	});
	
	jQuery(bookingFormID + '_TaxType').change(function() {
		SetBillingOptions();
	});
	
	jQuery(bookingFormID + '_PriceGroup').change(function() {
		SetBillingOptions();
	});
	
	jQuery('#CustomPrice').change(function() {
		SetBillingOptions();
	});
	
	var originalAddress = '';
	jQuery('#CustomBillingAddress').change(function() {
		if (jQuery('#CustomBillingAddress').attr('checked')) {
			if (!originalAddress) {
				originalAddress = jQuery('#BillingAddress').html();
			}
			// remove breaklines, for inserting into a textarea
			textareaAddress = originalAddress.replace(/<br\s?\/?>/gim, "");
			jQuery('#BillingAddressHolder').html('<textarea id="BillingAddress" name="BillingAddress">'+textareaAddress+'</textarea>');
		}
		else {
			jQuery('#BillingAddressHolder').html('<p id="BillingAddress">'+originalAddress+'</p>');
		}
	});
	
	jQuery('#EditBillingAddress').click(function() {
		var currentID = jQuery(bookingFormID + '_CustomerGroupID').val();
		if (currentID && currentID != 0) {
			ShowAddOrEditDialog(bookingFormID.replace('#', '') + '_CustomerGroupID', resourceBookingAdminHref + 'ResourceBookingForm/field/CustomerGroupID/EditFormHTML?id='+currentID, 'Editera kund');
		}
	});
	
	// set validation rules
	validationRules.Billing = {
		required: true
	};
});