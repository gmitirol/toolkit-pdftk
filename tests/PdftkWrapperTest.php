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
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

use Exception;

/**
 * This test class tests general methods of the pdftk wrapper.
 *
 * The features implemented from WrapperInterface are unit-tested in individual Pdftk*Test classes.
 */
class PdftkWrapperTest extends TestCase
{
    public function testGuessBinary()
    {
        $pdftk = new PdftkWrapper();
        $this->assertSame('/usr/bin/pdftk', $pdftk->guessBinary('Linux'));
        $this->assertSame('C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe', $pdftk->guessBinary('WINNT'));
        $this->assertSame('C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe', $pdftk->guessBinary('Windows'));
    }

    public function testBinarySetGet()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';

        $pdftk = new PdftkWrapper($binary);

        $this->assertSame(
            $binary,
            $pdftk->getBinary(false)
        );

        $this->assertSame(
            escapeshellarg($binary),
            $pdftk->getBinary(true)
        );

        $this->assertSame(
            $binary,
            $pdftk->setBinary($binary)->getBinary(false)
        );
    }

    public function testBinaryNotFound()
    {
        $binary = __DIR__ . '/Fixtures/missing.sh';

        $this->expectException(FileNotFoundException::Class);
        new PdftkWrapper($binary);
    }

    public function testGetPdfDataDumpException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $exception = $this->getTestException();

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
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to read PDF data from "%s"! Error: %s', $pdf, $exception->getMessage())
        );
        $pdftk->getPdfDataDump($pdf);
    }

    public function testGetPdfDataDumpExceptionHasErrorMessage()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = $this->getTestException();

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pdfOutputMessage);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $pdftk->getPdfDataDump($pdf);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to read PDF data from "%s"! Error: %s', $pdf, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    public function testUpdatePdfDataFromDumpException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $exception = $this->getTestException();

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
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);
        $this->expectException(PdfException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to write PDF data to "%s"! Error: %s', $target, $exception->getMessage())
        );
        $pdftk->updatePdfDataFromDump($pdf, 'Example data', $target);
    }

    public function testUpdatePdfDataFromDumpExceptionHasErrorMessage()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $pdfErrorMessage = 'PDF error message';
        $pdfOutputMessage = 'PDF output message';

        $exception = $this->getTestException();

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('mustRun')
                    ->will($this->throwException($exception));
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->once())
                    ->method('getOutput')
                    ->willReturn($pdfOutputMessage);

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $pdftk->updatePdfDataFromDump($pdf, 'Example data', $target);
        } catch (PdfException $e) {
            $this->assertSame(
                sprintf('Failed to write PDF data to "%s"! Error: %s', $target, $exception->getMessage()),
                $e->getMessage()
            );
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
            $this->assertSame($pdfOutputMessage, $e->getPdfOutput());
            $this->assertSame($exception, $e->getPrevious());
        }
    }

    private function getTestException()
    {
        return new Exception('pdftk exception message');
    }
}
