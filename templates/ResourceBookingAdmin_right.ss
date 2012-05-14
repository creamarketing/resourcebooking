<script type="text/javascript">
	var resourceBookingAdminHref = '{$BaseHref}admin/resourcebooking/';
	var changeURL = resourceBookingAdminHref + 'change';
</script>

<% if view = default %>
	<div id="Dashboard">
		<h1><% _t('ResourceBookingAdmin.MENUTITLE','Resource booking') %></h1>
		
		<h2>Reserveringar</h2>
		<% if NewRequests %>
			<table>
				<tr>
					<th>Resurs</th>
					<th>Kund</th>
					<th>Boknings-id</th>
					<th>Starttid</th>
					<th>Sluttid</th>
					<th>Detaljer</th>
					<th>Kalender</th>
				</tr>
				<% control NewRequests %>
					<tr>
						<td>$Resource.Group.Organization - $Resource.Group - <% if Resource.ParentID %>$Resource.Parent.Name - <% end_if %>$Resource.Name</td>
						<td>$Member.Group.Organization - $Member.Group - $Member.FullName</td>
						<td>$BookingID</td>
						<td>$Start.Format(d.m.Y H:i)</td>
						<td>$End.Format(d.m.Y H:i)</td>
						<td><input type="button" onclick="ShowBookingEditDialog($ID, 'request');" value="Visa detaljer" /></td>
						<td><input type="button" onclick="document.location = '{$Top.Link}showresources?resource=$ResourceID&date=$StartDate';" value="Visa i kalender" /></td>
					</tr>
				<% end_control %>
			</table>
		<% else %>
			<p>Inga reserveringar</p>
		<% end_if %>
		
		<h2>Avbokningar</h2>
		<p>Inga avbokningar</p>
		
		<h2>Ansökningar</h2>
		<p>Inga ansökningar</p>
		
		<div id="CustomBookingForms" style="display:none;">
			<div id="BookingRequestEditDialog">
				$BookingRequestEditForm
			</div>
		</div>
		
		<script type="text/javascript">
			changeURL = resourceBookingAdminHref + 'changebookingrequest';
		</script>
		
	</div>

<% else_if view = showresources %>
	<% include BookingCalendar %>
	
	<script type="text/javascript">
		eventURL = resourceBookingAdminHref + 'resources';
		changeURL = resourceBookingAdminHref + 'changebookingrequest';
		gotoDate = "$SelectedDate";
		preselectedOrganizationID = $PreselectedOrganizationID;
		preselectedGroupID = $PreselectedGroupID;
		preselectedResourceID = $PreselectedResourceID;
		
		function CustomShowBookingDialog(calEvent){
			ShowBookingEditDialog(calEvent.id, calEvent.type);
		}
	</script>

<% else_if view = editbookings %>
	$EditBookingsForm
	
<% else_if view = editbookingrequests %>
	$EditBookingRequestsForm

<% else_if view = editorganizations %>
	$EditOrganizationsForm
	
<% else_if view = editgroups %>
	$EditGroupsForm

<% else_if view = editresources %>
	$EditResourcesForm

<% else_if view = editservices %>
	$EditServicesForm

<% else_if view = editbookingtypes %>
	$EditBookingTypesForm

<% else_if view = edituserorganizations %>
	$EditUserOrganizationsForm
	
<% else_if view = editusergroups %>
	$EditUserGroupsForm
	
<% else_if view = editusers %>
	$EditUsersForm

<% else_if view = editusertypes %>
	$EditUserTypesForm

<% else_if view = editpricegroups %>
	$EditPriceGroupsForm

<% else_if view = edittaxtypes %>
	$EditTaxTypesForm
	
<% else_if view = showbookingreport %>
	$BookingReport.ReportForm
	
<% else_if view = showinvoicereport %>
	$InvoiceReport.ReportForm

<% end_if %>