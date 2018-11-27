<?php
namespace B13\Format\Pdf;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Lars Peipmann <lp@lightwerk.com>, Lightwerk GmbH
 *  (c) 2016 Daniel Goerz <dlg@lightwerk.com>, Lightwerk GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * PDF Settings
 *
 * @package TYPO3
 * @subpackage format
 */
class PdfSettings
{

    const ORIENTATION_PORTRAIT = 'Portrait';
    const ORIENTATION_LANDSCAPE = 'Landscape';

    /**
     * Path to the wkhtmltopdf binary
     *
     * @var string
     */
    protected $binaryFilePath = '/usr/bin/wkhtmltopdf';

    /**
     * @var string
     */
    protected $tempDirectoryPath = '/tmp/';

    /**
     * @var string
     */
    protected $tempFileName;

    /**
     * @var integer
     */
    protected $cacheSeconds = 3600;

    /**
     * If set the fileName will be appended with X characters of the md5 hash of the content
     *
     * @var integer
     */
    protected $md5Length = 0;

    /**
     * @var boolean
     */
    protected $printMediaTypeAttribute = false;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $footerHtmlAttribute;

    /**
     * @var boolean
     */
    protected $lowQualityAttribute;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var integer
     */
    protected $minimumFontSize = 0;

    /**
     * Either Portrait or Landscape
     *
     * @var string
     */
    protected $orientation = self::ORIENTATION_PORTRAIT;

    /**
     * @var int|null
     */
    protected $marginRight = null;

    /**
     * @var int|null
     */
    protected $marginLeft = null;

    /**
     * @var int|null
     */
    protected $marginTop = null;

    /**
     * @var int|null
     */
    protected $marginBottom = null;

    /**
     * @var int
     */
    protected $javaScriptDelay = 200;

    /**
     * @var string
     * default of wkhtmltopdf is A4
     */
    protected $pageSize = null;

    /**
     * Whether a generated PDF should be kept at the end of the process. By default it is deleted
     *
     * @var bool
     */
    protected $persistPDF = false;

    /**
     * @var string
     */
    protected $fullTempFileName = '';

