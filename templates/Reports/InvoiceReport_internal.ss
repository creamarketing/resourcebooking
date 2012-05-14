<div class="right bold big">
	Intern fakturering<br />
	<% if Start || End %>
		Period:  $Start - $End
	<% end_if %>
</div>

<div class="clear"></div>

<% if InvoiceRows %>
	<table>
		<tr>
			<th>Betalare</th>
			<th>Utrymme</th>
			<th>Kund</th>
			<th>Tot. pris</th>
			<th>Fakt. pris</th>
			<th>Subv. andel</th>
		</tr>
		<% control InvoiceRows %>
			<tr class="underlined">
				<td colspan="6">$Name</td>
			</tr>
			<% control Groups %>
				<tr>
					<td>&nbsp;</td>
					<td>$Name</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<% control Customers %>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>$Name</td>
						<td>$TotalPrice</td>
						<td>$ExternalPrice</td>
						<td>$InternalPrice</td>
					</tr>
				<% end_control %>
				<tr class="bold">
					<td>&nbsp;</td>
					<td class="overlined">Totalt</td>
					<td class="overlined">&nbsp;</td>
					<td class="overlined">&nbsp;</td>
					<td class="overlined">&nbsp;</td>
					<td class="overlined">$Total</td>
				</tr>
				<tr class="empty">
					<td colspan="6">&nbsp;</td>
				</tr>
			<% end_control %>
			<tr class="underlined bold">
				<td>Totalt</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>$Total</td>
			</tr>
		<% end_control %>
	</table>
<% else %>
	<p>Inga fakturarader funna...</p>
<% end_if %>