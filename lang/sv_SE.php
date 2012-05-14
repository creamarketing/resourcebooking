<?php

i18n::include_locale_file('resourcebooking', 'en_US');

global $lang;

if(array_key_exists('sv_SE', $lang) && is_array($lang['sv_SE'])) {
    $lang['sv_SE'] = array_merge($lang['en_US'], $lang['sv_SE']);
} else {
    $lang['sv_SE'] = $lang['en_US'];
}

$lang['sv_SE']['ResourceBooking']['EDITED'] = 'redigerad';
$lang['sv_SE']['ResourceBooking']['NOTFOUND'] = 'hittades inte';
$lang['sv_SE']['ResourceBooking']['BOOKED'] = 'bokad';
$lang['sv_SE']['ResourceBooking']['FREERESOURCES'] = 'Lediga resurser';
$lang['sv_SE']['ResourceBooking']['PLEASECHOOSE'] = 'Välj...';
$lang['sv_SE']['ResourceBooking']['ALL'] = 'Alla';
$lang['sv_SE']['ResourceBooking']['NONESELECTED'] = '(Ingen vald)';
$lang['sv_SE']['AdvancedDropdownField']['NONESELECTED'] = '(Ingen vald)';
$lang['sv_SE']['ResourceBooking']['APIACCESSALLOWED'] = 'Extern åtkomst tillåten';

$lang['sv_SE']['Resource']['SINGULARNAME'] = 'Resurs';
$lang['sv_SE']['Resource']['PLURALNAME'] = 'Resurser';
$lang['sv_SE']['Resource']['AVAILABLEFROM'] = 'Tillgänglig från';
$lang['sv_SE']['Resource']['AVAILABLETO'] = 'Tillgänglig till';
$lang['sv_SE']['Resource']['MEMBER'] = 'Anställd';
$lang['sv_SE']['Resource']['EXTRAWORK'] = 'Extra arbete';
$lang['sv_SE']['Resource']['FREETIME'] = 'Ledig';
$lang['sv_SE']['Resource']['STANDARDHOURS'] = 'Standardtimmar';
$lang['sv_SE']['Resource']['AVAILABLE'] = 'Tillgänglig';
$lang['sv_SE']['Resource']['TOTALHOURS'] = 'Timmar totalt';
$lang['sv_SE']['Resource']['NAME'] = 'Namn';
$lang['sv_SE']['Resource']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['Resource']['GROUP'] = 'Grupp';
$lang['sv_SE']['Resource']['ORGANIZATION'] = 'Organisation';
$lang['sv_SE']['Resource']['PARENT'] = 'Huvudresurs';
$lang['sv_SE']['Resource']['RESOURCES'] = 'Underresurser';
$lang['sv_SE']['Resource']['PRICEGROUPS'] = 'Subventionering';
$lang['sv_SE']['Resource']['SERVIES'] = 'Tjänster';
$lang['sv_SE']['Resource']['RESOURCEGROUP'] = 'Grupperad resurs';
$lang['sv_SE']['Resource']['POSTADDRESS'] = 'Näradress';
$lang['sv_SE']['Resource']['POSTCODE'] = 'Postnummer';
$lang['sv_SE']['Resource']['POSTOFFICE'] = 'Postanstalt';

$lang['sv_SE']['ResourceOrganization']['SINGULARNAME'] = 'Organisation';
$lang['sv_SE']['ResourceOrganization']['PLURALNAME'] = 'Organisationer';
$lang['sv_SE']['ResourceOrganization']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['ResourceOrganization']['NAME'] = 'Namn';
$lang['sv_SE']['ResourceOrganization']['GROUPS'] = 'Grupper';

$lang['sv_SE']['BookingLogItem']['CREATED'] = 'Skapad';
$lang['sv_SE']['BookingLogItem']['EDITED'] = 'Editerad, ändrade fält: ';

