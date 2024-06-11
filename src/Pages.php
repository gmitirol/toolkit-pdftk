<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

/**
 * Read PDF page information.
 */
class Pages
{
    /**
     * Pages.
     *
     * @var Page[]
     */
    private $pages = [];

    /**
     * @var WrapperInterface
     */
    private $wrapper;

    /**
     * Constructor.
     */
    public function __construct(WrapperInterface $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
    }

    /**
     * Add page to the pages array.
     */
    public function add(Page $page): self
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Returns all pages.
     *
     * @return Page[]
     */
    public function all(): array
    {
        return $this->pages;
    }

    /**
     * Remove all pages.
     */
    public function clear(): self
    {
        $this->pages = [];

        return $this;
    }

    /**
     * Imports pages from a PDF file.
     *
     * @throws PdfException
     */
    public function import(string $infile): self
    {
        $this->wrapper->importPages($this, $infile);

        return $this;
    }
}
