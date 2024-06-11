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

use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\Escaper;
use Gmi\Toolkit\Pdftk\Util\FileChecker;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * Wrapper for PDFtk.
 *
 * @internal Only the methods exposed by the interfaces should be accessed from outside.
 */
class PdftkWrapper implements WrapperInterface, BinaryPathAwareInterface
{
    use BinaryPathAwareTrait;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var FileChecker
     */
    private $fileChecker;

    /**
     * Constructor.
     *
     * @throws FileNotFoundException
     */
    public function __construct(string $pdftkBinary = null, ProcessFactory $processFactory = null)
    {
        $this->setBinary($pdftkBinary ?: $this->guessBinary(PHP_OS));
        $this->processFactory = $processFactory ?: new ProcessFactory();
        $this->escaper = new Escaper();
        $this->fileChecker = new FileChecker();
    }

    /**
     * Guesses the pdftk binary path based on the operating system.
     */
    public function guessBinary(string $operatingSystemString): string
    {
        if (strtoupper(substr($operatingSystemString, 0, 3)) === 'WIN') {
            $binary = 'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe';
        } else {
            $binary = '/usr/bin/pdftk';
        }

        return $binary;
    }

    /**
     * {@inheritDoc}
     */
    public function join(array $filePaths, string $outfile): void
    {
        $esc = $this->escaper;

        $filePathsEscaped = array_map(function (string $filePath) use ($esc) {
            return $esc->shellArg($filePath);
        }, $filePaths);

        $fileList = implode(' ', $filePathsEscaped);

        $commandLine = sprintf('%s %s cat output %s', $this->getBinary(), $fileList, $esc->shellArg($outfile));

        /**
         * @var Process
         */
        $process = $this->processFactory->createProcess($commandLine);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            throw new PdfException($e->getMessage(), 0, $e, $process->getErrorOutput(), $process->getOutput());
        }

