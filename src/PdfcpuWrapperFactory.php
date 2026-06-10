<?php
/**
 * pdfcpu wrapper factory
 *
 * @copyright 2014-2026 Institute of Legal Medicine, Medical University of Innsbruck
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
use Gmi\Toolkit\Pdftk\Util\FileChecker;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * Detects the installed pdfcpu version and instantiates the matching wrapper.
 *
 * Versions up to and including 0.11.x use single-dash long flags and are handled by PdfcpuV11Wrapper.
 * Versions 0.12.x and newer (including 0.13.x) adopted Cobra and use POSIX-style long flags; they are
 * handled by PdfcpuV12Wrapper.
 */
final class PdfcpuWrapperFactory
{
    /**
     * Instantiates the wrapper matching the version reported by the pdfcpu binary.
     *
     * @throws FileNotFoundException if the binary cannot be located
     * @throws PdfException if the version cannot be detected or parsed
     */
    public static function create(
        string $binary = null,
        ProcessFactory $processFactory = null
    ): AbstractPdfcpuWrapper {
        $resolvedBinary = $binary ?: self::guessBinary(PHP_OS);
        $resolvedProcessFactory = $processFactory ?: new ProcessFactory();

        (new FileChecker())->checkFileExists($resolvedBinary, 'Binary "%s" not found!');

        $version = self::detectVersion($resolvedBinary, $resolvedProcessFactory);

        if ($version['major'] === 0 && $version['minor'] < 12) {
            return new PdfcpuV11Wrapper($resolvedBinary, $resolvedProcessFactory);
        }

        return new PdfcpuV12Wrapper($resolvedBinary, $resolvedProcessFactory);
    }

    /**
     * Returns the parsed major and minor version of the given pdfcpu binary.
     *
     * @return array{major: int, minor: int}
     *
     * @throws PdfException if the binary cannot be executed or its version cannot be parsed
     */
    public static function detectVersion(
        string $binary,
        ProcessFactory $processFactory = null
    ): array {
        $resolvedProcessFactory = $processFactory ?: new ProcessFactory();

        $cmd = sprintf('%s version', escapeshellarg($binary));

        /**
         * @var Process
         */
        $process = $resolvedProcessFactory->createProcess($cmd);

        try {
            $process->mustRun();
        } catch (Exception $e) {
            throw new PdfException(
                sprintf('Failed to detect pdfcpu version from "%s"! Error: %s', $binary, $e->getMessage()),
                0,
                $e,
                $process->getErrorOutput(),
                $process->getOutput()
            );
        }

        $output = $process->getOutput();

        // Up to v0.12 pdfcpu prints the version on a line like "pdfcpu: v0.12.1 dev" (v0.12 also prints a
        // multi-line config-mismatch banner before it). v0.13 reworked the output into a key-aligned block
        // led by "version: 0.13.0" (no "v" prefix). Match either form anywhere in the output.
        if (preg_match('/^(?:pdfcpu:\s*v|version:\s*v?)(\d+)\.(\d+)/m', $output, $matches) !== 1) {
            throw new PdfException(
                sprintf('Failed to parse pdfcpu version from "%s"!', $binary),
                0,
                null,
                $process->getErrorOutput(),
                $output
            );
        }

        return ['major' => (int) $matches[1], 'minor' => (int) $matches[2]];
    }

    /**
     * Guesses the pdfcpu binary path based on the operating system.
     */
    private static function guessBinary(string $operatingSystemString): string
    {
        if (strtoupper(substr($operatingSystemString, 0, 3)) === 'WIN') {
            return 'C:\\Program Files\\pdfcpu\\pdfcpu.exe';
        }

        return '/usr/bin/pdfcpu';
    }
}
