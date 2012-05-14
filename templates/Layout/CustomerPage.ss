<div class="typography">
	<% if Menu(2) %>
		<% include SideBar %>
		<div id="Content">
	<% end_if %>

	<% if Level(2) %>
	  	<% include BreadCrumbs %>
	<% end_if %>
	
		<h2>$Title</h2>
	
		$Content
		
		<% if ResourceBookingMember %>
			<% if view = default %>
				<div id="Output">
					<div id="AjaxLoader" style="display:none;">
						<img src="{$BaseHref}resourcebooking/images/ajax-loader-white.gif" alt="Loading in progress..." />
					</div>
					<div id="StatusMessage" class="Message" style="display:none;"></div>
					<div id="ErrorMessage" class="Message" style="display:none;"></div>
				</div>
				<% if MemberBookings %>
					<table id="BookingTable">
						<tr>
							<th><% _t('Booking.START','Start') %></th>
							<th><% _t('Booking.END','End') %></th>
							<th><% _t('Resource.SINGULARNAME','Resource') %></th>
							<th><% _t('Booking.MOVE','Move') %></th>
							<th><% _t('Booking.DELETE','Delete') %></th>
						</tr>
						<% control MemberBookings %>
							<tr>
								<td>$Start.Format(d.m.Y H:i)</td>
								<td>$End.Format(d.m.Y H:i)</td>
								<td>$Resource.Name</td>
								<td>
									<% if canEdit %>
										<input id="Move{$ID}" type="button" value="<% _t('Booking.MOVE','Move') %>" onclick="MoveBooking($ID);" />
									<% else %>
										-
									<% end_if %>
								</td>
								<td>
									<% if canDelete %>
										<input id="Delete{$ID}" type="button" value="<% _t('Booking.DELETE','Delete') %>" onclick="ShowDeleteConfirmation($ID);" />
									<% else %>
										-
									<% end_if %>
								</td>
							</tr>
						<% end_control %>
					</table>
				<% else %>
					No bookings found.
				<% end_if %>
				
				<div style="display:none;">
					<form id="BookingDeleteForm" method="post" enctype="application/x-www-form-urlencoded" action="{$Link}DeleteBooking">
						<input id="DeleteBookingID" name="BookingID" type="hidden" value="" />
						<div><% _t('ResourceBookingMemberPage.DELETECONFIRMATION','Really delete booking?') %></div>
					</form>
				</div>
			
			<% else_if view = movebooking %>
				<div id="MoveBookingDescription">
					<p><% _t('ResourceBookingMemberPage.MOVEBOOKINGTEXT','Select a new time to move booking.') %></p>
					<input type="button" value="<% _t('ResourceBookingMemberPage.CANCEL','Cancel') %>" onclick="window.location = '$Link';" />
				</div>
				
			<% end_if %>
			
		<% end_if %> 
				
	<% if Menu(2) %>
		</div>
	<% end_if %>
</div>

<% if ResourceBookingMember %>
	<% if view = movebooking %>
		<% include BookingCalendar %>
		
		<script type="text/javascript">
			var resourceBookingAdminHref = '$Link';
			jQuery('#ResourceName').html('$CurrentResource');
			jQuery('#ResourceGroupName').html('$CurrentGroup');
			jQuery('#ResourceOrganizationName').html('$CurrentOrganization');
			
			function HandleBookingSuccess(){
				window.location = '$Link';
			}
		</script>
	<% end_if %>
<% else %>
	<div id="MemberPageLogin">
		<% include ResourceBookingLoginOrRegistration %>
	</div>
<% end_if %>