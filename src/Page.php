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
     */
    public function setPageNumber(int $pageNumber): self
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /**
     * Returns the page number.
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * Sets the page height.
     */
    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Returns the page height.
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * Returns the page height in millimeters, rounded to the millimeter.
     */
    public function getHeightMm(): int
    {
        return (int) round($this->convertPointToMm($this->height), 0);
    }

    /**
     * Sets the page width.
     */
    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Returns the page width.
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Returns the page width in millimeters, rounded to the millimeter.
     */
    public function getWidthMm(): int
    {
        return (int) round($this->convertPointToMm($this->width), 0);
    }

    /**
     * Sets the page rotation.
     */
    public function setRotation(int $rotation): self
    {
        $this->rotation = $rotation;

        return $this;
    }

    /**
     * Returns the page rotation.
     */
    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * Converts a measure of PostScript points to millimeters.
     */
    private function convertPointToMm(float $point): float
    {
        return $point / 72 * 25.4;
    }
}
