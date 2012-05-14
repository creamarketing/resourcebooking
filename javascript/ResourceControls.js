var resourceCheckboxClicked = false;
function onResourceSelect(event, ui) {
	itemSelected = true;
	if (resourceCheckboxClicked) {
		// checkbox clicked, toggle the clicked resource in the filter
		var filter = jQuery('#ResourceFilter').val().split(',');
		var selectedVal = jQuery(ui.item.option).val();
		var newFilter = new Array();
		var valExists = false;
		var names = '';
		for (var i = 0; i < filter.length; i++) {
			if (filter[i] == selectedVal) {
				valExists = true;
			}
			else if (filter[i] != 0) {
				newFilter.push(filter[i]);
				if (names != '') {
					names += ', ';
				}
				names += jQuery('#ResourceFilterSelect option[value="' + filter[i] + '"]').html();
			}
		}
		if (!valExists) {
			newFilter.push(selectedVal);
			if (names != '') {
				names += ', ';
			}
			names += jQuery('#ResourceFilterSelect option[value="' + selectedVal + '"]').html();
		}
		jQuery('#ResourceFilter').val(newFilter);
		jQuery('#ResourceFilterText').val(names);
		resourceCheckboxClicked = false;
		itemSelected = false;
	}
	else if (jQuery(ui.item.option).hasClass('level0') && resourceToResourceMapping[jQuery(ui.item.option).val()]) {
		jQuery('#ResourceFilter').val(resourceToResourceMapping[jQuery(ui.item.option).val()]);
	}
	else {
		jQuery('#ResourceFilter').val(jQuery(ui.item.option).val());
	}
	SetFilters();
	return itemSelected;
}

function getResourceSource(request, response) {
	var matcher = new RegExp('\\b' + jQuery.ui.autocomplete.escapeRegex(request.term), 'i');
	response(jQuery('#ResourceFilterSelect').children('option:enabled').map(function(){
		if (jQuery(this).val() == '' || matcher.test(jQuery(this).text())) {
			var text = '<label class="resourceLabel ' + jQuery(this).attr('class') + '">' + jQuery(this).text() + '</label>';
			if (jQuery(this).hasClass('main')) {
				var checked = '';
				var filter = jQuery('#ResourceFilter').val().split(',');
				if (jQuery.inArray(jQuery(this).val(), filter) != -1) {
					checked = 'checked="checked"';
				}
				text += '<input type="checkbox" ' + checked + ' onclick="resourceCheckboxClicked=true;" />';
			}
			return {
				label: text,
				value: jQuery(this).text(),
				option: this
			};
		}
	}));
}

function onResourceGroupSelect(event, ui) {
	jQuery('#ResourceGroupFilter').val(jQuery(ui.item.option).val());
	FilterResources();
	return true;
}

function onResourceOrganizationSelect(event, ui) {
	jQuery('#ResourceOrganizationFilter').val(jQuery(ui.item.option).val());
	FilterGroups();
	return true;
}

function FilterResources() {
	var selectedGroup = jQuery('#ResourceGroupFilter').val();
	
	// disable all resources
	jQuery('#ResourceFilterSelect option[value!=""]').attr('disabled', true);
	
	// enable all resource options which are mapped to the selected group
	if (selectedGroup != 0) {
		jQuery('#ResourceFilterSelect option').each(function(){
			var value = jQuery(this).val();
			if (value != 0 && resourceToGroupMapping[value] == selectedGroup) {
				jQuery(this).attr('disabled', false);
			}
		});
	}
	
	// select the 'none selected' option if the currently selected option is now disabled
	if (jQuery('#ResourceFilterSelect option[value=' + jQuery('#ResourceFilter').val() + ']').attr('disabled')) {
		jQuery('#ResourceFilter').val(0);
		jQuery('#ResourceFilterText').val(jQuery('#ResourceFilterSelect option[value=""]').html());
	}
	
	if (typeof SetFilter != "undefined") {
		SetFilters();
	}
}

function FilterGroups() {
	var selectedOrganization = jQuery('#ResourceOrganizationFilter').val();
	
	// disable all groups
	jQuery('#ResourceGroupFilterSelect option[value!=""]').attr('disabled', true);
	
	// enable all group options which are mapped to the selected organization
	if (selectedOrganization != 0) {
		jQuery('#ResourceGroupFilterSelect option').each(function(){
			var value = jQuery(this).val();
			if (value != 0 && groupToOrganizationMapping[value] == selectedOrganization) {
				jQuery(this).attr('disabled', false);
			}
			
		});
	}
	
	// select the 'none selected' option if the currently selected option is now disabled
	if (jQuery('#ResourceGroupFilterSelect option[value=' + jQuery('#ResourceGroupFilter').val() + ']').attr('disabled')) {
		jQuery('#ResourceGroupFilter').val(0);
		jQuery('#ResourceGroupFilterText').val(jQuery('#ResourceGroupFilterSelect option[value=""]').html());
	}
	
	FilterResources();
}