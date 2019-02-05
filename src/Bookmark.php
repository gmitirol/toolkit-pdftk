<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2019 Institute of Legal Medicine, Medical University of Innsbruck
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
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the bookmark title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the bookmark page.
     *
     * @param int $pageNumber
     *
     * @return self
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * Returns the bookmark page.
     *
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Sets the bookmark level.
     *
     * @param int $level
     *
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Returns the bookmark level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
}
