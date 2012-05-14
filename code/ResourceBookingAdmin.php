<?php

require_once 'Zend/Date.php';

class ResourceBookingAdmin extends LeftAndMain {
	
	static $extensions = array(
		'ResourceBookingExtension()'
	);
	
	static $url_segment = 'resourcebooking';
	
	static $menu_title = 'Resource Booking';
	
	static $menu_priority = 3;
	
	public $view = 'default';
	
	static $allowed_views = array(
		'default',
		'showresources',
		'editbookingrequests',
		'editbookings',
		'editorganizations',
		'editgroups',
		'editresources',
		'editservices',
		'editbookingtypes',
		'edituserorganizations',
		'editusergroups',
		'editusers',
		'editusertypes',
		'editpricegroups',
		'edittaxtypes',
		'showbookingreport',
		'showinvoicereport'
	);
	
	static $allowed_actions = array(
		'resources',
		'bookingrequests',
		'bookings',
		'changebookingrequest',
		'changebooking',
		'RegisterBookingMember',
		'BookResource',
		'EditBookingRequestsForm',
		'EditBookingsForm',
		'EditOrganizationsForm',
		'EditGroupsForm',
		'EditResourcesForm',
		'EditServicesForm',
		'EditBookingTypesForm',
		'EditUserOrganizationsForm',
		'EditUserGroupsForm',
		'EditUsersForm',
		'EditUserTypesForm',
		'EditPriceGroupsForm',
		'EditTaxTypesForm',
		'EditBookingRequest',
		'ResourceBookingFormAjax',
		'ResourceBookingForm',
		'BookingRequestEditForm',
		'BookingEditForm',
		'BookingEditFormAjax',
		'NewRequests',
		'BookingReport',
		'InvoiceReport',
		'PrintCalendarPDF'
	);
	
	function defineMethods() {
		parent::defineMethods();
		foreach (self::$allowed_views as $view) {
			self::$allowed_actions[] = $view;
		}
	}
	
	function canView($member = null) {
		if(!$member) {
			if (!Member::currentUser()) {
				return false;
			}
			else {
				$member = Member::currentUser();
			}
		}
		
		if ($member->inGroup('resourcebookingadmin')) {
			return true;
		}
		else {
			return parent::canView($member);
		}
	}
	
	function emulateIE7() {
		return false;
	}
	
	public function init() {
		parent::init();
		
		$this->getResourceBookingRequirements();
		
		// Booking Calendar admin stuff
		Requirements::javascript('resourcebooking/javascript/ResourceBookingAdmin.js');
		Requirements::css('resourcebooking/css/ResourceBookingAdmin.css');
		
		// extra css to make it look the same as on public page
		Requirements::css('themes/blackcandy/css/layout.css');
		Requirements::css('themes/blackcandy/css/form.css');
		
		// set view based on url parameter
		$urlAction = $this->urlParams['Action'];
		if (in_array($urlAction, self::$allowed_views)) {
			$this->view = $urlAction;
		}
		else {
			$this->view = 'default';
		}
		
		if (($urlAction != 'EditResourcesForm' && $urlAction != 'EditUsersForm') || Director::is_ajax()) {
			// block some cms css here, to make calendar look like on public page
			Requirements::block('cms/css/typography.css');
			Requirements::block('sapphire/css/Form.css');
		}
		
		if (Member::CurrentMember() && in_array(Member::CurrentMember()->Locale, Translatable::get_allowed_locales())) {
			Translatable::set_current_locale(Member::CurrentMember()->Locale);
		}
	}
	
	public function resources() {
		return $this->getAllEvents('resources', true);
	}
	
	public function bookingrequests() {
		return $this->getAllEvents('bookingrequests', true);
	}
	
	public function bookings() {
		return $this->getAllEvents('bookings', true);
	}
	
	public function BookingEditFormAjax() {
		$form = $this->BookingEditForm();
		if ($form) {
			return $form->forTemplate();
		}
		return null;
	}
	
	public function BookingEditForm() {
		$bookingID = $this->getRequest()->requestVar('BookingEditID');
		// store booking id in session, so that we can show popups in nested DOMs
		if ($bookingID) {
			Session::set('BookingID', $bookingID);
		}
		else if (!$this->getRequest()->requestVar('PreviewConfirmation')) {
			$bookingID = Session::get('BookingID');
		}
		$form = $this->ResourceBookingForm($bookingID);
		return $form;
	}
	
