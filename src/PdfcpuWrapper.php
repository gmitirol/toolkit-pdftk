<?php
/**
 * pdfcpu wrapper
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

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\Escaper;
use Gmi\Toolkit\Pdftk\Util\FileChecker;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * Wrapper for pdfcpu.
 *
 * @internal Only the methods exposed by the interfaces should be accessed from outside.
 */
class PdfcpuWrapper implements WrapperInterface, BinaryPathAwareInterface
{
    use BinaryPathAwareTrait;

    private const SUPPORTED_METADATA_ATTRIBUTES = [
        'Title', 'Keywords', 'Subject', 'Author', 'Creator', 'Producer', 'CreationDate', 'ModificationDate',
    ];

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
     * Guesses the pdfcpu binary path based on the operating system.
     */
    public function guessBinary(string $operatingSystemString): string
    {
        if (strtoupper(substr($operatingSystemString, 0, 3)) === 'WIN') {
            $binary = 'C:\\Program Files\\pdfcpu\\pdfcpu.exe';
        } else {
            $binary = '/usr/bin/pdfcpu';
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

        $commandLine = sprintf('%s merge %s %s', $this->getBinary(), $esc->shellArg($outfile), $fileList);

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
        $esc = $this->escaper;

        foreach ($mapping as $filename => $pages) {
            if ($outputFolder) {
                $target = sprintf('%s/%s', $outputFolder, $filename);
            } else {
                $target = $filename;
            }

            $commandLine = sprintf(
                '%s collect -pages %s %s %s',
                $this->getBinary(),
                implode(',', $pages),
                $esc->shellArg($infile),
                $esc->shellArg($target)
            );

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
        $temporaryOutFile = false;

        if ($outfile === null || $infile === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        }

        $esc = $this->escaper;

        $commandLine = sprintf(
            '%s collect -pages %s %s %s',
            $this->getBinary(),
            implode(',', $order),
            $esc->shellArg($infile),
            $esc->shellArg($outfile)
        );

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
        $temporaryOutFile = false;

        $this->fileChecker->checkPdfFileExists($infile);
        $bookmarksJson = $this->exportBookmarksToJson($bookmarks);
        $tempfile = tempnam(sys_get_temp_dir(), 'bookmarks') . '.json';
        file_put_contents($tempfile, $bookmarksJson);

        if ($outfile === null || $infile === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        }

        $cmd = sprintf(
            '%s bookmarks import -replace %s %s %s',
            $this->getBinary(),
            $this->escaper->shellArg($infile),
            $this->escaper->shellArg($tempfile),
            $this->escaper->shellArg($outfile)
        );

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to write PDF bookmarks to "%s"! Error: %s', $outfile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        unlink($tempfile);

        if ($temporaryOutFile && !isset($exception)) {
            unlink($infile);
            rename($outfile, $infile);
        }

        if (isset($exception)) {
            throw $exception;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importBookmarks(Bookmarks $bookmarks, string $infile): self
    {
        $tempBookmarksFile = tempnam(sys_get_temp_dir(), 'bookmarks') . '.json';

        $this->fileChecker->checkPdfFileExists($infile);

        $cmd = sprintf(
            '%s bookmarks export %s %s',
            $this->getBinary(),
            $this->escaper->shellArg($infile),
            $this->escaper->shellArg($tempBookmarksFile)
        );

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to read bookmarks data from "%s"! Error: %s', $infile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        if (isset($exception) && false === strpos($process->getErrorOutput(), 'no outlines available')) {
            @unlink($tempBookmarksFile);
            throw $exception;
        }

        $this->importBookmarksFromJson($bookmarks, @file_get_contents($tempBookmarksFile) ?: '');

        @unlink($tempBookmarksFile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importPages(Pages $pages, string $infile): self
    {
        $this->fileChecker->checkPdfFileExists($infile);

        $cmd = sprintf('%s info -pages 1- -j %s', $this->getBinary(), $this->escaper->shellArg($infile));

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to read pages data from "%s"! Error: %s', $infile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );

            throw $exception;
        }

        /**
         * Remove invalid JSON (useless line with the page numbers at the beginning)
         * @todo Remove when pdfcpu does not emit the extra pages line before JSON anymore
         */
        $outputCleaned = preg_replace('/^pages: (\d,?)+$/mu', '', $process->getOutput());
        $infoRaw = json_decode($outputCleaned, true);

        $pageBoundaries = $infoRaw['infos'][0]['pageBoundaries'];

        // the page numbers in the JSON are strings, not numbers and sorted as strings, ensure natural sort
        ksort($pageBoundaries, SORT_NATURAL);

        foreach ($pageBoundaries as $pageNumber => $pageInfo) {
            $page = new Page();

            $page
                ->setPageNumber((int) $pageNumber)
                ->setRotation((int) $pageInfo['rot'])
                ->setWidth((float) $pageInfo['mediaBox']['rect']['ur']['x'])
                ->setHeight((float) $pageInfo['mediaBox']['rect']['ur']['y'])
            ;

            $pages->add($page);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function applyMetadata(Metadata $metadata, string $infile, string $outfile = null): self
    {
        $temporaryOutFile = false;

        $this->fileChecker->checkPdfFileExists($infile);

        $properties = [];
        foreach ($metadata->all() as $key => $value) {
            $properties[] = sprintf('%s=%s', $key, $this->escaper->shellArg($value));
        }

        $propArgs = implode(' ', $properties);

        if ($outfile === null || $infile === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        }

        copy($infile, $outfile);

        $cmd = sprintf('%s properties add %s %s', $this->getBinary(), $this->escaper->shellArg($outfile), $propArgs);
        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to write PDF metadata to "%s"! Error: %s', $outfile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        if ($temporaryOutFile && !isset($exception)) {
            unlink($infile);
            rename($outfile, $infile);
        }

        if (isset($exception)) {
            throw $exception;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importMetadata(Metadata $metadata, string $infile): self
    {
        $cmd = sprintf('%s info -j %s', $this->getBinary(), $this->escaper->shellArg($infile));

        $process = $this->processFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            throw new PdfException(
                sprintf('Failed to read metadata data from "%s"! Error: %s', $infile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        $raw = json_decode($process->getOutput(), true);
        $metadataArray = $raw['infos'][0];

        foreach (self::SUPPORTED_METADATA_ATTRIBUTES as $attribute) {
            $attributeNormalized = lcfirst($attribute);

            if ($attributeNormalized === 'keywords' && isset($metadataArray['keywords'])) {
                $metadataArray['keywords'] = implode(', ', $metadataArray['keywords']);
            }

            if (isset($metadataArray[$attributeNormalized]) && '' !== trim($metadataArray[$attributeNormalized])) {
                $metadata->set($attribute, $metadataArray[$attributeNormalized]);
            }
        }

        return $this;
    }

    /**
     * Imports bookmarks from a pdfcpu bookmark JSON file.
     */
    private function importBookmarksFromJson(Bookmarks $bookmarks, string $json): self
    {
        $raw = json_decode($json, true);
        $bookmarksArray = $raw['bookmarks'] ?? [];

        $this->parseBookmarksTree($bookmarks, $bookmarksArray);

        return $this;
    }

    /**
     * Recursively traverse the bookmarks array and add the bookmarks appropriately.
     */
    private function parseBookmarksTree(Bookmarks $bookmarks, array $arr, int $level = 1)
    {
        foreach ($arr as $current) {
            $bookmark = new Bookmark();

            $bookmark
                ->setTitle($current['title'])
                ->setPageNumber($current['page'])
                ->setLevel($level)
            ;

            $bookmarks->add($bookmark);

            if (isset($current['kids'])) {
                $this->parseBookmarksTree($bookmarks, $current['kids'], $level + 1);
            }
        }
    }

    /**
     * Exports bookmarks to a pdfcpu bookmark JSON file.
     */
    private function exportBookmarksToJson(Bookmarks $bookmarks): string
    {
        $bookmarksRecursiveArray = $this->buildBookmarksTree($this->buildBookmarksArrayForTree($bookmarks));

        return json_encode(['bookmarks' => $bookmarksRecursiveArray], JSON_PRETTY_PRINT);
    }

    /**
     * Recursively build the JSON tree based on the normalized bookmarks array.
     */
    private function buildBookmarksTree(array $bookmarksArray, $parentId = null): array
    {
        $result = [];

        foreach ($bookmarksArray as $bookmarkItem) {
            if ($bookmarkItem['__parent'] === $parentId) {
                $children = $this->buildBookmarksTree($bookmarksArray, $bookmarkItem['__id']);
                if ($children) {
                    $bookmarkItem['kids'] = $children;
                }

                foreach ($bookmarkItem as $key => $value) {
                    if (strpos($key, "__") === 0) {
                        unset($bookmarkItem[$key]);
                    }
                }

                $result[] = $bookmarkItem;
            }
        }

        return $result;
    }

    /**
     * Builds an array with additional entries prefixed with "__" for level, id and parent id.
     */
    private function buildBookmarksArrayForTree(Bookmarks $bookmarks): array
    {
        $bookmarksArray = [];

        $b = $bookmarks->all();
        $bookmarksCount = count($b);

        $indexParent = null;

        for ($i = 0; $i < $bookmarksCount; $i++) {
            $bookmark = $b[$i];
            $prevBookmark = $b[$i - 1] ?? null;

            // bookmark has a higher level (is deeper down) than the previous one
            if ($prevBookmark && $prevBookmark->getLevel() < $bookmark->getLevel()) {
                $indexParent = $i - 1;
            // bookmark has a lower level (is higher up) than the previous one
            } elseif ($prevBookmark && $prevBookmark->getLevel() > $bookmark->getLevel()) {
                $indexParent = $this->getLastParentId($bookmarksArray, $bookmark->getLevel()) ?? null;
            }

            $bookmarksArray[] = [
                'title' => $bookmark->getTitle(),
                'page' => $bookmark->getPageNumber(),
                '__level' => $bookmark->getLevel(),
                '__id' => $i,
                '__parent' => $indexParent,
            ];
        }

        return $bookmarksArray;
    }

    /**
     * Returns the id of the last bookmark with a lower level than the provided current level.
     */
    private function getLastParentId(array $bookmarksArray, int $currentLevel): ?int
    {
        for ($j = count($bookmarksArray) - 1; $j >= 0; $j--) {
            if ($bookmarksArray[$j]['__level'] < $currentLevel) {
                return $bookmarksArray[$j]['__id'];
            }
        }

        return null;
    }
}
