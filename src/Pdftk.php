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
     * @var WrapperInterface
     */
    private $wrapper;

    /**
     * Constructor.
     *
     * @throws PdfException
     */
    public function __construct(array $options = [], WrapperInterface $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();

        if (isset($options['binary']) && $this->wrapper instanceof BinaryPathAwareInterface) {
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
     */
    public function getBookmarks(): Bookmarks
    {
        return $this->bookmarks;
    }

    /**
     * Alias for getBookmarks().
     */
    public function bookmarks(): Bookmarks
    {
        return $this->getBookmarks();
    }

    /**
     * Returns the metadata object.
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Alias for getMetadata().
     */
    public function metadata(): Metadata
    {
        return $this->getMetadata();
    }

    /**
     * Returns the pages object.
     */
    public function getPages(): Pages
    {
        return $this->pages;
    }

    /**
     * Alias for getPages().
     */
    public function pages(): Pages
    {
        return $this->getPages();
    }

    /**
     * Returns the PDF joiner.
     */
    public function getJoiner(): Joiner
    {
        return $this->joiner;
    }

    /**
     * Alias for getJoiner().
     */
    public function joiner(): Joiner
    {
        return $this->getJoiner();
    }

    /**
     * Returns the PDF splitter.
     */
    public function getSplitter(): Splitter
    {
        return $this->splitter;
    }

    /**
     * Alias for getSplitter().
     */
    public function splitter(): Splitter
    {
        return $this->getSplitter();
    }

    /**
     * Returns the PDF page order changer.
     */
    public function getPageOrder(): PageOrder
    {
        return $this->pageOrder;
    }

    /**
     * Alias for getPageOrder().
     */
    public function order(): PageOrder
    {
        return $this->getPageOrder();
    }

    /**
     * Apply bookmarks and metadata to PDF file.
     */
    public function apply(string $infile, string $outfile = null): self
    {
        $this->bookmarks->apply($infile, $outfile);
        $this->metadata->apply($outfile ?? $infile);

        return $this;
    }

    /**
     * Imports bookmarks, metadata and page information from a PDF file.
     */
    public function import(string $infile): self
    {
        $this->pages->clear();
        $this->bookmarks->clear();
        $this->metadata->clear();

        $this->pages->import($infile);
        $this->bookmarks->setMaxpage(count($this->pages->all()));
        $this->bookmarks->import($infile);
        $this->metadata->import($infile);

        return $this;
    }
}
