<?php

class CustomerPage extends Page {
	
}

class CustomerPage_Controller extends Page_Controller {
	
	static $extensions = array(
		'ResourceBookingExtension()'
	);
	
	public $view = 'default';
	public $bookingID = 0;
	public $resourceID = 0;
	
	public function init() {
		parent::init();
		
		$this->getResourceBookingRequirements();
		
		Requirements::javascript('resourcebooking/javascript/BookingEdit.js');
		Requirements::css('resourcebooking/css/ResourceBookingMemberPage.css');
		
		// set view based on url parameter
		$urlAction = $this->urlParams['Action'];
		if ($urlAction == 'movebooking') {
			$this->view = $urlAction;
		}
		else {
			$this->view = 'default';
		}
	}
	
	public function getMemberBookings() {
		$member = Member::currentUser();
		$bookings = null;
		if ($member) {
			$bookings = DataObject::get('Booking', "CustomerID = $member->ID", 'Start DESC');
		}
		return $bookings;
	}
	
	public function MoveBooking() {
		$this->bookingID = Convert::raw2sql($this->urlParams['ID']);
		if (!$this->bookingID || !is_numeric($this->bookingID)) {
			return $this->redirect(Director::baseURL() . $this->URLSegment);
		}
		$booking = DataObject::get_by_id('Booking', $this->bookingID);
		if ($booking && ($booking->Customer()->ID == Member::currentUserID())) {
			$this->resourceID = $booking->ResourceID;
			if ($this->urlParams['OtherID'] == 'events') {
				$_GET['start'] = time() + 24*60*60;
				$_GET['resourceFilter'] = $this->resourceID;
				return $this->events();
			}
			
			$ssv = new SSViewer("Page");
			$ssv->setTemplateFile("Layout", "CustomerPage");
			return $this->renderWith($ssv);
		}
		
		return $this->redirect(Director::baseURL() . $this->URLSegment);
	}
	
	public function DeleteBooking() {
		$bookingID = Convert::raw2sql($_POST['BookingID']);
		$booking = DataObject::get_by_id('Booking', $bookingID);
		if ($booking && ($booking->Customer()->ID == Member::currentUserID())) {
			$booking->delete();
			return "ok";
		}
		else {
			return 'Invalid id!';
		}
	}
	
	public function ResourceBookingForm() {
		$fields = Booking::ResourceBookingFormFields();
		$fields->push(new HiddenField('BookingID', 'BookingID', $this->bookingID));
		
		$actions = new FieldSet(
			new FormAction('BookResource', '')
		);
		
		return new Form($this, 'ResourceBookingForm', $fields, $actions);
	}
	
	public function BookResource($data, $form) {
		$responseText = "Booking moved!";
		$responseStatus = 200;
		
		$start = Convert::raw2sql($_POST['Start']);
		$end = Convert::raw2sql($_POST['End']);
		$bookingID = Convert::raw2sql($_POST['BookingID']);
		$resourceID = Convert::raw2sql($_POST['ResourceID']);
		$booking = DataObject::get_by_id('Booking', $bookingID);
		if ($booking && ($booking->Customer()->ID == Member::currentUserID())) {
			$reason = '';
			if (Booking::IsBookingAllowed($resourceID, $start, $end, $reason)) {
				$booking->Start = date('Y-m-d H:i', $start);
				$booking->End = date('Y-m-d H:i', $end);
				$booking->ResourceID = $resourceID;
				$booking->write();
			}
			else {
				$responseText = "Invalid booking: $reason";
				$responseStatus = 400;
			}
		}
		else {
			$responseText = 'Invalid id!';
			$responseStatus = 400;
		}
		
		$response = new SS_HTTPResponse($responseText, $responseStatus);
		return $response;
	}
	
	public function ShowResourceFilter() {
		return false;
	}

	public function ShowResourceGroupFilter() {
		return false;
	}
	
	public function ShowResourceOrganizationFilter() {
		return false;
	}
	
	public function getCurrentResource() {
		$resource = DataObject::get_by_id('Resource', $this->resourceID);
		if ($resource) {
			return $resource->Name;
		}
		else {
			return '';
		}
	}
	
	public function getCurrentGroup() {
		$resource = DataObject::get_by_id('Resource', $this->resourceID);
		if ($resource && $resource->Group()) {
			return $resource->Group()->Name;
		}
		else {
			return '';
		}
	}
	
	public function getCurrentOrganization() {
		$resource = DataObject::get_by_id('Resource', $this->resourceID);
		if ($resource && $resource->Group() && $resource->Group()->Organization()) {
			return $resource->Group()->Organization()->Name;
		}
		else {
			return '';
		}
	}
	
	public function QuickMonth() {
		return '';
	}
	
}

?>