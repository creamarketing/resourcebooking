jQuery(document).ready(function() {
	jQuery('tr.hoverable').hover(function() {
		jQuery(this).addClass('hovered');
	}, function() {
		jQuery(this).removeClass('hovered');
	});
	
	jQuery('tr.hovered').live('click', function() {
		itemID = jQuery(this).attr('rel');
		if (itemID) {
			top.ShowDetailsDialog(itemID);
		}
	});
});