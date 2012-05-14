<?php

class BookingLogItem extends DataObject {
	
	static $extensions = array(
		'TranslatableDataObject'
	);
	
	static $db = array(
		'Time' => 'SS_Datetime',
		'Type' => 'Enum("Created,Edited,Custom","Custom")',
		'Text' => 'Text'
	);
	
	static $has_one = array(
		'Booking' => 'Booking',
		'User' => 'Member'
	);
	
	static $has_many = array(
		'FieldChanges' => 'BookingLogItem_FieldChange'
	);
	
	static $translatableFields = array(
		'Text'
	);
	
	public function NiceTime() {
		return date('d.m.Y H:i', strtotime($this->Time));
	}
	
	public function LogText() {
		$text = $this->Text;
		if ($this->Type == 'Created') {
			$text = _t('BookingLogItem.CREATED', 'Bokning skapad');
		}
		else if ($this->Type == 'Edited') {
			$text = _t('BookingLogItem.EDITED', 'Bokning editerad, ändrade fält: ');
			$fieldCount = 0;
			if ($this->FieldChanges() && $this->FieldChanges()->Count() > 0) {
				foreach ($this->FieldChanges() as $fieldChange) {
					if ($fieldCount > 0) {
						$text .= ', ';
					}
					$text .= _t('Booking.' . strtoupper($fieldChange->FieldName), $fieldChange->FieldName);
					$fieldCount++;
				}
			}
		}
		return $text;
	}
	
}

class BookingLogItem_FieldChange extends DataObject {
	
	static $db = array(
		'FieldName' => 'Varchar(64)',
		'Before' => 'Text',
		'After' => 'Text'
	);
	
	static $has_one = array(
		'LogItem' => 'BookingLogItem'
	);
	
}

?>