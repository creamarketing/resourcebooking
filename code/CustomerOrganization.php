<?php

class CustomerOrganization extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	static $has_many = array(
		'Groups' => 'CustomerGroup'
	);
	
	function getCMSFields() {
		$fields = new FieldSet(
			new DialogTabSet('TabSet',
				new Tab('General', _t('CustomerOrganization.GENERAL', 'General'),
					new TextField('Name', _t('CustomerOrganization.NAME', 'Name'))
				),
				new Tab('Groups', _t('CustomerOrganization.GROUPS', 'Groups'),
					$groupDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Groups', 
						'CustomerGroup', 
						array(
							'Name' => _t('CustomerGroup.NAME', 'Name'),
							'Organization' =>_t('CustomerGroup.ORGANIZATION', 'Organization')
						),
						null,
						'ParentID = 0'
					)
				)
			)
		);
		$groupDOM->allowRelationOverride = true;
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function CustomerOrganizationToGroupMapping() {
		$mappingArray = 'var customerOrganizationToGroupMapping = new Array();';
		$organizations = DataObject::get('CustomerOrganization');
		if ($organizations) {
			foreach ($organizations as $organization) {
				$mappingArray .= "customerOrganizationToGroupMapping[{$organization->ID}] = new Array(";
				$first = true;
				foreach ($organization->Groups() as $group) {
					if (!$first) {
						$mappingArray .= ',';
					}
					else {
						$first = false;
					}
					$mappingArray .= "'{$group->ID}'";
				}
				$mappingArray .= ');';
			}
		}
		return $mappingArray;
	}
	
	public function forTemplate() {
		return $this->Name;
	}
	
	function isAdmin() {
		if (!Member::currentUser()) {
			return false;
		}
		return ResourceBookingExtension::isGroupAdmin();
	}
	
	public static function CustomerOrganizationFilter($showAddButton = false, $showEditButton = false) {
		$filter = new AdvancedDropdownField('CustomerOrganizationID', _t('CustomerOrganization.SINGULARNAME', 'Organization'), 'CustomerOrganization', null, $showAddButton, $showEditButton, 'CustomerOrganizationSelected');
		$filter->setForceNameAsID(true);
		Requirements::javascript('resourcebooking/javascript/CustomerControls.js');
		Requirements::customScript(self::CustomerOrganizationToGroupMapping());
		return $filter;
	}
	
}

?>