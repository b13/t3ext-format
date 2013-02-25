<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Benjamin Mack <typo3@b13.de>
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
 * Work Class "csv" that takes care of all the work with CSVs
 *
 * @author	Benjamin Mack <typo3@b13.de>
 * @package	TYPO3
 * @subpackage	Tx_Format
 */
class Tx_Format_Service_CsvService {

	protected $data = array();


	/*** 
	 * Part 1... Parsing
	 ***/
	protected static $delimiters = array(',', ';', "\t", ' ');
	protected static $qualifiers = array('"', '\'');

	public static function parseString($str, $delimiter = ';', $qualifier = '"', $lineFeed = "\n") {
		$recordsStack = array();
		$fieldsStack = array();
		$currentField = '';
		$isInQuote = FALSE;
		$skippedQuote = FALSE;
		$length = strlen($str);
		$useQualifier = (bool) strlen($qualifier);
		for ($i = 0; $i < $length; $i++) {
			// is it a new set or just a linebreak within the bounds of the qualifier?
			if ($str{$i} == $lineFeed) {
				if ($isInQuote) {
					$currentField .= $lineFeed;
				} else {
					$fieldsStack[] = $currentField;
					if (count($fieldsStack)) {
						$recordsStack[] = $fieldsStack;
					}
					$currentField = '';
					$fieldsStack = array();
				}
			// is it the end of a field or just within in the quote?
			} else if ($str{$i} == $delimiter) {
				if ($isInQuote) {
					$currentField .= $delimiter;
				} else {
					$fieldsStack[] = $currentField;
					$currentField = '';
				}
			} else if ($useQualifier && $str{$i} == $qualifier) {
				if ($isInQuote) {
					if ($skippedQuote) {
						$currentField .= $qualifier;
						$skippedQuote = FALSE;
					} else {
						$skippedQuote = TRUE;
						$isInQuote = FALSE;
					}
				} else {
					if ($skippedQuote) {
						$skippedQuote = FALSE;
					}
					$isInQuote = TRUE;
				}
			} else {
				$currentField .= $str{$i};
			}
		}
		if (strlen($currentField)) {
			$fieldsStack[] = $currentField;
		}
		if (count($fieldsStack)) {
			$recordsStack[] = $fieldsStack;
		}
		return $recordsStack;
	}


	/*** 
	 * Part 2... Creating
	 ***/

	public function addData($data) {
		$this->data[] = $data;
	}


	public function setData($data) {
		$this->data = (is_array($data) ? $data : array());
	}
	
	
	protected function prepareDataAsString($data, $delimiter = ';', $linedelimiter = NULL, $convertToLatin = TRUE) {
		$content = '';
		if ($linedelimiter === NULL) {
			$linedelimiter = chr(10);
		}
		// make a string out of the array
		foreach ($data as $row) {
			if (is_array($row)) {
				foreach ($row as &$field) {
					$field = $this->formatValue($field, $delimiter);
				}
				$content .= implode($delimiter, $row) . $linedelimiter;
			}
		}

		$content = trim($content);
		if ($convertToLatin) {
			$content = utf8_decode($content);
		}
		return $content;
	}

	/**
	 * sends the CSV data to the client, where $csvData is a single-level array
	 * 
	 * @param array $csvData the array with the data, will be separated with csv
	 */
	public function saveToOutput($filename = 'export') {

		$content = $this->prepareDataAsString($this->data);

		$filename .= '_' . date('Ymd') . '.csv';
		
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: text/csv; charset=utf-8');

		// it will be called like the file itself, can also be changed
		$agent = strtolower(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
		$dispo = (strpos($agent, 'win') !== false && strpos($agent, 'msie') !== false) ? '' : 'attachment; ';
		header('Content-Disposition: ' . $dispo . 'filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($content));

		// -- render the data --
		echo $content;
		exit;
	}
	
	/**
	 * creates a file for the CSV data
	 * 
	 * @param array $csvData the array with the data, will be separated with csv
	 */
	public function saveToFile($directoryName, $filename) {

		$content = $this->prepareDataAsString($this->data);

		$filename .= '_' . date('Ymd') . '.csv';
		
		// save the file to the file system as well
		$fullFile = rtrim($directoryName, '/') . '/' . $filename;
		t3lib_div::writeFile($fullFile, $csvData);
		return $fullFile;
	}

	/**
	 * formats a value to be compatible with the CSV output
	 * 
	 * @param mixed $value
	 * @return string
	 */
	protected function formatValue($value, $delimiter = ';') {
		$value = str_replace($delimiter, '\\' . $delimiter, $value);
		$value = str_replace('"', '""', $value);
		return '"' . $value . '"';
	}
}
