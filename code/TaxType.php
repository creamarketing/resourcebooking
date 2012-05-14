<?php

class TaxType extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'TaxPercent' => 'Int',
		'Name' => 'Varchar(128)'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	public function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('TaxType.NAME', 'Namn')),
			new NumericField('TaxPercent', _t('TaxType.TAXPERCENT', 'Moms %'))
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function TaxTypes() {
		$taxArray = 'var taxTypes = new Array();';
		$taxTypes = DataObject::get('TaxType');
		if ($taxTypes) {
			foreach ($taxTypes as $taxType) {
				$taxArray .= "taxTypes[{$taxType->ID}] = {$taxType->TaxPercent};";
			}
		}
		return $taxArray;
	}
	
}

?>