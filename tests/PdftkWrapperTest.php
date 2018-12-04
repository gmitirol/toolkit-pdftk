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
namespace Gmi\Toolkit\Pdftk\Tests;

use Symfony\Component\Process\Process;

use PHPUnit\Framework\TestCase;

use Gmi\Toolkit\Pdftk\PdftkWrapper;
use Gmi\Toolkit\Pdftk\Exception\FileNotFoundException;
use Gmi\Toolkit\Pdftk\Exception\PdfException;
use Gmi\Toolkit\Pdftk\Util\ProcessFactory;

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
        $pdftk = new PdftkWrapper($binary);
    }

    public function testGetPdfDataDumpException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('run');
        $mockProcess->expects($this->once())
                    ->method('isSuccessful')
                    ->willReturn(false);
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn('Error');
        $mockProcess->expects($this->never())
                    ->method('getOutput');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);
        $this->expectException(PdfException::class);
        $pdftk->getPdfDataDump($pdf);
    }

    public function testGetPdfDataDumpExceptionHasErrorMessage()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';

        $pdfErrorMessage = 'PDf error message';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('run');
        $mockProcess->expects($this->once())
                    ->method('isSuccessful')
                    ->willReturn(false);
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->never())
                    ->method('getOutput');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $pdftk->getPdfDataDump($pdf);
        } catch (PdfException $e) {
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
        }
    }

    public function testUpdatePdfDataFromDumpException()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('run');
        $mockProcess->expects($this->once())
                    ->method('isSuccessful')
                    ->willReturn(false);
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn('Error');
        $mockProcess->expects($this->never())
                    ->method('getOutput');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);
        $this->expectException(PdfException::class);
        $pdftk->updatePdfDataFromDump($pdf, 'Example data', $target);
    }

    public function testUpdatePdfDataFromDumpExceptionHasErrorMessage()
    {
        $binary = __DIR__ . '/Fixtures/binary.sh';
        $pdf = __DIR__ . '/Fixtures/example.pdf';
        $target = tempnam(sys_get_temp_dir(), 'pdf');

        $pdfErrorMessage = 'PDf error message';

        $mockProcess = $this->createMock(Process::class);
        $mockProcess->expects($this->once())
                    ->method('run');
        $mockProcess->expects($this->once())
                    ->method('isSuccessful')
                    ->willReturn(false);
        $mockProcess->expects($this->once())
                    ->method('getErrorOutput')
                    ->willReturn($pdfErrorMessage);
        $mockProcess->expects($this->never())
                    ->method('getOutput');

        $mockProcessFactory = $this->createMock(ProcessFactory::class);
        $mockProcessFactory->expects($this->once())
                           ->method('createProcess')
                           ->with($this->isType('string'))
                           ->willReturn($mockProcess);

        $pdftk = new PdftkWrapper($binary, $mockProcessFactory);

        try {
            $pdftk->updatePdfDataFromDump($pdf, 'Example data', $target);
        } catch (PdfException $e) {
            $this->assertSame($pdfErrorMessage, $e->getPdfError());
        }
    }
}
