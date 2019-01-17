<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Gmi\Toolkit\Pdftk\Exception\SplitException;

use Exception;

/**
 * Splits PDF files.
 */
class Splitter
{
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
    public function split($inputFile, $mapping, $outputFolder = null)
    {
        $commandLines = $this->buildCommandLines($inputFile, $mapping, $outputFolder);

        foreach ($commandLines as $commandLine) {
            $process = $this->wrapper->createProcess($commandLine);
            $process->setTimeout(300);


            try {
                $process->mustRun();
            } catch (Exception $e) {
                throw new SplitException(
                    sprintf('Failed to split PDF "%s"! Error: %s', $inputFile, $e->getMessage()),
                    0,
                    $e,
                    $process->getErrorOutput(),
                    $process->getOutput()
                );
            }
        }
    }

    /**
     * Builds the pdftk command lines for splitting.
     *
     * @param string $inputFile
     * @param array  $mapping
     * @param string $outputFolder
     *
     * @return string[]
     */
    private function buildCommandLines($inputFile, $mapping, $outputFolder = null)
    {
        $commandLines = [];

        foreach ($mapping as $filename => $pages) {
            if ($outputFolder) {
                $target = sprintf('%s/%s', $outputFolder, $filename);
            } else {
                $target = $filename;
            }

            $commandLines[] = sprintf(
                '%s %s cat %s output %s',
                $this->wrapper->getBinary(),
                escapeshellarg($inputFile),
                implode(' ', $pages),
                escapeshellarg($target)
            );
        }

        return $commandLines;
    }
}
