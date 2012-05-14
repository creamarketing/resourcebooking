<?php

class ResourceOrganization extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'ApiAccessAllowed' => 'Boolean'
	);
	
	static $has_many = array(
		'Groups' => 'ResourceGroup'
	);
	
	static $has_one = array(
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
	
	static $api_access = array('view' => array('Name'));
	
	public function canView($member=null) {
		if (!$member) {
			$member = Member::currentUser();
		}
		
		if ($member) {
			if ($member instanceof Customer) {
				$groups = $this->Groups();
				if ($groups) {
					foreach ($groups as $group) {
						if ($group->canView($member)) {
							return true;
						}
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
			return Member::currentUserID() == $this->AdministratorID && ResourceBookingExtension::isOrganizationAdmin();
		}
		return ResourceBookingExtension::isOrganizationAdmin();
	}
	
	function getCMSFields() {
		$admins = array(0 => _t('ResourceBooking.NONESELECTED', '(None selected)'));
		$organizationAdminGroup = DataObject::get_one('Group', "Code = 'resourceorganizationadmin'");
		if ($organizationAdminGroup && $organizationAdminGroup->Members()) {
			foreach ($organizationAdminGroup->Members() as $member) {
				$admins[$member->ID] = $member->getName();
			}
		}
		$fields = new FieldSet(
			new DialogTabSet('TabSet',
				new Tab('General', _t('ResourceOrganization.GENERAL', 'General'),
					new TextField('Name', _t('ResourceOrganization.NAME', 'Name')),
					new AdvancedDropdownField('AdministratorID', _t('ResourceOrganization.ADMINISTRATOR', 'Administratör'), $admins)
				),
				new Tab('Group', _t('ResourceOrganization.GROUPS', 'Groups'),
					$groupDOM = new DialogHasManyDataObjectManager(
						$this, 
						'Groups', 
						'ResourceGroup', 
						array(
							'Name' => _t('ResourceGroup.NAME', 'Name'),
							'Organization' => _t('ResourceGroup.ORGANIZATION', 'Organization')
						)
					)
				)
			)
		);
		$groupDOM->allowRelationOverride = true;
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	function onBeforeWrite() {
		parent::onBeforeWrite();
		/*
		if ($this->isChanged('ApiAccessAllowed') && $this->ApiAccessAllowed) {
			$groups = $this->Groups();
			if ($groups) {
				foreach ($groups as $group) {
					if (!$group->ApiAccessAllowed) {
						$group->ApiAccessAllowed = true;
						$group->write();
					}
				}
			}
		}
		*/
	}
	
	function forTemplate() {
		return $this->Name;
	}
	
	public static function ResourceOrganizationFilter() {
		$filter = new AdvancedDropdownField('ResourceOrganizationFilter', _t('ResourceOrganization.SINGULARNAME', 'Organization'), 'ResourceOrganization', null, false, false, 'onResourceOrganizationSelect');
		$filter->setForceNameAsID(true);
		Requirements::javascript('resourcebooking/javascript/ResourceControls.js');
		return $filter;
	}
	
}

?>