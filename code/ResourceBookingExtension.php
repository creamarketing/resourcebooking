<?php

class ResourceBookingExtension extends Extension {
	
	public static $allowed_actions = array(
		'events',
		'BookingMemberLoginForm',
		'BookingMemberRegistrationForm',
		'ResourceBookingForm',
		'ResourceBookingFormAjax',
		'GetCustomerGroupAddress'
	);
	
	public function events() {
		return $this->getAllEvents();
	}
	
	public function getAllEvents($type='resources', $admin=false) {
		$start = Convert::raw2sql($_GET['start']);
		$end = Convert::raw2sql($_GET['end']);
		$resourceFilter = array();
		$showFreeTime = true;
		$showBookings = $admin;
		$showRequests = $admin;
		$showCancelled = false;
		$showRejected = false;
		$events = array();
		
		if (isset($_GET['resourceFilter'])) {
			$resourceFilter = split(',', Convert::raw2sql($_GET['resourceFilter']));
		}
		
		if (isset($_GET['showFreeTime'])) {
			$showFreeTime = Convert::raw2sql($_GET['showFreeTime']);
		}
		if (isset($_GET['showBookings'])) {
			$showBookings = Convert::raw2sql($_GET['showBookings']);
		}
		if (isset($_GET['showRequests'])) {
			$showRequests = Convert::raw2sql($_GET['showRequests']);
		}
		if (isset($_GET['showCancelled'])) {
			$showCancelled = Convert::raw2sql($_GET['showCancelled']);
		}
		if (isset($_GET['showRejected'])) {
			$showRejected = Convert::raw2sql($_GET['showRejected']);
		}
		
		// require a resource filter
		if ($resourceFilter || $type == 'bookings' || $type == 'bookingrequests') {
			$range = $end - $start;
			if ($type == 'resources') {
				// do not show past events
				if ($start < time() && !$admin) {
					$start = time();
				}
			}
			$firstDay = new Zend_Date(strtotime(date('Y-m-d 00:00', $start)));
			$lastDay = new Zend_Date(strtotime(date('Y-m-d 00:00', $end)));
			$currDay = $firstDay;
			$period = array();
			while ($currDay->getTimestamp() <= $lastDay->getTimestamp()) {
				$period[] = $currDay->getTimestamp();
				$currDay->addDay(1);
			}
			
			// if time range is bigger than 7 days we are in month view, where we only show summary events
			if ($range > 7*24*60*60) {
				if ($type == 'bookings') {
					$events = $this->summaryBookings($period, $resourceFilter);
				}
				else if ($type == 'bookingrequests') {
					$events = $this->summaryRequests($period, $resourceFilter);
				}
				else {
					$events = $this->summaryBookings($period, $resourceFilter);
					$events = array_merge($events, $this->summaryRequests($period, $resourceFilter));
				}
			}
			else {
				if ($type == 'bookings') {
					$events = $this->detailBookings($period, $resourceFilter);
				}
				else if ($type == 'bookingrequests') {
					$events = $this->detailRequests($period, $resourceFilter);
				}
				else {
					$event = array();
					if ($showFreeTime) {
						$events = $this->detailResources($period, $resourceFilter);
					}
					if ($showBookings && self::inAdminSection()) {
						$events = array_merge($events, $this->detailBookings($period, $resourceFilter));
					}
					if ($showRequests && self::inAdminSection()) {
						$events = array_merge($events, $this->detailRequests($period, $resourceFilter));
					}
					if ($showCancelled && self::inAdminSection()) {
						$events = array_merge($events, $this->detailBookings($period, $resourceFilter, 'Cancelled'));
					}
					if ($showRejected && self::inAdminSection()) {
						$events = array_merge($events, $this->detailRequests($period, $resourceFilter, 'Rejected'));
					}
				}
			}
		}
		
		// return response as JSON-data
		$response = new SS_HTTPResponse(json_encode($events));
		$response->addHeader("Content-type", "application/json");
		return $response;
	}
	