$lang['sv_SE']['ResourceGroup']['SINGULARNAME'] = 'Grupp';
$lang['sv_SE']['ResourceGroup']['PLURALNAME'] = 'Grupper';
$lang['sv_SE']['ResourceGroup']['NAME'] = 'Namn';
$lang['sv_SE']['ResourceGroup']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['ResourceGroup']['ORGANIZATION'] = 'Organisation';
$lang['sv_SE']['ResourceGroup']['RESOURCES'] = 'Resurser';

$lang['sv_SE']['Service']['SINGULARNAME'] = 'Tjänst';
$lang['sv_SE']['Service']['PLURALNAME'] = 'Tjänster';
$lang['sv_SE']['Service']['NAME'] = 'Namn';

$lang['sv_SE']['Customer']['SINGULARNAME'] = 'Kontaktperson';
$lang['sv_SE']['Customer']['PLURALNAME'] = 'Kontaktpersoner';
$lang['sv_SE']['Customer']['FIRSTNAME'] = 'Förnamn';
$lang['sv_SE']['Customer']['LASTNAME'] = 'Efternamn';
$lang['sv_SE']['Customer']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['Customer']['GROUP'] = 'Avdelning/grupp';
$lang['sv_SE']['Customer']['ORGANIZATION'] = 'Organisation';
$lang['sv_SE']['Customer']['EXTERNALRESOURCES'] = 'Extern åtkomst';

$lang['sv_SE']['CustomerGroup']['SINGULARNAME'] = 'Avdelning/grupp';
$lang['sv_SE']['CustomerGroup']['PLURALNAME'] = 'Avdelningar/grupper';
$lang['sv_SE']['CustomerGroup']['NAME'] = 'Namn';
$lang['sv_SE']['CustomerGroup']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['CustomerGroup']['ORGANIZATION'] = 'Organisation';
$lang['sv_SE']['CustomerGroup']['CUSTOMERS'] = 'Kontaktpersoner';
$lang['sv_SE']['CustomerGroup']['SUBGROUPS'] = 'Undergrupper';
$lang['sv_SE']['CustomerGroup']['PARENT'] = 'Huvudgrupp';

$lang['sv_SE']['CustomerOrganization']['SINGULARNAME'] = 'Organisation';
$lang['sv_SE']['CustomerOrganization']['PLURALNAME'] = 'Organisationer';
$lang['sv_SE']['CustomerOrganization']['NAME'] = 'Namn';
$lang['sv_SE']['CustomerOrganization']['GENERAL'] = 'Allmänt';
$lang['sv_SE']['CustomerOrganization']['GROUPS'] = 'Avdelningar/grupper';

$lang['sv_SE']['CustomerType']['SINGULARNAME'] = 'Åldersgrupp';
$lang['sv_SE']['CustomerType']['PLURALNAME'] = 'Åldersgrupper';
$lang['sv_SE']['CustomerType']['NAME'] = 'Namn';

$lang['sv_SE']['PriceGroup']['SINGULARNAME'] = 'Prisgrupp';
$lang['sv_SE']['PriceGroup']['PLURALNAME'] = 'Prisgrupper';
$lang['sv_SE']['PriceGroup']['NAME'] = 'Namn';

$lang['sv_SE']['BookingRequest']['SINGULARNAME'] = 'Reservation';
$lang['sv_SE']['BookingRequest']['PLURALNAME'] = 'Reservationer';

