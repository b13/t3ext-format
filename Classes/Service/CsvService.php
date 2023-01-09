<?php

namespace B13\Format\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Work Class "csv" that takes care of all the work with CSVs
 *
 * @author    Benjamin Mack <typo3@b13.de>
 */
class CsvService
{
    /***
     * Part 1... Parsing
     ***/
    protected static $delimiters = [',', ';', "\t", ' '];
    protected static $qualifiers = ['"', '\''];
    protected $data = [];

    /**
     * @param string $str
     * @param string $delimiter
     * @param string $qualifier
     * @param string $lineFeed
     * @return array
     */
    public static function parseString($str, $delimiter = ';', $qualifier = '"', $lineFeed = "\n")
    {
        $recordsStack = [];
        $fieldsStack = [];
        $currentField = '';
        $isInQuote = false;
        $skippedQuote = false;
        $length = strlen($str);
        $useQualifier = (bool)strlen($qualifier);
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
                    $fieldsStack = [];
                    $skippedQuote = false;
                }
                // is it the end of a field or just within in the quote?
            } elseif ($str{$i} == $delimiter) {
                if ($isInQuote) {
                    $currentField .= $delimiter;
                } else {
                    $fieldsStack[] = $currentField;
                    $currentField = '';
                    $skippedQuote = false;
                }
            } elseif ($useQualifier && $str{$i} == $qualifier) {
                if ($isInQuote) {
                    if ($skippedQuote) {
                        $currentField .= $qualifier;
                        $skippedQuote = false;
                    } else {
                        $skippedQuote = true;
                        $isInQuote = false;
                    }
                } else {
                    if ($skippedQuote) {
                        $skippedQuote = false;
                        $currentField .= $qualifier;
                    }
                    $isInQuote = true;
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

    /**
     * @param $data
     */
    public function addData($data)
    {
        $this->data[] = $data;
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = (is_array($data) ? $data : []);
    }

    /**
     * sends the CSV data to the client, where $csvData is a single-level array
     *
     * @param string $filename
     * @param string $delimiter
     * @param string $linedelimiter
     * @param bool $convertToLatin
     */
    public function saveToOutput($filename = 'export', $delimiter = ';', $linedelimiter = null, $convertToLatin = true)
    {
        $content = $this->prepareDataAsString($this->data, $delimiter, $linedelimiter, $convertToLatin);

        $filename .= '_' . date('Ymd') . '.csv';

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);

        // define the charset depending on the $convertToLatin parameter
        if ($convertToLatin) {
            header('Content-Type: text/csv; charset=iso-8859-15');
        } else {
            header('Content-Type: text/csv; charset=utf-8');
        }

        // it will be called like the file itself, can also be changed
        $agent = strtolower(GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
        $dispo = (strpos($agent, 'win') !== false && strpos($agent, 'msie') !== false) ? '' : 'attachment; ';
        header('Content-Disposition: ' . $dispo . 'filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($content));

        // -- render the data --
        echo $content;
        exit;
    }

    /**
     * @param $data
     * @param string $delimiter
     * @param null $linedelimiter
     * @param bool $convertToLatin
     * @return bool|string
     */
    protected function prepareDataAsString($data, $delimiter = ';', $linedelimiter = null, $convertToLatin = true)
    {
        $content = '';
        if ($linedelimiter === null) {
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
     * formats a value to be compatible with the CSV output
     *
     * @param mixed $value
     * @param string $delimiter
     * @return string
     */
    protected function formatValue($value, $delimiter = ';')
    {
        $value = str_replace($delimiter, '\\' . $delimiter, $value);
        $value = str_replace('"', '""', $value);
        return '"' . $value . '"';
    }

    /**
     * creates a file for the CSV data
     *
     * @param string $directoryName
     * @param string $filename
     * @return string
     */
    public function saveToFile($directoryName, $filename)
    {
        $content = $this->prepareDataAsString($this->data);

        $filename .= '_' . date('Ymd') . '.csv';

        // save the file to the file system as well
        $fullFile = rtrim($directoryName, '/') . '/' . $filename;
        GeneralUtility::writeFile($fullFile, $content);
        return $fullFile;
    }
}
