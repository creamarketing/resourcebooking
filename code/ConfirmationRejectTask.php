<?php

class ConfirmationRejectTask extends DailyTask {
	
	function process() {
		$currentDate = date('Y-m-d');
		$confirmations = DataObject::get('Confirmation', "Accepted = NULL AND Rejected = NULL AND Due != NULL AND Due <= '$currentDate'");
		if ($confirmations && $confirmations->Count() > 0) {
			foreach ($confirmations as $confirmation) {
				echo ' - Confirmation ' .$confirmation->ID . " is expired!\n";
				$bookings = DataObject::get('Booking', "BookingID = {$confirmation->BookingID} AND Status = 'Preliminary'");
				if ($bookings) {
					foreach ($bookings as $booking) {
						$booking->Status = 'Rejected';
						$booking->write();
						echo '   - Rejected booking ' .$booking->ID . "!\n";
					}
				}
				$confirmation->Rejected = date('Y-m-d H:i');
				$confirmation->write();
			}
		}
		else {
			echo " - No expired confirmations found\n";
		}
		echo "\n";
	}
	
}

?>