	private function summaryBookings($period, $resourceFilter=null) {
		$events = array();
		foreach ($period as $day) {
			$nrOfBookings = count($this->detailBookings(array($day), $resourceFilter));
			if ($nrOfBookings > 0) {
				$events[] = array(
					'title' => _t('Booking.PLURALNAME', 'Bookings') . ": $nrOfBookings",
					'allDay' => true,
					'editable' => false,
					'start' => $day
				);
			}
		}
		return $events;
	}
	
	private function summaryRequests($period, $resourceFilter=null) {
		$events = array();
		foreach ($period as $day) {
			$nrOfRequests = count($this->detailRequests(array($day), $resourceFilter));
			if ($nrOfRequests > 0) {
				$events[] = array(
					'title' => _t('BookingRequest.PLURALNAME', 'Booking requests') . ": $nrOfRequests",
					'allDay' => true,
					'editable' => false,
					'start' => $day
				);
			}
		}
		return $events;
	}
	
	private function summaryResources($period, $resourceFilter=null) {
		$events = array();
		foreach ($period as $day) {
			$freeResourceEvents = $this->getFreeResourcesForDay($day, $resourceFilter);
			$resourceArray = array();
			foreach ($freeResourceEvents as $event) {
				if (!in_array($event['id'], $resourceArray)) {
					$resourceArray[] = $event['id'];
				}
			}
			if (count($resourceArray) > 0) {
				$events[] = array(
					'title' => _t('ResourceBooking.FREERESOURCES', 'Free resources') . ': ' . count($resourceArray),
					'allDay' => true,
					'editable' => false,
					'start' => $day
				);
			}
		}
		return $events;
	}
	
	private function detailBookings($period, $resourceFilter=null, $status=null) {
		$events = array();
		$bookings = new DataObjectSet();
		$filterSearch = '';
		if ($resourceFilter) {
			$filterSearch .= ' AND (';
			$first = true;
			foreach ($resourceFilter as $resource) {
				if ($first) {
					$first = false;
				}
				else {
					$filterSearch .= ' OR ';
				}
				$filterSearch .= "ResourceID = $resource";
			}
			$filterSearch .= ')';
		}
		$statusFilter = "(Status = 'Accepted' OR Status = 'Preliminary')";
		$onlyCancelled = false;
		if ($status) {
			$statusFilter = '(';
			if (is_array($status)) {
				$first = true;
				foreach ($status as $statusName) {
					$statusName = Convert::raw2sql($statusName);
					if ($first) {
						$first = false;
					}
					else {
						$statusFilter .= ' OR ';
					}
					$statusFilter .= "Status = '$statusName'";
				}
				if (count($status) == 1 && $status[0] == 'Cancelled') {
					$onlyCancelled = true;
				}
			}
			else {
				$statusName = Convert::raw2sql($status);
				$statusFilter .= "Status = '$statusName'";
				if ($statusName == 'Cancelled') {
					$onlyCancelled = true;
				}
			}
			$statusFilter .= ')';
		}
		
		foreach ($period as $day) {
			// get all bookings for this resource on the specified day
			$startDay = date('Y-m-d', $day);
			$endDay = date('Y-m-d', $day + 24*60*60);
			$search = "Start >= '$startDay' AND End <= '$endDay' $filterSearch AND $statusFilter";
			$bookings->merge(DataObject::get('Booking', $search, 'Start'));
		}
		
		foreach ($bookings as $booking) {
			if (!$booking->Resource()->isAdmin() && !$booking->CreatorID == Member::currentUserID()) {
				continue;
			}
			$resourceName = $booking->Resource()->Name;
			$customerName = $booking->Customer()->getName();
			$groupName = $booking->CustomerGroup()->Name;
			$typeName = $booking->Type()->Name;
			$title = $groupName . ' (' . $typeName . ')';
			$description = $customerName . '<br />' . $resourceName;
			if ($booking->Paid) {
				$description .= '<br />' . _t('Booking.PAID', 'Paid') . ' ' . date('d.m.Y H:i', strtotime($booking->PaymentTime));
			}
			else {
				$description .= '<br />' . _t('Booking.NOTPAID', 'Not paid');
			}
			$color = '#C4201D';
			if ($booking->Type() && $booking->Type()->Color) {
				$color = '#' . $booking->Type()->Color;
			}
			$className = 'booking';
			if ($onlyCancelled) {
				$color = '#000000';
			}
			$events[] = array(
				'id' => $booking->ID,
				'title' => $title,
				'description' => $description,
				'allDay' => false,
				'editable' => $booking->Resource()->isAdmin(),
				'start' => strtotime($booking->Start),
				'end' => strtotime($booking->End),
				'type' => 'booking',
				'className' => $className,
				'resourceID' => $booking->ResourceID,
				'recurring' => $booking->IsRecurring,
				'color' => $color
			);
		}
		
		return $events;
	}
	
