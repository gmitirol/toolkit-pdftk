<?php
/**
 * PDFtk wrapper
 *
 * @copyright 2014-2018 Institute of Legal Medicine, Medical University of Innsbruck
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
     * Set metadata key/value.
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     *
     * @throws PdftkException
     */
    public function set($key, $value)
    {
        $this->checkKey($key);

        $this->metadata[$key] = (string) $value;

        return $this;
    }

    /**
     * Get metadata value from key.
     *
     * @param string $key
     *
     * @return string|bool
     *
     * @throws PdftkException
     */
    public function get($key)
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
     * @param string $key
     *
     * @return self
     *
     * @throws PdftkException
     */
    public function remove($key)
    {
        $this->checkKey($key);

        unset($this->metadata[$key]);

        return $this;
    }

    /**
     * Checks whether a key is set.
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws PdftkException
     */
    public function has($key)
    {
        $this->checkKey($key);

        return isset($this->metadata[$key]);
    }

    /**
     * Returns all current keys.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->metadata);
    }

    /**
     * Returns all metadata entries [key => value].
     *
     * @return array
     */
    public function all()
    {
        return $this->metadata;
    }

    /**
     * Remove all metadata.
     *
     * @return self
     */
    public function clear()
    {
        $this->metadata = [];

        return $this;
    }

    /**
     * Apply metadata to file.
     *
     * @param string $infile
     * @param string $outfile
     *
     * @return self
     *
     * @throws PdftkException
     */
    public function apply($infile, $outfile = null)
    {
        $metaBlock = '';
        foreach ($this->metadata as $k => $v) {
            $metaBlock .= $this->buildInfoBlock($k, $v);
        }
        $this->wrapper->updatePdfDataFromDump($infile, $metaBlock, $outfile);

        return $this;
    }

    /**
     * Imports metadata from a PDF file.
     *
     * @param string $infile
     *
     * @return self
     */
    public function import($infile)
    {
        $dump = $this->wrapper->getPdfDataDump($infile);
        $this->importFromDump($dump);

        return $this;
    }

    /**
     * Imports PDF metadata from a pdftk dump.
     *
     * @param string $dump
     *
     * @return self
     */
    public function importFromDump($dump)
    {
        $matches = [];
        $regex = '/InfoBegin??\r?\nInfoKey: (?<key>.*)??\r?\nInfoValue: (?<value>.*)??\r?\n/';
        preg_match_all($regex, $dump, $matches, PREG_SET_ORDER);

        foreach ($matches as $meta) {
            $this->set($meta['key'], $meta['value']);
        }

        return $this;
    }

    /**
     * Builds an InfoBlock string.
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     *
     * @throws PdftkException
     */
    private function buildInfoBlock($key, $value)
    {
        $this->checkKey($key);

        return
            'InfoBegin' . PHP_EOL .
            'InfoKey: ' . $key . PHP_EOL .
            'InfoValue: ' . (string) $value . PHP_EOL;
    }

    /**
     * Checks a metadata key.
     *
     * @param string $key
     *
     * @throws PdfException
     */
    private function checkKey($key)
    {
        if (!is_string($key) || empty($key)) {
            throw new PdfException(sprintf('Invalid key name "%s"!', $key));
        }
    }
}
