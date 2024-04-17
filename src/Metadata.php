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
 * Apply metadata to PDF.
 */
class Metadata
{
    /**
     * InfoBlock
     *
     * Creator: if the PDF was converted from another format, the application
     * used to create the original document.
     *
     * Producer: if the PDF was converted from another format, the application
     * which did the conversion.
     *
     * @var array
     */
    private $metadata = [];

    /**
     * @var WrapperInterface
     */
    private $wrapper;

    /**
     * Constructor.
     */
    public function __construct(WrapperInterface $wrapper = null)
    {
        $this->wrapper = $wrapper ?: new PdftkWrapper();
    }

    /**
     * Set metadata key/value.
     *
     * @throws PdftkException
     */
    public function set(string $key, string $value): self
    {
        $this->checkKey($key);

        $this->metadata[$key] = (string) $value;

        return $this;
    }

    /**
     * Get metadata value from key.
     *
     * @return string|bool
     *
     * @throws PdftkException
     */
    public function get(string $key)
    {
        $this->checkKey($key);

        if (!isset($this->metadata[$key])) {
            return false;
        }

        return $this->metadata[$key];
    }

    /**
     * Unset metadata for key.
     *
     * @throws PdftkException
     */
    public function remove(string $key): self
    {
        $this->checkKey($key);

        unset($this->metadata[$key]);

        return $this;
    }

    /**
     * Checks whether a key is set.
     *
     * @throws PdftkException
     */
    public function has(string $key): bool
    {
        $this->checkKey($key);

        return isset($this->metadata[$key]);
    }

    /**
     * Returns all current keys.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->metadata);
    }

    /**
     * Returns all metadata entries [key => value].
     */
    public function all(): array
    {
        return $this->metadata;
    }

    /**
     * Remove all metadata.
     */
    public function clear(): self
    {
        $this->metadata = [];

        return $this;
    }

    /**
     * Apply metadata to file.
     *
     * @throws PdftkException
     */
    public function apply(string $infile, string $outfile = null): self
    {
        $this->wrapper->applyMetadata($this, $infile, $outfile);

        return $this;
    }

    /**
     * Imports metadata from a PDF file.
     *
     * @throws PdfException
     */
    public function import(string $infile): self
    {
        $this->wrapper->importMetadata($this, $infile);

        return $this;
    }

    /**
     * Checks a metadata key.
     *
     * @throws PdfException
     */
    private function checkKey(string $key): void
    {
        if (!is_string($key) || empty($key)) {
            throw new PdfException(sprintf('Invalid key name "%s"!', $key));
        }
    }
}