	private function detailRequests($period, $resourceFilter=null, $status=null) {
		$events = array();
		$requests = new DataObjectSet();
		$filterSearch = '';
		if ($resourceFilter) {
			$filterSearch .= ' AND (';
			$first = true;
			foreach ($resourceFilter as $resource) {
				if ($first) {
					$first = false;
				}
				else {
					$filterSearch .= ' OR ';
				}
				$filterSearch .= "ResourceID = $resource";
			}
			$filterSearch .= ')';
		}
		
		$statusFilter = "(Status = 'Pending')";
		$onlyRejected = false;
		if ($status) {
			$statusFilter = '(';
			if (is_array($status)) {
				$first = true;
				foreach ($status as $statusName) {
					$statusName = Convert::raw2sql($statusName);
					if ($first) {
						$first = false;
					}
					else {
						$statusFilter .= ' OR ';
					}
					$statusFilter .= "Status = '$statusName'";
				}
				if (count($status) == 1 && $status[0] == 'Rejected') {
					$onlyRejected = true;
				}
			}
			else {
				$statusName = Convert::raw2sql($status);
				$statusFilter .= "Status = '$statusName'";
				if ($statusName == 'Rejected') {
					$onlyRejected = true;
				}
			}
			$statusFilter .= ')';
		}
		
		foreach ($period as $day) {
			// get all bookings for this resource on the specified day
			$startDay = date('Y-m-d', $day);
			$endDay = date('Y-m-d', $day + 24*60*60);
			$search = "Start >= '$startDay' AND End <= '$endDay' $filterSearch AND $statusFilter";
			$requests->merge(DataObject::get('Booking', $search, 'Start'));
		}
		
		foreach ($requests as $request) {
			if (!$request->Resource()->isAdmin() && !$request->CreatorID == Member::currentUserID()) {
				continue;
			}
			$resourceName = $request->Resource()->Name;
			$customerName = $request->Customer()->getName();
			$groupName = $request->CustomerGroup()->Name;
			$typeName = $request->Type()->Name;
			$title = $groupName . ' (' . $typeName . ')';
			$description = $customerName . '<br />' . $resourceName;
			$color = '#7094db';
			if ($request->Type() && $request->Type()->Color) {
				$color = '#' . $request->Type()->Color;
			}
			if ($onlyRejected) {
				$color = '#000000';
			}
			$events[] = array(
				'id' => $request->ID,
				'title' => $title,
				'description' => $description,
				'allDay' => false,
				'editable' => $request->Resource()->isAdmin(),
				'start' => strtotime($request->Start),
				'end' => strtotime($request->End),
				'type' => 'request',
				'resourceID' => $request->ResourceID,
				'recurring' => $request->IsRecurring,
				'color' => $color
			);
		}
		
		return $events;
	}
	
	private function detailResources($period, $resourceFilter=null) {
		$events = array();
		foreach ($period as $day) {
			$events = array_merge($events, $this->getFreeResourcesForDay($day, $resourceFilter));
		}
		return $events;
	}
	
