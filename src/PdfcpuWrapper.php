<?php
/**
 * pdfcpu wrapper facade
 *
 * @copyright 2014-2026 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */

namespace Gmi\Toolkit\Pdftk;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

/**
 * Auto-detecting pdfcpu wrapper.
 *
 * Delegates every call to a version-specific wrapper (PdfcpuV11Wrapper or PdfcpuV12Wrapper) chosen
 * by PdfcpuWrapperFactory based on the installed pdfcpu binary's reported version. Use this when
 * the caller does not care which pdfcpu version is installed.
 */
final class PdfcpuWrapper implements WrapperInterface, BinaryPathAwareInterface
{
    /**
     * @var AbstractPdfcpuWrapper
     */
    private $inner;

    /**
     * @var ProcessFactory|null
     */
    private $processFactory;

    /**
     * Constructor.
     *
     * @throws FileNotFoundException if the binary cannot be located
     * @throws PdfException if the binary's version cannot be detected
     */
    public function __construct(string $pdfcpuBinary = null, ProcessFactory $processFactory = null)
    {
        $this->processFactory = $processFactory;
        $this->inner = PdfcpuWrapperFactory::create($pdfcpuBinary, $processFactory);
    }

    /**
     * {@inheritDoc}
     */
    public function setBinary(string $binaryPath): self
    {
        $this->inner = PdfcpuWrapperFactory::create($binaryPath, $this->processFactory);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBinary(bool $escaped = true): string
    {
        return $this->inner->getBinary($escaped);
    }

    /**
     * {@inheritDoc}
     */
    public function join(array $filePaths, string $outfile): void
    {
        $this->inner->join($filePaths, $outfile);
    }

    /**
     * {@inheritDoc}
     */
    public function split(string $infile, array $mapping, string $outputFolder = null): void
    {
        $this->inner->split($infile, $mapping, $outputFolder);
    }

    /**
     * {@inheritDoc}
     */
    public function reorder(string $infile, array $order, string $outfile = null): void
    {
        $this->inner->reorder($infile, $order, $outfile);
    }

    /**
     * {@inheritDoc}
     */
    public function applyBookmarks(Bookmarks $bookmarks, string $infile, string $outfile = null): self
    {
        $this->inner->applyBookmarks($bookmarks, $infile, $outfile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importBookmarks(Bookmarks $bookmarks, string $infile): self
    {
        $this->inner->importBookmarks($bookmarks, $infile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importPages(Pages $pages, string $infile): self
    {
        $this->inner->importPages($pages, $infile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function applyMetadata(Metadata $metadata, string $infile, string $outfile = null): self
    {
        $this->inner->applyMetadata($metadata, $infile, $outfile);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function importMetadata(Metadata $metadata, string $infile): self
    {
        $this->inner->importMetadata($metadata, $infile);

        return $this;
    }
}