$lang['sv_SE']['Booking']['SINGULARNAME'] = 'Bokning';
$lang['sv_SE']['Booking']['PLURALNAME'] = 'Bokningar';
$lang['sv_SE']['Booking']['STARTDATE'] = 'Startdatum';
$lang['sv_SE']['Booking']['START'] = 'Start';
$lang['sv_SE']['Booking']['END'] = 'Slut';
$lang['sv_SE']['Booking']['STARTTIME'] = 'Starttid';
$lang['sv_SE']['Booking']['ENDTIME'] = 'Sluttid';
$lang['sv_SE']['Booking']['MOVE'] = 'Flytta';
$lang['sv_SE']['Booking']['DELETE'] = 'Avboka';
$lang['sv_SE']['Booking']['PAYMENT'] = 'Betalning';
$lang['sv_SE']['Booking']['PAY'] = 'Betala';
$lang['sv_SE']['Booking']['PAID'] = 'Betald';
$lang['sv_SE']['Booking']['NOTPAID'] = 'Inte betald';
$lang['sv_SE']['Booking']['PAYMENTSUCCESS'] = 'Betalningen av bokning %s lyckades.';
$lang['sv_SE']['Booking']['PAYMENTFAILED'] = 'Betalningen av bokning %s misslyckades!';
$lang['sv_SE']['Booking']['PAYMENTCANCELED'] = 'Betalningen av bokning %s avbröts av användaren.';
$lang['sv_SE']['Booking']['TYPEID'] = 'Bokningstyp';
$lang['sv_SE']['Booking']['MEMBERID'] = 'Kund';
$lang['sv_SE']['Booking']['BILLINGDETAILSID'] = 'Fakturering';
$lang['sv_SE']['Booking']['Pending'] = 'Obekräftad';
$lang['sv_SE']['Booking']['Preliminary'] = 'Preliminär';
$lang['sv_SE']['Booking']['Accepted'] = 'Godkänd';
$lang['sv_SE']['Booking']['Rejected'] = 'Förkastad';
$lang['sv_SE']['Booking']['Cancelled'] = 'Avbokad';
$lang['sv_SE']['Booking']['INTERNALPAID'] = 'Faktureringsstatus (intern faktura)';
$lang['sv_SE']['Booking']['INTERNALPAYMENTDATE'] = 'Faktureringsdatum (intern faktura)';

$lang['sv_SE']['BookingType']['SINGULARNAME'] = 'Bokningstyp';
$lang['sv_SE']['BookingType']['PLURALNAME'] = 'Bokningstyper';
$lang['sv_SE']['BookingType']['NAME'] = 'Namn';
$lang['sv_SE']['BookingType']['TAX'] = 'Moms %';
$lang['sv_SE']['BookingType']['COLOR'] = 'Färg';

$lang['sv_SE']['ResourceBookingMemberProfile']['SINGULARNAME'] = 'Profil';
$lang['sv_SE']['ResourceBookingMemberProfile']['PLURALNAME'] = 'Profiler';
$lang['sv_SE']['ResourceBookingMemberProfile']['ADDPROFILE'] = 'Lägg till profil';
$lang['sv_SE']['ResourceBookingMemberProfile']['FIRSTNAME'] = 'Förnamn';
$lang['sv_SE']['ResourceBookingMemberProfile']['LASTNAME'] = 'Efternamn';
$lang['sv_SE']['ResourceBookingMemberProfile']['TELNR'] = 'Tel.';
$lang['sv_SE']['ResourceBookingMemberProfile']['EMAIL'] = 'E-post';
$lang['sv_SE']['ResourceBookingMemberProfile']['ADDRESS'] = 'Adress';
$lang['sv_SE']['ResourceBookingMemberProfile']['ACCEPTSPECIALOFFERS'] = 'Acceptera erbjudanden per e-post';

$lang['sv_SE']['ResourceBookingMemberPage']['MOVEBOOKINGTEXT'] = 'Välj en ny tid nedan för att flytta din bokning.';
$lang['sv_SE']['ResourceBookingMemberPage']['CANCEL'] = 'Avbryt';
$lang['sv_SE']['ResourceBookingMemberPage']['DELETECONFIRMATION'] = 'Är du säker att du vill avboka din bokning?';