	private function getFreeResourcesForDay($day, $resourceFilter=null) {
		$events = array();
		$resources = DataObject::get('Resource');
		foreach($resources as $resource) {
			// filter out resources based on resource- and service-filters
			if ($resourceFilter && !in_array($resource->ID, $resourceFilter)) {
				continue;
			}
			
			$parentResource = null;
			$hasChildren = false;
			if ($resource->ResourceGroup) {
				$parentResource = $resource->Parent();
			}
			else if ($resource->Children() && $resource->Children()->Count() > 0) {
				$parentResource = $resource;
				$hasChildren = true;
			}
			
			// get all bookings for this resource on the specified day
			$startDay = date('Y-m-d', $day);
			$endDay = date('Y-m-d', $day + 24*60*60);
			
			$bookings = new DataObjectSet();
			$startTime = INF;
			$resourceEndTime = 0;
			$resourceIDFilter = '';
			if ($parentResource) {
				foreach ($parentResource->Children() as $childResource) {
					if ($resourceIDFilter != '') {
						$resourceIDFilter .= ' OR ';
					}
					$resourceIDFilter .= "ResourceID = {$childResource->ID}";
					$bookings->merge(DataObject::get('Booking', "ResourceID = {$childResource->ID} AND Start >= '$startDay' AND End <= '$endDay' AND Status = 'Accepted'", 'Start'));
					$currentStartTime = $childResource->getStartTime($day);
					if ($currentStartTime < $startTime) {
						$startTime = $currentStartTime;
					}
					$currentEndTime = $childResource->getEndTime($day);
					if ($currentEndTime > $resourceEndTime) {
						$resourceEndTime = $currentEndTime;
					}
				}
			}
			else {
				$resourceIDFilter = "ResourceID = {$resource->ID}";
				$startTime = $resource->getStartTime($day);
				$resourceEndTime = $resource->getEndTime($day);
			}
			
			$bookings = DataObject::get('Booking', "($resourceIDFilter) AND Start >= '$startDay' AND End <= '$endDay' AND Status = 'Accepted'", 'Start');
			
			// create events from free time for this resource on this day
			if ($startTime < time() && !self::inAdminSection()) {
				// ...except if start time is less than current time, in which case start time will be current time (rounded to nearest 15 minutes)
				$timeWithHour = $day + date('G', time())*60*60;
				$minutes = date('i', time());
				if ($minutes < 15) {
					$startTime = $timeWithHour + 15*60;
				}
				else if ($minutes < 30) {
					$startTime = $timeWithHour + 30*60;
				}
				else if ($minutes < 45) {
					$startTime = $timeWithHour + 45*60;
				}
				else {
					$startTime = $timeWithHour + 60*60;
				}
			}
			
			if ($bookings) {
				// create an event between each booking (if there is free time)
				foreach ($bookings as $booking) {
					$endTime = strtotime($booking->Start);
					if ($endTime > $startTime) {
						$freeDuration = ($endTime - $startTime)/60; // in minutes
						if ($freeDuration > 0) {
							$events[] = array(
								'id' => $resource->ID,
								'title' => $resource->Name,
								'description' => '',
								'hasChildren' => $hasChildren,
								'allDay' => false,
								'editable' => false,
								'start' => $startTime,
								'end' => $endTime,
								'type' => 'resource',
								'className' => 'resource',
								'backgroundColor' => '#E0E0E0',
								'borderColor' => '#000000',
								'textColor' => '#000000'
							);
						}
					}
					$startTime = strtotime($booking->End);
				}
			}
			
			if ($resourceEndTime > $startTime) {
				$events[] = array(
					'id' => $resource->ID,
					'title' => $resource->Name,
					'description' => '',
					'hasChildren' => $hasChildren,
					'allDay' => false,
					'editable' => false,
					'start' => $startTime,
					'end' => $resourceEndTime,
					'type' => 'resource',
					'className' => 'resource',
					'backgroundColor' => '#E0E0E0',
					'borderColor' => '#000000',
					'textColor' => '#000000'
				);
			}
		}
		return $events;
	}
	
