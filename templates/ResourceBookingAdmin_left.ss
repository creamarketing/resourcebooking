<div id="leftContainer">
	<ul id="sitetree" class="tree unformatted">
		<li class="Root last">
			<a href="$Link">E-Bokning</a>
			<ul>
				<li class="children">
					<a href="{$Link}showresources">Tider & bokningar</a>
					<ul>
						<li><a href="{$Link}showresources" class="<% if view == showresources %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.CALENDAR','Kalender') %></a></li>
						<% if isAdmin %>
							<li><a href="{$Link}editbookingrequests" class="<% if view == editbookingrequests %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.SHOWBOOKINGREQUESTS','Show booking requests') %></a></li>
							<li><a href="{$Link}editbookings" class="<% if view == editbookings %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.SHOWBOOKINGS','Show bookings') %></a></li>
						<% end_if %>
					</ul>
				</li>
				<% if isOrganizationAdmin %>
					<li class="children">
						<a href="{$Link}editresources" class="<% if view == manageresources %>current<% else %>link<% end_if %>">Resurshantering</a>
						<ul>
							<% if isAdmin %>
								<li><a href="{$Link}editorganizations" class="<% if view == editorganizations %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITRESOURCEORGANIZATIONS','Edit organizations') %></a></li>
							<% end_if %>
							<li><a href="{$Link}editgroups" class="<% if view == editgroups %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITRESOURCEGROUPS','Edit groups') %></a></li>
							<li><a href="{$Link}editresources" class="<% if view == editresources %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITRESOURCES','Edit resources') %></a></li>
							<% if isAdmin %>
								<li><a href="{$Link}editservices" class="<% if view == editservices %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITSERVICES','Tjänster') %></a></li>
								<li><a href="{$Link}editbookingtypes" class="<% if view == editbookingtypes %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITBOOKINGTYPES','Edit booking types') %></a></li>
							<% end_if %>
						</ul>
					</li>
				<% end_if %>
				<% if isGroupAdmin %>
					<li class="children">
						<a href="{$Link}editusers" class="<% if view == manageusers %>current<% else %>link<% end_if %>">Kundhantering</a>
						<ul>
							<li><a href="{$Link}edituserorganizations" class="<% if view == edituserorganizations %>current<% else %>link<% end_if %>">Organisationer</a></li>
							<li><a href="{$Link}editusergroups" class="<% if view == editusergroups %>current<% else %>link<% end_if %>">Avdelningar/grupper</a></li>
							<li><a href="{$Link}editusers" class="<% if view == editusers %>current<% else %>link<% end_if %>">Kontaktpersoner</a></li>
							<% if isAdmin %>
								<li><a href="{$Link}editusertypes" class="<% if view == editusertypes %>current<% else %>link<% end_if %>">Åldersgrupper</a></li>
							<% end_if %>
						</ul>
					</li>
				<% end_if %>
				<% if isAdmin %>
					<li class="children">
						<a href="{$Link}edittaxtypes">Fakturering</a>
						<ul>
							<li><a href="{$Link}edittaxtypes" class="<% if view == edittaxtypes %>current<% else %>link<% end_if %>"><% _t('ResourceBookingAdmin.EDITTAXTYPES','Momsval') %></a></li>
							<li><a href="{$Link}editpricegroups" class="<% if view == editpricegroups %>current<% else %>link<% end_if %>">Prisgrupper</a></li>
						</ul>
					</li>
				<% end_if %>
				<% if isGroupAdmin %>
					<li class="children">
						<a href="{$Link}showbookingreport">Rapporter</a>
						<ul>
							<li><a href="{$Link}showbookingreport" class="<% if view == showbookingreport %>current<% else %>link<% end_if %>">Bokningsrapport</a></li>
							<li><a href="{$Link}showinvoicereport" class="<% if view == showinvoicereport %>current<% else %>link<% end_if %>">Fakturarapport</a></li>
						</ul>
					</li>
				<% end_if %>
			</ul>
		</li>
	</ul>
	
	<a id="showOrHideLeft" href='#' onclick="showOrHideLeft(); return false;">&nbsp;</a>
</div>

<script type="text/javascript">
	
	var leftWidth = 0;
	function showOrHideLeft() {
		jQuery('.qtip').remove();
		if (jQuery('#left').width() > 12) {
			leftWidth = jQuery('#left').width();
			jQuery('#left').animate(
				{
					width: 12
				},
				{
					duration: 400,
					step: function(){
						fixRightWidth();
					},
					complete: function() {
						jQuery('#sitetree').hide();
						jQuery('#showOrHideLeft').css('background-image', 'url(resourcebooking/images/arrowRight.gif)');
						jQuery(window).resize();
					}
				}
			);
		}
		else {
			jQuery('#sitetree').show();
			jQuery('#left').animate(
				{
					width: leftWidth
				},
				{
					duration: 400,
					step: function(){
						fixRightWidth();
					},
					complete: function() {
						jQuery('#showOrHideLeft').css('background-image', 'url(resourcebooking/images/arrowLeft.gif)');
						jQuery(window).resize();
					}
				}
			);
		}
	}
	
</script>
