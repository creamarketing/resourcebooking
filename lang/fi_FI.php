<?php

i18n::include_locale_file('resourcebooking', 'en_US');

global $lang;

if(array_key_exists('fi_FI', $lang) && is_array($lang['fi_FI'])) {
    $lang['fi_FI'] = array_merge($lang['en_US'], $lang['fi_FI']);
} else {
    $lang['fi_FI'] = $lang['en_US'];
}

$lang['fi_FI']['ResourceBooking']['EDITED'] = 'muokattu';
$lang['fi_FI']['ResourceBooking']['NOTFOUND'] = 'ei löytynyt';
$lang['fi_FI']['ResourceBooking']['BOOKED'] = 'varattu';
$lang['fi_FI']['ResourceBooking']['FREERESOURCES'] = 'Vapaat resurssit';
$lang['fi_FI']['ResourceBooking']['PLEASECHOOSE'] = 'Valitse...';
$lang['fi_FI']['ResourceBooking']['ALL'] = 'Kaikki';
$lang['fi_FI']['ResourceBooking']['NONESELECTED'] = '(Ei valittu)';
$lang['fi_FI']['AdvancedDropdownField']['NONESELECTED'] = '(Ei valittu)';

$lang['fi_FI']['Resource']['SINGULARNAME'] = 'Resurssi';
$lang['fi_FI']['Resource']['PLURALNAME'] = 'Resurssit';
$lang['fi_FI']['Resource']['AVAILABLEFROM'] = 'Saatavilla (alkaen)';
$lang['fi_FI']['Resource']['AVAILABLETO'] = 'Saatavilla (päättyen)';
$lang['fi_FI']['Resource']['MEMBER'] = 'Työntekijä';
$lang['fi_FI']['Resource']['EXTRAWORK'] = 'Lisätyö';
$lang['fi_FI']['Resource']['FREETIME'] = 'Vapaa';
$lang['fi_FI']['Resource']['STANDARDHOURS'] = 'Normaalityö';
$lang['fi_FI']['Resource']['AVAILABLE'] = 'Töissä';
$lang['fi_FI']['Resource']['TOTALHOURS'] = 'Kokonaistunnit';
$lang['fi_FI']['Resource']['NAME'] = 'Nimi';

$lang['fi_FI']['ResourceOrganization']['SINGULARNAME'] = 'Organisaatio';
$lang['fi_FI']['ResourceOrganization']['PLURALNAME'] = 'Organisaatiot';
$lang['fi_FI']['ResourceOrganization']['NAME'] = 'Nami';

$lang['fi_FI']['ResourceGroup']['SINGULARNAME'] = 'Ryhmä';
$lang['fi_FI']['ResourceGroup']['PLURALNAME'] = 'Ryhmät';
$lang['fi_FI']['ResourceGroup']['NAME'] = 'Nami';

$lang['fi_FI']['BookingRequest']['SINGULARNAME'] = 'Varauspyyntö';
$lang['fi_FI']['BookingRequest']['PLURALNAME'] = 'Varauspyynnöt';

$lang['fi_FI']['Booking']['SINGULARNAME'] = 'Varaus';
$lang['fi_FI']['Booking']['PLURALNAME'] = 'Varaukset';
$lang['fi_FI']['Booking']['STARTDATE'] = 'Päivä';
$lang['fi_FI']['Booking']['START'] = 'Alku';
$lang['fi_FI']['Booking']['END'] = 'Pääty';
$lang['fi_FI']['Booking']['STARTTIME'] = 'Alkuaika';
$lang['fi_FI']['Booking']['ENDTIME'] = 'Loppuaika';
$lang['fi_FI']['Booking']['MOVE'] = 'Siirrä';
$lang['fi_FI']['Booking']['DELETE'] = 'Poista';
$lang['fi_FI']['Booking']['PAYMENT'] = 'Maksu';
$lang['fi_FI']['Booking']['PAY'] = 'Maksa';
$lang['fi_FI']['Booking']['PAID'] = 'Maksettu';
$lang['fi_FI']['Booking']['NOTPAID'] = 'Ei maksettu';
$lang['fi_FI']['Booking']['PAYMENTSUCCESS'] = 'Varaus %s:in maksu onnistui.';
$lang['fi_FI']['Booking']['PAYMENTFAILED'] = 'Varaus %s:in maksu epäonnistui!';
$lang['fi_FI']['Booking']['PAYMENTCANCELED'] = 'Varaus %s:in maksu keskeytetty käyttäjältä.';
$lang['fi_FI']['Booking']['TYPEID'] = 'Varaustyyppi';
$lang['fi_FI']['Booking']['MEMBERID'] = 'Asiakas';
$lang['fi_FI']['Booking']['BILLINGDETAILSID'] = 'Laskutus';

$lang['fi_FI']['BookingLogItem']['CREATED'] = 'Luotu';
$lang['fi_FI']['BookingLogItem']['EDITED'] = 'Muokattu, muutetut kentät: ';

$lang['fi_FI']['ResourceBookingMemberProfile']['SINGULARNAME'] = 'Profiili';
$lang['fi_FI']['ResourceBookingMemberProfile']['PLURALNAME'] = 'Profiilit';
$lang['fi_FI']['ResourceBookingMemberProfile']['ADDPROFILE'] = 'Lisää profiili';
$lang['fi_FI']['ResourceBookingMemberProfile']['FIRSTNAME'] = 'Etunimi';
$lang['fi_FI']['ResourceBookingMemberProfile']['LASTNAME'] = 'Sukunimi';
$lang['fi_FI']['ResourceBookingMemberProfile']['TELNR'] = 'Puh.';
$lang['fi_FI']['ResourceBookingMemberProfile']['EMAIL'] = 'S-posti';
$lang['fi_FI']['ResourceBookingMemberProfile']['ADDRESS'] = 'Osoite';
$lang['fi_FI']['ResourceBookingMemberProfile']['ACCEPTSPECIALOFFERS'] = 'Hyväksy tarjouksia sähköpostitse';

