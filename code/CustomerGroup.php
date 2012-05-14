<?php

class CustomerGroup extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'Info' => 'Varchar(256)',
		'Address' => 'Varchar(256)',
		'CanSubsidize' => 'Boolean',
		'CostCenter' => 'Int',
		'ProjectNr' => 'Int',
		'AccountNr' => 'Int',
		'IsMainGroup' => 'Boolean',
		'HasChildGroups' => 'Boolean',
		'InternalCustomer' => 'Boolean',
		'ProvisionReceiver' => 'Boolean'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	static $has_one = array(
		'Organization' => 'CustomerOrganization',
		'Parent' => 'CustomerGroup'
	);
	
	static $has_many = array(
		'Customers' => 'Customer',
		'Children' => 'CustomerGroup'
	);
	
	static $many_many = array(
		'AllCustomers' => 'Customer'
	);
	
	static $defaults = array(
		'HasChildGroups' => false
	);
	
	function isAdmin() {
		if (!Member::currentUser()) {
			return false;
		}
		return ResourceBookingExtension::isGroupAdmin();
	}
	
	function getCMSFields() {
		$organizationID = $this->OrganizationID;
		if (!$organizationID) {
			$organizationID = 0;
		}
		
		$fields = new FieldSet(
			new DialogTabSet('TabSet',
				new Tab('General', _t('CustomerGroup.GENERAL', 'General'),
					new TextField('Name', _t('CustomerGroup.NAME', 'Namn')),
					new TextField('Info', _t('CustomerGroup.INFO', 'Info')),
					new TextareaField('Address', _t('CustomerGroup.ADDRESS', 'Adress')),
					new CheckboxField('CanSubsidize', _t('CustomerGroup.CANSUBSIDIZE', 'Kan subventionera')),
					new CheckboxField('IsMainGroup', _t('CustomerGroup.MAINGROUP', 'Huvudgrupp')),
					new CheckboxField('InternalCustomer', _t('CustomerGroup.INTERNALCUSTOMER', 'Intern kund')),
					new CheckboxField('ProvisionReceiver', _t('CustomerGroup.PROVISIONRECEIVER', 'Provisionsmottagare')),
					new FieldGroup(_t('CustomerGroup.ACCOUNT', 'Konto'),
						new NumericField('CostCenter', _t('CustomerGroup.COSTCENTER', 'Kostnadsställe')),
						new NumericField('ProjectNr', _t('CustomerGroup.PROJECTNR', 'Proj.')),
						new NumericField('AccountNr', _t('CustomerGroup.ACCOUNTNR', 'Ink.'))
					)
				),
				new Tab('Organization', _t('CustomerGroup.ORGANIZATION', 'Organization'),
					new DialogHasOneDataObjectManager(
						$this, 
						'Organization', 
						'CustomerOrganization', 
						array(
							'Name' => _t('CustomerOrganization.NAME', 'Name')
						)
					)
				),
				new Tab('Groups', _t('CustomerGroup.SUBGROUPS', 'Sub-groups'),
					$subGroupDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Children', 
						'CustomerGroup', 
						array(
							'Name' => _t('CustomerGroup.NAME', 'Name'), 
							'Organization.Name' => _t('CustomerGroup.ORGANIZATION', 'Organisation'),
							'Parent' => _t('CustomerGroup.PARENT', 'Parent group')
						), 
						null, 
						"ID != $this->ID AND ((ParentID = 0 AND HasChildGroups = 0 AND IsMainGroup = 0) OR ParentID = {$this->ID}) AND (OrganizationID = 0 OR OrganizationID = {$organizationID})"
					)
				),
				new Tab('Customers', _t('CustomerGroup.CUSTOMERS', 'Customers'),
					$customerDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Customers', 
						'Customer', 
						array(
							'FirstName' => _t('Customer.FIRSTNAME', 'First name'), 
							'Surname' => _t('Customer.LASTNAME', 'Last name'),
							'Group' => _t('Customer.GROUP', 'Avdelning/grupp'),
							'Group.Organization.Name' => _t('Customer.ORGANIZATION', 'Organization')
						),
						null,
						"GroupID = 0 OR GroupID = {$this->ID}"
					)
				)
			)
		);
		$customerDOM->allowRelationOverride = true;
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function CustomerGroupToCustomerMapping() {
		$mappingArray = 'var customerGroupToCustomerMapping = new Array();';
		$groups = DataObject::get('CustomerGroup');
		if ($groups) {
			foreach ($groups as $group) {
				$mappingArray .= "customerGroupToCustomerMapping[{$group->ID}] = new Array(";
				$first = true;
				foreach ($group->AllCustomers() as $customer) {
					if (!$first) {
						$mappingArray .= ',';
					}
					else {
						$first = false;
					}
					$mappingArray .= "'{$customer->ID}'";
				}
				$mappingArray .= ');';
			}
		}
		return $mappingArray;
	}
	
	public function forTemplate() {
		return $this->Name;
	}
	
	function forDropdown() {
		return $this->Organization()->Name . ' - ' . $this->Name;
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if ($this->ParentID && !$this->OrganizationID) {
			$this->OrganizationID = $this->Parent()->OrganizationID;
		}
		
		if ($this->Children() && $this->Children()->Count() > 0) {
			$this->HasChildGroups = true;
		}
		else if (!$this->Children() || ($this->Children() && $this->Children()->Count() == 0)) {
			$this->HasChildGroups = false;
		}
	}
	
	function Account() {
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
		if ($this->AccountNr) {
			if ($account != '') {
				$account .= ' ';
			}
			$account .= $this->AccountNr;
		}
		return $account;
	}
	
	public static function CustomerGroupFilter($showAddButton = false, $showEditButton = false) {
		$groups = array('' => _t('AdvancedDropdownField.NONESELECTED', '(None selected)'));
		$mainGroups = DataObject::get('CustomerGroup', 'ParentID = 0');
		if ($mainGroups) {
			foreach ($mainGroups as $mainGroup) {
				$groups[$mainGroup->ID] = $mainGroup->Name;
				if ($mainGroup->Children()) {
					foreach ($mainGroup->Children() as $childGroup) {
						$groups[$childGroup->ID] = $mainGroup->Name . ' - ' . $childGroup->Name;
					}
				}
			}
		}
		$filter = new AdvancedDropdownField('CustomerGroupID', _t('Customer.GROUP', 'Avdelning/grupp'), $groups, null, $showAddButton, $showEditButton, 'CustomerGroupSelected');
		$filter->setForceNameAsID(true);
		$filter->setSourceClass('CustomerGroup');
		Requirements::javascript('resourcebooking/javascript/CustomerControls.js');
		Requirements::customScript(self::CustomerGroupToCustomerMapping());
		return $filter;
	}
	
}

?>