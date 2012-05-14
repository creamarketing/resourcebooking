<?php

class Customer extends Member {
	
	static $extensions = array(
		'PermissionExtension'
	);
	
	static $db = array(
		'Address' => 'Varchar(128)',
		'SecurityGroup' => 'Int',
		'Inactive' => 'Boolean'
	);
	
	static $has_one = array(
		'Group' => 'CustomerGroup',
		'ExternalBookingType' => 'BookingType'
	);
	
	static $many_many = array(
		'ExternalResources' => 'Resource'
	);
	
	static $belongs_many_many = array(
		'CustomerGroups' => 'CustomerGroup'
	);
	
	static $searchable_fields = array(
		'Address'
	);
	
	static $api_access = array(
         'view' => array('ID', 'ExternalBookingTypeID')
	);
	
	function isAdmin() {
		if (!Member::currentUser()) {
			return false;
		}
		return ResourceBookingExtension::isGroupAdmin();
	}
	
	public function canView($member=null) {
		if (!$member)
			$member = Member::currentUser();
			
		if ($member) {
			if ($this->ID == $member->ID)
			return true;
		}
		return parent::canView($member);
	}
	
	public function getCMSFields() {
		$member = new Member();
		$memberFields = $member->getMemberFormFields();
		
		// remove date and time format fields and locale filed, not needed here
		$memberFields->removeByName('DateFormat');
		$memberFields->removeByName('TimeFormat');
		$memberFields->removeByName('Locale');
		
		$fields = new FieldSet();
		if (substr($_GET['url'], -9) == 'myprofile') {
			// only simple cms fields for my profile
			$fields = $memberFields;
			$fields->push(new TextareaField('Address', _t('Customer.ADDRESS', 'Adress')));
		}
		else {
			$customerGroups = $this->CustomerGroups();
			$groupArray = array();
			if ($customerGroups) {
				$groupArray = $customerGroups->map('ID', 'Name');
			}
			$fields->push(
				$tabSet = new DialogTabSet('TabSet',
					$generalTab = new Tab('General', _t('Customer.GENERAL', 'General')),
					new Tab('Group', _t('Customer.GROUP', 'Group'),
						new DialogManyManyDataObjectManager(
							$this,
							'CustomerGroups', 
							'CustomerGroup', 
							array(
								'Name' => _t('CustomerGroup.NAME', 'Name'),
								'Organization' =>_t('CustomerGroup.ORGANIZATION', 'Organization'),
								'Parent' => _t('CustomerGroup.PARENT', 'Parent group')
							)
						),
						new DropdownField('GroupID', _t('Customer.DEFAULTGROUP', 'Standardgrupp'), $groupArray),
						new LiteralField('CustomScript', "
							<script type='text/javascript'>
								jQuery(document).ready(function() {
									jQuery('input[name=\"CustomerGroups[]\"]').change(function() {
										var id = jQuery(this).val();
										var name = jQuery(this).parent().prev().children().first().children().first().html();
										if (jQuery(this).attr('checked')) {
											jQuery('#GroupID select').append('<option value=\"' + id + '\">' + name + '</option>');
										}
										else {
											jQuery('#GroupID select option[value=\"' + id + '\"]').remove();
										}
									});
								});
							</script>
						")
					)
				)
			);
			$generalTab->setChildren($memberFields);
			$generalTab->push(new TextareaField('Address', _t('Customer.ADDRESS', 'Adress')));
			if (ResourceBookingExtension::adminLevel() >= 2) {
				$generalTab->push(new CheckboxField('Inactive', _t('Customer.INACTIVE', 'Inaktiv')));
				$groups = array(
					0 => 'Kund',
					1 => 'Gruppadministratör'
				);
				if (ResourceBookingExtension::adminLevel() == 3) {
					$groups[2] = 'Organisationsadministratör';
					$groups[3] = 'Huvudadministratör';
				}
				$generalTab->push(new DropdownField('SecurityGroup', _t('Customer.SECURITYGROUP', 'Säkerhetsgrupp'), $groups));
			}
			if (ResourceBookingExtension::adminLevel() == 3) {
				$tabSet->push(
					new Tab('ExternalResources', _t('Customer.EXTERNALRESOURCES', 'External access'),
						new DialogManyManyDataObjectManager(
							$this,
							'ExternalResources',
							'Resource',
							array(
								'NiceFullName' => _t('Resource.NAME', 'Name')							
							)
						),
						new DialogHasOneDataObjectManager(
							$this, 
							'ExternalBookingType', 
							'BookingType'
						)
					)
				);
			}
		}
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function BookingMemberLoginForm($controller) {
		$actions = new FieldSet(
			new FormAction('LoginBookingMember', 'Login')
		);
		$loginForm = new MemberLoginForm($controller, 'BookingMemberLoginForm', null, $actions, false);
		return $loginForm;
	}
	
	public static function BookingMemberRegistrationForm($controller) {
		$member = new Member();
		$fields = $member->getMemberFormFields();
		
		// remove date and time format fields and locale filed, not needed here
		$fields->removeByName('DateFormat');
		$fields->removeByName('TimeFormat');
		$fields->removeByName('Locale');

		$actions = new FieldSet(
			new FormAction('RegisterBookingMember', 'Register')
		);
		
		$requirements = new Member_Validator();
		
		return new Form($controller, "BookingMemberRegistrationForm", $fields, $actions, $requirements);
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		$memberGroup0 = DataObject::get_one('Group', "Code = 'customer'");
		$memberGroup1 = DataObject::get_one('Group', "Code = 'resourcegroupadmin'");
		$memberGroup2 = DataObject::get_one('Group', "Code = 'resourceorganizationadmin'");
		$memberGroup3 = DataObject::get_one('Group', "Code = 'resourcebookingadmin'");
		if (!$this->inGroup('customer')) {
			$memberGroup0->Members()->add($this);
		}
		if ($this->SecurityGroup == 0) {
			$memberGroup1->Members()->remove($this);
			$memberGroup2->Members()->remove($this);
			$memberGroup3->Members()->remove($this);
		}
		if ($this->SecurityGroup == 1) {
			$memberGroup1->Members()->add($this);
			$memberGroup2->Members()->remove($this);
			$memberGroup3->Members()->remove($this);
		}
		if ($this->SecurityGroup == 2) {
			$memberGroup2->Members()->add($this);
			$memberGroup3->Members()->remove($this);
		}
		if ($this->SecurityGroup == 3) {
			$memberGroup3->Members()->add($this);
		}
	}
	
	public function FullName() {
		return $this->FirstName . ' ' . $this->Surname;
	}
	
	public function BillingAddress() {
		return nl2br($this->Group()->Address, true);
	}
	
	function getValidator() {
		return null;
	}
	
	function Organization() {
		return $this->Group()->Organization();
	}
	
	public static function CustomerFilter($showAddButton = false, $showEditButton = false) {
		$filter = new AdvancedDropdownField('CustomerID', _t('Customer.SINGULARNAME', 'Kontaktperson'), 'Customer', null, $showAddButton, $showEditButton, 'CustomerSelected');
		$filter->setForceNameAsID(true);
		Requirements::javascript('resourcebooking/javascript/CustomerControls.js');
		return $filter;
	}
	
}

?>