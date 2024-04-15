<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

/**
 * Represents a single PDF page.
 */
class Page
{
    /**
     * Page number in the PDF.
     *
     * @var int
     */
    private $pageNumber;

    /**
     * Height of the page in PostScript points (see pdftk dump_data).
     *
     * @var float
     */
    private $height;

    /**
     * Width of the page in PostScript points (see pdftk dump_data).
     *
     * @var float
     */
    private $width;

    /**
     * Rotation of the page in degrees (0 - no rotation).
     *
     * @var int
     */
    private $rotation;

    /**
     * Sets the page number.
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
     * Returns the page number.
     *
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Sets the page height.
     *
     * @param float $height
     *
     * @return self
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Returns the page height.
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Returns the page height in millimeters, rounded to the millimeter.
     *
     * @return int
     */
    public function getHeightMm()
    {
        return (int) round($this->convertPointToMm($this->height), 0);
    }

    /**
     * Sets the page width.
     *
     * @param float $width
     *
     * @return self
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Returns the page width.
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the page width in millimeters, rounded to the millimeter.
     *
     * @return int
     */
    public function getWidthMm()
    {
        return (int) round($this->convertPointToMm($this->width), 0);
    }

    /**
     * Sets the page rotation.
     *
     * @param int $rotation
     *
     * @return self
     */
    public function setRotation($rotation)
    {
        $this->rotation = $rotation;

        return $this;
    }

    /**
     * Returns the page rotation.
     *
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * Converts a measure of PostScript points to millimeters.
     *
     * @param float $point
     *
     * @return float
     */
    private function convertPointToMm($point)
    {
        return $point / 72 * 25.4;
    }
}
