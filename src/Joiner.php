<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @author Nikola Vrlazic <nikola.vrlazic@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Symfony\Component\Finder\Finder;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\JoinException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Sorter\FileSorterInterface;
use Gmi\Toolkit\Sorter\NaturalFileSorter;

use SplFileInfo;

/**
 * Searches folder, and naturally sorts PDF files. After sorting the PDFs, all PDFs will be combined to one file.
 *
 * <pre>
 * $joiner = new PdfJoiner();
 * $joiner->joinByPattern('/var/www/test/foo', '/^FILE123456_.*\.pdf$', '/tmp/my_file.pdf');
 * </pre>
 */
class Joiner
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var FileSorterInterface
     */
    private $sorter;

    /**
     * @var WrapperInterface
     */
    private $wrapper;

    /**
     * Constructor.
     */
    public function __construct(
        WrapperInterface $wrapper = null,
        Finder $finder = null,
        FileSorterInterface $sorter = null
    ) {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
        $this->finder = $finder ?: new Finder();
        $this->sorter = $sorter ?: new NaturalFileSorter();
    }

    /**
     * Joins all PDFs in a folder matching a pattern, naturally sorted, to one file.
     *
     * @throws FileNotFoundException if no matching input files are found in the input folder
     * @throws JoinException if the PDF join fails
     */
    public function joinByPattern(string $inputFolder, string $pattern, string $output)
    {
        // array of files which match the pattern
        $foundFiles = $this->getFiles($inputFolder, $pattern);

        if (count($foundFiles) === 0) {
            throw new FileNotFoundException(
                sprintf(
                    'No files in "%s" are matching to the pattern "%s".',
                    $inputFolder,
                    $pattern
                )
            );
        }

        // array of sorted files
        $files = $this->sorter->sort($foundFiles);

        return $this->join($files, $output);
    }

    /**
     * Joins a list of PDFs (represented by SplFileInfo instances) to one file.
     *
     * @param SplFileInfo[] $files Either array or ArrayObject
     *
     * @throws JoinException if the PDF join fails
     */
    public function join($files, string $output): void
    {
        $filePaths = [];
        foreach ($files as $file) {
            $filePaths[] = $file->getPathname();
        }

        try {
            $this->wrapper->join($filePaths, $output);
        } catch (PdfException $e) {
            throw new JoinException(
                sprintf('Failed to join PDF "%s"! Error: %s', $output, $e->getMessage()),
                0,
                $e,
                $e->getPdfError(),
                $e->getPdfOutput()
            );
        }
    }

    /**
     * Finds all files inside the folder with specific pattern.
     *
     * @return SplFileInfo[]
     */
    private function getFiles(string $folder, string $pattern): array
    {
        $files = $this->finder->files()->name($pattern)->in($folder);

        $results = [];

        foreach ($files as $file) {
            $results[] = $file;
        }

        return $results;
    }
}
