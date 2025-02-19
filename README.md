# TYPO3 Extension format

> [!CAUTION]
> This TYPO3 Extension will not be maintained by b13 any further starting January 2025. So, you're on your own using this extension.
> 
> *Why?*
> 
> This extension was initially created back when TYPO3 v4 was hip and cool. However, we've experienced in the past years, that we've utilized composer-based packages directly for solving these problems, when they are needed. We don't need "one-fits-it-all" solution.
>
> If you need a CSV file (or output) generated by PHP, copy over the file into your project, or code the 5 lines on your own.
>
> If you need Excel support, utilize the composer package `phpoffice/phpspreadsheet` directly.
>
> And if you need PDF creation support - we recommend also to look into solutions you need - some people need wkhtmltopdf, some need mpdf, and others a different requirement.

----

Utility Extension for putting data in different formats like

* CSV
* PDF
* Excel

## Installation

Simply install the extension with Composer or the Extension Manager. Include the TypoScript if you want to use the PDF generation.

## Usage

### CSV

Use the public API of the `CsvService` to generate a CSV file or output the CSV string directly.

### Excel

There is no wrapper functionality to help with the creation of excel files. However the library
`phpoffice/phpspreadsheet` is required as a composer dependency and can therefore be used out of
the box in composer based installations.

### PDF

The PDF functionality relies on wkhtmltopdf which must be available on the server.
There are several ways to provide the binary. Please refer to the wkhtmltopdf documentation. 

The extension provides a PdfService. here is an example usage:

```
$pdfService = GeneralUtility::makeInstance(PdfService::class);
$pdfService->setContent($myHtml);
$absolutepathToFile = $pdfService->saveToFile('myPdf');
```

This will create a file `myPdf.pdf` with the contents of `$myHtml` in a directory that can be configured in TypoScript.
The whole TypoScript configuration (`constants.typoscript`):

```
plugin.tx_format {
  settings {
    pdf {
      // Path to the wkhtmltopdf binary
      binaryFilePath = /usr/local/bin/wkhtmltopdf
      // Path were the PDFs are stored
      tempDirectoryPath = /tmp/
      // Default file name of the generated PDF
      tempFileName =
      // If set the fileName will be appended with X characters of the md5 hash of the content
      md5Length = 0
      // Use print media-type instead of screen
      printMediaTypeAttribute = 1
      // Generates lower quality pdf/ps. Useful to shrink the result document space
      lowQualityAttribute = 0
      // Adds a html footer
      footerHtmlAttribute =
      // URL to render (instead of content)
      url =
      // Minimum font size
      minimumFontSize = 15
      // Set the page left margin (default 10mm)
      marginLeft = 10
      // Set the page right margin (default 10mm)
      marginRight = 10
      // Set the page top margin
      marginTop = 10
      // Set the page bottom margin
      marginBottom = 10
      // The default page size of the rendered document is A4, but using this
      // --page-size optionthis can be changed to almost anything else, such as: A3,
      // Letter and Legal.  For a full list of supported pages sizes please see
      // <http://qt-project.org/doc/qt-4.8/qprinter.html#PaperSize-enum>.
      pageSize =
      // Wait some milliseconds for javascript finish (default 200)
      javaScriptDelay = 200
      // Set orientation to Landscape or Portrait (default Portrait)
      orientation = Portrait
      // Whether a generated PDF should be kept at the end of the process. By default it is deleted.
      persistPDF = 0
      // For all supported attributes refer to https://wkhtmltopdf.org/usage/wkhtmltopdf.txt
      additionalAttributes =
    }
  }
}
```

Every setting can be overwritten during runtime:

```
$pdfService = GeneralUtility::makeInstance(PdfService::class);
$pdfService->setSettings(
  [
    'orientation' => 'Landscape,
    'marginLeft' => 25,
    'tempDirectoryPath' => PATH_site . 'something/public/'
  ]
);
```
### Sharing our expertise

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