	public function ResourceFilter() {
		return Resource::ResourceFilter();
	}
	
	public function ResourceGroupFilter() {
		return ResourceGroup::ResourceGroupFilter();
	}
	
	public function ResourceOrganizationFilter() {
		return ResourceOrganization::ResourceOrganizationFilter();
	}
	
	public function ShowBookingSection() {
		return false;
	}
	
	public function ShowResourceFilter() {
		return true;
	}

	public function ShowResourceGroupFilter() {
		return true;
	}
	
	public function ShowResourceOrganizationFilter() {
		return true;
	}

	public static function ResourceBookingMember() {
		if (Member::currentUser()) {
			return Member::currentUser()->inGroup('customer');
		}
		else {
			return false;
		}
	}
	
	public static function ResourceToResourceMapping() {
		return Resource::ResourceToResourceMapping();
	}
	
	public static function ResourceToGroupMapping() {
		return Resource::ResourceToGroupMapping();
	}
	
	public static function GroupToOrganizationMapping() {
		return ResourceGroup::GroupToOrganizationMapping();
	}
	
	public static function CustomerOrganizationToGroupMapping() {
		return CustomerOrganization::CustomerOrganizationToGroupMapping();
	}
	
	public static function CustomerGroupToCustomerMapping() {
		return CustomerGroup::CustomerGroupToCustomerMapping();
	}
	
	public static function isAdmin() {
		return Member::currentUser() && (Member::currentUser()->inGroup('administrators') || Member::currentUser()->inGroup('resourcebookingadmin'));
	}
	
	public static function isOrganizationAdmin() {
		return (Member::currentUser() && Member::currentUser()->inGroup('resourceorganizationadmin')) || self::isAdmin();
	}
	
	public static function isGroupAdmin() {
		return (Member::currentUser() && Member::currentUser()->inGroup('resourcegroupadmin')) || self::isOrganizationAdmin();
	}
	
	public static function adminLevel() {
		if (self::isAdmin()) {
			return 3;
		}
		else if (self::isOrganizationAdmin()) {
			return 2;
		}
		else if (self::isGroupAdmin()) {
			return 1;
		}
		return 0;
	}
	
	public static function inAdminSection() {
		return is_a(Controller::curr(), 'ResourceBookingAdmin');
	}
	
	public static function IsLoggedIn() {
		if (Member::currentUser() && Member::currentUser()->inGroup('customer')) {
			return 'var isLoggedIn = true;';
		}
		else {
			return 'var isLoggedIn = false;';
		}
	}
	
	public static function AutocompleteSearchAllowed() {
		$isiPad = (bool) strpos($_SERVER['HTTP_USER_AGENT'], 'iPad');
		return !$isiPad;
	}
	
	public function IsAutocompleteSearchAllowed() {
		if (self::AutocompleteSearchAllowed()) {
			return 'var autocompleteSearch = true;';
		}
		else {
			return 'var autocompleteSearch = false;';
		}
	}
	
	public function BookingMemberLoginForm() {
		return Customer::BookingMemberLoginForm($this->owner);
	}
	
	public function LoginBookingMember($data, $form) {
		$responseText = "Login successful!";
		$responseStatus = 200;
		
		$loginForm = $this->BookingMemberLoginForm();
		$member = $loginForm->performLogin($data);
		if ($member) {
			$responseText .= " Welcome {$member->getName()}!";
		}
		else {
			$responseText = "Login failed!";
			$responseStatus = 400;
		}
		
		if ($this->ViewEventsWithoutLogin()) {
			$response = new SS_HTTPResponse($responseText, $responseStatus);
			return $response;
		}
		else {
			$this->owner->redirectBack();
		}
	}
	
