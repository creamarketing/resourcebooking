<?php

class BookingType extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'Color' => 'Varchar(6)'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	public function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('BookingType.NAME', 'Name')),
			new ColorField('Color', _t('BookingType.COLOR', 'Color'))
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
}

?>