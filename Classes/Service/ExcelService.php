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
 * Class to create an excel and output it 
 *
 * @author	b:dreizehn GmbH <typo3@b13.de>
 * @package	TYPO3
 * @subpackage	Tx_Format
 */
class Tx_Format_Service_ExcelService {

	/**
	 * holds the PHPExcel object
	 * @type PHPExcel
	 */
	protected $excelObj;


	/**
	 * constructor, adds the excel export object
	 */
	public function __construct() {
			// load PHPExcel
		require_once(t3lib_extMgm::extPath('format', 'Contrib/PHPExcel/PHPExcel.php'));
		 
		$this->excelObj = new PHPExcel();
	}
	
	/**
	 * sets the data to an excel sheet
	 *
	 * @param array $data
	 * @param integer $sheetNo the sheet number (counting from 0)
	 * @param string $sheetName (if there is no sheet available, create one and name it like this)
	 */
	public function setData($data, $sheetNo = NULL, $sheetName = NULL) {
		if ($sheetNo !== NULL) {
			// check if the sheet exists
			if ($this->excelObj->getSheetCount() > $sheetNo) {
				$sheet = $this->excelObj->getSheet($sheetNo);
			}
			if (!$sheet) {
					// Create a new worksheet called "Sheet"
					// Attach the worksheet as the worksheet in the PHPExcel object
				$sheet = $this->excelObj->createSheet($sheetNo);
			}
		} else {
				// just fetches the current sheet number
			$sheet = $this->excelObj->getActiveSheet();
		}
		if ($sheetName) {
			$sheet->setTitle($sheetName);
		}

			// XLS settings
		$currentRow = 1;
			// render a row for each participant
		foreach ($data as $row) {

			// $currentColumnIndex = ord('A');
			$columnIndex = $this->getNextColumnIndex();	// returns "A"
			foreach ($row as $field) {
					// set the value for the cell
				$sheet->setCellValueExplicit($columnIndex . $currentRow, $field, PHPExcel_Cell_DataType::TYPE_STRING);
					// raise the column index from "B" to "C" or sth.
				$columnIndex = $this->getNextColumnIndex($columnIndex);
			}
			$currentRow++;
		}
		
			// calculate the column widhts
		$sheet->calculateColumnWidths();
	}
	
	/********************
	 * STYLING FUNCTIONS
	 ********************/

	/**
	 * make sure the columns width are adjusted
	 * not used anymore, now done via "calculateColumnWidths"
	 *
	 * @param $sheetNo to only do this on a certain sheet (optional)
	 * @param $columnIndex to only do this on a certain column on a sheet (optional)
	 *
	 */
	public function setColumnAutoWidth($sheetNo = NULL, $columnIndex = NULL) {
		try {
				// do this on a specific sheet
			if ($sheetNo !== NULL) {
				$sheet = $this->excelObj->getSheet($sheetNo);
				$sheets = array($sheet);
			} else {
				$sheets = $this->excelObj->getAllSheets();
			}
				// loop through all selected sheets
			foreach ($sheets as $sheet) {
				if ($columnIndex !== NULL) {
					// set the width on a specific column
					$sheet->getColumnDimension($columnIndex)->setAutoSize(true);
				} else {
					$highestColumn = $this->getNextColumnIndex($sheet->getHighestColumn());

					// loop through all columns
					$columnIndex = $this->getNextColumnIndex();
					do {
						$sheet->getColumnDimension($columnIndex)->setAutoSize(true);
						$columnIndex = $this->getNextColumnIndex($columnIndex);
					} while ($columnIndex !== $highestColumn);
				}
			}
		} catch (Exception $e) {
			
		}
	}
	
	/**
	 * simple function to color/mark the header row
	 *
	 * @param string $backgroundColor a hex code without the leading hash #
	 * @param string $fontColor a hex code without the leading hash #
	 * @param boolean $fontWeight whether the header should be bold
	 */
	public function markHeaderRow($backgroundColor = '000000', $fontColor = 'FFFFFF', $fontWeight = TRUE) {
		$borderStyles = array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb'=>'1006A3')
		);
		$headerStyles = array(
			'borders' => array(
				'bottom' => $borderStyles,
				'left'   => $borderStyles,
				'top'    => $borderStyles,
				'right'  => $borderStyles,
			),
			'fill' => array(
				'type'  => PHPExcel_Style_Fill::FILL_SOLID,
				'color' => array('rgb' => ($backgroundColor ? $backgroundColor : 'FFFFFF')),
			),
			'font' => array(
				'color' => array('rgb' => ($fontColor ? $fontColor : '000000')),
				'bold'  => $fontWeight,
			)
		);

			// select one or more sheets
		if ($sheetNo !== NULL) {
			$sheet = $this->excelObj->getSheet($sheetNo);
			$sheets = array($sheet);
		} else {
			$sheets = $this->excelObj->getAllSheets();
		}
			// loop through all selected sheets
		foreach ($sheets as $sheet) {
			$rowIndex = 1;
			// get the next highest column
			$highestColumn = $this->getNextColumnIndex($sheet->getHighestColumn());

			$columnIndex = $this->getNextColumnIndex();
			// loop through all columns
			do {
				$sheet->getStyle($columnIndex . $rowIndex)->applyFromArray($headerStyles);
				$columnIndex = $this->getNextColumnIndex($columnIndex);
			} while ($columnIndex !== $highestColumn);
		}
	}
	
	/********************
	 * OUTPUT FUNCTIONS
	 ********************/
	
	/**
	 * saves the XLS ands sends it to the browser
	 *
	 * @param string $filename
	 */
	public function saveToOutput($filename) {
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter = PHPExcel_IOFactory::createWriter($this->excelObj, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
	
	/**
	 * saves it to file
	 */
	public function saveToFile($filename) {
		// @todo
	}

	/**
	 * helper function to loop through the columns
	 *
	 * basically, this function returns "A", "B", "C", "D" ... "FV" depending on the value given
	 *
	 * @param string $currentColumnIndex e.g. "K"
	 * @return string the new index "L"
	 */
	protected function getNextColumnIndex($currentColumnIndex = NULL) {
		$columnIndexPrefix = '';
		$nextColumnIndex = 'A';
		if ($currentColumnIndex !== NULL) {
			if (strlen($currentColumnIndex) > 1) {
				$currentIndexPrefix = substr($currentColumnIndex, 0, -1);	// get the first characters, once we have more than 26 columns, we need to add "A", "B" etc
				$currentColumnIndex = substr($currentColumnIndex, -1);	// get the last character
			}
				// we are at Z, next one needs to be "AZ" or "BZ"
			if ($currentColumnIndex === 'Z') {
				if ($currentIndexPrefix) {
					$currentIndexPrefix = chr(ord($currentIndexPrefix)+1);	// count up the prefix and add the "A"
				} else {
					$currentIndexPrefix = 'A';
				}
				$nextColumnIndex = $currentIndexPrefix . 'A'; 
			} else {
					// just count from "O" to "P"
				$nextColumnIndex = $currentIndexPrefix . chr(ord($currentColumnIndex)+1);
			}			
		}
		return $nextColumnIndex;
	}

}