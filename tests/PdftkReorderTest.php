<?php
/**
 * PDFtk wrapper reorder test
 *
 * @copyright 2014-2024 Institute of Legal Medicine, Medical University of Innsbruck
 * @author Martin Pircher <martin.pircher@i-med.ac.at>
 * @author Andreas Erhard <andreas.erhard@i-med.ac.at>
 * @license LGPL-3.0-only
 * @link http://www.gerichtsmedizin.at/
 *
 * @package pdftk
 */
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

class PdftkReorderTest extends TestCase
{
    public function testReorderException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $exception = new Exception('Error message');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn('Error');
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn('Output');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with("'$binary' '/path/to/input' cat 3 1 2 output '/path/to/output.pdf'")
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $wrapper->reorder('/path/to/input', [3, 1, 2], '/path/to/output.pdf');
        } catch (PdfException $e) {
            $msg = sprintf('Failed to reorder PDF "%s"! Error: Error message', '/path/to/input');
            $this->assertSame($msg, $e->getMessage());
            $this->assertSame($exception, $e->getPrevious());
            $this->assertSame('Error', $e->getPdfError());
            $this->assertSame('Output', $e->getPdfOutput());
        }
    }

    public function testReorder()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with("'$binary' '/path/to/input' cat 3 1 2 output '/path/to/output.pdf'")
                           ->willReturn($mockProcess);

        $wrapper = new PdftkWrapper($binary, $mockProcessFactory);

        $wrapper->reorder('/path/to/input', [3, 1, 2], '/path/to/output.pdf');
    }
}
