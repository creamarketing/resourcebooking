<?php

class ConfirmationPage extends Page {
	
}

class ConfirmationPage_Controller extends Page_Controller {
	
	public $result = null;
	public $error = null;
	public $confirmation = null;
	
	static $url_handlers = array(
		'$Action!/accept' => 'confirmBooking',
		'$Action!/reject' => 'rejectBooking',
		'$Action!' => 'confirmationDetails',
	);
	
	public function init() {
		parent::init();
		
		if (!$this->urlParams['Action']) {
			$this->error = _t('ConfirmationPage.NOCONFIRMATIONHASH', 'No confirmation identification given!');
		}
	}
	
	function confirmationDetails() {
		$confirmHash = $this->urlParams['Action'];
		$confirmation = DataObject::get_one('Confirmation', "Hash = '$confirmHash'");
		if ($confirmation) {
			$this->confirmation = $confirmation;
		}
		else {
			$this->error = _t('ConfirmationPage.CONFIRMATIONNOTFOUND', 'No corresponding confirmation found!');
		}
		return $this->getViewer($this->action)->process($this);
	}
	
	function confirmBooking() {
		$confirmHash = $this->urlParams['Action'];
		$confirmation = DataObject::get_one('Confirmation', "Hash = '$confirmHash'");
		if ($confirmation) {
			$bookings = DataObject::get('Booking', "BookingID = {$confirmation->BookingID} AND Status = 'Preliminary'");
			if ($bookings) {
				foreach ($bookings as $booking) {
					$booking->Status = 'Accepted';
					$booking->write();
				}
			}
			$confirmation->Accepted = date('Y-m-d H:i');
			$confirmation->write();
			$result = _t('ConfirmationPage.BOOKINGACCEPTED', 'Booking accepted!');
		}
		else {
			$this->error = _t('ConfirmationPage.CONFIRMATIONNOTFOUND1', 'No corresponding confirmation found, booking not confirmed!');
		}
		return $this->getViewer($this->action)->process($this);
	}
	
	function rejectBooking() {
		$confirmHash = $this->urlParams['Action'];
		$confirmation = DataObject::get_one('Confirmation', "Hash = '$confirmHash'");
		if ($confirmation) {
			$bookings = DataObject::get('Booking', "BookingID = {$confirmation->BookingID} AND Status = 'Preliminary'");
			if ($bookings) {
				foreach ($bookings as $booking) {
					$booking->Status = 'Rejected';
					$booking->write();
				}
			}
			$confirmation->Rejected = date('Y-m-d H:i');
			$confirmation->write();
			$result = _t('ConfirmationPage.BOOKINGREJECTED', 'Booking rejected!');
		}
		else {
			$this->error = _t('ConfirmationPage.CONFIRMATIONNOTFOUND2', 'No corresponding confirmation found, booking not rejected!');
		}
		return $this->getViewer($this->action)->process($this);
	}
	
}

?>