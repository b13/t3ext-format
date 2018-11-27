<?php
namespace B13\Format\Service;

use B13\Format\Pdf\PdfSettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class to create a pdf and output it
 *
 * @author	b:dreizehn GmbH <typo3@b13.de>
 * @package	TYPO3
 * @subpackage	tx_format
 */
class PdfService
{
    /**
     * holds the PDF object
     */
    protected $pdfObject;

    /**
     * @var PdfSettings
     */
    protected $settings = null;

    /**
     *
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(
        ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
        'format'
        );
        $this->setSettings($settings['pdf'] ?? []);
    }

    /**
     * Set the settings
     *
     * @param array $settings
     * @return $this
     */
    public function setSettings(array $settings)
    {
        if (!isset($this->settings)) {
            $this->settings = GeneralUtility::makeInstance(PdfSettings::class);
        }
        $this->settings->setProperties($settings);
        return $this;
    }

    /**
     * sets the PDF content
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->settings->setContent($content);
    }

    /**
     * @param $fileName
     * @return void
     */
    public function saveToOutput(string $fileName)
    {
        $absoluteFilePath = $this->saveToFile($fileName);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0', true);
        header('Cache-Control :private');
        header('Content-Type: application/pdf', true);

        $agent = strtolower(GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
        $dispo = (strpos($agent, 'win') !== false && strpos($agent, 'msie') !== false) ? '' : 'attachment; ';

        header('Content-Transfer-Encodin :binary', true);
        header('Content-Disposition: ' . $dispo . 'filename="' . $fileName . '"', true);
        header('Content-Length: ' . filesize($absoluteFilePath), true);

        ob_clean();
        readfile($absoluteFilePath);
        exit;
    }

    /**
     * @param string $filename
     * @return string
     * @throws \B13\Format\Exception
     */
    public function saveToFile(string $filename = ''): string
    {
        $this->createTempDirectory();

        if (!empty($filename)) {
            $this->settings->setTempFileName($filename);
        }

        $contentParameter = $this->getContentParameter();
        $command = $this->settings->getAbsoluteBinaryFilePath() .
            $this->settings->getPrintMediaTypeAttribute() .
            $this->settings->getLowQualityAttribute() .
            $this->settings->getFooterHtmlAttribute() .
            $this->settings->getMinimumFontSizeAttribute() .
            $this->settings->getJavaScriptDelayAttribute() .
            $this->settings->getOrientationAttribute() .
            $this->settings->getMarginAttributes() .
            $this->settings->getPageSizeAttribute() .
            ' ' . $contentParameter . ' ' . $this->settings->getAbsoluteTempFilePath();

        exec($command, $res, $ret);
        if ($ret !== 0) {
            throw new \B13\Format\Exception('cannot execute ' . $command, 1508825188);
        }
        return $this->settings->getAbsoluteTempFilePath();
    }

    /**
     * Creates temp directory
     *
     * @return $this
     */
    protected function createTempDirectory()
    {
        $tempDirectoryPath = $this->settings->getAbsoluteTempDirectoryPath();
        if (!is_dir($tempDirectoryPath)) {
            GeneralUtility::mkdir_deep($tempDirectoryPath);
        }
        return $this;
    }

    /**
     * @return string
     * @throws \B13\Format\Exception
     */
    protected function getContentParameter()
    {
        if ($this->settings->hasContent()) {
            $htmlTempFile = $this->settings->getAbsoluteHtmlTempFilePath();
            if (!file_put_contents($htmlTempFile, $this->settings->getContent())) {
                throw new \B13\Format\Exception('Cannot write html content to ' . $htmlTempFile, 1508825187);
            }
            GeneralUtility::fixPermissions($htmlTempFile);
            $contentParameter = $htmlTempFile;
        } elseif (!empty($this->settings->getUrl())) {
            $contentParameter = $this->settings->hasContent() ? '-' : $this->settings->getUrl();
        } else {
            throw new \B13\Format\Exception('Invalid settings: need content or url', 1508825186);
        }
        return $contentParameter;
    }
}
