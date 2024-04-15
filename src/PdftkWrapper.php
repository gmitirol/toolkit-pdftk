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

use Symfony\Component\Process\Process;

use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * Wrapper for PDFtk.
 *
 * @internal
 */
class PdftkWrapper
{
    /**
     * @var string pdftk binary including full path
     */
    private $pdftkBinary;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * Constructor.
     *
     * @throws FileNotFoundException
     */
    public function __construct(string $pdftkBinary = null, ProcessFactory $processFactory = null)
    {
        $this->setBinary($pdftkBinary ?: $this->guessBinary(PHP_OS));
        $this->processFactory = $processFactory ?: new ProcessFactory();
    }

    /**
     * Guesses the pdftk binary path based on the operating system.
     */
    public function guessBinary($operatingSystemString): string
    {
        if (strtoupper(substr($operatingSystemString, 0, 3)) === 'WIN') {
            $binary = 'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe';
        } else {
            $binary = '/usr/bin/pdftk';
        }

        return $binary;
    }

    /**
     * Set pdftk binary to use.
     *
     * @throws FileNotFoundException
     */
    public function setBinary(string $binary): self
    {
        if (!file_exists($binary)) {
            throw new FileNotFoundException(sprintf('Binary "%s" not found', $binary));
        }

        $this->pdftkBinary = $binary;

        return $this;
    }

    /**
     * Get current used pdftk binary.
     *
     * @param bool $escaped Whether the binary path should be shell escaped
     */
    public function getBinary(bool $escaped = true): string
    {
        return $escaped ? escapeshellarg($this->pdftkBinary) : $this->pdftkBinary;
    }

    /**
     * Creates a (pdftk) process using the ProcessFactory.
     */
    public function createProcess(string $commandLine): Process
    {
        return $this->processFactory->createProcess($commandLine);
    }

    /**
     * Get data dump.
     *
     * @throws PdfException
     */
    public function getPdfDataDump(string $pdf): string
    {
        if (!file_exists($pdf)) {
            throw new FileNotFoundException(sprintf('PDF "%s" not found', $pdf));
        }

        $tempfile = tempnam(sys_get_temp_dir(), 'pdf');
        $cmd = sprintf(
            '%s %s dump_data_utf8 output %s',
            $this->getBinary(),
            escapeshellarg($pdf),
            escapeshellarg($tempfile)
        );

        $process = $this->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to read PDF data from "%s"! Error: %s', $pdf, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        $dump = file_get_contents($tempfile);
        unlink($tempfile);

        if (isset($exception)) {
            throw $exception;
        }

        return $dump;
    }

    /**
     * Update PDF data from dump.
     *
     * @param string $pdf     input file
     * @param string $data    dump data or filename of containing dump data
     * @param string $outfile output file (is input when null)
     *
     * @throws PdfException
     */
    public function updatePdfDataFromDump(string $pdf, string $data, string $outfile = null): void
    {
        $temporaryOutFile = false;

        if (!file_exists($pdf)) {
            throw new FileNotFoundException(sprintf('PDF "%s" not found', $pdf));
        }

        if ($outfile === null || $pdf === $outfile) {
            $temporaryOutFile = true;
            $outfile = tempnam(sys_get_temp_dir(), 'pdf');
        }

        $tempfile = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tempfile, $data);

        $cmd = sprintf(
            '%s %s update_info_utf8 %s output %s',
            $this->getBinary(),
            escapeshellarg($pdf),
            escapeshellarg($tempfile),
            escapeshellarg($outfile)
        );

        $process = $this->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            $exception = new PdfException(
                sprintf('Failed to write PDF data to "%s"! Error: %s', $outfile, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        unlink($tempfile);

        if ($temporaryOutFile && !isset($exception)) {
            unlink($pdf);
            rename($outfile, $pdf);
        }

        if (isset($exception)) {
            throw $exception;
        }
    }
}
