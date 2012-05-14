<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<style>
			<% if ConfirmationType = pdf %>
				@page {
					size: A4 portrait;
					margin: 2cm 1cm;
				
					@top-left {
						content: element(header);
					}
				
					@bottom-left {
				        content: element(footer);
				    }
				}
				#pagenumber:before {
					content: counter(page);
				}
				#pagecount:before {
					content: counter(pages);
				}
				#header {
					position: running(header);
				}
				#footer {
					position: running(footer);
				}
				.page {
					page-break-before: always;
				}
				.page.first {
					page-break-before: avoid;
				}
			<% else %>
				.page {
					padding: 25px 0 25px 20px;
				}
				.page.first {
					padding: 0 0 25px 20px;
				}
			<% end_if %>
			
			body {
				font-family: Arial, Helvetica, sans-serif;
				font-size: 14px;
			}
			.page {
				padding-left: 20px;
			}
			table {
				width: 700px;
			}
			#footer table {
				width: 720px;
			}
			td {
				text-align: left;
				vertical-align: top;
				width: 33%;
			}
			table.detail-table td,
			table.detail-table th {
				width: auto;
				border-bottom: 1px solid #aaa;
				font-size: 12px;
			}
			table.detail-table tfoot td {
				border-bottom: none;
			}
			tr.padded td,
			.padded {
				padding: 20px 20px 20px 20px;
			}
			tr.padded-top td,
			.padded-top {
				padding-top: 10px;
			}
			tr.padded-top-big td,
			.padded-top-big {
				padding-top: 20px;
			}
			td#upperright {
				line-height: 30px;
				font-size: 16px;
				font-weight: normal;
			}
			td#address {
				line-height: 20px;
			}
			.bold {
				font-weight: bold;
			}
			.line {
				border-bottom: 1px solid #aaa;
			}
			.dark-line {
				border-bottom: 1px solid #000;
			}
			.right {
				text-align: right;
			}
			.small-text {
				font-size: 12px;
			}
			hr {
				height: 1px;
				border: none;
				color: #000;
				background-color: #000;
			}
		</style>
		
		<title><% _t("Confirmation.TITLE","Bokningsbekräftelse") %></title>
	</head>
	
	<body>
		
		<div id="header">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<img src="{$BaseHref}resourcebooking/images/Korsholmskommun_logo_web.jpg" alt="Korsholms kommun / Mustasaaren kunta" />
					</td>
					<td></td>
					<td id="upperright" rowspan="3">
						<% _t("Confirmation.TITLE","Bokningsbekräftelse") %><br />
						$Today
					</td>
				</tr>
			</table>
		</div>
		
		<% if ConfirmationType = pdf %>
			<div id="footer">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="line" colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="2">$Booking.Resource.Name</td>
						<td class="right"><% _t("Confirmation.PAGE","Sida") %> <span id="pagenumber"></span> <% _t("Confirmation.OF","av") %> <span id="pagecount"></span></td>
					</tr>
					<tr>
						<td colspan="2">$Booking.Resource.Address</td>
						<td></td>
					</tr>
				</table>
			</div>
		<% end_if %>
		
		<%-- First page --%>
		<div class="page first">
			<table cellpadding="0" cellspacing="0">
				<tr class="padded bold">
					<td id="address">$Booking.Customer.BillingAddress</td>
					<td></td>
					<td></td>
				</tr>
				
				<tr>
					<td><% _t("Confirmation.BOOKINGID","Bokningsnummer") %></td>
					<td class="bold" colspan="2">$Booking.BookingID</td>
				</tr>
				<tr>
					<td><% _t("Confirmation.CREATEDDATE","Beställningsdatum") %></td>
					<td class="bold" colspan="2">$Created</td>
				</tr>
				<tr>
					<td><% _t("Confirmation.MEMBER","Betsällare") %></td>
					<td class="bold" colspan="2">$Booking.Customer.FullName</td>
				</tr>
				<tr>
					<td><% _t("Confirmation.STARTEND","Bokningstid") %></td>
					<td class="bold" colspan="2">$Start - $End</td>
				</tr>
				<tr>
					<td><% _t("Confirmation.BOOKINGMEMBER","Bokare") %></td>
					<td class="bold" colspan="2">$BookingMember.getName ($BookingMember.Email)</td>
				</tr>
				
				<tr>
					<td colspan="3"><hr /></td>
				</tr>
				
				<% if AcceptedBookings %>
					<tr>
						<td><% _t("Confirmation.APPENDIX","Bilaga") %> 1</td>
						<td colspan="2"><% _t("Confirmation.CONFIRMEDBOOKINGS","Godkända bokningar") %></td>
					</tr>
				<% end_if %>
				<% if RejectedBookings %>
					<tr>
						<td><% _t("Confirmation.APPENDIX","Bilaga") %> 2</td>
						<td colspan="2"><% _t("Confirmation.REJECTEDBOOKINGS","Ej godkända bokningar") %></td>
					</tr>
				<% end_if %>
				<% if CancelledBookings %>
					<tr>
						<td><% _t("Confirmation.APPENDIX","Bilaga") %> 3</td>
						<td colspan="2"><% _t("Confirmation.CANCELLEDBOOKINGS","Avbokade tider") %></td>
					</tr>
				<% end_if %>
				
				<% if ExtraText %>
					<tr class="padded-top-big">
						<td colspan="3">
							$ExtraText
						</td>
					</tr>
				<% end_if %>
				
				<tr class="padded">
					<td colspan="3">&nbsp;</td>
				</tr>
				
				<% if ConfirmationType = pdf %>
					<tr class="small-text">
						<td colspan="2"><% _t("Confirmation.CONFIRMPDFTEXT","Reserveringen av Er tur träder i kraft endast om denna bekräftelse returneras underteckad senast") %>:</td>
						<td class="padded bold"><span class="line">$Booking.NiceConfirmBefore</span></td>
					</tr>
					<tr class="small-text padded-top">
						<td><% _t("Confirmation.BILLINGADDRESS","Faktureringsaddress (om annan än ovan)") %>:</td>
						<td class="dark-line" colspan="2">&nbsp;</td>
					</tr>
					<tr class="small-text padded-top">
						<td>&nbsp;</td>
						<td class="dark-line" colspan="2">&nbsp;</td>
					</tr>
					<tr class="small-text padded-top-big">
						<td><% _t("Confirmation.NAMESIGNED","Underskrift") %>:</td>
						<td class="dark-line" colspan="2">&nbsp;</td>
					</tr>
					<tr class="small-text padded-top">
						<td><% _t("Confirmation.NAMECLEARTEXT","Namn i klartext") %>:</td>
						<td class="dark-line" colspan="2">&nbsp;</td>
					</tr>
					<tr class="small-text padded-top">
						<td><% _t("Confirmation.TELANDMAIL","Telefon, E-post") %>:</td>
						<td class="dark-line" colspan="2">&nbsp;</td>
					</tr>
				<% else %>
					<tr class="small-text">
						<td colspan="2"><% _t("Confirmation.CONFIRMTEXT","Reserveringen av Er tur träder i kraft endast om denna bekräftelse bekräftas via nedanstående länk senast") %>:</td>
						<td class="padded bold"><span class="line">$Booking.NiceConfirmBefore</span></td>
					</tr>
					<tr>
						<td class="bold" colspan="3"><a href="{$ConfirmPageLink}{$ConfirmHash}/accept"><% _t("Confirmation.CONFIRM","Bekräfta") %></a></td>
					</tr>
					<tr>
						<td class="bold" colspan="3"><a href="{$ConfirmPageLink}{$ConfirmHash}/reject"><% _t("Confirmation.REJECT","Förkasta") %></a></td>
					</tr>
				<% end_if %>
			</table>
		</div>
		
		<%-- Appendix 1 --%>
		<% if AcceptedBookings %>
			<div class="page">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="bold"><% _t("Confirmation.APPENDIX","Bilaga") %> 1</td>
						<td class="bold" colspan="2"><% _t("Confirmation.CONFIRMEDBOOKINGS","Godkända bokningar") %></td>
					</tr>
					<tr>
						<td class="padded-top" colspan="3">
							<table cellpadding="0" cellspacing="0" class="detail-table">
								<thead>
									<tr>
										<th><% _t("Confirmation.START","Start") %></th>
										<th><% _t("Confirmation.END","Slut") %></th>
										<th><% _t("Confirmation.RESOURCE","Utrymme") %></th>
										<th><% _t("Confirmation.TEXT","Text") %></th>
										<th><% _t("Confirmation.PRICE","Pris") %></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="3"></td>
										<td><% _t("Confirmation.TOTAL","Totalt") %>:</td>
										<td>$AcceptedBookingsTotalPrice €</td>
									</tr>
								</tfoot>
								<tbody>
									<% control AcceptedBookings %>
										<tr>
											<td>$NiceStartTime</td>
											<td>$NiceEndTime</td>
											<td>$Resource.Name</td>
											<td><% if BookingText %>$BookingText<% else %>&nbsp;<% end_if %></td>
											<td>$Price €</td>
										</tr>
									<% end_control %>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
		<% end_if %>
		
		<%-- Appendix 2 --%>
		<% if RejectedBookings %>
			<div class="page">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="bold"><% _t("Confirmation.APPENDIX","Bilaga") %> 2</td>
						<td class="bold" colspan="2"><% _t("Confirmation.REJECTEDBOOKINGS","Ej godkända bokningar") %></td>
					</tr>
					<tr>
						<td class="padded-top" colspan="3">
							<table cellpadding="0" cellspacing="0" class="detail-table">
								<thead>
									<tr>
										<th><% _t("Confirmation.START","Start") %></th>
										<th><% _t("Confirmation.END","Slut") %></th>
										<th><% _t("Confirmation.RESOURCE","Utrymme") %></th>
										<th><% _t("Confirmation.TEXT","Text") %></th>
										<th><% _t("Confirmation.PRICE","Pris") %></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="3"></td>
										<td><% _t("Confirmation.TOTAL","Totalt") %>:</td>
										<td>$RejectedBookingsTotalPrice €</td>
									</tr>
								</tfoot>
								<tbody>
									<% control RejectedBookings %>
										<tr>
											<td>$NiceStartTime</td>
											<td>$NiceEndTime</td>
											<td>$Resource.Name</td>
											<td><% if BookingText %>$BookingText<% else %>&nbsp;<% end_if %></td>
											<td>0 €</td>
										</tr>
									<% end_control %>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
		<% end_if %>
		
		<%-- Appendix 3 --%>
		<% if CancelledBookings %>
			<div class="page">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="bold"><% _t("Confirmation.APPENDIX","Bilaga") %> 3</td>
						<td class="bold" colspan="2"><% _t("Confirmation.CANCELLEDBOOKINGS","Avbokade tider") %></td>
					</tr>
					<tr>
						<td class="padded-top" colspan="3">
							<table cellpadding="0" cellspacing="0" class="detail-table">
								<thead>
									<tr>
										<th><% _t("Confirmation.START","Start") %></th>
										<th><% _t("Confirmation.END","Slut") %></th>
										<th><% _t("Confirmation.RESOURCE","Utrymme") %></th>
										<th><% _t("Confirmation.TEXT","Text") %></th>
										<th><% _t("Confirmation.PRICE","Pris") %></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="3"></td>
										<td><% _t("Confirmation.TOTAL","Totalt") %>:</td>
										<td>$CancelledBookingsTotalPrice €</td>
									</tr>
								</tfoot>
								<tbody>
									<% control CancelledBookings %>
										<tr>
											<td>$NiceStartTime</td>
											<td>$NiceEndTime</td>
											<td>$Resource.Name</td>
											<td><% if BookingText %>$BookingText<% else %>&nbsp;<% end_if %></td>
											<td>0 €</td>
										</tr>
									<% end_control %>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
		<% end_if %>
		
		<% if ConfirmationType != pdf %>
			<div id="footer">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td class="line" colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="3">$Booking.Resource.Name</td>
					</tr>
					<tr>
						<td colspan="2">$Booking.Resource.Address</td>
						<td></td>
					</tr>
				</table>
			</div>
		<% end_if %>
		
	</body>
</html>
