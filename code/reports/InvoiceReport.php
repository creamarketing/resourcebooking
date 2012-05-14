<?php

class InvoiceReport extends ResourceBookingReport {
	
	protected $orientation = 'landscape';
	
	protected function ReportOptionFields() {
		$fields = parent::ReportOptionFields();
		
		$fields->push(new HeaderField('ResourceSelection', 'Resurs', 5));
		$fields->push(ResourceOrganization::ResourceOrganizationFilter());
		$fields->push(ResourceGroup::ResourceGroupFilter(false));
		$fields->push(Resource::ResourceFilter(false));
		
		$fields->push(new HeaderField('CustomerSelection', 'Kund', 5));
		$fields->push(CustomerOrganization::CustomerOrganizationFilter());
		$fields->push(CustomerGroup::CustomerGroupFilter());
		$fields->push(Customer::CustomerFilter());
		
		$fields->push(new NumericField('BookingID', 'Bokningsid'));
		
		$types = array(
			0 => 'Intern faktura',
			1 => 'Extern faktura',
			2 => 'Provisionsfaktura'
		);
		$fields->push(new AdvancedDropdownField('Type', 'Faktureringstyp', $types));
		
		Requirements::javascript('resourcebooking/javascript/reports/InvoiceReport.js');
		
		return $fields;
	}
	
	protected function ReportActions() {
		$actions = parent::ReportActions();
		
		$actions->push(new FormAction('MarkAsBilled', _t('InvoiceReport.MARKASBILLED', 'Mark as billed'), null, null, 'hidden'));
		
		return $actions;
	}
	
	public function GenerateReportData() {
		$customFields = array();
		
		$bookings = $this->GetReportCache();
		if (!$bookings) {
			$bookings = $this->FilterBookings();
			$this->StoreReportCache($bookings);
		}
		
		$type = 0;
		if (isset($this->data['Type'])) {
			$type = $this->data['Type'];
		}
		
		if ($type == 0) {
			$customFields['InvoiceRows'] = new DataObjectSet($this->GenerateInternalInvoiceRows($bookings));
			return $this->renderWith('Reports/InvoiceReport_internal', $customFields);
		}
		else if ($type == 1) {
			$customFields['InvoiceRows'] = new DataObjectSet($this->GenerateExternalInvoiceRows($bookings));
			return $this->renderWith('Reports/InvoiceReport_external', $customFields);
		}
		else if ($type == 2) {
			$customFields['InvoiceRows'] = new DataObjectSet($this->GenerateProvisionInvoiceRows($bookings));
			return $this->renderWith('Reports/InvoiceReport_provision', $customFields);
		}
	}
	
	public function MarkAsBilled($data, $form) {
		$this->data = $data;
		
		$bookings = $this->GetReportCache();
		if (!$bookings) {
			$bookings = $this->FilterBookings();
			$this->StoreReportCache($bookings);
		}
		
		if ($bookings) {
			$type = 0;
			if (isset($this->data['Type'])) {
				$type = $this->data['Type'];
			}
			foreach ($bookings as $booking) {
				$billingDetails = $booking->BillingDetails();
				if ($billingDetails) {
					if ($type == 0) {
						$billingDetails->InternalPaid = 1;
						$billingDetails->InternalPaymentDate = date('Y-m-d H:i');
					}
					else if ($type == 1) {
						$billingDetails->ExternalPaid = 1;
						$billingDetails->ExternalPaymentDate = date('Y-m-d H:i');
					}
					else if ($type == 0) {
						$billingDetails->ProvisionPaid = 1;
						$billingDetails->ProvisionPaymentDate = date('Y-m-d H:i');
					}
					$billingDetails->write();
				}
			}
		}
		
		return 'OK';
	}
	
