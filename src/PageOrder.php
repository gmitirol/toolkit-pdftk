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

use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Exception\ReorderException;

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
     * Reorders the PDFs using the provided page order.
     *
     * @param string $file    Path to the PDF file.
     * @param int[]  $order   New page order, must match the number of pages in the PDF e.g. [3,1,2].
     * @param string $outfile Output file path, if null the input file is overwritten after successful reordering.
     *
     * @throws ReorderException if the PDF reordering fails
     */
    public function reorder(string $file, array $order, string $outfile = null): void
    {
        try {
            $this->checkOrderHasCorrectPageNumbers($order);
            $this->checkOrderPagesEqualNumberOfPdfPages($order, $file);

            $this->wrapper->reorder($file, $order, $outfile);
        } catch (PdfException $e) {
            throw new ReorderException(
                sprintf('Failed to reorder PDF "%s"! Error: %s', $file, $e->getMessage()),
                0,
                $e,
                $e->getPdfError(),
                $e->getPdfOutput()
            );
        }
    }

    /**
     * Checks that the page numbers of the order are correct (all numbers sequentially 1 to the maximum contained).
     *
     * @param int[] $order
     *
     * @throws ReorderException
     */
    private function checkOrderHasCorrectPageNumbers(array $order): void
    {
        if (0 === count($order)) {
            throw new ReorderException('Empty page order!');
        }

        $expected = range(1, max($order));
        if (array_diff($order, $expected) !== [] || array_diff($expected, $order) !== []) {
            throw new ReorderException('Invalid page order!');
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
    private function checkOrderPagesEqualNumberOfPdfPages(array $order, string $file): void
    {
        $pages = new Pages();
        $this->wrapper->importPages($pages, $file);

        if (count($pages->all()) !== count($order)) {
            throw new ReorderException('Invalid number of pages!');
        }
    }
}
