<?php

class Resource extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'AvailableByDefault' => 'Boolean',
		'AvailableFrom' => 'Time',
		'AvailableTo' => 'Time',
		'ApiAccessAllowed' => 'Boolean',
		'ResourceGroup' => 'Boolean',
		'Price' => 'Decimal',
		'PostAddress' => 'Varchar(255)',
		'PostCode' => 'Int',
		'PostOffice' => 'Varchar(100)',
		'HasChildResources' => 'Boolean'
	);
	
	static $api_access = true;
	
	static $translatableFields = array(
		'Name'
	);
	
	static $defaults = array(
		'AvailableByDefault' => true,
		'AvailableFrom' => '08:00',
		'AvailableTo' => '22:00',
		'ResourceGroup' => false,
		'ApiAccessAllowed' => true,
		'HasChildResources' => false
	);
	
	static $has_one = array(
		'Group' => 'ResourceGroup',
		'Parent' => 'Resource'
	);
	
	static $has_many = array(
		'Children' => 'Resource'
	);
	
	static $many_many = array(
		'PriceGroups' => 'PriceGroup',
		'AllowedServices' => 'Service'
	);
	
	static $belongs_many = array(
		'ExternalCustomers' => 'Customer'
	);
	
	static $searchable_fields = array(
		'Name',
		'GroupID'
	);
	
	static $summary_fields = array(
		'Name',
		'Group.Organization',
		'Group',
		'Parent'
	);
	
	static $default_sort = 'Name ASC';
	
	public function canView($member=null) {
		if (!$member)
			$member = Member::currentUser();
			
		if ($member) {
			if ($member instanceof Customer && !$this->isAdmin()) {
				if (count($member->ExternalResources('ResourceID = ' . $this->ID)))
					return true;
				
				$parentResource = $this->Parent();
				if ($parentResource && count($member->ExternalResources('ResourceID = ' . $parentResource->ID)))
					return true;
			}
		}
		return parent::canView($member);
	}	
	
	function getCMSFields() {
		i18n::set_time_format('HH:mm');
		
		$groupID = $this->GroupID;
		if (!$groupID) {
			$groupID = 0;
		}
		$fields = new FieldSet(
			new DialogTabSet('TabSet',
				new Tab('General', _t('Resource.GENERAL', 'General'),
					new TextField('Name', _t('Resource.NAME', 'Name')),
					new TimeField('AvailableFrom', _t('Resource.AVAILABLEFROM', 'Available from')),
					new TimeField('AvailableTo', _t('Resource.AVAILABLETO', 'Available to')),
					new NumericField('Price', _t('Resource.PRICE', 'Pris (€/h)')),
					new TextField('PostAddress', _t('Resource.POSTADDRESS', 'Post address')),
					$postCode = new NumericField('PostCode', _t('Resource.POSTCODE', 'Post code')),
					new TextField('PostOffice', _t('Resource.POSTOFFICE', 'Post office')),
					new CheckboxField('ResourceGroup', _t('Resource.RESOURCEGROUP', 'Resource group'))
				),
				new Tab('Group', _t('Resource.GROUP', 'Group'),
					$groupDOM = new DialogHasOneDataObjectManager(
						$this, 
						'Group', 
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
				),
				new Tab('Resources', _t('Resource.RESOURCES', 'Sub-resources'),
					$subResourceDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Children', 
						'Resource', 
						array(
							'Name' => _t('Resource.NAME', 'Name'), 
							'Group.Organization.Name' => _t('Resource.ORGANIZATION', 'Organisation'),
							'Group' => _t('Resource.GROUP', 'Group'),
							'Parent' => _t('Resource.PARENT', 'Parent resource')
						), 
						null, 
						"ID != $this->ID AND ((ParentID = 0 AND HasChildResources = 0) OR ParentID = {$this->ID}) AND (GroupID = 0 OR GroupID = {$groupID})"
					)
				),
				new Tab('PriceGroups', _t('Resource.PRICEGROUPS', 'Subventionering'),
					new DialogManyManyDataObjectManager($this, 'PriceGroups', 'PriceGroup', array('Name' => _t('PriceGroup.NAME', 'Name')))
				),
				new Tab('Services', _t('Resource.SERVICES', 'Tjänster'),
					new DialogManyManyDataObjectManager($this, 'AllowedServices', 'Service', array('Name' => _t('Service.NAME', 'Name')))
				)
			)
		);
		
		if (!ResourceBookingExtension::isAdmin()) {
			$groupDOM->setSourceFilter('ResourceOrganization.AdministratorID = ' . Member::currentUserID());
		}
		
		$subResourceDOM->allowRelationOverride = true;
		$postCode->setMaxLength(5);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		/*
		if ($this->isChanged('ApiAccessAllowed') && $this->ApiAccessAllowed) {
			$children = $this->Children();
			if ($children) {
				foreach ($children as $child) {
					if (!$child->ApiAccessAllowed) {
						$child->ApiAccessAllowed = true;
						$child->write();
					}
				}
			}
		}
		if ($this->isChanged('ApiAccessAllowed') && !$this->ApiAccessAllowed) {
			$group = $this->Group();
			if ($group && $group->ApiAccessAllowed) {
				$group->ApiAccessAllowed = false;
				$group->write();
			}
			$parent = $this->Parent();
			if ($parent && $parent->ApiAccessAllowed) {
				$parent->ApiAccessAllowed = false;
				$parent->write();
			}
		}
		*/
		
		if ($this->ParentID && !$this->GroupID) {
			$this->GroupID = $this->Parent()->GroupID;
		}
		
		if ($this->Children() && $this->Children()->Count() > 0) {
			$this->HasChildResources = true;
		}
		else if (!$this->Children() || ($this->Children() && $this->Children()->Count() == 0)) {
			$this->HasChildResources = false;
		}
	}
	
	public function getRealChildren() {
		$children = new DataObjectSet();
		foreach ($this->Children() as $child) {
			if (!$child->ResourceGroup) {
				$children->push($child);
			}
		}
		return $children;
	}
	
	public static function ResourceFilter($showAll = true) {
		$resources = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$resourceObjects = DataObject::get('Resource', 'ParentID = 0');
		if ($resourceObjects) {
			foreach ($resourceObjects as $resource) {
				if ($showAll || $resource->isAdmin()) {
					$disabled = false;
					if ($showAll) {
						$disabled = true;
					}
					$children = $resource->Children();
					if ($children && $children->Count() > 0) {
						$resources[$resource->ID] = array('id' => $resource->ID, 'class' => 'level0', 'text' => $resource->Name, 'disabled' => $disabled);
						foreach ($children as $child) {
							$resources[$child->ID] = array('id' => $child->ID, 'class' => 'main level1', 'text' => $child->Name, 'disabled' => $disabled);
						}
					}
					else {
						$resources[$resource->ID] = array('id' => $resource->ID, 'class' => 'main level0', 'text' => $resource->Name, 'disabled' => $disabled);
					}
				}
			}
		}
		$filter = new AdvancedDropdownField('ResourceFilter', _t('Resource.SINGULARNAME', 'Resource'), $resources, null, false, false, 'onResourceSelect', 'getResourceSource');
		$filter->setForceNameAsID(true);
		Requirements::javascript('resourcebooking/javascript/ResourceControls.js');
		Requirements::customScript(self::ResourceToGroupMapping());
		Requirements::customScript(self::ResourceToResourceMapping());
		return $filter;
	}
	
	public static function ResourceToGroupMapping() {
		$mappingArray = 'var resourceToGroupMapping = new Array();';
		$resources = DataObject::get('Resource');
		if ($resources) {
			foreach ($resources as $resource) {
				$mappingArray .= "resourceToGroupMapping[{$resource->ID}] = $resource->GroupID;";
			}
		}
		return $mappingArray;
	}
	
	public static function ResourceToResourceMapping() {
		$mappingArray = 'var resourceToResourceMapping = new Array();';
		$resources = DataObject::get('Resource', 'ParentID = 0');
		if ($resources) {
			foreach ($resources as $resource) {
				$mappingArray .= "resourceToResourceMapping[{$resource->ID}] = '";
				$children = $resource->Children();
				if ($children) {
					$first = true;
					foreach ($children as $child) {
						if (!$first) {
							$mappingArray .= ",";
						}
						else {
							$first = false;
						}
						$mappingArray .= "{$child->ID}";
					}
				}
				$mappingArray .= "';";
			}
		}
		return $mappingArray;
	}
	
	public function getAvailableFromTime($time) {
		$dayTime = strtotime(date('Y-m-d 00:00', $time));
		if ($this->AvailableByDefault) {
			$availableFromTime = $dayTime + date('G', strtotime($this->AvailableFrom))*60*60 + date('i', strtotime($this->AvailableFrom))*60;
		}
		else {
			$availableFromTime = strtotime(date('Y-m-d 23:59', $time));
		}
		return $availableFromTime;
	}
	
	public function getStartTime($time) {
		$availableFromTime = $this->getAvailableFromTime($time);
		$searchStart = date('Y-m-d 00:00', $availableFromTime);
		$searchEnd = date('Y-m-d H:i', $availableFromTime);
		$existingAdjustments = DataObject::get('Booking', "Start >= '$searchStart' AND End <= '$searchEnd' AND ResourceID = {$this->ID}", 'Start ASC');
		if ($existingAdjustments) {
			return strtotime($existingAdjustments->First()->Start);
		}
		else {
			return $availableFromTime;
		}
	}
	
	public function getAvailableToTime($time) {
		$dayTime = strtotime(date('Y-m-d 00:00', $time));
		if ($this->AvailableByDefault) {
			$availableToTime = $dayTime + date('G', strtotime($this->AvailableTo))*60*60 + date('i', strtotime($this->AvailableTo))*60;
		}
		else {
			$availableToTime = strtotime(date('Y-m-d 23:59', $time));
		}
		return $availableToTime;
	}
	
	public function getEndTime($time) {
		$availableToTime = $this->getAvailableToTime($time);
		$searchStart = date('Y-m-d H:i', $availableToTime);
		if (!$this->AvailableByDefault) {
			$searchStart = date('Y-m-d 00:00', $availableToTime);
		}
		$searchEnd = date('Y-m-d 23:59', $availableToTime);
		$existingAdjustments = DataObject::get('Booking', "Start >= '$searchStart' AND End <= '$searchEnd' AND ResourceID = {$this->ID}", 'Start DESC');
		if ($existingAdjustments) {
			return strtotime($existingAdjustments->First()->End);
		}
		else {
			return $availableToTime;
		}
	}
	
	function forTemplate() {
		return $this->Name;
	}
	
	function FullName() {
		$groupName = '';
		if ($this->GroupID) {
			$groupName = $this->Group()->FullName() . ' - ';
		}
		$resourceName = $this->Name;
		if ($this->ParentID) {
			$resourceName = $this->Parent()->Name . ' - ' . $this->Name;
		}
		return $groupName . $resourceName;
	}
	
	function getNiceFullName() {
		return $this->FullName();
	}
	
	public function requireDefaultRecords() {
		// set HasChildResources on resources with children
		$resources = DataObject::get('Resource', 'ParentID > 0');
		if ($resources && count($resources)) {
			foreach ($resources as $resource) {
				$parent = $resource->Parent();
				if ($parent && !$parent->HasChildResources) {
					$parent->HasChildResources = true;
					$parent->write();
				}
			}
		}
	}
	
	function isAdmin() {
		return $this->adminLevel() >= 1;
	}
	
	function adminLevel() {
		if (ResourceBookingExtension::isAdmin()) {
			return 3;
		}
		else if ($this->Group() && $this->Group()->Organization()->isAdmin()){
			return 2;
		}
		else if ($this->Group() && $this->Group()->isAdmin()) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	function Organization() {
		return $this->Group()->Organization();
	}
	
}

?>