	public function BookingRequestEditForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HiddenField('BookingRequestID'),
				new OptionsetField('Action', _t('ResourceBookingAdmin.ACTION', 'Action') . ':',
					array(0 => _t('ResourceBookingAdmin.ACCEPT', 'Accept'), 1 => _t('ResourceBookingAdmin.REJECT', 'Reject'))
				)
			);
			
			$actions = new FieldSet(
				new FormAction('EditBookingRequest', '')
			);
			
			return new Form($this, 'BookingRequestEditForm', $fields, $actions);
		}
	}
	
	public function EditBookingRequest($data, $form) {
		if (self::isAdmin()) {
			$responseText = _t('BookingRequest.SINGULARNAME', 'Booking request') . ' ' . _t('ResourceBooking.EDITED', 'edited') . '!';
			$responseStatus = 200;
			
			$sqlSafeData = Convert::raw2sql($data);
			$requestID = $sqlSafeData['BookingRequestID'];
			$action = $sqlSafeData['Action'];
			
			$request = DataObject::get_one('Booking', "ID = $requestID AND Status != 'Accepted'");
			if ($request) {
				if ($action == 0) {
					$errorReason = '';
					if (Booking::IsAllowed($request->ResourceID, strtotime($request->Start), strtotime($request->End), $request->Status, $errorReason)) {
						$request->Status = 'Accepted';
						$request->write();
						$responseText = _t('BookingRequest.SINGULARNAME', 'Booking request') . ' ' . $requestID . ' accepted!';
					}
					else {
						$responseText = 'Error: ' . $errorReason;
						$responseStatus = 400;
					}
				}
				else {
					$request->Status = 'Rejected';
					$request->write();
					$responseText = _t('BookingRequest.SINGULARNAME', 'Booking request') . ' ' . $requestID . ' rejected!';
				}
			}
			else {
				$responseText = _t('BookingRequest.SINGULARNAME', 'Booking request') . ' ' . $requestID . ' not found!';
				$responseStatus = 400;
			}
			
			$response = new SS_HTTPResponse($responseText, $responseStatus);
			return $response;
		}
	}
	
	public function EditBookingsForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('BookingsHeader', _t('Booking.PLURALNAME', 'Bookings')),
				$bookingDOM = new DialogDataObjectManager(
					$this, 
					'Bookings', 
					'Booking', 
					array(
						'Resource.Name' => 'Resurs',
						'BookingID' => 'Boknings-id',
						'NiceStartTime' => 'Starttid',
						'NiceEndTime' => 'Sluttid'
					),
					null,
					"Status = 'Accepted'"
				)
			);
			
			$bookingDOM->setPermissions(array('edit', 'delete'));
			$bookingDOM->popupClass = 'BookingEditPopup';
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditBookingsForm", $fields, $actions);
		}
	}
	
	public function EditBookingRequestsForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('BookingsHeader', _t('BookingRequest.PLURALNAME', 'Booking requests')),
				$bookingDOM = new DialogDataObjectManager(
					$this, 
					'Bookings', 
					'Booking', 
					array(
						'Resource.Name' => 'Resurs',
						'BookingID' => 'Boknings-id',
						'NiceStartTime' => 'Starttid',
						'NiceEndTime' => 'Sluttid'
					),
					null,
					"Status = 'Pending'"
				)
			);
			
			$bookingDOM->setPermissions(array('edit', 'delete'));
			$bookingDOM->popupClass = 'BookingEditPopup';
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditBookingRequestsForm", $fields, $actions);
		}
	}
	
	public function EditBookingTypesForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('BookingTypeHeader', _t('BookingType.PLURALNAME', 'Booking types')),
				$typeDOM = new DialogDataObjectManager($this, 'BookingType', 'BookingType', array('Name' => _t('BookingType.NAME', 'Name')))
			);
			
			$typeDOM->setAddTitle(_t('BookingType.SINGULARNAME', 'Booking type'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditBookingTypesForm", $fields, $actions);
		}
	}
	
	public function EditResourcesForm() {
		if (self::isOrganizationAdmin()) {
			$fields = new FieldSet(
				new HeaderField('ResourceHeader', _t('Resource.PLURALNAME', 'Resources')),
				$resourceDOM = new DialogDataObjectManager(
					$this, 
					'Resources', 
					'Resource', 
					array(
						'Name' => _t('Resource.NAME', 'Name'), 
						'Group.Organization.Name' => _t('Resource.ORGANIZATION', 'Organisation'),
						'Group' => _t('Resource.GROUP', 'Group'),
						'Parent' => _t('Resource.PARENT', 'Parent resource')
					),
					null,
					null,
					'GroupID ASC',
					'LEFT JOIN ResourceGroup ON ResourceGroup.ID = Resource.GroupID LEFT JOIN ResourceOrganization ON ResourceOrganization.ID = ResourceGroup.OrganizationID'
				)
			);
			
			if (!self::isAdmin()) {
				$resourceDOM->setSourceFilter('ResourceOrganization.AdministratorID = ' . Member::currentUserID());
			}
			
			$groups = DataObject::get('ResourceGroup', null, 'OrganizationID ASC');
			$groupArray = array();
			if ($groups) {
				foreach ($groups as $group) {
					if (self::isAdmin() || $group->isAdmin()) {
						$groupArray[$group->ID] = $group->forDropdown();
					}
				}
			}
			$resourceDOM->setFilter('GroupID', _t('Resource.GROUP', 'Group'), $groupArray);
			$resourceDOM->setAddTitle(_t('Resource.SINGULARNAME', 'Resource'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditResourcesForm", $fields, $actions);
		}
	}
	
	public function EditServicesForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('ServiceHeader', _t('Service.PLURALNAME', 'Tjänster')),
				$serviceDOM = new DialogDataObjectManager($this, 'Services', 'Service')
			);
			$serviceDOM->setAddTitle(_t('Service.SINGULARNAME', 'Tjänst'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditServicesForm", $fields, $actions);
		}
	}
	
	public function EditGroupsForm() {
		if (self::isOrganizationAdmin()) {
			$fields = new FieldSet(
				new HeaderField('GroupHeader', _t('ResourceGroup.PLURALNAME', 'Resource groups')),
				$groupDOM = new DialogDataObjectManager(
					$this, 
					'ResourceGroups', 
					'ResourceGroup', 
					array(
						'Name' => _t('ResourceGroup.NAME', 'Name'),
						'Organization' => _t('ResourceGroup.ORGANIZATION', 'Organization')
					), 
					null, 
					null, 
					'OrganizationID ASC',
					'LEFT JOIN ResourceOrganization ON ResourceOrganization.ID = ResourceGroup.OrganizationID'
				)
			);
			
			if (!self::isAdmin()) {
				$groupDOM->setSourceFilter('ResourceOrganization.AdministratorID = ' . Member::currentUserID());
			}
			$organizations = DataObject::get('ResourceOrganization');
			$organizationArray = array();
			if ($organizations) {
				foreach ($organizations as $organization) {
					if (self::isAdmin() || $organization->AdministratorID == Member::currentUserID()) {
						$organizationArray[$organization->ID] = $organization->Name;
					}
				}
			}
			$groupDOM->setFilter('OrganizationID', _t('ResourceGroup.ORGANIZATION', 'Organization'), $organizationArray);
			$groupDOM->setAddTitle(_t('ResourceGroup.SINGULARNAME', 'Resource group'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditGroupsForm", $fields, $actions);
		}
	}
	
	public function EditOrganizationsForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('OrganizationHeader', _t('ResourceOrganization.PLURALNAME', 'Resource organization')),
				$organizationDOM = new DialogDataObjectManager(
					$this, 
					'ResourceOrganizations', 
					'ResourceOrganization',
					array(
						'Name' => _t('ResourceOrganization.NAME', 'Name')
					)
				)
			);
			
			$organizationDOM->setAddTitle(_t('ResourceOrganization.SINGULARNAME', 'Resource group'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditOrganizationsForm", $fields, $actions);
		}
	}
	
	public function EditUserOrganizationsForm() {
		if (self::isGroupAdmin()) {
			$fields = new FieldSet(
				new HeaderField('CustomerOrganizationHeader', _t('CustomerOrganization.PLURALNAME', 'Customer organizations')),
				$dm = new DialogDataObjectManager(
					$this, 
					'CustomerOrganizations', 
					'CustomerOrganization', 
					array(
						'Name' => _t('CustomerGroup.NAME', 'Namn')
					)
				)
			);
			$dm->setAddTitle(_t('CustomerOrganization.SINGULARNAME', 'Customer organization'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditUserOrganizationsForm", $fields, $actions);
		}
	}
	
	public function EditUserGroupsForm() {
		if (self::isGroupAdmin()) {
			$fields = new FieldSet(
				new HeaderField('CustomerGroupHeader', _t('CustomerGroup.PLURALNAME', 'Customer groups')),
				$dm = new DialogDataObjectManager(
					$this, 
					'CustomerGroups', 
					'CustomerGroup', 
					array(
						'Name' => _t('CustomerGroup.NAME', 'Namn'),
						'Organization' =>_t('CustomerGroup.ORGANIZATION', 'Organization'),
						'Parent' => _t('CustomerGroup.PARENT', 'Parent group')
					)
				)
			);
			$organizations = DataObject::get('CustomerOrganization');
			if ($organizations) {
				$organizations = $organizations->map();
			}
			else {
				$organizations = array();
			}
			$dm->setFilter('OrganizationID', _t('CustomerGroup.ORGANIZATION', 'Organization'), $organizations);
			$dm->setAddTitle(_t('CustomerGroup.SINGULARNAME', 'Customer group'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditUserGroupsForm", $fields, $actions);
		}
	}
	
	public function EditUsersForm() {
		if (self::isGroupAdmin()) {
			$fields = new FieldSet(
				new HeaderField('CustomerHeader', _t('Customer.PLURALNAME', 'Contact persons')),
				$dm = new DialogDataObjectManager(
					$this, 
					'Customer', 
					'Customer', 
					array(
						'FirstName' => _t('Customer.FIRSTNAME', 'First name'), 
						'Surname' => _t('Customer.LASTNAME', 'Last name'),
						'Group' => _t('Customer.GROUP', 'Avdelning/grupp'),
						'Group.Organization.Name' => _t('Customer.ORGANIZATION', 'Organization')
					)
				)
			);
			$groups = DataObject::get('CustomerGroup', null, 'OrganizationID ASC');
			if ($groups) {
				$groups = $groups->map('ID', 'forDropdown');
			}
			else {
				$groups = array();
			}
			$dm->setFilter('GroupID', _t('Customer.GROUP', 'Group'), $groups);
			$dm->setAddTitle(_t('Customer.SINGULARNAME', 'Contact person'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditUsersForm", $fields, $actions);
		}
	}
	
	public function EditUserTypesForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('CustomerTypeHeader', _t('CustomerType.PLURALNAME', 'Customer types')),
				$dm = new DialogDataObjectManager($this, 'CustomerType', 'CustomerType', array('Name' => _t('CustomerType.NAME', 'Name')))
			);
			$dm->setAddTitle(_t('CustomerType.SINGULARNAME', 'Customer type'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditUserTypesForm", $fields, $actions);
		}
	}
	
	public function EditTaxTypesForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('TaxTypeHeader', _t('TaxType.PLURALNAME', 'Momsval')),
				$dm = new DialogDataObjectManager($this, 'TaxType', 'TaxType', array('Name' => _t('TaxType.NAME', 'Namn'), 'TaxPercent' => _t('TaxType.TAXPERCENT', 'Moms %')))
			);
			$dm->setAddTitle(_t('TaxType.SINGULARNAME', 'Momsval'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditTaxTypesForm", $fields, $actions);
		}
	}
	
	public function EditPriceGroupsForm() {
		if (self::isAdmin()) {
			$fields = new FieldSet(
				new HeaderField('PriceGroupHeader', _t('PriceGroup.PLURALNAME', 'Price groups')),
				$dm = new DialogDataObjectManager($this, 'PriceGroup', 'PriceGroup', array('Name' => _t('PriceGroup.NAME', 'Name')))
			);
			$dm->setAddTitle(_t('PriceGroup.SINGULARNAME', 'Price group'));
			
			$actions = new FieldSet();
			
		  	return new Form($this, "EditPriceGroupsForm", $fields, $actions);
		}
	}
	
	public function changebookingrequest() {
		return $this->changebooking();
	}
	
	public function changebooking() {
		$id = Convert::raw2sql($_GET['id']);
		$startDelta = Convert::raw2sql($_GET['startDelta']);
		$endDelta = Convert::raw2sql($_GET['endDelta']);
		$editType = Convert::raw2sql($_GET['editType']);
		if ($editType != 'single' && $editType != 'day' && $editType != 'all') {
			$editType = 'single';
		}
		
		$responseText = _t('Booking.SINGULARNAME', 'Booking') . " $id " . _t('ResourceBooking.EDITED', 'edited') . "!";
		$responseStatus = 200;
		
		$booking = DataObject::get_by_id('Booking', $id);
		if ($booking) {
			if ($booking->IsRecurring && $editType != 'single') {
				$bookingDay = date('w', strtotime($booking->Start));
				$recurringBookings = DataObject::get('Booking', "BookingID = {$booking->BookingID} AND IsRecurring = 1");
				foreach ($recurringBookings as $recurringBooking) {
					$recurringDay = date('w', strtotime($recurringBooking->Start));
					if ($editType == 'all' || ($editType == 'day' && $recurringDay == $bookingDay)) {
						$start = strtotime($recurringBooking->Start) + $startDelta;
						$end = strtotime($recurringBooking->End) + $endDelta;
						$recurringBooking->Start = date('Y-m-d H:i', $start);
						$recurringBooking->End = date('Y-m-d H:i', $end);
						$recurringBooking->write();
					}
				}
			}
			else {
				$start = strtotime($booking->Start) + $startDelta;
				$end = strtotime($booking->End) + $endDelta;
				$booking->Start = date('Y-m-d H:i', $start);
				$booking->End = date('Y-m-d H:i', $end);
				$booking->write();
			}
		}
		else {
			$responseText = _t('Booking.SINGULARNAME', 'Booking') . " $id " . _t('ResourceBooking.NOTFOUND', 'not found') . "!";
			$responseStatus = 400;
		}
		
		$response = new SS_HTTPResponse($responseText, $responseStatus);
		return $response;
	}
	
	public function IsLoggedIn() {
		return 'var isLoggedIn = true;';
	}
	
	public function EditBooking($data, $form) {
		$responseText = _t('Resource.SINGULARNAME', 'Resource') .' ' . _t('ResourceBooking.BOOKED', 'booked') . '!';
		$responseStatus = 200;
		
		if (isset($data['BookingEditID'])) {
			$bookingEditID = intval($data['BookingEditID']);
			$booking = DataObject::get_by_id('Booking', intval($bookingEditID));
			
			if ($booking) {
				$result = $form->saveInto($booking);
				if ($result == 1) {
					$booking->write();
				}
				else {
					$responseText = $result;
					if ($result != 'OK' && !isset($data['PreviewConfirmation'])) {
						$responseStatus = 400;
					}
				}
			}
			else {
				$responseText = 'Error: resource not found!';
				$responseStatus = 400;
			}
		}
		else {
			$responseText = 'Error: no resource id given!';
			$responseStatus = 400;
		}
		
		$response = new SS_HTTPResponse($responseText, $responseStatus);
		return $response;
	}
	
	public function BookResource($data, $form) {
		$responseText = _t('Resource.SINGULARNAME', 'Resource') .' ' . _t('ResourceBooking.BOOKED', 'booked') . '!';
		$responseStatus = 200;
		
		$booking = new Booking();
		$result = $form->saveInto($booking);
		if ($result == 1) {
			$booking->write();
		}
		else {
			$responseText = $result;
			if ($result != 'OK' && !isset($data['PreviewConfirmation'])) {
				$responseStatus = 400;
			}
		}
		
		$response = new SS_HTTPResponse($responseText, $responseStatus);
		return $response;
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
		$bookingTypes = DataObject::get('BookingType');
		if ($bookingTypes) {
			foreach ($bookingTypes as $bookingType) {
				if ($bookingType->Color) {
					$colors->push(new ArrayData(
						array(
							'backgroundColor' => '#' . $bookingType->Color,
							'borderColor' => '#' . $bookingType->Color,
							'label' => $bookingType->Name
						))
					);
				}
			}
		}
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#7094db',
				'borderColor' => '#7094db',
				'type' => 'request',
				'label' => _t('BookingRequest.PLURALNAME', 'Booking requests')
			))
		);
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#d10000',
				'borderColor' => '#d10000',
				'type' => 'booking',
				'label' => _t('Booking.PLURALNAME', 'Bookings')
			))
		);
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#d10000',
				'borderColor' => '#d10000',
				'type' => 'recurring',
				'label' => _t('BookingRequest.RECURRING', 'Återkommande')
			))
		);
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#000000',
				'borderColor' => '#000000',
				'type' => 'booking',
				'label' => _t('Booking.CANCELLED', 'Avbokningar')
			))
		);
		$colors->push(new ArrayData(
			array(
				'backgroundColor' => '#000000',
				'borderColor' => '#000000',
				'type' => 'request',
				'label' => _t('BookingRequest.REJECTED', 'Förkastade')
			))
		);
		return $colors;
	}
	
	public function NewRequests() {
		$select = 'Booking.*';
		$from = array('Booking');
		$where = "Status = 'Pending'";
		$orderBy = 'Start DESC';
		$groupBy = 'BookingID';
		$limit = 10;
		$adminID = Member::currentUserID();
		if (self::adminLevel() >= 1) {
			$from[] = 'LEFT JOIN Resource ON Resource.ID = Booking.ResourceID';
			$from[] = 'LEFT JOIN ResourceGroup ON ResourceGroup.ID = Resource.GroupID';
		}
		if (self::adminLevel() == 1) {
			$where .= " AND ResourceGroup.AdministratorID = $adminID";
		}
		if (self::adminLevel() == 2) {
			$from[] = 'LEFT JOIN ResourceOrganization ON ResourceOrganization.ID = ResourceGroup.OrganizationID';
			$where .= " AND ResourceOrganization.AdministratorID = $adminID";
		}
		
		$query = new SQLQuery($select, $from, $where, $orderBy, $groupBy, '', $limit);
		$records = $query->execute();
		$requests = singleton('DataObject')->buildDataObjectSet($records, 'DataObjectSet', $query, 'Booking');
		return $requests;
	}
	
	public function ShowBookingSection() {
		return true;
	}
	
	public function BookingReport() {
		if (self::isGroupAdmin()) {
			$report = new BookingReport($this, 'BookingReport');
			return $report;
		}
	}
	
	public function InvoiceReport() {
		if (self::isAdmin()) {
			$report = new InvoiceReport($this, 'InvoiceReport');
			return $report;
		}
	}
	
	public function PrintCalendarPDF() {
		Requirements::clear();
		Requirements::css('resourcebooking/css/fullcalendar-1.5.css');
		Requirements::css('resourcebooking/css/ResourceBooking.css');
		Requirements::css('resourcebooking/css/ResourceBookingPrint.css');
		
		$resourceID = (int)$_REQUEST['ResourceID'];
		$resourceName = '';
		if ($resourceID) {
			$resource = DataObject::get_by_id('Resource', $resourceID);
			if ($resource) {
				$resourceName = $resource->FullName();
			}
		}
		$view = $_REQUEST['View'];
		$viewText = '';
		if ($view == 'agendaDay') {
			$viewText = 'Dagsöversikt';
		}
		else if ($view == 'agendaWeek') {
			$viewText = 'Veckoöversikt';
		}
		else if ($view == 'month') {
			$viewText = 'Månadsöversikt';
		}
		
		$customFields = array(
			'CalendarHTML' => $_REQUEST['CalendarHTML'],
			'Title' => $_REQUEST['Title'],
			'ViewText' => $viewText,
			'ResourceName' => $resourceName
		);
		
		$downloadToken = time();
		if (isset($_REQUEST['DownloadToken'])) {
			$downloadToken = $_REQUEST['DownloadToken'];
		}
		Cookie::set('printDownloadToken', $downloadToken);
		return singleton('PDFRenditionService')->render($this->renderWith('PrintCalendarPDF', $customFields), 'browser', 'calendar.pdf');
	}
	
}

?>