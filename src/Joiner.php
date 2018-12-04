<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @author Nikola Vrlazic <nikola.vrlazic@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\JoinException;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Util\FileSorter;

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
     * @var FileSorter
     */
    private $sorter;

    /**
     * @var PdftkWrapper
     */
    private $wrapper;

    /**
     * Constructor.
     *
     * @param PdftkWrapper $wrapper
     * @param Finder       $finder
     * @param FileSorter   $sorter
     */
    public function __construct(PdftkWrapper $wrapper = null, Finder $finder = null, FileSorter $sorter = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
        $this->finder = $finder ?: new Finder();
        $this->sorter = $sorter ?: new FileSorter();
    }

    /**
     * Joins all PDFs in a folder matching a pattern, naturally sorted, to one file.
     *
     * @param string $inputFolder
     * @param string $pattern
     * @param string $output
     *
     * @throws FileNotFoundException if no matching input files are found in the input folder
     * @throws JoinException if the PDF join fails
     */
    public function joinByPattern($inputFolder, $pattern, $output)
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
        $files = $this->sortFiles($foundFiles);

        return $this->join($files, $output);
    }

    /**
     * Joins a list of PDFs (represented by SplFileInfo instances) to one file.
     *
     * @param SplFileInfo[] $files
     * @param string        $output
     *
     * @throws JoinException if the PDF join fails
     */
    public function join($files, $output)
    {
        $filePaths = [];
        foreach ($files as $file) {
            $filePaths[] = escapeshellarg($file->getPathname());
        }

        $fileList = implode(' ', $filePaths);

        $commandLine = sprintf('%s %s cat output %s', $this->wrapper->getBinary(), $fileList, escapeshellarg($output));

        /**
         * @var Process
         */
        $process = $this->wrapper->createProcess($commandLine);
        $process->setTimeout(300);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new JoinException(
                sprintf('Failed to join PDF "%s"!', $output),
                0,
                null,
                $process->getErrorOutput()
            );
        }

        $process->getOutput();
    }

    /**
     * Finds all files inside the folder with specific pattern.
     *
     * @param string $folder
     * @param string $pattern
     *
     * @return SplFileInfo[]
     */
    private function getFiles($folder, $pattern)
    {
        $files = $this->finder->files()->name($pattern)->in($folder);

        $results = [];

        foreach ($files as $file) {
            $results[] = $file;
        }

        return $results;
    }

    /**
     * Sort found files by natural sorting.
     *
     * @param SplFileInfo[] $files
     *
     * @return SplFileInfo[]
     */
    private function sortFiles($files)
    {
        return $this->sorter->sortNaturally($files);
    }
}
