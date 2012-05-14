<div class="right bold big">
	Provisionsfaktura<br />
	<% if Start || End %>
		Period:  $Start - $End
	<% end_if %>
</div>

<div class="clear"></div>

<% if InvoiceRows %>
	<table>
		<tr>
			<th>Mottagare, inkomstkonto</th>
			<th>Utrymme, utgiftskonto</th>
			<th>Int. fakt.</th>
			<th>Ext. fakt.</th>
			<th>Totalt</th>
			<th>Provision</th>
		</tr>
		<% control InvoiceRows %>
			<tr class="underlined">
				<td colspan="6">$Name</td>
			</tr>
			<% control Groups %>
				<tr class="hoverable" rel="$ID">
					<td>&nbsp;</td>
					<td>$Name</td>
					<td>$InternalPrice</td>
					<td>$ExternalPrice</td>
					<td>$TotalPrice</td>
					<td>$Provision</td>
				</tr>
			<% end_control %>
			<tr class="overlined bold">
				<td>Totalt</td>
				<td>&nbsp;</td>
				<td>$TotalInternal</td>
				<td>$TotalExternal</td>
				<td>$TotalTotal</td>
				<td>$TotalProvision</td>
			</tr>
		<% end_control %>
	</table>
<% else %>
	<p>Inga fakturarader funna...</p>
<% end_if %>