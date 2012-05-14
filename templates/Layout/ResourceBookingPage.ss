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
				
	<% if Menu(2) %>
		</div>
	<% end_if %>
</div>

<% if ResourceBookingMember || ViewEventsWithoutLogin %>
	<script type="text/javascript">
		var resourceBookingMemberPage = '$getResourceBookingMemberPageLink';
		var resourceBookingAdminHref = '$Link';
		var changeURL = resourceBookingAdminHref + 'change';
	</script>
	<% include BookingCalendar %>
	
<% else %>
	<div id="MemberPageLogin">
		<% include ResourceBookingLoginOrRegistration %>
	</div>
<% end_if %>