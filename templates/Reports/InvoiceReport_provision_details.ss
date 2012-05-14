<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<style>
			@page {
				size: A4 $Orientation;
				margin: 2cm 1cm;
			}
			
			* {
				margin: 0;
				padding: 0;
			}
		</style>
		
		<title>Detaljer för fakturarad</title>
	</head>
	
	<body>
		
		<div class="left">
			<label class="left bold" style="padding-right: 15px;">Plats</label> <label class="left">$ResourceGroup</label>
		</div>
		
		<div class="clear"></div>

		<table>
			<tr>
				<th>Utrymme</th>
				<th>Boknings id</th>
				<th>Bokningstyp</th>
				<th>Tid</th>
				<th>Osubv. pris netto</th>
				<th>Pris netto</th>
				<th>Pris brutto</th>
				<th>moms %</th>
				<th>Kund</th>
			</tr>
			<% control Bookings %>
				<tr>
					<td>$Resource</td>
					<td>$BookingID</td>
					<td>$Type</td>
					<td>$Time</td>
					<td>$UnsubsidizedPrice</td>
					<td>$PriceExclTax</td>
					<td>$PriceInclTax</td>
					<td>$Tax</td>
					<td>$Contact</td>
				</tr>
			<% end_control %>
		</table>
		
	</body>
	
</html>