        $process->getOutput();
    }

    /**
     * {@inheritDoc}
     */
    public function split(string $infile, array $mapping, string $outputFolder = null): void
    {
        $commandLines = $this->buildSplitCommandLines($infile, $mapping, $outputFolder);

        foreach ($commandLines as $commandLine) {
            $process = $this->processFactory->createProcess($commandLine);
            try {
                $process->mustRun();
            } catch (Exception $e) {
                throw new PdfException($e->getMessage(), 0, $e, $process->getErrorOutput(), $process->getOutput());
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function reorder(string $infile, array $order, string $outfile = null): void
    {
        $pageNumbers = implode(' ', $order);

        $temporaryOutFile = false;

        if ($outfile === null || $infile === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf');
        }

        $esc = $this->escaper;

        $commandLine = sprintf(
            '%s %s cat %s output %s',
            $this->getBinary(),
            $esc->shellArg($infile),
            $pageNumbers,
            $esc->shellArg($outfile)
        );

        /**
         * @var Process
         */
        $process = $this->processFactory->createProcess($commandLine);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            throw new PdfException(
                sprintf('Failed to reorder PDF "%s"! Error: %s', $infile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        if ($temporaryOutFile) {
            unlink($infile);
            rename($outfile, $infile);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function applyBookmarks(Bookmarks $bookmarks, string $infile, string $outfile = null): self
    {
        $this->updatePdfDataFromDump($infile, $this->buildBookmarksBlock($bookmarks), $outfile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importBookmarks(Bookmarks $bookmarks, string $infile): self
    {
        $dump = $this->getPdfDataDump($infile);
        $this->importBookmarksFromDump($bookmarks, $dump);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importPages(Pages $pages, string $infile): self
    {
        $dump = $this->getPdfDataDump($infile);
        $this->importPagesFromDump($pages, $dump);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function applyMetadata(Metadata $metadata, string $infile, string $outfile = null): self
    {
        $metadataBlock = $this->buildMetadataBlock($metadata);

        $this->updatePdfDataFromDump($infile, $metadataBlock, $outfile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importMetadata(Metadata $metadata, string $infile): self
    {
        $dump = $this->getPdfDataDump($infile);
        $this->importMetadataFromDump($metadata, $dump);

        return $this;
    }

    /**
     * Get data dump.
     *
     * @throws PdfException
     */
    public function getPdfDataDump(string $pdf): string
    {
        $this->fileChecker->checkPdfFileExists($pdf);

        $esc = $this->escaper;

        $tempfile = tempnam(sys_get_temp_dir(), 'pdf');
        $cmd = sprintf(
            '%s %s dump_data_utf8 output %s',
            $this->getBinary(),
            $esc->shellArg($pdf),
            $esc->shellArg($tempfile)
        );

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to read PDF data from "%s"! Error: %s', $pdf, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        $dump = file_get_contents($tempfile);
        unlink($tempfile);

        if (isset($exception)) {
            throw $exception;
        }

        return $dump;
    }

    /**
     * Update PDF data from dump.
     *
     * @param string $pdf     input file
     * @param string $data    dump data or filename of containing dump data
     * @param string $outfile output file (is input when null)
     *
     * @throws PdfException
     */
    public function updatePdfDataFromDump(string $pdf, string $data, string $outfile = null): void
    {
        $temporaryOutFile = false;

        $this->fileChecker->checkPdfFileExists($pdf);

        if ($outfile === null || $pdf === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf');
        }

        $tempfile = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tempfile, $data);

        $esc = $this->escaper;

        $cmd = sprintf(
            '%s %s update_info_utf8 %s output %s',
            $this->getBinary(),
            $esc->shellArg($pdf),
            $esc->shellArg($tempfile),
            $esc->shellArg($outfile)
        );

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to write PDF data to "%s"! Error: %s', $outfile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        unlink($tempfile);

        if ($temporaryOutFile && !isset($exception)) {
            unlink($pdf);
            rename($outfile, $pdf);
        }

        if (isset($exception)) {
            throw $exception;
        }
    }

    /**
     * Builds the pdftk command lines for splitting.
     *
     * @return string[]
     */
    private function buildSplitCommandLines(string $inputFile, array $mapping, string $outputFolder = null): array
    {
        $commandLines = [];
        $esc = $this->escaper;

        foreach ($mapping as $filename => $pages) {
            if ($outputFolder) {
                $target = sprintf('%s/%s', $outputFolder, $filename);
            } else {
                $target = $filename;
            }

            $commandLines[] = sprintf(
                '%s %s cat %s output %s',
                $this->getBinary(),
                $esc->shellArg($inputFile),
                implode(' ', $pages),
                $esc->shellArg($target)
            );
        }

        return $commandLines;
    }

    /**
     * Imports bookmarks from a pdftk dump.
     */
    private function importBookmarksFromDump(Bookmarks $bookmarks, string $dump): self
    {
        $matches = [];
        $regex = '/BookmarkBegin\nBookmarkTitle: (?<title>.+)\n' .
                 'BookmarkLevel: (?<level>[0-9]+)\nBookmarkPageNumber: (?<page>[0-9]+)/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        foreach ($matches as $bm) {
            $bookmark = new Bookmark();
            $bookmark
                ->setTitle($bm['title'])
                ->setPageNumber((int) $bm['page'])
                ->setLevel((int) $bm['level'])
            ;

            $bookmarks->add($bookmark);
        }

        return $this;
    }

    /**
     * Builds an Bookmark string for all bookmarks.
     */
    private function buildBookmarksBlock(Bookmarks $bookmarks): string
    {
        $result = '';

        foreach ($bookmarks->all() as $bookmark) {
            $result .= $this->buildBookmarkBlock($bookmark);
        }

        return $result;
    }

    /**
     * Builds an Bookmark string for a single bookmark.
     */
    private function buildBookmarkBlock(Bookmark $bookmark): string
    {
        return
            'BookmarkBegin' . PHP_EOL .
            'BookmarkTitle: ' . $bookmark->getTitle() . PHP_EOL .
            'BookmarkLevel: ' . $bookmark->getLevel() . PHP_EOL .
            'BookmarkPageNumber: ' . $bookmark->getPageNumber() . PHP_EOL;
    }

    /**
     * Imports page meta data from a pdftk dump.
     */
    public function importPagesFromDump(Pages $pages, string $dump): self
    {
        $matches = [];
        $regex = '/PageMediaBegin\nPageMediaNumber: (?<page>.+)\nPageMediaRotation: (?<rotation>[0-9]+)\n' .
                 'PageMediaRect: .*\n' .
                 'PageMediaDimensions: (?<dim>(([0-9]\,)?[0-9]+(\.[0-9]+)?) (([0-9]\,)?[0-9]+(\.[0-9]+)?))/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        foreach ($matches as $p) {
            $page = new Page();

            $dimensions = explode(' ', $p['dim']);

            $page
                ->setPageNumber((int) $p['page'])
                ->setRotation((int) $p['rotation'])
                ->setWidth((float) str_replace(',', '', $dimensions[0]))
                ->setHeight((float) str_replace(',', '', $dimensions[1]))
            ;

            $pages->add($page);
        }

        return $this;
    }

    /**
     * Imports PDF metadata from a pdftk dump.
     */
    public function importMetadataFromDump(Metadata $metadata, string $dump): self
    {
        $matches = [];
        $regex = '/InfoBegin??\r?\nInfoKey: (?<key>.*)??\r?\nInfoValue: (?<value>.*)??\r?\n/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        foreach ($matches as $meta) {
            $metadata->set($meta['key'], $meta['value']);
        }

        return $this;
    }

    /**
     * Builds an Metadata string for all metadata entries.
     */
    public function buildMetadataBlock(Metadata $metadata): string
    {
        $result = '';

        foreach ($metadata->all() as $key => $value) {
            $result .= 'InfoBegin' . PHP_EOL;
            $result .= 'InfoKey: ' . $key . PHP_EOL;
            $result .= 'InfoValue: ' . (string) $value . PHP_EOL;
        }

        return $result;
    }
}
