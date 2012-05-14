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
		
		<% if confirmation %>
			<h4>Details</h4>
			<p id="details">
				<b>Booking-id:</b> $confirmation.BookingID<br />
				<b>Sent:</b> $confirmation.Sent<br />
				<b>Due date:</b> $confirmation.Due <br />
				<% if confirmation.Accepted %>
					<b>Accepted:</b> $confirmation.Accepted <br />
				<% end_if %>
				<% if confirmation.Rejected %>
					<b>Rejected:</b> $confirmation.Rejected <br />
				<% end_if %>
			</p>
		<% end_if %>
		
		<% if error %>
			<h4>Error</h4>
			<p id="error">$error</p>
		<% end_if %>
		
		<% if result %>
			<h4>Result</h4>
			<p id="result">$result</p>
		<% end_if %>
		
	<% if Menu(2) %>
		</div>
	<% end_if %>
</div>