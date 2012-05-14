<?php

class Booking extends DataObject {
	
	static $extensions = array(
		'PermissionExtension'
	);
	
	static $db = array(
		'BookingID' => 'Int',
		'IsRecurring' => 'Boolean',
		'Start' => 'SS_Datetime',
		'End' => 'SS_Datetime',
		'ConfirmBefore' => 'Date',
		'BookingText' => 'Text',
		'Status' => 'Enum("Pending,Preliminary,Accepted,Rejected,Cancelled","Pending")'
	);
	
	static $has_one = array(
		'Resource' => 'Resource',
		'CustomerGroup' => 'CustomerGroup',
		'Customer' => 'Customer',
		'Creator' => 'Member',
		'Type' => 'BookingType',
		'BillingDetails' => 'BillingDetails'
	);
	
	static $has_many = array(
		'ServiceBookings' => 'ServiceBooking',
		'LogItems' => 'BookingLogItem'
	);
	
	static $default_sort = 'Start ASC';
	
	static $searchable_fields = array(
		'ResourceID',
		'Status'
	);
	
	static $api_access = true;
	
	static $sortfield_override = array(
		'NiceStartTime' => 'Start',
		'NiceEndTime' => 'End'
	);
	
	private $justCreated = false;
	
	public function canView($member=null) {
		if (!$member)
			$member = Member::currentUser();
			
		if ($member) {
			if ($member instanceof Customer) {
				$resource = $this->Resource();
				if ($resource && $resource->canView($member))
					return true;
			}
		}
		return parent::canView($member);
	}	
	
	public function canCreate($member = null) {
		if (!$member)
			$member = Member::currentUser();
			
		if ($member) {
			if ($member instanceof Customer) {
				if (isset($_POST['ResourceID'])) {
					$resourceID = (int)$_POST['ResourceID'];
					$resource = DataObject::get_by_id('Resource', $resourceID);
								
					if ($resource && $resource->canView($member))
						return true;
				}
			}
		}
		return parent::canCreate($member);
	}		
	
	public function canEdit($member=null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		if ($this->CustomerID == $member->ID) {
			// one can only edit bookings which are not in the past
			if (strtotime($this->Start) > time()) {				
				return true;
			}
			else {
				return false;
			}
		}
		return parent::canEdit($member);
	}
	
	public function canDelete($member=null) {
		if ($this->canEdit($member) && !$this->Paid) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if (!$this->ID) {
			$this->justCreated = true;
			$this->CreatorID = Member::currentUserID();
		}
		else if (!$this->justCreated) {
			$changedFields = $this->getChangedFields(false, 2);
			if ($changedFields) {
				$logItem = new BookingLogItem();
				$logItem->Time = date('Y-m-d H:i');
				$logItem->BookingID = $this->ID;
				$logItem->UserID = Member::currentUserID();
				$logItem->Type = 'Edited';
				
				$logSaved = false;
				foreach ($changedFields as $fieldName => $values) {
					if ($fieldName != 'ClassName' && $fieldName != 'RecordClassName') {
						if (!$logSaved) {
							$logItem->write();
						}
						$fieldChange = new BookingLogItem_FieldChange();
						$fieldChange->FieldName = $fieldName;
						$fieldChange->Before = $values['before'];
						$fieldChange->After = $values['after'];
						$fieldChange->LogItemID = $logItem->ID;
						$fieldChange->write();
					}
				}
			}
		}
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		if (!$this->BookingID) {
			$this->BookingID = $this->getNextBookingID();
			$this->write();
		}
		
		if ($this->justCreated) {
			$logItem = new BookingLogItem();
			$logItem->Time = date('Y-m-d H:i');
			$logItem->Type = 'Created';
			$logItem->BookingID = $this->ID;
			$logItem->UserID = Member::currentUserID();
			$logItem->write();
			
			$this->justCreated = false;
		}
	}
	
	public function getBookingStatus() {
		$status = $this->Status;
		return _t("Booking.$status", $status);
	}
	
	private function getNextBookingID() {
		$newID = 0;
		// try to find the next booking id by finding the biggest existing booking id and adding one
		$query = new SQLQuery(array('MAX(BookingID)+1 AS NewBookingID'), array('Booking'));
		$results = $query->execute();
		if ($results->numRecords() > 0) {
			$record = $results->nextRecord();
			if ($record && isset($record['NewBookingID'])) {
				$newID = $record['NewBookingID'];
			}
		}
		
		if (!$newID) {
			// if calculation of new booking id fails, try to increment the id until we find a valid one,
			// as a last resort
			$newID = $this->ID;
			while (!$this->validNewBookingID($newID)) {
				$newID++;
			}
		}
		
		return $newID;
	}
	
	private function validNewBookingID($newID) {
		$newID = intval($newID);
		$query = new SQLQuery(array('*'), array('Booking'), array("BookingID = $newID"));
		$results = $query->execute();
		if ($results->numRecords() > 0) {
			return false;
		}
		
		return true;
	}
	
	public static function IsAllowed($resourceID, $start, $end, $status, &$reason, $bookingID = null) {
		$member = Member::currentUser();
		$resource = DataObject::get_by_id('Resource', $resourceID);
		
		// check that current user is ok
		if (!($member && ($member->inGroup('administrators') || $member->inGroup('resourcebookingadmin') || $member->inGroup('resourceorganizationadmin') || $member->inGroup('resourcegroupadmin') || $member->inGroup('customer')))) {
			$reason = 'User does not have sufficient priviliges!';
			return false;
		}
		
		// check that we found a valid resource object
		if (!$resource) {
			$reason = 'Invalid resource!';
			return false;
		}
		
		// check that specified start time is ok
		if (!is_numeric($start)) {
			$reason = 'Invalid start time!';
			return false;
		}
		
		// check that specified end time is ok
		if (!(is_numeric($end) && $end > $start )) {
			$reason = 'Invalid end time!';
			return false;
		}
		
		// check that start time is in resource's available time
		$startDay = date('Y-m-d', $start);
		$startTime = strtotime($startDay) + date('G', strtotime($resource->AvailableFrom))*60*60 + date('i', strtotime($resource->AvailableFrom))*60;
		$endDay = date('Y-m-d', $end);
		$endTime = strtotime($endDay) +  date('G', strtotime($resource->AvailableTo))*60*60 + date('i', strtotime($resource->AvailableTo))*60;
		if (!($start >= $startTime && $start <= $endTime)) {
			$reason = 'Start time outside of resource\'s available time!';
			return false;
		}
						
		// check that end time is in resource's available time
		if (!($end <= $endTime)) {
			$reason = 'End time outside of resource\'s available time!';
			return false;
		}
		
		// extra check for bookings that are accepted (i.e. no conflicting accepted bookings allowed)
		if ($status == 'Accepted') {
			// check that resource if free on given time
			$bookingStart = date('Y-m-d H:i', $start);
			$bookingEnd = date('Y-m-d H:i', $end);
			$existingBookingIDCheck = '';
			if ($bookingID) {
				$existingBookingIDCheck = " AND ID != $bookingID";
			}
			$bookings = DataObject::get('Booking', "ResourceID = $resourceID AND Status = 'Accepted' AND ((Start <= '$bookingStart' AND End > '$bookingStart') OR (Start >= '$bookingStart' AND Start < '$bookingEnd')) $existingBookingIDCheck");
			if ($bookings)  {
				$reason = 'Conflicting booking found!';
				return false;
			}
		}
		
		// booking allowed! \o/
		return true;
	}
	