	public function ViewEventsWithoutLogin() {
		return ResourceBookingPage::$RequireLogin === false;
	}
	
	public function BookingMemberRegistrationForm() {
		return Customer::BookingMemberRegistrationForm($this->owner);
	}
	
	public function RegisterBookingMember($data, $form) {
		$responseText = "Registration successful!";
		$responseStatus = 200;
		
		// Important: don't allow setting the ID!
		if(isset($data['ID'])) {
			$responseText = "Registration failed! (ID not allowed)";
			$responseStatus = 400;
		}
		
		// Important: escape all data before doing any queries!
		$sqlData = Convert::raw2sql($data);
		
		// Important: safety-check that there is no existing member with this email adress!
		if($member = DataObject::get_one("Member", "`Email` = '". $sqlData['Email'] . "'")) {
			$responseText = _t('ResourceBookingPage.EMAILEXISTS','Sorry, that email address already exists. Please choose another.');
			$responseStatus = 400;
		}
		else {
			$member = new Customer();
			$form->saveInto($member);
			$member->Locale = Translatable::get_current_locale();
	  		
			$member->write();
			$member->login();
		}
		
		if ($this->ViewEventsWithoutLogin()) {
			$response = new SS_HTTPResponse($responseText, $responseStatus);
			return $response;
		}
		else {
			$this->owner->redirectBack();
		}
	}
	
	public function ResourceBookingFormAjax() {
		$resourceID = $this->owner->getRequest()->requestVar('ResourceID');
		// store resource id in session so that customer add/edit popups will work
		Session::set('ResourceID', $resourceID);
		$form = $this->ResourceBookingForm(null, $resourceID);
		if ($form) {
			return $form->forTemplate();
		}
		return null;
	}
	
	public function ResourceBookingForm($bookingID = null, $resourceID = null) {
		$booking = null;
		$name = 'ResourceBookingForm';
		$actions = new FieldSet();
		if ($bookingID && is_numeric($bookingID)) {
			$booking = DataObject::get_by_id('Booking', intval($bookingID));
			$name = 'BookingEditForm';
			$actions->push(new FormAction('EditBooking', ''));
		}
		else {
			if (!$resourceID) {
				// we need a resource id for e.g. customer edit/add popups, so try to fetch the last one from session
				$resourceID = Session::get('ResourceID');
			}
			$booking = new Booking();
			$booking->ResourceID = $resourceID;
			$actions->push(new FormAction('BookResource', ''));
		}
		
		$fields = $booking->getCMSFields();
		
		$form = new BookingEditPopup($this->owner, $name, $fields, null, false, $booking);
		$form->addExtraClass('ResourceBookingForm');
		$form->setActions($actions);
		
		return $form;
	}
	
	public function BookResource($data, $form) {
		$responseText = "Resource booked!";
		$responseStatus = 200;
		
		// Important: escape all data before doing anything with it!
		//            (to at least try to prevent sql-injection attacks)
		$sqlSafeData = Convert::raw2sql($data);
		if (isset($sqlSafeData['ResourceID']) && isset($sqlSafeData['Start']) && isset($sqlSafeData['End'])) {
			$resourceID = $sqlSafeData['ResourceID'];
			$start = $sqlSafeData['Start'];
			$end = $sqlSafeData['End'];
			$typeID = $sqlSafeData['Type'];
			$status = 'Pending';
			
			if (Member::currentUserID()) {
				// check if booking is allowed
				$errorReason = '';
				if (Booking::IsAllowed($resourceID, $start, $end, $status, $errorReason)) {
					$booking = new Booking();
					$booking->ResourceID = $resourceID;
					$booking->CustomerID = Member::currentUserID();
					$booking->TypeID = $typeID;
					$booking->Start = date('Y-m-d H:i', $start);
					$booking->End = date('Y-m-d H:i', $end);
					$booking->Status = $status;
					$booking->write();
				}
				else {
					$responseText = $errorReason;
					$responseStatus = 400;
				}
			}
			else {
				$responseText = "Please login or register before trying to book a resource!";
				$responseStatus = 400;
			}
		}
		else {
			$responseText = "Invalid parameters sent!";
			$responseStatus = 400;
		}
		
		$response = new SS_HTTPResponse($responseText, $responseStatus);
		return $response;
	}
	
