<?php

class ResourceGroup extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'ApiAccessAllowed' => 'Boolean',
		'CostCenter' => 'Int',
		'ProjectNr' => 'Int',
		'AccountNr' => 'Int',
		'ProvisionPercent' => 'Int'
	);
	
	static $has_many = array(
		'Resources' => 'Resource'
	);
	
	static $has_one = array(
		'Organization' => 'ResourceOrganization',
		'Administrator' => 'Member'
	);
	
	static $defaults = array(
		'ApiAccessAllowed' => true
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	static $searchable_fields = array(
		'Name'
	);
	
	static $summary_fields = array(
		'Name'
	);
	
	static $default_sort = 'Name ASC';
	
	static $api_access = array('view' => array('Name', 'OrganizationID'));
	
	private static $internalAccountNr = 343;
	private static $provisionAccountNr = 483;
	
	public function canView($member=null) {
		if (!$member)
			$member = Member::currentUser();
		
		if ($member) {
			if ($member instanceof Customer && !$this->isAdmin()) {
				$resources = $this->Resources();
				if ($resources) {
					foreach ($resources as $resource) {
						if ($resource->canView($member))
							return true;
					}
				}
			}
		}
		return parent::canView($member);
	}	
	
	function isAdmin() {
		if (!Member::currentUser()) {
			return false;
		}
		if ($this->AdministratorID) {
			return Member::currentUserID() == $this->AdministratorID && ResourceBookingExtension::isGroupAdmin();
		}
		if ($this->Organization()) {
			return $this->Organization()->isAdmin();
		}
		return false;
	}
	
	function getCMSFields() {
		$admins = array(0 => _t('ResourceBooking.NONESELECTED', '(None selected)'));
		$groupAdminGroup = DataObject::get_one('Group', "Code = 'resourcegroupadmin'");
		if ($groupAdminGroup && $groupAdminGroup->Members()) {
			foreach ($groupAdminGroup->Members() as $member) {
				$admins[$member->ID] = $member->getName();
			}
		}
		$fields = new FieldSet(
			new DialogTabSet('TabSet',
				new Tab('General', _t('ResourceGroup.GENERAL', 'General'),
					new TextField('Name', _t('ResourceGroup.NAME', 'Name')),
					new AdvancedDropdownField('AdministratorID', _t('ResourceGroup.ADMINISTRATOR', 'Administratör'), $admins),
					new NumericField('ProvisionPercent', _t('ResourceGroup.PROVISIONPERCENT', 'Provision %')),
					new FieldGroup(_t('ResourceGroup.ACCOUNT', 'Konto'),
						new NumericField('CostCenter', _t('ResourceGroup.COSTCENTER', 'Kostnadsställe')),
						new NumericField('ProjectNr', _t('ResourceGroup.PROJECTNR', 'Proj.')),
						new NumericField('AccountNr', _t('ResourceGroup.ACCOUNTNR', 'Ink.'))
					)
				),
				new Tab('Grouping', _t('ResourceGroup.ORGANIZATION', 'Organization'),
					$organizationDOM = new DialogHasOneDataObjectManager(
						$this, 
						'Organization', 
						'ResourceOrganization', 
						array(
							'Name' => _t('ResourceOrganization.NAME', 'Name')
						)
					)
				),
				new Tab('Resource', _t('ResourceGroup.RESOURCES', 'Resources'),
					$resourceDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Resources', 
						'Resource', 
						array(
							'Name' => _t('Resource.NAME', 'Name'), 
							'Group.Organization.Name' => _t('Resource.ORGANIZATION', 'Organisation'),
							'Group' => _t('Resource.GROUP', 'Group')
						),
						null,
						"ParentID = 0 AND (GroupID = 0 OR GroupID = {$this->ID})"
					)
				)
			)
		);
		
		if (!ResourceBookingExtension::isAdmin()) {
			$organizationDOM->setSourceFilter('AdministratorID = ' . Member::currentUserID());
		}
		
		$resourceDOM->allowRelationOverride = true;
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		/*
		if ($this->isChanged('ApiAccessAllowed') && $this->ApiAccessAllowed) {
			$resources = $this->Resources();
			if ($resources) {
				foreach ($resources as $resource) {
					if (!$resource->ApiAccessAllowed) {
						$resource->ApiAccessAllowed = true;
						$resource->write();
					}
				}
			}
		}
		if ($this->isChanged('ApiAccessAllowed') && !$this->ApiAccessAllowed) {
			$organization = $this->Organization();
			if ($organization && $organization->ApiAccessAllowed) {
				$organization->ApiAccessAllowed = false;
				$organization->write();
			}
		}
		*/
	}
	
	function forDropdown() {
		return $this->Organization()->Name . ' - ' . $this->Name;
	}
	
	function forTemplate() {
		return $this->Name;
	}
	
	public static function ResourceGroupFilter($showAll = true) {
		$groups = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$groupObjects = DataObject::get('ResourceGroup');
		if ($groupObjects) {
			foreach ($groupObjects as $group) {
				if ($showAll || $group->isAdmin()) {
					$groups[$group->ID] = $group->Name;
				}
			}
		}
		$filter = new AdvancedDropdownField('ResourceGroupFilter', _t('ResourceGroup.SINGULARNAME', 'Group'), $groups, null, false, false, 'onResourceGroupSelect');
		$filter->setForceNameAsID(true);
		Requirements::javascript('resourcebooking/javascript/ResourceControls.js');
		Requirements::customScript(self::GroupToOrganizationMapping());
		return $filter;
	}
	
	public static function GroupToOrganizationMapping() {
		$mappingArray = 'var groupToOrganizationMapping = new Array();';
		$groups = DataObject::get('ResourceGroup');
		if ($groups) {
			foreach ($groups as $group) {
				$mappingArray .= "groupToOrganizationMapping[{$group->ID}] = $group->OrganizationID;";
			}
		}
		return $mappingArray;
	}
	
	function FullName() {
		$organizationName = '';
		if ($this->Organization()) {
			$organizationName = $this->Organization()->Name . ' - ';
		}
		return $organizationName . $this->Name;
	}
	
	function ParentResources() {
		$resources = DataObject::get('Resource', "GroupID = {$this->ID} AND ParentID = 0");
		return $resources;
	}
	
	function Account($type = 'external') {
		$account = '';
		if ($this->CostCenter) {
			$account .= $this->CostCenter;
		}
		if ($this->ProjectNr) {
			if ($account != '') {
				$account .= ' ';
			}
			$account .= $this->ProjectNr;
		}
		if ($this->AccountNr && $type == 'external') {
			if ($account != '') {
				$account .= ' ';
			}
			$account .= $this->AccountNr;
		}
		
		if ($type == 'internal') {
			if ($account != '') {
				$account .= ' ';
			}
			$account .= self::$internalAccountNr;
		}
		if ($type == 'provision') {
			if ($account != '') {
				$account .= ' ';
			}
			$account .= self::$provisionAccountNr;
		}
		
		return $account;
	}
}

?>