$lang['fi_FI']['ResourceBookingMemberPage']['MOVEBOOKINGTEXT'] = 'Valitse uusi aika alhaalta siirtämään varauksesi.';
$lang['fi_FI']['ResourceBookingMemberPage']['CANCEL'] = 'Keskeytä';
$lang['fi_FI']['ResourceBookingMemberPage']['DELETECONFIRMATION'] = 'Oletko varma että haluat poistaa varauksesi?';

$lang['fi_FI']['ResourceBookingAdmin']['MENUTITLE'] = 'Resurssivaraus';
$lang['fi_FI']['ResourceBookingAdmin']['SHOWFREERESOURCES'] = 'Näytä vapaat ajat';
$lang['fi_FI']['ResourceBookingAdmin']['SHOWBOOKINGS'] = 'Näytä varaukset';
$lang['fi_FI']['ResourceBookingAdmin']['SHOWBOOKINGREQUESTS'] = 'Näytä varauspyynnöt';
$lang['fi_FI']['ResourceBookingAdmin']['EDITWORKINGHOURS'] = 'Muokkaa työtunnit';
$lang['fi_FI']['ResourceBookingAdmin']['EDITRESOURCES'] = 'Muokkaa resurssit';
$lang['fi_FI']['ResourceBookingAdmin']['EDITSERVICES'] = 'Muokkaa palvelut';
$lang['fi_FI']['ResourceBookingAdmin']['MANAGEUSERS'] = 'Asiakashallinta';
$lang['fi_FI']['ResourceBookingAdmin']['VIEWREPORTS'] = 'Raportit';
$lang['fi_FI']['ResourceBookingAdmin']['WELCOMEMESSAGE'] = 'Tervetuloa resurssivaraukseen!<br />Valitse haluamasi toiminto vasemmalla olevasta listasta.';
$lang['fi_FI']['ResourceBookingAdmin']['SENDMESSAGE'] = 'Lähetä viesti';
$lang['fi_FI']['ResourceBookingAdmin']['SUBJECT'] = 'Otsikko';
$lang['fi_FI']['ResourceBookingAdmin']['MESSAGE'] = 'Viesti';
$lang['fi_FI']['ResourceBookingAdmin']['SENDTO'] = 'Lähteä';
$lang['fi_FI']['ResourceBookingAdmin']['ALLWHOACCEPT'] = 'Kaikille jotka hyväksyy';
$lang['fi_FI']['ResourceBookingAdmin']['SPECIFICUSERS'] = 'Tietty käyttäjille';
$lang['fi_FI']['ResourceBookingAdmin']['SELECTEDUSERS'] = 'Valittu käyttäjiä';
$lang['fi_FI']['ResourceBookingAdmin']['SEND'] = 'Lähetä';
$lang['fi_FI']['ResourceBookingAdmin']['TYPE'] = 'Tyyppi';
$lang['fi_FI']['ResourceBookingAdmin']['ACTION'] = 'Toiminta';
$lang['fi_FI']['ResourceBookingAdmin']['ACCEPT'] = 'Hyväksy';
$lang['fi_FI']['ResourceBookingAdmin']['REJECT'] = 'Hylätä';

$lang['fi_FI']['BookingCalendar']['REFRESH'] = 'Päivitä';
$lang['fi_FI']['BookingCalendar']['LOGIN'] = 'Kirjaudu sisään';
$lang['fi_FI']['BookingCalendar']['REGISTER'] = 'Rekisteröidy';

$lang['fi_FI']['ResourceBookingReports']['BOOKINGSPERRESOURCEREPORT'] = 'Varauksia per resurssi';
$lang['fi_FI']['ResourceBookingReports']['BOOKINGSPERSERVICEREPORT'] = 'Varauksia per palvelu';
$lang['fi_FI']['ResourceBookingReports']['TOTALBOOKINGS'] = 'Varauksien määrä';
$lang['fi_FI']['ResourceBookingReports']['TOTALDURATION'] = 'Kokonaisaika';
$lang['fi_FI']['ResourceBookingReports']['TOTALINCOME'] = 'Kokonaistulos';
$lang['fi_FI']['ResourceBookingReports']['START'] = 'Alkupäivämäärä';
$lang['fi_FI']['ResourceBookingReports']['END'] = 'Loppupäivämäärä';

$lang['fi_FI']['Confirmation']['TITLE'] = 'Varausvahvistus';

$lang['fi_FI']['ResourceBookingReport']['STARTDATE'] = 'Alkupvm';
$lang['fi_FI']['ResourceBookingReport']['ENDDATE'] = 'Päätypvm';
$lang['fi_FI']['ResourceBookingReport']['GENERATEREPORT'] = 'Luo raportti';
$lang['fi_FI']['ResourceBookingReport']['SAVEPDF'] = 'Tallenna PDF';

$lang['fi_FI']['BookingReport']['NAME'] = 'Varausraportti';

$lang['fi_FI']['InvoiceReport']['NAME'] = 'Laskutusraportti';

?>