	public function getResourceBookingRequirements() {
		// jQuery and jQuery ui
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery-ui-1.8rc3.custom.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui/jquery.ui.dialog.js');
		Requirements::block(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css');
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-cookie/jquery.cookie.js');
		Requirements::javascript('resourcebooking/javascript/jquery-ui-1.8.6.custom.min.js');
		Requirements::css('resourcebooking/css/smoothness/jquery-ui-1.8.6.custom.css');
		
		// jquery ondemand needed for ajax js and css loading
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/core/jquery.ondemand.js');
		
		// javascript localization
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('resourcebooking/javascript/lang');
		
		// full calendar jQuery plugin
		Requirements::javascript('resourcebooking/javascript/fullcalendar-1.5.js');
		Requirements::css('resourcebooking/css/fullcalendar-1.5.css');
		
		// qTip jQuery tooltip plugin
		Requirements::javascript('resourcebooking/javascript/jquery.qtip-1.0.min.js');
		
		// jQuery validate
		Requirements::javascript('resourcebooking/javascript/jquery.validate.min.js');
		
		// our own Booking Calendar stuff
		Requirements::javascript('resourcebooking/javascript/BookingCalendar.js');
		Requirements::css('resourcebooking/css/ResourceBooking.css');
		
		Requirements::javascript('creamarketing/javascript/AdvancedDropdownField.js');
	}
	
	public function getResourceBookingMemberPageLink() {
		//$page = DataObject::get_one('ResourceBookingMemberPage');
		$page = '';
		if ($page) {
			return $page->URLSegment;
		}
		else {
			return '';
		}
	}
	
	public function ColorLegend() {
		$colors = new DataObjectSet();
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#E0E0E0',
				'borderColor' => '#000000',
				'label' => _t('ResourceBooking.FREERESOURCES', 'Free resources')
			))
		);
		return $colors;
	}
	
	public function SelectedDate() {
		if (isset($_GET['date'])) {
			return $_GET['date'];
		}
		return '';
	}
	
	public function PreselectedOrganizationID() {
		if (isset($_GET['organization'])) {
			return intval($_GET['organization']);
		}
		else if (self::adminLevel() == 2) {
			$administeredOrganization = DataObject::get_one('ResourceOrganization', 'AdministratorID = ' . Member::currentUserID());
			if ($administeredOrganization) {
				return $administeredOrganization->ID;
			}
		}
		return 0;
	}
	
	public function PreselectedGroupID() {
		if (isset($_GET['group'])) {
			return intval($_GET['group']);
		}
		else if (self::adminLevel() == 1) {
			$administeredGroup = DataObject::get_one('ResourceGroup', 'AdministratorID = ' . Member::currentUserID());
			if ($administeredGroup) {
				return $administeredGroup->ID;
			}
		}
		return 0;
	}
	
	public function PreselectedResourceID() {
		if (isset($_GET['resource'])) {
			return intval($_GET['resource']);
		}
		return 0;
	}
	
	public function GetCustomerGroupAddress() {
		$customerGroupID = (int)$_GET['CustomerGroupID'];
		$address = '';
		$status = 200;
		if ($customerGroupID) {
			$customerGroup = DataObject::get_by_id('CustomerGroup', $customerGroupID);
			$address = nl2br($customerGroup->Address);
		}
		else {
			$status = 400;
		}
		
		$response = new SS_HTTPResponse($address, $status);
		return $response;
	}
	
	public function QuickMonth() {
		$field = new AdvancedDropdownField('QuickMonth', 'Month', '', '', false, false, 'onQuickMonthSelect');
		return $field;
	}
	
}

?>