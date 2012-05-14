<?php

class BookingReport extends ResourceBookingReport {
	
	protected $orientation = 'landscape';
	
	protected function ReportOptionFields() {
		$fields = parent::ReportOptionFields();
		
		$fields->push(new HeaderField('ResourceSelection', 'Resurs', 5));
		if (ResourceBookingExtension::adminLevel() == 3) {
			$fields->push(ResourceOrganization::ResourceOrganizationFilter());
		}
		if (ResourceBookingExtension::adminLevel() >= 2) {
			$fields->push(ResourceGroup::ResourceGroupFilter(false));
		}
		if (ResourceBookingExtension::adminLevel() >= 1) {
			$fields->push(Resource::ResourceFilter(false));
		}
			
		$fields->push(new HeaderField('CustomerSelection', 'Kund', 5));
		$fields->push(CustomerOrganization::CustomerOrganizationFilter());
		$fields->push(CustomerGroup::CustomerGroupFilter());
		$fields->push(Customer::CustomerFilter());
		
		$fields->push(new NumericField('BookingID', 'Bokningsid'));
		
		$subsidizers = DataObject::get('CustomerGroup', 'CanSubsidize = 1');
		$subsidizersArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($subsidizers) {
			$subsidizersArray = $subsidizers->map('ID', 'Name', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		}
		$fields->push(new AdvancedDropdownField('SubsidizerID', 'Subventionerare', $subsidizersArray));
		
		$priceGroups = DataObject::get('PriceGroup');
		$priceGroupsArray = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		if ($priceGroups) {
			$priceGroupsArray = $priceGroups->map('ID', 'Name', _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		}
		$fields->push(new AdvancedDropdownField('PriceGroupID', 'Prisgrupp', $priceGroupsArray));
		
		return $fields;
	}
	
	public function GenerateReportData() {
		$customFields = array();
		$bookingFilter = '';
		$bookingJoin = '';
		if (isset($this->data['StartDate']) && $this->data['StartDate']) {
			$customFields['Start'] = $this->data['StartDate'];
			$date = new Zend_Date($this->data['StartDate'], 'dd.MM.yyyy', i18n::get_locale());
			$bookingFilter = "Start >= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		if (isset($this->data['EndDate']) && $this->data['EndDate']) {
			$customFields['End'] = $this->data['EndDate'];
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$date = new Zend_Date($this->data['EndDate'], 'dd.MM.yyyy', i18n::get_locale());
			$bookingFilter .= "End <= '" . $date->toString('yyyy-MM-dd') . "'";
		}
		
		if (isset($this->data['ResourceFilter']) && $this->data['ResourceFilter']) {
			$resourceID = (int)$this->data['ResourceFilter'];
			$resource = DataObject::get_by_id('Resource', $resourceID);
			if ($resource && $resource->isAdmin()) {
				$customFields['Resource'] = $resource;
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter = "ResourceID = $resourceID";
			}
		}
		else  {
			if (ResourceBookingExtension::adminLevel() >= 1 && ResourceBookingExtension::adminLevel() < 3) {
				$bookingJoin .= ' LEFT JOIN Resource ON Resource.ID = Booking.ResourceID LEFT JOIN ResourceGroup ON ResourceGroup.ID = Resource.GroupID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= 'ResourceGroup.AdministratorID = ' . Member::currentUserID();
			}
			if (ResourceBookingExtension::adminLevel() == 2) {
				$bookingJoin .= ' LEFT JOIN ResourceOrganization ON ResourceOrganization.ID = ResourceGroup.OrganizationID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter .= 'ResourceOrganization.AdministratorID = ' . Member::currentUserID();
			}
		}
		
		if (isset($this->data['CustomerGroupID']) && $this->data['CustomerGroupID']) {
			$customerGroupID = (int)$this->data['CustomerGroupID'];
			$customerGroup = DataObject::get_by_id('CustomerGroup', $customerGroupID);
			if ($customerGroup) {
				$customFields['CustomerGroup'] = $customerGroup;
				$bookingJoin .= ' LEFT JOIN Customer ON Booking.CustomerID = Customer.ID';
				if ($bookingFilter != '') {
					$bookingFilter .= ' AND ';
				}
				$bookingFilter = "GroupID = $customerGroupID";
			}
		}
		
		if (isset($this->data['BookingID']) && $this->data['BookingID']) {
			$bookingID = (int)$this->data['BookingID'];
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= "BookingID = $bookingID";
		}
		
		$billingDetailsJoin = false;
		if (isset($this->data['SubsidizerID']) && $this->data['SubsidizerID']) {
			$subsidizerID = (int)$this->data['SubsidizerID'];
			$bookingJoin .= ' LEFT JOIN BillingDetails ON Booking.BillingDetailsID = BillingDetails.ID';
			$billingDetailsJoin = true;
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= "BillingDetails.SubsidizerID = $subsidizerID";
		}
		
		if (isset($this->data['PriceGroupID']) && $this->data['PriceGroupID']) {
			$priceGroupID = (int)$this->data['PriceGroupID'];
			if (!$billingDetailsJoin) {
				$bookingJoin .= ' LEFT JOIN BillingDetails ON Booking.BillingDetailsID = BillingDetails.ID';
			}
			if ($bookingFilter != '') {
				$bookingFilter .= ' AND ';
			}
			$bookingFilter .= "BillingDetails.PriceGroupID = $priceGroupID";
		}
		
		$bookings = DataObject::get('Booking', $bookingFilter, '', $bookingJoin);
		if ($bookings) {
			$customFields['Bookings'] = $bookings;
		}
		
		return $this->renderWith('Reports/BookingReport', $customFields);
	}
	
}

?>