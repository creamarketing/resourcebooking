<div class="right bold big">
	Fakturaunderlag (extern faktura)<br />
	<% if Start || End %>
		Period:  $Start - $End
	<% end_if %>
</div>

<div class="clear"></div>

<% if InvoiceRows %>
	<% control InvoiceRows %>
		<div class="Page <% if IsFirst %>first<% end_if %>">
			<div class="left">
				<label class="left bold" style="padding-right: 15px;">Fakturamottagare</label> <label class="left">$Address</label>
			</div>
			
			<div class="clear"></div>
			
			<table>
				<tr>
					<th>Utrymme</th>
					<th>Bokningstyp</th>
					<th>Bokningsid</th>
					<th>Timmar</th>
					<th>Osubv. pris netto</th>
					<th>Pris netto</th>
					<th>Pris brutto</th>
					<th>Budgetkonto</th>
					<th>moms %</th>
					<th>Kund</th>
				</tr>
				<% control Bookings %>
					<tr class="hoverable" rel="$ID">
						<td>$Resource</td>
						<td>$Type</td>
						<td>$BookingID</td>
						<td>$Hours</td>
						<td>$UnsubsidizedPrice</td>
						<td>$PriceExclTax</td>
						<td>$PriceInclTax</td>
						<td>$Account</td>
						<td>$Tax</td>
						<td>$Contact</td>
					</tr>
				<% end_control %>
			</table>
		</div>
	<% end_control %>
<% else %>
	<p>Inga fakturarader funna...</p>
<% end_if %>