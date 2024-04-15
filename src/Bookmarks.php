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

use Gmi\Toolkit\Pdftk\Exception\PdfException;

/**
 * Apply bookmarks to PDF.
 */
class Bookmarks
{
    /**
     * Bookmarks.
     *
     * @var Bookmark[]
     */
    private $bookmarks = [];

    /**
     * Maximum page number.
     *
     * @var int
     */
    private $maxpage = -1;

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
     * Add bookmark to page.
     */
    public function add(Bookmark $bookmark): self
    {
        if (0 === $bookmark->getPageNumber()) {
            return $this;
        }

        if ($bookmark->getLevel() < 1 || $bookmark->getLevel() > 99) {
            throw new PdfException(sprintf('Invalid bookmark level: %s', $bookmark->getLevel()));
        }

        if (!is_int($bookmark->getPageNumber()) || $bookmark->getPageNumber() < 1) {
            throw new PdfException(sprintf('Invalid page number: %s', $bookmark->getPageNumber()));
        } elseif ($this->maxpage > 0 && $bookmark->getPageNumber() > $this->maxpage) {
            throw new PdfException('Page number out of range!');
        }

        $this->bookmarks[] = $bookmark;

        return $this;
    }

    /**
     * Returns all bookmarks.
     *
     * @return Bookmark[]
     */
    public function all(): array
    {
        return $this->bookmarks;
    }

    /**
     * Deletes a bookmark.
     */
    public function remove(Bookmark $bookmark): self
    {
        foreach ($this->bookmarks as $key => $currentBookmark) {
            // weak comparison - value objects with equal values are considered equal
            if ($currentBookmark == $bookmark) {
                unset($this->bookmarks[$key]);
            }
        }

        $this->resetArrayIndizes();

        return $this;
    }

    /**
     * Delete bookmark from page.
     */
    public function removeByPageNumber(int $pageNumber): self
    {
        foreach ($this->bookmarks as $key => $currentBookmark) {
            if ($currentBookmark->getPageNumber() === $pageNumber) {
                unset($this->bookmarks[$key]);
            }
        }

        $this->resetArrayIndizes();

        return $this;
    }

    /**
     * Remove all bookmarks.
     */
    public function clear(): self
    {
        $this->bookmarks = [];

        return $this;
    }

    /**
     * Apply bookmarks to PDF file.
     */
    public function apply(string $infile, string $outfile = null): self
    {
        $this->wrapper->updatePdfDataFromDump($infile, $this->buildBookmarksBlock(), $outfile);

        return $this;
    }

    /**
     * Imports bookmarks from a PDF file.
     */
    public function import(string $infile): self
    {
        $dump = $this->wrapper->getPdfDataDump($infile);
        $this->importFromDump($dump);

        return $this;
    }

    /**
     * Imports bookmarks from a pdftk dump.
     */
    public function importFromDump(string $dump): self
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

            $this->add($bookmark);
        }

        return $this;
    }

    /**
     * Sets the maximum page number of the PDF to validate page numbers.
     *
     * @internal
     */
    public function setMaxpage(int $maxpage): self
    {
        $this->maxpage = $maxpage;

        return $this;
    }

    /**
     * Builds an Bookmark string for all bookmarks.
     */
    private function buildBookmarksBlock(): string
    {
        $result = '';

        foreach ($this->bookmarks as $bookmark) {
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
     * Resets the bookmark array indizes (e.g. after removal of a bookmark).
     */
    private function resetArrayIndizes(): void
    {
        $this->bookmarks = array_values($this->bookmarks);
    }
}
