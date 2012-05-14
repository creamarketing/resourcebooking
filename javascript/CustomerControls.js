function CustomerOrganizationSelected(event, ui) {
	var organizationID = jQuery('#CustomerOrganizationID').val();
	if (ui) {
		organizationID = jQuery(ui.item.option).val();
	}
	if (organizationID && organizationID > 0) {
		var allowedGroups = customerOrganizationToGroupMapping[organizationID];
		// disable all groups
		jQuery('#CustomerGroupIDSelect option[value!=""]').attr('disabled', true);
		// disable all customers
		jQuery('#CustomerIDSelect option[value!=""]').attr('disabled', true);
		
		// enable all group options which are mapped to the selected organization
		for (var i = 0; i < allowedGroups.length; i++) {
			var groupID = allowedGroups[i];
			jQuery('#CustomerGroupIDSelect option[value=' + groupID + ']').attr('disabled', false);
			
			// enable all related customers
			var allowedCustomers = customerGroupToCustomerMapping[groupID];
			for (var j = 0; j < allowedCustomers.length; j++) {
				var customerID = allowedCustomers[j];
				jQuery('#CustomerIDSelect option[value=' + customerID + ']').attr('disabled', false);
			}
		}
		jQuery('#CustomerOrganizationID').val(organizationID);
	}
	else {
		jQuery('#CustomerOrganizationID').val(0);
		// enable all groups
		jQuery('#CustomerGroupIDSelect option[value!=0]').attr('disabled', false);
		CustomerGroupSelected(null, null, true);
	}
}

function CustomerGroupSelected(event, ui, noOrganizationSelected) {
	var groupID = jQuery('#CustomerGroupID').val();
	if (ui) {
		groupID = jQuery(ui.item.option).val();
	}
	if (groupID && groupID > 0) {
		// disable all customers
		jQuery('#CustomerIDSelect option[value!=""]').attr('disabled', true);
		
		// enable all related customers
		var allowedCustomers = customerGroupToCustomerMapping[groupID];
		for (var i = 0; i < allowedCustomers.length; i++) {
			var customerID = allowedCustomers[i];
			jQuery('#CustomerIDSelect option[value=' + customerID + ']').attr('disabled', false);
		}
		
		// select organization
		for (var organizationID in customerOrganizationToGroupMapping) {
			if (jQuery.inArray(groupID, customerOrganizationToGroupMapping[organizationID]) != -1) {
				jQuery('#CustomerOrganizationID').val(organizationID);
				jQuery('#CustomerOrganizationIDText').val(jQuery('#CustomerOrganizationIDSelect option[value=' + organizationID + ']').text());
				break;
			}
		}
		jQuery('#CustomerGroupID').val(groupID);
	}
	else {
		jQuery('#CustomerGroupID').val(0);
		// enable all customers
		jQuery('#CustomerIDSelect option[value!=""]').attr('disabled', false);
		if (!noOrganizationSelected) {
			CustomerOrganizationSelected();
		}
	}
}

function CustomerSelected(event, ui) {
	var customerID = jQuery(ui.item.option).val();
	if (customerID && customerID > 0) {
		// select group
		if (jQuery('#CustomerGroupID').val() == 0) {
			var groupID = 0;
			for (groupID in customerGroupToCustomerMapping) {
				if (jQuery.inArray(customerID, customerGroupToCustomerMapping[groupID]) != -1) {
					jQuery('#CustomerGroupID').val(groupID);
					jQuery('#CustomerGroupIDText').val(jQuery('#CustomerGroupIDSelect option[value=' + groupID + ']').text());
					break;
				}
			}
		}
		
		// select organization
		if (jQuery('#CustomerOrganizationID').val() == 0) {
			for (var organizationID in customerOrganizationToGroupMapping) {
				if (jQuery.inArray(groupID, customerOrganizationToGroupMapping[organizationID]) != -1) {
					jQuery('#CustomerOrganizationID').val(organizationID);
					jQuery('#CustomerOrganizationIDText').val(jQuery('#CustomerOrganizationIDSelect option[value=' + organizationID + ']').text());
					break;
				}
			}
		}
		jQuery('#CustomerID').val(customerID);
	}
	else {
		jQuery('#CustomerID').val(0);
		CustomerGroupSelected();
	}
}