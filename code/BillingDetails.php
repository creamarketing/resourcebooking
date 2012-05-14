<?php

class BillingDetails extends DataObject {
	
	static $db = array(
		'BillingAddress' => 'Varchar(256)',
		'CustomPrice' => 'Decimal',
		'InternalPaid' => 'Boolean',
		'ExternalPaid' => 'Boolean',
		'ProvisionPaid' => 'Boolean',
		'InternalPaymentDate' => 'SS_Datetime',
		'ExternalPaymentDate' => 'SS_Datetime',
		'ProvisionPaymentDate' => 'SS_Datetime',
		'ProvisionPercent' => 'Int'
	);
	
	static $has_one = array(
		'PriceGroup' => 'PriceGroup',
		'Subsidizer' => 'CustomerGroup',
		'TaxType' => 'TaxType'
	);
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if ($this->ID) {
			$changedFields = $this->getChangedFields(false, 2);
			if ($changedFields) {
				$booking = DataObject::get_one('Booking', "BillingDetailsID = {$this->ID}");
				if ($booking) {
					$logItem = new BookingLogItem();
					$logItem->Time = date('Y-m-d H:i');
					$logItem->BookingID = $booking->ID;
					$logItem->UserID = Member::currentUserID();
					$logItem->Type = 'Edited';
				
					$logSaved = false;
					foreach ($changedFields as $fieldName => $values) {
						if ($fieldName != 'ClassName' && $fieldName != 'RecordClassName') {
							if (!$logSaved) {
								$logItem->write();
							}
							$fieldChange = new BookingLogItem_FieldChange();
							$fieldChange->FieldName = $fieldName;
							$fieldChange->Before = $values['before'];
							$fieldChange->After = $values['after'];
							$fieldChange->LogItemID = $logItem->ID;
							$fieldChange->write();
						}
					}
				}
			}
		}
	}
	
}

?>