<?php

$extensionClassesPath = t3lib_extMgm::extPath('format') . 'Classes/';
return array(
	'tx_format_service_csvservice'   => $extensionClassesPath . 'Service/CsvService.php',
	'tx_format_service_excelservice' => $extensionClassesPath . 'Service/ExcelService.php',
	'tx_format_service_pdfservice'   => $extensionClassesPath . 'Service/PdfService.php',
);
