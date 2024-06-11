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

use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Exception\SplitException;

/**
 * Splits PDF files.
 */
class Splitter
{
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
     * Splits a PDF according to the provided filename => pages mapping.
     *
     * @param string $inputFile    Filename of the input file which should be split
     * @param array  $mapping      Mapping of output filename to page numbers
     * @param string $outputFolder Folder where the output files should be stored (without trailing slash).
     *                             If the output folder is null, the filenames of the mapping
     *                             (which can contain a path) are used as they are.
     *
     * @throws SplitException if the PDF split fails
     */
    public function split(string $inputFile, array $mapping, string $outputFolder = null): void
    {
        try {
            $this->wrapper->split($inputFile, $mapping, $outputFolder);
        } catch (PdfException $e) {
            throw new SplitException(
                sprintf('Failed to split PDF "%s"! Error: %s', $inputFile, $e->getMessage()),
                0,
                $e,
                $e->getPdfError(),
                $e->getPdfOutput()
            );
        }
    }
}
