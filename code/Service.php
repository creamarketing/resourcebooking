<?php

class Service extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'Price' => 'Decimal',
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	static $has_many = array(
		'Options' => 'ServiceOption'
	);
	
	static $belongs_many_many = array(
		'Resources' => 'Resource'
	);
	
	function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('Service.NAME', 'Namn')),
			new NumericField('Price', _t('Service.PRICE', 'Pris (€/st)')),
			$serviceOptionDOM = new DialogHasManyDataObjectManager($this, 'Options', 'ServiceOption', array('Name' => 'Namn'))
		);
		$serviceOptionDOM->setAddTitle(_t('ServiceOption.SINGULARNAME', 'Alternativ'));
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
}

class ServiceOption extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'Price' => 'Decimal',
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	static $has_one = array(
		'Service' => 'Service'
	);
	
	function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('Service.NAME', 'Namn')),
			new NumericField('Price', _t('Service.PRICE', 'Pris (€/st)'))
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
}

class ServiceOptionValue extends DataObject {
	
	static $db = array(
		'Amount' => 'Int'
	);
	
	static $has_one = array(
		'ServiceBooking' => 'ServiceBooking',
		'ServiceOption' => 'ServiceOption'
	);
	
}

class ServiceBooking extends DataObject {
	
	static $db = array(
		'Start' => 'SS_Datetime',
		'Amount' => 'Int',
		'ExtraInfo' => 'Text'
	);
	
	static $has_one = array(
		'Booking' => 'Booking',
		'Service' => 'Service',
		'Resource' => 'Resource'
	);
	
	static $has_many = array(
		'ServiceOptionValues' => 'ServiceOptionValue'
	);
	
}

?>