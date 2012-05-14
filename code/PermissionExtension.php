<?php

class PermissionExtension extends DataObjectDecorator {
	
	public function canView($member = null) {
		if ($this->owner->hasMethod('isAdmin')) {
			return $this->owner->isAdmin();
		}
		return ResourceBookingExtension::isAdmin();
	}

	public function canCreate($member = null) {
		if ($this->owner->hasMethod('isAdmin')) {
			return $this->owner->isAdmin();
		}
		return ResourceBookingExtension::isAdmin();
	}
	
	public function canEdit($member = null) {
		if ($this->owner->hasMethod('isAdmin')) {
			return $this->owner->isAdmin();
		}
		return ResourceBookingExtension::isAdmin();
	}

	public function canDelete($member = null) {
		if ($this->owner->hasMethod('isAdmin')) {
			return $this->owner->isAdmin();
		}
		return ResourceBookingExtension::isAdmin();
	}
	
}

?>