PHP Toolkit - PDFtk
===================

This library provides an object-oriented, simple interface for the most important PDFtk features.

The current build status and code analysis can be found here:
  * [Travis CI](https://travis-ci.org/gmitirol/toolkit-pdftk)
  * [Scrutinizer CI](https://scrutinizer-ci.com/g/gmitirol/toolkit-pdftk/)

Requirements
------------
* PHP 5.6.0 or higher
* mbstring extension
* pdftk

Installation
------------
The recommended way to install toolkit-pdftk is via composer.
```json
"require": {
    "gmi/toolkit-pdftk": "2.2.*"
}
```

Usage examples
--------------
```php
use Gmi\Toolkit\Pdftk\Bookmark;
use Gmi\Toolkit\Pdftk\Pdftk;

$source = '/path/to/source.pdf';
$target = '/path/to/target.pdf';

$pdftk = new Pdftk();
// import a source PDF (metadata, page information, bookmarks)
$pdftk->import($source);

// create an additional bookmark
$exampleBookmark = new Bookmark();
$exampleBookmark
    ->setPageNumber(1)
    ->setLevel(2)
    ->setTitle('Section 3')
;

// add the bookmark to the PDF
$pdftk->bookmarks()->add($exampleBookmark);
// set metadata entry for the PDF
$pdftk->metadata()->set('Author', 'Jane Doe');

// apply bookmarks and metadata to the source PDF using a specified target PDF
$pdftk->apply($source, $target);
```

Tests
-----
The test suite can be run with `vendor/bin/phpunit tests`.
