<?php

class ResourceBookingPage extends Page {
	
	public static $RequireLogin = true;
	
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
	
		// create the resource booking admin group
		$this->getOrCreateGroup('RESOURCEBOOKINGADMIN', 'resourcebookingadmin', 'Resursbokningsadministratörer');
	
		// create the resource organization admin group
		$this->getOrCreateGroup('RESOURCEORGANIZATIONADMIN', 'resourceorganizationadmin', 'Organisationsadministratörer');
	
		// create the resource group admin group
		$this->getOrCreateGroup('RESOURCEGROUPADMIN', 'resourcegroupadmin', 'Resursgruppsadministratörer');
	
		// create the customer group
		$this->getOrCreateGroup('CUSTOMER', 'customer', 'Kund');
		
		$this->moveGroupsToOrganizations(array());
	}
	
	private function getOrCreateGroup($permissionCode, $groupCode, $groupTitle) {
		// create the group, if it doesn't exist
		$memberGroup = DataObject::get_one('Group', "Code = '$groupCode'");
		if (!$memberGroup) {
			$group = new Group();
			$group->Code = $groupCode;
			$group->Title = $groupTitle;
			$group->write();

			Permission::grant($group->ID, $permissionCode);
			DB::alteration_message(_t('ResourceBookingPage.GROUPCREATED', "Group $groupTitle created"), "created");
		}
		else {
			// check that the existing group has the correct permission
			$queryResult = DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '$memberGroup->ID' AND \"Code\" LIKE '$permissionCode'");
			if ($queryResult->numRecords() == 0 ) {
				Permission::grant($memberGroup->ID, $permissionCode);
				DB::alteration_message(_t('ResourceBookingPage.PERMISSIONADDED', "Added permissions for existing group $groupTitle"), "created");
			}
		}
	}
	
	private function moveGroupsToOrganizations($ids = array()) {
		foreach ($ids as $id) {
			$badGroup = DataObject::get_by_id('ResourceGroup', $id);
			if ($badGroup) {
				$newOrganization = new ResourceOrganization();
				$newOrganization->Name_fi_FI = $badGroup->Name_fi_FI;
				$newOrganization->Name_sv_SE = $badGroup->Name_sv_SE;
				$newOrganization->Name_en_US = $badGroup->Name_en_US;
				$newOrganization->write();
				DB::alteration_message("ResourceGroup {$badGroup->ID} moved to a new organization", "created");
				
				if ($badGroup->ParentResources()) {
					foreach ($badGroup->ParentResources() as $badResource) {
						$newGroup = new ResourceGroup();
						$newGroup->Name_fi_FI = $badResource->Name_fi_FI;
						$newGroup->Name_sv_SE = $badResource->Name_sv_SE;
						$newGroup->Name_en_US = $badResource->Name_en_US;
						$newGroup->OrganizationID = $newOrganization->ID;
						$newGroup->write();
						DB::alteration_message("Resource {$badResource->ID} moved to a new group", "created");
						
						if ($badResource->Children()) {
							foreach ($badResource->Children() as $badChild) {
								$badChild->GroupID = $newGroup->ID;
								$badChild->ParentID = 0;
								$badChild->write();
								DB::alteration_message("Child-resource {$badChild->ID} moved to a new parent group", "created");
							}
						}
						
						$badResource->delete();
					}
				}
				
				$badGroup->delete();
			}
		}
	}
	
}

class ResourceBookingPage_Controller extends Page_Controller {
	
	static $extensions = array(
		'ResourceBookingExtension()'
	);
	
	public function init() {
		parent::init();
		
		$this->getResourceBookingRequirements();
	}
	
	public function QuickMonth() {
		return '';
	}
	
}

?>