    /**
     * Sets properties
     *
     * @param $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        foreach ($properties as $name => $value) {
            if (isset($value) && strlen($value) > 0) {
                $this->setProperty($name, $value);
            }
        }
        return $this;
    }

    /**
     * Set property
     *
     * @param $name
     * @param $value
     * @throws \Exception
     * @return $this
     */
    public function setProperty($name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            throw new \Exception('Property ' . $name . ' does not exist in ' . get_class($this));
        }
        return $this;
    }

    /**
     * Returns the absolute binary file path
     *
     * @return string
     */
    public function getAbsoluteBinaryFilePath()
    {
        return $this->getAbsoluteFilePath($this->getBinaryFilePath());
    }

    /**
     * Returns absolute file path
     *
     * @param string $filePath
     * @return string
     */
    protected function getAbsoluteFilePath($filePath)
    {
        return $filePath{0} === '/' ? $filePath : \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($filePath);
    }

    /**
     * Returns binary file path
     *
     * @return string
     */
    public function getBinaryFilePath()
    {
        return $this->binaryFilePath;
    }

    /**
     * Set binary file path
     *
     * @param string $binaryFilePath
     * @return $this
     */
    public function setBinaryFilePath($binaryFilePath)
    {
        $this->binaryFilePath = $binaryFilePath;
        return $this;
    }

    /**
     * Returns cache seconds
     *
     * @return integer
     */
    public function getCacheSeconds()
    {
        return $this->cacheSeconds;
    }

    /**
     * Sets cache seconds
     *
     * @param integer $cacheSeconds
     * @return $this
     */
    public function setCacheSeconds($cacheSeconds)
    {
        $this->cacheSeconds = $cacheSeconds;
        return $this;
    }

    /**
     * Returns the public temp file url
     *
     * @return string
     */
    public function getPublicTempFileUrl()
    {
        return '/' . substr($this->getAbsoluteTempFilePath(), strlen(PATH_site));
    }

    /**
     * Returns absolute temp file path
     *
     * @return string
     */
    public function getAbsoluteTempFilePath()
    {
        return $this->getAbsoluteTempDirectoryPath() . $this->getFullTempFileName();
    }

    /**
     * Returns absolute temp directory path
     *
     * @return string
     */
    public function getAbsoluteTempDirectoryPath()
    {
        return $this->getAbsoluteFilePath($this->getTempDirectoryPath());
    }

    /**
     * Returns temp directory path
     *
     * @return string
     */
    public function getTempDirectoryPath()
    {
        return $this->tempDirectoryPath;
    }

    /**
     * Sets temp directory path
     *
     * @param string $tempDirectoryPath
     * @return $this
     */
    public function setTempDirectoryPath($tempDirectoryPath)
    {
        $this->tempDirectoryPath = $tempDirectoryPath;
        return $this;
    }

    /**
     * Returns the full file name of the temp file
     *
     * @return string
     */
    public function getFullTempFileName()
    {
        if (!empty($this->fullTempFileName)) {
            return $this->fullTempFileName;
        }
        $tempFileName = $this->getTempFileName();
        if ($this->getMd5Length() > 0 && $this->hasContent()) {
            $tempFileName .= '_' . substr(md5(serialize($this->getContent())), 0, $this->getMd5Length());
        }
        $tempFileName .= '.pdf';
        $this->fullTempFileName = $tempFileName;
        return $this->fullTempFileName;
    }

    /**
     * @return string
     */
    public function getAbsoluteHtmlTempFilePath()
    {
        $pdfFullTempFilePath = $this->getAbsoluteTempFilePath();
        return str_replace('.pdf', '.html', $pdfFullTempFilePath);
    }

    /**
     * Returns the name of the temp file
     *
     * @return string
     */
    public function getTempFileName()
    {
        $tempFileName = preg_replace(
            array('/ä/', '/ö/', '/ü/', '/Ä/', '/Ö/', '/Ü/', '/ß/', '/ /', '/[^0-9A-Za-z\_\.\-]/'),
            array('ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '_', ''),
            trim($this->tempFileName)
        );
        return $tempFileName;
    }

    /**
     * Sets name of the temp file
     *
     * @param string $tempFileName
     * @return $this
     */
    public function setTempFileName($tempFileName)
    {
        $this->tempFileName = $tempFileName;
        return $this;
    }

    /**
     * Returns Md5 Length
     *
     * @return integer
     */
    public function getMd5Length()
    {
        return $this->md5Length;
    }

    /**
     * Sets Md5 Length
     *
     * @param integer $md5Length
     * @return $this
     */
    public function setMd5Length($md5Length)
    {
        $this->md5Length = (int)$md5Length;
        return $this;
    }

    /**
     * Does this object has a value in content?
     *
     * @return boolean
     */
    public function hasContent()
    {
        return !empty($this->content);
    }

    /**
     * Returns the content
     *
     * @return string
     */
    public function getContent()
    {
        return (string)$this->content;
    }

    /**
     * Sets the content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Returns Url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets Url
     *
     * @param string $url
     * @return $this;
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Does this object has a value in url?
     *
     * @return boolean
     */
    public function hasUrl()
    {
        return !empty($this->url);
    }

    /**
     * @return string
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * @param string $orientation
     * @return $this
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrientationAttribute()
    {
        // Portrait is default, so we only set Landscape if configured correctly
        if ($this->orientation === self::ORIENTATION_LANDSCAPE) {
            return  ' --orientation ' . self::ORIENTATION_LANDSCAPE;
        }
        return '';
    }

    /**
     * Returns media type attribute
     *
     * @return string
     */
    public function getPrintMediaTypeAttribute()
    {
        if (!empty($this->printMediaTypeAttribute)) {
            return ' --print-media-type';
        }
        return '';
    }

    /**
     * Enables/disables media type attribute
     *
     * @param boolean $boolean
     * @return $this
     */
    public function setPrintMediaTypeAttribute($boolean = true)
    {
        $this->printMediaTypeAttribute = !empty($boolean);
        return $this;
    }

    /**
     * Returns the footer html attribute
     *
     * @return string
     */
    public function getFooterHtmlAttribute()
    {
        if (!empty($this->footerHtml)) {
            return ' --footer-html ' . escapeshellarg($this->footerHtml);
        }
        return '';
    }

    /**
     * Sets footer html attribute
     *
     * @param string $footerHtmlAttribute
     * @return void
     */
    public function setFooterHtmlAttribute($footerHtmlAttribute)
    {
        $this->footerHtmlAttribute = $footerHtmlAttribute;
    }

    /**
     * Returns low quality attribute
     *
     * @return string
     */
    public function getLowQualityAttribute()
    {
        if ($this->lowQualityAttribute) {
            return ' --lowquality';
        }
        return '';
    }

    /**
     * Enables/disables low quality
     *
     * @param boolean $boolean
     * @return $this
     */
    public function setLowQualityAttribute($boolean = true)
    {
        $this->lowQualityAttribute = !empty($boolean);
        return $this;
    }

    /**
     * Returns MinimumFontSizeAttribute
     *
     * @return string
     */
    public function getMinimumFontSizeAttribute()
    {
        if ($this->hasMinimumFontSize()) {
            return ' --minimum-font-size ' . $this->getMinimumFontSize();
        }
        return '';
    }

    /**
     * Has MinimumFontSize
     *
     * @return bool
     */
    public function hasMinimumFontSize()
    {
        return !empty($this->minimumFontSize) && $this->minimumFontSize > 0;
    }

    /**
     * Returns MinimumFontSize
     *
     * @return int
     */
    public function getMinimumFontSize()
    {
        return $this->minimumFontSize;
    }

    /**
     * Sets MinimumFontSize
     *
     * @param int $minimumFontSize
     * @return $this
     */
    public function setMinimumFontSize($minimumFontSize)
    {
        $this->minimumFontSize = intval($minimumFontSize);
        return $this;
    }

    /**
     * @return int
     */
    public function getJavaScriptDelay()
    {
        return $this->javaScriptDelay;
    }

    /**
     * @param int $javaScriptDelay
     * @return $this
     */
    public function setJavaScriptDelay($javaScriptDelay)
    {
        $this->javaScriptDelay = $javaScriptDelay;
        return $this;
    }

    /**
     * @return string
     */
    public function getMarginAttributes()
    {
        $attributes = '';
        if ($this->hasMarginRight()) {
            $attributes .= ' --margin-right ' . $this->getMarginRight();
        }
        if ($this->hasMarginLeft()) {
            $attributes .= ' --margin-left ' . $this->getMarginLeft();
        }
        if ($this->hasMarginTop()) {
            $attributes .= ' --margin-top ' . $this->getMarginTop();
        }
        if ($this->hasMarginBottom()) {
            $attributes .= ' --margin-bottom ' . $this->getMarginBottom();
        }
        return $attributes;
    }

    /**
     * @return bool
     */
    public function hasMarginRight()
    {
        return !empty($this->marginRight) || $this->marginRight === 0 || $this->marginRight === '0';
    }

    /**
     * @return int
     */
    public function getMarginRight()
    {
        return $this->convertToCorrectMarginSyntax($this->marginRight);
    }

    /**
     * @param int $marginRight
     * @return $this
     */
    public function setMarginRight($marginRight)
    {
        $this->marginRight = $marginRight;
        return $this;
    }

    /**
     * @param mixed $margin
     * @return string
     */
    protected function convertToCorrectMarginSyntax($margin)
    {
        if ($margin === 0 || $margin === '0') {
            return '0';
        }
        $margin = (string)$margin;
        if ($margin === '') {
            return '';
        }
        if (substr($margin, -2) === 'mm') {
            return $margin;
        }
        return $margin . 'mm';
    }

    /**
     * @return bool
     */
    public function hasMarginLeft()
    {
        return !empty($this->marginLeft) || $this->marginLeft === 0 || $this->marginLeft === '0';
    }

    /**
     * @return int
     */
    public function getMarginLeft()
    {
        return $this->convertToCorrectMarginSyntax($this->marginLeft);
    }

    /**
     * @param int $marginLeft
     * @return $this
     */
    public function setMarginLeft($marginLeft)
    {
        $this->marginLeft = $marginLeft;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMarginTop()
    {
        return !empty($this->marginTop) || $this->marginTop === 0 || $this->marginTop === '0';
    }

    /**
     * @return int
     */
    public function getMarginTop()
    {
        return $this->convertToCorrectMarginSyntax($this->marginTop);
    }

    /**
     * @param int $marginTop
     * @return $this
     */
    public function setMarginTop($marginTop)
    {
        $this->marginTop = $marginTop;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasMarginBottom()
    {
        return !empty($this->marginBottom) || $this->marginBottom === 0 || $this->marginBottom === '0';
    }

    /**
     * @return int
     */
    public function getMarginBottom()
    {
        return $this->convertToCorrectMarginSyntax($this->marginBottom);
    }

    /**
     * @param int $marginBottom
     * @return $this
     */
    public function setMarginBottom($marginBottom)
    {
        $this->marginBottom = $marginBottom;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageSizeAttribute()
    {
        if ($this->pageSize !== null) {
            return ' --page-size ' . $this->pageSize;
        }
        return '';
    }

    /**
     * @param string $pageSize
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @param bool $persistPDF
     * @return $this
     */
    public function setPersistPDF($persistPDF)
    {
        $this->persistPDF = $persistPDF;
        return $this;
    }

    /**
     * 200 is default for wkhtmltopdf. So we don't add the argument
     * if it is set to 200
     *
     * --javascript-delay <msec> Wait some milliseconds for javascript finish (default 200)
     *
     * @return string
     */
    public function getJavaScriptDelayAttribute()
    {
        if ((int)$this->javaScriptDelay !== 200) {
            return ' --javascript-delay ' . (int)$this->javaScriptDelay;
        }
        return '';
    }

    public function __destruct()
    {
        if (file_exists($this->getAbsoluteHtmlTempFilePath())) {
            @unlink($this->getAbsoluteHtmlTempFilePath());
        }

        if ((bool)$this->persistPDF === false && file_exists($this->getAbsoluteTempFilePath())) {
            @unlink($this->getAbsoluteTempFilePath());
        }
    }
}
