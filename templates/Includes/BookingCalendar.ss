<div id="BookingCalendarContainer">
	<div id="Info">
		<div id="ResourceOrganizationSection" class="FilterSection">
			<h3><% _t('ResourceOrganization.SINGULARNAME','Organization') %></h3>
			<% if showResourceOrganizationFilter %>
				$ResourceOrganizationFilter
			<% else %>
				<span id="ResourceOrganizationName">--</span>
			<% end_if %>
		</div>
		
		<div id="ResourceGroupSection" class="FilterSection">
			<h3><% _t('ResourceGroup.SINGULARNAME','Group') %></h3>
			<% if showResourceGroupFilter %>
				$ResourceGroupFilter
			<% else %>
				<span id="ResourceGroupName">--</span>
			<% end_if %>
		</div>
		
		<div id="ResourceSection" class="FilterSection">
			<h3><% _t('Resource.SINGULARNAME','Resource') %></h3>
			<% if showResourceFilter %>
				$ResourceFilter
			<% else %>
				<span id="ResourceName">--</span>
			<% end_if %>
		</div>
		
		<% if QuickMonth %>
			<div id="QuickMonthSection" class="FilterSection">
				<h3>Månad</h3>
				$QuickMonth
			</div>
		<% end_if %>
		
		<% if showBookingSection %>
			<div id="BookingsSection" class="FilterSection">
				<h3><% _t('Booking.SHOW','Visa') %></h3>
				<input id="FreeTimeFilter"  type="checkbox" onclick="SetFilters();" checked="checked" /><label for="FreeTimeFilter"><% _t('Booking.FREETIME','Ledig tid') %></label>
				<input id="BookingsFilter"  type="checkbox" onclick="SetFilters();" checked="checked" /><label for="BookingsFilter"><% _t('Booking.PLURALNAME','Bokningar') %></label>
				<input id="RequestsFilter"  type="checkbox" onclick="SetFilters();" checked="checked" /><label for="RequestsFilter"><% _t('BookingRequest.PLURALNAME','Reservationer') %></label>
				<input id="CancelledFilter" type="checkbox" onclick="SetFilters();" /><label for="CancelledFilter"><% _t('Booking.CANCELLED','Avbokningar') %></label>
				<input id="RejectedFilter"  type="checkbox" onclick="SetFilters();" /><label for="RejectedFilter"><% _t('Booking.REJECTED','Förkastade') %></label>
			</div>
		<% end_if %>
	</div>
	
	<div id="BookingCalendar"></div>
	
	<div id="Output" class="Output">
		<div id="AjaxLoader" class="AjaxLoader" style="display:none;">
			<img src="{$BaseHref}resourcebooking/images/ajax-loader-white.gif" alt="Loading in progress..." />
		</div>
		<div id="StatusMessage" class="Message StatusMessage" style="display:none;"></div>
		<div id="ErrorMessage" class="Message ErrorMessage" style="display:none;"></div>
	</div>
	
	<div id="Legend">
		<% if ColorLegend %>
			<ul>
				<% control ColorLegend %>
					<li>
						<div class="legendColor fc-event-vert" style="background-color: $backgroundColor; border-color: $borderColor;">
							<div class="fc-event-bg $type"></div>
							<% if type == booking %>
								<span class="fc-event-booking">
									<img alt="Recurring" src="resourcebooking/images/booking.png">
								</span>
							<% end_if %>
							<% if type == recurring %>
								<span class="fc-event-recurring">
									<img alt="Recurring" src="resourcebooking/images/recurring.gif">
								</span>
							<% end_if %>
						</div>
						<label>$label</label>
					</li>
				<% end_control %>
			</ul>
		<% end_if %>
	</div>
</div>

<script type="text/javascript">
	$CustomerOrganizationToGroupMapping
	$CustomerGroupToCustomerMapping
	$IsLoggedIn
	$IsAutocompleteSearchAllowed
</script>