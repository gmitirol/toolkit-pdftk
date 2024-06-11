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

/**
 * Represents a single PDF bookmark.
 *
 * @psalm-suppress MissingConstructor
 */
class Bookmark
{
    /**
     * Bookmark title, as displayed in the PDF viewer's bookmark list.
     *
     * @var string
     */
    private $title;

    /**
     * Page where the bookmark is written.
     *
     * @var int
     */
    private $pageNumber;

    /**
     * Bookmark level.
     *
     * @var int
     */
    private $level = 1;

    /**
     * Sets the bookmark title.
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the bookmark title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the bookmark page.
     */
    public function setPageNumber(int $pageNumber): self
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * Returns the bookmark page.
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * Sets the bookmark level.
     */
    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Returns the bookmark level.
     */
    public function getLevel(): int
    {
        return $this->level;
    }
}
