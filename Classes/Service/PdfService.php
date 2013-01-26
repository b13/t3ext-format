<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 b:dreizehn GmbH <typo3@b13.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Class to create a pdf and output it 
 *
 * @author	b:dreizehn GmbH <typo3@b13.de>
 * @package	TYPO3
 * @subpackage	Tx_Format
 */
class Tx_Format_Service_PdfService {

	/**
	 * holds the PDF object
	 */
	protected $pdfObject;


	/**
	 *
	 */
	public function __construct() {
			// load PHPExcel
		require_once(t3lib_extMgm::extPath('format', 'Contrib/dompdf/dompdf_config.inc.php'));
		$this->pdfObject = new DOMPDF();
		$this->pdfObject->set_paper('A4', 'portrait');
		$this->pdfObject->set_base_path(PATH_site);
	}
	
	/**
	 * sets the data
	 */
	public function setData($data) {
		$this->pdfObject->load_html($data);
	}


	public function saveToOutput($filename) {
		$this->pdfObject->render();
		$this->pdfObject->stream($filename . '.pdf');
	}
	
	public function saveToFile($filename) {
		// @todo
	}

}