<?php

class ResourceBookingReport extends Controller {
	
	protected $parentController;
	protected $reportName;
	protected $data;
	protected $orientation = 'portrait';
	
	public function __construct($parentController, $reportName = 'ResourceBookingReport') {
		parent::__construct();
		$this->parentController = $parentController;
		$this->reportName = $reportName;
	}
	
	public function ReportForm() {
		$fields = $this->ReportOptionFields();
		
		$actions = $this->ReportActions();
		
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-form/jquery.form.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-cookie/jquery.cookie.js');
		Requirements::javascript('resourcebooking/javascript/jquery-ui-1.8.6.custom.min.js');
		Requirements::css('resourcebooking/css/smoothness/jquery-ui-1.8.6.custom.css');
		Requirements::javascript('resourcebooking/javascript/reports/ResourceBookingReport.js');
		Requirements::css('resourcebooking/css/reports/ResourceBookingReport.css');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/i18n.js');
		Requirements::add_i18n_javascript('resourcebooking/javascript/lang');
		
		return new Form($this, 'ReportForm', $fields, $actions);
	}
	
	public function GenerateReport($data, $form, $clearReportCache = true) {
		if ($clearReportCache) {
			$this->ClearReportCache();
		}
		Requirements::clear();
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::css('resourcebooking/css/reports/ResourceBookingReport_iframe.css');
		Requirements::javascript('resourcebooking/javascript/reports/ResourceBookingReport_iframe.js');
		
		$customFields = array(
			'Title' => _t("{$this->class}.NAME", $this->class),
		);
		$this->data = $data;
		return $this->renderWith('Reports/ResourceBookingReport', $customFields);
	}
	
	public function GenerateReportData() {
		return '';
	}
	
	protected function GetReportCache() {
		return Session::get('ReportCache');
	}
	
	protected function StoreReportCache($bookings) {
		if ($bookings) {
			Session::set('ReportCache', $bookings);
		}
	}
	
	protected function ClearReportCache() {
		Session::clear('ReportCache');
	}
	
	public function PDF($data, $form) {
		$downloadToken = time();
		if (isset($_REQUEST['DownloadToken'])) {
			$downloadToken = $_REQUEST['DownloadToken'];
		}
		Cookie::set('fileDownloadToken', $downloadToken);
		return singleton('PDFRenditionService')->render($this->GenerateReport($data, $form, false), 'browser', 'report.pdf');
	}
	
	protected function ReportActions() {
		$actions = new FieldSet(
			new FormAction('GenerateReport', _t('ResourceBookingReport.GENERATEREPORT', 'Generate report')),
			new FormAction('PDF', _t('ResourceBookingReport.SAVEPDF', 'Save PDF'), null, null, 'hidden'),
			new FormAction('ItemDetails', _t('ResourceBookingReport.ITEMDETAILS', 'Show details'), null, null, 'hidden')
		);
		return $actions;
	}
	
	protected function ReportOptionFields() {
		$fields = new FieldSet(
			new HeaderField('Header', _t("{$this->class}.NAME", $this->class)),
			new HiddenField('Orientation', '', $this->orientation),
			new HiddenField('FormAction', '', 1),
			new HiddenField('DownloadToken', ''),
			new HiddenField('DetailItemID', ''),
			$startDate = new DateField('StartDate', _t('ResourceBookingReport.STARTDATE', 'Start date')),
			$endDate = new DateField('EndDate', _t('ResourceBookingReport.ENDDATE', 'End date')),
			new LiteralField('', '<div class="clear"></div>')
		);
		$startDate->setConfig('showcalendar', true);
		$startDate->setConfig('dateformat', 'dd.MM.yyyy');
		$endDate->setConfig('showcalendar', true);
		$endDate->setConfig('dateformat', 'dd.MM.yyyy');
		
		return $fields;
	}
	
	public function ItemDetails($data, $form) {
		$this->data = $data;
		$itemID = $data['DetailItemID'];
		
		return $itemID;
	}
	
	public function Link() {
		return $this->parentController->Link() . $this->reportName;
	}
	
	public function Orientation() {
		return $this->orientation;
	}
	
}

?>