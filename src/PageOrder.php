<?php
/**
 * Page order manipulation class.
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\ReorderException;

use Exception;

/**
 * Searches folder, and naturally sorts PDF files. After sorting the PDFs, all PDFs will be combined to one file.
 *
 * <pre>
 * $orderChanger->reorderPages('/path/to/file.pdf', [4, 1, 3, 2], '/path/to/output.pdf');
 * </pre>
 */
class PageOrder
{
    /**
     * @var PdftkWrapper
     */
    private $wrapper;

    /**
     * Constructor.
     *
     * @param PdftkWrapper        $wrapper
     */
    public function __construct(PdftkWrapper $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
    }

    /**
     * Reorders the PDFs using the provided page order.
     *
     * @param string $file    Path to the PDF file.
     * @param int[]  $order   New page order, must match the number of pages in the PDF e.g. [3,1,2].
     * @param string $outfile Output file path, if null the input file is overwritten after successful reordering.
     *
     * @throws JoinException if the PDF join fails
     */
    public function reorder($file, $order, $outfile = null)
    {
        $this->checkOrderPagesEqualNumberOfPdfPages($order, $file);
        $this->checkOrderHasCorrectPageNumbers($order);

        $pageNumbers = implode(' ', $order);

        $temporaryOutFile = false;

        if ($outfile === null || $file === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf');
        }

        // check that the passed order has all pages specified, from 1 to the maximum passed
        
        $binary = $this->wrapper->getBinary();
        $commandLine = sprintf(
            '%s %s cat %s output %s',
            $binary,
            escapeshellarg($file),
            $pageNumbers,
            escapeshellarg($outfile)
        );

        /**
         * @var Process
         */
        $process = $this->wrapper->createProcess($commandLine);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            throw new ReorderException(
                sprintf('Failed to reorder PDF "%s"! Error: %s', $file, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        if ($temporaryOutFile) {
            unlink($file);
            rename($outfile, $file);
        }
    }

    /**
     * Checks that the number of pages matches between the provided order and the PDF.
     *
     * @param int[]  $order
     * @param string $file
     *
     * @throws ReorderException
     */
    private function checkOrderPagesEqualNumberOfPdfPages($order, $file)
    {
        $dump = $this->wrapper->getPdfDataDump($file);
        $matches = [];
        $regex = '/PageMediaBegin\n/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        if (count($matches) !== count($order)) {
            throw new ReorderException('Invalid number of pages!');
        }
    }

    /**
     * Checks that the page numbers of the order are correct (all numbers sequentially 1 to the maximum contained).
     *
     * @param int[] $order
     *
     * @throws ReorderException
     */
    private function checkOrderHasCorrectPageNumbers($order)
    {
        $expected = range(1, max($order));
        if (array_diff($order, $expected) !== [] || array_diff($expected, $order) !== []) {
            throw new ReorderException('Invalid page order!');
        }
    }
}
