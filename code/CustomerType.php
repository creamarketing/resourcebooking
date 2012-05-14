<?php

class CustomerType extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('CustomerGroup.NAME', 'Name'))
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
}

?>