	public function getCMSFields($params = null) {
		$fields = new FieldSet();
			
		$fields->push(
			$tabSet = new DialogTabSet('Tabs',
				$this->GeneralTab(),
				$this->RepeatTab(),
				$this->MiscTab()
			)
		);
		
		if ($this->Resource()) {
			// billing and confirmation options are only relevant for group admins and above 
			if ($this->Resource()->adminLevel() >= 1) {
				$tabSet->insertBefore($this->EconomicsTab(), 'Misc');
				$tabSet->insertAfter($this->ConfirmationTab(), 'Misc');
			}
			
			if ($this->ID) {
				// log is only for organization admins and above
				if ($this->Resource()->adminLevel() >= 2) {
					$tabSet->push($this->LogTab());
				}
			}
		}
		
		$fields->push(new BookingRequirementsField('Requirements'));
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function ResourceBookingFormFields() {
		$fields = new FieldSet(
			new LabelField('ResourceNameLabel', _t('Resource.SINGULARNAME', 'Resource') . ':'),
			$nameLabel = new LabelField('ResourceName', '', '', true),
			new HiddenField('ResourceID'),
			$startDate = new DateField('StartDate', _t('Booking.STARTDATE', 'Startdatum') . ':'),
			new AdvancedDropdownField('Start',''),
			$endDate = new DateField('EndDate', _t('Booking.ENDDATE', 'Slutdatum') . ':'),
			new AdvancedDropdownField('End', '')
		);
		$nameLabel->addExtraClass('ResourceNameLabel');
		$startDate->setConfig('showcalendar', true);
		$startDate->setConfig('dateformat', 'dd.MM.yyyy');
		$endDate->setConfig('showcalendar', true);
		$endDate->setConfig('dateformat', 'dd.MM.yyyy');
		
		return $fields;
	}
	
	private function GeneralTab() {
		$generalTab = new Tab('General', _t('ResourceBookingForm.GENERAL', 'Allmänt'));
		$generalTab->setChildren(self::ResourceBookingFormFields());
		$showAddAndEditButtons = (ResourceBookingExtension::adminLevel() >= 1);
		$customerGroups = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$mainGroups = DataObject::get('CustomerGroup', 'ParentID = 0');
		if ($mainGroups) {
			foreach ($mainGroups as $mainGroup) {
				$customerGroups[$mainGroup->ID] = $mainGroup->Name;
				if ($mainGroup->Children()) {
					foreach ($mainGroup->Children() as $childGroup) {
						$customerGroups[$childGroup->ID] = $mainGroup->Name . ' - ' . $childGroup->Name;
					}
				}
			}
		}
		$activeCustomers = array('', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$customers = DataObject::get('Customer', 'Inactive = 0');
		if ($customers) {
			$activeCustomers = $customers->map('ID', 'Name', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		}
		
		if ($this->Resource() && $this->Resource()->isAdmin()) {
			if (!$this->ID) {
				$bookingIDOptions = array(0 => 'Automatisk', 1 => 'Specifik');
				$generalTab->push(new OptionsetField('SpecificBookingID', _t('ResourceBookingForm.SPECIFICBOOKINGID', 'Boknings-id:'), $bookingIDOptions));
				$generalTab->push(new NumericField('BookingID', '', null, 11));
			}
			else {
				$generalTab->push(new NumericField('BookingID', 'Boknings-id:', $this->BookingID, 11));
			}
			
			$generalTab->push(new AdvancedDropdownField('CustomerOrganizationID', 'Organisation:', 'CustomerOrganization', 0, $showAddAndEditButtons, $showAddAndEditButtons, 'CustomerOrganizationSelected'));
			$generalTab->push($groupSelection = new AdvancedDropdownField('CustomerGroupID', 'Avdelning/grupp:', $customerGroups, 0, $showAddAndEditButtons, $showAddAndEditButtons, 'CustomerGroupSelected'));
			$generalTab->push($customerSelection = new AdvancedDropdownField('CustomerID', 'Kontaktperson:', $activeCustomers, 0, $showAddAndEditButtons, $showAddAndEditButtons, 'CustomerSelected'));
			$customerSelection->setSourceClass('Customer');
			$groupSelection->setSourceClass('CustomerGroup');
		}
		$generalTab->push(new AdvancedDropdownField('Type', 'Bokningstyp:', 'BookingType'));
		
		if (!$this->ID) {
			// new booking
			if ($this->Resource()) {
				$resourceName = $this->Resource()->Name;
				if ($this->Resource()->ParentID) {
					$resourceName = $this->Resource()->Parent()->Name . ' - ' . $resourceName;
				}
				$generalTab->fieldByName('ResourceName')->setTitle($resourceName);
				$generalTab->fieldByName('ResourceName')->addExtraClass('nolabel');
				$generalTab->fieldByName('ResourceID')->setValue($this->Resource()->ID);
			
				$availableTimes = array();
				$currentTime = new Zend_Date($this->Resource()->getAvailableFromTime(time()));
				$availableTo = new Zend_Date($this->Resource()->getAvailableToTime(time()));
				while($currentTime->getTimestamp() <= $availableTo->getTimestamp()) {
					$currHour = $currentTime->get(Zend_Date::HOUR_SHORT);
					$currMinute = $currentTime->get(Zend_Date::MINUTE_SHORT);
					$availableTimes[$currHour*60+$currMinute] = $currHour . ':' . $currMinute;
					$currentTime->addMinute(15);
				}
				$generalTab->fieldByName('Start')->setSource($availableTimes);
				$generalTab->fieldByName('End')->setSource($availableTimes);
				
				if ($this->Resource()->adminLevel() >= 1) {
					$generalTab->push(new OptionsetField('Status', 'Bokningsstatus:', array('Accepted' => 'Bokning', 'Pending' => 'Reservation'), 'Accepted'));
				}
			}
		}
		else {
			// existing booking
			if ($this->IsRecurring) {
				// add last start and end in the recurring series, for convinience
				$lastRecurring = DataObject::get('Booking', "BookingID = {$this->BookingID} AND IsRecurring = 1", 'Start DESC', '', 1);
				if ($lastRecurring) {
					$lastRecurring = $lastRecurring->First();
					$generalTab->insertAfter(new LiteralField('LastStart', '<div id="LastStart"><b>Sista återkommande:</b> ' . $lastRecurring->NiceStartTime() . '</div>'), 'Start');
					$generalTab->insertAfter(new LiteralField('LastEnd', '<div id="LastEnd"><b>Sista återkommande:</b> ' . $lastRecurring->NiceEndTime(false) . '</div>'), 'End');
				}
			}
			$generalTab->push(new HiddenField('BookingEditID', '', $this->ID));
			
			if ($this->Resource()->adminLevel() >= 1) { 
				$statuses = array('Accepted' => 'Bokning', 'Pending' => 'Reservation');
				if ($this->Status == 'Accepted' || $this->Status == 'Cancelled') {
					$statuses['Cancelled'] = 'Avbokad';
				}
				else if ($this->Status == 'Pending' || $this->Status == 'Rejected') {
					$statuses['Rejected'] = 'Förkastad';
				}
				$generalTab->push(new OptionsetField('Status', 'Bokningsstatus:', $statuses, $this->Status));
			}
			
			$generalTab->fieldByName('ResourceName')->setTitle($this->Resource()->Name);
			$generalTab->fieldByName('ResourceName')->addExtraClass('nolabel');
			$generalTab->fieldByName('ResourceID')->setValue($this->ResourceID);
			$generalTab->fieldByName('StartDate')->setValue(date('d.m.Y', strtotime($this->Start)));
			$generalTab->fieldByName('EndDate')->setValue(date('d.m.Y', strtotime($this->End)));
				
			$availableTimes = array();
			$currentTime = new Zend_Date($this->Resource()->getAvailableFromTime(strtotime($this->Start)));
			$availableTo = new Zend_Date($this->Resource()->getAvailableToTime(strtotime($this->Start)));
			while($currentTime->getTimestamp() <= $availableTo->getTimestamp()) {
				$currHour = $currentTime->get(Zend_Date::HOUR_SHORT);
				$currMinute = $currentTime->get(Zend_Date::MINUTE_SHORT);
				$availableTimes[$currHour*60+$currMinute] = $currHour . ':' . $currMinute;
				$currentTime->addMinute(15);
			}
			$startTime = new Zend_Date(strtotime($this->Start));
			$startHour = $startTime->get(Zend_Date::HOUR_SHORT);
			$startMinute = $startTime->get(Zend_Date::MINUTE_SHORT);
			$generalTab->fieldByName('Start')->setSource($availableTimes);
			$generalTab->fieldByName('Start')->setValue($startHour*60 + $startMinute);
			$endTime = new Zend_Date(strtotime($this->End));
			$endHour = $endTime->get(Zend_Date::HOUR_SHORT);
			$endMinute = $endTime->get(Zend_Date::MINUTE_SHORT);
			$generalTab->fieldByName('End')->setSource($availableTimes);
			$generalTab->fieldByName('End')->setValue($endHour*60 + $endMinute);
				
			$generalTab->fieldByName('CustomerOrganizationID')->setValue($this->CustomerGroup()->OrganizationID);
			$generalTab->fieldByName('CustomerGroupID')->setValue($this->CustomerGroupID);
			$generalTab->fieldByName('CustomerID')->setValue($this->CustomerID);
				
			$generalTab->fieldByName('Type')->setValue($this->TypeID);
		}
		
		return $generalTab;
	}
	
	private function RepeatTab() {
		$repeatTab = new Tab('Repeat', _t('ResourceBookingForm.REPEAT', 'Återkommande'));
		$repeatTab->push(new CheckboxField('Repeat', _t('ResourceBookingForm.REPEAT', 'Återkommande') . ':'));
		
		if (!$this->ID) {
			// repeat controls
			$repeatTab->push(
				$repeatGroup = new FieldGroup(
					new AdvancedDropdownField('RepeatType', 'Upprepas:', array('' => 'dagligen', 'w' => 'veckovis', 'm' => 'månadsvis'), '', false, false, 'RepeatTypeChanged'),
					new AdvancedDropdownField('RepeatEach', 'Upprepa efter:', array('' => 1, 2 => 2, 3 => 3, 4 => 4), '', false, false, 'RepeatEachChanged'),
					new CheckboxSetField('RepeatDays', 'Upprepa varje:', array(1 => 'Må', 2 => 'Ti', 3 => 'On', 4 => 'To', 5 => 'Fr', 6 => 'Lö', 7 => 'Sö')),
					new LabelField('RepeatEachLabel', 'dagar', '', true),
					new OptionsetField('RepeatStop', 'Slutar:', array(0 => 'Efter', 1 => 'På')),
					new NumericField('RepeatTimes', '', 10),
					new LabelField('RepeatTimesLabel', 'gånger', '', true),
					new LiteralField('Summary', '<label>Sammanfattning:</label> <p id="RepeatSummary"></p>'),
					new LiteralField('CheckRepeat', '<input id="CheckRepeatButton" type="button" value="Kolla" /><div id="CheckResult"></div>')
				)
			);
			$repeatTab->push($repeatEndDay = new DateField('RepeatEndDay', ''));
			$repeatGroup->setID('RepeatGroup');
			$repeatEndDay->setConfig('dateformat', 'dd.MM.YYYY');
			$repeatEndDay->setConfig('showcalendar', true);
		}
		else {
			$repeatField = $repeatTab->fieldByName('Repeat')->performReadonlyTransformation();
			$repeatField->setValue($this->IsRecurring);
			$repeatTab->replaceField('Repeat', $repeatField);
			if ($this->IsRecurring) {
				$repeatTab->push(
					$repeatDOM = new DialogDataObjectManager(
						$this,
						'RepeatBookings', 
						'Booking',
						array(
							'NiceStartTime' => 'Starttid',
							'NiceEndTime' => 'Sluttid',
							'getBookingStatus' => 'Status'
						),
						null,
						"BookingID = {$this->BookingID} AND IsRecurring = 1 AND ID != {$this->ID}"
					)
				);
				$repeatDOM->popupClass = 'BookingEditPopup';
				$repeatDOM->setPermissions(array('add', 'edit', 'delete', 'duplicate'));
				$repeatDOM->setHighlightConditions(
					array(
						array(
							'rule' => '$Conflicting() == true',
							'class' => 'conflicting'
						)
					)
				);
			
				$repeatTab->push(
					$repeatEditOptions = new OptionsetField(
						'RecurringEditType', 
						_t('ResourceBookingForm.RECURRINGEDITTYPE', 'Editera:'),
						array(
							'single' => 'Ändra bara denna bokning',
							'day' => 'Ändra alla återkommande på denna veckodag',
							'all' => 'Ändra alla återkommande'
						),
							'single'
					)
				);
			}
		}
		
		return $repeatTab;
	}
	
	private function EconomicsTab() {
		$economicsTab = new Tab('Economics', _t('ResourceBookingForm.ECONOMICS', 'Fakturering'));
		$economicsTab->push(new AdvancedDropdownField('Billing', _t('ResourceBookingForm.BILLING', 'Fakturering') . ':', array('' => _t('ResourceBooking.NONESELECTED', '(None selected)'), 1 => 'Ingen fakturering', 2 => 'Fakturering')));
		$economicsTab->push($billingGroup = new FieldGroup());
		
		if ($this->Resource()) {
			$priceGroups = $this->Resource()->PriceGroups();
			if ($priceGroups && $priceGroups->Count() > 0) {
				$priceGroups = $priceGroups->map('ID', 'Name', _t('ResourceBooking.NONESELECTED', '(None selected)'));
				$subsidizers = DataObject::get('CustomerGroup', 'CanSubsidize = 1');
				if ($subsidizers) {
					$subsidizers = $subsidizers->map('ID', 'Name', _t('ResourceBooking.NONESELECTED', '(None selected)'));
				}
				else {
					$subsidizers = array('' => _t('ResourceBooking.NONESELECTED', '(None selected)'));
				}
				$billingGroup->push(new CheckboxField('Subsidize', _t('ResourceBookingForm.SUBSIDIZE', 'Subventionera') . ':'));
				$billingGroup->push(
					$subsidizeGroup = new FieldGroup(
						new AdvancedDropdownField('PriceGroup', 'Prisgrupp:', $priceGroups),
						new AdvancedDropdownField('Subsidizer', 'Subventionerare:', $subsidizers)
					)
				);
				$subsidizeGroup->setID('SubsidizeGroup');
		
				$economicsTab->push(new LiteralField('PriceGroups', '
					<script type="text/javascript">
					//<![CDATA[
						' . PriceGroup::PriceGroups() . '
					//]]>
					</script>
				'));
			}
			
			$economicsTab->push(new LiteralField('TaxTypes', '
				<script type="text/javascript">
				//<![CDATA[
					' . TaxType::TaxTypes() . '
				//]]>
				</script>
			'));
			
			$provisionPercent = $this->Resource()->Group()->ProvisionPercent;
			if ($provisionPercent > 0) {
				$billingGroup->push(new LiteralField('ProvisionPercent', "
										<div class='field'>
											<label>Provision:</label><input id='Provision' type='text' name='ProvisionPercent' value='$provisionPercent' /> %
										</div>
									"
				));
			}
		}
		$billingGroup->push(new AdvancedDropdownField('TaxType', 'Moms:', 'TaxType'));
		$billingGroup->push(new AdvancedDropdownField('CustomerType', 'Åldersgrupp:', 'CustomerType'));
		$billingGroup->push(
			$priceGroup = new FieldGroup(
				new LabelField('Price', _t('ResourceBookingForm.PRICE', 'Pris') . ':'),
				new LiteralField('PriceTable', "
							<div class='field'>
								<table id='PriceTable'>
									<tr>
										<th>&nbsp;</th>
										<th>netto (€)</th>
										<th>brutto (€)</th>
										<th></th>
									</tr>
									<tr>
										<th>Timpris</th>
										<td id='HourPriceTaxExcl'>{$this->Resource()->Price}</td>
										<td id='HourPriceTaxIncl'>{$this->Resource()->Price}</td>
										<td>timmar: <span id='TotalHours'>0</span></td>
									</tr>
									<tr>
										<th>Grundpris</th>
										<td id='NormalPriceTaxExcl'></td>
										<td id='NormalPriceTaxIncl'></td>
										<td></td>
									</tr>
									<tr>
										<th>Verkligt pris</th>
										<td id='CustomPriceTaxExcl'><input id='CustomPrice' type='text' value='0' name='CustomPrice' /></td>
										<td id='CustomPriceTaxIncl'></td>
										<td></td>
									</tr>
									<tr>
										<th>Totalpris</th>
										<td id='TotalPriceTaxExcl'></td>
										<td id='TotalPriceTaxIncl'></td>
										<td></td>
									</tr>
								</table>
							</div>
						")
			)
		);
		$billingAddress = '';
		if ($this->BillingDetailsID) {
			$billingAddress = nl2br($this->BillingDetails()->BillingAddress);
		}
		$billingGroup->push(
		$billingAddressGroup = new FieldGroup(
		new LabelField('BillingAddressLabel', _t('ResourceBookingForm.BILLINGADDRESS', 'Faktureringsadress') . ':'),
		new LiteralField('BillingAddressField', "
							<div id='BillingAddressField' class='field'>
								<div id='BillingAddressHolder'>
									<p id='BillingAddress'>$billingAddress</p>
								</div>
								<div id='BillingAddressChoice'>
									<input id='CustomBillingAddress' name='CustomBillingAddress' type='checkbox' /> Specifik för denna bokning
									<input id='EditBillingAddress' name='EditBillingAddress' type='button' value='Editera kundens adress' />
								</div>
							</div>
						")
		)
		);
		$billingGroup->setID('BillingGroup');
		$priceGroup->setID('PriceGroup');
		
		if ($this->ID)  {
			$billing = 1;
			if ($this->BillingDetails() && $this->BillingDetails()->ID) {
				$billing = 2;
				if ($this->BillingDetails()->SubsidizerID) {
					$billingGroup->fieldByName('Subsidize')->setValue(1);
					$subsidizeGroup->fieldByName('PriceGroup')->setValue($this->BillingDetails()->PriceGroupID);
					$subsidizeGroup->fieldByName('Subsidizer')->setValue($this->BillingDetails()->SubsidizerID);
				}
				$billingGroup->fieldByName('TaxType')->setValue($this->BillingDetails()->TaxTypeID);
			}
			$economicsTab->fieldByName('Billing')->setValue($billing);
		}
		
		return $economicsTab;
	}
	
	private function MiscTab() {
		$miscTab = new Tab('Misc', _t('ResourceBookingForm.MISC', 'Övrigt'));
		$miscTab->push(new TextField('BookingText', _t('ResourceBookingForm.BOOKINGTEXT', 'Bokningstext') . ':'), $this->BookingText, 25);
		$miscTab->push(new TextareaField('InfoText', _t('ResourceBookingForm.INFOTEXT', 'Inforuta') . ':'));
		if ($this->Resource() && $this->Resource()->AllowedServices() && $this->Resource()->AllowedServices()->Count() > 0) {
			// only first service for now...
			$service = $this->Resource()->AllowedServices()->First();
			$availableTimes = array();
			$currentTime = new Zend_Date(date('Y-m-d 07:00'));
			$availableTo = new Zend_Date(date('Y-m-d 22:00'));
			while($currentTime->getTimestamp() <= $availableTo->getTimestamp()) {
				$currHour = $currentTime->get(Zend_Date::HOUR_SHORT);
				$currMinute = $currentTime->get(Zend_Date::MINUTE_SHORT);
				$availableTimes[$currHour*60+$currMinute] = $currHour . ':' . $currMinute;
				$currentTime->addMinute(15);
			}
			$groupResources = $this->Resource()->Group()->Resources();
			$allowedResources = array();
			if ($groupResources) {
				foreach ($groupResources as $groupResource) {
					if ($groupResource->AllowedServices() && $groupResource->AllowedServices()->containsIDs(array($service->ID))) {
						$allowedResources[$groupResource->ID] = $groupResource->Name;
					}
				}
			}
			$miscTab->push(new CheckboxField('Service', $service->Name . ':'));
			$miscTab->push(
				$serviceGroup = new FieldGroup(
					new HiddenField('ServiceID', '', $service->ID),
					$serviceTimeGroup = new FieldGroup(
						$serviceDate = new DateField('ServiceDate', 'Tid' . ':'),
						$serviceTime = new AdvancedDropdownField('ServiceTime', '', $availableTimes)
					),
					$servicePlace = new AdvancedDropdownField('ServicePlace', 'Plats:', $allowedResources, $this->ResourceID),
					$serviceAmountGroup = new FieldGroup(
						$serviceAmountField = new NumericField('ServiceAmount', 'Antal' . ':'),
						new LiteralField('ServicePrice', "<div class='field'>st ($service->Price €/st)</div>")
					),
					new LabelField('ServiceOptionsLabel', 'Alternativ' . ':'),
					$serviceOptionsGroup = new FieldGroup(),
					new TextareaField('ServiceExtraInfo', 'Extra info:', 2)
				)
			);
			$serviceTimeGroup->setID('ServiceTimeGroup');
			$serviceDate->setConfig('dateformat', 'dd.MM.YYYY');
			$serviceDate->setConfig('showcalendar', true);
			$serviceAmountField->addExtraClass('ServiceAmount');
			$serviceGroup->setID('ServiceGroup');
			$serviceAmountGroup->setID('ServiceAmountGroup');
			$serviceOptionsGroup->setID('ServiceOptionsGroup');
			$servicePlace->addExtraClass('ServicePlace');
			$serviceOptionGroups= array();
			if ($service->Options() && $service->Options()->Count() > 0) {
				foreach ($service->Options() as $option) {
					$serviceOptionGroup = new FieldGroup();
					$serviceOptionGroup->addExtraClass('ServiceOptionGroup');
					$serviceOptionField = new NumericField('ServiceOption_' . $option->ID, $option->Name . ':');
					$serviceOptionField->addExtraClass('ServiceAmount');
					$serviceOptionPrice = new LiteralField('ServiceOptionPrice', "<div class='field'>st ($option->Price €/st)</div>");
					$serviceOptionGroup->push($serviceOptionField);
					$serviceOptionGroup->push($serviceOptionPrice);
					$serviceOptionsGroup->push($serviceOptionGroup);
					$serviceOptionGroups[$option->ID] = $serviceOptionGroup;
				}
			}
		}
		
		if ($this->ID) {
			if ($this->ServiceBookings() && $this->ServiceBookings()->Count() > 0) {
				$miscTab->fieldByName('Service')->setValue(1);
				// only first service booking for now...
				$serviceBooking = $this->ServiceBookings()->First();
				$startTime = new Zend_Date(strtotime($serviceBooking->Start));
				$startHour = $startTime->get(Zend_Date::HOUR_SHORT);
				$startMinute = $startTime->get(Zend_Date::MINUTE_SHORT);
				$serviceDate->setValue(date('d.m.Y', strtotime($serviceBooking->Start)));
				$serviceTime->setValue($startHour*60 + $startMinute);
				$serviceAmountField->setValue($serviceBooking->Amount);
				$serviceGroup->fieldByName('ServiceExtraInfo')->setValue($serviceBooking->ExtraInfo);
				if ($serviceBooking->ServiceOptionValues()) {
					foreach ($serviceBooking->ServiceOptionValues() as $option) {
						$serviceOptionGroups[$option->ID]->fieldByName('ServiceOption_' . $option->ID)->setValue($option->Amount);
					}
				}
			}
		}
		
		return $miscTab;
	}
	
	private function ConfirmationTab() {
		$confirmationTab = new Tab('Confirmation', _t('ResourceBookingForm.CONFIRMATION', 'Bekräftelse'));
		$languages = array('sv_SE' => 'Svenska', 'fi_FI' => 'Finska', 'en_US' => 'Engelska');
		$confirmationTab->push(new CheckboxField('Confirmation', _t('ResourceBookingForm.CONFIRMATION', 'Skicka bekräftelse') . ':'));
		$confirmationTab->push(
			$confirmationGroup = new FieldGroup(
				$confirmDate = new DateField('ConfirmBefore', _t('ResourceBookingForm.CONFIRMBEFORE', 'Bekräftelsetid') . ':'),
				new AdvancedDropdownField('ConfirmLanguage', _t('ResourceBookingForm.CONFIRMLANGUAGE', 'Språk') . ':', $languages, 'sv_SE'),
				new CheckboxField('PDFConfirmation', _t('ResourceBookingForm.PDFCONFIRMATION', 'PDF') . ':'),
				new CheckboxField('EMailConfirmation', _t('ResourceBookingForm.EMAILCONFIRMATION', 'E-post') . ':'),
				$confirmationEmailGroup = new FieldGroup(
					new TextField('ConfirmationEmails', _t('ResourceBookingForm.CONFIRMATIONEMAILS', 'E-post adresser') . ':')
				),
				new TextareaField('ConfirmationText', _t('ResourceBookingForm.CONFIRMATIONTEXT', 'Meddelande') . ':'),
				new LiteralField('PreviewConfirmation', '<input id="PreviewConfirmationButton" type="button" value="Förhandsgranska" />')
			)
		);
		$confirmationGroup->setID('ConfirmationGroup');
		$confirmationEmailGroup->setID('ConfirmationEmailGroup');
		$confirmDate->setConfig('dateformat', 'dd.MM.YYYY');
		$confirmDate->setConfig('showcalendar', true);
		
		return $confirmationTab;
	}
	
	private function LogTab() {
		$logTab = new Tab('Log', _t('ResourceBookingForm.LOG', 'Logg'));
		
		$logItems = $this->LogItems();
		if ($logItems && $logItems->Count() > 0) {
			$logTable = "
							<table class='LogTable'>
								<tr>
									<th class='col1'>Tid</th>
									<th class='col2'>Användare</th>
									<th class='col3'>Text</th>
								</tr>
						";
			foreach ($logItems as $logItem) {
				$logTable .= "
								<tr>
									<td class='col1'>{$logItem->NiceTime()}</td>
									<td class='col2'>{$logItem->User()->getName()}</td>
									<td class='col3'>{$logItem->LogText()}</td>
								</tr>
							 ";
			}
			$logTable .= "</table>";
			$logTab->push(new LiteralField('LogTable', $logTable));
		}
		
		return $logTab;
	}
	
	public function StartDate() {
		return date('Y-m-d', strtotime($this->Start));
	}
	
	public function NiceStartTime() {
		return date('d.m.Y H:i', strtotime($this->Start));
	}
	
	public function NiceEndTime($shortFormat = true) {
		$format = 'd.m.Y H:i';
		if (date('d.m.y', strtotime($this->Start)) == date('d.m.y', strtotime($this->End)) && $shortFormat) {
			$format = 'H:i';
		}
		return date($format, strtotime($this->End));
	}
	
	public function NiceConfirmBefore() {
		return date('d.m.Y', strtotime($this->ConfirmBefore));
	}
	
	public function Price($includeTax = true, $includeSubsidation = true) {
		$bookingPrice = 0;
		if ($this->Resource()) {
			$hours = $this->Duration('h');
			$price = $this->Resource()->Price;
			if ($this->BillingDetails() && $this->BillingDetails()->CustomPrice > 0) {
				$price = $this->BillingDetails()->CustomPrice;
			}
			$bookingPrice = $price*$hours;
			if ($this->BillingDetails() && $this->BillingDetails()->PriceGroup() && $includeSubsidation) {
				$bookingPrice = $bookingPrice * ((100 - $this->BillingDetails()->PriceGroup()->SubsidizePercent)/100);
			}
			if ($this->BillingDetails() && $this->BillingDetails()->TaxType() && $includeTax) {
				$bookingPrice = $bookingPrice * ((100 + $this->BillingDetails()->TaxType()->TaxPercent)/100);
			}
		}
		return round($bookingPrice, 2);
	}
	
	public function Conflicting() {
		$reason = '';
		return !Booking::IsAllowed($this->ResourceID, strtotime($this->Start), strtotime($this->End), 'Accepted', $reason, $this->ID);
	}
	
	public function Duration($type = 's') {
		$duration = strtotime($this->End) - strtotime($this->Start); // in seconds
		if ($type == 'h') {
			$duration = $duration/(60*60);
		}
		else if ($type == 'm') {
			$duration = $duration/60;
		}
		return $duration;
	}
	
}

class BookingRequirementsField extends FormField {
	
	function Field() {
		Requirements::javascript('resourcebooking/javascript/jquery.validate.min.js');
		Requirements::javascript('resourcebooking/javascript/BookingControls.js');
		Requirements::javascript('resourcebooking/javascript/RepeatControls.js');
		Requirements::javascript('resourcebooking/javascript/BillingControls.js');
		Requirements::javascript('resourcebooking/javascript/ConfirmationControls.js');
		Requirements::javascript('resourcebooking/javascript/MiscControls.js');
		Requirements::css('resourcebooking/css/ResourceBooking.css');
		//Requirements::css('resourcebooking/css/ResourceBookingAdmin.css');
		Requirements::block('cms/css/cms_right.css');
		Requirements::css('themes/blackcandy/css/layout.css');
		Requirements::css('themes/blackcandy/css/form.css');
		Requirements::customCSS('html, body {background: #fff !important;}');
		Requirements::customScript("var resourceBookingAdminHref = '{$this->BaseHref()}admin/resourcebooking/';");
		return '';
	}
	
	function FieldHolder() {
		return $this->Field();
	}
	
}

class BookingEditPopup extends DialogDataObjectManager_Popup {
	
	function loadDataFrom($data, $clearMissingFields = false, $fieldList = null) {
		// do nothing, getCMSFields already sets proper values in the fieldset
		return;
	}
	
	function saveInto($booking, $fieldList = null) {
		$result = 1;
		$data = $_POST;
		// Important: escape all data before doing anything with it!
		//            (to at least try to prevent sql-injection attacks)
		$sqlSafeData = Convert::raw2sql($data);
		if (isset($sqlSafeData['ResourceID']) && isset($sqlSafeData['Start']) && isset($sqlSafeData['End'])) {
			$resource = DataObject::get_by_id('Resource', intval($sqlSafeData['ResourceID']));
			if ($resource) {
				// gather general booking details
				$bookingDetails = array();
				$bookingDetails['ResourceID'] = intval($sqlSafeData['ResourceID']);
				$bookingDetails['CustomerGroupID'] = 0;
				$bookingDetails['CustomerID'] = 0;
				$bookingDetails['Start'] = $sqlSafeData['Start'];
				$bookingDetails['End'] = $sqlSafeData['End'];
				$bookingDetails['Status'] = 'Pending';
				if (isset($sqlSafeData['CustomerGroupID']) && $sqlSafeData['CustomerGroupID']) {
					$bookingDetails['CustomerGroupID'] = intval($sqlSafeData['CustomerGroupID']);
				}
				else if (is_a(Member::currentUser(), 'Customer')) {
					$bookingDetails['CustomerGroupID'] = Member::currentUser()->GroupID;
				}
				if (isset($sqlSafeData['CustomerID']) && $sqlSafeData['CustomerID']) {
					$bookingDetails['CustomerID'] = intval($sqlSafeData['CustomerID']);
				}
				else if (is_a(Member::currentUser(), 'Customer')) {
					$bookingDetails['CustomerID'] = Member::currentUserID();
				}
				if (isset($sqlSafeData['Status']) && $sqlSafeData['Status']) {
					$bookingDetails['Status'] = $sqlSafeData['Status'];
				}
				$bookingDetails['TypeID'] = intval($sqlSafeData['Type']);
				$bookingDetails['BookingID'] = null;
				if (isset($sqlSafeData['BookingID']) && $sqlSafeData['BookingID']) {
					$bookingDetails['BookingID'] = intval($sqlSafeData['BookingID']);
				}
				$repeat = false;
				if (isset($sqlSafeData['Repeat']) && (intval($sqlSafeData['Repeat']) == 1 || $sqlSafeData['Repeat'] == 'Ja')) {
					if (!isset($sqlSafeData['BookingEditID'])) {
						$repeat = true;
					}
					if (isset($sqlSafeData['RecurringEditType'])) {
						$bookingDetails['RecurringEditType'] = $sqlSafeData['RecurringEditType'];
					}
					else {
						$bookingDetails['RecurringEditType'] = 'single';
					}
					$bookingDetails['IsRecurring'] = 1;
				}
				else {
					$bookingDetails['IsRecurring'] = $booking->IsRecurring;
					$bookingDetails['RecurringEditType'] = 'single';
				}
				$bookingDetails['BookingText'] = '';
				if (isset($sqlSafeData['BookingText']) && $sqlSafeData['BookingText']) {
					$bookingDetails['BookingText'] = $sqlSafeData['BookingText'];
				}
				$bookingDetails['ConfirmationText'] = '';
				if (isset($data['ConfirmationText']) && $data['ConfirmationText']) {
					$bookingDetails['ConfirmationText'] = nl2br($data['ConfirmationText'], true);
				}
				$bookingDetails['ConfirmationEmails'] = '';
				if (isset($sqlSafeData['ConfirmationEmails']) && $sqlSafeData['ConfirmationEmails']) {
					$bookingDetails['ConfirmationEmails'] = $sqlSafeData['ConfirmationEmails'];
				}
				$bookingDetails['ConfirmBefore'] = date('Y-m-d', time() + 60*60*24*7*2);
				if (isset($sqlSafeData['ConfirmBefore']) && $sqlSafeData['ConfirmBefore']) {
					$matches = array();
					preg_match('/^(\d\d?).(\d\d?).(\d\d\d\d)$/', $sqlSafeData['ConfirmBefore'], $matches);
					if (count($matches) == 4) {
						$bookingDetails['ConfirmBefore'] = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
					}
				}
				$bookingDetails['ConfirmLanguage'] = 'sv_SE';
				if (isset($sqlSafeData['ConfirmLanguage']) && $sqlSafeData['ConfirmLanguage']) {
					$bookingDetails['ConfirmLanguage'] = $sqlSafeData['ConfirmLanguage'];
				}
				
				// billing details
				$billingDetails = array();
				if (isset($sqlSafeData['Billing']) && $sqlSafeData['Billing'] == 2) {
					$billingDetails['Subsidize'] = false;
					$billingDetails['PriceGroup'] = 0;
					$billingDetails['Subsidizer'] = 0;
					if (isset($sqlSafeData['Subsidize']) && $sqlSafeData['Subsidize']) {
						$billingDetails['Subsidize'] = true;
						$billingDetails['PriceGroup'] = $sqlSafeData['PriceGroup'];
						$billingDetails['Subsidizer'] = $sqlSafeData['Subsidizer'];
					}
					$billingDetails['ProvisionPercent'] = 0;
					if (isset($sqlSafeData['ProvisionPercent']) && $sqlSafeData['ProvisionPercent']) {
						$billingDetails['ProvisionPercent'] = $sqlSafeData['ProvisionPercent'];
					}
					$billingDetails['TaxType'] = $sqlSafeData['TaxType'];
					$billingDetails['CustomPrice'] = false;
					$duration = ($bookingDetails['End'] - $bookingDetails['Start'])/(60*60); // in hours
					if (floatval($sqlSafeData['CustomPrice']) != floatval($resource->Price * $duration)) {
						$billingDetails['CustomPrice'] = floatval($sqlSafeData['CustomPrice']);
					}
					$billingDetails['BillingAddress'] = false;
					if (isset($sqlSafeData['BillingAddress']) && $sqlSafeData['BillingAddress']) {
						$billingDetails['BillingAddress'] = $data['BillingAddress'];
					}
				}
				$bookingDetails['BillingDetails'] = $billingDetails;
				
				// service details
				$serviceDetails = array();
				if (isset($sqlSafeData['Service']) && $sqlSafeData['Service']) {
					$serviceID = (int)$sqlSafeData['ServiceID'];
					$service = DataObject::get_by_id('Service', $serviceID);
					if ($service) {
						// extra check that service is allowed for given resource
						if ($resource->AllowedServices() && $resource->AllowedServices()->containsIDs(array($serviceID))) {
							$serviceDetails['ServiceID'] = $serviceID;
							$serviceResourceID = (int)$sqlSafeData['ServicePlace'];
							$servicePlace = DataObject::get_by_id('Resource', $serviceResourceID);
							if ($servicePlace && $servicePlace->AllowedServices() && $servicePlace->AllowedServices()->containsIDs(array($serviceResourceID))) {
								$serviceDetails['ResourceID'] = $serviceResourceID;
							}
							else {
								$serviceDetails['ResourceID'] = $resource->ID;
							}
							$serviceDetails['Start'] = $sqlSafeData['ServiceTime'];
							$serviceDetails['Amount'] = $sqlSafeData['ServiceAmount'];
							$serviceDetails['ExtraInfo'] = $sqlSafeData['ServiceExtraInfo'];
							$serviceDetails['Options'] = array();
							if ($service->Options()) {
								foreach ($service->Options() as $option) {
									if (isset($sqlSafeData['ServiceOption_' . $option->ID])) {
										$serviceDetails['Options'][$option->ID] = $sqlSafeData['ServiceOption_' . $option->ID];
									}
								}
							}
						}
					}
				}
				$bookingDetails['ServiceDetails'] = $serviceDetails;
				
				// misc details
				$checkOnly = false;
				$bookingDetails['SkipSave'] = false;
				if (isset($sqlSafeData['CheckOnly']) && $sqlSafeData['CheckOnly']) {
					$checkOnly = true;
					$bookingDetails['SkipSave'] = true;
					$result = 'OK';
				}
				$bookingDetails['Confirmation'] = false;
				if (isset($sqlSafeData['Confirmation']) && intval($sqlSafeData['Confirmation']) == 1) {
					$bookingDetails['Confirmation'] = true;
					if ($bookingDetails['Status'] == 'Accepted') {
						$bookingDetails['Status'] = 'Preliminary';
					}
				}
				$previewConfirmation = false;
				if (isset($sqlSafeData['PreviewConfirmation']) && $sqlSafeData['PreviewConfirmation']) {
					$previewConfirmation = true;
				}
				$bookings = new DataObjectSet();
				
				// check if booking is allowed
				$errorReason = '';
				$allowed = false;
				if ($resource->ResourceGroup) {
					foreach ($resource->Parent()->getRealChildren() as $resourceChild) {
						$allowed = Booking::IsAllowed($resourceChild->ID, $bookingDetails['Start'], $bookingDetails['End'], $bookingDetails['Status'], $errorReason, $booking->ID);
						if (!$allowed) {
							break;
						}
					}
				}
				else {
					$allowed = Booking::IsAllowed($bookingDetails['ResourceID'], $bookingDetails['Start'], $bookingDetails['End'], $bookingDetails['Status'], $errorReason, $booking->ID);
				}
				
				if ($allowed) {
					if ($resource->ResourceGroup) {
						foreach ($resource->Parent()->getRealChildren() as $resourceChild) {
							$bookingDetails['ResourceID'] = $resourceChild->ID;
							$bookings->push($this->DoBooking($booking, $bookingDetails));
							$bookingDetails['BookingID'] = $bookings->Last()->BookingID;
							$booking = new Booking();
						}
					}
					else {
						if ($repeat) {
							$conflicts = array('errors' => array(), 'warnings' => array());
							// always book the selected time first
							$bookings->push($this->CheckAndBookRecurring($booking, $bookingDetails, $conflicts));
							// store booking id, to be used in subsequent recurring events
							if ($bookings->Count() > 0) {
								$booking = $bookings->Last();
								$bookingDetails['BookingID'] = $booking->BookingID;
							}
							
							// recurrance parameters
							$repeatType = 'd';
							if (isset($sqlSafeData['RepeatType']) && $sqlSafeData['RepeatType']) {
								$repeatType = $sqlSafeData['RepeatType'];
							}
							$repeatEach = 1;
							if (isset($sqlSafeData['RepeatEach']) && $sqlSafeData['RepeatEach']) {
								$repeatEach = intval($sqlSafeData['RepeatEach']);
							}
							$repeatStop = 0;
							if (isset($sqlSafeData['RepeatStop']) && $sqlSafeData['RepeatStop']) {
								$repeatStop = intval($sqlSafeData['RepeatStop']);
							}
							$repeatTimes = 1;
							if (isset($sqlSafeData['RepeatTimes']) && $sqlSafeData['RepeatTimes']) {
								$repeatTimes = intval($sqlSafeData['RepeatTimes']);
							}
							$repeatEndDay = '';
							if (isset($sqlSafeData['RepeatEndDay']) && $sqlSafeData['RepeatEndDay']) {
								$repeatEndDay = $sqlSafeData['RepeatEndDay'];
							}
							$repeatDays = array();
							if (isset($sqlSafeData['RepeatDays']) && $sqlSafeData['RepeatDays']) {
								$repeatDays = $sqlSafeData['RepeatDays'];
							}
							
							// set start and end dates based on parameters
							$startDate = new Zend_Date($bookingDetails['Start']);
							$duration = $bookingDetails['End'] - $bookingDetails['Start'];
							if ($repeatType == 'd') {
								$startDate->addDay($repeatEach);
							}
							else if ($repeatType == 'w') {
								if ($repeatDays) {
									// special handling for repeating several days per week,
									// we need to handle all events for the first week here (after start time of course)
									foreach($repeatDays as $day) {
										if ($day > $startDate->get(Zend_Date::WEEKDAY_DIGIT)) {
											$startDate->setWeekday($day);
											$bookingDetails['Start'] = $startDate->getTimestamp();
											$bookingDetails['End'] = $startDate->getTimestamp() + $duration;
											$booking = new Booking();
											$bookings->push($this->CheckAndBookRecurring($booking, $bookingDetails, $conflicts));
										}
									}
								}
								$startDate->addWeek($repeatEach);
							}
							else if ($repeatType == 'm') {
								$startDate->addMonth($repeatEach);
							}
							$endDate = new Zend_Date($bookingDetails['Start']);
							if ($repeatStop == 1) {
								$endDate = $endDate->setDate($repeatEndDay, 'dd.MM.YYYY');
							}
							else {
								if ($repeatType == 'd') {
									$endDate = $endDate->addDay($repeatEach*$repeatTimes);
								}
								else if ($repeatType == 'w') {
									$endDate = $endDate->addWeek($repeatEach*$repeatTimes);
								}
								else if ($repeatType == 'm') {
									$endDate = $endDate->addMonth($repeatEach*$repeatTimes);
								}
							}
							
							// create the recurring events
							$currDate = $startDate;
							while($currDate->getTimestamp() <= $endDate->getTimestamp()) {
								if ($repeatType == 'w' && $repeatDays) {
									foreach($repeatDays as $day) {
										$currDate->setWeekday($day);
										$bookingDetails['Start'] = $currDate->getTimestamp();
										$bookingDetails['End'] = $currDate->getTimestamp() + $duration;
										$booking = new Booking();
										$bookings->push($this->CheckAndBookRecurring($booking, $bookingDetails, $conflicts));
									}
								}
								else {
									$bookingDetails['Start'] = $currDate->getTimestamp();
									$bookingDetails['End'] = $currDate->getTimestamp() + $duration;
									$booking = new Booking();
									$bookings->push($this->CheckAndBookRecurring($booking, $bookingDetails, $conflicts));
								}
								
								if ($repeatType == 'd') {
									$currDate->addDay($repeatEach);
								}
								else if ($repeatType == 'w') {
									$currDate->addWeek($repeatEach);
								}
								else if ($repeatType == 'm') {
									$currDate->addMonth($repeatEach);
								}
							}
							
							if ($checkOnly) {
								if ($conflicts['errors'] || $conflicts['warnings']) {
									$result = '';
									if ($conflicts['errors']) {
										$result .= '<div class="Message Error">';
										if (count($conflicts['errors']) == 1) {
											$result .= 'En konflikt: ';
										}
										else {
											$result .= count($conflicts['errors']) . ' konflikter: ';
										}
										$first = true;
										foreach($conflicts['errors'] as $conflictDate) {
											if (!$first) {
												$result .= ', ';
											}
											$result .= $conflictDate;
											$first = false;
										}
										$result .= '</div>';
									}
									
									if ($conflicts['warnings']) {
										$result .= '<div class="Message Warning">';
										if (count($conflicts['warnings']) == 1) {
											$result .= 'En varning: ';
										}
										else {
											$result .= count($conflicts['warnings']) . ' varningar: ';
										}
										$first = true;
										foreach($conflicts['warnings'] as $conflictDate) {
											if (!$first) {
												$result .= ', ';
											}
											$result .= $conflictDate;
											$first = false;
										}
										$result .= '</div>';
									}
								}
								else {
									$result = 'OK';
								}
							}
						}
						else if ($bookingDetails['IsRecurring'] == 1 && $bookingDetails['RecurringEditType'] != 'single') {
							$bookingDay = date('w', strtotime($booking->Start));
							$recurringBookings = DataObject::get('Booking', "BookingID = {$booking->BookingID} AND IsRecurring = 1");
							$startDelta = $bookingDetails['Start'] - strtotime($booking->Start);
							$endDelta = $bookingDetails['End'] - strtotime($booking->End);
							foreach ($recurringBookings as $recurringBooking) {
								$recurringDay = date('w', strtotime($recurringBooking->Start));
								if ($bookingDetails['RecurringEditType'] == 'all' || ($bookingDetails['RecurringEditType'] == 'day' && $recurringDay == $bookingDay)) {
									$bookingDetails['Start'] = strtotime($recurringBooking->Start) + $startDelta;
									$bookingDetails['End'] = strtotime($recurringBooking->End) + $endDelta;
									$bookings->push($this->DoBooking($recurringBooking, $bookingDetails));
								}
							}
						}
						else {
							$bookings->push($this->DoBooking($booking, $bookingDetails));
						}
					}
					
					if ($bookingDetails['Confirmation'] && $bookings) {
						if ($bookings->Count() == 1 && $booking->IsRecurring) {
							$recurringBookings = DataObject::get('Booking', "BookingID = {$booking->BookingID} AND ID != {$booking->ID} AND IsRecurring = 1");
							if ($recurringBookings) {
								foreach ($recurringBookings as $recurringBooking) {
									$bookings->push($recurringBooking);
								}
							}
						}
						// show preview confirmation
						$email = false;
						if (isset($sqlSafeData['EMailConfirmation']) && $sqlSafeData['EMailConfirmation']) {
							$email = true;
						}
						$pdf = false;
						if (isset($sqlSafeData['PDFConfirmation']) && $sqlSafeData['PDFConfirmation']) {
							$pdf = true;
						}
						$newConfirmation = new Confirmation();
						$newConfirmation->BookingID = $bookings->First()->BookingID;
						$newConfirmation->ExtraText = $bookingDetails['ConfirmationText'];
						$newConfirmation->Due = $bookings->First()->ConfirmBefore;
						$newConfirmation->EmailAddresses = $bookingDetails['ConfirmationEmails'];
						$newConfirmation->Locale = $bookingDetails['ConfirmLanguage'];
						$confirmationResult = $newConfirmation->Send($bookings, $email, $pdf, $previewConfirmation);
						if ($previewConfirmation) {
							$result = $confirmationResult;
							if (!$booking->ID) {
								// remove billing details that have been saved temporarily
								foreach ($bookings as $booking) {
									if ($booking->BillingDetails() && $booking->BillingDetails()->ID) {
										$booking->BillingDetails()->delete();
									}
								}
							}
						}
						else {
							// mark pending bookings as preliminary
							foreach ($bookings as $booking) {
								if ($booking->Status == 'Pending') {
									$booking->Status == 'Preliminary';
									$booking->write();
								}
							}
						}
					}
				}
				else {
					$result = $errorReason;
				}
			}
			else {
				$result = "Invalid resource-id sent!";
			}
		}
		else {
			$result = "Invalid parameters sent!";
		}
		return $result;
	}
	
	private function CheckAndBookRecurring($booking, $bookingDetails, &$conflicts) {
		$reason = '';
		if (!Booking::IsAllowed($bookingDetails['ResourceID'], $bookingDetails['Start'], $bookingDetails['End'], $bookingDetails['Status'], $reason, $booking->ID)) {
			if ($bookingDetails['SkipSave']) {
				$conflicts['errors'][] = date('d.m.Y', $bookingDetails['Start']);
			}
			$bookingDetails['Status'] = 'Pending';
		}
		else if ($bookingDetails['SkipSave']) {
			// warn for conflicting pending bookings
			$bookingStart = date('Y-m-d H:i', $bookingDetails['Start']);
			$bookingEnd = date('Y-m-d H:i', $bookingDetails['End']);
			$existingBookingIDCheck = " AND ID != $booking->ID";
			$resourceID = $bookingDetails['ResourceID'];
			$bookings = DataObject::get('Booking', "ResourceID = $resourceID AND Status = 'Pending' AND ((Start <= '$bookingStart' AND End > '$bookingStart') OR (Start >= '$bookingStart' AND Start < '$bookingEnd')) $existingBookingIDCheck");
			if ($bookings)  {
				$conflicts['warnings'][] = date('d.m.Y', $bookingDetails['Start']);
			}
		}
		
		return $this->DoBooking($booking, $bookingDetails);
	}
	
	private function DoBooking($booking, $bookingDetails) {
		$startDay = date('Y-m-d', $bookingDetails['Start']);
		$endDay = date('Y-m-d', $bookingDetails['End']);
		if ($startDay == $endDay) {
			// ok to do booking
			$booking->ResourceID = $bookingDetails['ResourceID'];
			$booking->CustomerGroupID = $bookingDetails['CustomerGroupID'];
			$booking->CustomerID = $bookingDetails['CustomerID'];
			$booking->BookingID = $bookingDetails['BookingID'];
			$booking->TypeID = $bookingDetails['TypeID'];
			$booking->Start = date('Y-m-d H:i:00', $bookingDetails['Start']);
			$booking->End = date('Y-m-d H:i:00', $bookingDetails['End']);
			$booking->Status = $bookingDetails['Status'];
			$booking->IsRecurring = $bookingDetails['IsRecurring'];
			$booking->ConfirmBefore = $bookingDetails['ConfirmBefore'];
			$booking->BookingText = $bookingDetails['BookingText'];
			
			$billingDetails = $bookingDetails['BillingDetails'];
			if ($billingDetails) {
				$billingDetailsObject = $booking->BillingDetails();
				if (!$billingDetailsObject)  {
					$billingDetailsObject = new BillingDetails();
				}
				if ($billingDetails['BillingAddress']) {
					$billingDetailsObject->BillingAddress = $billingDetails['BillingAddress'];
				}
				else {
					$customer = DataObject::get_by_id('Customer', (int)$booking->CustomerID);
					if ($customer) {
						$billingDetailsObject->BillingAddress = $customer->Group()->Address;
					}
				}
				if ($billingDetails['CustomPrice']) {
					$billingDetailsObject->CustomPrice = $billingDetails['CustomPrice'];
				}
				if ($billingDetails['Subsidize']) {
					$billingDetailsObject->PriceGroupID = $billingDetails['PriceGroup'];
					$billingDetailsObject->SubsidizerID = $billingDetails['Subsidizer'];
				}
				$billingDetailsObject->ProvisionPercent = $billingDetails['ProvisionPercent'];
				$billingDetailsObject->TaxTypeID = $billingDetails['TaxType'];
				$billingDetailsObject->write();
				$booking->BillingDetailsID = $billingDetailsObject->ID;
			}
			
			if ($bookingDetails['SkipSave']) {
				$booking->Created = date('Y-m-d H:i:00');
			}
			else {
				$booking->write();
				
				$serviceDetails = $bookingDetails['ServiceDetails'];
				if ($serviceDetails) {
					$serviceBooking = new ServiceBooking();
					$serviceBooking->BookingID = $booking->ID;
					$serviceBooking->ServiceID = $serviceDetails['ServiceID'];
					$serviceBooking->ResourceID = $serviceDetails['ResourceID'];
					$serviceBooking->Start = date('Y-m-d H:i:00', $serviceDetails['Start']);
					$serviceBooking->Amount = date('Y-m-d H:i:00', $serviceDetails['Amount']);
					$serviceBooking->ExtraInfo = $serviceDetails['ExtraInfo'];
					$serviceBooking->write();
					foreach ($serviceDetails['Options'] as $optionID => $amount) {
						$serviceOptionValue = new ServiceOptionValue();
						$serviceOptionValue->Amount = $amount;
						$serviceOptionValue->ServiceBookingID = $serviceBooking->ID;
						$serviceOptionValue->ServiceOptionID = $optionID;
						$serviceOptionValue->write();
					}
				}
			}
		}
		else {
			// split booking into one booking for this day and one or more bookings for the rest
			$resource = DataObject::get_by_id('Resource', $bookingDetails['ResourceID']);
			if ($resource) {
				$origEnd = $bookingDetails['End'];
				$bookingDetails['End'] = $resource->getAvailableToTime($bookingDetails['Start']);
				$this->DoBooking($booking, $bookingDetails);
				
				$bookingDetails['Start'] = $resource->getAvailableFromTime($bookingDetails['End']);
				$bookingDetails['End'] = $origEnd;
				$this->DoBooking(new Booking(), $bookingDetails);
			}
		}
		return $booking;
	}
	
}

?>