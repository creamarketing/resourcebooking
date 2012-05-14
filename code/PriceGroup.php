<?php

class PriceGroup extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject',
		'PermissionExtension'
	);
	
	static $db = array(
		'Name' => 'Varchar(64)',
		'SubsidizePercent' => 'Int'
	);
	
	static $belongs_many_many = array(
		'Resources' => 'Resource'
	);
	
	static $translatableFields = array(
		'Name'
	);
	
	function getCMSFields() {
		$fields = new FieldSet(
			new TextField('Name', _t('PriceGroup.NAME', 'Name')),
			new NumericField('SubsidizePercent', _t('PriceGroup.SUBSIDIZEPERCENT', 'Subventionering (%)'))
		);
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	public static function PriceGroups() {
		$priceGroupArray = 'var subsidize = new Array();';
		$priceGroups = DataObject::get('PriceGroup');
		if ($priceGroups) {
			foreach ($priceGroups as $priceGroup) {
				$priceGroupArray .= "subsidize[{$priceGroup->ID}] = {$priceGroup->SubsidizePercent};";
			}
		}
		return $priceGroupArray;
	}
	
}

?>