	public function ItemDetails($data, $form) {
		$this->data = $data;
		$itemID = $data['DetailItemID'];
	
		$bookings = $this->GetReportCache();
		if (!$bookings) {
			$bookings = $this->FilterBookings();
			$this->StoreReportCache($bookings);
		}
		
		Requirements::clear();
		Requirements::css('resourcebooking/css/reports/ResourceBookingReport_iframe.css');
		
		$detailedBookings = new DataObjectSet();
		if ($bookings) {
			$type = 0;
			if (isset($this->data['Type'])) {
				$type = $this->data['Type'];
			}
			if ($type == 1) {
				$bookingID = '';
				$resourceName = '';
				foreach ($bookings as $booking) {
					$rowID = $this->RowID($booking);
					if ($rowID == $itemID) {
						$detailedBookings->push(new ArrayData(array(
							'Type' => $booking->Type()->Name,
							'Time' => $booking->NiceStartTime() . ' - ' . $booking->NiceEndTime(),
							'UnsubsidizedPrice' => $booking->Price(false, false),
							'PriceExclTax' => $booking->Price(false, true),
							'PriceInclTax' => $booking->Price(true, true),
							'Tax' => $booking->BillingDetails()->TaxType()->TaxPercent,
							'Contact' => $booking->Customer()->FullName()
						)));
						
						if (!$bookingID) {
							$bookingID = $booking->BookingID;
						}
						if (!$resourceName) {
							$resourceName = $booking->Resource()->Group()->Name . ' - ' . $booking->Resource()->Name . ', ' . $booking->Resource()->Group()->Account();
						}
					}
				}
				$customFields = array(
					'Bookings' => $detailedBookings,
					'BookingID' => $bookingID,
					'Resource' => $resourceName
				);
				return $this->renderWith('Reports/InvoiceReport_external_details', $customFields);
			}
			else if ($type == 2) {
				$resourceGroupName = '';
				foreach ($bookings as $booking) {
					$rowID = $this->RowID($booking);
					if ($rowID == $itemID) {
						$detailedBookings->push(new ArrayData(array(
							'BookingID' => $booking->BookingID,
							'Resource' => $booking->Resource()->Name,
							'Type' => $booking->Type()->Name,
							'Time' => $booking->NiceStartTime() . ' - ' . $booking->NiceEndTime(),
							'UnsubsidizedPrice' => $booking->Price(false, false),
							'PriceExclTax' => $booking->Price(false, true),
							'PriceInclTax' => $booking->Price(true, true),
							'Tax' => $booking->BillingDetails()->TaxType()->TaxPercent,
							'Contact' => $booking->Customer()->FullName()
						)));
						
						if (!$resourceGroupName) {
							$resourceGroupName = $booking->Resource()->Group()->Name . ', ' . $booking->Resource()->Group()->Account();
						}
					}
				}
				$customFields = array(
					'Bookings' => $detailedBookings,
					'ResourceGroup' => $resourceGroupName
				);
				return $this->renderWith('Reports/InvoiceReport_provision_details', $customFields);
			}
		}
	}
	
