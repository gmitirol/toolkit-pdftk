<?php
/**
 * PDF library wrapper
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

/**
 * Wrapper interface for PDF manipulation libraries.
 *
 * To be implemented by PDF library wrappers.
 *
 * All methods should throw a NotImplementedException if they do not support a requested operation.
 *
 * They should throw a PdfException (or appropriate subclass) if the operation fails.
 * This allows to provide consistent error messages regardless of the implementation.
 *
 * Implementations may provide a fluent interface, which makes method chaining more convenient.
 */
interface WrapperInterface
{
    // ## Process creation methods - All wrapper implementations must perform proper shell escaping!

    /**
     * Joins the PDFs provided in $filePaths to an $output file.
     *
     * @return void|self
     */
    public function join(array $filePaths, string $outfile);

    /**
     * Splits the $inputFile PDF based on a mapping array into an output folder.
     *
     * @return void|self
     */
    public function split(string $infile, array $mapping, string $outputFolder = null);

    /**
     * Reorders PDF pages based on a $order array into an $outfile.
     *
     * @return void|self
     */
    public function reorder(string $infile, array $order, string $outfile = null);

    // ## Bookmark methods

    /**
     * Applies the provided Bookmarks instance to an PDF file $infile using an $outfile as target.
     * Existing bookmarks are overwritten.
     *
     * If $outfile is null, the $infile is overwritten after successful application instead.
     *
     * @return void|self
     */
    public function applyBookmarks(Bookmarks $bookmarks, string $infile, string $outfile = null);

    /**
     * Imports the bookmarks of the PDF $infile to the provided Bookmarks instance.
     *
     * @return void|self
     */
    public function importBookmarks(Bookmarks $bookmarks, string $infile);

    // ## Page methods

    /**
     * Imports the page info of the PDF $infile to the provided Pages instance.
     *
     * @return void|self
     */
    public function importPages(Pages $pages, string $infile);

    // ## Metadata methods

    /**
     * Applies the provided Metadata instance to an PDF file $infile using an $outfile as target.
     *
     * If $outfile is null, the $infile is overwritten after successful application instead.
     *
     * @return void|self
     */
    public function applyMetadata(Metadata $metadata, string $infile, string $outfile = null);

    /**
     * Imports the metadata info of the PDF $infile to the provided Metadata instance.
     *
     * @return void|self
     */
    public function importMetadata(Metadata $metadata, string $infile);
}
