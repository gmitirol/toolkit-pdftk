<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2019 Institute of Legal Medicine, Medical University of Innsbruck
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
     * @var PdftkWrapper
     */
    private $wrapper;

    /**
     * Constructor.
     *
     * @param PdftkWrapper $wrapper
     */
    public function __construct(PdftkWrapper $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
    }

    /**
     * Returns all pages.
     *
     * @return Page[]
     */
    public function all()
    {
        return $this->pages;
    }

    /**
     * Remove all pages.
     *
     * @return self
     */
    public function clear()
    {
        $this->pages = [];

        return $this;
    }

    /**
     * Imports pages from a PDF file.
     *
     * @param string $infile
     *
     * @return self
     */
    public function import($infile)
    {
        $dump = $this->wrapper->getPdfDataDump($infile);
        $this->importFromDump($dump);

        return $this;
    }

    /**
     * Imports page meta data from a pdftk dump.
     *
     * @param string $dump
     *
     * @return $this
     */
    public function importFromDump($dump)
    {
        $matches = [];
        $regex = '/PageMediaBegin\nPageMediaNumber: (?<page>.+)\nPageMediaRotation: (?<rotation>[0-9]+)\n' .
                 'PageMediaRect: .*\n' .
                 'PageMediaDimensions: (?<dim>(([0-9]\,)?[0-9]+(\.[0-9]+)?) (([0-9]\,)?[0-9]+(\.[0-9]+)?))/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        $this->pages = [];
        foreach ($matches as $p) {
            $page = new Page();

            $dimensions = explode(' ', $p['dim']);

            $page
                ->setPageNumber((int) $p['page'])
                ->setRotation((int) $p['rotation'])
                ->setWidth((float) str_replace(',', '', $dimensions[0]))
                ->setHeight((float) str_replace(',', '', $dimensions[1]))
            ;

            $this->pages[] = $page;
        }

        return $this;
    }
}