$lang['sv_SE']['ResourceBookingAdmin']['MENUTITLE'] = 'Resursbokning';
$lang['sv_SE']['ResourceBookingAdmin']['SHOWFREERESOURCES'] = 'Lediga tider';
$lang['sv_SE']['ResourceBookingAdmin']['SHOWBOOKINGS'] = 'Bokningar';
$lang['sv_SE']['ResourceBookingAdmin']['SHOWBOOKINGREQUESTS'] = 'Reservationer';
$lang['sv_SE']['ResourceBookingAdmin']['EDITWORKINGHOURS'] = 'Redigera arbetstid';
$lang['sv_SE']['ResourceBookingAdmin']['EDITRESOURCES'] = 'Resurser';
$lang['sv_SE']['ResourceBookingAdmin']['EDITRESOURCEGROUPS'] = 'Grupper';
$lang['sv_SE']['ResourceBookingAdmin']['EDITRESOURCEORGANIZATIONS'] = 'Organisationer';
$lang['sv_SE']['ResourceBookingAdmin']['EDITSERVICES'] = 'Tjänster';
$lang['sv_SE']['ResourceBookingAdmin']['EDITBOOKINGTYPES'] = 'Bokningstyper';
$lang['sv_SE']['ResourceBookingAdmin']['MANAGEUSERS'] = 'Kundhantering';
$lang['sv_SE']['ResourceBookingAdmin']['VIEWREPORTS'] = 'Rapporter';
$lang['sv_SE']['ResourceBookingAdmin']['WELCOMEMESSAGE'] = 'Välkommen till resurbokning!<br />Vänligen välj önskad funktion till vänster.';
$lang['sv_SE']['ResourceBookingAdmin']['SENDMESSAGE'] = 'Skicka meddelande';
$lang['sv_SE']['ResourceBookingAdmin']['SUBJECT'] = 'Rubrik';
$lang['sv_SE']['ResourceBookingAdmin']['MESSAGE'] = 'Meddelande';
$lang['sv_SE']['ResourceBookingAdmin']['SENDTO'] = 'Skicka till';
$lang['sv_SE']['ResourceBookingAdmin']['ALLWHOACCEPT'] = 'Alla som godkänner';
$lang['sv_SE']['ResourceBookingAdmin']['SPECIFICUSERS'] = 'Specifika användare';
$lang['sv_SE']['ResourceBookingAdmin']['SELECTEDUSERS'] = 'Valda användare';
$lang['sv_SE']['ResourceBookingAdmin']['SEND'] = 'Skicka';
$lang['sv_SE']['ResourceBookingAdmin']['TYPE'] = 'Typ';
$lang['sv_SE']['ResourceBookingAdmin']['ACTION'] = 'Åtgärd';
$lang['sv_SE']['ResourceBookingAdmin']['ACCEPT'] = 'Godkänn';
$lang['sv_SE']['ResourceBookingAdmin']['REJECT'] = 'Förkasta';

$lang['sv_SE']['BookingCalendar']['REFRESH'] = 'Uppdatera';
$lang['sv_SE']['BookingCalendar']['LOGIN'] = 'Logga in';
$lang['sv_SE']['BookingCalendar']['REGISTER'] = 'Registrera dig';

$lang['sv_SE']['ResourceBookingReports']['BOOKINGSPERRESOURCEREPORT'] = 'Bokningar per resurs';
$lang['sv_SE']['ResourceBookingReports']['BOOKINGSPERSERVICEREPORT'] = 'Bokningar per tjänst';
$lang['sv_SE']['ResourceBookingReports']['TOTALBOOKINGS'] = 'Bokningar totalt';
$lang['sv_SE']['ResourceBookingReports']['TOTALDURATION'] = 'Total tid';
$lang['sv_SE']['ResourceBookingReports']['TOTALINCOME'] = 'Total inkomst';
$lang['sv_SE']['ResourceBookingReports']['START'] = 'Startdatum';
$lang['sv_SE']['ResourceBookingReports']['END'] = 'Slutdatum';

$lang['sv_SE']['Confirmation']['TITLE'] = 'Bokningsbekräftelse';

$lang['sv_SE']['ResourceBookingReport']['STARTDATE'] = 'Startdatum';
$lang['sv_SE']['ResourceBookingReport']['ENDDATE'] = 'Slutdatum';
$lang['sv_SE']['ResourceBookingReport']['GENERATEREPORT'] = 'Generera rapport';
$lang['sv_SE']['ResourceBookingReport']['SAVEPDF'] = 'Spara PDF';

$lang['sv_SE']['BookingReport']['NAME'] = 'Bokningsrapport';

$lang['sv_SE']['InvoiceReport']['NAME'] = 'Fakturarapport';

?>