	private function FilterBookings() {
		$bookingFilter = "Booking.Status = 'Accepted'";
		$bookingJoin = 'LEFT JOIN BillingDetails ON Booking.BillingDetailsID = BillingDetails.ID';
		
		if (isset($this->data['StartDate']) && $this->data['StartDate']) {
			$customFields['Start'] = $this->data['StartDate'];
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$date = new Zend_Date($this->data['StartDate'], 'dd.MM.yyyy', i18n::get_locale());
			$bookingFilter .= "Start >= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		if (isset($this->data['EndDate']) && $this->data['EndDate']) {
			$customFields['End'] = $this->data['EndDate'];
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$date = new Zend_Date($this->data['EndDate'], 'dd.MM.yyyy', i18n::get_locale());
			$bookingFilter .= "End <= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		
		if (isset($this->data['BookingID']) && $this->data['BookingID']) {
			$bookingID = (int)$this->data['BookingID'];
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= "BookingID = $bookingID";
		}
		
		$type = 0;
		if (isset($this->data['Type'])) {
			$type = $this->data['Type'];
			if ($type == 0) {
				// internal invoice, filter only bookings which are subsidized or made by an internal customer
				$bookingJoin .= ' LEFT JOIN CustomerGroup ON CustomerGroup.ID = Booking.CustomerGroupID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= 'BillingDetails.InternalPaid = 0 AND (BillingDetails.SubsidizerID > 0 OR CustomerGroup.InternalCustomer = 1)';
			}
			else if ($type == 1) {
				// external invoice, not for internal customers
				$bookingJoin .= ' LEFT JOIN CustomerGroup ON CustomerGroup.ID = Booking.CustomerGroupID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= 'BillingDetails.ExternalPaid = 0 AND CustomerGroup.InternalCustomer = 1';
			}
			else if ($type == 2) {
				// provision invoice, filter only bookings which are marked for provision
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= 'BillingDetails.ProvisionPaid = 0 AND BillingDetails.ProvisionPercent > 0';
			}
		}
		
		if (isset($this->data['ResourceFilter']) && $this->data['ResourceFilter']) {
			$resourceID = (int)$this->data['ResourceFilter'];
			$resource = DataObject::get_by_id('Resource', $resourceID);
			if ($resource && $resource->isAdmin()) {
				$customFields['Resource'] = $resource;
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= "ResourceID = $resourceID";
			}
		}
		else if (isset($this->data['ResourceGroupFilter']) && $this->data['ResourceGroupFilter']) {
			$bookingJoin .= ' LEFT JOIN Resource ON Resource.ID = Booking.ResourceID';
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= 'Resource.GroupID = ' . (int)$this->data['ResourceGroupFilter'];
		}
		else if (isset($this->data['ResourceOrganizationFilter']) && $this->data['ResourceOrganizationFilter']) {
			$bookingJoin .= ' LEFT JOIN Resource ON Resource.ID = Booking.ResourceID';
			$bookingJoin .= ' LEFT JOIN ResourceGroup ON ResourceGroup.ID = Resource.GroupID';
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= 'ResourceGroup.OrganizationID = ' . (int)$this->data['ResourceOrganizationFilter'];
		}
		
		if (isset($this->data['CustomerID']) && $this->data['CustomerID']) {
			$customerID = (int)$this->data['CustomerID'];
			$customer = DataObject::get_by_id('Customer', $customerID);
			if ($customer) {
				$bookingJoin .= ' LEFT JOIN Customer ON Booking.CustomerID = Customer.ID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= "Customer.ID = $customerID";
			}
		}
		else if (isset($this->data['CustomerGroupID']) && $this->data['CustomerGroupID']) {
			$customerGroupID = (int)$this->data['CustomerGroupID'];
			$customerGroup = DataObject::get_by_id('CustomerGroup', $customerGroupID);
			if ($customerGroup) {
				$customFields['CustomerGroup'] = $customerGroup;
				$bookingJoin .= ' LEFT JOIN CustomerGroup ON CustomerGroup.ID = Booking.CustomerGroupID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= "CustomerGroup.ID = $customerGroupID";
			}
		}
		else if (isset($this->data['CustomerOrganizationID']) && $this->data['CustomerOrganizationID']) {
			$customerOrganizationID = (int)$this->data['CustomerOrganizationID'];
			$customerOrganization = DataObject::get_by_id('CustomerOrganization', $customerOrganizationID);
			if ($customerOrganization) {
				$bookingJoin .= ' LEFT JOIN CustomerGroup ON CustomerGroup.ID = Booking.CustomerGroupID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= "CustomerGroup.OrganizationID = $customerOrganizationID";
			}
		}
		
		$bookings = DataObject::get('Booking', $bookingFilter, '', $bookingJoin);
		return $bookings;
	}
	
	private function GenerateInternalInvoiceRows($bookings) {
		$invoiceRows = array();
		if ($bookings) {
			foreach ($bookings as $booking) {
				$row = new ArrayData(array());
				$billingDetails = $booking->BillingDetails();
				$payer = 0;
				// group by payer
				if ($billingDetails->SubsidizerID) {
					$payer = $billingDetails->SubsidizerID;
				}
				else {
					$payer = $booking->CustomerGroupID;
				}
				if (isset($invoiceRows[$payer])) {
					$row = $invoiceRows[$payer];
				}
				
				if (!$row->hasField('Name')) {
					$subsidizer = '';
					$subsidizerGroup = DataObject::get_by_id('CustomerGroup', (int)$payer);
					if ($subsidizerGroup) {
						$subsidizer = 'Konto ' . $subsidizerGroup->Account() . ', ' . $subsidizerGroup->Name;
					}
					$row->setField('Name', $subsidizer);
				}
					
				$resourceGroup = $booking->Resource()->Group();
				if (!$row->hasField('Groups')) {
					$groups = new DataObjectSet();
				}
				else {
					$groups = $row->getField('Groups');
				}
				$rowID = $this->RowID($booking);
				$group = $groups->find('ID', $rowID);
				if (!$group) {
					$group = new ArrayData(array(
						'ID' => $rowID,
						'Name' => $resourceGroup->Name . ', ' . $resourceGroup->Account('internal')
					));
					$groups->push($group);
				}
					
				$customer = $booking->CustomerGroup();
				if (!$group->hasField('Customers')) {
					$customers = new DataObjectSet();
					$group->setField('Customers', $customers);
				}
				else {
					$customers = $group->getField('Customers');
				}
				$customerGroup = $customers->find('ID', $customer->ID);
				if (!$customerGroup) {
					$customerGroup = new ArrayData(array(
						'ID' => $customer->ID,
						'Name' => $customer->Name
					));
					$customers->push($customerGroup);
				}
					
				$totalPrice = $booking->Price(true, false);
				if (!$customerGroup->hasField('TotalPrice')) {
					$customerGroup->setField('TotalPrice', $totalPrice);
				}
				else {
					$currTotal = $customerGroup->getField('TotalPrice');
					$customerGroup->setField('TotalPrice', $currTotal + $totalPrice);
				}
					
				$externalPrice = $booking->Price(true, true);
				if (!$customerGroup->hasField('ExternalPrice')) {
					$customerGroup->setField('ExternalPrice', $externalPrice);
				}
				else {
					$currExternal = $customerGroup->getField('ExternalPrice');
					$customerGroup->setField('ExternalPrice', $currExternal + $externalPrice);
				}
					
				$internalPrice = $totalPrice - $externalPrice;
				if (!$customerGroup->hasField('InternalPrice')) {
					$customerGroup->setField('InternalPrice', $internalPrice);
				}
				else {
					$currInternal = $customerGroup->getField('InternalPrice');
					$customerGroup->setField('InternalPrice', $currInternal + $internalPrice);
				}
					
				$groupTotal = $internalPrice;
				if (!$group->hasField('Total')) {
					$group->setField('Total', $internalPrice);
				}
				else {
					$currTotal = $group->getField('Total');
					$group->setField('Total', $currTotal + $internalPrice);
				}
					
				if (!$row->hasField('Total')) {
					$row->setField('Total', $internalPrice);
				}
				else {
					$currTotal = $row->getField('Total');
					$row->setField('Total', $currTotal + $internalPrice);
				}
					
				$row->setField('Groups', $groups);
					
				$invoiceRows[$billingDetails->SubsidizerID] = $row;
			}
		}
		return $invoiceRows;
	}
	
	private function GenerateExternalInvoiceRows($bookings) {
		$invoiceRows = array();
		if ($bookings) {
			$firstInsert = true;
			foreach ($bookings as $booking) {
				if ($booking->Price() == 0) {
					// ignore bookings with 0 price, i.e. those that are 100% subsidized
					continue;
				}
				// group by billing address (by hashing it)
				$billingDetails = $booking->BillingDetails();
				$addressHash = md5($billingDetails->BillingAddress);
				if (isset($invoiceRows[$addressHash])) {
					$row = $invoiceRows[$addressHash];
				}
				else {
					$row = new ArrayData(array(
						'Address' => nl2br($billingDetails->BillingAddress),
						'IsFirst' => $firstInsert
					));
					$firstInsert = false;
				}
				
				// group rows by address - booking id - resource id
				if (!$row->hasField('Bookings')) {
					$groupedBookings = new DataObjectSet();
				}
				else {
					$groupedBookings = $row->getField('Bookings');
				}
				$rowID = $this->RowID($booking);
				$bookingRow = $groupedBookings->find('ID', $rowID);
				if (!$bookingRow) {
					$bookingRow = new ArrayData(array(
						'ID' => $rowID,
						'BookingID' => $booking->BookingID,
						'Resource' => $booking->Resource()->Group()->Name . ' - ' . $booking->Resource()->Name,
						'Type' => $booking->Type()->Name
					));
					$groupedBookings->push($bookingRow);
				}
					
				$hours = 0;
				if ($bookingRow->hasField('Hours')) {
					$hours = $bookingRow->getField('Hours');
				}
				$hours += $booking->Duration('h');
				$bookingRow->setField('Hours', $hours);
				
				$unsubsidizedPrice = 0;
				if ($bookingRow->hasField('UnsubsidizedPrice')) {
					$unsubsidizedPrice = $bookingRow->getField('UnsubsidizedPrice');
				}
				$unsubsidizedPrice += $booking->Price(false, false);
				$bookingRow->setField('UnsubsidizedPrice', $unsubsidizedPrice);
				
				$priceExclTax = 0;
				if ($bookingRow->hasField('PriceExclTax')) {
					$priceExclTax = $bookingRow->getField('PriceExclTax');
				}
				$priceExclTax += $booking->Price(false);
				$bookingRow->setField('PriceExclTax', $priceExclTax);
					
				$priceInclTax = 0;
				if ($bookingRow->hasField('PriceInclTax')) {
					$priceInclTax = $bookingRow->getField('PriceInclTax');
				}
				$priceInclTax += $booking->Price(true);
				$bookingRow->setField('PriceInclTax', $priceInclTax);
					
				$bookingRow->setField('Account', $booking->Resource()->Group()->Account());
					
				$tax = 0;
				if ($booking->BillingDetails() && $booking->BillingDetails()->TaxType()) {
					$tax = $booking->BillingDetails()->TaxType()->TaxPercent;
				}
				$bookingRow->setField('Tax', $tax);
					
				$customer = '';
				if ($booking->Customer()) {
					$customer = $booking->Customer()->FullName();
				}
				$bookingRow->setField('Contact', $customer);
				
				$row->setField('Bookings', $groupedBookings);
				
				$invoiceRows[$addressHash] = $row;
			}
		}
		return $invoiceRows;
	}
	
	private function GenerateProvisionInvoiceRows($bookings) {
		$invoiceRows = array();
		$provisionReceiver = DataObject::get_one('CustomerGroup', 'ProvisionReceiver = 1');
		if ($bookings && $provisionReceiver) {
			$row = new ArrayData(array(
				'Name' => $provisionReceiver->Name . ', ' . $provisionReceiver->Account()
			));
			foreach ($bookings as $booking) {
				$billingDetails = $booking->BillingDetails();
				
				// group by resource group
				$resourceGroup = $booking->Resource()->Group();
				if (!$row->hasField('Groups')) {
					$groups = new DataObjectSet();
				}
				else {
					$groups = $row->getField('Groups');
				}
				$rowID = $this->RowID($booking);
				$group = $groups->find('ID', $rowID);
				if (!$group) {
					$group = new ArrayData(array(
						'ID' => $rowID,
						'Name' => $resourceGroup->Name . ', ' . $resourceGroup->Account('provision')
					));
					$groups->push($group);
				}
				
				$totalPrice = $booking->Price(true, false);
				if (!$group->hasField('TotalPrice')) {
					$group->setField('TotalPrice', $totalPrice);
				}
				else {
					$currTotal = $group->getField('TotalPrice');
					$group->setField('TotalPrice', $currTotal + $totalPrice);
				}
				
				$externalPrice = $booking->Price(true, true);
				if (!$group->hasField('ExternalPrice')) {
					$group->setField('ExternalPrice', $externalPrice);
				}
				else {
					$currExternal = $group->getField('ExternalPrice');
					$group->setField('ExternalPrice', $currExternal + $externalPrice);
				}
				
				$internalPrice = $totalPrice - $externalPrice;
				if (!$group->hasField('InternalPrice')) {
					$group->setField('InternalPrice', $internalPrice);
				}
				else {
					$currInternal = $group->getField('InternalPrice');
					$group->setField('InternalPrice', $currInternal + $internalPrice);
				}
				
				$provision = $totalPrice * ($billingDetails->ProvisionPercent / 100);
				if (!$group->hasField('Provision')) {
					$group->setField('Provision', $provision);
				}
				else {
					$currProvision = $group->getField('Provision');
					$group->setField('Provision', $currProvision + $provision);
				}
				
				if (!$row->hasField('TotalInternal')) {
					$row->setField('TotalInternal', $internalPrice);
				}
				else {
					$currTotal = $row->getField('TotalInternal');
					$row->setField('TotalInternal', $currTotal + $internalPrice);
				}
				if (!$row->hasField('TotalExternal')) {
					$row->setField('TotalExternal', $externalPrice);
				}
				else {
					$currTotal = $row->getField('TotalExternal');
					$row->setField('TotalExternal', $currTotal + $externalPrice);
				}
				if (!$row->hasField('TotalTotal')) {
					$row->setField('TotalTotal', $totalPrice);
				}
				else {
					$currTotal = $row->getField('TotalTotal');
					$row->setField('TotalTotal', $currTotal + $totalPrice);
				}
				if (!$row->hasField('TotalProvision')) {
					$row->setField('TotalProvision', $provision);
				}
				else {
					$currTotal = $row->getField('TotalProvision');
					$row->setField('TotalProvision', $currTotal + $provision);
				}
				
				$row->setField('Groups', $groups);
			}
			$invoiceRows[0] = $row;
		}
		return $invoiceRows;
	}
	
	private function RowID($booking) {
		$rowID = '';
		$type = 0;
		if (isset($this->data['Type'])) {
			$type = $this->data['Type'];
		}
		if ($type == 0) {
			$payer = 0;
			if ($booking->BillingDetailsID && $booking->BillingDetails()->SubsidizerID) {
				$payer = $booking->BillingDetails()->SubsidizerID;
			}
			else if ($booking->CustomerGroupID) {
				$payer = $booking->CustomerGroupID;
			}
			if ($booking->ResourceID && $payer) {
				$rowID = $payer . '-' . $booking->Resource()->GroupID;
			}
		}
		else if ($type == 1) {
			if ($booking->BillingDetailsID) {
				$addressHash = md5($booking->BillingDetails()->BillingAddress);
				$rowID = $addressHash . '-' . $booking->BookingID . '-' . $booking->ResourceID;
			}
		}
		else if ($type == 2) {
			if ($booking->ResourceID) {
				$rowID = $booking->Resource()->GroupID;
			}
		}
		return $rowID;
	}
	
}

?>