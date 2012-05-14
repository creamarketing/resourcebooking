<% if Resource %>
	<p><b>Resurs:</b> $Resource.Name</p>
<% end_if %>
<% if CustomerGroup %>
	<p><b>Kund:</b> $CustomerGroup.Name</p>
<% end_if %>
<% if Start || End %>
	<p><b>Period:</b> $Start - $End</p>
<% end_if %>
<% if Bookings %>
	<table>
		<tr>
			<th>Kund</th>
			<th>Utrymme</th>
			<th>Tid</th>
			<th>Bokningstyp</th>
			<th>Status</th>
		</tr>
		<% control Bookings %>
			<tr>
				<td>$Customer.Group.Name - $Customer.FullName</td>
				<td>$Resource.FullName</td>
				<td>$NiceStartTime - $NiceEndTime</td>
				<td>$Type.Name</td>
				<td>$getBookingStatus</td>
			</tr>
		<% end_control %>
	</table>
<% else %>
	<p>Inga bokningar funna...</p>
<% end_if %>