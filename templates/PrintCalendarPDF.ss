<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	
	<head>
		<style>
			@page {
				/* a4 portrait size in pixels @ 96dpi is 794 x 1123 */
				size: A4 portrait;
				margin: 20px;
			}
		</style>
		<title>Print calendar</title>
	</head>
	
	<body>
		<div id="Header">
			<h1>$ViewText</h1>
		</div>
		<div id="Title">$ResourceName&nbsp;&nbsp;$Title</div>
		<div id="PrintCalendar" class="pdf">
			$CalendarHTML
		</div>
	</body>
	
</html>