<?php

class Confirmation extends DataObject {
	
	static $db = array(
		'Hash' => 'Varchar(32)',
		'BookingID' => 'Int',
		'ExtraText' => 'Text',
		'Due' => 'Date',
		'EmailAddresses' => 'Text',
		'Sent' => 'SS_Datetime',
		'Locale' => 'Varchar(16)',
		'Accepted' => 'SS_Datetime',
		'Rejected' => 'SS_Datetime'
	);
	
	static $defaults = array(
		'Locale' => 'sv_SE'
	);
	
	function Send($bookings = null, $email = false, $pdf = false, $preview = false) {
		if (!$bookings) {
			$bookings = DataObject::get('Booking', "BookingID = {$this->BookingID}");
		}
		if ($bookings) {
			Requirements::clear();
			$customFields = array('Booking' => $bookings->First());
			$customFields['ExtraText'] = $this->ExtraText;
			$start = INF;
			$end = 0;
			$created = INF;
			$acceptedBookings = new DataObjectSet();
			$acceptedBookingsTotalPrice = 0;
			$rejectedBookings = new DataObjectSet();
			$rejectedBookingsTotalPrice = 0;
			$cancelledBookings = new DataObjectSet();
			$cancelledBookingsTotalPrice = 0;
			foreach ($bookings as $booking) {
				if ($start > strtotime($booking->Start)) {
					$start = strtotime($booking->Start);
				}
				if ($end < strtotime($booking->End)) {
					$end = strtotime($booking->End);
				}
				if ($created > strtotime($booking->Created)) {
					$created = strtotime($booking->Created);
				}
				
				if ($booking->Status == 'Accepted' || $booking->Status == 'Preliminary' || $booking->Status == 'Pending') {
					$acceptedBookings->push($booking);
					$acceptedBookingsTotalPrice += $booking->Price();
				}
				else if ($booking->Status == 'Rejected') {
					$rejectedBookings->push($booking);
					//$rejectedBookingsTotalPrice += $booking->Price();
				}
				else if ($booking->Status == 'Cancelled') {
					$cancelledBookings->push($booking);
					//$cancelledBookingsTotalPrice += $booking->Price();
				}
			}
			$customFields['Start'] = date('d.m.Y', $start);
			$customFields['End'] = date('d.m.Y', $end);
			$customFields['Created'] = date('d.m.Y', $created);
			$customFields['Today'] = date('d.m.Y');
			$customFields['ConfirmationType'] = '';
			$customFields['AcceptedBookings'] = $acceptedBookings;
			$customFields['AcceptedBookingsTotalPrice'] = $acceptedBookingsTotalPrice;
			$customFields['RejectedBookings'] = $rejectedBookings;
			$customFields['RejectedBookingsTotalPrice'] = $rejectedBookingsTotalPrice;
			$customFields['CancelledBookings'] = $cancelledBookings;
			$customFields['CancelledBookingsTotalPrice'] = $cancelledBookingsTotalPrice;
			$confirmPageLink = null;
			$confirmPage = DataObject::get_one('ConfirmationPage');
			if ($confirmPage) {
				$confirmPageLink = Director::absoluteURL($confirmPage->Link());
			}
			$customFields['ConfirmPageLink'] = $confirmPageLink;
			$customFields['BookingMember'] = Member::currentUser();
			
			if (!$preview) {
				$this->Sent = date('Y-m-d H:i');
				$this->write();
			}
			else {
				if (!$this->Hash) {
					$this->Hash = strtolower(md5(time()));
				}
			}
			
			$customFields['ConfirmHash'] = $this->Hash;
			
			$storedLocale = i18n::get_locale();
			if (i18n::validate_locale($this->Locale)) {
				i18n::set_locale($this->Locale);
			}
			if ($email) {
				$customFields['ConfirmationType'] = 'email';
				$addresses = array();
				if ($preview) {
					$addresses[] = Member::currentUser()->Email;
				}
				else {
					$emailAddresses = explode(',', $this->EmailAddresses);
					foreach ($emailAddresses as $address) {
						$addresses[] = trim($address);
					}
				}
				
				foreach ($addresses as $to) {
					$email = new Email('no-reply@creamarketing.com', $to, 'Bokningsbekräftelse');
					$email->setTemplate("Confirmation");
					$email->populateTemplate($customFields);
					try {
						$email->send();
					}
					catch (Exception $e) {
						// silently catch email sending errors...
					}
				}
			}
			
			$content = '';
			if ($pdf) {
				$customFields['ConfirmationType'] = 'pdf';
				$content = singleton('PDFRenditionService')->render(singleton('Confirmation')->renderWith('Confirmation', $customFields), 'browser', 'bokningsbekraftelse.pdf');
			}
			else if ($preview) {
				$customFields['ConfirmationType'] = 'html';
				$content = singleton('Confirmation')->renderWith('Confirmation', $customFields);
			}
			
			i18n::set_locale($storedLocale);
			return $content;
		}
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if (!$this->Hash) {
			$this->Hash = strtolower(md5(time()));
		}
	}
	
}

?>