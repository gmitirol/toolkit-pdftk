<?php
/**
 * bookmarks helper for pdfcpu wrapper
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\Escaper;
use Gmi\Toolkit\Pdftk\Util\FileChecker;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * Bookmarks helper for pdfcpu wrapper.
 *
 * This class encapsulates the complexity of pdfcpu bookmark handling to reduce complexity of the PdfcpuWrapper.
 *
 * @internal
 */
class PdfcpuWrapperBookmarksHelper
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
    public function __construct(string $pdftkBinaryPath, ProcessFactory $processFactory)
    {
        $this->binaryPath = $pdftkBinaryPath;
        $this->processFactory = $processFactory;
        $this->escaper = new Escaper();
        $this->fileChecker = new FileChecker();
    }

    /**
     * @see WrapperInterface::applyBookmarks()
     */
    public function applyBookmarks(Bookmarks $bookmarks, string $infile, string $outfile = null): void
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
    }

    /**
     * @see WrapperInterface::importBookmarks()
     */
    public function importBookmarks(Bookmarks $bookmarks, string $infile): void
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
    }

    /**
     * Imports bookmarks from a pdfcpu bookmark JSON file.
     */
    private function importBookmarksFromJson(Bookmarks $bookmarks, string $json): void
    {
        $raw = json_decode($json, true);
        $bookmarksArray = $raw['bookmarks'] ?? [];

        $this->parseBookmarksTree($bookmarks, $bookmarksArray);
    }

    /**
     * Recursively traverse the bookmarks array and add the bookmarks appropriately.
     */
    private function parseBookmarksTree(Bookmarks $bookmarks, array $arr, int $level = 1): void
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
    private function buildBookmarksTree(array $bookmarksArray, int $parentId = null): array
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
