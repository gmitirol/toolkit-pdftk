<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Gmi\Toolkit\Pdftk\Exception\PdfException;

class Pdftk
{
    /**
     * @var Bookmarks
     */
    private $bookmarks;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var Pages
     */
    private $pages;

    /**
     * @var Joiner
     */
    private $joiner;

    /**
     * @var Splitter
     */
    private $splitter;

    /**
     * @var PageOrder
     */
    private $pageOrder;

    /**
     * @var PdftkWrapper
     */
    private $wrapper;

    /**
     * Constructor.
     *
     * @param array        $options
     * @param PdftkWrapper $wrapper
     *
     * @throws PdfException
     */
    public function __construct($options = [], PdftkWrapper $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();

        if (isset($options['binary'])) {
            $this->wrapper->setBinary($options['binary']);
        }

        $this->bookmarks = new Bookmarks($wrapper);
        $this->metadata = new Metadata($wrapper);
        $this->pages = new Pages($wrapper);
        $this->joiner = new Joiner($wrapper);
        $this->splitter = new Splitter($wrapper);
        $this->pageOrder = new PageOrder($wrapper);
    }

    /**
     * Returns the bookmarks object.
     *
     * @return Bookmarks
     */
    public function getBookmarks()
    {
        return $this->bookmarks;
    }

    /**
     * Alias for getBookmarks().
     *
     * @return Bookmarks
     */
    public function bookmarks()
    {
        return $this->getBookmarks();
    }

    /**
     * Returns the metadata object.
     *
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Alias for getMetadata().
     *
     * @return Metadata
     */
    public function metadata()
    {
        return $this->getMetadata();
    }

    /**
     * Returns the pages object.
     *
     * @return Pages
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Alias for getPages().
     *
     * @return Pages
     */
    public function pages()
    {
        return $this->getPages();
    }

    /**
     * Returns the PDF joiner.
     *
     * @return Joiner
     */
    public function getJoiner()
    {
        return $this->joiner;
    }

    /**
     * Alias for getJoiner().
     *
     * @return Joiner
     */
    public function joiner()
    {
        return $this->getJoiner();
    }

    /**
     * Returns the PDF splitter.
     *
     * @return Splitter
     */
    public function getSplitter()
    {
        return $this->splitter;
    }

    /**
     * Alias for getSplitter().
     *
     * @return Splitter
     */
    public function splitter()
    {
        return $this->getSplitter();
    }

    /**
     * Returns the PDF page order changer.
     *
     * @return PageOrder
     */
    public function getPageOrder()
    {
        return $this->pageOrder;
    }

    /**
     * Alias for getPageOrder().
     *
     * @return PageOrder
     */
    public function order()
    {
        return $this->getPageOrder();
    }

    /**
     * Apply bookmarks and metadata to PDF file.
     *
     * @param string $infile
     * @param string $outfile
     *
     * @return self
     */
    public function apply($infile, $outfile = null)
    {
        $this->bookmarks->apply($infile, $outfile);
        $this->metadata->apply($outfile);

        return $this;
    }

    /**
     * Imports bookmarks, metadata and page information from a PDF file.
     *
     * @param string $infile
     *
     * @return self
     */
    public function import($infile)
    {
        $dump = $this->wrapper->getPdfDataDump($infile);

        $this->pages->clear();
        $this->bookmarks->clear();
        $this->metadata->clear();

        $this->pages->importFromDump($dump);
        $this->bookmarks->setMaxpage(count($this->pages->all()));
        $this->bookmarks->importFromDump($dump);
        $this->metadata->importFromDump($dump);

        return $this;
    }
}
